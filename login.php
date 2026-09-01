<?php
require __DIR__ . '/app/bootstrap.php';

if (is_logged_in()) redirect('dashboard.php');

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (attempt_login($username, $password)) {
        redirect('dashboard.php');
    }

    $error = 'Tên tài khoản hoặc mật khẩu không chính xác.';
}

$hotNews = latest_hot_news();
$pageTitle = 'Đăng nhập';
$bodyClass = 'auth-page';
require __DIR__ . '/partials/header.php';
?>
<main class="auth-shell">
    <section class="auth-story">
        <div class="city-grid"></div>
        <div class="auth-story-content">
            <div class="eyebrow">LOS SANTOS · SAN ANDREAS · 1992</div>
            <h1><span>#TWO</span><br>YEARS</h1>
            <p class="story-copy">Two Years to back</p>

            <div class="story-points">
                <span><b>01</b> Master Account</span>
                <span><b>02</b> Character Management</span>
                <span><b>03</b> Heavy Roleplay</span>
            </div>

            <?php if ($hotNews): ?>
            <a class="hot-news-card" href="<?= e(url('news-view.php?id=' . (int)$hotNews['news_id'])) ?>">
                <div class="hot-news-meta">
                    <strong>HOT NEWS</strong>
                    <span><?= e(date('d/m/Y', strtotime($hotNews['published_at'] ?: $hotNews['created_at']))) ?></span>
                </div>
                <h2><?= e($hotNews['title']) ?></h2>
                <p><?= e($hotNews['excerpt'] ?: mb_substr($hotNews['content'], 0, 180)) ?></p>
                <span class="read-more">ĐỌC TIN →</span>
            </a>
            <?php else: ?>
            <div class="hot-news-card muted-card">
                <div class="hot-news-meta"><strong>HOT NEWS</strong><span>LOS SANTOS · 1992</span></div>
                <h2>#TWO YEARS — Chặng đường tiếp theo</h2>
                <p>Tin nổi bật sẽ xuất hiện tại đây sau khi Ban quản trị đăng bài đầu tiên.</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="auth-panel">
        <div class="auth-panel-inner">
            <a class="mini-brand" href="<?= e(url('login.php')) ?>"><span>LS</span><b>LOS SANTOS ROLEPLAY</b><small>SAMP · USER CONTROL PANEL</small></a>
            <div class="eyebrow">MASTER ACCOUNT</div>
            <h2>Chào mừng trở lại.</h2>
            <p>Đăng nhập bằng tài khoản chính đang sử dụng trên máy chủ.</p>

            <?php if ($error): ?><div class="form-error"><?= e($error) ?></div><?php endif; ?>

            <form method="post" class="form-grid auth-form">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <label>
                    <span>Tên tài khoản</span>
                    <input name="username" maxlength="24" required autocomplete="username" value="<?= old('username') ?>" placeholder="master_account">
                </label>
                <label>
                    <span class="field-label-row"><b>Mật khẩu</b><a href="<?= e(url('forgot-password.php')) ?>">Quên mật khẩu?</a></span>
                    <input type="password" name="password" required autocomplete="current-password" placeholder="••••••••••••">
                </label>
                <button class="btn primary wide" type="submit">ĐĂNG NHẬP UCP <span>→</span></button>
            </form>

            <div class="auth-register auth-register-box">
                <div><b>Chưa có Master Account?</b><small>Tạo tài khoản UCP và dùng chung với máy chủ.</small></div>
                <a class="btn outline" href="<?= e(url('register.php')) ?>">ĐĂNG KÝ</a>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
