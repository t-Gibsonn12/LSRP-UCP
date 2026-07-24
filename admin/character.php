<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));

function load_admin_character(int $id): ?array
{
    $stmt = db()->prepare(
        "SELECT pc.*, pa.username
         FROM player_characters pc
         LEFT JOIN player_accounts pa ON pa.account_id = pc.account_id
         WHERE pc.character_id = ? LIMIT 1"
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

$character = load_admin_character($id);
if (!$character) exit('Không tìm thấy nhân vật.');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $cash = filter_var($_POST['cash'] ?? null, FILTER_VALIDATE_INT);
    $bank = filter_var($_POST['bank'] ?? null, FILTER_VALIDATE_INT);
    $level = filter_var($_POST['level'] ?? null, FILTER_VALIDATE_INT);
    $skin = filter_var($_POST['skin'] ?? null, FILTER_VALIDATE_INT);
    $health = filter_var($_POST['health'] ?? null, FILTER_VALIDATE_FLOAT);
    $armour = filter_var($_POST['armour'] ?? null, FILTER_VALIDATE_FLOAT);
    $job = filter_var($_POST['job'] ?? null, FILTER_VALIDATE_INT);

    if ($cash === false || $cash < 0 || $cash > 2000000000) $errors[] = 'Tiền mặt không hợp lệ.';
    if ($bank === false || $bank < 0 || $bank > 2000000000) $errors[] = 'Tiền ngân hàng không hợp lệ.';
    if ($level === false || $level < 1 || $level > 100) $errors[] = 'Level phải từ 1 đến 100.';
    if ($skin === false || $skin < 0 || $skin > 311) $errors[] = 'Skin ID phải từ 0 đến 311.';
    if ($health === false || $health < 1 || $health > 100) $errors[] = 'Health phải từ 1 đến 100.';
    if ($armour === false || $armour < 0 || $armour > 100) $errors[] = 'Armour phải từ 0 đến 100.';
    if ($job === false || $job < 0 || $job > 15) $errors[] = 'Job ID không hợp lệ.';

    if (!$errors) {
        $before = [
            'cash' => (int)$character['cash'],
            'bank' => (int)$character['bank'],
            'level' => (int)$character['level'],
            'skin' => (int)$character['skin'],
            'health' => (float)$character['health'],
            'armour' => (float)$character['armour'],
            'job' => (int)($character['job'] ?? 0),
        ];

        $stmt = db()->prepare(
            "UPDATE player_characters
             SET cash=?, bank=?, level=?, skin=?, health=?, armour=?, job=?
             WHERE character_id=? LIMIT 1"
        );
        $stmt->execute([$cash, $bank, $level, $skin, $health, $armour, $job, $id]);

        $after = compact('cash', 'bank', 'level', 'skin', 'health', 'armour', 'job');

        admin_log('character.update', 'character', $id, [
            'name' => $character['name'],
            'before' => $before,
            'after' => $after
        ]);

        flash('success', 'Đã cập nhật dữ liệu nhân vật. Thay đổi được lưu trực tiếp vào player_characters.');
        redirect('admin/character.php?id=' . $id);
    }
}

$character = load_admin_character($id);

$pageTitle = 'Admin · ' . str_replace('_', ' ', $character['name']);
require dirname(__DIR__) . '/partials/header.php';
?>
<main class="page-shell">
    <a class="back-link" href="<?= e(url('admin/characters.php')) ?>">← QUẢN LÝ NHÂN VẬT</a>

    <section class="character-profile admin-character-profile">
        <div class="profile-skin">
            <div class="skin-big"><img src="<?= e(skin_url($character['skin'])) ?>" alt=""></div>
            <span>SKIN <?= (int)$character['skin'] ?></span>
        </div>
        <div class="profile-title">
            <span class="eyebrow">ADMIN · CHARACTER #<?= (int)$character['character_id'] ?></span>
            <h1><?= e(str_replace('_', ' ', $character['name'])) ?></h1>
            <p>Master Account: <strong><?= e($character['username'] ?? ('#' . $character['account_id'])) ?></strong> · Slot 0<?= (int)$character['slot'] ?></p>
        </div>
    </section>

    <?php foreach ($errors as $error): ?><div class="form-error"><?= e($error) ?></div><?php endforeach; ?>

    <form method="post" class="admin-editor-grid">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int)$character['character_id'] ?>">

        <section class="detail-card">
            <span class="eyebrow">KINH TẾ</span>
            <h2>Tiền của nhân vật</h2>
            <label><span>Tiền mặt</span><input type="number" name="cash" min="0" max="2000000000" value="<?= (int)$character['cash'] ?>"></label>
            <label><span>Ngân hàng</span><input type="number" name="bank" min="0" max="2000000000" value="<?= (int)$character['bank'] ?>"></label>
        </section>

        <section class="detail-card">
            <span class="eyebrow">PROGRESSION</span>
            <h2>Trạng thái nhân vật</h2>
            <label><span>Level</span><input type="number" name="level" min="1" max="100" value="<?= (int)$character['level'] ?>"></label>
            <label><span>Job ID (0 = không có, 1 = Pizza)</span><input type="number" name="job" min="0" max="15" value="<?= (int)($character['job'] ?? 0) ?>"></label>
        </section>

        <section class="detail-card">
            <span class="eyebrow">APPEARANCE</span>
            <h2>Skin</h2>
            <label><span>SA-MP Skin ID</span><input type="number" name="skin" min="0" max="311" value="<?= (int)$character['skin'] ?>"></label>
        </section>

        <section class="detail-card">
            <span class="eyebrow">VITALS</span>
            <h2>Health / Armour</h2>
            <label><span>Health</span><input type="number" step="0.1" name="health" min="1" max="100" value="<?= e((string)$character['health']) ?>"></label>
            <label><span>Armour</span><input type="number" step="0.1" name="armour" min="0" max="100" value="<?= e((string)$character['armour']) ?>"></label>
        </section>

        <div class="editor-submit">
            <p><strong>Lưu ý:</strong> Đây là dữ liệu thật trong <code>player_characters</code>. Mọi thay đổi được ghi vào Admin Log.</p>
            <button class="btn primary">LƯU THAY ĐỔI NHÂN VẬT</button>
        </div>
    </form>
</main>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
