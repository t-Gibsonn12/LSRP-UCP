<?php
require __DIR__ . '/app/bootstrap.php';
require_login();

$errors = [];
$emailErrors = [];
$emailAvailable = true;

try {
    $stmt = db()->prepare("SELECT account_id, username, email, password_hash, register_date FROM player_accounts WHERE account_id = ? LIMIT 1");
    $stmt->execute([current_account_id()]);
    $dbAccount = $stmt->fetch() ?: [];
} catch (Throwable $e) {
    $emailAvailable = false;
    $stmt = db()->prepare("SELECT account_id, username, password_hash, register_date FROM player_accounts WHERE account_id = ? LIMIT 1");
    $stmt->execute([current_account_id()]);
    $dbAccount = $stmt->fetch() ?: [];
    $dbAccount['email'] = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? 'password');

    if ($action === 'email') {
        if (!$emailAvailable) {
            $emailErrors[] = 'Chưa cài migration email của UCP V4.';
        }

        $newEmail = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['email_password'] ?? '');

        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL) || strlen($newEmail) > 190) {
            $emailErrors[] = 'Email mới không hợp lệ.';
        }
        if (!password_verify($password, (string)($dbAccount['password_hash'] ?? ''))) {
            $emailErrors[] = 'Mật khẩu xác nhận không chính xác.';
        }
        if ($newEmail === strtolower((string)($dbAccount['email'] ?? ''))) {
            $emailErrors[] = 'Email mới phải khác email hiện tại.';
        }

        if (!$emailErrors) {
            $stmt = db()->prepare("SELECT account_id FROM player_accounts WHERE email = ? AND account_id <> ? LIMIT 1");
            $stmt->execute([$newEmail, current_account_id()]);
            if ($stmt->fetch()) $emailErrors[] = 'Email này đang được một Master Account khác sử dụng.';
        }

        if (!$emailErrors) {
            $stmt = db()->prepare("UPDATE player_accounts SET email = ? WHERE account_id = ?");
            $stmt->execute([$newEmail, current_account_id()]);
            $_SESSION['account']['email'] = $newEmail;
            flash('success', 'Email Master Account đã được cập nhật. V4 hiện chưa yêu cầu xác nhận email.');
            redirect('account.php#email');
        }
    } else {
        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        if (!password_verify($current, (string)($dbAccount['password_hash'] ?? ''))) {
            $errors[] = 'Mật khẩu hiện tại không chính xác.';
        }
        if (strlen($new) < 6) {
            $errors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
        }
        if ($new !== $confirm) {
            $errors[] = 'Xác nhận mật khẩu mới không khớp.';
        }
        if ($current === $new) {
            $errors[] = 'Mật khẩu mới phải khác mật khẩu hiện tại.';
        }

        if (!$errors) {
            $newHash = password_hash($new, PASSWORD_BCRYPT, [
                'cost' => (int)$GLOBALS['config']['bcrypt_cost']
            ]);

            $stmt = db()->prepare("UPDATE player_accounts SET password_hash = ? WHERE account_id = ?");
            $stmt->execute([$newHash, current_account_id()]);

            session_regenerate_id(true);
            flash('success', 'Đã đổi mật khẩu Master Account. Mật khẩu mới dùng được cả UCP và trong game.');
            redirect('account.php#password');
        }
    }
}

$stmt = db()->prepare("SELECT COUNT(*) FROM player_characters WHERE account_id = ?");
$stmt->execute([current_account_id()]);
$characterCount = (int)$stmt->fetchColumn();

$twoYearsPackage = twoyears_account_package((int)current_account_id());

$ticketCount = 0;
try {
    $stmt = db()->prepare("SELECT COUNT(*) FROM ucp_support_requests WHERE account_id = ? AND status <> 'closed'");
    $stmt->execute([current_account_id()]);
    $ticketCount = (int)$stmt->fetchColumn();
} catch (Throwable $e) {}

