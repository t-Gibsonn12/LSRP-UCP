<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
$item = [
    'title' => '', 'excerpt' => '', 'content' => '',
    'is_hot' => 0, 'is_published' => 0, 'published_at' => ''
];

if ($id) {
    $stmt = db()->prepare("SELECT * FROM ucp_news WHERE news_id = ? LIMIT 1");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if (!$item) exit('Không tìm thấy bài viết.');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title = trim((string)($_POST['title'] ?? ''));
    $excerpt = trim((string)($_POST['excerpt'] ?? ''));
    $content = trim((string)($_POST['content'] ?? ''));
    $isHot = isset($_POST['is_hot']) ? 1 : 0;
    $isPublished = isset($_POST['is_published']) ? 1 : 0;
    $publishedAt = trim((string)($_POST['published_at'] ?? ''));
    $publishedAt = $publishedAt !== '' ? date('Y-m-d H:i:s', strtotime($publishedAt)) : null;

    if (mb_strlen($title) < 5) $errors[] = 'Tiêu đề quá ngắn.';
    if (mb_strlen($content) < 20) $errors[] = 'Nội dung quá ngắn.';

    if (!$errors) {
        if ($id) {
            $stmt = db()->prepare(
                "UPDATE ucp_news SET title=?, excerpt=?, content=?, is_hot=?, is_published=?,
                 published_at=?, updated_by=? WHERE news_id=?"
            );
            $stmt->execute([$title, $excerpt, $content, $isHot, $isPublished, $publishedAt, current_account_id(), $id]);
            admin_log('news.update', 'news', $id, ['title' => $title, 'published' => $isPublished, 'hot' => $isHot]);
            flash('success', 'Đã cập nhật bài viết.');
        } else {
            $stmt = db()->prepare(
                "INSERT INTO ucp_news
                 (title, excerpt, content, is_hot, is_published, published_at, created_by, updated_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$title, $excerpt, $content, $isHot, $isPublished, $publishedAt, current_account_id(), current_account_id()]);
            $id = (int)db()->lastInsertId();
            admin_log('news.create', 'news', $id, ['title' => $title, 'published' => $isPublished, 'hot' => $isHot]);
            flash('success', 'Đã tạo bài viết.');
        }
        redirect('admin/news.php');
    }

    $item = array_merge($item, [
        'title' => $title, 'excerpt' => $excerpt, 'content' => $content,
        'is_hot' => $isHot, 'is_published' => $isPublished, 'published_at' => $publishedAt
    ]);
}

$pageTitle = $id ? 'Sửa tin tức' : 'Đăng tin';
require dirname(__DIR__) . '/partials/header.php';
?>
<main class="page-shell narrow-page">
    <a class="back-link" href="<?= e(url('admin/news.php')) ?>">← NEWSROOM</a>
    <div class="page-heading"><div><span class="eyebrow">ADMIN · NEWS EDITOR</span><h1><?= $id ? 'Sửa bài viết' : 'Đăng tin mới' ?></h1></div></div>

    <?php foreach ($errors as $error): ?><div class="form-error"><?= e($error) ?></div><?php endforeach; ?>

    <form method="post" class="panel-form">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= $id ?>">
        <label><span>Tiêu đề</span><input name="title" maxlength="180" required value="<?= e($item['title']) ?>"></label>
        <label><span>Mô tả ngắn</span><textarea name="excerpt" rows="3" maxlength="500"><?= e($item['excerpt']) ?></textarea></label>
        <label><span>Nội dung</span><textarea name="content" rows="16" required><?= e($item['content']) ?></textarea></label>
        <label><span>Thời gian đăng</span><input type="datetime-local" name="published_at" value="<?= !empty($item['published_at']) ? e(date('Y-m-d\TH:i', strtotime($item['published_at']))) : '' ?>"></label>
        <div class="check-row">
            <label class="check"><input type="checkbox" name="is_published" <?= (int)$item['is_published'] ? 'checked' : '' ?>><span>Đăng công khai</span></label>
            <label class="check"><input type="checkbox" name="is_hot" <?= (int)$item['is_hot'] ? 'checked' : '' ?>><span>HOT NEWS</span></label>
        </div>
        <button class="btn primary"><?= $id ? 'LƯU THAY ĐỔI' : 'ĐĂNG BÀI' ?></button>
    </form>
</main>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
