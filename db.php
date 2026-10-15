<?php
declare(strict_types=1);

/**
 * db.php
 * Configures secure PHP sessions, establishes a PDO database connection,
 * and provides helper functions for CSRF protection and HTML escaping.
 */

// 1. Configure secure session cookie parameters before starting the session
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
           || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');

    session_set_cookie_params([
        'lifetime' => 0,          // Session cookie expires when browser closes
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,   // True when served over HTTPS
        'httponly' => true,       // Prevents JavaScript access to session cookie
        'samesite' => 'Strict',   // Mitigates cross-site request forgery
    ]);

    session_start();
}

// 2. Database credentials (default XAMPP local configuration)
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'user_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// 3. Establish PDO connection with safe error handling
$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, // Enforce native prepared statements
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Log detailed error on the server without exposing sensitive details to the user
    error_log('Database Connection Error: ' . $e->getMessage());
    http_response_code(500);
    exit('A system error occurred while connecting to the service. Please try again later.');
}

/**
 * Safely escape strings for HTML output to prevent XSS attacks.
 */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Generate or retrieve the current session's CSRF token.
 */
function generate_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify a submitted CSRF token against the session token.
 */
function verify_csrf_token(?string $token): bool
{
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}