$pageTitle = 'Tài khoản';
require __DIR__ . '/partials/header.php';
?>
<main class="page-shell account-page">
    <div class="page-heading">
        <div><span class="eyebrow">MASTER ACCOUNT</span><h1><?= e(current_account()['username']) ?></h1><p>Thông tin, bảo mật và hỗ trợ cho tài khoản chính.</p></div>
        <a class="btn outline" href="<?= e(url('support.php')) ?>">? GỬI YÊU CẦU HỖ TRỢ</a>
    </div>

    <section class="account-overview-grid">
        <article class="account-overview-card">
            <span>MASTER ACCOUNT ID</span><strong>#<?= (int)current_account_id() ?></strong><small>Định danh tài khoản</small>
        </article>
        <article class="account-overview-card">
            <span>EMAIL</span><strong class="account-email-value"><?= e($dbAccount['email'] ?: 'Chưa thiết lập') ?></strong><small>Chưa yêu cầu xác nhận</small>
        </article>
        <article class="account-overview-card">
            <span>NHÂN VẬT</span><strong><?= $characterCount ?> / <?= (int)$GLOBALS['config']['max_characters'] ?></strong><small>Character slots</small>
        </article>
        <article class="account-overview-card">
            <span>HỖ TRỢ ĐANG MỞ</span><strong><?= $ticketCount ?></strong><small>Support requests</small>
        </article>
    </section>

    <?php if ($twoYearsPackage): ?>
    <section class="twoyears-account-card">
        <div class="twoyears-account-card-mark">#TWOYEARS</div>
        <div class="twoyears-account-card-copy">
            <span class="eyebrow">EARLY REGISTRATION ACCOUNT</span>
            <h2>Thẻ #TWOYEARS Account</h2>
            <p>Master Account này thuộc nhóm đăng ký sớm và được giữ quyền lợi Tester, role Discord #TWOYEARS, kênh trao đổi phát triển cùng các đặc quyền được mở theo từng giai đoạn vận hành.</p>
        </div>
        <div class="twoyears-account-card-id">
            <span>CARD ID</span>
            <strong><?= e((string)$twoYearsPackage['card_code']) ?></strong>
            <small>Joined <?= e(date('d/m/Y', strtotime((string)$twoYearsPackage['joined_at']))) ?></small>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!$emailAvailable): ?>
        <div class="form-error">Database chưa có cột email. Import <code>database/INSTALL_UCP_V4.sql</code> để bật đầy đủ V4.</div>
    <?php endif; ?>

    <section class="account-settings-grid">
        <article class="detail-card" id="email">
            <span class="eyebrow">CONTACT EMAIL</span>
            <h2>Đổi email</h2>
            <p class="muted">Email dùng cho liên hệ và sẽ là nền cho hệ thống quên mật khẩu sau này. V4 chưa gửi mail xác nhận.</p>

            <?php foreach ($emailErrors as $error): ?><div class="form-error"><?= e($error) ?></div><?php endforeach; ?>

            <form method="post" class="form-grid account-form">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="email">
                <label><span>Email mới</span><input type="email" name="email" maxlength="190" required value="<?= e((string)($dbAccount['email'] ?? '')) ?>" autocomplete="email"></label>
                <label><span>Mật khẩu hiện tại để xác nhận</span><input type="password" name="email_password" required autocomplete="current-password"></label>
                <button class="btn primary" <?= !$emailAvailable ? 'disabled' : '' ?>>CẬP NHẬT EMAIL <span>→</span></button>
            </form>
        </article>

        <article class="detail-card" id="password">
            <span class="eyebrow">SECURITY</span>
            <h2>Đổi mật khẩu</h2>
            <p class="muted">Mật khẩu thay đổi tại đây dùng chung cho UCP và đăng nhập máy chủ.</p>

            <?php foreach ($errors as $error): ?><div class="form-error"><?= e($error) ?></div><?php endforeach; ?>

            <form method="post" class="form-grid account-form">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="password">
                <label><span>Mật khẩu hiện tại</span><input type="password" name="current_password" required autocomplete="current-password"></label>
                <label><span>Mật khẩu mới</span><input type="password" name="new_password" minlength="6" required autocomplete="new-password"></label>
                <label><span>Xác nhận mật khẩu mới</span><input type="password" name="confirm_password" minlength="6" required autocomplete="new-password"></label>
                <button class="btn primary">CẬP NHẬT MẬT KHẨU <span>→</span></button>
            </form>
        </article>

        <article class="detail-card account-wide" id="security">
            <span class="eyebrow">ACCOUNT CENTER</span>
            <h2>Trung tâm tài khoản</h2>
            <div class="account-action-list">
                <a href="<?= e(url('support.php')) ?>"><span>01</span><div><b>Yêu cầu hỗ trợ</b><small>Gửi ticket trực tiếp tới Ban quản trị UCP.</small></div><strong>→</strong></a>
                <a href="<?= e(url('forgot-password.php')) ?>"><span>02</span><div><b>Khôi phục mật khẩu</b><small>Giao diện đã có, chức năng email recovery sẽ bật sau.</small></div><strong>→</strong></a>
                <a href="<?= e(url('characters.php')) ?>"><span>03</span><div><b>Quản lý nhân vật</b><small>Xem nhân vật, hồ sơ đang chờ và gửi yêu cầu mới.</small></div><strong>→</strong></a>
                <a class="danger-row" href="<?= e(url('logout.php')) ?>"><span>04</span><div><b>Đăng xuất phiên hiện tại</b><small>Kết thúc phiên UCP trên thiết bị này.</small></div><strong>↗</strong></a>
            </div>
        </article>
    </section>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
