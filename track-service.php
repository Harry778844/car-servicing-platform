<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * Live 12-Stage Service Tracking Module
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/helpers.php';

$pageTitle = 'Live Vehicle Service Tracking';
$query = trim($_GET['booking'] ?? '');
$booking = null;
$statusHistory = [];
$inspection = null;
$photos = [];
$estimate = null;
$invoice = null;
$servicesList = [];
$pickupInfo = null;

if (!empty($query)) {
    try {
        // Find booking by booking_number or vehicle reg_number
        $booking = Database::fetchOne("
            SELECT b.*, 
                   v.make, v.model, v.year, v.reg_number, v.fuel_type, v.color,
                   u.name as customer_name, u.phone as customer_phone,
                   p_user.name as pickup_name, p_user.phone as pickup_phone,
                   t_user.name as tech_name,
                   emp_p.employee_code as pickup_code, emp_t.employee_code as tech_code
            FROM bookings b
            JOIN customers c ON b.customer_id = c.id
            JOIN users u ON c.user_id = u.id
            JOIN vehicles v ON b.vehicle_id = v.id
            LEFT JOIN employees emp_p ON b.assigned_pickup_id = emp_p.id
            LEFT JOIN users p_user ON emp_p.user_id = p_user.id
            LEFT JOIN employees emp_t ON b.assigned_technician_id = emp_t.id
            LEFT JOIN users t_user ON emp_t.user_id = t_user.id
            WHERE b.booking_number = ? OR v.reg_number = ?
            ORDER BY b.id DESC LIMIT 1
        ", [$query, $query]);

        if ($booking) {
            $bId = $booking['id'];
            // Fetch status history
            $statusHistory = Database::fetchAll("
                SELECT sh.*, u.name as updated_by_name 
                FROM status_history sh
                LEFT JOIN users u ON sh.updated_by_user_id = u.id
                WHERE sh.booking_id = ?
                ORDER BY sh.id ASC
            ", [$bId]);

            // Fetch booked services
            $servicesList = Database::fetchAll("SELECT * FROM booking_services WHERE booking_id = ?", [$bId]);

            // Fetch inspection & photos
            $inspection = Database::fetchOne("SELECT * FROM vehicle_inspections WHERE booking_id = ? LIMIT 1", [$bId]);
            if ($inspection) {
                $photos = Database::fetchAll("SELECT * FROM inspection_photos WHERE inspection_id = ?", [$inspection['id']]);
            }

            // Fetch estimate
            $estimate = Database::fetchOne("SELECT * FROM service_estimates WHERE booking_id = ? LIMIT 1", [$bId]);

            // Fetch invoice
            $invoice = Database::fetchOne("SELECT * FROM invoices WHERE booking_id = ? LIMIT 1", [$bId]);

            // Fetch pickup request
            $pickupInfo = Database::fetchOne("SELECT * FROM pickup_requests WHERE booking_id = ? LIMIT 1", [$bId]);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle customer estimate approval action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'respond_estimate') {
    $approvalStatus = $_POST['approval_status'] === 'approved' ? 'approved' : 'rejected';
    $remarks = trim($_POST['customer_remarks'] ?? '');
    $estimateId = (int)$_POST['estimate_id'];
    $bookingId = (int)$_POST['booking_id'];

    Database::execute(
        "UPDATE service_estimates SET customer_approval_status = ?, customer_remarks = ?, approved_at = NOW() WHERE id = ?",
        [$approvalStatus, $remarks, $estimateId]
    );

    if ($approvalStatus === 'approved') {
        log_status_history($bookingId, 'Service in Progress', 'Customer approved service estimate. Mechanical work commenced.', $_SESSION['user_id'] ?? null);
        set_flash('success', 'Estimate approved! Technicians have commenced servicing your vehicle.');
    } else {
        log_status_history($bookingId, 'Inspection in Progress', 'Customer requested revisions to the estimate: ' . $remarks, $_SESSION['user_id'] ?? null);
        set_flash('warning', 'Estimate marked as rejected. The workshop service advisor will contact you.');
    }
    header('Location: ' . BASE_URL . '/track-service.php?booking=' . ($booking['booking_number'] ?? $query));
    exit;
}

$extraJs = 'assets/js/tracker.js';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Search Section -->
<section class="py-5 bg-black border-bottom border-dark">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <span class="hero-tag mb-2"><i class="fa-solid fa-radar me-1"></i> Live Workshop Telematics</span>
                <h1 class="fw-bold fs-1 text-white mb-3">VEHICLE SERVICE <span class="text-danger">TRACKER</span></h1>
                <p class="text-muted mb-4">Track each milestone of your vehicle servicing in real-time from pickup to doorstep delivery.</p>

                <form action="track-service.php" method="GET" id="tracker-search-form" class="card-dark p-3 border-secondary">
                    <div class="row g-2">
                        <div class="col-md-9">
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-danger"><i class="fa-solid fa-crosshairs"></i></span>
                                <input type="text" id="tracking-query" name="booking" class="form-control form-control-dark" placeholder="Enter Booking ID (e.g. BK-2026-001) or Car Reg No." value="<?= htmlspecialchars($query) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-red w-100 py-2">
                                <i class="fa-solid fa-magnifying-glass me-1"></i> Track Now
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Quick evaluation demo buttons -->
                <div class="d-flex justify-content-center gap-2 mt-3 small text-muted flex-wrap">
                    <span>Try Demo Records:</span>
                    <a href="track-service.php?booking=BK-2026-001" class="badge bg-dark border border-secondary text-light text-decoration-none">BK-2026-001 (In Progress)</a>
                    <a href="track-service.php?booking=BK-2026-002" class="badge bg-dark border border-secondary text-light text-decoration-none">BK-2026-002 (Delivered)</a>
                    <a href="track-service.php?booking=HR 26 DQ 8899" class="badge bg-dark border border-secondary text-light text-decoration-none">HR 26 DQ 8899 (By Reg)</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($booking): ?>
    <?php 
        $currentStatus = $booking['current_status'];
        $progress = get_status_progress($currentStatus);
        $statusKey = array_search($currentStatus, BOOKING_STATUSES);
        if ($statusKey === false) $statusKey = 1;
    ?>
    <section class="py-5" data-is-live-booking="<?= ($currentStatus !== 'Delivered') ? '1' : '0' ?>">
        <div class="container">
            
            <!-- Top Status Overview Card -->
            <div class="card-dark p-4 p-md-5 mb-5 border-danger border-opacity-40">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                            <h2 class="fw-bold text-white mb-0"><?= htmlspecialchars($booking['make'] . ' ' . $booking['model']) ?></h2>
                            <span class="badge bg-danger fs-6 px-3 py-1"><?= htmlspecialchars($booking['reg_number']) ?></span>
                            <?= get_status_badge($currentStatus) ?>
                        </div>
                        <div class="text-muted small">
                            <span class="me-3"><i class="fa-solid fa-receipt me-1 text-danger"></i>Booking Ref: <strong><?= htmlspecialchars($booking['booking_number']) ?></strong></span>
                            <span class="me-3"><i class="fa-solid fa-calendar me-1 text-danger"></i>Date: <?= format_date($booking['booking_date'], 'd M Y') ?></span>
                            <span><i class="fa-solid fa-user me-1 text-danger"></i>Owner: <?= htmlspecialchars($booking['customer_name']) ?></span>
                        </div>
                    </div>

                    <div class="col-lg-4 text-lg-end">
                        <div class="text-muted small mb-1">Service Progress</div>
                        <div class="d-flex align-items-center justify-content-lg-end gap-2">
                            <div class="progress flex-grow-1" style="height: 12px; background-color: #1e2230; max-width: 220px;">
                                <div class="progress-bar bg-danger progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?= $progress ?>%;"></div>
                            </div>
                            <span class="fw-bold text-white fs-5"><?= $progress ?>%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Estimate Customer Approval Action Callout -->
            <?php if ($currentStatus === 'Waiting for Customer Approval' && $estimate && $estimate['customer_approval_status'] === 'pending'): ?>
                <div class="p-4 rounded card-dark border-warning mb-5 bg-warning bg-opacity-10">
                    <div class="row align-items-center g-3">
                        <div class="col-md-8">
                            <h4 class="fw-bold text-warning mb-1"><i class="fa-solid fa-hourglass-half me-2"></i>Service Estimate Approval Required</h4>
                            <p class="text-light small mb-0">Our workshop inspection found items requiring replacement. Review the detailed estimate and approve to proceed with repairs.</p>
                            <div class="mt-2 text-white fw-bold">Estimate Total: <?= format_currency($estimate['grand_total']) ?></div>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <button type="button" class="btn btn-warning fw-bold px-4" data-bs-toggle="modal" data-bs-target="#estimateModal">
                                Review & Approve Estimate
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row g-5">
                <!-- Left Column: The 12-Stage Visual Stepper Timeline -->
                <div class="col-lg-7">
                    <div class="card-dark p-4 p-md-5">
                        <h4 class="fw-bold text-white mb-4"><i class="fa-solid fa-timeline text-danger me-2"></i>12-Stage Workflow Progress</h4>

                        <div class="stepper-container">
                            <?php foreach (BOOKING_STATUSES as $stepNumber => $statusLabel): ?>
                                <?php 
                                    $isCompleted = ($stepNumber < $statusKey) || ($currentStatus === 'Delivered');
                                    $isActive = ($stepNumber === $statusKey) && ($currentStatus !== 'Delivered');
                                    $stepClass = $isCompleted ? 'completed' : ($isActive ? 'active' : '');

                                    // Find remarks from status history if exists
                                    $histMatch = null;
                                    foreach ($statusHistory as $sh) {
                                        if ($sh['status'] === $statusLabel) {
                                            $histMatch = $sh;
                                        }
                                    }
                                ?>
                                <div class="stepper-step <?= $stepClass ?>">
                                    <div class="stepper-node">
                                        <?php if ($isCompleted): ?>
                                            <i class="fa-solid fa-check"></i>
                                        <?php elseif ($isActive): ?>
                                            <i class="fa-solid fa-gear fa-spin"></i>
                                        <?php else: ?>
                                            <?= $stepNumber ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-baseline">
                                            <h6 class="fw-bold mb-1 <?= $isActive ? 'text-danger' : ($isCompleted ? 'text-white' : 'text-muted') ?>">
                                                <?= $stepNumber ?>. <?= htmlspecialchars($statusLabel) ?>
                                            </h6>
                                            <?php if ($histMatch): ?>
                                                <small class="text-muted" style="font-size:0.75rem;">
                                                    <?= format_date($histMatch['created_at'], 'd M, h:i A') ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($histMatch && !empty($histMatch['remarks'])): ?>
                                            <p class="text-muted small mb-0"><?= htmlspecialchars($histMatch['remarks']) ?></p>
                                        <?php elseif ($isActive): ?>
                                            <p class="text-danger small mb-0"><i class="fa-solid fa-circle-dot me-1"></i> Currently active step at AutoPro Hub</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Personnel, Service List, and Inspection Photos -->
                <div class="col-lg-5">
                    
                    <!-- Assigned Team -->
                    <div class="card-dark p-4 mb-4">
                        <h5 class="fw-bold text-white mb-3 border-bottom border-dark pb-2"><i class="fa-solid fa-user-shield text-danger me-2"></i>Assigned AutoPro Crew</h5>
                        
                        <!-- Pickup Executive -->
                        <div class="d-flex align-items-center gap-3 p-3 rounded bg-surface border border-secondary mb-3">
                            <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center" style="width:45px;height:45px;font-size:1.2rem;">
                                <i class="fa-solid fa-truck-pickup"></i>
                            </div>
                            <div class="flex-grow-1">
                                <small class="text-muted text-uppercase d-block" style="font-size:0.75rem;">Pickup & Delivery Specialist</small>
                                <strong class="text-white"><?= htmlspecialchars($booking['pickup_name'] ?? 'Driver Being Assigned') ?></strong>
                                <?php if (!empty($booking['pickup_phone'])): ?>
                                    <div class="small text-danger mt-1"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($booking['pickup_phone']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Technician -->
                        <div class="d-flex align-items-center gap-3 p-3 rounded bg-surface border border-secondary">
                            <div class="rounded-circle bg-info text-dark d-flex align-items-center justify-content-center" style="width:45px;height:45px;font-size:1.2rem;">
                                <i class="fa-solid fa-screwdriver-wrench"></i>
                            </div>
                            <div class="flex-grow-1">
                                <small class="text-muted text-uppercase d-block" style="font-size:0.75rem;">Master Workshop Mechanic</small>
                                <strong class="text-white"><?= htmlspecialchars($booking['tech_name'] ?? 'Assigned on Workshop Bay') ?></strong>
                                <div class="small text-muted mt-1"><i class="fa-solid fa-certificate text-warning me-1"></i>ASE Certified Master</div>
                            </div>
                        </div>
                    </div>

                    <!-- Booked Services & Pricing Card -->
                    <div class="card-dark p-4 mb-4">
                        <h5 class="fw-bold text-white mb-3 border-bottom border-dark pb-2"><i class="fa-solid fa-list-check text-danger me-2"></i>Services Selected</h5>
                        <ul class="list-unstyled mb-3">
                            <?php foreach ($servicesList as $bs): ?>
                                <li class="d-flex justify-content-between py-2 border-bottom border-dark small">
                                    <span class="text-white"><i class="fa-solid fa-circle-check text-success me-2"></i><?= htmlspecialchars($bs['service_name']) ?></span>
                                    <strong class="text-muted"><?= format_currency($bs['price']) ?></strong>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="d-flex justify-content-between text-white fw-bold fs-5 pt-2">
                            <span>Total Estimated:</span>
                            <span class="text-danger"><?= format_currency($booking['total_amount']) ?></span>
                        </div>
                        <div class="text-muted small mt-1">Payment Status: <span class="badge bg-<?= $booking['payment_status'] === 'Paid' ? 'success' : 'warning' ?>"><?= $booking['payment_status'] ?></span></div>

                        <?php if ($invoice): ?>
                            <div class="d-grid mt-3 gap-2">
                                <a href="invoice.php?inv=<?= $invoice['invoice_number'] ?>" class="btn btn-outline-light btn-sm" target="_blank">
                                    <i class="fa-solid fa-file-invoice me-1"></i> View Tax Invoice
                                </a>
                                <?php if ($booking['payment_status'] !== 'Paid'): ?>
                                    <a href="payment.php?booking=<?= $booking['booking_number'] ?>" class="btn btn-red btn-sm">
                                        <i class="fa-solid fa-credit-card me-1"></i> Pay Online via Razorpay
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Vehicle Inspection Checklist Summary -->
                    <?php if ($inspection): ?>
                        <div class="card-dark p-4 mb-4">
                            <h5 class="fw-bold text-white mb-3 border-bottom border-dark pb-2"><i class="fa-solid fa-clipboard-check text-danger me-2"></i>Workshop Bay Inspection</h5>
                            <div class="row g-2 small mb-3">
                                <div class="col-6 text-muted">Fuel Level: <strong class="text-white"><?= htmlspecialchars($inspection['fuel_level'] ?? 'N/A') ?></strong></div>
                                <div class="col-6 text-muted">Odometer: <strong class="text-white"><?= number_format($inspection['odometer_reading'] ?? 0) ?> KM</strong></div>
                                <div class="col-6 text-muted">Exterior: <strong class="text-white"><?= htmlspecialchars($inspection['exterior_condition'] ?? 'Good') ?></strong></div>
                                <div class="col-6 text-muted">Tyres: <strong class="text-white"><?= htmlspecialchars($inspection['tyre_condition'] ?? 'Good') ?></strong></div>
                                <div class="col-6 text-muted">Engine Oil: <strong class="text-white"><?= htmlspecialchars($inspection['engine_oil_status'] ?? 'Good') ?></strong></div>
                                <div class="col-6 text-muted">Battery: <strong class="text-white"><?= htmlspecialchars($inspection['battery_voltage'] ?? '12.6V') ?></strong></div>
                            </div>
                            <?php if (!empty($inspection['general_remarks'])): ?>
                                <p class="text-muted small mb-0 bg-surface p-2 rounded border border-dark">
                                    <strong>Mechanic Notes:</strong> <?= htmlspecialchars($inspection['general_remarks']) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

        </div>
    </section>

    <!-- Estimate Approval Modal -->
    <?php if ($estimate): ?>
        <div class="modal fade" id="estimateModal" tabindex="-1" aria-labelledby="estimateModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content card-dark border-secondary">
                    <div class="modal-header border-dark">
                        <h5 class="modal-title text-white fw-bold" id="estimateModalLabel"><i class="fa-solid fa-receipt text-danger me-2"></i>Estimate <?= htmlspecialchars($estimate['estimate_number']) ?></h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="track-service.php?booking=<?= htmlspecialchars($booking['booking_number']) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="respond_estimate">
                        <input type="hidden" name="estimate_id" value="<?= $estimate['id'] ?>">
                        <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">

                        <div class="modal-body text-light">
                            <div class="p-3 rounded bg-surface border border-secondary mb-3">
                                <div class="d-flex justify-content-between mb-2"><span>Parts Replacement Total:</span> <strong><?= format_currency($estimate['parts_total']) ?></strong></div>
                                <div class="d-flex justify-content-between mb-2"><span>Labor & Diagnostics:</span> <strong><?= format_currency($estimate['labor_total']) ?></strong></div>
                                <div class="d-flex justify-content-between mb-2"><span>GST (18%):</span> <strong><?= format_currency($estimate['tax_amount']) ?></strong></div>
                                <?php if ($estimate['discount_amount'] > 0): ?>
                                    <div class="d-flex justify-content-between mb-2 text-success"><span>Special Privilege Discount:</span> <strong>- <?= format_currency($estimate['discount_amount']) ?></strong></div>
                                <?php endif; ?>
                                <hr class="border-secondary">
                                <div class="d-flex justify-content-between fs-5 fw-bold text-danger"><span>Grand Total:</span> <strong><?= format_currency($estimate['grand_total']) ?></strong></div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Remarks / Instructions for Technician:</label>
                                <textarea name="customer_remarks" class="form-control form-control-dark" rows="2" placeholder="e.g. Approved, please install OEM brake pads."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer border-dark d-flex justify-content-between">
                            <button type="submit" name="approval_status" value="rejected" class="btn btn-outline-danger">Reject / Request Callback</button>
                            <button type="submit" name="approval_status" value="approved" class="btn btn-success"><i class="fa-solid fa-circle-check me-1"></i>Approve & Begin Repairs</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

<?php elseif (!empty($query)): ?>
    <section class="py-5 text-center">
        <div class="container py-5">
            <i class="fa-solid fa-car-tunnel text-muted fa-4x mb-3"></i>
            <h3 class="fw-bold text-white mb-2">No Booking Found</h3>
            <p class="text-muted mx-auto mb-4" style="max-width: 500px;">
                We could not locate any active servicing request matching "<strong><?= htmlspecialchars($query) ?></strong>". Please verify your booking reference or registration number.
            </p>
            <a href="track-service.php" class="btn btn-outline-light">Try Another Search</a>
        </div>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
