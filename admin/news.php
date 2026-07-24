<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$rows = db()->query("SELECT * FROM ucp_news ORDER BY created_at DESC, news_id DESC")->fetchAll();

$pageTitle = 'Quản lý tin tức';
require dirname(__DIR__) . '/partials/header.php';
?>
<main class="page-shell">
    <div class="page-heading">
        <div><span class="eyebrow">ADMIN · NEWSROOM</span><h1>Quản lý tin tức</h1><p>Tin được lưu trực tiếp trong SQL.</p></div>
        <a class="btn primary" href="<?= e(url('admin/news-edit.php')) ?>">+ ĐĂNG TIN</a>
    </div>

    <section class="table-panel">
        <div class="table-head news-admin-head"><span>BÀI VIẾT</span><span>TRẠNG THÁI</span><span>HOT</span><span>THAO TÁC</span></div>
        <?php foreach ($rows as $row): ?>
        <div class="table-row news-admin-head">
            <div><strong><?= e($row['title']) ?></strong><small><?= e($row['created_at']) ?></small></div>
            <div><span class="badge <?= (int)$row['is_published'] ? 'success' : 'warning' ?>"><?= (int)$row['is_published'] ? 'Đã đăng' : 'Bản nháp' ?></span></div>
            <div><?= (int)$row['is_hot'] ? '<span class="badge danger">HOT</span>' : '—' ?></div>
            <div class="row-actions">
                <a class="text-link" href="<?= e(url('admin/news-edit.php?id=' . (int)$row['news_id'])) ?>">SỬA</a>
                <form method="post" action="<?= e(url('admin/news-delete.php')) ?>" onsubmit="return confirm('Xóa bài viết này?')">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= (int)$row['news_id'] ?>">
                    <button class="link-danger" type="submit">XÓA</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </section>
</main>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
