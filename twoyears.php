<?php
require __DIR__ . '/app/bootstrap.php';
require_login();

$accountId = (int)current_account_id();
$package = twoyears_account_package($accountId);
$reward = twoyears_account_reward($accountId);
$rewardCharacter = null;
$rewardVehicle = null;

if ($reward) {
    $stmt = db()->prepare("SELECT character_id, name, slot FROM player_characters WHERE character_id = ? AND account_id = ? LIMIT 1");
    $stmt->execute([(int)$reward['character_id'], $accountId]);
    $rewardCharacter = $stmt->fetch() ?: null;

    if ($rewardCharacter && (int)($reward['vehicle_granted'] ?? 0) === 1) {
        $vehicleResult = owned_vehicles_for_character($accountId, (int)$reward['character_id']);
        foreach ($vehicleResult['vehicles'] as $vehicle) {
            if ((int)$vehicle['vehicle_id'] === (int)($reward['vehicle_id'] ?? 0)) {
                $rewardVehicle = $vehicle;
                break;
            }
        }
    }
}

$pageTitle = '#TWOYEARS';
require __DIR__ . '/partials/header.php';
?>
<main class="page-shell">
    <div class="page-heading">
        <div>
            <span class="eyebrow">EARLY REGISTRATION PROGRAM</span>
            <h1>#TWOYEARS</h1>
            <p>Quyền lợi dành cho Master Account tham gia sớm trong giai đoạn phát triển Los Santos Roleplay.</p>
        </div>
        <a class="btn outline" href="<?= e(url('notifications.php')) ?>">THÔNG BÁO</a>
    </div>

    <section class="twoyears-account-card">
        <div class="twoyears-account-card-mark">#TWOYEARS</div>
        <div class="twoyears-account-card-copy">
            <span class="eyebrow">EARLY ACCOUNT</span>
            <h2><?= e(current_account()['username'] ?? '') ?></h2>
            <p>Tài khoản của bạn được ghi nhận trong chương trình đăng ký sớm. Phần thưởng phương tiện chỉ áp dụng một lần cho nhân vật đầu tiên được phê duyệt.</p>
        </div>
        <div class="twoyears-account-card-id">
            <span>ACCOUNT CARD</span>
            <strong><?= e((string)($package['card_code'] ?? ('TWY-' . str_pad((string)$accountId, 6, '0', STR_PAD_LEFT)))) ?></strong>
            <small><?= $package ? 'Đã kích hoạt' : 'Chưa ghi nhận' ?></small>
        </div>
    </section>

    <div class="twoyears-page-grid">
        <section class="twoyears-benefits-card">
            <span class="eyebrow">PACKAGE BENEFITS</span>
            <h2>Quyền lợi #TWOYEARS</h2>
            <div class="twoyears-benefits-list">
                <div><span>01</span><p><strong>1 Thẻ #TWOYEARS Account</strong><small>Quyền lợi Tester và nhiều đặc quyền dành cho thành viên tham gia sớm.</small></p></div>
                <div><span>02</span><p><strong>01 Faggio khởi đầu</strong><small>Được cấp trực tiếp vào nhân vật đầu tiên sau khi hồ sơ nhân vật được Ban quản trị phê duyệt.</small></p></div>
                <div><span>03</span><p><strong>Role Discord #TWOYEARS</strong><small>Dấu hiệu nhận diện nhóm thành viên đồng hành từ giai đoạn phát triển.</small></p></div>
                <div><span>04</span><p><strong>Trao đổi trực tiếp với đội ngũ phát triển</strong><small>Tham gia phản hồi và trao đổi phù hợp với tiến độ phát triển máy chủ.</small></p></div>
                <div><span>05</span><p><strong>Theo dõi liên tục cập nhật mới</strong><small>Nắm bắt các thay đổi, thử nghiệm và nội dung mới của dự án.</small></p></div>
                <div><span>06</span><p><strong>Các quyền lợi lớn khi máy chủ vận hành</strong><small>Các đặc quyền tiếp theo sẽ được công bố trong từng giai đoạn vận hành.</small></p></div>
            </div>
        </section>

        <aside class="twoyears-status-card">
            <span class="eyebrow">REWARD STATUS</span>
            <h2>Trạng thái phần thưởng</h2>
            <div class="twoyears-status-list">
                <div class="twoyears-status-row">
                    <span>MASTER ACCOUNT</span>
                    <strong><?= $package ? 'ĐÃ THAM GIA SỚM' : 'CHƯA GHI NHẬN' ?></strong>
                    <small><?= e((string)($package['joined_at'] ?? '')) ?></small>
                </div>
                <div class="twoyears-status-row">
                    <span>NHÂN VẬT NHẬN THƯỞNG</span>
                    <strong><?= $rewardCharacter ? e(str_replace('_', ' ', (string)$rewardCharacter['name'])) : 'CHƯA CÓ' ?></strong>
                    <small><?= $rewardCharacter ? 'Slot 0' . (int)$rewardCharacter['slot'] : 'Nhân vật đầu tiên được phê duyệt sẽ nhận phần thưởng.' ?></small>
                </div>
                <div class="twoyears-status-row">
                    <span>FAGGIO · MODEL 462</span>
                    <strong><?= (int)($reward['vehicle_granted'] ?? 0) === 1 ? 'ĐÃ CẤP' : 'CHƯA CẤP' ?></strong>
                    <small><?= $rewardVehicle ? 'Biển số ' . e((string)$rewardVehicle['plate']) . ' · Vehicle #' . (int)$rewardVehicle['vehicle_id'] : 'Xe sẽ được insert vào player_vehicles khi nhân vật đầu tiên được phê duyệt.' ?></small>
                </div>
            </div>
        </aside>
    </div>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
