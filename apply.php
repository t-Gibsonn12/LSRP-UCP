<?php
require __DIR__ . '/app/bootstrap.php';
require_login();

$pdo = db();
$applicationsReady = ucp_ensure_character_applications_table();
$skinOptions = samp_skin_options();

$stmt = $pdo->prepare("SELECT slot FROM player_characters WHERE account_id = ?");
$stmt->execute([current_account_id()]);
$usedSlots = array_map('intval', array_column($stmt->fetchAll(), 'slot'));

$pendingSlots = [];
if ($applicationsReady) {
    try {
        $stmt = $pdo->prepare("SELECT slot FROM character_applications WHERE account_id = ? AND status = 'pending'");
        $stmt->execute([current_account_id()]);
        $pendingSlots = array_map('intval', array_column($stmt->fetchAll(), 'slot'));
    } catch (Throwable $e) {
        error_log('[LSRP UCP] Could not read pending character applications: ' . $e->getMessage());
    }
}

$availableSlots = [];
for ($i = 1; $i <= (int)$GLOBALS['config']['max_characters']; $i++) {
    if (!in_array($i, $usedSlots, true) && !in_array($i, $pendingSlots, true)) {
        $availableSlots[] = $i;
    }
}

$errors = [];
$requestedSlot = (int)($_GET['slot'] ?? ($_POST['slot'] ?? ($availableSlots[0] ?? 0)));
$birthDateValue = trim((string)($_POST['birth_date'] ?? ''));
$maxBirthYear = (int)($GLOBALS['config']['game_year'] ?? date('Y'));
$minBirthYear = $maxBirthYear - 80;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $slot = (int)($_POST['slot'] ?? 0);
    $name = trim((string)($_POST['character_name'] ?? ''));
    $gender = (int)($_POST['gender'] ?? -1);
    $birthDate = $birthDateValue;
    $birthDay = 0;
    $birthMonth = 0;
    $birthYear = 0;
    $birthPlace = (int)($_POST['birth_place'] ?? -1);
    $nationality = trim((string)($_POST['nationality'] ?? ''));
    $skinTone = (int)($_POST['skin_tone'] ?? -1);
    $skin = (int)($_POST['skin'] ?? 0);
    $heightCm = (int)($_POST['height_cm'] ?? 0);
    $weightKg = (int)($_POST['weight_kg'] ?? 0);
    $occupation = trim((string)($_POST['occupation'] ?? ''));
    $personality = trim((string)($_POST['personality'] ?? ''));
    $strengths = trim((string)($_POST['strengths'] ?? ''));
    $weaknesses = trim((string)($_POST['weaknesses'] ?? ''));
    $concept = trim((string)($_POST['concept'] ?? ''));
    $background = trim((string)($_POST['background'] ?? ''));
    $goal = trim((string)($_POST['roleplay_goal'] ?? ''));
    $rulesAgreed = isset($_POST['rules_agreed']) ? 1 : 0;
    $textLength = static fn(string $value): int => function_exists('mb_strlen')
        ? mb_strlen($value, 'UTF-8')
        : strlen($value);

    if (!$applicationsReady) $errors[] = 'Hệ thống hồ sơ chưa sẵn sàng. Vui lòng báo Ban quản trị.';
    if (!in_array($slot, $availableSlots, true)) $errors[] = 'Slot này không còn khả dụng.';
    if (!valid_character_name($name)) $errors[] = 'Tên nhân vật phải theo dạng Firstname_Lastname, chỉ dùng chữ cái.';
    if (!in_array($gender, [0, 1], true)) $errors[] = 'Vui lòng chọn giới tính cho nhân vật.';

    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $birthDate, $dateParts)) {
        $birthYear = (int)$dateParts[1];
        $birthMonth = (int)$dateParts[2];
        $birthDay = (int)$dateParts[3];
    }
    if (
        $birthYear === 0
        || !checkdate($birthMonth, $birthDay, $birthYear)
        || $birthYear < $minBirthYear
        || $birthYear > $maxBirthYear
    ) {
        $errors[] = 'Vui lòng chọn ngày sinh hợp lệ trong thời kỳ Roleplay.';
    }

    if (!in_array($birthPlace, [0, 1, 2, 3], true)) $errors[] = 'Vui lòng chọn nơi sinh.';
    if ($textLength($nationality) < 2 || $textLength($nationality) > 80) $errors[] = 'Quốc tịch cần từ 2 đến 80 ký tự.';
    if (!in_array($skinTone, [0, 1, 2], true)) $errors[] = 'Vui lòng chọn màu da.';
    if (!array_key_exists($skin, $skinOptions)) $errors[] = 'Vui lòng chọn skin hợp lệ.';
    if ($heightCm < 130 || $heightCm > 230) $errors[] = 'Chiều cao phải từ 130 đến 230 cm.';
    if ($weightKg < 35 || $weightKg > 220) $errors[] = 'Cân nặng phải từ 35 đến 220 kg.';
    if ($textLength($occupation) < 2 || $textLength($occupation) > 80) $errors[] = 'Nghề nghiệp cần từ 2 đến 80 ký tự.';
    if ($textLength($personality) < 30) $errors[] = 'Tính cách cần ít nhất 30 ký tự.';
    if ($textLength($strengths) < 20) $errors[] = 'Điểm mạnh cần ít nhất 20 ký tự.';
    if ($textLength($weaknesses) < 20) $errors[] = 'Điểm yếu cần ít nhất 20 ký tự.';
    if ($textLength($concept) < 30) $errors[] = 'Khái quát nhân vật cần ít nhất 30 ký tự.';
    if ($textLength($background) < 100) $errors[] = 'Tiểu sử nhân vật cần ít nhất 100 ký tự.';
    if ($textLength($goal) < 50) $errors[] = 'Mục tiêu Roleplay cần ít nhất 50 ký tự.';
    if (!$rulesAgreed) $errors[] = 'Bạn cần xác nhận đã đọc và đồng ý luật Roleplay.';

    try {
        if (!$errors) {
            $stmt = $pdo->prepare("SELECT character_id FROM player_characters WHERE name = ? LIMIT 1");
            $stmt->execute([$name]);
            if ($stmt->fetch()) $errors[] = 'Tên nhân vật này đã tồn tại.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare("SELECT application_id FROM character_applications WHERE character_name = ? AND status = 'pending' LIMIT 1");
            $stmt->execute([$name]);
            if ($stmt->fetch()) $errors[] = 'Tên nhân vật này đang có một hồ sơ chờ duyệt.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare(
                "INSERT INTO character_applications
                 (account_id, slot, character_name, gender, birth_day, birth_month, birth_year,
                  birth_place, nationality, skin_tone, skin, height_cm, weight_kg, occupation,
                  personality, strengths, weaknesses, concept, background, roleplay_goal,
                  rules_agreed, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
            );
            $stmt->execute([
                current_account_id(),
                $slot,
                $name,
                $gender,
                $birthDay,
                $birthMonth,
                $birthYear,
                $birthPlace,
                $nationality,
                $skinTone,
                $skin,
                $heightCm,
                $weightKg,
                $occupation,
                $personality,
                $strengths,
                $weaknesses,
                $concept,
                $background,
                $goal,
                $rulesAgreed
            ]);
            $applicationId = (int)$pdo->lastInsertId();

            ucp_notification_create(
                (int)current_account_id(),
                'character_application_submitted',
                'Đã gửi yêu cầu tạo nhân vật',
                'Hồ sơ ' . str_replace('_', ' ', $name) . ' đã được gửi tới Ban quản trị và đang chờ phê duyệt.',
                'applications.php',
                'Xem nhân vật chờ duyệt',
                'character-application-submitted-' . $applicationId,
                ['application_id' => $applicationId, 'character_name' => $name, 'slot' => $slot]
            );

            flash('success', 'Hồ sơ nhân vật đã được gửi tới Ban quản trị. Thông báo đã được lưu vào Notification Center.');
            redirect('applications.php');
        }
    } catch (Throwable $e) {
        error_log('[LSRP UCP] Character application submit failed: ' . $e->getMessage());
        $errors[] = 'Không thể lưu hồ sơ lúc này. Vui lòng thử lại hoặc báo Ban quản trị.';
    }
}

