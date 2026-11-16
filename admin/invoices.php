<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * Admin Invoice & Financial Management
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

require_role('admin', 'login.php');

$currentUser = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_paid') {
    $invId = (int)$_POST['invoice_id'];
    Database::execute("UPDATE invoices SET status = 'paid' WHERE id = ?", [$invId]);
    $inv = Database::fetchOne("SELECT booking_id FROM invoices WHERE id = ?", [$invId]);
    if ($inv) {
        Database::execute("UPDATE bookings SET payment_status = 'Paid' WHERE id = ?", [$inv['booking_id']]);
    }
    set_flash('success', 'Invoice marked as paid.');
    header('Location: ' . BASE_URL . '/admin/invoices.php');
    exit;
}

$invoices = Database::fetchAll("
    SELECT inv.*, u.name as customer_name, u.phone as customer_phone,
           v.make, v.model, v.reg_number, b.booking_number
    FROM invoices inv
    JOIN bookings b ON inv.booking_id = b.id
    JOIN customers c ON inv.customer_id = c.id
    JOIN users u ON c.user_id = u.id
    JOIN vehicles v ON b.vehicle_id = v.id
    ORDER BY inv.id DESC
");

$pageTitle = 'Invoices & Billing Hub';
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
            <li><a href="customers.php" class="sidebar-link"><i class="fa-solid fa-users"></i><span class="nav-text">Customers</span></a></li>
            <li><a href="employees.php" class="sidebar-link"><i class="fa-solid fa-id-badge"></i><span class="nav-text">Staff & Drivers</span></a></li>
            <li><a href="services.php" class="sidebar-link"><i class="fa-solid fa-tools"></i><span class="nav-text">Services Catalog</span></a></li>
            <li><a href="invoices.php" class="sidebar-link active"><i class="fa-solid fa-file-invoice-dollar"></i><span class="nav-text">Invoices & Billing</span></a></li>
        </ul>
    </aside>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-white mb-1"><i class="fa-solid fa-file-invoice-dollar text-danger me-2"></i>Invoices & Billing</h2>
                <p class="text-muted small mb-0">GST tax invoices, payment settlement status, and financial records.</p>
            </div>
        </div>

        <div class="card-dark p-4">
            <div class="table-responsive">
                <table class="table table-dark-custom mb-0">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Booking Ref</th>
                            <th>Customer</th>
                            <th>Vehicle</th>
                            <th>Taxable</th>
                            <th>GST (18%)</th>
                            <th>Final Bill</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $inv): ?>
                            <tr>
                                <td><strong class="text-white"><?= htmlspecialchars($inv['invoice_number']) ?></strong></td>
                                <td><?= htmlspecialchars($inv['booking_number']) ?></td>
                                <td>
                                    <div class="text-white fw-bold"><?= htmlspecialchars($inv['customer_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($inv['customer_phone']) ?></small>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($inv['make'] . ' ' . $inv['model']) ?></div>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($inv['reg_number']) ?></span>
                                </td>
                                <td><?= format_currency($inv['subtotal']) ?></td>
                                <td><?= format_currency($inv['tax_amount']) ?></td>
                                <td class="fw-bold text-white fs-6"><?= format_currency($inv['final_amount']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $inv['status'] === 'paid' ? 'success' : 'danger' ?>">
                                        <?= strtoupper($inv['status']) ?>
                                    </span>
                                </td>
                                <td><?= format_date($inv['issued_at'], 'd M Y') ?></td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="../invoice.php?inv=<?= htmlspecialchars($inv['invoice_number']) ?>" class="btn btn-dark-surface btn-sm" target="_blank" title="View Print Invoice">
                                            <i class="fa-solid fa-eye text-info"></i>
                                        </a>
                                        <?php if ($inv['status'] !== 'paid'): ?>
                                            <form method="POST" action="invoices.php" style="display:inline;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="mark_paid">
                                                <input type="hidden" name="invoice_id" value="<?= $inv['id'] ?>">
                                                <button type="submit" class="btn btn-success btn-sm" title="Mark Paid">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
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
