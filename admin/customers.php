<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * Admin Customer Management Module
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

require_role('admin', 'login.php');

$currentUser = current_user();

$customers = Database::fetchAll("
    SELECT c.*, u.name, u.email, u.phone, u.status as user_status, u.created_at as joined_date,
           (SELECT COUNT(*) FROM vehicles v WHERE v.customer_id = c.id) as vehicle_count,
           (SELECT COUNT(*) FROM bookings b WHERE b.customer_id = c.id) as booking_count
    FROM customers c
    JOIN users u ON c.user_id = u.id
    ORDER BY c.id DESC
");

$pageTitle = 'Customer Directory';
$extraCss = 'assets/css/dashboard.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <aside class="dashboard-sidebar">
        <div class="sidebar-user">
            <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center fw-bold sidebar-avatar">AD</div>
            <div class="user-info">
                <div class="fw-bold text-white small"><?= htmlspecialchars($currentUser['name']) ?></div>
                <span class="badge bg-danger text-uppercase" style="font-size:0.65rem;">Administrator</span>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li><a href="index.php" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i><span class="nav-text">Command Center</span></a></li>
            <li><a href="bookings.php" class="sidebar-link"><i class="fa-solid fa-calendar-days"></i><span class="nav-text">All Bookings</span></a></li>
            <li><a href="customers.php" class="sidebar-link active"><i class="fa-solid fa-users"></i><span class="nav-text">Customers</span></a></li>
            <li><a href="employees.php" class="sidebar-link"><i class="fa-solid fa-id-badge"></i><span class="nav-text">Staff & Drivers</span></a></li>
            <li><a href="services.php" class="sidebar-link"><i class="fa-solid fa-tools"></i><span class="nav-text">Services Catalog</span></a></li>
            <li><a href="invoices.php" class="sidebar-link"><i class="fa-solid fa-file-invoice-dollar"></i><span class="nav-text">Invoices & Billing</span></a></li>
        </ul>
    </aside>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-white mb-1"><i class="fa-solid fa-users text-danger me-2"></i>Customer Directory</h2>
                <p class="text-muted small mb-0">Registered vehicle owners, contact details, garage size and booking activity.</p>
            </div>
        </div>

        <div class="card-dark p-4">
            <div class="table-responsive">
                <table class="table table-dark-custom mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Customer Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Location / City</th>
                            <th>Vehicles in Garage</th>
                            <th>Bookings Placed</th>
                            <th>Status</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $c): ?>
                            <tr>
                                <td><?= $c['id'] ?></td>
                                <td><strong class="text-white"><?= htmlspecialchars($c['name']) ?></strong></td>
                                <td><?= htmlspecialchars($c['email']) ?></td>
                                <td><span class="text-danger fw-bold"><?= htmlspecialchars($c['phone']) ?></span></td>
                                <td><?= htmlspecialchars($c['city'] ?: 'Gurugram') ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?= $c['vehicle_count'] ?> Cars</span>
                                </td>
                                <td>
                                    <span class="badge bg-danger"><?= $c['booking_count'] ?> Orders</span>
                                </td>
                                <td>
                                    <span class="badge bg-success bg-opacity-25 text-success">Active</span>
                                </td>
                                <td><?= format_date($c['joined_date'], 'd M Y') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
