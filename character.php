<?php
require __DIR__ . '/app/bootstrap.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare("SELECT * FROM player_characters WHERE character_id = ? AND account_id = ? LIMIT 1");
$stmt->execute([$id, current_account_id()]);
$character = $stmt->fetch();

if (!$character) {
    http_response_code(404);
    exit('Không tìm thấy nhân vật.');
}

$twoYearsReward = twoyears_character_reward((int)current_account_id(), (int)$character['character_id']);

$vehicleResult = owned_vehicles_for_character((int)current_account_id(), (int)$character['character_id']);
$characterVehicles = $vehicleResult['vehicles'];

$pageTitle = str_replace('_', ' ', $character['name']);
require __DIR__ . '/partials/header.php';
?>
<main class="page-shell">
    <a class="back-link" href="<?= e(url('characters.php')) ?>">← QUAY LẠI NHÂN VẬT</a>

    <section class="character-profile">
        <div class="profile-skin">
            <div class="skin-big"><img src="<?= e(skin_url($character['skin'])) ?>" alt="Skin <?= (int)$character['skin'] ?>"></div>
            <span>SKIN <?= (int)$character['skin'] ?></span>
        </div>
        <div class="profile-title">
            <span class="eyebrow">CHARACTER #<?= (int)$character['character_id'] ?> · SLOT 0<?= (int)$character['slot'] ?></span>
            <h1><?= e(str_replace('_', ' ', $character['name'])) ?></h1>
            <p><?= (int)$character['character_created'] === 1 ? 'Hồ sơ nhân vật đã hoàn tất.' : 'Đang chờ hoàn tất Character Creator trong game.' ?></p>
            <div class="profile-badges">
                <span class="badge success">LEVEL <?= (int)$character['level'] ?></span>
                <span class="badge"><?= e(job_name($character['job'] ?? 0)) ?></span>
                <span class="badge">PHƯƠNG TIỆN · <?= count($characterVehicles) ?></span>
            </div>
        </div>
    </section>

    <?php if ($twoYearsReward): ?>
    <section class="twoyears-character-banner">
        <div class="twoyears-character-banner-icon">462</div>
        <div>
            <span class="eyebrow">#TWOYEARS · EARLY CHARACTER REWARD</span>
            <h2>Faggio khởi đầu</h2>
            <?php if ((int)($twoYearsReward['vehicle_granted'] ?? 0) === 1): ?>
                <p>Nhân vật đầu tiên của tài khoản đã nhận 01 Faggio model 462. Phương tiện được lưu trực tiếp theo Character ID trong hệ thống sở hữu xe.</p>
            <?php else: ?>
                <p>Phần thưởng #TWOYEARS đã được ghi nhận nhưng chưa thể cấp xe. Hãy liên hệ Ban quản trị để kiểm tra trạng thái reward.</p>
            <?php endif; ?>
        </div>
        <strong class="<?= (int)($twoYearsReward['vehicle_granted'] ?? 0) === 1 ? 'reward-granted' : 'reward-pending' ?>"><?= (int)($twoYearsReward['vehicle_granted'] ?? 0) === 1 ? 'ĐÃ NHẬN' : 'ĐANG XỬ LÝ' ?></strong>
    </section>
    <?php endif; ?>

    <section class="detail-grid">
        <div class="detail-card">
            <span class="eyebrow">HỒ SƠ CÁ NHÂN</span>
            <dl>
                <div><dt>Giới tính</dt><dd><?= e(gender_name($character['gender'] ?? 0)) ?></dd></div>
                <div><dt>Ngày sinh</dt><dd><?= sprintf('%02d/%02d/%04d', (int)($character['birth_day'] ?? 1), (int)($character['birth_month'] ?? 1), (int)($character['birth_year'] ?? 2000)) ?></dd></div>
                <div><dt>Tuổi IC</dt><dd><?= e((string)(character_age($character) ?? '—')) ?></dd></div>
                <div><dt>Nơi sinh</dt><dd><?= e(birthplace_name($character['birth_place'] ?? 0)) ?></dd></div>
                <div><dt>Màu da</dt><dd><?= e(skin_tone_name($character['skin_tone'] ?? 0)) ?></dd></div>
                <div><dt>Chiều cao</dt><dd><?= (int)($character['height_cm'] ?? 0) ?> cm</dd></div>
                <div><dt>Cân nặng</dt><dd><?= (int)($character['weight_kg'] ?? 0) ?> kg</dd></div>
            </dl>
        </div>

        <div class="detail-card">
            <span class="eyebrow">KINH TẾ & TRẠNG THÁI</span>
            <dl>
                <div><dt>Tiền mặt</dt><dd class="money"><?= e(money($character['cash'])) ?></dd></div>
                <div><dt>Ngân hàng</dt><dd class="money"><?= e(money($character['bank'])) ?></dd></div>
                <div><dt>Máu</dt><dd><?= e((string)$character['health']) ?></dd></div>
                <div><dt>Giáp</dt><dd><?= e((string)$character['armour']) ?></dd></div>
                <div><dt>Công việc</dt><dd><?= e(job_name($character['job'] ?? 0)) ?></dd></div>
                <div><dt>Interior</dt><dd><?= (int)$character['interior_id'] ?></dd></div>
                <div><dt>Virtual World</dt><dd><?= (int)$character['virtual_world'] ?></dd></div>
            </dl>
        </div>

        <div class="detail-card wide-card">
            <span class="eyebrow">VỊ TRÍ & HOẠT ĐỘNG</span>
            <dl class="inline-dl">
                <div><dt>X</dt><dd><?= e((string)$character['pos_x']) ?></dd></div>
                <div><dt>Y</dt><dd><?= e((string)$character['pos_y']) ?></dd></div>
                <div><dt>Z</dt><dd><?= e((string)$character['pos_z']) ?></dd></div>
                <div><dt>Góc</dt><dd><?= e((string)$character['pos_a']) ?></dd></div>
                <div><dt>Lần cuối chơi</dt><dd><?= e(!empty($character['last_played']) ? date('d/m/Y H:i:s', strtotime($character['last_played'])) : 'Chưa có') ?></dd></div>
            </dl>
        </div>
    </section>

    <div class="section-heading character-vehicle-heading">
        <div><span class="eyebrow">OWNED VEHICLES</span><h2>Phương tiện sở hữu</h2></div>
    </div>

    <?php if (!$vehicleResult['available'] || !$characterVehicles): ?>
        <section class="character-vehicle-empty">
            <span class="vehicle-mini-icon">◇</span>
            <div>
                <strong>Chưa có phương tiện</strong>
                <p>Xe thuộc nhân vật này sẽ tự xuất hiện khi có dữ liệu trong hệ thống phương tiện.</p>
            </div>
        </section>
    <?php else: ?>
        <section class="vehicle-grid character-owned-vehicle-grid">
            <?php foreach ($characterVehicles as $vehicle): ?>
                <article class="vehicle-card <?= $vehicle['favorite'] ? 'favorite' : '' ?>">
                    <div class="vehicle-visual">
                        <div class="vehicle-visual-top">
                            <span>MODEL <?= (int)$vehicle['model'] ?></span>
                            <?php if ($vehicle['favorite']): ?><b title="Phương tiện yêu thích">★ FAVORITE</b><?php endif; ?>
                        </div>
                        <svg class="vehicle-silhouette" viewBox="0 0 180 90" aria-hidden="true">
                            <path d="M27 59 38 34c3-8 10-13 19-14l54-5c10-1 18 3 24 11l14 20 14 5c5 2 8 7 8 12v6h-14a18 18 0 0 1-35 0H61a18 18 0 0 1-35 0H10v-5c0-4 3-8 7-9l10-3Zm30-30c-5 1-8 3-10 8l-5 12h39V26l-24 3Zm34-4v24h42l-12-17c-3-5-8-8-15-8l-15 1ZM44 61a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm96 0a10 10 0 1 0 0 20 10 10 0 0 0 0-20Z"/>
                        </svg>
                        <div class="vehicle-model-number"><?= (int)$vehicle['model'] ?></div>
                    </div>
                    <div class="vehicle-card-body">
                        <div class="vehicle-title-row">
                            <div>
                                <span class="eyebrow">#<?= (int)$vehicle['vehicle_id'] ?> · <?= e($vehicle['state']) ?></span>
                                <h3><?= e($vehicle['model_name']) ?></h3>
                            </div>
                            <span class="vehicle-plate"><?= e($vehicle['plate']) ?></span>
                        </div>
                        <dl class="vehicle-metrics">
                            <div><dt>ODO</dt><dd><?= e(format_vehicle_metric($vehicle['mileage'], ' km')) ?></dd></div>
                            <div><dt>FUEL</dt><dd><?= e(format_vehicle_metric($vehicle['fuel'], '%')) ?></dd></div>
                            <div><dt>HEALTH</dt><dd><?= e(format_vehicle_metric($vehicle['health'])) ?></dd></div>
                        </dl>
                        <?php if ($vehicle['color1'] !== null || $vehicle['color2'] !== null): ?>
                            <div class="vehicle-colors">
                                <span>Màu xe</span>
                                <b><?= e((string)($vehicle['color1'] ?? '—')) ?> / <?= e((string)($vehicle['color2'] ?? '—')) ?></b>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
