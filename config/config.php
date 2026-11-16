<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * Global Configuration File
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default timezone
date_default_timezone_set('Asia/Kolkata');

// Application Metadata
define('APP_NAME', 'AutoPro Car Care');
define('APP_TAGLINE', 'Premium Doorstep Car Servicing & Workshop Network');
define('APP_VERSION', '2.5.0');
define('APP_EMAIL', 'support@autopro.com');
define('APP_PHONE', '+91 (800) 456-7890');
define('APP_ADDRESS', 'AutoPro Towers, Tech Corridor, Gurugram, HR 122003');
define('APP_CURRENCY', '₹');
define('APP_CURRENCY_CODE', 'INR');

// Determine base URL dynamically
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$script_name = isset($_SERVER['SCRIPT_NAME']) ? dirname($_SERVER['SCRIPT_NAME']) : '';
$base_url = rtrim($protocol . $host . str_replace('\\', '/', $script_name), '/');
// Clean up if nested in subdirectories
if (strpos($base_url, '/admin') !== false) {
    $base_url = substr($base_url, 0, strpos($base_url, '/admin'));
} elseif (strpos($base_url, '/customer') !== false) {
    $base_url = substr($base_url, 0, strpos($base_url, '/customer'));
} elseif (strpos($base_url, '/pickup') !== false) {
    $base_url = substr($base_url, 0, strpos($base_url, '/pickup'));
} elseif (strpos($base_url, '/technician') !== false) {
    $base_url = substr($base_url, 0, strpos($base_url, '/technician'));
} elseif (strpos($base_url, '/api') !== false) {
    $base_url = substr($base_url, 0, strpos($base_url, '/api'));
}
define('BASE_URL', $base_url);

// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'carservice_db');
define('DB_CHARSET', 'utf8mb4');

// Razorpay Test Gateway Keys (Standard Test Credentials)
define('RAZORPAY_KEY_ID', 'rzp_test_AutoPro1234');
define('RAZORPAY_KEY_SECRET', 'secret_key_autopro_secure_99');

// Google Maps API
define('GOOGLE_MAPS_KEY', 'AIzaSyDemoKeyAutoProMapsLocation2026');

// SMTP Email Settings
define('SMTP_HOST', 'smtp.mailtrap.io');
define('SMTP_PORT', 587);
define('SMTP_USER', 'demo_autopro_smtp');
define('SMTP_PASS', 'demo_password');
define('SMTP_FROM_EMAIL', 'notifications@autopro.com');
define('SMTP_FROM_NAME', 'AutoPro Service Center');

// The 12 Canonical Booking Statuses
define('BOOKING_STATUSES', [
    1 => 'Booking Pending',
    2 => 'Booking Confirmed',
    3 => 'Pickup Assigned',
    4 => 'Vehicle Picked Up',
    5 => 'Vehicle Received at Workshop',
    6 => 'Inspection in Progress',
    7 => 'Waiting for Customer Approval',
    8 => 'Service in Progress',
    9 => 'Service Completed',
    10 => 'Ready for Delivery',
    11 => 'Out for Delivery',
    12 => 'Delivered'
]);

// Status color badges and icons
define('STATUS_METADATA', [
    'Booking Pending' => ['color' => 'warning', 'icon' => 'fa-clock', 'progress' => 8],
    'Booking Confirmed' => ['color' => 'info', 'icon' => 'fa-calendar-check', 'progress' => 16],
    'Pickup Assigned' => ['color' => 'primary', 'icon' => 'fa-user-check', 'progress' => 25],
    'Vehicle Picked Up' => ['color' => 'primary', 'icon' => 'fa-car-side', 'progress' => 33],
    'Vehicle Received at Workshop' => ['color' => 'info', 'icon' => 'fa-warehouse', 'progress' => 42],
    'Inspection in Progress' => ['color' => 'warning', 'icon' => 'fa-clipboard-check', 'progress' => 50],
    'Waiting for Customer Approval' => ['color' => 'warning', 'icon' => 'fa-hourglass-half', 'progress' => 58],
    'Service in Progress' => ['color' => 'danger', 'icon' => 'fa-tools', 'progress' => 67],
    'Service Completed' => ['color' => 'success', 'icon' => 'fa-check-circle', 'progress' => 75],
    'Ready for Delivery' => ['color' => 'info', 'icon' => 'fa-shield-check', 'progress' => 83],
    'Out for Delivery' => ['color' => 'primary', 'icon' => 'fa-truck-moving', 'progress' => 92],
    'Delivered' => ['color' => 'success', 'icon' => 'fa-flag-checkered', 'progress' => 100]
]);

// User Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_CUSTOMER', 'customer');
define('ROLE_PICKUP', 'pickup');
define('ROLE_TECHNICIAN', 'technician');
