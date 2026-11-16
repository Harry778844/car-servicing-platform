<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * Customer Dashboard Portal
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

require_role('customer', 'login.php');

$currentUser = current_user();
$userId = $currentUser['id'];

// Fetch customer record
$customer = Database::fetchOne("SELECT * FROM customers WHERE user_id = ? LIMIT 1", [$userId]);
if (!$customer) {
    Database::execute("INSERT INTO customers (user_id) VALUES (?)", [$userId]);
    $customer = Database::fetchOne("SELECT * FROM customers WHERE user_id = ? LIMIT 1", [$userId]);
}
$customerId = $customer['id'];

// Customer metrics
$vehiclesCount = Database::fetchOne("SELECT COUNT(*) as cnt FROM vehicles WHERE customer_id = ?", [$customerId])['cnt'] ?? 0;
$activeBookingsCount = Database::fetchOne("SELECT COUNT(*) as cnt FROM bookings WHERE customer_id = ? AND current_status != 'Delivered'", [$customerId])['cnt'] ?? 0;
$completedCount = Database::fetchOne("SELECT COUNT(*) as cnt FROM bookings WHERE customer_id = ? AND current_status = 'Delivered'", [$customerId])['cnt'] ?? 0;
$totalSpent = Database::fetchOne("SELECT SUM(final_amount) as total FROM invoices WHERE customer_id = ? AND status = 'paid'", [$customerId])['total'] ?? 0;

