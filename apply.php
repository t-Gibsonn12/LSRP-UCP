<?php
require __DIR__ . '/app/bootstrap.php';
require_login();

$pdo = db();
$stmt = $pdo->prepare("SELECT slot FROM player_characters WHERE account_id = ?");
$stmt->execute([current_account_id()]);
$usedSlots = array_map('intval', array_column($stmt->fetchAll(), 'slot'));

try {
    $stmt = $pdo->prepare("SELECT slot FROM character_applications WHERE account_id = ? AND status = 'pending'");
    $stmt->execute([current_account_id()]);
    $pendingSlots = array_map('intval', array_column($stmt->fetchAll(), 'slot'));
} catch (Throwable $e) {
    $pendingSlots = [];
}

$availableSlots = [];
for ($i = 1; $i <= (int)$GLOBALS['config']['max_characters']; $i++) {
    if (!in_array($i, $usedSlots, true) && !in_array($i, $pendingSlots, true)) {
        $availableSlots[] = $i;
    }
}

$errors = [];
$requestedSlot = (int)($_GET['slot'] ?? ($_POST['slot'] ?? ($availableSlots[0] ?? 0)));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $slot = (int)($_POST['slot'] ?? 0);
    $name = trim((string)($_POST['character_name'] ?? ''));
    $concept = trim((string)($_POST['concept'] ?? ''));
    $background = trim((string)($_POST['background'] ?? ''));
    $goal = trim((string)($_POST['roleplay_goal'] ?? ''));

    if (!in_array($slot, $availableSlots, true)) $errors[] = 'Slot này không còn khả dụng.';
    if (!valid_character_name($name)) $errors[] = 'Tên nhân vật phải theo dạng Firstname_Lastname, chỉ dùng chữ cái.';
    if (mb_strlen($concept) < 30) $errors[] = 'Concept cần ít nhất 30 ký tự.';
    if (mb_strlen($background) < 100) $errors[] = 'Background cần ít nhất 100 ký tự.';
    if (mb_strlen($goal) < 50) $errors[] = 'Mục tiêu roleplay cần ít nhất 50 ký tự.';

    if (!$errors) {
        $stmt = $pdo->prepare("SELECT character_id FROM player_characters WHERE name = ? LIMIT 1");
        $stmt->execute([$name]);
        if ($stmt->fetch()) $errors[] = 'Tên nhân vật này đã tồn tại.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare("SELECT application_id FROM character_applications WHERE character_name = ? AND status = 'pending' LIMIT 1");
        $stmt->execute([$name]);
        if ($stmt->fetch()) $errors[] = 'Tên nhân vật này đang có một hồ sơ chờ duyệt.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            "INSERT INTO character_applications
             (account_id, slot, character_name, concept, background, roleplay_goal, status)
             VALUES (?, ?, ?, ?, ?, ?, 'pending')"
        );
        $stmt->execute([current_account_id(), $slot, $name, $concept, $background, $goal]);
        $applicationId = (int)$pdo->lastInsertId();

        ucp_notification_create(
            (int)current_account_id(),
            'character_application_submitted',
            'Đã gửi yêu cầu tạo nhân vật',
            'Hồ sơ ' . str_replace('_', ' ', $name) . ' đã được gửi tới Ban quản trị và đang chờ phê duyệt.',
            'applications.php',
            'Xem nhân vật chờ duyệt',
            'character-application-submitted-' . $applicationId,
            ['application_id' => $applicationId, 'character_name' => $name, 'slot' => $slot]
        );

        flash('success', 'Hồ sơ nhân vật đã được gửi tới Ban quản trị. Thông báo đã được lưu vào Notification Center.');
        redirect('applications.php');
    }
}

$pageTitle = 'Gửi yêu cầu tạo nhân vật';
require __DIR__ . '/partials/header.php';
?>
<main class="page-shell narrow-page">
    <a class="back-link" href="<?= e(url('characters.php')) ?>">← QUAY LẠI</a>
    <div class="page-heading">
        <div><span class="eyebrow">CHARACTER APPLICATION</span><h1>Gửi yêu cầu tạo nhân vật</h1><p>Hồ sơ được Ban quản trị xem xét trước khi slot nhân vật được tạo.</p></div>
    </div>

    <?php if (!$availableSlots): ?>
        <div class="empty-state"><h2>Không còn slot khả dụng.</h2><p>Bạn đã có đủ nhân vật hoặc hồ sơ đang chờ duyệt.</p></div>
    <?php else: ?>
        <?php foreach ($errors as $error): ?><div class="form-error"><?= e($error) ?></div><?php endforeach; ?>

        <form method="post" class="panel-form">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <div class="form-two">
                <label><span>Slot nhân vật</span>
                    <select name="slot">
                        <?php foreach ($availableSlots as $slot): ?>
                            <option value="<?= $slot ?>" <?= $requestedSlot === $slot ? 'selected' : '' ?>>Slot 0<?= $slot ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><span>Tên nhân vật</span><input name="character_name" maxlength="24" placeholder="Michael_Johnson" required value="<?= old('character_name') ?>"></label>
            </div>

            <label><span>Character Concept</span><textarea name="concept" rows="4" required placeholder="Nhân vật là ai, thuộc tầng lớp nào, đang sống thế nào tại Los Santos?"><?= old('concept') ?></textarea></label>
            <label><span>Background</span><textarea name="background" rows="9" required placeholder="Quá khứ, gia đình, biến cố, lý do tới Los Santos..."><?= old('background') ?></textarea></label>
            <label><span>Mục tiêu Roleplay</span><textarea name="roleplay_goal" rows="6" required placeholder="Bạn muốn phát triển nhân vật theo hướng nào?"><?= old('roleplay_goal') ?></textarea></label>

            <button class="btn primary">GỬI HỒ SƠ →</button>
        </form>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
