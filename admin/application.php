<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));

$stmt = db()->prepare(
    "SELECT ca.*, pa.username
     FROM character_applications ca
     LEFT JOIN player_accounts pa ON pa.account_id = ca.account_id
     WHERE ca.application_id = ? LIMIT 1"
);
$stmt->execute([$id]);
$app = $stmt->fetch();

if (!$app) exit('Không tìm thấy hồ sơ.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $note = trim((string)($_POST['admin_note'] ?? ''));

    if ($app['status'] !== 'pending') {
        flash('warning', 'Hồ sơ này đã được xử lý.');
        redirect('admin/application.php?id=' . $id);
    }

    if ($action === 'reject') {
        $stmt = db()->prepare(
            "UPDATE character_applications
             SET status='rejected', admin_note=?, reviewed_by=?, reviewed_at=NOW()
             WHERE application_id=? AND status='pending'"
        );
        $stmt->execute([$note, current_account_id(), $id]);
        admin_log('character_application.reject', 'application', $id, [
            'character_name' => $app['character_name'],
            'account_id' => (int)$app['account_id'],
            'note' => $note
        ]);
        flash('success', 'Đã từ chối hồ sơ.');
        redirect('admin/applications.php');
    }

    if ($action === 'approve') {
        $pdo = db();
        $characterId = 0;
        $locked = null;

        try {
            // IMPORTANT: all CREATE TABLE helpers run BEFORE the transaction.
            // MySQL DDL causes an implicit commit, which was the reason for:
            // "There is no active transaction" in V4.5/V4.5.1.
            if (!ucp_ensure_notifications_table()) {
                throw new RuntimeException('Không thể chuẩn bị Notification Center.');
            }
            if (!twoyears_ensure_tables()) {
                throw new RuntimeException('Không thể chuẩn bị dữ liệu #TWOYEARS.');
            }
            if (!twoyears_ensure_vehicle_table()) {
                throw new RuntimeException('Không thể chuẩn bị bảng player_vehicles.');
            }

            // Transaction ONLY contains character/application rows. Reward and
            // notification are handled after commit, so helper DDL can never kill it.
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT * FROM character_applications WHERE application_id=? FOR UPDATE");
            $stmt->execute([$id]);
            $locked = $stmt->fetch();

            if (!$locked || $locked['status'] !== 'pending') {
                throw new RuntimeException('Hồ sơ đã được xử lý.');
            }

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM player_characters WHERE account_id=?");
            $stmt->execute([(int)$locked['account_id']]);
            if ((int)$stmt->fetchColumn() >= (int)$GLOBALS['config']['max_characters']) {
                throw new RuntimeException('Master Account đã đủ 3 nhân vật.');
            }

            $stmt = $pdo->prepare("SELECT character_id FROM player_characters WHERE account_id=? AND slot=? LIMIT 1");
            $stmt->execute([(int)$locked['account_id'], (int)$locked['slot']]);
            if ($stmt->fetch()) throw new RuntimeException('Slot nhân vật đã bị chiếm.');

            $stmt = $pdo->prepare("SELECT character_id FROM player_characters WHERE name=? LIMIT 1");
            $stmt->execute([$locked['character_name']]);
            if ($stmt->fetch()) throw new RuntimeException('Tên nhân vật đã tồn tại.');

            $stmt = $pdo->prepare(
                "INSERT INTO player_characters
                (account_id, slot, name, skin, cash, bank, level, health, armour,
                 pos_x, pos_y, pos_z, pos_a, interior_id, virtual_world, character_created)
                VALUES (?, ?, ?, 26, 500, 0, 1, 100.0, 0.0,
                        2495.3633, -1687.3105, 13.5156, 0.0, 0, 0, 0)"
            );
            $stmt->execute([
                (int)$locked['account_id'],
                (int)$locked['slot'],
                $locked['character_name']
            ]);
            $characterId = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare(
                "UPDATE character_applications
                 SET status='approved', admin_note=?, reviewed_by=?, reviewed_at=NOW()
                 WHERE application_id=? AND status='pending'"
            );
            $stmt->execute([$note, current_account_id(), $id]);
            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('Hồ sơ đã được xử lý bởi phiên khác.');
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('danger', 'Không thể duyệt: ' . $e->getMessage());
            redirect('admin/application.php?id=' . $id);
        }

        // Character approval is now safely committed. From here on, reward and
        // notification cannot make the approval transaction disappear.
        $accountId = (int)$locked['account_id'];
        $characterName = (string)$locked['character_name'];
        $twoYearsReward = null;
        $vehicleGrant = ['granted' => false, 'vehicle_id' => null, 'reason' => 'not_eligible'];

        if (twoyears_account_package($accountId)) {
            $twoYearsReward = twoyears_queue_character_reward($accountId, $characterId);
            if ($twoYearsReward) {
                $vehicleGrant = twoyears_grant_faggio($accountId, $characterId);
            }
        }

        $notificationId = null;
        if ($twoYearsReward) {
            $plate = (string)($vehicleGrant['plate'] ?? twoyears_vehicle_plate($characterId));
            $granted = (bool)($vehicleGrant['granted'] ?? false);
            $message = str_replace('_', ' ', $characterName) . ' đã được Ban quản trị phê duyệt. ';
            if ($granted) {
                $message .= 'Nhân vật đã nhận 01 Faggio model 462 #TWOYEARS, biển số ' . $plate . '.';
            } else {
                $message .= 'Quyền lợi #TWOYEARS đã được ghi nhận. Hệ thống chưa thể cấp Faggio tự động; Ban quản trị có thể chạy SQL repair V4.5.3 để cấp lại.';
            }

            $notificationId = ucp_notification_create(
                $accountId,
                'character_approved',
                $granted ? 'Nhân vật đã được duyệt · Đã nhận #TWOYEARS' : 'Nhân vật đã được duyệt · #TWOYEARS',
                $message,
                'character.php?id=' . $characterId,
                'Xem thông tin nhân vật',
                'twoyears-character-approved-' . $characterId,
                [
                    'application_id' => $id,
                    'character_id' => $characterId,
                    'character_name' => $characterName,
                    'slot' => (int)$locked['slot'],
                    'vehicle_model' => 462,
                    'vehicle_id' => !empty($vehicleGrant['vehicle_id']) ? (int)$vehicleGrant['vehicle_id'] : 0,
                    'vehicle_plate' => $plate,
                    'vehicle_granted' => $granted,
                    'vehicle_error' => (string)($vehicleGrant['error'] ?? $vehicleGrant['reason'] ?? '')
                ]
            );
        } else {
            $notificationId = ucp_notification_create(
                $accountId,
                'character_approved_basic',
                'Nhân vật đã được phê duyệt',
                str_replace('_', ' ', $characterName) . ' đã được Ban quản trị phê duyệt và Character Slot đã được tạo.',
                'character.php?id=' . $characterId,
                'Xem thông tin nhân vật',
                'character-approved-' . $characterId,
                [
                    'application_id' => $id,
                    'character_id' => $characterId,
                    'character_name' => $characterName,
                    'slot' => (int)$locked['slot']
                ]
            );
        }

        admin_log('character_application.approve', 'application', $id, [
            'character_id' => $characterId,
            'character_name' => $characterName,
            'account_id' => $accountId,
            'slot' => (int)$locked['slot'],
            'twoyears_reward_recorded' => (bool)$twoYearsReward,
            'twoyears_faggio_granted' => (bool)($vehicleGrant['granted'] ?? false),
            'twoyears_vehicle_id' => $vehicleGrant['vehicle_id'] ?? null,
            'notification_id' => $notificationId,
            'vehicle_reason' => $vehicleGrant['reason'] ?? null,
            'vehicle_error' => $vehicleGrant['error'] ?? null
        ]);

        if (!$notificationId) {
            flash('warning', 'Đã duyệt nhân vật nhưng Notification Center chưa lưu được thông báo. Hãy chạy SQL repair V4.5.3.');
        } elseif ($twoYearsReward && !($vehicleGrant['granted'] ?? false)) {
            $detail = trim((string)($vehicleGrant['error'] ?? ''));
            flash('warning', 'Đã duyệt và đã gửi thông báo, nhưng Faggio chưa được cấp.' . ($detail !== '' ? ' Lỗi xe: ' . $detail : ' Hãy chạy SQL repair V4.5.3.'));
        } else {
            $rewardText = ($twoYearsReward && ($vehicleGrant['granted'] ?? false))
                ? ' Faggio #TWOYEARS đã được cấp vào player_vehicles.'
                : '';
            flash('success', 'Đã duyệt hồ sơ và gửi thông báo cho người chơi.' . $rewardText);
        }

        redirect('admin/character.php?id=' . $characterId);
    }
}

