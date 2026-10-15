<?php
declare(strict_types=1);

/**
 * login.php
 * Authenticates users via username or email, enforces login rate limiting,
 * verifies hashed passwords, regenerates session IDs, and redirects to dashboard.php.
 */

require_once __DIR__ . '/db.php';

// Redirect already authenticated users directly to their dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Configure session-based brute-force rate limiting (max 5 failed attempts per 15 minutes)
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION_SECONDS', 900); // 15 minutes

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['lockout_until']  = 0;
}

$error = '';
$identifier = '';

// Retrieve and clear one-time registration or logout success messages
$flashSuccess = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken  = $_POST['csrf_token'] ?? '';
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    // 1. Check if client is currently rate-limited
    if (time() < $_SESSION['lockout_until']) {
        $remainingMinutes = (int)ceil(($_SESSION['lockout_until'] - time()) / 60);
        $error = "Too many failed login attempts. Please wait {$remainingMinutes} minute(s) before trying again.";
    } elseif (!verify_csrf_token($csrfToken)) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } elseif ($identifier === '' || $password === '') {
        $error = 'Please enter both your username/email and password.';
    } else {
        try {
            // 2. Query user by username OR email using a prepared statement
            $stmt = $pdo->prepare(
                'SELECT id, password FROM users WHERE username = :id_username OR email = :id_email LIMIT 1'
            );
            $stmt->execute([
                ':id_username' => $identifier,
                ':id_email'    => $identifier,
            ]);
            $user = $stmt->fetch();

            // 3. Verify password hash securely
            if ($user && password_verify($password, $user['password'])) {
                // Reset rate-limiting counters
                $_SESSION['login_attempts'] = 0;
                $_SESSION['lockout_until']  = 0;

                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);

                // Store only the authenticated user's unique ID in the session
                $_SESSION['user_id']       = (int)$user['id'];
                $_SESSION['authenticated'] = true;
                $_SESSION['csrf_token']    = bin2hex(random_bytes(32));

                header('Location: dashboard.php');
                exit;
            } else {
                // Record failed attempt and display a generic message
                $_SESSION['login_attempts']++;

                if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
                    $_SESSION['lockout_until'] = time() + LOCKOUT_DURATION_SECONDS;
                    $error = 'Too many failed login attempts. Your login has been temporarily locked for 15 minutes.';
                } else {
                    $error = 'Invalid username/email or password.';
                }
            }
        } catch (PDOException $e) {
            error_log('Login Error: ' . $e->getMessage());
            $error = 'An unexpected error occurred. Please try again later.';
        }
    }
}

$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | MyAccount Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="auth-wrapper">
        <section class="auth-card" aria-labelledby="login-heading">
            <div class="auth-header">
                <span class="brand-badge">MyAccount</span>
                <h1 id="login-heading">Welcome Back</h1>
                <p>Sign in with your username or email address.</p>
            </div>

            <?php if ($flashSuccess !== ''): ?>
                <div class="alert alert-success" role="status">
                    <?= e($flashSuccess) ?>
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="alert alert-error" role="alert">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form id="loginForm" action="login.php" method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

                <div class="form-group">
                    <label for="identifier">Username or Email Address</label>
                    <input
                        type="text"
                        id="identifier"
                        name="identifier"
                        value="<?= e($identifier) ?>"
                        placeholder="Enter your username or email"
                        required
                        autocomplete="username"
                        aria-describedby="identifierError"
                    >
                    <span id="identifierError" class="field-error" role="status"></span>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-input-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                            aria-describedby="loginPasswordError"
                        >
                        <button type="button" class="toggle-password" data-target="password" aria-label="Show password">
                            Show
                        </button>
                    </div>
                    <span id="loginPasswordError" class="field-error" role="status"></span>
                </div>

                <button type="submit" class="btn btn-primary btn-full">Sign In</button>
            </form>

            <p class="auth-footer">
                Don't have an account yet? <a href="register.php">Create an account</a>
            </p>
        </section>
    </main>
    <script src="script.js"></script>
</body>
</html>