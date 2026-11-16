<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * Service Catalog Management
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

require_role('admin', 'login.php');

$currentUser = current_user();
$error = '';
$success = '';

// Handle update or add service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'edit_service') {
        $id = (int)$_POST['service_id'];
        $price = (float)$_POST['base_price'];
        $time = trim($_POST['estimated_time'] ?? '2-3 Hours');
        $desc = trim($_POST['description'] ?? '');

        try {
            Database::execute("
                UPDATE services SET base_price = ?, estimated_time = ?, description = ? WHERE id = ?
            ", [$price, $time, $desc, $id]);
            set_flash('success', 'Service pricing and details updated successfully.');
            header('Location: ' . BASE_URL . '/admin/services.php');
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$services = Database::fetchAll("SELECT * FROM services ORDER BY id ASC");

$pageTitle = 'Manage Services Catalog';
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
            <li><a href="services.php" class="sidebar-link active"><i class="fa-solid fa-tools"></i><span class="nav-text">Services Catalog</span></a></li>
            <li><a href="invoices.php" class="sidebar-link"><i class="fa-solid fa-file-invoice-dollar"></i><span class="nav-text">Invoices & Billing</span></a></li>
        </ul>
    </aside>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-white mb-1"><i class="fa-solid fa-tools text-danger me-2"></i>Service Packages & Pricing</h2>
                <p class="text-muted small mb-0">Manage service base rates, duration benchmarks, and active catalog visibility.</p>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card-dark p-4">
            <div class="table-responsive">
                <table class="table table-dark-custom mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Service Name</th>
                            <th>Category</th>
                            <th>Duration</th>
                            <th>Base Rate</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $s): ?>
                            <tr>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($s['code']) ?></span></td>
                                <td>
                                    <div class="text-white fw-bold"><i class="fa-solid <?= htmlspecialchars($s['icon'] ?? 'fa-wrench') ?> text-danger me-2"></i><?= htmlspecialchars($s['name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars(substr($s['description'], 0, 75)) ?>...</small>
                                </td>
                                <td><?= htmlspecialchars($s['category']) ?></td>
                                <td><span class="text-light small"><i class="fa-solid fa-clock me-1 text-muted"></i><?= htmlspecialchars($s['estimated_time']) ?></span></td>
                                <td class="fw-bold text-white fs-6"><?= format_currency($s['base_price']) ?></td>
                                <td>
                                    <span class="badge bg-success bg-opacity-25 text-success">Active</span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-dark-surface btn-sm" data-bs-toggle="modal" data-bs-target="#editSrvModal<?= $s['id'] ?>">
                                        <i class="fa-solid fa-pen-to-square text-danger"></i> Edit
                                    </button>
                                </td>
                            </tr>

                            <!-- Edit Service Modal -->
                            <div class="modal fade" id="editSrvModal<?= $s['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content card-dark border-secondary">
                                        <div class="modal-header border-dark">
                                            <h5 class="modal-title text-white fw-bold">Edit Service: <?= htmlspecialchars($s['name']) ?></h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="services.php">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="edit_service">
                                            <input type="hidden" name="service_id" value="<?= $s['id'] ?>">
                                            <div class="modal-body text-light">
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">Base Price (INR):</label>
                                                    <input type="number" step="0.01" name="base_price" class="form-control form-control-dark" value="<?= $s['base_price'] ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">Estimated Turnaround:</label>
                                                    <input type="text" name="estimated_time" class="form-control form-control-dark" value="<?= htmlspecialchars($s['estimated_time']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">Package Description:</label>
                                                    <textarea name="description" class="form-control form-control-dark" rows="3"><?= htmlspecialchars($s['description']) ?></textarea>
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
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
