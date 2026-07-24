<?php
require __DIR__ . '/app/bootstrap.php';
require_login();

$stmt = db()->prepare("SELECT * FROM character_applications WHERE account_id = ? ORDER BY created_at DESC");
$stmt->execute([current_account_id()]);
$applications = $stmt->fetchAll();

$pageTitle = 'Nhân vật chờ duyệt';
require __DIR__ . '/partials/header.php';
?>
<main class="page-shell">
    <div class="page-heading">
        <div><span class="eyebrow">CHARACTER APPLICATIONS</span><h1>Nhân vật chờ duyệt</h1><p>Theo dõi trạng thái các hồ sơ nhân vật đã gửi.</p></div>
        <div class="heading-actions">
            <a class="btn outline" href="<?= e(url('characters.php')) ?>">← NHÂN VẬT</a>
            <a class="btn primary" href="<?= e(url('apply.php')) ?>">+ HỒ SƠ MỚI</a>
        </div>
    </div>

    <section class="table-panel">
        <div class="table-head"><span>NHÂN VẬT</span><span>SLOT</span><span>TRẠNG THÁI</span><span>NGÀY GỬI</span></div>
        <?php foreach ($applications as $app): ?>
        <div class="table-row">
            <div><strong><?= e(str_replace('_', ' ', $app['character_name'])) ?></strong><small><?= e(mb_substr($app['concept'], 0, 80)) ?></small></div>
            <div>0<?= (int)$app['slot'] ?></div>
            <div><span class="badge <?= e(application_status_class($app['status'])) ?>"><?= e(application_status_name($app['status'])) ?></span></div>
            <div><?= e(date('d/m/Y H:i', strtotime($app['created_at']))) ?></div>
        </div>
        <?php if (!empty($app['admin_note'])): ?>
            <div class="admin-note"><strong>Phản hồi Ban quản trị:</strong> <?= e($app['admin_note']) ?></div>
        <?php endif; ?>
        <?php endforeach; ?>

        <?php if (!$applications): ?><div class="empty-state"><h2>Chưa có nhân vật chờ duyệt.</h2><p>Các hồ sơ đã gửi sẽ xuất hiện tại đây.</p></div><?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
