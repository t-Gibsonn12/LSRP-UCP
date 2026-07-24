<?php
require __DIR__ . '/app/bootstrap.php';
require_login();

$notificationId = (int)($_GET['id'] ?? $_POST['notification_id'] ?? 0);
if ($notificationId <= 0) {
    flash('danger', 'Thông báo không hợp lệ.');
    redirect('notifications.php');
}

$notification = ucp_notification_mark_read((int)current_account_id(), $notificationId);
if (!$notification) {
    http_response_code(404);
    exit('Không tìm thấy thông báo này.');
}

$meta = [];
if (!empty($notification['meta_json'])) {
    $decoded = json_decode((string)$notification['meta_json'], true);
    if (is_array($decoded)) $meta = $decoded;
}

$target = ucp_notification_target($notification['action_url'] ?? null);
$actionLabel = trim((string)($notification['action_label'] ?? '')) ?: 'Mở nội dung liên quan';
$type = (string)$notification['type'];
$typeLabel = match ($type) {
    'twoyears_joined' => '#TWOYEARS',
    'character_application_submitted' => 'HỒ SƠ NHÂN VẬT',
    'character_approved', 'character_approved_basic' => 'PHÊ DUYỆT NHÂN VẬT',
    default => strtoupper(str_replace('_', ' ', $type)),
};
$typeIcon = str_contains($type, 'approved') ? '✓' : (str_contains($type, 'submitted') ? '↑' : '#');

$pageTitle = 'Chi tiết thông báo';
require __DIR__ . '/partials/header.php';
?>
<main class="page-shell narrow-page notification-page">
    <a class="back-link" href="<?= e(url('notifications.php')) ?>">← QUAY LẠI THÔNG BÁO</a>

    <section class="notification-detail-card">
        <div class="notification-detail-head">
            <span class="notification-detail-icon"><?= e($typeIcon) ?></span>
            <div>
                <span class="eyebrow"><?= e($typeLabel) ?></span>
                <h1><?= e((string)$notification['title']) ?></h1>
                <p><?= e(date('d/m/Y · H:i', strtotime((string)$notification['created_at']))) ?></p>
            </div>
            <span class="badge success">ĐÃ ĐỌC</span>
        </div>

        <div class="notification-detail-message">
            <?= nl2br(e((string)$notification['message'])) ?>
        </div>

        <?php if ($meta): ?>
        <div class="notification-detail-meta">
            <?php if (!empty($meta['character_name'])): ?>
                <div><span>NHÂN VẬT</span><strong><?= e(str_replace('_', ' ', (string)$meta['character_name'])) ?></strong></div>
            <?php endif; ?>
            <?php if (!empty($meta['slot'])): ?>
                <div><span>SLOT</span><strong>0<?= (int)$meta['slot'] ?></strong></div>
            <?php endif; ?>
            <?php if (!empty($meta['application_id'])): ?>
                <div><span>APPLICATION</span><strong>#<?= (int)$meta['application_id'] ?></strong></div>
            <?php endif; ?>
            <?php if (!empty($meta['vehicle_model'])): ?>
                <div><span>PHẦN THƯỞNG</span><strong><?= e(vehicle_model_name((int)$meta['vehicle_model'])) ?> · Model <?= (int)$meta['vehicle_model'] ?></strong></div>
            <?php endif; ?>
            <?php if (!empty($meta['vehicle_plate'])): ?>
                <div><span>BIỂN SỐ</span><strong><?= e((string)$meta['vehicle_plate']) ?></strong></div>
            <?php endif; ?>
            <?php if (!empty($meta['card_code'])): ?>
                <div><span>ACCOUNT CARD</span><strong><?= e((string)$meta['card_code']) ?></strong></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="notification-detail-actions">
            <a class="btn outline" href="<?= e(url('notifications.php')) ?>">TẤT CẢ THÔNG BÁO</a>
            <?php if (!empty($notification['action_url'])): ?>
                <a class="btn primary" href="<?= e(url($target)) ?>"><?= e(strtoupper($actionLabel)) ?> →</a>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
