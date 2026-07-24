<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin/news.php');
verify_csrf();

$id = (int)($_POST['id'] ?? 0);
$stmt = db()->prepare("SELECT title FROM ucp_news WHERE news_id = ? LIMIT 1");
$stmt->execute([$id]);
$title = $stmt->fetchColumn();

if ($title !== false) {
    $stmt = db()->prepare("DELETE FROM ucp_news WHERE news_id = ?");
    $stmt->execute([$id]);
    admin_log('news.delete', 'news', $id, ['title' => $title]);
    flash('success', 'Đã xóa bài viết.');
}

redirect('admin/news.php');
