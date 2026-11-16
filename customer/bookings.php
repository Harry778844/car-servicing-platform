<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * Customer Booking History & Invoices
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

require_role('customer', 'login.php');

$currentUser = current_user();
$userId = $currentUser['id'];

$customer = Database::fetchOne("SELECT id FROM customers WHERE user_id = ? LIMIT 1", [$userId]);
$customerId = $customer['id'] ?? 0;

$statusFilter = trim($_GET['status'] ?? 'all');
$params = [$customerId];
$whereStatus = "";
if ($statusFilter === 'active') {
    $whereStatus = " AND b.current_status != 'Delivered'";
} elseif ($statusFilter === 'completed') {
    $whereStatus = " AND b.current_status = 'Delivered'";
}

$bookings = Database::fetchAll("
    SELECT b.*, v.make, v.model, v.reg_number, inv.invoice_number, inv.status as invoice_status
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    LEFT JOIN invoices inv ON b.id = inv.booking_id
    WHERE b.customer_id = ? {$whereStatus}
    ORDER BY b.id DESC
", $params);

$pageTitle = 'Service History & Invoices';
$extraCss = 'assets/css/dashboard.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <aside class="dashboard-sidebar">
        <div class="sidebar-user">
            <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center fw-bold sidebar-avatar">
                <?= strtoupper(substr($currentUser['name'], 0, 2)) ?>
            </div>
            <div class="user-info">
                <div class="fw-bold text-white small text-truncate" style="max-width: 150px;"><?= htmlspecialchars($currentUser['name']) ?></div>
                <span class="badge bg-danger text-uppercase" style="font-size:0.65rem;">Customer</span>
            </div>
        </div>

        <ul class="sidebar-menu">
            <li><a href="index.php" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i><span class="nav-text">Dashboard</span></a></li>
            <li><a href="vehicles.php" class="sidebar-link"><i class="fa-solid fa-car"></i><span class="nav-text">My Garage</span></a></li>
            <li><a href="bookings.php" class="sidebar-link active"><i class="fa-solid fa-calendar-check"></i><span class="nav-text">Service History</span></a></li>
            <li><a href="../book-service.php" class="sidebar-link text-danger"><i class="fa-solid fa-calendar-plus"></i><span class="nav-text">Book New Pickup</span></a></li>
            <li><a href="../track-service.php" class="sidebar-link"><i class="fa-solid fa-crosshairs"></i><span class="nav-text">Live Vehicle Tracker</span></a></li>
        </ul>
    </aside>

    <main class="dashboard-content">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <h2 class="fw-bold text-white mb-1"><i class="fa-solid fa-clock-rotate-left text-danger me-2"></i>Service History</h2>
                <p class="text-muted small mb-0">Track all your scheduled, ongoing, and completed vehicle service records.</p>
            </div>
            <a href="../book-service.php" class="btn btn-red mt-3 mt-md-0 px-4">
                <i class="fa-solid fa-calendar-plus me-1"></i> Book New Service
            </a>
        </div>

        <!-- Filter tabs -->
        <div class="filter-tabs mb-4">
            <a href="bookings.php?status=all" class="filter-btn <?= $statusFilter === 'all' ? 'active' : '' ?>">All Bookings</a>
            <a href="bookings.php?status=active" class="filter-btn <?= $statusFilter === 'active' ? 'active' : '' ?>">Ongoing / Active</a>
            <a href="bookings.php?status=completed" class="filter-btn <?= $statusFilter === 'completed' ? 'active' : '' ?>">Completed & Delivered</a>
        </div>

        <div class="card-dark p-4">
            <?php if (!empty($bookings)): ?>
                <div class="table-responsive">
                    <table class="table table-dark-custom mb-0">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Vehicle</th>
                                <th>Date Booked</th>
                                <th>Pickup Scheduled</th>
                                <th>Service Status</th>
                                <th>Total Bill</th>
                                <th>Payment</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($b['booking_number']) ?></strong></td>
                                    <td>
                                        <div class="text-white fw-bold"><?= htmlspecialchars($b['make'] . ' ' . $b['model']) ?></div>
                                        <small class="badge bg-danger"><?= htmlspecialchars($b['reg_number']) ?></small>
                                    </td>
                                    <td><?= format_date($b['booking_date'], 'd M Y') ?></td>
                                    <td>
                                        <div class="text-light small"><?= format_date($b['preferred_pickup_date'], 'd M Y') ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($b['preferred_pickup_time']) ?></small>
                                    </td>
                                    <td><?= get_status_badge($b['current_status']) ?></td>
                                    <td class="fw-bold text-white"><?= format_currency($b['total_amount']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $b['payment_status'] === 'Paid' ? 'success' : 'warning' ?>">
                                            <?= htmlspecialchars($b['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="../track-service.php?booking=<?= htmlspecialchars($b['booking_number']) ?>" class="btn btn-dark-surface btn-sm" title="Track Live">
                                                <i class="fa-solid fa-radar text-danger"></i>
                                            </a>
                                            <?php if (!empty($b['invoice_number'])): ?>
                                                <a href="../invoice.php?inv=<?= htmlspecialchars($b['invoice_number']) ?>" class="btn btn-dark-surface btn-sm" title="Tax Invoice" target="_blank">
                                                    <i class="fa-solid fa-file-invoice text-info"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($b['payment_status'] !== 'Paid'): ?>
                                                <a href="../payment.php?booking=<?= htmlspecialchars($b['booking_number']) ?>" class="btn btn-red btn-sm" title="Pay Now">
                                                    <i class="fa-solid fa-credit-card"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="fa-solid fa-calendar-xmark fa-3x mb-3"></i>
                    <p class="mb-3">No bookings match the selected filter.</p>
                    <a href="../book-service.php" class="btn btn-red btn-sm">Schedule New Service</a>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
