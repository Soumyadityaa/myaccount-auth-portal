<?php
declare(strict_types=1);

/**
 * register.php
 * Handles new user registration with server-side validation,
 * uniqueness checks, password hashing, and CSRF protection.
 */

require_once __DIR__ . '/db.php';

// Redirect already authenticated users directly to their dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$formData = [
    'name'     => '',
    'username' => '',
    'email'    => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        $errors['general'] = 'Invalid security token. Please refresh the page and try again.';
    } else {
        // 2. Collect and sanitize input
        $name            = trim($_POST['name'] ?? '');
        $username        = trim($_POST['username'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $password        = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $formData['name']     = $name;
        $formData['username'] = $username;
        $formData['email']    = $email;

        // 3. Validate Full Name
        if ($name === '') {
            $errors['name'] = 'Full name is required.';
        } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $errors['name'] = 'Full name must be between 2 and 100 characters.';
        }

        // 4. Validate Username
        if ($username === '') {
            $errors['username'] = 'Username is required.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            $errors['username'] = 'Username must be 3–50 characters and contain only letters, numbers, or underscores.';
        }

        // 5. Validate Email Address
        if ($email === '') {
            $errors['email'] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        // 6. Validate Password & Confirmation
        if ($password === '') {
            $errors['password'] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long.';
        }

        if ($confirmPassword === '') {
            $errors['confirm_password'] = 'Please confirm your password.';
        } elseif ($password !== $confirmPassword) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        // 7. Check uniqueness of username and email in MySQL
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare(
                    'SELECT username, email FROM users WHERE username = :username OR email = :email LIMIT 2'
                );
                $stmt->execute([
                    ':username' => $username,
                    ':email'    => $email,
                ]);
                $existingUsers = $stmt->fetchAll();

                foreach ($existingUsers as $existing) {
                    if (strcasecmp($existing['username'], $username) === 0) {
                        $errors['username'] = 'This username is already taken.';
                    }
                    if (strcasecmp($existing['email'], $email) === 0) {
                        $errors['email'] = 'An account with this email address already exists.';
                    }
                }

                // 8. Insert new account with hashed password
                if (empty($errors)) {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                    $insertStmt = $pdo->prepare(
                        'INSERT INTO users (name, username, email, password) VALUES (:name, :username, :email, :password)'
                    );
                    $insertStmt->execute([
                        ':name'     => $name,
                        ':username' => $username,
                        ':email'    => $email,
                        ':password' => $passwordHash,
                    ]);

                    $_SESSION['flash_success'] = 'Account created successfully! Please log in with your credentials.';
                    header('Location: login.php');
                    exit;
                }
            } catch (PDOException $e) {
                error_log('Registration Error: ' . $e->getMessage());
                $errors['general'] = 'Could not complete registration at this time. Please try again.';
            }
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
    <title>Create Account | MyAccount Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="auth-wrapper">
        <section class="auth-card" aria-labelledby="register-heading">
            <div class="auth-header">
                <span class="brand-badge">MyAccount</span>
                <h1 id="register-heading">Create an Account</h1>
                <p>Register to access your personalized dashboard.</p>
            </div>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-error" role="alert">
                    <?= e($errors['general']) ?>
                </div>
            <?php endif; ?>

            <form id="registerForm" action="register.php" method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?= e($formData['name']) ?>"
                        placeholder="e.g., Rahul Sharma"
                        required
                        autocomplete="name"
                        aria-describedby="nameError"
                        class="<?= isset($errors['name']) ? 'input-invalid' : '' ?>"
                    >
                    <span id="nameError" class="field-error" role="status">
                        <?= isset($errors['name']) ? e($errors['name']) : '' ?>
                    </span>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="<?= e($formData['username']) ?>"
                        placeholder="e.g., rahul_s"
                        required
                        autocomplete="username"
                        aria-describedby="usernameError"
                        class="<?= isset($errors['username']) ? 'input-invalid' : '' ?>"
                    >
                    <span id="usernameError" class="field-error" role="status">
                        <?= isset($errors['username']) ? e($errors['username']) : '' ?>
                    </span>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= e($formData['email']) ?>"
                        placeholder="rahul@example.com"
                        required
                        autocomplete="email"
                        aria-describedby="emailError"
                        class="<?= isset($errors['email']) ? 'input-invalid' : '' ?>"
                    >
                    <span id="emailError" class="field-error" role="status">
                        <?= isset($errors['email']) ? e($errors['email']) : '' ?>
                    </span>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-input-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Minimum 8 characters"
                            required
                            minlength="8"
                            autocomplete="new-password"
                            aria-describedby="passwordError"
                            class="<?= isset($errors['password']) ? 'input-invalid' : '' ?>"
                        >
                        <button type="button" class="toggle-password" data-target="password" aria-label="Show password">
                            Show
                        </button>
                    </div>
                    <span id="passwordError" class="field-error" role="status">
                        <?= isset($errors['password']) ? e($errors['password']) : '' ?>
                    </span>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-input-wrapper">
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Re-enter your password"
                            required
                            minlength="8"
                            autocomplete="new-password"
                            aria-describedby="confirmPasswordError"
                            class="<?= isset($errors['confirm_password']) ? 'input-invalid' : '' ?>"
                        >
                        <button type="button" class="toggle-password" data-target="confirm_password" aria-label="Show confirm password">
                            Show
                        </button>
                    </div>
                    <span id="confirmPasswordError" class="field-error" role="status">
                        <?= isset($errors['confirm_password']) ? e($errors['confirm_password']) : '' ?>
                    </span>
                </div>

                <button type="submit" class="btn btn-primary btn-full">Register Account</button>
            </form>

            <p class="auth-footer">
                Already have an account? <a href="login.php">Sign in here</a>
            </p>
        </section>
    </main>
    <script src="script.js"></script>
</body>
</html>