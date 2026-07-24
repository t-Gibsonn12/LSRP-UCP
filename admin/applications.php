<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$status = $_GET['status'] ?? 'pending';
$allowed = ['pending', 'approved', 'rejected', 'all'];
if (!in_array($status, $allowed, true)) $status = 'pending';

$sql = "SELECT ca.*, pa.username
        FROM character_applications ca
        LEFT JOIN player_accounts pa ON pa.account_id = ca.account_id";
$params = [];
if ($status !== 'all') {
    $sql .= " WHERE ca.status = ?";
    $params[] = $status;
}
$sql .= " ORDER BY ca.created_at DESC";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Duyệt nhân vật';
require dirname(__DIR__) . '/partials/header.php';
?>
<main class="page-shell">
    <div class="page-heading">
        <div><span class="eyebrow">ADMIN · CHARACTER APPLICATIONS</span><h1>Duyệt nhân vật</h1><p>Hồ sơ tạo nhân vật từ Master Account.</p></div>
    </div>

    <div class="filter-tabs">
        <?php foreach (['pending'=>'Đang chờ', 'approved'=>'Đã duyệt', 'rejected'=>'Từ chối', 'all'=>'Tất cả'] as $key=>$label): ?>
            <a class="<?= $status === $key ? 'active' : '' ?>" href="<?= e(url('admin/applications.php?status=' . $key)) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>

    <section class="table-panel">
        <div class="table-head"><span>HỒ SƠ</span><span>MASTER ACCOUNT</span><span>TRẠNG THÁI</span><span>NGÀY GỬI</span></div>
        <?php foreach ($rows as $row): ?>
        <a class="table-row" href="<?= e(url('admin/application.php?id=' . (int)$row['application_id'])) ?>">
            <div><strong><?= e(str_replace('_', ' ', $row['character_name'])) ?></strong><small>Slot 0<?= (int)$row['slot'] ?></small></div>
            <div><?= e($row['username'] ?? ('#' . $row['account_id'])) ?></div>
            <div><span class="badge <?= e(application_status_class($row['status'])) ?>"><?= e(application_status_name($row['status'])) ?></span></div>
            <div><?= e(date('d/m/Y H:i', strtotime($row['created_at']))) ?></div>
        </a>
        <?php endforeach; ?>
    </section>
</main>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