$pageTitle = 'Gửi yêu cầu tạo nhân vật';
require __DIR__ . '/partials/header.php';
?>
<main class="page-shell narrow-page application-page">
    <a class="back-link" href="<?= e(url('characters.php')) ?>">← QUAY LẠI</a>

    <div class="page-heading">
        <div>
            <span class="eyebrow">CHARACTER APPLICATION</span>
            <h1>Tạo hồ sơ nhân vật</h1>
            <p>Hoàn thiện thông tin để Ban quản trị xét duyệt danh tính Roleplay của bạn.</p>
        </div>
    </div>

    <?php if (!$availableSlots): ?>
        <div class="empty-state"><h2>Không còn slot khả dụng.</h2><p>Bạn đã có đủ nhân vật hoặc hồ sơ đang chờ duyệt.</p></div>
    <?php else: ?>
        <?php foreach ($errors as $error): ?><div class="form-error"><?= e($error) ?></div><?php endforeach; ?>

        <form method="post" class="panel-form application-form">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

            <section class="application-section">
                <div class="application-section-heading">
                    <span>01</span>
                    <div><small>IDENTITY DOSSIER</small><h2>Thông tin cơ bản</h2><p>Những dữ liệu nhận diện chính của nhân vật.</p></div>
                </div>
                <div class="form-two">
                    <label><span>Slot nhân vật</span>
                        <select name="slot">
                            <?php foreach ($availableSlots as $availableSlot): ?>
                                <option value="<?= $availableSlot ?>" <?= $requestedSlot === $availableSlot ? 'selected' : '' ?>>Slot 0<?= $availableSlot ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label><span>Tên nhân vật</span><input name="character_name" maxlength="24" placeholder="Michael_Johnson" required value="<?= old('character_name') ?>"></label>
                </div>
                <div class="form-three">
                    <label><span>Giới tính</span>
                        <select name="gender" required>
                            <option value="">Chọn giới tính</option>
                            <option value="0" <?= (int)($_POST['gender'] ?? -1) === 0 ? 'selected' : '' ?>>Nam</option>
                            <option value="1" <?= (int)($_POST['gender'] ?? -1) === 1 ? 'selected' : '' ?>>Nữ</option>
                        </select>
                    </label>
                    <label><span>Ngày sinh</span><input type="date" name="birth_date" min="<?= $minBirthYear ?>-01-01" max="<?= $maxBirthYear ?>-12-31" required value="<?= e($birthDateValue) ?>"></label>
                    <label><span>Nơi sinh</span>
                        <select name="birth_place" required>
                            <option value="">Chọn nơi sinh</option>
                            <?php foreach ([0 => 'Los Santos', 1 => 'San Fierro', 2 => 'Las Venturas', 3 => 'Nơi khác'] as $placeId => $placeName): ?>
                                <option value="<?= $placeId ?>" <?= (int)($_POST['birth_place'] ?? -1) === $placeId ? 'selected' : '' ?>><?= e($placeName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <div class="form-three">
                    <label><span>Quốc tịch</span><input name="nationality" maxlength="80" placeholder="American" required value="<?= old('nationality') ?>"></label>
                    <label><span>Màu da</span>
                        <select name="skin_tone" required>
                            <option value="">Chọn màu da</option>
                            <?php foreach ([0 => 'Da trắng', 1 => 'Da vàng', 2 => 'Da đen'] as $toneId => $toneName): ?>
                                <option value="<?= $toneId ?>" <?= (int)($_POST['skin_tone'] ?? -1) === $toneId ? 'selected' : '' ?>><?= e($toneName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label><span>Nghề nghiệp</span><input name="occupation" maxlength="80" placeholder="Thợ máy, tài xế, sinh viên..." required value="<?= old('occupation') ?>"></label>
                </div>
                <div class="form-three">
                    <label><span>Chiều cao (cm)</span><input type="number" name="height_cm" min="130" max="230" placeholder="180" required value="<?= e((string)($_POST['height_cm'] ?? '')) ?>"></label>
                    <label><span>Cân nặng (kg)</span><input type="number" name="weight_kg" min="35" max="220" placeholder="75" required value="<?= e((string)($_POST['weight_kg'] ?? '')) ?>"></label>
                    <div class="form-field-note"><span>ROLEPLAY STANDARD</span><p>Thông tin cần hợp lý với bối cảnh Los Santos năm 1992.</p></div>
                </div>
            </section>

            <section class="application-section">
                <div class="application-section-heading">
                    <span>02</span>
                    <div><small>APPEARANCE & PERSONALITY</small><h2>Ngoại hình & tính cách</h2><p>Chọn diện mạo và mô tả cách nhân vật tồn tại trong thế giới RP.</p></div>
                </div>
                <div class="skin-picker">
                    <div class="field-label-row"><span>Skin nhân vật</span><b>Đủ bộ skin SA-MP · 0–311</b></div>
                    <div class="skin-picker-tools">
                        <label class="skin-search">
                            <span class="sr-only">Tìm skin</span>
                            <input type="text" data-skin-search placeholder="Tìm theo model hoặc tên skin..." autocomplete="off">
                        </label>
                        <span class="skin-picker-count" data-skin-count><?= count($skinOptions) ?> SKIN</span>
                    </div>
                    <div class="skin-picker-grid" data-skin-grid>
                        <?php foreach ($skinOptions as $skinId => $skinName): ?>
                            <label class="skin-option" data-skin-option data-skin-search-text="<?= e($skinName . ' ' . $skinId) ?>">
                                <input type="radio" name="skin" value="<?= $skinId ?>" <?= (int)($_POST['skin'] ?? 26) === $skinId ? 'checked' : '' ?>>
                                <span class="skin-option-card">
                                    <img loading="lazy" decoding="async" src="<?= e(skin_url($skinId)) ?>" alt="<?= e($skinName) ?>">
                                    <b><?= e($skinName) ?></b><small>MODEL #<?= $skinId ?></small>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="skin-picker-empty" data-skin-empty hidden>Không tìm thấy skin phù hợp.</div>
                </div>
                <label><span>Tính cách</span><textarea name="personality" rows="5" minlength="30" required placeholder="Nhân vật cư xử ra sao, giao tiếp thế nào, điều gì chi phối họ?"><?= old('personality') ?></textarea></label>
                <div class="form-two">
                    <label><span>Điểm mạnh</span><textarea name="strengths" rows="4" minlength="20" required placeholder="Kỹ năng, phẩm chất hoặc lợi thế của nhân vật..."><?= old('strengths') ?></textarea></label>
                    <label><span>Điểm yếu</span><textarea name="weaknesses" rows="4" minlength="20" required placeholder="Hạn chế, khuyết điểm hoặc nỗi bất an..."><?= old('weaknesses') ?></textarea></label>
                </div>
            </section>

            <section class="application-section">
                <div class="application-section-heading">
                    <span>03</span>
                    <div><small>CHARACTER STORY</small><h2>Câu chuyện nhân vật</h2><p>Đây là phần quan trọng nhất để Ban quản trị hiểu hướng Roleplay của bạn.</p></div>
                </div>
                <label><span>Khái quát nhân vật</span><textarea name="concept" rows="5" minlength="30" required placeholder="Nhân vật là ai, thuộc tầng lớp nào và đang sống như thế nào?"><?= old('concept') ?></textarea></label>
                <label><span>Tiểu sử nhân vật</span><textarea name="background" rows="10" minlength="100" required placeholder="Tuổi thơ, quá trình trưởng thành và những dấu mốc quan trọng trong cuộc đời nhân vật..."><?= old('background') ?></textarea></label>
                <label><span>Mục tiêu Roleplay</span><textarea name="roleplay_goal" rows="6" minlength="50" required placeholder="Bạn muốn phát triển nhân vật theo hướng nào trong quá trình chơi?"><?= old('roleplay_goal') ?></textarea></label>
            </section>

            <section class="application-section application-confirmation">
                <div class="application-section-heading">
                    <span>04</span>
                    <div><small>FINAL REVIEW</small><h2>Xác nhận hồ sơ</h2><p>Kiểm tra lại thông tin trước khi gửi cho Ban quản trị.</p></div>
                </div>
                <label class="application-check">
                    <input type="checkbox" name="rules_agreed" value="1" <?= isset($_POST['rules_agreed']) ? 'checked' : '' ?>>
                    <span>Tôi xác nhận thông tin trên là đúng và đã đọc, hiểu luật Roleplay của Los Santos.</span>
                </label>
                <button class="btn primary application-submit">GỬI HỒ SƠ XÉT DUYỆT <b>→</b></button>
            </section>
        </form>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
