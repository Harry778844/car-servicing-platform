<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * Pickup & Delivery Executive Portal
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

require_role('pickup', 'login.php');

$currentUser = current_user();
$userId = $currentUser['id'];

// Fetch employee record
$employee = Database::fetchOne("SELECT * FROM employees WHERE user_id = ? LIMIT 1", [$userId]);
$employeeId = $employee['id'] ?? 0;

$error = '';
$success = '';

// Handle Pickup status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $bookingId = (int)$_POST['booking_id'];
    $targetStatus = $_POST['target_status'] ?? '';
    $odometer = !empty($_POST['odometer']) ? (int)$_POST['odometer'] : null;
    $fuel = trim($_POST['fuel_level'] ?? '');
    $notes = trim($_POST['pickup_notes'] ?? '');

    try {
        if ($targetStatus === 'Vehicle Picked Up') {
            Database::execute("
                UPDATE pickup_requests 
                SET actual_pickup_time = NOW(), pickup_odometer = ?, pickup_fuel_level = ?, pickup_notes = ?, status = 'Picked Up'
                WHERE booking_id = ?
            ", [$odometer, $fuel, $notes, $bookingId]);

            log_status_history($bookingId, 'Vehicle Picked Up', "Collected from doorstep. Odometer: {$odometer} KM, Fuel: {$fuel}. Notes: {$notes}", $userId);
            set_flash('success', 'Vehicle marked as Picked Up. Drive safely to workshop!');

        } elseif ($targetStatus === 'Vehicle Received at Workshop') {
            Database::execute("
                UPDATE pickup_requests SET status = 'Delivered_To_Workshop' WHERE booking_id = ?
            ", [$bookingId]);

            log_status_history($bookingId, 'Vehicle Received at Workshop', 'Vehicle arrived safely at workshop service bay.', $userId);
            set_flash('success', 'Vehicle safely checked into workshop bay.');

        } elseif ($targetStatus === 'Out for Delivery') {
            Database::execute("
                UPDATE pickup_requests SET status = 'Out_For_Delivery' WHERE booking_id = ?
            ", [$bookingId]);

            log_status_history($bookingId, 'Out for Delivery', 'Executive en route to deliver vehicle to customer address.', $userId);
            set_flash('success', 'Vehicle marked Out for Delivery.');

        } elseif ($targetStatus === 'Delivered') {
            $dropOdo = !empty($_POST['drop_odometer']) ? (int)$_POST['drop_odometer'] : null;
            Database::execute("
                UPDATE pickup_requests SET actual_drop_time = NOW(), drop_odometer = ?, status = 'Delivered' WHERE booking_id = ?
            ", [$dropOdo, $bookingId]);

            log_status_history($bookingId, 'Delivered', 'Vehicle safely delivered back to customer. Handover complete.', $userId);
            set_flash('success', 'Vehicle successfully marked as Delivered!');
        }

        header('Location: ' . BASE_URL . '/pickup/index.php');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch assigned tasks (Both Pickup and Delivery tasks)
$assignedTasks = Database::fetchAll("
    SELECT b.*, v.make, v.model, v.year, v.reg_number, v.color,
           u.name as customer_name, u.phone as customer_phone,
           pr.scheduled_time, pr.pickup_odometer, pr.pickup_fuel_level, pr.status as pr_status
    FROM bookings b
    JOIN customers c ON b.customer_id = c.id
    JOIN users u ON c.user_id = u.id
    JOIN vehicles v ON b.vehicle_id = v.id
    LEFT JOIN pickup_requests pr ON b.id = pr.booking_id
    WHERE b.assigned_pickup_id = ? OR pr.pickup_executive_id = ?
    ORDER BY b.id DESC
", [$employeeId, $employeeId]);

$pageTitle = 'Driver Task Terminal';
$extraCss = 'assets/css/dashboard.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <!-- Pickup Driver Sidebar -->
    <aside class="dashboard-sidebar">
        <div class="sidebar-user">
            <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center fw-bold sidebar-avatar">
                <i class="fa-solid fa-truck-pickup"></i>
            </div>
            <div class="user-info">
                <div class="fw-bold text-white small"><?= htmlspecialchars($currentUser['name']) ?></div>
                <span class="badge bg-danger text-uppercase" style="font-size:0.65rem;"><?= htmlspecialchars($employee['employee_code'] ?? 'DRIVER') ?></span>
            </div>
        </div>

        <ul class="sidebar-menu">
            <li><a href="index.php" class="sidebar-link active"><i class="fa-solid fa-clipboard-list"></i><span class="nav-text">My Route Tasks</span></a></li>
            <li><a href="../track-service.php" class="sidebar-link"><i class="fa-solid fa-crosshairs"></i><span class="nav-text">Live Tracker</span></a></li>
        </ul>
    </aside>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-white mb-1"><i class="fa-solid fa-truck-pickup text-danger me-2"></i>Pickup & Drop Terminal</h2>
                <p class="text-muted small mb-0">Doorstep vehicle collection, odometer logging, and customer delivery completion.</p>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <?php if (!empty($assignedTasks)): ?>
                <?php foreach ($assignedTasks as $task): ?>
                    <?php 
                        $curr = $task['current_status'];
                        $isReadyForDelivery = in_array($curr, ['Ready for Delivery', 'Out for Delivery']);
                    ?>
                    <div class="col-lg-6">
                        <div class="card-dark p-4 h-100 border-<?= $isReadyForDelivery ? 'info' : 'danger' ?> border-opacity-40">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <span class="badge bg-secondary text-uppercase mb-1"><?= htmlspecialchars($task['booking_number']) ?></span>
                                    <h4 class="fw-bold text-white mb-0"><?= htmlspecialchars($task['make'] . ' ' . $task['model']) ?></h4>
                                    <span class="badge bg-danger fs-6"><?= htmlspecialchars($task['reg_number']) ?></span>
                                </div>
                                <?= get_status_badge($curr) ?>
                            </div>

                            <div class="bg-surface p-3 rounded border border-dark small mb-3">
                                <div class="text-light mb-1">
                                    <i class="fa-solid fa-user text-danger me-2"></i>Customer: <strong><?= htmlspecialchars($task['customer_name']) ?></strong>
                                </div>
                                <div class="text-light mb-2">
                                    <i class="fa-solid fa-phone text-danger me-2"></i>Contact: <a href="tel:<?= htmlspecialchars($task['customer_phone']) ?>" class="text-danger fw-bold"><?= htmlspecialchars($task['customer_phone']) ?></a>
                                </div>
                                <div class="text-muted">
                                    <i class="fa-solid fa-location-dot text-danger me-2"></i>Pickup Location:<br>
                                    <span class="text-white"><?= htmlspecialchars($task['pickup_address']) ?>, <?= htmlspecialchars($task['pickup_city']) ?> - <?= htmlspecialchars($task['pickup_pincode']) ?></span>
                                </div>
                                <div class="mt-2 text-warning small">
                                    <i class="fa-solid fa-clock me-1"></i> Scheduled: <?= format_date($task['preferred_pickup_date'], 'd M Y') ?> (<?= htmlspecialchars($task['preferred_pickup_time']) ?>)
                                </div>
                            </div>

                            <!-- Driver Action Buttons depending on status -->
                            <div class="mt-auto pt-3 border-top border-dark">
                                <?php if ($curr === 'Booking Confirmed' || $curr === 'Pickup Assigned'): ?>
                                    <button type="button" class="btn btn-red w-100 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#pickupModal<?= $task['id'] ?>">
                                        <i class="fa-solid fa-key me-2"></i>Collect Vehicle from Customer
                                    </button>
                                <?php elseif ($curr === 'Vehicle Picked Up'): ?>
                                    <form method="POST" action="index.php">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update_pickup">
                                        <input type="hidden" name="booking_id" value="<?= $task['id'] ?>">
                                        <input type="hidden" name="target_status" value="Vehicle Received at Workshop">
                                        <button type="submit" class="btn btn-info w-100 py-2 fw-bold text-dark">
                                            <i class="fa-solid fa-warehouse me-2"></i>Check-in at Workshop Bay
                                        </button>
                                    </form>
                                <?php elseif ($curr === 'Ready for Delivery'): ?>
                                    <form method="POST" action="index.php">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update_pickup">
                                        <input type="hidden" name="booking_id" value="<?= $task['id'] ?>">
                                        <input type="hidden" name="target_status" value="Out for Delivery">
                                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                                            <i class="fa-solid fa-truck-fast me-2"></i>Start Delivery to Customer
                                        </button>
                                    </form>
                                <?php elseif ($curr === 'Out for Delivery'): ?>
                                    <button type="button" class="btn btn-success w-100 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#deliverModal<?= $task['id'] ?>">
                                        <i class="fa-solid fa-handshake me-2"></i>Complete Vehicle Handover
                                    </button>
                                <?php elseif ($curr === 'Delivered'): ?>
                                    <div class="text-center text-success py-2 fw-bold small">
                                        <i class="fa-solid fa-circle-check me-1"></i> Delivery Complete & Handover Done
                                    </div>
                                <?php else: ?>
                                    <div class="text-center text-muted small py-2">
                                        Vehicle in workshop servicing bay (<?= htmlspecialchars($curr) ?>)
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Modal: Collect Vehicle (Odometer, Fuel, Scratches) -->
                    <div class="modal fade" id="pickupModal<?= $task['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content card-dark border-secondary">
                                <div class="modal-header border-dark">
                                    <h5 class="modal-title text-white fw-bold">Doorstep Pickup: <?= htmlspecialchars($task['reg_number']) ?></h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="index.php">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update_pickup">
                                    <input type="hidden" name="booking_id" value="<?= $task['id'] ?>">
                                    <input type="hidden" name="target_status" value="Vehicle Picked Up">

                                    <div class="modal-body text-light">
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Current Odometer Reading (KM) *</label>
                                            <input type="number" name="odometer" class="form-control form-control-dark" placeholder="e.g. 42350" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Fuel Level Gauge *</label>
                                            <select name="fuel_level" class="form-select form-select-dark" required>
                                                <option value="100% (Full Tank)">100% (Full Tank)</option>
                                                <option value="75% (3/4 Tank)">75% (3/4 Tank)</option>
                                                <option value="50% (Half Tank)">50% (Half Tank)</option>
                                                <option value="25% (1/4 Tank)">25% (1/4 Tank)</option>
                                                <option value="Low (Reserve)">Low (Reserve)</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Pre-existing Scratches / Dents Observations:</label>
                                            <textarea name="pickup_notes" class="form-control form-control-dark" rows="3" placeholder="Note down any exterior marks, cracked lights, or customer belongings verified..."></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-dark">
                                        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-red px-4"><i class="fa-solid fa-check me-1"></i>Confirm Pickup</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Modal: Complete Delivery Handover -->
                    <div class="modal fade" id="deliverModal<?= $task['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content card-dark border-secondary">
                                <div class="modal-header border-dark">
                                    <h5 class="modal-title text-white fw-bold">Handover Vehicle: <?= htmlspecialchars($task['reg_number']) ?></h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="index.php">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update_pickup">
                                    <input type="hidden" name="booking_id" value="<?= $task['id'] ?>">
                                    <input type="hidden" name="target_status" value="Delivered">

                                    <div class="modal-body text-light">
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Final Drop-off Odometer (KM):</label>
                                            <input type="number" name="drop_odometer" class="form-control form-control-dark" placeholder="e.g. 42365">
                                        </div>
                                        <div class="p-3 rounded bg-surface border border-secondary mb-3 small text-muted">
                                            <i class="fa-solid fa-circle-check text-success me-1"></i>
                                            Confirm that customer has inspected vehicle clean exterior, checked AC & lights, and keys have been handed over.
                                        </div>
                                    </div>
                                    <div class="modal-footer border-dark">
                                        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success px-4"><i class="fa-solid fa-flag-checkered me-1"></i>Complete Handover</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5 card-dark">
                    <i class="fa-solid fa-truck-ramp-box fa-4x text-muted mb-3"></i>
                    <h4 class="text-white fw-bold mb-2">No Active Route Assignments</h4>
                    <p class="text-muted">You currently do not have any vehicle pickups or drops assigned.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
