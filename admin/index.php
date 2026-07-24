<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$stats = ['pending' => 0, 'characters' => 0, 'news' => 0, 'accounts' => 0, 'support' => 0];
try { $stats['pending'] = (int)db()->query("SELECT COUNT(*) FROM character_applications WHERE status='pending'")->fetchColumn(); } catch (Throwable $e) {}
$stats['characters'] = (int)db()->query("SELECT COUNT(*) FROM player_characters")->fetchColumn();
try { $stats['news'] = (int)db()->query("SELECT COUNT(*) FROM ucp_news")->fetchColumn(); } catch (Throwable $e) {}
$stats['accounts'] = (int)db()->query("SELECT COUNT(*) FROM player_accounts")->fetchColumn();
try { $stats['support'] = (int)db()->query("SELECT COUNT(*) FROM ucp_support_requests WHERE status <> 'closed'")->fetchColumn(); } catch (Throwable $e) {}

$pageTitle = 'Quản trị UCP';
require dirname(__DIR__) . '/partials/header.php';
?>
<main class="page-shell">
    <section class="hero admin-hero">
        <div><span class="eyebrow">ADMIN CONTROL CENTER</span><h1>Quản trị <span>UCP.</span></h1><p>Duyệt hồ sơ, quản lý nhân vật, newsroom và hỗ trợ người chơi từ một nơi.</p></div>
    </section>

    <section class="stat-grid admin-stat-grid">
        <div class="stat-card"><span>HỒ SƠ CHỜ</span><strong><?= $stats['pending'] ?></strong><p>Cần xử lý</p></div>
        <div class="stat-card"><span>SUPPORT ĐANG MỞ</span><strong><?= $stats['support'] ?></strong><p>Ticket hỗ trợ</p></div>
        <div class="stat-card"><span>NHÂN VẬT</span><strong><?= $stats['characters'] ?></strong><p>player_characters</p></div>
        <div class="stat-card"><span>MASTER ACCOUNTS</span><strong><?= $stats['accounts'] ?></strong><p>Đã đăng ký</p></div>
    </section>

    <section class="admin-actions admin-actions-four">
        <a href="<?= e(url('admin/applications.php')) ?>"><span>01</span><div><h2>Duyệt nhân vật</h2><p>Approve / reject Character Application.</p></div><b>→</b></a>
        <a href="<?= e(url('admin/characters.php')) ?>"><span>02</span><div><h2>Quản lý nhân vật</h2><p>Tiền mặt, ngân hàng, level, skin, job, HP và armour.</p></div><b>→</b></a>
        <a href="<?= e(url('admin/news.php')) ?>"><span>03</span><div><h2>Newsroom</h2><p>Đăng, sửa, ẩn và chọn HOT NEWS.</p></div><b>→</b></a>
        <a href="<?= e(url('admin/support.php')) ?>"><span>04</span><div><h2>Support Center</h2><p>Xem, phản hồi và đóng yêu cầu hỗ trợ từ người chơi.</p></div><b>→</b></a>
    </section>
</main>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
