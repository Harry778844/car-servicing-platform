<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * Admin Booking Management & Status Dispatch
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

require_role('admin', 'login.php');

$error = '';
$success = '';

// Handle Booking Assignment & Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_booking') {
        $bookingId = (int)$_POST['booking_id'];
        $status = $_POST['current_status'] ?? '';
        $pickupId = !empty($_POST['assigned_pickup_id']) ? (int)$_POST['assigned_pickup_id'] : null;
        $techId = !empty($_POST['assigned_technician_id']) ? (int)$_POST['assigned_technician_id'] : null;
        $paymentStatus = $_POST['payment_status'] ?? 'Unpaid';
        $remarks = trim($_POST['status_remarks'] ?? '');

        try {
            // Update booking details
            Database::execute("
                UPDATE bookings 
                SET assigned_pickup_id = ?, assigned_technician_id = ?, payment_status = ?
                WHERE id = ?
            ", [$pickupId, $techId, $paymentStatus, $bookingId]);

            // If status changed, log audit trail
            if (!empty($status)) {
                log_status_history($bookingId, $status, $remarks, $_SESSION['user_id']);
            }

            // Sync with pickup_requests if driver changed
            if ($pickupId) {
                Database::execute("
                    UPDATE pickup_requests SET pickup_executive_id = ?, status = 'Assigned' WHERE booking_id = ?
                ", [$pickupId, $bookingId]);
            }

            // If payment status marked as paid, update invoice as well
            if ($paymentStatus === 'Paid') {
                Database::execute("UPDATE invoices SET status = 'paid' WHERE booking_id = ?", [$bookingId]);
            }

            set_flash('success', "Booking updated successfully!");
            header('Location: ' . BASE_URL . '/admin/bookings.php');
            exit;
        } catch (Exception $e) {
            $error = 'Update failed: ' . $e->getMessage();
        }
    }
}

