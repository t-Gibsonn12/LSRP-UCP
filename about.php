<?php
require __DIR__ . '/app/bootstrap.php';
$pageTitle = 'Giới thiệu';
$bodyClass = 'public-page';
require __DIR__ . '/partials/header.php';
?>
<?php if (!is_logged_in()): ?>
<nav class="public-nav" aria-label="Điều hướng công khai">
    <a class="public-brand" href="<?= e(url()) ?>">
        <span class="brand-mark">LS</span>
        <span><strong>LOS SANTOS ROLEPLAY</strong><small>VIETNAMESE · UCP</small></span>
    </a>
    <div class="public-links">
        <a href="<?= e(url()) ?>">Trang chủ</a>
        <a href="<?= e(url('about.php')) ?>">Giới thiệu</a>
        <a href="<?= e(url('login.php')) ?>">Đăng nhập</a>
        <a class="btn primary" href="<?= e(url('register.php')) ?>">TẠO MASTER ACCOUNT</a>
    </div>
</nav>
<?php endif; ?>

<main>
    <section class="about-hero">
        <span class="eyebrow">ABOUT LOS SANTOS ROLEPLAY</span>
        <h1>Một thành phố.<br><span>Vô số câu chuyện.</span></h1>
        <p>Los Santos Roleplay Vietnamese được định hướng như một cộng đồng Heavy Roleplay nơi người chơi xây dựng nhân vật có nền tảng, mục tiêu và sự phát triển dài hạn. Mỗi quyết định trong game nên đóng góp vào câu chuyện của nhân vật thay vì chỉ tối ưu chỉ số.</p>
        <div class="public-hero-actions">
            <?php if (is_logged_in()): ?>
                <a class="btn primary" href="<?= e(url('dashboard.php')) ?>">VỀ UCP <span>→</span></a>
                <a class="btn outline" href="<?= e(url('characters.php')) ?>">NHÂN VẬT CỦA TÔI</a>
            <?php else: ?>
                <a class="btn primary" href="<?= e(url('register.php')) ?>">THAM GIA LSRP <span>→</span></a>
                <a class="btn outline" href="<?= e(url('login.php')) ?>">ĐĂNG NHẬP</a>
            <?php endif; ?>
        </div>
    </section>

    <section class="public-content">
        <div class="public-section-head">
            <div><span class="eyebrow">OUR DIRECTION</span><h2>Roleplay có chiều sâu và tính liên tục.</h2></div>
            <p>UCP được thiết kế như một hồ sơ số của cuộc sống nhân vật: Master Account quản lý danh tính, còn mỗi character giữ skin hiện tại, tài chính, nghề nghiệp, trạng thái và tài sản thuộc riêng nhân vật đó.</p>
        </div>
        <div class="about-pillar-grid">
            <article class="about-pillar"><small>01 · IMMERSION</small><h3>Nhập vai có bối cảnh</h3><p>Nhân vật không chỉ là tên và skin. Hồ sơ cá nhân, ngày sinh, nơi sinh, chiều cao, cân nặng và nghề nghiệp giúp mỗi người có điểm bắt đầu rõ ràng.</p></article>
            <article class="about-pillar"><small>02 · CONSEQUENCE</small><h3>Lựa chọn tạo ra lịch sử</h3><p>Tiến trình, tài sản và trạng thái nhân vật được lưu theo Character ID để những gì diễn ra trong game có thể phản ánh lại trên UCP.</p></article>
            <article class="about-pillar"><small>03 · COMMUNITY</small><h3>Cộng đồng là trung tâm</h3><p>News, thông báo và Support Center giúp người chơi theo dõi thay đổi, nhận phản hồi và làm việc trực tiếp với đội ngũ quản trị.</p></article>
            <article class="about-pillar"><small>04 · LONG TERM</small><h3>Phát triển dài hạn</h3><p>Kiến trúc UCP hướng tới việc mở rộng faction, business, property, inventory, transaction history và nhiều dữ liệu gameplay khác khi gamemode tích hợp sâu hơn.</p></article>
        </div>
    </section>

    <section class="public-content" style="padding-top:0">
        <div class="public-section-head"><div><span class="eyebrow">YOUR JOURNEY</span><h2>Từ Master Account đến một cuộc đời tại Los Santos.</h2></div><p>Luồng UCP được giữ đơn giản: tài khoản là lớp xác thực; character mới là danh tính Roleplay thực sự.</p></div>
        <div class="about-flow">
            <div><b>01</b><strong>Tạo tài khoản</strong><span>Đăng ký Master Account và bảo mật thông tin đăng nhập.</span></div>
            <div><b>02</b><strong>Gửi hồ sơ</strong><span>Chọn slot, xây dựng thông tin và gửi Character Application.</span></div>
            <div><b>03</b><strong>Được xét duyệt</strong><span>Đội ngũ quản trị kiểm tra hồ sơ trước khi nhân vật bước vào game.</span></div>
            <div><b>04</b><strong>Sống câu chuyện</strong><span>Profile UCP phản ánh skin, tiến trình và tài sản đúng của nhân vật.</span></div>
        </div>
    </section>
</main>

<?php if (!is_logged_in()): ?>
<div class="public-footer"><span>© <?= date('Y') ?> LOS SANTOS ROLEPLAY VIETNAMESE</span><span>UCP V5 · Giới thiệu</span></div>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
