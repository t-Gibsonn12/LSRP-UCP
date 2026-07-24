<?php
require __DIR__ . '/app/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare(
    "SELECT * FROM ucp_news
     WHERE news_id = ? AND is_published = 1
     AND (published_at IS NULL OR published_at <= NOW())
     LIMIT 1"
);
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    http_response_code(404);
    exit('Không tìm thấy bài viết.');
}

$pageTitle = $item['title'];
require __DIR__ . '/partials/header.php';
?>
<main class="page-shell article-shell">
    <a class="back-link" href="<?= e(url('news.php')) ?>">← TIN TỨC</a>
    <article class="article">
        <div class="news-meta">
            <span><?= (int)$item['is_hot'] === 1 ? 'HOT NEWS' : 'LOS SANTOS NEWS' ?></span>
            <time><?= e(date('d/m/Y H:i', strtotime($item['published_at'] ?: $item['created_at']))) ?></time>
        </div>
        <h1><?= e($item['title']) ?></h1>
        <?php if ($item['excerpt']): ?><p class="article-lead"><?= e($item['excerpt']) ?></p><?php endif; ?>
        <div class="article-body"><?= nl2br(e($item['content'])) ?></div>
    </article>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
