<?php
declare(strict_types=1);

/**
 * logout.php
 * Terminates the authenticated session exclusively via a CSRF-verified POST request,
 * clears session variables, invalidates the session cookie, and redirects to login.php.
 */

require_once __DIR__ . '/db.php';

// Enforce POST request for state-changing logout action
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrfToken)) {
    header('Location: dashboard.php');
    exit;
}

// 1. Clear all session data
$_SESSION = [];

// 2. Invalidate the session cookie in the browser
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// 3. Destroy the server-side session storage
session_destroy();

// 4. Start a fresh session solely to display a clean sign-out notice
session_start();
session_regenerate_id(true);
$_SESSION['flash_success'] = 'You have been safely logged out.';

header('Location: login.php');
exit;