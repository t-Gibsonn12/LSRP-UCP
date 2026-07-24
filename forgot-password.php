<?php
require __DIR__ . '/app/bootstrap.php';
if (is_logged_in()) redirect('dashboard.php');

$pageTitle = 'Quên mật khẩu';
$bodyClass = 'auth-page';
require __DIR__ . '/partials/header.php';
?>
<main class="auth-shell compact-auth">
    <section class="auth-story">
        <div class="city-grid"></div>
        <div class="auth-story-content">
            <div class="eyebrow">ACCOUNT RECOVERY</div>
            <h1><span>RESET</span><br>ACCESS</h1>
            <p class="story-copy">Khu vực khôi phục Master Account đã có giao diện và sẵn chỗ để gắn mailer/token ở bản sau.</p>
        </div>
    </section>

    <section class="auth-panel">
        <div class="auth-panel-inner">
            <a class="mini-brand" href="<?= e(url('login.php')) ?>"><span>LS</span><b>LOS SANTOS ROLEPLAY</b><small>USER CONTROL PANEL</small></a>
            <div class="eyebrow">FORGOT PASSWORD</div>
            <h2>Khôi phục tài khoản.</h2>
            <p>Tính năng gửi liên kết đặt lại mật khẩu qua email <strong>chưa được kích hoạt</strong> trong V4.</p>

            <div class="coming-soon-card">
                <span>COMING SOON</span>
                <h3>Email recovery</h3>
                <p>Sau này người chơi sẽ nhập email, nhận token và đặt lại mật khẩu tại đây.</p>
            </div>

            <form class="form-grid auth-form" onsubmit="return false">
                <label><span>Email Master Account</span><input type="email" placeholder="you@example.com" disabled></label>
                <button class="btn disabled wide" type="button" disabled>GỬI LIÊN KẾT KHÔI PHỤC</button>
            </form>

            <div class="auth-register"><a href="<?= e(url('login.php')) ?>">← Quay lại đăng nhập</a></div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
