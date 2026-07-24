<?php
require __DIR__ . '/app/bootstrap.php';
require_login();

$stmt = db()->prepare("SELECT * FROM player_characters WHERE account_id = ? ORDER BY slot ASC");
$stmt->execute([current_account_id()]);
$characters = $stmt->fetchAll();

$pending = 0;
try {
    $stmt = db()->prepare("SELECT COUNT(*) FROM character_applications WHERE account_id = ? AND status = 'pending'");
    $stmt->execute([current_account_id()]);
    $pending = (int)$stmt->fetchColumn();
} catch (Throwable $e) {}

$charCount = count($characters);
$freeSlots = max(0, (int)$GLOBALS['config']['max_characters'] - $charCount);
$memberSince = current_account()['register_date'] ?? null;

$pageTitle = 'Trang chủ';
require __DIR__ . '/partials/header.php';
?>
<main class="page-shell">
    <section class="hero dashboard-hero">
        <div>
            <div class="eyebrow">WELCOME BACK, <?= e(strtoupper((string)current_account()['username'])) ?></div>
            <h1>Los Santos Roleplay<br><span>Vietnamese</span></h1>
            <p>Master Account của bạn đang liên kết với <strong><?= $charCount ?>/<?= (int)$GLOBALS['config']['max_characters'] ?></strong> nhân vật.</p>
            <div class="hero-actions">
                <a class="btn primary" href="<?= e(url('characters.php')) ?>">XEM NHÂN VẬT <span>→</span></a>
                <a class="btn outline" href="<?= e(url('news.php')) ?>">TIN TỨC</a>
            </div>
        </div>
        <div class="hero-stamp">LOS SANTOS<br><strong>1992</strong></div>
    </section>

    <section class="stat-grid">
        <div class="stat-card"><span>NHÂN VẬT</span><strong><?= $charCount ?> <small>/ <?= (int)$GLOBALS['config']['max_characters'] ?></small></strong><p>Đang liên kết</p></div>
        <div class="stat-card"><span>NHÂN VẬT CHƯA TẠO</span><strong><?= $freeSlots ?></strong><p>Có thể gửi hồ sơ mới</p></div>
        <div class="stat-card"><span>NHÂN VẬT CHỜ DUYỆT</span><strong><?= $pending ?></strong><p>Character applications</p></div>
        <div class="stat-card"><span>THÀNH VIÊN TỪ</span><strong class="small-value"><?= e($memberSince ? date('d/m/Y', strtotime($memberSince)) : '—') ?></strong><p>Master Account</p></div>
    </section>

    <div class="section-heading">
        <div><span class="eyebrow">CHARACTER SLOTS</span><h2>Nhân vật của bạn</h2></div>
        <a class="section-link" href="<?= e(url('characters.php')) ?>">QUẢN LÝ TẤT CẢ →</a>
    </div>

    <section class="character-grid">
        <?php for ($slot = 1; $slot <= (int)$GLOBALS['config']['max_characters']; $slot++): ?>
            <?php
            $character = null;
            foreach ($characters as $row) {
                if ((int)$row['slot'] === $slot) { $character = $row; break; }
            }
            ?>
            <?php if ($character): ?>
                <article class="character-card">
                    <div class="slot-label">SLOT 0<?= $slot ?></div>
                    <a class="skin-stage character-entry-link" href="<?= e(url('character.php?id=' . (int)$character['character_id'])) ?>" aria-label="Xem nhân vật <?= e(str_replace('_', ' ', $character['name'])) ?>">
                        <img src="<?= e(skin_url($character['skin'])) ?>" alt="Skin <?= (int)$character['skin'] ?>">
                        <span class="entry-hover-label">XEM NHÂN VẬT →</span>
                    </a>
                    <div class="character-card-body">
                        <span class="badge success"><?= (int)$character['character_created'] === 1 ? 'ACTIVE PROFILE' : 'CREATOR PENDING' ?></span>
                        <h3><?= e(str_replace('_', ' ', $character['name'])) ?></h3>
                        <p>Level <?= (int)$character['level'] ?> · Skin <?= (int)$character['skin'] ?> · <?= e(job_name($character['job'] ?? 0)) ?></p>
                        <a class="text-link" href="<?= e(url('character.php?id=' . (int)$character['character_id'])) ?>">MỞ HỒ SƠ <span>→</span></a>
                    </div>
                </article>
            <?php else: ?>
                <article class="character-card empty">
                    <div class="slot-label">SLOT 0<?= $slot ?></div>
                    <a class="empty-icon character-entry-link" href="<?= e(url('apply.php?slot=' . $slot)) ?>" aria-label="Tạo nhân vật tại slot <?= $slot ?>">
                        <span>+</span>
                        <small>TẠO NHÂN VẬT</small>
                    </a>
                    <div class="character-card-body">
                        <span class="badge">EMPTY SLOT</span>
                        <h3>TẠO NHÂN VẬT</h3>
                        <p>Nhấn dấu + để chuyển thẳng sang hồ sơ tạo nhân vật.</p>
                        <a class="text-link" href="<?= e(url('apply.php?slot=' . $slot)) ?>">GỬI YÊU CẦU <span>→</span></a>
                    </div>
                </article>
            <?php endif; ?>
        <?php endfor; ?>
    </section>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
