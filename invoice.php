<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * Official Tax Invoice View & Print Generator
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/helpers.php';

$invNum = trim($_GET['inv'] ?? '');
$invoice = null;
$booking = null;
$services = [];
$customer = null;

if (!empty($invNum)) {
    try {
        $invoice = Database::fetchOne("SELECT * FROM invoices WHERE invoice_number = ? LIMIT 1", [$invNum]);
        if ($invoice) {
            $booking = Database::fetchOne("
                SELECT b.*, v.make, v.model, v.year, v.reg_number, v.fuel_type, v.color
                FROM bookings b
                JOIN vehicles v ON b.vehicle_id = v.id
                WHERE b.id = ? LIMIT 1
            ", [$invoice['booking_id']]);

            $customer = Database::fetchOne("
                SELECT c.*, u.name, u.email, u.phone
                FROM customers c
                JOIN users u ON c.user_id = u.id
                WHERE c.id = ? LIMIT 1
            ", [$invoice['customer_id']]);

            $services = Database::fetchAll("SELECT * FROM booking_services WHERE booking_id = ?", [$invoice['booking_id']]);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

if (!$invoice || !$booking) {
    die("Invoice not found. Please verify the invoice number.");
}

$pageTitle = 'Tax Invoice ' . $invoice['invoice_number'];
$extraCss = 'assets/css/print.css';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <a href="track-service.php?booking=<?= htmlspecialchars($booking['booking_number']) ?>" class="btn btn-outline-light">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Tracking
        </a>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-red px-4">
                <i class="fa-solid fa-print me-1"></i> Print / Save PDF
            </button>
        </div>
    </div>

    <!-- Printable Invoice Sheet -->
    <div class="card-dark p-4 p-md-5 invoice-card border-secondary shadow-lg">
        <!-- Invoice Header -->
        <div class="row align-items-center border-bottom border-dark pb-4 mb-4">
            <div class="col-sm-6">
                <h2 class="fw-bold mb-1 brand-font text-white"><i class="fa-solid fa-car-tunnel text-danger me-2"></i>AUTO<span class="text-danger">PRO</span></h2>
                <div class="text-muted small">
                    <?= APP_NAME ?> &bull; GSTIN: 07AAACH7409R1ZZ<br>
                    <?= APP_ADDRESS ?><br>
                    Phone: <?= APP_PHONE ?> | Email: <?= APP_EMAIL ?>
                </div>
            </div>
            <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                <span class="badge bg-<?= $invoice['status'] === 'paid' ? 'success' : 'danger' ?> fs-6 px-3 py-1 text-uppercase mb-2">
                    <?= $invoice['status'] === 'paid' ? 'PAID' : 'PAYMENT DUE' ?>
                </span>
                <h4 class="fw-bold text-white mb-0"><?= htmlspecialchars($invoice['invoice_number']) ?></h4>
                <div class="text-muted small">Date: <?= format_date($invoice['issued_at'], 'd M Y') ?></div>
                <div class="text-muted small">Due Date: <?= format_date($invoice['due_date'], 'd M Y') ?></div>
            </div>
        </div>

        <!-- Billed To & Vehicle Info -->
        <div class="row g-4 mb-4">
            <div class="col-sm-6">
                <h6 class="text-danger fw-bold text-uppercase small mb-2">Billed To (Customer):</h6>
                <strong class="text-white fs-5"><?= htmlspecialchars($customer['name'] ?? 'Valued Customer') ?></strong>
                <div class="text-muted small mt-1">
                    Phone: <?= htmlspecialchars($customer['phone'] ?? '') ?><br>
                    Email: <?= htmlspecialchars($customer['email'] ?? '') ?><br>
                    Address: <?= htmlspecialchars($customer['address'] ?? $booking['pickup_address']) ?>, <?= htmlspecialchars($customer['city'] ?? $booking['pickup_city']) ?>
                </div>
            </div>

            <div class="col-sm-6 text-sm-end">
                <h6 class="text-danger fw-bold text-uppercase small mb-2">Vehicle Serviced:</h6>
                <strong class="text-white fs-5"><?= htmlspecialchars($booking['make'] . ' ' . $booking['model'] . ' (' . $booking['year'] . ')') ?></strong>
                <div class="text-muted small mt-1">
                    Registration No: <span class="badge bg-danger"><?= htmlspecialchars($booking['reg_number']) ?></span><br>
                    Fuel Type: <?= htmlspecialchars($booking['fuel_type']) ?><br>
                    Booking Reference: <?= htmlspecialchars($booking['booking_number']) ?>
                </div>
            </div>
        </div>

        <!-- Itemized Table -->
        <div class="table-responsive mb-4">
            <table class="table table-dark-custom">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 55%;">Service / Description</th>
                        <th style="width: 20%;" class="text-center">SAC / HSN Code</th>
                        <th style="width: 20%;" class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $idx = 1; foreach ($services as $srv): ?>
                        <tr>
                            <td><?= $idx++ ?></td>
                            <td>
                                <strong class="text-white"><?= htmlspecialchars($srv['service_name']) ?></strong>
                                <div class="text-muted small">Genuine spares & precision multi-point maintenance labor included</div>
                            </td>
                            <td class="text-center text-muted small">998729</td>
                            <td class="text-end text-white fw-bold"><?= format_currency($srv['price']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Calculations & Totals -->
        <div class="row g-4 align-items-end">
            <div class="col-sm-7">
                <div class="p-3 rounded bg-surface border border-secondary small text-muted">
                    <strong>Payment Terms:</strong> All servicing includes 6 months or 10,000 KM warranty. Payments accepted via Razorpay, UPI, Credit/Debit cards, or Cash upon delivery.
                </div>
            </div>

            <div class="col-sm-5">
                <div class="p-3 rounded bg-surface border border-secondary">
                    <div class="d-flex justify-content-between small text-muted mb-2">
                        <span>Taxable Amount (Subtotal):</span>
                        <strong class="text-white"><?= format_currency($invoice['subtotal']) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mb-2">
                        <span>CGST (9.0%):</span>
                        <strong class="text-white"><?= format_currency($invoice['tax_amount'] / 2) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mb-2">
                        <span>SGST (9.0%):</span>
                        <strong class="text-white"><?= format_currency($invoice['tax_amount'] / 2) ?></strong>
                    </div>
                    <?php if ($invoice['discount'] > 0): ?>
                        <div class="d-flex justify-content-between small text-success mb-2">
                            <span>Promotional Discount:</span>
                            <strong>- <?= format_currency($invoice['discount']) ?></strong>
                        </div>
                    <?php endif; ?>
                    <hr class="border-secondary">
                    <div class="d-flex justify-content-between fs-4 fw-bold text-white">
                        <span>Grand Total:</span>
                        <span class="text-danger"><?= format_currency($invoice['final_amount']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Authorized Signature & Seal -->
        <div class="row mt-5 pt-4 border-top border-dark text-muted small align-items-center">
            <div class="col-sm-6">
                <i class="fa-solid fa-stamp text-danger me-1"></i> Computer Generated Digital Tax Invoice
            </div>
            <div class="col-sm-6 text-sm-end">
                <div class="fw-bold text-white">For AutoPro Car Servicing Pvt Ltd</div>
                <div class="text-muted" style="margin-top: 30px;">Authorized Signatory</div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
