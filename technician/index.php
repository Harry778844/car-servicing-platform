<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * Workshop Service Technician Bay Terminal
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

require_role('technician', 'login.php');

$currentUser = current_user();
$userId = $currentUser['id'];

$employee = Database::fetchOne("SELECT * FROM employees WHERE user_id = ? LIMIT 1", [$userId]);
$employeeId = $employee['id'] ?? 0;

$error = '';
$success = '';

// Handle Technician status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $bookingId = (int)$_POST['booking_id'];
    $targetStatus = $_POST['target_status'] ?? '';
    $remarks = trim($_POST['remarks'] ?? '');

    try {
        if (!empty($targetStatus)) {
            log_status_history($bookingId, $targetStatus, $remarks, $userId);

            // If service completed, auto-create service record
            if ($targetStatus === 'Service Completed') {
                $actionTaken = trim($_POST['action_taken'] ?? 'Routine servicing and scheduled maintenance executed.');
                $parts = trim($_POST['parts_replaced'] ?? 'Oil Filter, Engine Oil');
                $hours = (float)($_POST['hours_spent'] ?? 2.0);

                Database::execute("
                    INSERT INTO service_records (booking_id, technician_id, service_name, action_taken, parts_replaced, hours_spent, completed_at, notes)
                    VALUES (?, ?, 'Servicing & Quality Check', ?, ?, ?, NOW(), ?)
                ", [$bookingId, $employeeId, $actionTaken, $parts, $hours, $remarks]);

                // Also generate tax invoice if not yet existing
                $existingInv = Database::fetchOne("SELECT id FROM invoices WHERE booking_id = ? LIMIT 1", [$bookingId]);
                if (!$existingInv) {
                    $b = Database::fetchOne("SELECT * FROM bookings WHERE id = ?", [$bookingId]);
                    $invNum = generate_invoice_number();
                    $subtotal = $b['total_amount'] / 1.18;
                    $tax = $b['total_amount'] - $subtotal;
                    Database::execute("
                        INSERT INTO invoices (invoice_number, booking_id, customer_id, subtotal, tax_rate, tax_amount, final_amount, status, due_date)
                        VALUES (?, ?, ?, ?, 18.00, ?, ?, 'unpaid', DATE_ADD(CURDATE(), INTERVAL 2 DAY))
                    ", [$invNum, $bookingId, $b['customer_id'], $subtotal, $tax, $b['total_amount']]);
                }
            }

            set_flash('success', "Service status updated to '{$targetStatus}'.");
            header('Location: ' . BASE_URL . '/technician/index.php');
            exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch vehicles assigned to this technician
$assignedJobs = Database::fetchAll("
    SELECT b.*, v.make, v.model, v.year, v.reg_number, v.fuel_type, v.color,
           u.name as customer_name, u.phone as customer_phone,
           (SELECT COUNT(*) FROM vehicle_inspections vi WHERE vi.booking_id = b.id) as has_inspection,
           (SELECT COUNT(*) FROM service_estimates se WHERE se.booking_id = b.id) as has_estimate
    FROM bookings b
    JOIN customers c ON b.customer_id = c.id
    JOIN users u ON c.user_id = u.id
    JOIN vehicles v ON b.vehicle_id = v.id
    WHERE b.assigned_technician_id = ? OR b.current_status IN ('Vehicle Received at Workshop', 'Inspection in Progress', 'Service in Progress')
    ORDER BY b.id DESC
", [$employeeId]);

$pageTitle = 'Technician Workshop Bay';
$extraCss = 'assets/css/dashboard.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <!-- Technician Sidebar -->
    <aside class="dashboard-sidebar">
        <div class="sidebar-user">
            <div class="rounded-circle bg-info text-dark d-flex align-items-center justify-content-center fw-bold sidebar-avatar">
                <i class="fa-solid fa-screwdriver-wrench"></i>
            </div>
            <div class="user-info">
                <div class="fw-bold text-white small"><?= htmlspecialchars($currentUser['name']) ?></div>
                <span class="badge bg-info text-dark text-uppercase" style="font-size:0.65rem;"><?= htmlspecialchars($employee['employee_code'] ?? 'TECH-BAY') ?></span>
            </div>
        </div>

        <ul class="sidebar-menu">
            <li><a href="index.php" class="sidebar-link active"><i class="fa-solid fa-screwdriver-wrench"></i><span class="nav-text">Workshop Bays</span></a></li>
            <li><a href="../track-service.php" class="sidebar-link"><i class="fa-solid fa-crosshairs"></i><span class="nav-text">Live Tracker</span></a></li>
        </ul>
    </aside>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-white mb-1"><i class="fa-solid fa-screwdriver-wrench text-danger me-2"></i>Service Bay Terminal</h2>
                <p class="text-muted small mb-0">Multi-point diagnostic inspection, repair estimate generation and service completion updates.</p>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <?php if (!empty($assignedJobs)): ?>
                <?php foreach ($assignedJobs as $job): ?>
                    <?php 
                        $curr = $job['current_status'];
                        $bId = $job['id'];
                    ?>
                    <div class="col-lg-6">
                        <div class="card-dark p-4 h-100 border-danger border-opacity-40 position-relative">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <span class="badge bg-secondary mb-1"><?= htmlspecialchars($job['booking_number']) ?></span>
                                    <h4 class="fw-bold text-white mb-0"><?= htmlspecialchars($job['make'] . ' ' . $job['model']) ?></h4>
                                    <span class="badge bg-danger fs-6"><?= htmlspecialchars($job['reg_number']) ?></span>
                                </div>
                                <?= get_status_badge($curr) ?>
                            </div>

                            <div class="bg-surface p-3 rounded border border-dark small mb-3">
                                <div class="text-light mb-1">
                                    <i class="fa-solid fa-user text-danger me-2"></i>Owner: <strong><?= htmlspecialchars($job['customer_name']) ?></strong> (<?= htmlspecialchars($job['customer_phone']) ?>)
                                </div>
                                <div class="text-light mb-2">
                                    <i class="fa-solid fa-gas-pump text-danger me-2"></i>Specs: <strong><?= htmlspecialchars($job['fuel_type']) ?> &bull; <?= htmlspecialchars($job['color']) ?></strong>
                                </div>
                                <?php if (!empty($job['customer_notes'])): ?>
                                    <div class="p-2 rounded bg-black border border-secondary text-warning small">
                                        <i class="fa-solid fa-comment-dots me-1"></i> Customer Complaint: <?= htmlspecialchars($job['customer_notes']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Multi-Action Workflow Toolbar -->
                            <div class="d-flex flex-wrap gap-2 pt-2 border-top border-dark">
                                
                                <!-- Step 1: Start Inspection -->
                                <?php if ($curr === 'Vehicle Received at Workshop'): ?>
                                    <form method="POST" action="index.php" class="w-100">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update_tech">
                                        <input type="hidden" name="booking_id" value="<?= $bId ?>">
                                        <input type="hidden" name="target_status" value="Inspection in Progress">
                                        <button type="submit" class="btn btn-warning w-100 py-2 fw-bold text-dark">
                                            <i class="fa-solid fa-clipboard-check me-2"></i>Start Multi-Point Inspection
                                        </button>
                                    </form>

                                <!-- Step 2: Fill Inspection & Create Estimate -->
                                <?php elseif ($curr === 'Inspection in Progress'): ?>
                                    <a href="inspection.php?booking_id=<?= $bId ?>" class="btn btn-red flex-grow-1">
                                        <i class="fa-solid fa-list-check me-1"></i> <?= $job['has_inspection'] ? 'Edit Inspection Report' : 'Complete 60-Point Checklist' ?>
                                    </a>
                                    <a href="estimate.php?booking_id=<?= $bId ?>" class="btn btn-outline-warning flex-grow-1">
                                        <i class="fa-solid fa-file-invoice-dollar me-1"></i> Generate Estimate
                                    </a>

                                <!-- Step 3: Waiting for customer approval -->
                                <?php elseif ($curr === 'Waiting for Customer Approval'): ?>
                                    <div class="w-100 p-2 rounded bg-warning bg-opacity-10 border border-warning text-warning small text-center mb-2">
                                        <i class="fa-solid fa-hourglass-half me-1"></i> Waiting for customer approval on estimate.
                                    </div>
                                    <form method="POST" action="index.php" class="w-100">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update_tech">
                                        <input type="hidden" name="booking_id" value="<?= $bId ?>">
                                        <input type="hidden" name="target_status" value="Service in Progress">
                                        <button type="submit" class="btn btn-outline-light btn-sm w-100">
                                            <i class="fa-solid fa-bolt me-1"></i> Override: Customer Approved via Phone
                                        </button>
                                    </form>

                                <!-- Step 4: Servicing in Progress -->
                                <?php elseif ($curr === 'Service in Progress'): ?>
                                    <button type="button" class="btn btn-success w-100 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#completeServiceModal<?= $bId ?>">
                                        <i class="fa-solid fa-circle-check me-2"></i>Mark Service Completed
                                    </button>

                                <!-- Step 5: Service Completed -->
                                <?php elseif ($curr === 'Service Completed'): ?>
                                    <form method="POST" action="index.php" class="w-100">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update_tech">
                                        <input type="hidden" name="booking_id" value="<?= $bId ?>">
                                        <input type="hidden" name="target_status" value="Ready for Delivery">
                                        <button type="submit" class="btn btn-info text-dark w-100 py-2 fw-bold">
                                            <i class="fa-solid fa-shield-check me-2"></i>Quality Check Passed &rarr; Ready for Delivery
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="text-muted small py-2 w-100 text-center">
                                        Current Status: <strong><?= htmlspecialchars($curr) ?></strong>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>

                    <!-- Modal: Complete Service Form -->
                    <div class="modal fade" id="completeServiceModal<?= $bId ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content card-dark border-secondary">
                                <div class="modal-header border-dark">
                                    <h5 class="modal-title text-white fw-bold">Service Completion Log: <?= htmlspecialchars($job['reg_number']) ?></h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="index.php">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update_tech">
                                    <input type="hidden" name="booking_id" value="<?= $bId ?>">
                                    <input type="hidden" name="target_status" value="Service Completed">

                                    <div class="modal-body text-light">
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Action Taken / Repairs Performed *</label>
                                            <textarea name="action_taken" class="form-control form-control-dark" rows="3" required placeholder="e.g. Engine oil replaced with 5W30 synthetic, new oil filter installed, front brake pads renewed and disc rotors skimmed."></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Parts Replaced (OEM):</label>
                                            <input type="text" name="parts_replaced" class="form-control form-control-dark" placeholder="e.g. Bosch Brake Pads, OEM Synthetic Oil 4.5L">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Total Labor Hours Spent:</label>
                                            <input type="number" step="0.5" name="hours_spent" class="form-control form-control-dark" value="2.5">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Final Quality Audit Remarks:</label>
                                            <input type="text" name="remarks" class="form-control form-control-dark" placeholder="e.g. Road test completed, brake pedal response firm, no diagnostic DTC codes.">
                                        </div>
                                    </div>
                                    <div class="modal-footer border-dark">
                                        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success px-4"><i class="fa-solid fa-check me-1"></i>Submit Service Completion</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5 card-dark">
                    <i class="fa-solid fa-wrench fa-4x text-muted mb-3"></i>
                    <h4 class="text-white fw-bold mb-2">Workshop Bays Clear</h4>
                    <p class="text-muted">No vehicles currently require servicing on your assigned bay.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
