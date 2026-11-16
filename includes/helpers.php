<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * Helper Utility Functions
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate or get CSRF token
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output hidden CSRF input field
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Validate CSRF token
 */
function verify_csrf(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Format currency with symbol
 */
function format_currency($amount): string {
    return APP_CURRENCY . ' ' . number_format((float)$amount, 2);
}

/**
 * Format datetime to readable format
 */
function format_date($datetime, string $format = 'd M Y, h:i A'): string {
    if (empty($datetime)) return 'N/A';
    try {
        $date = new DateTime($datetime);
        return $date->format($format);
    } catch (Exception $e) {
        return (string)$datetime;
    }
}

/**
 * Human readable relative time
 */
function time_ago($timestamp): string {
    if (empty($timestamp)) return 'never';
    $time = is_numeric($timestamp) ? (int)$timestamp : strtotime($timestamp);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return round($diff / 60) . ' mins ago';
    if ($diff < 86400) return round($diff / 3600) . ' hours ago';
    if ($diff < 604800) return round($diff / 86400) . ' days ago';
    return date('d M Y', $time);
}

/**
 * Render visual status badge HTML
 */
function get_status_badge(string $status): string {
    $meta = STATUS_METADATA[$status] ?? ['color' => 'secondary', 'icon' => 'fa-circle-dot'];
    $color = $meta['color'];
    $icon = $meta['icon'];
    return "<span class=\"badge bg-{$color} bg-opacity-25 text-{$color} border border-{$color} px-2 py-1\"><i class=\"fa-solid {$icon} me-1\"></i> " . htmlspecialchars($status) . "</span>";
}

/**
 * Get numerical progress percentage (0 - 100) for a status
 */
function get_status_progress(string $status): int {
    return STATUS_METADATA[$status]['progress'] ?? 10;
}

/**
 * Set flash session message
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // success, danger, warning, info
        'message' => $message
    ];
}

/**
 * Retrieve and clear flash message
 */
function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Check if a user is currently logged in
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Get current logged in user details
 */
function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'customer',
        'avatar' => $_SESSION['user_avatar'] ?? 'assets/img/default-avatar.png'
    ];
}

/**
 * Insert system notification for user
 */
function send_notification(int $user_id, string $title, string $message, string $type = 'info', ?string $link = null): bool {
    try {
        return Database::execute(
            "INSERT INTO notifications (user_id, title, message, type, link, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())",
            [$user_id, $title, $message, $type, $link]
        );
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Record booking status audit trail in status_history
 */
function log_status_history(int $booking_id, string $status, string $remarks = '', ?int $user_id = null): bool {
    try {
        $uid = $user_id ?: ($_SESSION['user_id'] ?? null);
        Database::execute(
            "INSERT INTO status_history (booking_id, status, remarks, updated_by_user_id, created_at) VALUES (?, ?, ?, ?, NOW())",
            [$booking_id, $status, $remarks, $uid]
        );
        // Also update current_status on bookings table
        Database::execute(
            "UPDATE bookings SET current_status = ?, updated_at = NOW() WHERE id = ?",
            [$status, $booking_id]
        );
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Generate unique IDs
 */
function generate_booking_number(): string {
    return 'BK-' . date('Y') . '-' . strtoupper(substr(uniqid(), -5));
}

function generate_invoice_number(): string {
    return 'INV-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
}

function generate_estimate_number(): string {
    return 'EST-' . date('Y') . '-' . strtoupper(substr(uniqid(), -5));
}
