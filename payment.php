<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * Payment Gateway & Checkout Page (Razorpay / Cash)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth_middleware.php';

$bookingNum = trim($_GET['booking'] ?? '');
$invoiceNum = trim($_GET['invoice'] ?? '');
$booking = null;
$invoice = null;

if (!empty($bookingNum) || !empty($invoiceNum)) {
    try {
        if (!empty($bookingNum)) {
            $booking = Database::fetchOne("
                SELECT b.*, v.make, v.model, v.reg_number, u.name as customer_name, u.email as customer_email, u.phone as customer_phone
                FROM bookings b
                JOIN customers c ON b.customer_id = c.id
                JOIN users u ON c.user_id = u.id
                JOIN vehicles v ON b.vehicle_id = v.id
                WHERE b.booking_number = ? LIMIT 1
            ", [$bookingNum]);
            
            if ($booking) {
                $invoice = Database::fetchOne("SELECT * FROM invoices WHERE booking_id = ? LIMIT 1", [$booking['id']]);
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Auto-generate invoice if missing for this booking
if ($booking && !$invoice) {
    try {
        $invNum = generate_invoice_number();
        $subtotal = $booking['total_amount'] / 1.18;
        $tax = $booking['total_amount'] - $subtotal;
        Database::execute("
            INSERT INTO invoices (invoice_number, booking_id, customer_id, subtotal, tax_rate, tax_amount, discount, final_amount, status, due_date)
            VALUES (?, ?, ?, ?, 18.00, ?, 0.00, ?, 'unpaid', DATE_ADD(CURDATE(), INTERVAL 2 DAY))
        ", [$invNum, $booking['id'], $booking['customer_id'], $subtotal, $tax, $booking['total_amount']]);
        
        $invoice = Database::fetchOne("SELECT * FROM invoices WHERE invoice_number = ? LIMIT 1", [$invNum]);
    } catch (Exception $e) {
        // Fallback
    }
}

// Handle Payment Submission (Razorpay or Cash)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_payment') {
    $method = $_POST['payment_method'] ?? 'razorpay';
    $bookingId = (int)$_POST['booking_id'];
    $invoiceId = (int)$_POST['invoice_id'];
    $amount = (float)$_POST['amount'];
    $txnId = ($method === 'cash') ? 'TXN-CASH-' . strtoupper(substr(uniqid(), -6)) : 'pay_RZP_' . strtoupper(substr(uniqid(), -9));

    try {
        // Insert payment record
        Database::execute("
            INSERT INTO payments (invoice_id, booking_id, transaction_id, payment_gateway, amount, currency, status, payment_response, payment_date)
            VALUES (?, ?, ?, ?, ?, 'INR', 'successful', ?, NOW())
        ", [$invoiceId, $bookingId, $txnId, $method, $amount, json_encode(['method' => $method, 'txn' => $txnId, 'time' => date('c')])]);

        // Update invoice status
        Database::execute("UPDATE invoices SET status = 'paid' WHERE id = ?", [$invoiceId]);

        // Update booking payment status
        Database::execute("UPDATE bookings SET payment_status = 'Paid' WHERE id = ?", [$bookingId]);

        // Send confirmation notification
        send_notification(
            $_SESSION['user_id'] ?? 2,
            'Payment Received (' . format_currency($amount) . ')',
            "Payment for booking {$booking['booking_number']} received successfully via " . strtoupper($method) . ". Txn ID: {$txnId}.",
            'success',
            'invoice.php?inv=' . ($invoice['invoice_number'] ?? '')
        );

        set_flash('success', "Payment of " . format_currency($amount) . " confirmed successfully! Txn ID: {$txnId}");
        header('Location: ' . BASE_URL . '/invoice.php?inv=' . $invoice['invoice_number']);
        exit;
    } catch (Exception $e) {
        $error = 'Payment processing failed: ' . $e->getMessage();
    }
}

$pageTitle = 'Secure Checkout & Payment Gateway';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="text-center mb-5">
                <span class="hero-tag mb-2"><i class="fa-solid fa-lock me-1"></i> 256-Bit SSL Encrypted Checkout</span>
                <h1 class="fw-bold fs-1 text-white mb-2">SERVICE <span class="text-danger">PAYMENT</span></h1>
                <p class="text-muted">Pay securely online via Razorpay (UPI, Credit/Debit Cards, Netbanking) or Cash upon Delivery.</p>
            </div>

            <?php if ($booking && $invoice): ?>
                <?php $isAlreadyPaid = ($booking['payment_status'] === 'Paid' || $invoice['status'] === 'paid'); ?>

                <?php if ($isAlreadyPaid): ?>
                    <div class="card-dark p-5 text-center border-success mb-4">
                        <i class="fa-solid fa-circle-check text-success fa-4x mb-3"></i>
                        <h2 class="fw-bold text-white mb-2">Invoice Already Paid</h2>
                        <p class="text-muted mb-4">This booking (<?= htmlspecialchars($booking['booking_number']) ?>) is fully settled. No further payment is required.</p>
                        <div class="d-flex justify-content-center gap-3">
                            <a href="invoice.php?inv=<?= htmlspecialchars($invoice['invoice_number']) ?>" class="btn btn-red px-4" target="_blank">
                                <i class="fa-solid fa-file-invoice me-1"></i> View Tax Invoice
                            </a>
                            <a href="track-service.php?booking=<?= htmlspecialchars($booking['booking_number']) ?>" class="btn btn-outline-light px-4">
                                <i class="fa-solid fa-radar me-1"></i> Back to Tracking
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <!-- Invoice Breakdown -->
                        <div class="col-md-6">
                            <div class="card-dark p-4 h-100">
                                <h5 class="fw-bold text-white mb-3 border-bottom border-dark pb-2">
                                    <i class="fa-solid fa-receipt text-danger me-2"></i>Invoice Details
                                </h5>
                                
                                <div class="mb-3">
                                    <small class="text-muted d-block">Booking Reference</small>
                                    <strong class="text-white"><?= htmlspecialchars($booking['booking_number']) ?></strong>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted d-block">Vehicle</small>
                                    <strong class="text-white"><?= htmlspecialchars($booking['make'] . ' ' . $booking['model']) ?></strong>
                                    <span class="badge bg-danger ms-2"><?= htmlspecialchars($booking['reg_number']) ?></span>
                                </div>

                                <div class="p-3 rounded bg-surface border border-secondary mb-3">
                                    <div class="d-flex justify-content-between small text-muted mb-2">
                                        <span>Subtotal Labor & Parts:</span>
                                        <strong class="text-white"><?= format_currency($invoice['subtotal']) ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between small text-muted mb-2">
                                        <span>GST (18%):</span>
                                        <strong class="text-white"><?= format_currency($invoice['tax_amount']) ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between small text-muted mb-2">
                                        <span>Doorstep Pickup & Delivery:</span>
                                        <span class="badge bg-success bg-opacity-25 text-success">FREE</span>
                                    </div>
                                    <hr class="border-secondary">
                                    <div class="d-flex justify-content-between fs-5 fw-bold text-white">
                                        <span>Payable Amount:</span>
                                        <span class="text-danger"><?= format_currency($invoice['final_amount']) ?></span>
                                    </div>
                                </div>

                                <div class="small text-muted">
                                    <i class="fa-solid fa-shield-halved text-success me-1"></i> Official GST compliant electronic tax invoice generated.
                                </div>
                            </div>
                        </div>

                        <!-- Payment Methods & Razorpay Simulation -->
                        <div class="col-md-6">
                            <div class="card-dark p-4 h-100">
                                <h5 class="fw-bold text-white mb-3 border-bottom border-dark pb-2">
                                    <i class="fa-solid fa-credit-card text-danger me-2"></i>Payment Method
                                </h5>

                                <form method="POST" action="payment.php?booking=<?= htmlspecialchars($booking['booking_number']) ?>" id="paymentForm">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="process_payment">
                                    <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                    <input type="hidden" name="invoice_id" value="<?= $invoice['id'] ?>">
                                    <input type="hidden" name="amount" value="<?= $invoice['final_amount'] ?>">

                                    <div class="mb-3">
                                        <label class="p-3 rounded border border-secondary bg-surface d-block cursor-pointer mb-2">
                                            <div class="d-flex align-items-center gap-3">
                                                <input type="radio" name="payment_method" value="razorpay" class="form-check-input" checked id="pay_rzp">
                                                <div>
                                                    <strong class="text-white d-block"><i class="fa-solid fa-bolt text-warning me-1"></i> Razorpay Quick Checkout</strong>
                                                    <small class="text-muted">UPI (GPay, PhonePe, Paytm), Cards, Netbanking</small>
                                                </div>
                                            </div>
                                        </label>

                                        <label class="p-3 rounded border border-secondary bg-surface d-block cursor-pointer">
                                            <div class="d-flex align-items-center gap-3">
                                                <input type="radio" name="payment_method" value="cash" class="form-check-input" id="pay_cash">
                                                <div>
                                                    <strong class="text-white d-block"><i class="fa-solid fa-money-bill-wave text-success me-1"></i> Cash on Delivery (COD)</strong>
                                                    <small class="text-muted">Pay cash or scan QR when executive drops car</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>

                                    <div class="d-grid mt-4">
                                        <button type="submit" class="btn btn-red btn-lg" id="payNowBtn">
                                            <i class="fa-solid fa-lock me-2"></i>Pay <?= format_currency($invoice['final_amount']) ?>
                                        </button>
                                    </div>
                                </form>

                                <div class="text-center mt-3 text-muted" style="font-size:0.75rem;">
                                    <i class="fa-brands fa-cc-visa fa-2x me-2 text-white"></i>
                                    <i class="fa-brands fa-cc-mastercard fa-2x me-2 text-white"></i>
                                    <i class="fa-solid fa-qrcode fa-2x text-white"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="card-dark p-5 text-center">
                    <i class="fa-solid fa-file-circle-question text-muted fa-4x mb-3"></i>
                    <h3 class="fw-bold text-white mb-2">Find Your Booking to Pay</h3>
                    <p class="text-muted mb-4">Please enter your booking reference to pull up the invoice and checkout.</p>
                    <form action="payment.php" method="GET" class="row justify-content-center g-2">
                        <div class="col-md-6">
                            <input type="text" name="booking" class="form-control form-control-dark" placeholder="Enter Booking ID (e.g. BK-2026-001)" required>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-red w-100">Find Invoice</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
