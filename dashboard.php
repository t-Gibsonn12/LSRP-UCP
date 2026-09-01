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
$totalWealth = 0;
$totalVehicles = 0;
$vehicleCounts = [];
foreach ($characters as $character) {
    $totalWealth += (int)($character['cash'] ?? 0) + (int)($character['bank'] ?? 0);
    try {
        $result = owned_vehicles_for_character((int)current_account_id(), (int)$character['character_id']);
        $vehicleCounts[(int)$character['character_id']] = count($result['vehicles'] ?? []);
        $totalVehicles += $vehicleCounts[(int)$character['character_id']];
    } catch (Throwable $e) {
        $vehicleCounts[(int)$character['character_id']] = 0;
    }
}

$pageTitle = 'Tổng quan';
require __DIR__ . '/partials/header.php';
?>
<main class="page-shell">
    <section class="hero v5-dashboard-hero">
        <div class="v5-dashboard-copy">
            <span class="eyebrow">MASTER ACCOUNT · <?= e(strtoupper((string)current_account()['username'])) ?></span>
            <h1>Chọn danh tính.<br><span>Tiếp tục câu chuyện.</span></h1>
            <p>UCP quản lý từng nhân vật như một danh tính độc lập: skin hiện tại, kinh tế, công việc và phương tiện đều gắn với đúng Character ID.</p>
            <div class="hero-actions">
                <a class="btn primary" href="<?= e(url('characters.php')) ?>">QUẢN LÝ NHÂN VẬT <span>→</span></a>
                <a class="btn outline" href="<?= e(url('about.php')) ?>">GIỚI THIỆU LSRP</a>
            </div>
            <div class="v5-account-line"><i class="v5-online-dot"></i><strong><?= e((string)current_account()['username']) ?></strong><span>Master Account #<?= (int)current_account_id() ?></span><span><?= $pending ?> hồ sơ chờ duyệt</span></div>
        </div>
        <div class="v5-dashboard-art"><div class="v5-seal">IDENTITY PORTAL</div></div>
    </section>

    <section class="stat-grid">
        <div class="stat-card"><span>CHARACTER SLOTS</span><strong><?= $charCount ?> <small>/ <?= (int)$GLOBALS['config']['max_characters'] ?></small></strong><p>Danh tính đã liên kết</p></div>
        <div class="stat-card"><span>TÀI SẢN PHƯƠNG TIỆN</span><strong><?= $totalVehicles ?></strong><p>Xe thuộc các nhân vật</p></div>
        <div class="stat-card"><span>TỔNG TÀI CHÍNH</span><strong class="small-value"><?= e(money($totalWealth)) ?></strong><p>Cash + Bank của characters</p></div>
        <div class="stat-card"><span>THÀNH VIÊN TỪ</span><strong class="small-value"><?= e($memberSince ? date('d/m/Y', strtotime($memberSince)) : '—') ?></strong><p><?= $freeSlots ?> slot còn trống</p></div>
    </section>

    <div class="section-heading">
        <div><span class="eyebrow">CHARACTER SELECTOR</span><h2>Nhân vật của bạn</h2></div>
        <a class="section-link" href="<?= e(url('applications.php')) ?>">HỒ SƠ ĐÃ GỬI →</a>
    </div>

    <section class="v5-character-grid">
        <?php for ($slot = 1; $slot <= (int)$GLOBALS['config']['max_characters']; $slot++): ?>
            <?php
            $character = null;
            foreach ($characters as $row) {
                if ((int)$row['slot'] === $slot) { $character = $row; break; }
            }
            ?>
            <?php if ($character): ?>
                <a class="v5-char-card" href="<?= e(url('character.php?id=' . (int)$character['character_id'])) ?>">
                    <div class="v5-char-visual">
                        <div class="v5-char-top"><span class="v5-char-slot">SLOT 0<?= $slot ?> · #<?= (int)$character['character_id'] ?></span><span class="v5-char-state"><i></i><?= (int)$character['character_created'] === 1 ? 'ACTIVE' : 'PENDING' ?></span></div>
                        <img src="<?= e(skin_url($character['skin'])) ?>" alt="Skin <?= (int)$character['skin'] ?>">
                    </div>
                    <div class="v5-char-body">
                        <small>CHARACTER IDENTITY</small>
                        <h3><?= e(str_replace('_', ' ', $character['name'])) ?></h3>
                        <div class="v5-char-metrics">
                            <div><span>LEVEL</span><b><?= (int)$character['level'] ?></b></div>
                            <div><span>SKIN</span><b>#<?= (int)$character['skin'] ?></b></div>
                            <div><span>VEHICLES</span><b><?= (int)($vehicleCounts[(int)$character['character_id']] ?? 0) ?></b></div>
                        </div>
                        <div class="v5-char-footer"><span><?= e(job_name($character['job'] ?? 0)) ?></span><strong><?= e(money((int)($character['cash'] ?? 0) + (int)($character['bank'] ?? 0))) ?></strong></div>
                    </div>
                </a>
            <?php else: ?>
                <?php
                $emptySkin = $slot === 2 ? 105 : 12;
                $emptyLabel = $slot === 2 ? 'STREET IDENTITY' : 'CITY IDENTITY';
                ?>
                <a class="v5-char-card v5-empty-card v5-empty-slot-<?= (int)$slot ?>" href="<?= e(url('apply.php?slot=' . $slot)) ?>">
                    <div class="v5-char-visual v5-empty-visual">
                        <div class="v5-char-top">
                            <span class="v5-char-slot">CHARACTER 0<?= $slot ?></span>
                            <span class="v5-char-state v5-char-state-empty"><i></i>AVAILABLE</span>
                        </div>
                        <span class="v5-empty-watermark">LSRP</span>
                        <span class="v5-empty-tag">LOS SANTOS · 1992</span>
                        <img src="<?= e(skin_url($emptySkin)) ?>" alt="Nhân vật gợi ý cho Character 0<?= $slot ?>" loading="lazy">
                    </div>
                    <div class="v5-char-body v5-empty-body">
                        <small><?= e($emptyLabel) ?></small>
                        <h3>Chưa có nhân vật</h3>
                        <p>Viết nên một danh tính mới và bắt đầu câu chuyện của bạn tại Los Santos.</p>
                        <span class="btn primary">TẠO HỒ SƠ <b>→</b></span>
                    </div>
                </a>
            <?php endif; ?>
        <?php endfor; ?>
    </section>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
