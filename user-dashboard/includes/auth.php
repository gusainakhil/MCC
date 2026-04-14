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

if (!function_exists('ud_auth_role')) {
    function ud_auth_role()
    {
        $user = ud_auth_user();
        return $user ? strtoupper((string) ($user['role'] ?? '')) : '';
    }
}

if (!function_exists('ud_is_authenticated')) {
    function ud_is_authenticated()
    {
        return ud_auth_user() !== null;
    }
}

if (!function_exists('ud_require_auth')) {
    function ud_require_auth($redirectTo = '../login.php')
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

if (!function_exists('ud_role_home_path')) {
    function ud_role_home_path($role, $userId = 0, $fromUserDashboard = false)
    {
        $role = strtoupper((string) $role);

        if ($role === 'ORG_ADMIN') {
            $base = $fromUserDashboard ? 'index.php' : 'user-dashboard/index.php';
            return $base . '?user_id=' . (int) $userId;
        }

        return $fromUserDashboard ? '../index.php' : 'index.php';
    }
}

if (!function_exists('ud_redirect_authenticated_user')) {
    function ud_redirect_authenticated_user($fromUserDashboard = false)
    {
        $user = ud_auth_user();
        if ($user) {
            $target = ud_role_home_path($user['role'] ?? '', (int) ($user['user_id'] ?? 0), $fromUserDashboard);
            header('Location: ' . $target);
            exit;
        }
    }
}

if (!function_exists('ud_require_org_admin_dashboard')) {
    function ud_require_org_admin_dashboard($fallback = '../index.php')
    {
        $role = ud_auth_role();
        if ($role !== 'ORG_ADMIN') {
            header('Location: ' . $fallback);
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