// Fetch latest active booking
$latestBooking = Database::fetchOne("
    SELECT b.*, v.make, v.model, v.reg_number 
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    WHERE b.customer_id = ? 
    ORDER BY b.id DESC LIMIT 1
", [$customerId]);

// Fetch all bookings for table
$allBookings = Database::fetchAll("
    SELECT b.*, v.make, v.model, v.reg_number 
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    WHERE b.customer_id = ? 
    ORDER BY b.id DESC LIMIT 10
", [$customerId]);

// Fetch customer vehicles
$vehicles = Database::fetchAll("SELECT * FROM vehicles WHERE customer_id = ? ORDER BY id DESC", [$customerId]);

$pageTitle = 'Customer Dashboard';
$extraCss = 'assets/css/dashboard.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <!-- Sidebar Navigation -->
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
            <li>
                <a href="index.php" class="sidebar-link active">
                    <i class="fa-solid fa-gauge-high"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="vehicles.php" class="sidebar-link">
                    <i class="fa-solid fa-car"></i>
                    <span class="nav-text">My Garage</span>
                </a>
            </li>
            <li>
                <a href="bookings.php" class="sidebar-link">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span class="nav-text">Service History</span>
                </a>
            </li>
            <li>
                <a href="../book-service.php" class="sidebar-link text-danger">
                    <i class="fa-solid fa-calendar-plus"></i>
                    <span class="nav-text">Book New Pickup</span>
                </a>
            </li>
            <li>
                <a href="../track-service.php" class="sidebar-link">
                    <i class="fa-solid fa-crosshairs"></i>
                    <span class="nav-text">Live Vehicle Tracker</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content Area -->
    <main class="dashboard-content">
        <!-- Top Greeting Bar -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <h2 class="fw-bold text-white mb-1">Hello, <?= htmlspecialchars($currentUser['name']) ?>!</h2>
                <p class="text-muted small mb-0">Welcome to your personal car servicing hub. Manage vehicles, track servicing and approve estimates.</p>
            </div>
            <div class="mt-3 mt-md-0 d-flex gap-2">
                <a href="../book-service.php" class="btn btn-red px-3">
                    <i class="fa-solid fa-plus me-1"></i> Book Service
                </a>
                <a href="vehicles.php" class="btn btn-outline-light px-3">
                    <i class="fa-solid fa-car me-1"></i> Add Vehicle
                </a>
            </div>
        </div>

        <!-- 4 KPI Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div>
                        <div class="stat-label">My Vehicles</div>
                        <div class="stat-val"><?= $vehiclesCount ?></div>
                    </div>
                    <div class="stat-icon bg-danger bg-opacity-25 text-danger">
                        <i class="fa-solid fa-car"></i>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div>
                        <div class="stat-label">Active Servicing</div>
                        <div class="stat-val text-warning"><?= $activeBookingsCount ?></div>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-25 text-warning">
                        <i class="fa-solid fa-gears"></i>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div>
                        <div class="stat-label">Completed Services</div>
                        <div class="stat-val text-success"><?= $completedCount ?></div>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-25 text-success">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div>
                        <div class="stat-label">Total Invested</div>
                        <div class="stat-val fs-3"><?= format_currency($totalSpent) ?></div>
                    </div>
                    <div class="stat-icon bg-info bg-opacity-25 text-info">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Service Tracker Highlight (if active) -->
        <?php if ($latestBooking && $latestBooking['current_status'] !== 'Delivered'): ?>
            <?php 
                $currStatus = $latestBooking['current_status'];
                $pct = get_status_progress($currStatus);
            ?>
            <div class="card-dark p-4 mb-4 border-danger border-opacity-50">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
                    <div>
                        <span class="badge bg-danger mb-2">LIVE SERVICE TRACKING</span>
                        <h4 class="fw-bold text-white mb-1"><?= htmlspecialchars($latestBooking['make'] . ' ' . $latestBooking['model']) ?> (<?= htmlspecialchars($latestBooking['reg_number']) ?>)</h4>
                        <div class="text-muted small">Booking: <strong><?= htmlspecialchars($latestBooking['booking_number']) ?></strong> &bull; Scheduled Pickup: <?= format_date($latestBooking['preferred_pickup_date'], 'd M Y') ?></div>
                    </div>
                    <div class="mt-3 mt-md-0 text-md-end">
                        <?= get_status_badge($currStatus) ?>
                        <div class="mt-2">
                            <a href="../track-service.php?booking=<?= htmlspecialchars($latestBooking['booking_number']) ?>" class="btn btn-red btn-sm">
                                <i class="fa-solid fa-radar me-1"></i> Open Full Telematics
                            </a>
                        </div>
                    </div>
                </div>

                <div class="progress" style="height: 10px; background-color: #1e2230;">
                    <div class="progress-bar bg-danger progress-bar-striped progress-bar-animated" style="width: <?= $pct ?>%;"></div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Recent Bookings Table -->
        <div class="card-dark p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-white mb-0"><i class="fa-solid fa-clock-rotate-left text-danger me-2"></i>My Bookings</h5>
                <a href="bookings.php" class="small text-danger">View All</a>
            </div>

            <?php if (!empty($allBookings)): ?>
                <div class="table-responsive">
                    <table class="table table-dark-custom mb-0">
                        <thead>
                            <tr>
                                <th>Booking Ref</th>
                                <th>Vehicle</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Amount</th>
                                <th>Payment</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allBookings as $b): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($b['booking_number']) ?></strong></td>
                                    <td>
                                        <div class="text-white fw-semibold"><?= htmlspecialchars($b['make'] . ' ' . $b['model']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($b['reg_number']) ?></small>
                                    </td>
                                    <td><?= format_date($b['booking_date'], 'd M Y') ?></td>
                                    <td><?= get_status_badge($b['current_status']) ?></td>
                                    <td class="fw-bold text-white"><?= format_currency($b['total_amount']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $b['payment_status'] === 'Paid' ? 'success' : 'warning' ?>">
                                            <?= htmlspecialchars($b['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="../track-service.php?booking=<?= htmlspecialchars($b['booking_number']) ?>" class="btn btn-dark-surface btn-sm" title="Track Live">
                                            <i class="fa-solid fa-radar text-danger"></i>
                                        </a>
                                        <?php if ($b['payment_status'] !== 'Paid'): ?>
                                            <a href="../payment.php?booking=<?= htmlspecialchars($b['booking_number']) ?>" class="btn btn-red btn-sm" title="Pay Now">
                                                <i class="fa-solid fa-credit-card"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4 text-muted">
                    <i class="fa-solid fa-calendar-xmark fa-3x mb-2"></i>
                    <p class="mb-3">You have not scheduled any car services yet.</p>
                    <a href="../book-service.php" class="btn btn-red btn-sm">Schedule Your First Pickup</a>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
