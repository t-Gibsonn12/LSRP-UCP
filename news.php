<?php
require __DIR__ . '/app/bootstrap.php';

$news = [];
$error = null;
try {
    $stmt = db()->query(
        "SELECT * FROM ucp_news
         WHERE is_published = 1
         AND (published_at IS NULL OR published_at <= NOW())
         ORDER BY is_hot DESC, COALESCE(published_at, created_at) DESC, news_id DESC"
    );
    $news = $stmt->fetchAll();
} catch (Throwable $e) {
    $error = 'Chưa cài bảng tin tức UCP.';
}

$pageTitle = 'Tin tức';
require __DIR__ . '/partials/header.php';
?>
<main class="page-shell">
    <section class="hero news-hero">
        <div><span class="eyebrow">LOS SANTOS NEWSROOM</span><h1>Tin tức <span>cộng đồng.</span></h1><p>Thông báo, cập nhật từ Los Santos Roleplay Vietnamese.</p></div>
    </section>

    <?php if ($error): ?><div class="form-error"><?= e($error) ?></div><?php endif; ?>

    <section class="news-grid">
        <?php foreach ($news as $item): ?>
        <a class="news-card <?= (int)$item['is_hot'] === 1 ? 'hot' : '' ?>" href="<?= e(url('news-view.php?id=' . (int)$item['news_id'])) ?>">
            <div class="news-meta">
                <span><?= (int)$item['is_hot'] === 1 ? 'HOT NEWS' : 'NEWS' ?></span>
                <time><?= e(date('d/m/Y', strtotime($item['published_at'] ?: $item['created_at']))) ?></time>
            </div>
            <h2><?= e($item['title']) ?></h2>
            <p><?= e($item['excerpt'] ?: mb_substr($item['content'], 0, 180)) ?></p>
            <strong>ĐỌC TIẾP →</strong>
        </a>
        <?php endforeach; ?>

        <?php if (!$news && !$error): ?><div class="empty-state"><h2>Chưa có tin được đăng.</h2></div><?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