// Fetch drivers and technicians for dropdowns
$drivers = Database::fetchAll("
    SELECT e.id, e.employee_code, u.name, u.phone 
    FROM employees e 
    JOIN users u ON e.user_id = u.id 
    WHERE e.department = 'pickup_delivery' AND e.status = 'available'
");

$technicians = Database::fetchAll("
    SELECT e.id, e.employee_code, u.name, e.designation 
    FROM employees e 
    JOIN users u ON e.user_id = u.id 
    WHERE e.department = 'workshop_technician'
");

// Filters
$statusFilter = trim($_GET['status'] ?? 'all');
$search = trim($_GET['search'] ?? '');
$params = [];
$whereClause = "WHERE 1=1";

if ($statusFilter !== 'all') {
    $whereClause .= " AND b.current_status = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $whereClause .= " AND (b.booking_number LIKE ? OR v.reg_number LIKE ? OR u.name LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$bookings = Database::fetchAll("
    SELECT b.*, v.make, v.model, v.reg_number, 
           u.name as customer_name, u.phone as customer_phone,
           p_user.name as pickup_name, t_user.name as tech_name
    FROM bookings b
    JOIN customers c ON b.customer_id = c.id
    JOIN users u ON c.user_id = u.id
    JOIN vehicles v ON b.vehicle_id = v.id
    LEFT JOIN employees emp_p ON b.assigned_pickup_id = emp_p.id
    LEFT JOIN users p_user ON emp_p.user_id = p_user.id
    LEFT JOIN employees emp_t ON b.assigned_technician_id = emp_t.id
    LEFT JOIN users t_user ON emp_t.user_id = t_user.id
    {$whereClause}
    ORDER BY b.id DESC
", $params);

$pageTitle = 'Booking Management & Workflow Dispatch';
$extraCss = 'assets/css/dashboard.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <!-- Admin Sidebar -->
    <aside class="dashboard-sidebar">
        <div class="sidebar-user">
            <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center fw-bold sidebar-avatar">
                AD
            </div>
            <div class="user-info">
                <div class="fw-bold text-white small"><?= htmlspecialchars($currentUser['name']) ?></div>
                <span class="badge bg-danger text-uppercase" style="font-size:0.65rem;">Administrator</span>
            </div>
        </div>

        <ul class="sidebar-menu">
            <li><a href="index.php" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i><span class="nav-text">Command Center</span></a></li>
            <li><a href="bookings.php" class="sidebar-link active"><i class="fa-solid fa-calendar-days"></i><span class="nav-text">All Bookings</span></a></li>
            <li><a href="customers.php" class="sidebar-link"><i class="fa-solid fa-users"></i><span class="nav-text">Customers</span></a></li>
            <li><a href="employees.php" class="sidebar-link"><i class="fa-solid fa-id-badge"></i><span class="nav-text">Staff & Drivers</span></a></li>
            <li><a href="services.php" class="sidebar-link"><i class="fa-solid fa-tools"></i><span class="nav-text">Services Catalog</span></a></li>
            <li><a href="invoices.php" class="sidebar-link"><i class="fa-solid fa-file-invoice-dollar"></i><span class="nav-text">Invoices & Billing</span></a></li>
        </ul>
    </aside>

    <main class="dashboard-content">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <h2 class="fw-bold text-white mb-1"><i class="fa-solid fa-calendar-days text-danger me-2"></i>Manage Bookings</h2>
                <p class="text-muted small mb-0">Track bookings across all 12 stages, assign pickup drivers and allocate workshop technician bays.</p>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Search Bar and Filter Tabs -->
        <div class="card-dark p-3 mb-4">
            <form method="GET" action="bookings.php" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" name="search" class="form-control form-control-dark" placeholder="Search by Booking #, Reg #, Customer..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-5">
                    <select name="status" class="form-select form-select-dark" onchange="this.form.submit()">
                        <option value="all">-- All 12 Workflow Statuses --</option>
                        <?php foreach (BOOKING_STATUSES as $num => $stName): ?>
                            <option value="<?= $stName ?>" <?= $statusFilter === $stName ? 'selected' : '' ?>>
                                <?= $num ?>. <?= $stName ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-red w-100">Filter</button>
                </div>
            </form>
        </div>

        <!-- Bookings List Table -->
        <div class="card-dark p-4">
            <div class="table-responsive">
                <table class="table table-dark-custom mb-0">
                    <thead>
                        <tr>
                            <th>Ref #</th>
                            <th>Customer</th>
                            <th>Car / Reg</th>
                            <th>Pickup Date</th>
                            <th>Current Status</th>
                            <th>Assigned Driver</th>
                            <th>Assigned Tech</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th class="text-end">Manage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($bookings)): ?>
                            <?php foreach ($bookings as $b): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($b['booking_number']) ?></strong></td>
                                    <td>
                                        <div class="text-white fw-bold"><?= htmlspecialchars($b['customer_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($b['customer_phone']) ?></small>
                                    </td>
                                    <td>
                                        <div class="text-light"><?= htmlspecialchars($b['make'] . ' ' . $b['model']) ?></div>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($b['reg_number']) ?></span>
                                    </td>
                                    <td>
                                        <div><?= format_date($b['preferred_pickup_date'], 'd M Y') ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($b['preferred_pickup_time']) ?></small>
                                    </td>
                                    <td><?= get_status_badge($b['current_status']) ?></td>
                                    <td>
                                        <small class="text-white"><?= htmlspecialchars($b['pickup_name'] ?: 'Unassigned') ?></small>
                                    </td>
                                    <td>
                                        <small class="text-white"><?= htmlspecialchars($b['tech_name'] ?: 'Unassigned') ?></small>
                                    </td>
                                    <td class="fw-bold text-white"><?= format_currency($b['total_amount']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $b['payment_status'] === 'Paid' ? 'success' : 'warning' ?>">
                                            <?= htmlspecialchars($b['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-dark-surface btn-sm" data-bs-toggle="modal" data-bs-target="#editBookingModal<?= $b['id'] ?>" title="Edit Status & Assignment">
                                            <i class="fa-solid fa-pen-to-square text-danger"></i>
                                        </button>
                                        <a href="../track-service.php?booking=<?= htmlspecialchars($b['booking_number']) ?>" class="btn btn-dark-surface btn-sm" target="_blank" title="Live Telematics">
                                            <i class="fa-solid fa-radar text-info"></i>
                                        </a>
                                    </td>
                                </tr>

                                <!-- Modal to update booking -->
                                <div class="modal fade" id="editBookingModal<?= $b['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content card-dark border-secondary">
                                            <div class="modal-header border-dark">
                                                <h5 class="modal-title text-white fw-bold">Manage Booking: <?= htmlspecialchars($b['booking_number']) ?></h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST" action="bookings.php">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="update_booking">
                                                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">

                                                <div class="modal-body text-light">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold">Update Status (12 Canonical Stages):</label>
                                                        <select name="current_status" class="form-select form-select-dark">
                                                            <?php foreach (BOOKING_STATUSES as $num => $stName): ?>
                                                                <option value="<?= $stName ?>" <?= $b['current_status'] === $stName ? 'selected' : '' ?>>
                                                                    <?= $num ?>. <?= $stName ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold">Assign Pickup Executive:</label>
                                                        <select name="assigned_pickup_id" class="form-select form-select-dark">
                                                            <option value="">-- Unassigned --</option>
                                                            <?php foreach ($drivers as $drv): ?>
                                                                <option value="<?= $drv['id'] ?>" <?= $b['assigned_pickup_id'] == $drv['id'] ? 'selected' : '' ?>>
                                                                    <?= htmlspecialchars($drv['name']) ?> (<?= $drv['employee_code'] ?>)
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold">Assign Service Technician:</label>
                                                        <select name="assigned_technician_id" class="form-select form-select-dark">
                                                            <option value="">-- Unassigned --</option>
                                                            <?php foreach ($technicians as $tech): ?>
                                                                <option value="<?= $tech['id'] ?>" <?= $b['assigned_technician_id'] == $tech['id'] ? 'selected' : '' ?>>
                                                                    <?= htmlspecialchars($tech['name']) ?> (<?= $tech['designation'] ?>)
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold">Payment Status:</label>
                                                        <select name="payment_status" class="form-select form-select-dark">
                                                            <option value="Unpaid" <?= $b['payment_status'] === 'Unpaid' ? 'selected' : '' ?>>Unpaid</option>
                                                            <option value="Paid" <?= $b['payment_status'] === 'Paid' ? 'selected' : '' ?>>Paid (Settled)</option>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label small fw-bold">Status Change Remark / Note:</label>
                                                        <input type="text" name="status_remarks" class="form-control form-control-dark" placeholder="e.g. Assigned driver for 10:00 AM pickup slot">
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-dark">
                                                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-red px-4">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">No bookings found matching your search criteria.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
