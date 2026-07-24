<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$q = trim((string)($_GET['q'] ?? ''));
$sql = "SELECT pc.*, pa.username
        FROM player_characters pc
        LEFT JOIN player_accounts pa ON pa.account_id = pc.account_id";
$params = [];

if ($q !== '') {
    $sql .= " WHERE pc.name LIKE ? OR pa.username LIKE ? OR pc.character_id = ?";
    $like = '%' . $q . '%';
    $params = [$like, $like, (int)$q];
}
$sql .= " ORDER BY pc.character_id DESC LIMIT 100";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Quản lý nhân vật';
require dirname(__DIR__) . '/partials/header.php';
?>
<main class="page-shell">
    <div class="page-heading">
        <div><span class="eyebrow">ADMIN · CHARACTERS</span><h1>Quản lý nhân vật</h1><p>Tìm theo tên character, Master Account hoặc Character ID.</p></div>
    </div>

    <form class="search-bar" method="get">
        <input name="q" placeholder="Michael_Johnson / Duy / 12" value="<?= e($q) ?>">
        <button class="btn primary">TÌM KIẾM</button>
    </form>

    <section class="character-list">
        <?php foreach ($rows as $character): ?>
        <a class="character-row" href="<?= e(url('admin/character.php?id=' . (int)$character['character_id'])) ?>">
            <div class="row-skin"><img src="<?= e(skin_url($character['skin'])) ?>" alt=""></div>
            <div class="row-main">
                <span class="eyebrow">#<?= (int)$character['character_id'] ?> · SLOT 0<?= (int)$character['slot'] ?></span>
                <h2><?= e(str_replace('_', ' ', $character['name'])) ?></h2>
                <p>Master: <?= e($character['username'] ?? ('#' . $character['account_id'])) ?> · Level <?= (int)$character['level'] ?></p>
            </div>
            <div class="row-meta"><span>ECONOMY</span><strong><?= e(money($character['cash'])) ?> / <?= e(money($character['bank'])) ?></strong></div>
            <div class="arrow">→</div>
        </a>
        <?php endforeach; ?>
    </section>
</main>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
