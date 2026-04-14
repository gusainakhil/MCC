<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!function_exists('ud_auth_user')) {
    function ud_auth_user()
    {
        return isset($_SESSION['mcc_user']) && is_array($_SESSION['mcc_user']) ? $_SESSION['mcc_user'] : null;
    }
}

if (!function_exists('ud_is_authenticated')) {
    function ud_is_authenticated()
    {
        return ud_auth_user() !== null;
    }
}

if (!function_exists('ud_require_auth')) {
    function ud_require_auth($redirectTo = 'login.php')
    {
        if (!ud_is_authenticated()) {
            header('Location: ' . $redirectTo);
            exit;
        }
    }
}

if (!function_exists('ud_login_user')) {
    function ud_login_user(array $userRow)
    {
        $_SESSION['mcc_user'] = [
            'user_id' => (int) ($userRow['user_id'] ?? 0),
            'user_name' => (string) ($userRow['user_name'] ?? ''),
            'username' => (string) ($userRow['username'] ?? ''),
            'full_name' => (string) ($userRow['full_name'] ?? ''),
            'role' => (string) ($userRow['role'] ?? ''),
            'status' => (string) ($userRow['status'] ?? 'Active'),
        ];
    }
}

if (!function_exists('ud_logout_user')) {
    function ud_logout_user()
    {
        unset($_SESSION['mcc_user']);
    }
}

if (!function_exists('ud_auth_redirect_if_logged_in')) {
    function ud_auth_redirect_if_logged_in($redirectTo = 'index.php')
    {
        if (ud_is_authenticated()) {
            header('Location: ' . $redirectTo);
            exit;
        }
    }
}

if (!function_exists('ud_authenticated_user_id')) {
    function ud_authenticated_user_id()
    {
        $user = ud_auth_user();
        return $user ? (int) ($user['user_id'] ?? 0) : 0;
    }
}
