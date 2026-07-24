<?php

function current_account(): ?array
{
    return $_SESSION['account'] ?? null;
}

function current_account_id(): ?int
{
    return isset($_SESSION['account']['account_id'])
        ? (int)$_SESSION['account']['account_id']
        : null;
}

function is_logged_in(): bool
{
    return current_account_id() !== null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('warning', 'Vui lòng đăng nhập bằng Master Account.');
        redirect('login.php');
    }
}

function establish_account_session(array $account): void
{
    session_regenerate_id(true);
    $_SESSION['account'] = [
        'account_id' => (int)$account['account_id'],
        'username' => (string)$account['username'],
        'email' => $account['email'] ?? null,
        'register_date' => $account['register_date'] ?? ($account['created_at'] ?? null),
    ];
}

function login_account_by_id(int $accountId): bool
{
    $stmt = db()->prepare("SELECT * FROM player_accounts WHERE account_id = ? LIMIT 1");
    $stmt->execute([$accountId]);
    $account = $stmt->fetch();
    if (!$account) return false;

    establish_account_session($account);
    return true;
}

function attempt_login(string $username, string $password): bool
{
    $stmt = db()->prepare("SELECT * FROM player_accounts WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $account = $stmt->fetch();

    if (!$account || !password_verify($password, $account['password_hash'] ?? '')) {
        return false;
    }

    establish_account_session($account);
    return true;
}

function register_account(string $username, string $email, string $password): int
{
    $hash = password_hash($password, PASSWORD_BCRYPT, [
        'cost' => (int)$GLOBALS['config']['bcrypt_cost']
    ]);

    $stmt = db()->prepare(
        "INSERT INTO player_accounts (username, email, password_hash) VALUES (?, ?, ?)"
    );
    $stmt->execute([$username, strtolower($email), $hash]);

    return (int)db()->lastInsertId();
}
