<?php
require __DIR__ . '/app/bootstrap.php';
require_login();

$stmt = db()->prepare("SELECT * FROM player_characters WHERE account_id = ? ORDER BY slot ASC");
$stmt->execute([current_account_id()]);
$characters = $stmt->fetchAll();

$pendingCount = 0;
try {
    $stmt = db()->prepare("SELECT COUNT(*) FROM character_applications WHERE account_id = ? AND status = 'pending'");
    $stmt->execute([current_account_id()]);
    $pendingCount = (int)$stmt->fetchColumn();
} catch (Throwable $e) {}

$pageTitle = 'Nhân vật';
require __DIR__ . '/partials/header.php';
?>
<main class="page-shell">
    <div class="page-heading">
        <div>
            <span class="eyebrow">QUẢN LÍ NHÂN VẬT</span>
            <h1>Nhân vật</h1>
            <p>Mỗi tài khoản có tối đa 3 nhân vật.</p>
        </div>
        <div class="heading-actions">
            <a class="btn outline" href="<?= e(url('applications.php')) ?>">NHÂN VẬT CHỜ DUYỆT<?= $pendingCount > 0 ? ' · ' . $pendingCount : '' ?></a>
            <?php if (count($characters) < (int)$GLOBALS['config']['max_characters']): ?>
                <a class="btn primary" href="<?= e(url('apply.php')) ?>">+ GỬI YÊU CẦU TẠO NHÂN VẬT</a>
            <?php endif; ?>
        </div>
    </div>

    <section class="character-list">
        <?php foreach ($characters as $character): ?>
        <a class="character-row" href="<?= e(url('character.php?id=' . (int)$character['character_id'])) ?>">
            <div class="row-skin"><img src="<?= e(skin_url($character['skin'])) ?>" alt=""></div>
            <div class="row-main">
                <span class="badge success">SLOT 0<?= (int)$character['slot'] ?></span>
                <h2><?= e(str_replace('_', ' ', $character['name'])) ?></h2>
                <p>Skin <?= (int)$character['skin'] ?> · Level <?= (int)$character['level'] ?> · <?= e(job_name($character['job'] ?? 0)) ?></p>
            </div>
            <div class="row-meta">
                <span>LAST PLAYED</span>
                <strong><?= e(!empty($character['last_played']) ? date('d/m/Y H:i', strtotime($character['last_played'])) : 'Chưa vào game') ?></strong>
            </div>
            <div class="arrow">→</div>
        </a>
        <?php endforeach; ?>

        <?php if (!$characters): ?>
            <div class="empty-state"><h2>Chưa có nhân vật.</h2><p>Gửi hồ sơ đầu tiên để bắt đầu.</p><a class="btn primary" href="<?= e(url('apply.php')) ?>">GỬI HỒ SƠ</a></div>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
