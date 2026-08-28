<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

$stats = ['pending' => 0, 'characters' => 0, 'news' => 0, 'accounts' => 0, 'support' => 0];
try { $stats['pending'] = (int)db()->query("SELECT COUNT(*) FROM character_applications WHERE status='pending'")->fetchColumn(); } catch (Throwable $e) {}
$stats['characters'] = (int)db()->query("SELECT COUNT(*) FROM player_characters")->fetchColumn();
try { $stats['news'] = (int)db()->query("SELECT COUNT(*) FROM ucp_news")->fetchColumn(); } catch (Throwable $e) {}
$stats['accounts'] = (int)db()->query("SELECT COUNT(*) FROM player_accounts")->fetchColumn();
try { $stats['support'] = (int)db()->query("SELECT COUNT(*) FROM ucp_support_requests WHERE status <> 'closed'")->fetchColumn(); } catch (Throwable $e) {}

$pageTitle = 'Operations Center';
require dirname(__DIR__) . '/partials/header.php';
?>
<main class="page-shell">
    <section class="hero admin-hero v5-dashboard-hero">
        <div class="v5-dashboard-copy">
            <span class="eyebrow">LSRP ADMINISTRATION · OPERATIONS CENTER</span>
            <h1>Điều hành<br><span>toàn bộ UCP.</span></h1>
            <p>Xét duyệt Character Application, quản lý dữ liệu nhân vật, newsroom và support từ một trung tâm vận hành duy nhất.</p>
            <div class="hero-actions"><a class="btn primary" href="<?= e(url('admin/applications.php')) ?>">XỬ LÝ HỒ SƠ <span>→</span></a><a class="btn outline" href="<?= e(url('dashboard.php')) ?>">PLAYER UCP</a></div>
        </div>
        <div class="v5-dashboard-art"><div class="v5-seal">ADMIN ACCESS</div></div>
    </section>

    <section class="stat-grid admin-stat-grid">
        <div class="stat-card"><span>HỒ SƠ CHỜ DUYỆT</span><strong><?= $stats['pending'] ?></strong><p><?= $stats['pending'] > 0 ? 'Cần staff xử lý' : 'Queue đã sạch' ?></p></div>
        <div class="stat-card"><span>SUPPORT ĐANG MỞ</span><strong><?= $stats['support'] ?></strong><p>Ticket cần theo dõi</p></div>
        <div class="stat-card"><span>NHÂN VẬT</span><strong><?= $stats['characters'] ?></strong><p>player_characters</p></div>
        <div class="stat-card"><span>MASTER ACCOUNTS</span><strong><?= $stats['accounts'] ?></strong><p><?= $stats['news'] ?> bài newsroom</p></div>
    </section>

    <div class="section-heading"><div><span class="eyebrow">ADMIN MODULES</span><h2>Công cụ vận hành</h2></div><span class="badge success">SYSTEM READY</span></div>
    <section class="admin-actions admin-actions-four">
        <a href="<?= e(url('admin/applications.php')) ?>"><span>01</span><div><h2>Duyệt nhân vật</h2><p>Review, approve hoặc reject Character Application và theo dõi queue chờ.</p></div><b>→</b></a>
        <a href="<?= e(url('admin/characters.php')) ?>"><span>02</span><div><h2>Quản lý nhân vật</h2><p>Kiểm soát tiền, bank, level, skin hiện tại, job, health và armour.</p></div><b>→</b></a>
        <a href="<?= e(url('admin/news.php')) ?>"><span>03</span><div><h2>Newsroom</h2><p>Đăng bài, chỉnh sửa nội dung, quản lý trạng thái hiển thị và HOT NEWS.</p></div><b>→</b></a>
        <a href="<?= e(url('admin/support.php')) ?>"><span>04</span><div><h2>Support Center</h2><p>Xem yêu cầu, phản hồi người chơi và đóng ticket sau khi xử lý xong.</p></div><b>→</b></a>
    </section>
</main>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
