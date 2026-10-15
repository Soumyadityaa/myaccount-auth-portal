<?php
declare(strict_types=1);

/**
 * index.php
 * Routes visitors to the personalized dashboard if authenticated,
 * or to the login page if unauthenticated.
 */

require_once __DIR__ . '/db.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

header('Location: login.php');
exit;