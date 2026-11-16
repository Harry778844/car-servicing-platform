<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * Admin Dashboard & KPI Command Center
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

require_role('admin', 'login.php');

$currentUser = current_user();

// Key Analytics
$totalRevenue = Database::fetchOne("SELECT SUM(amount) as total FROM payments WHERE status = 'successful'")['total'] ?? 0;
$totalBookings = Database::fetchOne("SELECT COUNT(*) as cnt FROM bookings")['cnt'] ?? 0;
$activeJobs = Database::fetchOne("SELECT COUNT(*) as cnt FROM bookings WHERE current_status != 'Delivered'")['cnt'] ?? 0;
$totalCustomers = Database::fetchOne("SELECT COUNT(*) as cnt FROM customers")['cnt'] ?? 0;
$totalEmployees = Database::fetchOne("SELECT COUNT(*) as cnt FROM employees WHERE status = 'available'")['cnt'] ?? 0;

// Recent 10 bookings
$recentBookings = Database::fetchAll("
    SELECT b.*, v.make, v.model, v.reg_number, u.name as customer_name, u.phone as customer_phone
    FROM bookings b
    JOIN customers c ON b.customer_id = c.id
    JOIN users u ON c.user_id = u.id
    JOIN vehicles v ON b.vehicle_id = v.id
    ORDER BY b.id DESC LIMIT 10
");

// Breakdown by status
$statusCounts = Database::fetchAll("
    SELECT current_status, COUNT(*) as count 
    FROM bookings 
    GROUP BY current_status
");

$pageTitle = 'Admin Command Center';
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
            <li><a href="index.php" class="sidebar-link active"><i class="fa-solid fa-gauge-high"></i><span class="nav-text">Command Center</span></a></li>
            <li><a href="bookings.php" class="sidebar-link"><i class="fa-solid fa-calendar-days"></i><span class="nav-text">All Bookings</span></a></li>
            <li><a href="customers.php" class="sidebar-link"><i class="fa-solid fa-users"></i><span class="nav-text">Customers</span></a></li>
            <li><a href="employees.php" class="sidebar-link"><i class="fa-solid fa-id-badge"></i><span class="nav-text">Staff & Drivers</span></a></li>
            <li><a href="services.php" class="sidebar-link"><i class="fa-solid fa-tools"></i><span class="nav-text">Services Catalog</span></a></li>
            <li><a href="invoices.php" class="sidebar-link"><i class="fa-solid fa-file-invoice-dollar"></i><span class="nav-text">Invoices & Billing</span></a></li>
        </ul>
    </aside>

    <!-- Admin Content Area -->
    <main class="dashboard-content">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <h2 class="fw-bold text-white mb-1"><i class="fa-solid fa-sliders text-danger me-2"></i>Admin Command Center</h2>
                <p class="text-muted small mb-0">Live operations, revenue metrics, workshop fleet dispatch and service queue.</p>
            </div>
            <div class="mt-3 mt-md-0 d-flex gap-2">
                <a href="bookings.php" class="btn btn-red px-3">
                    <i class="fa-solid fa-calendar-days me-1"></i> Manage Queue
                </a>
                <a href="../config/setup.php" class="btn btn-outline-light px-3" target="_blank">
                    <i class="fa-solid fa-database me-1"></i> Database Tools
                </a>
            </div>
        </div>

        <!-- 4 Core Metric KPI Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div>
                        <div class="stat-label">Total Revenue Collected</div>
                        <div class="stat-val text-success fs-3"><?= format_currency($totalRevenue) ?></div>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-25 text-success">
                        <i class="fa-solid fa-indian-rupee-sign"></i>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div>
                        <div class="stat-label">Active Workshop Jobs</div>
                        <div class="stat-val text-warning"><?= $activeJobs ?></div>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-25 text-warning">
                        <i class="fa-solid fa-gears"></i>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div>
                        <div class="stat-label">Total Bookings</div>
                        <div class="stat-val"><?= $totalBookings ?></div>
                    </div>
                    <div class="stat-icon bg-danger bg-opacity-25 text-danger">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div>
                        <div class="stat-label">Registered Customers</div>
                        <div class="stat-val text-info"><?= $totalCustomers ?></div>
                    </div>
                    <div class="stat-icon bg-info bg-opacity-25 text-info">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pipeline Status Summary Badges -->
        <div class="card-dark p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold text-white small mb-0"><i class="fa-solid fa-traffic-light text-danger me-2"></i>Workflow Queue Breakdown</h6>
                <a href="bookings.php" class="text-danger small">Full Pipeline View</a>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($statusCounts as $sc): ?>
                    <span class="badge bg-surface border border-secondary text-light px-3 py-2">
                        <?= htmlspecialchars($sc['current_status']) ?>: <strong class="text-danger ms-1"><?= $sc['count'] ?></strong>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Bookings Table -->
        <div class="card-dark p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-white mb-0"><i class="fa-solid fa-list-check text-danger me-2"></i>Live Service Bookings</h5>
                <a href="bookings.php" class="btn btn-outline-danger btn-sm">View All <?= $totalBookings ?> Bookings</a>
            </div>

            <div class="table-responsive">
                <table class="table table-dark-custom mb-0">
                    <thead>
                        <tr>
                            <th>Ref Number</th>
                            <th>Customer & Phone</th>
                            <th>Vehicle</th>
                            <th>Pickup Date</th>
                            <th>Workflow Status</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentBookings as $b): ?>
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
                                <td><?= format_date($b['preferred_pickup_date'], 'd M Y') ?></td>
                                <td><?= get_status_badge($b['current_status']) ?></td>
                                <td class="fw-bold text-white"><?= format_currency($b['total_amount']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $b['payment_status'] === 'Paid' ? 'success' : 'warning' ?>">
                                        <?= htmlspecialchars($b['payment_status']) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="bookings.php?booking_id=<?= $b['id'] ?>" class="btn btn-dark-surface btn-sm" title="Manage / Assign">
                                        <i class="fa-solid fa-pen-to-square text-danger"></i>
                                    </a>
                                    <a href="../track-service.php?booking=<?= htmlspecialchars($b['booking_number']) ?>" class="btn btn-dark-surface btn-sm" title="Live Tracker" target="_blank">
                                        <i class="fa-solid fa-radar text-info"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
