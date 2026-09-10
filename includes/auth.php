<?php
/**
 * includes/auth.php
 * Single shared-login auth. Session expires after SESSION_TIMEOUT_SECONDS
 * of inactivity (checked on every admin request, not just cookie age).
 */

require_once __DIR__ . '/functions.php';

function is_logged_in(): bool
{
    if (empty($_SESSION['is_admin'])) {
        return false;
    }
    $lastActivity = $_SESSION['last_activity'] ?? 0;
    if (time() - $lastActivity > SESSION_TIMEOUT_SECONDS) {
        log_out();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

function attempt_login(string $password): bool
{
    $settings = get_settings();
    $hash = $settings['admin_password_hash'] ?? '';
    if (!$hash || !password_verify($password, $hash)) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['is_admin'] = true;
    $_SESSION['last_activity'] = time();
    return true;
}

function log_out(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

/** Call at the top of every admin page (except login/setup). Redirects to login if needed. */
function require_login(): void
{
    if (!is_logged_in()) {
        redirect('login.php');
    }
}
