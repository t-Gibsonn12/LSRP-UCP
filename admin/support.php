<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$dbReady = true;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    $status = (string)($_POST['status'] ?? 'open');
    $adminNote = trim((string)($_POST['admin_note'] ?? ''));
    $allowed = ['open', 'in_progress', 'closed'];

    if ($ticketId > 0 && in_array($status, $allowed, true)) {
        try {
            $stmt = db()->prepare("UPDATE ucp_support_requests SET status = ?, admin_note = ?, updated_at = NOW() WHERE ticket_id = ?");
            $stmt->execute([$status, $adminNote ?: null, $ticketId]);
            admin_log('support_update', 'support_ticket', $ticketId, ['status' => $status]);
            flash('success', 'Đã cập nhật ticket hỗ trợ #' . $ticketId . '.');
            redirect('admin/support.php');
        } catch (Throwable $e) {
            $error = 'Không thể cập nhật ticket. Kiểm tra database UCP V4.';
        }
    }
}

$rows = [];
try {
    $rows = db()->query(
        "SELECT sr.*, pa.username, pa.email
         FROM ucp_support_requests sr
         LEFT JOIN player_accounts pa ON pa.account_id = sr.account_id
         ORDER BY FIELD(sr.status, 'open', 'in_progress', 'closed'), sr.updated_at DESC, sr.ticket_id DESC"
    )->fetchAll();
} catch (Throwable $e) {
    $dbReady = false;
    $error = 'Chưa cài bảng ucp_support_requests. Import database/INSTALL_UCP_V4.sql.';
}

$statusLabel = static function (string $status): string {
    return match ($status) {
        'in_progress' => 'Đang xử lý',
        'closed' => 'Đã đóng',
        default => 'Đang mở',
    };
};

$pageTitle = 'Support Center';
require dirname(__DIR__) . '/partials/header.php';
?>
<main class="page-shell">
    <div class="page-heading">
        <div><span class="eyebrow">ADMIN · SUPPORT CENTER</span><h1>Yêu cầu hỗ trợ</h1><p>Phản hồi các ticket từ Master Account.</p></div>
        <a class="btn outline" href="<?= e(url('admin/index.php')) ?>">← QUẢN TRỊ</a>
    </div>

    <?php if ($error): ?><div class="form-error"><?= e($error) ?></div><?php endif; ?>

    <section class="admin-ticket-list">
        <?php foreach ($rows as $row): ?>
            <article class="admin-ticket">
                <div class="admin-ticket-meta">
                    <div>
                        <span class="eyebrow">TICKET #<?= (int)$row['ticket_id'] ?></span>
                        <h2><?= e($row['subject']) ?></h2>
                        <p><?= e($row['username'] ?? ('Account #' . $row['account_id'])) ?> · <?= e($row['email'] ?? 'Chưa có email') ?> · <?= e(date('d/m/Y H:i', strtotime($row['created_at']))) ?></p>
                    </div>
                    <span class="badge <?= $row['status'] === 'closed' ? 'success' : ($row['status'] === 'in_progress' ? 'warning' : 'danger') ?>"><?= e($statusLabel($row['status'])) ?></span>
                </div>
                <div class="admin-ticket-message"><?= nl2br(e($row['message'])) ?></div>
                <form method="post" class="admin-ticket-form">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="ticket_id" value="<?= (int)$row['ticket_id'] ?>">
                    <label><span>Trạng thái</span><select name="status"><option value="open" <?= $row['status'] === 'open' ? 'selected' : '' ?>>Đang mở</option><option value="in_progress" <?= $row['status'] === 'in_progress' ? 'selected' : '' ?>>Đang xử lý</option><option value="closed" <?= $row['status'] === 'closed' ? 'selected' : '' ?>>Đã đóng</option></select></label>
                    <label><span>Phản hồi Ban quản trị</span><textarea name="admin_note" rows="4" placeholder="Nội dung phản hồi cho người chơi..."><?= e((string)($row['admin_note'] ?? '')) ?></textarea></label>
                    <button class="btn primary" <?= !$dbReady ? 'disabled' : '' ?>>LƯU PHẢN HỒI <span>→</span></button>
                </form>
            </article>
        <?php endforeach; ?>
        <?php if (!$rows && $dbReady): ?><div class="empty-state"><h2>Không có ticket hỗ trợ.</h2></div><?php endif; ?>
    </section>
</main>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