$pageTitle = 'Xem hồ sơ nhân vật';
require dirname(__DIR__) . '/partials/header.php';
?>
<main class="page-shell narrow-page">
    <a class="back-link" href="<?= e(url('admin/applications.php')) ?>">← DANH SÁCH HỒ SƠ</a>

    <section class="application-review">
        <div class="review-header">
            <div>
                <span class="eyebrow">APPLICATION #<?= (int)$app['application_id'] ?> · SLOT 0<?= (int)$app['slot'] ?></span>
                <h1><?= e(str_replace('_', ' ', $app['character_name'])) ?></h1>
                <p>Master Account: <strong><?= e($app['username'] ?? ('#' . $app['account_id'])) ?></strong></p>
            </div>
            <span class="badge <?= e(application_status_class($app['status'])) ?>"><?= e(application_status_name($app['status'])) ?></span>
        </div>

        <div class="review-section"><span>CHARACTER CONCEPT</span><p><?= nl2br(e($app['concept'])) ?></p></div>
        <div class="review-section"><span>BACKGROUND</span><p><?= nl2br(e($app['background'])) ?></p></div>
        <div class="review-section"><span>ROLEPLAY GOAL</span><p><?= nl2br(e($app['roleplay_goal'])) ?></p></div>

        <?php if ($app['status'] === 'pending'): ?>
        <form method="post" class="review-actions">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= (int)$app['application_id'] ?>">
            <label><span>Ghi chú Admin</span><textarea name="admin_note" rows="4" placeholder="Lý do duyệt / từ chối hoặc ghi chú cho người chơi..."></textarea></label>
            <div class="button-row">
                <button class="btn danger" name="action" value="reject">TỪ CHỐI</button>
                <button class="btn primary" name="action" value="approve">DUYỆT & TẠO CHARACTER</button>
            </div>
        </form>
        <?php elseif (!empty($app['admin_note'])): ?>
            <div class="admin-note"><strong>Ghi chú:</strong> <?= e($app['admin_note']) ?></div>
        <?php endif; ?>
    </section>
</main>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
