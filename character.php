<?php
require __DIR__ . '/app/bootstrap.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT * FROM player_characters WHERE character_id = ? AND account_id = ? LIMIT 1");
$stmt->execute([$id, current_account_id()]);
$character = $stmt->fetch();
if (!$character) { http_response_code(404); exit('Không tìm thấy nhân vật.'); }

$twoYearsReward = twoyears_character_reward((int)current_account_id(), (int)$character['character_id']);
$vehicleResult = owned_vehicles_for_character((int)current_account_id(), (int)$character['character_id']);
$characterVehicles = $vehicleResult['vehicles'];
$totalWealth = (int)($character['cash'] ?? 0) + (int)($character['bank'] ?? 0);
$pageTitle = str_replace('_', ' ', $character['name']);
require __DIR__ . '/partials/header.php';
?>
<main class="page-shell">
    <a class="back-link" href="<?= e(url('characters.php')) ?>">← CHARACTER SELECTOR</a>

    <section class="v5-profile-hero">
        <div class="v5-profile-copy">
            <span class="eyebrow">OFFICIAL CHARACTER RECORD · #<?= (int)$character['character_id'] ?></span>
            <h1><?= e(str_replace('_', ' ', $character['name'])) ?></h1>
            <p><?= (int)$character['character_created'] === 1 ? 'Hồ sơ nhân vật đang hoạt động và sẵn sàng phản ánh dữ liệu gameplay theo Character ID.' : 'Hồ sơ đã được tạo nhưng Character Creator trong game chưa hoàn tất.' ?></p>
            <div class="v5-profile-tags">
                <span class="badge success"><?= (int)$character['character_created'] === 1 ? 'ACTIVE PROFILE' : 'CREATOR PENDING' ?></span>
                <span class="badge">LEVEL <?= (int)$character['level'] ?></span>
                <span class="badge"><?= e(job_name($character['job'] ?? 0)) ?></span>
                <span class="badge"><?= count($characterVehicles) ?> PHƯƠNG TIỆN</span>
            </div>
        </div>
        <div class="v5-profile-portrait">
            <span class="v5-model-tag">SKIN MODEL <?= (int)$character['skin'] ?> · SLOT 0<?= (int)$character['slot'] ?></span>
            <img src="<?= e(skin_url($character['skin'])) ?>" alt="Skin hiện tại của <?= e(str_replace('_', ' ', $character['name'])) ?>">
        </div>
    </section>

    <?php if ($twoYearsReward): ?>
    <section class="twoyears-character-banner" style="margin-top:18px">
        <div class="twoyears-character-banner-icon">462</div>
        <div><span class="eyebrow">#TWOYEARS · EARLY CHARACTER REWARD</span><h2>Faggio khởi đầu</h2><p><?= (int)($twoYearsReward['vehicle_granted'] ?? 0) === 1 ? 'Phần thưởng đã được cấp vào hệ thống sở hữu xe của nhân vật này.' : 'Phần thưởng đã được ghi nhận nhưng chưa thể cấp xe. Hãy liên hệ Ban quản trị để kiểm tra.' ?></p></div>
        <strong class="<?= (int)($twoYearsReward['vehicle_granted'] ?? 0) === 1 ? 'reward-granted' : 'reward-pending' ?>"><?= (int)($twoYearsReward['vehicle_granted'] ?? 0) === 1 ? 'ĐÃ NHẬN' : 'ĐANG XỬ LÝ' ?></strong>
    </section>
    <?php endif; ?>

    <section class="v5-profile-layout">
        <aside>
            <div class="v5-side-card">
                <span class="v5-card-kicker">IDENTITY DOSSIER</span><h2>Hồ sơ cá nhân</h2>
                <dl class="v5-dl">
                    <div><dt>Character ID</dt><dd>#<?= (int)$character['character_id'] ?></dd></div>
                    <div><dt>Giới tính</dt><dd><?= e(gender_name($character['gender'] ?? 0)) ?></dd></div>
                    <div><dt>Ngày sinh</dt><dd><?= sprintf('%02d/%02d/%04d', (int)($character['birth_day'] ?? 1), (int)($character['birth_month'] ?? 1), (int)($character['birth_year'] ?? 2000)) ?></dd></div>
                    <div><dt>Tuổi IC</dt><dd><?= e((string)(character_age($character) ?? '—')) ?></dd></div>
                    <div><dt>Nơi sinh</dt><dd><?= e(birthplace_name($character['birth_place'] ?? 0)) ?></dd></div>
                    <div><dt>Màu da</dt><dd><?= e(skin_tone_name($character['skin_tone'] ?? 0)) ?></dd></div>
                    <div><dt>Thể trạng</dt><dd><?= (int)($character['height_cm'] ?? 0) ?> cm · <?= (int)($character['weight_kg'] ?? 0) ?> kg</dd></div>
                </dl>
            </div>
            <div class="v5-side-card">
                <span class="v5-card-kicker">GAME STATE</span><h2>Đồng bộ thế giới</h2>
                <dl class="v5-dl">
                    <div><dt>Interior</dt><dd><?= (int)$character['interior_id'] ?></dd></div>
                    <div><dt>Virtual World</dt><dd><?= (int)$character['virtual_world'] ?></dd></div>
                    <div><dt>Last Played</dt><dd><?= e(!empty($character['last_played']) ? date('d/m/Y H:i', strtotime($character['last_played'])) : 'Chưa có') ?></dd></div>
                </dl>
            </div>
        </aside>

        <div>
            <section class="v5-main-card">
                <span class="v5-card-kicker">CHARACTER OVERVIEW</span><h2>Tổng quan nhân vật</h2>
                <div class="v5-overview-grid">
                    <div class="v5-overview-stat"><span>Tổng tài chính</span><strong><?= e(money($totalWealth)) ?></strong><small>Cash + Bank</small></div>
                    <div class="v5-overview-stat"><span>Level</span><strong><?= (int)$character['level'] ?></strong><small><?= e(job_name($character['job'] ?? 0)) ?></small></div>
                    <div class="v5-overview-stat"><span>Phương tiện</span><strong><?= count($characterVehicles) ?></strong><small>Owned by Character ID</small></div>
                </div>
                <div class="v5-overview-grid" style="margin-top:10px">
                    <div class="v5-overview-stat"><span>Tiền mặt</span><strong><?= e(money($character['cash'])) ?></strong><small>On hand</small></div>
                    <div class="v5-overview-stat"><span>Ngân hàng</span><strong><?= e(money($character['bank'])) ?></strong><small>Bank balance</small></div>
                    <div class="v5-overview-stat"><span>Health / Armour</span><strong><?= e((string)$character['health']) ?> / <?= e((string)$character['armour']) ?></strong><small>Current state</small></div>
                </div>
            </section>

            <section class="v5-main-card" style="margin-top:16px">
                <span class="v5-card-kicker">WORLD POSITION</span><h2>Vị trí & hoạt động</h2>
                <div class="v5-world-grid">
                    <div><small>POS X</small><b><?= e((string)$character['pos_x']) ?></b></div>
                    <div><small>POS Y</small><b><?= e((string)$character['pos_y']) ?></b></div>
                    <div><small>POS Z</small><b><?= e((string)$character['pos_z']) ?></b></div>
                    <div><small>ANGLE</small><b><?= e((string)$character['pos_a']) ?>°</b></div>
                </div>
            </section>

            <section class="v5-main-card" style="margin-top:16px">
                <div class="section-heading" style="margin-top:0"><div><span class="v5-card-kicker">OWNED VEHICLES</span><h2 style="margin:4px 0">Phương tiện sở hữu</h2></div><span class="badge"><?= count($characterVehicles) ?> VEHICLES</span></div>
                <?php if (!$vehicleResult['available'] || !$characterVehicles): ?>
                    <div class="character-vehicle-empty"><span class="vehicle-mini-icon">◇</span><div><strong>Chưa có phương tiện</strong><p>Chỉ những xe thực sự thuộc Character ID #<?= (int)$character['character_id'] ?> mới xuất hiện tại đây.</p></div></div>
                <?php else: ?>
                    <div class="v5-vehicle-grid">
                        <?php foreach ($characterVehicles as $vehicle): ?>
                            <article class="v5-vehicle-card">
                                <div class="v5-vehicle-image">
                                    <img loading="lazy" src="https://assets.open.mp/assets/images/vehiclePictures/Vehicle_<?= (int)$vehicle['model'] ?>.jpg" alt="<?= e($vehicle['model_name']) ?>">
                                    <?php if ($vehicle['favorite']): ?><span class="badge success v5-vehicle-status">★ FAVORITE</span><?php endif; ?>
                                </div>
                                <div class="v5-vehicle-body">
                                    <div class="v5-vehicle-head"><div><small>VEHICLE #<?= (int)$vehicle['vehicle_id'] ?> · MODEL <?= (int)$vehicle['model'] ?></small><h3><?= e($vehicle['model_name']) ?></h3></div><span class="v5-plate"><?= e($vehicle['plate']) ?></span></div>
                                    <div class="v5-vehicle-metrics">
                                        <div><span>ODO</span><b><?= e(format_vehicle_metric($vehicle['mileage'], ' km')) ?></b></div>
                                        <div><span>FUEL</span><b><?= e(format_vehicle_metric($vehicle['fuel'], '%')) ?></b></div>
                                        <div><span>HEALTH</span><b><?= e(format_vehicle_metric($vehicle['health'])) ?></b></div>
                                    </div>
                                    <div class="v5-char-footer" style="margin-top:12px"><span><?= e($vehicle['state']) ?></span><strong>Màu <?= e((string)($vehicle['color1'] ?? '—')) ?> / <?= e((string)($vehicle['color2'] ?? '—')) ?></strong></div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </section>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
