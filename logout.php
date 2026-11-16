<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * User Logout Script
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

// Start a fresh session for the flash message
session_start();
set_flash('info', 'You have been securely signed out.');

header('Location: ' . BASE_URL . '/login.php');
exit;
