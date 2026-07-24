<?php

function is_admin(): bool
{
    if (!is_logged_in()) return false;

    $admins = array_map('strtolower', $GLOBALS['config']['admin_usernames'] ?? []);
    $username = strtolower((string)(current_account()['username'] ?? ''));

    return in_array($username, $admins, true);
}

function require_admin(): void
{
    require_login();

    if (!is_admin()) {
        http_response_code(403);
        exit('Bạn không có quyền truy cập khu vực quản trị.');
    }
}
