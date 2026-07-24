<?php
require __DIR__ . '/app/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'mark_all_read') {
        ucp_notification_mark_all_read((int)current_account_id());
        flash('success', 'Đã đánh dấu tất cả thông báo là đã đọc.');
        redirect('notifications.php');
    }
}

$notifications = ucp_notifications_for_account((int)current_account_id(), 50);
$unreadCount = ucp_notification_unread_count((int)current_account_id());
$pageTitle = 'Thông báo';
require __DIR__ . '/partials/header.php';
?>
<main class="page-shell narrow-page notification-page">
    <div class="page-heading">
        <div>
            <span class="eyebrow">NOTIFICATION CENTER</span>
            <h1>Thông báo</h1>
            <p>Mọi cập nhật quan trọng của Master Account và nhân vật được lưu tại đây.</p>
        </div>
        <?php if ($unreadCount > 0): ?>
            <form method="post">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <button class="btn outline" type="submit" name="action" value="mark_all_read">ĐÁNH DẤU TẤT CẢ ĐÃ ĐỌC</button>
            </form>
        <?php endif; ?>
    </div>

    <section class="notification-center-card">
        <div class="notification-center-summary">
            <span>CHƯA ĐỌC</span>
            <strong><?= (int)$unreadCount ?></strong>
            <small>Tổng <?= count($notifications) ?> thông báo gần nhất</small>
        </div>

        <div class="notification-center-list">
            <?php if (!$notifications): ?>
                <div class="notification-center-empty">
                    <span>◇</span>
                    <h2>Chưa có thông báo.</h2>
                    <p>Thông báo gửi hồ sơ, phê duyệt nhân vật và các cập nhật #TWOYEARS sẽ được lưu tại đây.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <a href="<?= e(url('notification.php?id=' . (int)$notification['notification_id'])) ?>" class="notification-center-item <?= (int)$notification['is_read'] === 0 ? 'unread' : '' ?>">
                        <span class="notification-center-item-inner">
                            <span class="notification-center-icon"><?= str_contains((string)$notification['type'], 'approved') ? '✓' : (str_contains((string)$notification['type'], 'submitted') ? '↑' : '#') ?></span>
                            <span class="notification-center-copy">
                                <span class="eyebrow"><?= e(strtoupper(str_replace('_', ' ', (string)$notification['type']))) ?></span>
                                <strong><?= e((string)$notification['title']) ?></strong>
                                <small><?= e((string)$notification['message']) ?></small>
                                <em><?= e(date('d/m/Y · H:i', strtotime((string)$notification['created_at']))) ?></em>
                            </span>
                            <span class="notification-center-action">XEM CHI TIẾT →</span>
                        </span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
