<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * Authentication & Role-Based Access Control Middleware
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helpers.php';

/**
 * Enforce that the user must be authenticated
 */
function require_login(string $redirectUrl = 'login.php'): void {
    if (!is_logged_in()) {
        set_flash('warning', 'Please login to access this area.');
        header('Location: ' . BASE_URL . '/' . ltrim($redirectUrl, '/'));
        exit;
    }
}

/**
 * Enforce specific role(s)
 * @param string|array $roles Single role or array of allowed roles
 */
function require_role($roles, string $forbiddenRedirect = 'index.php'): void {
    require_login();
    
    $allowed = is_array($roles) ? $roles : [$roles];
    $userRole = $_SESSION['user_role'] ?? '';

    if (!in_array($userRole, $allowed, true)) {
        set_flash('danger', 'Access denied. You do not have permission to view that page.');
        header('Location: ' . BASE_URL . '/' . ltrim($forbiddenRedirect, '/'));
        exit;
    }
}

/**
 * Redirect user to their appropriate role-based dashboard
 */
function redirect_by_role(?string $role = null): void {
    $r = $role ?: ($_SESSION['user_role'] ?? '');
    switch ($r) {
        case 'admin':
            header('Location: ' . BASE_URL . '/admin/index.php');
            exit;
        case 'pickup':
            header('Location: ' . BASE_URL . '/pickup/index.php');
            exit;
        case 'technician':
            header('Location: ' . BASE_URL . '/technician/index.php');
            exit;
        case 'customer':
        default:
            header('Location: ' . BASE_URL . '/customer/index.php');
            exit;
    }
}
