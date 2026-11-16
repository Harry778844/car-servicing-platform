<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * AJAX API: Razorpay Webhook & Payment Verification
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$rawPayload = file_get_contents('php://input');
$data = json_decode($rawPayload, true) ?: $_POST;

$action = $data['action'] ?? ($_GET['action'] ?? '');

if ($action === 'verify_razorpay') {
    $razorpayPaymentId = $data['razorpay_payment_id'] ?? ('pay_' . uniqid());
    $bookingId = (int)($data['booking_id'] ?? 0);
    $invoiceId = (int)($data['invoice_id'] ?? 0);
    $amount = (float)($data['amount'] ?? 0);

    if ($bookingId <= 0 || $invoiceId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Missing booking or invoice ID']);
        exit;
    }

    try {
        Database::execute("
            INSERT INTO payments (invoice_id, booking_id, transaction_id, payment_gateway, amount, currency, status, payment_response, payment_date)
            VALUES (?, ?, ?, 'razorpay', ?, 'INR', 'successful', ?, NOW())
        ", [$invoiceId, $bookingId, $razorpayPaymentId, $amount, json_encode($data)]);

        Database::execute("UPDATE invoices SET status = 'paid' WHERE id = ?", [$invoiceId]);
        Database::execute("UPDATE bookings SET payment_status = 'Paid' WHERE id = ?", [$bookingId]);

        echo json_encode([
            'success' => true,
            'transaction_id' => $razorpayPaymentId,
            'message' => 'Payment verified and recorded.'
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown endpoint']);
