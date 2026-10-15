<?php
declare(strict_types=1);

/**
 * dashboard.php
 * Protected page that retrieves and displays account details exclusively
 * for the user identified by $_SESSION['user_id'].
 */

require_once __DIR__ . '/db.php';

// 1. Enforce authentication check
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 2. Retrieve current user's data strictly using the session user_id
try {
    $stmt = $pdo->prepare(
        'SELECT id, name, username, email, created_at FROM users WHERE id = :id LIMIT 1'
    );
    $stmt->execute([':id' => (int)$_SESSION['user_id']]);
    $currentUser = $stmt->fetch();

    // If the user record was deleted while the session was active, force logout
    if (!$currentUser) {
        $_SESSION = [];
        session_destroy();
        header('Location: login.php');
        exit;
    }
} catch (PDOException $e) {
    error_log('Dashboard Fetch Error: ' . $e->getMessage());
    http_response_code(500);
    exit('Unable to load account details right now. Please try again later.');
}

// Format registration timestamp nicely
$registeredDate = date('F j, Y \a\t g:i A', strtotime((string)$currentUser['created_at']));
$csrfToken      = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — <?= e($currentUser['name']) ?> | MyAccount Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="dashboard-body">
    <!-- Top Navigation Bar -->
    <header class="navbar">
        <div class="navbar-container">
            <div class="navbar-brand">
                <span class="brand-badge">MyAccount</span>
                <span class="navbar-title">User Portal</span>
            </div>

            <form action="logout.php" method="POST" class="logout-form">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <button type="submit" class="btn btn-outline">Log Out</button>
            </form>
        </div>
    </header>

    <!-- Main Personalized Content -->
    <main class="dashboard-container">
        <section class="welcome-banner" aria-labelledby="welcome-heading">
            <div class="avatar-circle" aria-hidden="true">
                <?= e(mb_strtoupper(mb_substr($currentUser['name'], 0, 1))) ?>
            </div>
            <div>
                <p class="welcome-subtitle">Authenticated Session Active</p>
                <h1 id="welcome-heading">Welcome back, <?= e($currentUser['name']) ?>!</h1>
                <p class="welcome-description">
                    You are viewing your private, session-protected account dashboard.
                </p>
            </div>
        </section>

        <section class="profile-card" aria-labelledby="profile-heading">
            <div class="profile-card-header">
                <h2 id="profile-heading">Account Information</h2>
                <span class="status-pill">Verified Account</span>
            </div>

            <dl class="profile-grid">
                <div class="profile-item">
                    <dt>Unique Account ID</dt>
                    <dd><span class="mono-badge">#<?= e((string)$currentUser['id']) ?></span></dd>
                </div>

                <div class="profile-item">
                    <dt>Full Name</dt>
                    <dd><?= e($currentUser['name']) ?></dd>
                </div>

                <div class="profile-item">
                    <dt>Username</dt>
                    <dd>@<?= e($currentUser['username']) ?></dd>
                </div>

                <div class="profile-item">
                    <dt>Email Address</dt>
                    <dd><?= e($currentUser['email']) ?></dd>
                </div>

                <div class="profile-item profile-item-wide">
                    <dt>Account Registration Date</dt>
                    <dd><?= e($registeredDate) ?></dd>
                </div>
            </dl>
        </section>
    </main>
</body>
</html>