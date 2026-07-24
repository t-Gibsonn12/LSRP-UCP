<?php
require __DIR__ . '/app/bootstrap.php';
require_login();

$errors = [];
$dbReady = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $category = (string)($_POST['category'] ?? 'account');
    $subject = trim((string)($_POST['subject'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));
    $allowedCategories = ['account', 'character', 'technical', 'other'];

    if (!in_array($category, $allowedCategories, true)) $errors[] = 'Danh mục hỗ trợ không hợp lệ.';
    if (mb_strlen($subject) < 5 || mb_strlen($subject) > 120) $errors[] = 'Tiêu đề cần từ 5 đến 120 ký tự.';
    if (mb_strlen($message) < 30 || mb_strlen($message) > 5000) $errors[] = 'Nội dung cần từ 30 đến 5000 ký tự.';

    if (!$errors) {
        try {
            $stmt = db()->prepare("INSERT INTO ucp_support_requests (account_id, category, subject, message, status) VALUES (?, ?, ?, ?, 'open')");
            $stmt->execute([current_account_id(), $category, $subject, $message]);
            flash('success', 'Yêu cầu hỗ trợ đã được gửi tới Ban quản trị.');
            redirect('support.php');
        } catch (Throwable $e) {
            $errors[] = 'Chưa cài bảng hỗ trợ UCP V4. Hãy import database/INSTALL_UCP_V4.sql.';
            $dbReady = false;
        }
    }
}

$tickets = [];
try {
    $stmt = db()->prepare("SELECT * FROM ucp_support_requests WHERE account_id = ? ORDER BY created_at DESC, ticket_id DESC");
    $stmt->execute([current_account_id()]);
    $tickets = $stmt->fetchAll();
} catch (Throwable $e) {
    $dbReady = false;
}

$statusLabel = static function (string $status): string {
    return match ($status) {
        'in_progress' => 'Đang xử lý',
        'closed' => 'Đã đóng',
        default => 'Đang mở',
    };
};
$statusClass = static function (string $status): string {
    return match ($status) {
        'in_progress' => 'warning',
        'closed' => 'success',
        default => 'danger',
    };
};

$pageTitle = 'Yêu cầu hỗ trợ';
require __DIR__ . '/partials/header.php';
?>
<main class="page-shell">
    <div class="page-heading">
        <div><span class="eyebrow">SUPPORT CENTER</span><h1>Yêu cầu hỗ trợ</h1><p>Gửi vấn đề về tài khoản, nhân vật hoặc kỹ thuật cho Ban quản trị.</p></div>
        <a class="btn outline" href="<?= e(url('account.php')) ?>">← TÀI KHOẢN</a>
    </div>

    <section class="support-layout">
        <article class="detail-card support-compose">
            <span class="eyebrow">NEW REQUEST</span>
            <h2>Tạo ticket hỗ trợ</h2>
            <p class="muted">Mô tả rõ vấn đề để Ban quản trị xử lý nhanh hơn.</p>

            <?php if (!$dbReady): ?><div class="form-error">Database support chưa sẵn sàng. Import <code>database/INSTALL_UCP_V4.sql</code>.</div><?php endif; ?>
            <?php foreach ($errors as $error): ?><div class="form-error"><?= e($error) ?></div><?php endforeach; ?>

            <form method="post" class="form-grid">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <label><span>Danh mục</span><select name="category"><option value="account">Tài khoản</option><option value="character">Nhân vật</option><option value="technical">Kỹ thuật</option><option value="other">Khác</option></select></label>
                <label><span>Tiêu đề</span><input name="subject" maxlength="120" required value="<?= old('subject') ?>" placeholder="Ví dụ: Không thể truy cập nhân vật"></label>
                <label><span>Nội dung</span><textarea name="message" rows="9" maxlength="5000" required placeholder="Mô tả chi tiết vấn đề, thời điểm xảy ra và những gì bạn đã thử..."><?= old('message') ?></textarea></label>
                <button class="btn primary" <?= !$dbReady ? 'disabled' : '' ?>>GỬI YÊU CẦU <span>→</span></button>
            </form>
        </article>

        <div class="support-list">
            <div class="section-heading"><div><span class="eyebrow">YOUR REQUESTS</span><h2>Ticket của bạn</h2></div></div>
            <?php foreach ($tickets as $ticket): ?>
                <article class="support-ticket">
                    <div class="support-ticket-head">
                        <span class="badge <?= e($statusClass($ticket['status'])) ?>"><?= e($statusLabel($ticket['status'])) ?></span>
                        <small>#<?= (int)$ticket['ticket_id'] ?> · <?= e(date('d/m/Y H:i', strtotime($ticket['created_at']))) ?></small>
                    </div>
                    <h3><?= e($ticket['subject']) ?></h3>
                    <p><?= nl2br(e($ticket['message'])) ?></p>
                    <?php if (!empty($ticket['admin_note'])): ?><div class="support-reply"><b>Phản hồi Ban quản trị</b><p><?= nl2br(e($ticket['admin_note'])) ?></p></div><?php endif; ?>
                </article>
            <?php endforeach; ?>
            <?php if (!$tickets): ?><div class="empty-state"><h2>Chưa có ticket.</h2><p>Các yêu cầu hỗ trợ của bạn sẽ xuất hiện tại đây.</p></div><?php endif; ?>
        </div>
    </section>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
