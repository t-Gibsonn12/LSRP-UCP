<?php
require __DIR__ . '/app/bootstrap.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$pageTitle = 'Los Santos Roleplay Vietnamese';
$bodyClass = 'public-page';
require __DIR__ . '/partials/header.php';
?>
<nav class="public-nav" aria-label="Điều hướng công khai">
    <a class="public-brand" href="<?= e(url()) ?>">
        <span class="brand-mark">LS</span>
        <span><strong>LOS SANTOS ROLEPLAY</strong><small>SA-MP · VIETNAMESE UCP</small></span>
    </a>
    <div class="public-links">
        <a href="#experience">Trải nghiệm</a>
        <a href="<?= e(url('about.php')) ?>">Giới thiệu</a>
        <a href="<?= e(url('login.php')) ?>">Đăng nhập</a>
        <a class="btn primary" href="<?= e(url('register.php')) ?>">TẠO MASTER ACCOUNT</a>
    </div>
</nav>

<main>
    <section class="public-hero">
        <div class="public-hero-copy">
            <span class="eyebrow">LOS SANTOS · 1992 · HEAVY ROLEPLAY</span>
            <h1>Sống một<br><span>cuộc đời khác.</span></h1>
            <p>Los Santos Roleplay Vietnamese là không gian nhập vai tập trung vào danh tính nhân vật, câu chuyện dài hạn và những lựa chọn có hậu quả. UCP là trung tâm quản lý Master Account, hồ sơ nhân vật và toàn bộ dữ liệu gắn với cuộc sống Roleplay của bạn.</p>
            <div class="public-hero-actions">
                <a class="btn primary" href="<?= e(url('register.php')) ?>">BẮT ĐẦU HỒ SƠ <span>→</span></a>
                <a class="btn outline" href="<?= e(url('about.php')) ?>">GIỚI THIỆU LSRP</a>
            </div>
        </div>
        <div class="public-hero-art" aria-hidden="true"><div class="public-scan"></div></div>
    </section>

    <section class="public-content" id="experience">
        <div class="public-section-head">
            <div><span class="eyebrow">THE EXPERIENCE</span><h2>Không chỉ là một tài khoản game.</h2></div>
            <p>Mỗi character là một danh tính độc lập. Skin hiện tại, tiền bạc, công việc, trạng thái và phương tiện chỉ xuất hiện khi dữ liệu thực sự thuộc về nhân vật đó. UCP được xây để phản chiếu đúng thế giới trong game, không phải một catalog rời rạc.</p>
        </div>
        <div class="public-feature-grid">
            <article class="public-feature"><span>01</span><h3>Character First</h3><p>Tạo hồ sơ Roleplay, chờ xét duyệt và quản lý từng nhân vật theo slot riêng. Mỗi profile giữ dữ liệu cá nhân, tiến trình và trạng thái độc lập.</p></article>
            <article class="public-feature"><span>02</span><h3>Tài sản đúng chủ</h3><p>Phương tiện chỉ hiển thị khi thuộc đúng Character ID. Khi dữ liệu game thay đổi, hồ sơ UCP phản ánh lại trạng thái sở hữu tương ứng.</p></article>
            <article class="public-feature"><span>03</span><h3>Portal dài hạn</h3><p>News, notifications, support, account security và khu vực quản trị cùng nằm trong một hệ thống để phục vụ cộng đồng lâu dài.</p></article>
        </div>
    </section>

    <section class="public-content" style="padding-top:0">
        <div class="about-pillar-grid">
            <article class="about-pillar"><small>ROLEPLAY STANDARD</small><h3>Danh tính trước, gameplay sau</h3><p>Tên, tuổi, ngoại hình, nguồn gốc và bối cảnh nhân vật là nền tảng để bước vào Los Santos với một câu chuyện rõ ràng.</p></article>
            <article class="about-pillar"><small>UCP PHILOSOPHY</small><h3>Một nguồn dữ liệu thống nhất</h3><p>UCP được tổ chức xoay quanh Master Account và Character ID để thuận lợi cho việc đồng bộ trực tiếp với hệ thống game sau này.</p></article>
        </div>
        <div class="public-hero-actions" style="margin-top:24px">
            <a class="btn primary" href="<?= e(url('register.php')) ?>">TẠO TÀI KHOẢN</a>
            <a class="btn outline" href="<?= e(url('login.php')) ?>">ĐÃ CÓ TÀI KHOẢN</a>
        </div>
    </section>
</main>

<div class="public-footer">
    <span>© <?= date('Y') ?> LOS SANTOS ROLEPLAY VIETNAMESE</span>
    <span>UCP V5 · Heavy Roleplay Portal</span>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
