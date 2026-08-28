<?php
$flashes = pull_flashes();
$pageTitle = $pageTitle ?? $GLOBALS['config']['app_name'];
$bodyClass = $bodyClass ?? '';
$currentFile = basename($_SERVER['PHP_SELF'] ?? '');
$isAdminPage = str_contains(str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? ''), '/admin/');
$account = current_account();
$initial = $account ? strtoupper(mb_substr((string)$account['username'], 0, 1)) : 'LS';

$notifications = [];
$notificationUnreadCount = 0;
if (is_logged_in()) {
    $notificationAccountId = (int)current_account_id();
    $notifications = ucp_notifications_for_account($notificationAccountId, 8);
    $notificationUnreadCount = ucp_notification_unread_count($notificationAccountId);
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#07090d">
    <title><?= e($pageTitle) ?> · <?= e($GLOBALS['config']['app_name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('public/css/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('public/css/v5.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<div class="noise"></div>

<?php if (is_logged_in()): ?>
<header class="topbar">
    <a class="brand" href="<?= e(url('dashboard.php')) ?>" aria-label="Los Santos Roleplay UCP">
        <span class="brand-mark">LS</span>
        <span class="brand-copy"><strong>LOS SANTOS</strong><small>ROLEPLAY · UCP V5</small></span>
    </a>

    <button class="mobile-nav-toggle" type="button" data-mobile-nav aria-label="Mở menu" aria-expanded="false">☰</button>

    <nav class="desktop-nav" data-main-nav>
        <a class="<?= $currentFile === 'dashboard.php' && !$isAdminPage ? 'active' : '' ?>" href="<?= e(url('dashboard.php')) ?>">Tổng quan</a>
        <a class="<?= in_array($currentFile, ['news.php', 'news-view.php'], true) && !$isAdminPage ? 'active' : '' ?>" href="<?= e(url('news.php')) ?>">Tin tức</a>
        <a class="<?= in_array($currentFile, ['characters.php', 'character.php', 'applications.php', 'apply.php'], true) && !$isAdminPage ? 'active' : '' ?>" href="<?= e(url('characters.php')) ?>">Nhân vật</a>
        <a class="<?= $currentFile === 'about.php' && !$isAdminPage ? 'active' : '' ?>" href="<?= e(url('about.php')) ?>">Giới thiệu</a>
        <?php if (is_admin()): ?><a class="<?= $isAdminPage ? 'active admin-link' : 'admin-link' ?>" href="<?= e(url('admin/index.php')) ?>">Quản trị</a><?php endif; ?>
    </nav>

    <div class="topbar-actions">
        <div class="notification-menu" data-notification-menu>
            <button class="notification-trigger" type="button" data-notification-trigger aria-label="Thông báo" aria-expanded="false">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                <?php if ($notificationUnreadCount > 0): ?><span class="notification-count"><?= $notificationUnreadCount > 99 ? '99+' : (int)$notificationUnreadCount ?></span><?php endif; ?>
            </button>
            <div class="notification-dropdown" data-notification-dropdown>
                <div class="notification-dropdown-head"><div><b>Thông báo</b><small><?= (int)$notificationUnreadCount ?> chưa đọc</small></div><a href="<?= e(url('notifications.php')) ?>">XEM TẤT CẢ</a></div>
                <div class="notification-dropdown-list">
                    <?php if (!$notifications): ?>
                        <div class="notification-empty"><span>◇</span><b>Chưa có thông báo.</b><small>Cập nhật tài khoản và nhân vật sẽ xuất hiện tại đây.</small></div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <a href="<?= e(url('notification.php?id=' . (int)$notification['notification_id'])) ?>" class="notification-item <?= (int)$notification['is_read'] === 0 ? 'unread' : '' ?>">
                                <span class="notification-item-inner">
                                    <span class="notification-type-icon"><?= str_contains((string)$notification['type'], 'approved') ? '✓' : (str_contains((string)$notification['type'], 'submitted') ? '↑' : '#') ?></span>
                                    <span class="notification-copy"><b><?= e((string)$notification['title']) ?></b><small><?= e((string)$notification['message']) ?></small><em><?= e(date('d/m/Y · H:i', strtotime((string)$notification['created_at']))) ?></em></span>
                                    <?php if ((int)$notification['is_read'] === 0): ?><i></i><?php endif; ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <a class="twoyears-top-icon <?= $currentFile === 'twoyears.php' ? 'active' : '' ?>" href="<?= e(url('twoyears.php')) ?>" title="Quyền lợi #TWOYEARS" aria-label="Quyền lợi #TWOYEARS"><span>#</span><b>2Y</b></a>

        <div class="account-menu" data-account-menu>
            <button class="account-trigger" type="button" data-account-trigger aria-expanded="false">
                <span class="account-avatar"><?= e($initial) ?></span>
                <span class="account-trigger-copy"><b><?= e($account['username'] ?? '') ?></b><small>Master Account</small></span>
                <span class="account-chevron">⌄</span>
            </button>
            <div class="account-dropdown" data-account-dropdown>
                <div class="account-dropdown-head"><span class="account-avatar large"><?= e($initial) ?></span><div><b><?= e($account['username'] ?? '') ?></b><small>Master Account #<?= (int)($account['account_id'] ?? 0) ?></small></div></div>
                <a href="<?= e(url('twoyears.php')) ?>"><span>#</span><div><b>#TWOYEARS</b><small>Xem quyền lợi đăng ký sớm</small></div></a>
                <a href="<?= e(url('account.php')) ?>"><span>◎</span><div><b>Tài khoản</b><small>Thông tin Master Account</small></div></a>
                <a href="<?= e(url('account.php#email')) ?>"><span>@</span><div><b>Đổi email</b><small>Cập nhật địa chỉ liên hệ</small></div></a>
                <a href="<?= e(url('account.php#password')) ?>"><span>◆</span><div><b>Đổi mật khẩu</b><small>Bảo mật tài khoản</small></div></a>
                <a href="<?= e(url('support.php')) ?>"><span>?</span><div><b>Yêu cầu hỗ trợ</b><small>Gửi ticket cho Ban quản trị</small></div></a>
                <div class="account-dropdown-divider"></div>
                <a class="logout-item" href="<?= e(url('logout.php')) ?>"><span>↗</span><div><b>Đăng xuất</b><small>Kết thúc phiên hiện tại</small></div></a>
            </div>
        </div>
    </div>
</header>
<?php endif; ?>

<?php if ($flashes): ?>
<div class="flash-stack">
    <?php foreach ($flashes as $flash): ?><div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>
