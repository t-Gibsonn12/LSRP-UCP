<?php
require __DIR__ . '/app/bootstrap.php';

if (is_logged_in()) redirect('dashboard.php');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim((string)($_POST['username'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if (!preg_match('/^[A-Za-z0-9_]{3,24}$/', $username)) {
        $errors[] = 'Tên tài khoản phải có 3-24 ký tự, chỉ gồm chữ, số hoặc dấu gạch dưới.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $errors[] = 'Email không hợp lệ.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Xác nhận mật khẩu không khớp.';
    }

    if (!$errors) {
        $stmt = db()->prepare("SELECT account_id FROM player_accounts WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        if ($stmt->fetch()) $errors[] = 'Tên tài khoản này đã tồn tại.';
    }

    if (!$errors) {
        try {
            $stmt = db()->prepare("SELECT account_id FROM player_accounts WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) $errors[] = 'Email này đã được sử dụng.';
        } catch (Throwable $e) {
            $errors[] = 'Chưa cài migration email của UCP V4. Hãy import database/INSTALL_UCP_V4.sql.';
        }
    }

    if (!$errors) {
        try {
            $accountId = register_account($username, $email, $password);
            $package = twoyears_register_account_package($accountId);
            $cardCode = $package['card_code'] ?? ('TWY-' . str_pad((string)$accountId, 6, '0', STR_PAD_LEFT));

            ucp_notification_create(
                $accountId,
                'twoyears_joined',
                'Bạn đã tham gia sớm #TWOYEARS',
                'Bạn đã đăng ký tham gia sớm Los Santos Roleplay. Khi nhân vật đầu tiên được tạo và phê duyệt, bạn sẽ nhận quyền lợi của #TWOYEARS MEMBER bao gồm 01 phương tiện Faggio khởi đầu cùng các quyền lợi khác    .',
                'twoyears.php',
                'Xem quyền lợi #TWOYEARS',
                'twoyears-account-' . $accountId,
                ['card_code' => $cardCode, 'package' => '#TWOYEARS']
            );

            if (!login_account_by_id($accountId)) {
                throw new RuntimeException('Không thể tạo phiên đăng nhập sau khi đăng ký.');
            }

            flash('success', 'Tạo Master Account thành công. Bạn đã được đăng nhập tự động.');
            redirect('dashboard.php');
        } catch (Throwable $e) {
            $errors[] = 'Không thể tạo tài khoản lúc này. Kiểm tra migration UCP V4 và thử lại.';
        }
    }
}

$pageTitle = 'Đăng ký';
$bodyClass = 'auth-page';
require __DIR__ . '/partials/header.php';
?>
<main class="auth-shell compact-auth">
    <section class="auth-story">
        <div class="city-grid"></div>
        <div class="auth-story-content">
            <div class="eyebrow">LOS SANTOS ROLEPLAY VIETNAMESE</div>
            <h1><span>MASTER</span><br>ACCOUNT</h1>
            <p class="story-copy">Một tài khoản chính. Tối đa ba nhân vật. Mọi hồ sơ đều được quản lý tại một nơi.</p>
            <div class="register-rules">
                <span><b>01</b><small>Master Account quản lý tối đa ba nhân vật.</small></span>
                <span><b>02</b><small>Email được lưu cho tài khoản, hiện chưa yêu cầu xác nhận.</small></span>
                <span><b>03</b><small>Tài khoản này dùng chung cho UCP và máy chủ.</small></span>
            </div>
        </div>
    </section>

    <section class="auth-panel">
        <div class="auth-panel-inner">
            <a class="mini-brand" href="<?= e(url('login.php')) ?>"><span>LS</span><b>LOS SANTOS ROLEPLAY</b><small>USER CONTROL PANEL</small></a>
            <div class="eyebrow">CREATE MASTER ACCOUNT</div>
            <h2>Tạo tài khoản.</h2>
            <p>Email được lưu ngay từ lúc đăng ký. Bản V4 chưa bật xác nhận email.</p>

            <?php foreach ($errors as $error): ?>
                <div class="form-error"><?= e($error) ?></div>
            <?php endforeach; ?>

            <form method="post" class="form-grid auth-form" autocomplete="on">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <label><span>Tên Master Account</span><input name="username" required maxlength="24" autocomplete="username" value="<?= old('username') ?>" placeholder="duy_1992"><small>3-24 ký tự, chữ/số/dấu gạch dưới.</small></label>
                <label><span>Email</span><input type="email" name="email" required maxlength="190" autocomplete="email" value="<?= old('email') ?>" placeholder="you@example.com"><small>Chưa cần xác nhận email ở phiên bản này.</small></label>
                <label><span>Mật khẩu</span><input type="password" name="password" minlength="6" required autocomplete="new-password" placeholder="Tối thiểu 6 ký tự"></label>
                <label><span>Xác nhận mật khẩu</span><input type="password" name="confirm_password" minlength="6" required autocomplete="new-password" placeholder="Nhập lại mật khẩu"></label>
                <button class="btn primary wide" type="submit">TẠO MASTER ACCOUNT <span>→</span></button>
            </form>

            <div class="auth-register auth-register-box">
                <div><b>Đã có tài khoản?</b><small>Quay lại đăng nhập UCP.</small></div>
                <a class="btn outline" href="<?= e(url('login.php')) ?>">ĐĂNG NHẬP</a>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
