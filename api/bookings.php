<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * AJAX API: Booking Status & Telematics Feed
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($action === 'get_status') {
    $bookingNumber = trim($_GET['booking_number'] ?? '');
    if (empty($bookingNumber)) {
        echo json_encode(['success' => false, 'error' => 'Missing booking number']);
        exit;
    }

    try {
        $booking = Database::fetchOne("
            SELECT b.booking_number, b.current_status, b.total_amount, b.payment_status,
                   v.make, v.model, v.reg_number
            FROM bookings b
            JOIN vehicles v ON b.vehicle_id = v.id
            WHERE b.booking_number = ? LIMIT 1
        ", [$bookingNumber]);

        if ($booking) {
            $progress = get_status_progress($booking['current_status']);
            echo json_encode([
                'success' => true,
                'booking' => $booking,
                'progress' => $progress,
                'badge' => get_status_badge($booking['current_status'])
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Booking not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
