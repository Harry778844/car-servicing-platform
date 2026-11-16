<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * Employee Management Module (Pickup Drivers & Workshop Technicians)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

require_role('admin', 'login.php');

$currentUser = current_user();
$error = '';
$success = '';

// Handle add employee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_employee') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $department = $_POST['department'] ?? 'pickup_delivery';
        $designation = trim($_POST['designation'] ?? '');
        $empCode = strtoupper(trim($_POST['employee_code'] ?? ''));
        $license = trim($_POST['license_number'] ?? '');
        $role = ($department === 'pickup_delivery') ? 'pickup' : 'technician';

        try {
            $existing = Database::fetchOne("SELECT id FROM users WHERE email = ? LIMIT 1", [$email]);
            if ($existing) {
                $error = 'Email is already registered.';
            } else {
                $hash = password_hash('Password@123', PASSWORD_BCRYPT);
                Database::execute(
                    "INSERT INTO users (name, email, phone, password_hash, role, status) VALUES (?, ?, ?, ?, ?, 'active')",
                    [$name, $email, $phone, $hash, $role]
                );
                $uId = Database::lastInsertId();

                Database::execute("
                    INSERT INTO employees (user_id, employee_code, designation, department, license_number, joining_date, status)
                    VALUES (?, ?, ?, ?, ?, CURDATE(), 'available')
                ", [$uId, $empCode, $designation, $department, $license]);

                set_flash('success', "Employee {$name} ({$empCode}) onboarded successfully!");
                header('Location: ' . BASE_URL . '/admin/employees.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Employee creation failed: ' . $e->getMessage();
        }
    }
}

$employees = Database::fetchAll("
    SELECT e.*, u.name, u.email, u.phone, u.role
    FROM employees e
    JOIN users u ON e.user_id = u.id
    ORDER BY e.department DESC, e.id ASC
");

$pageTitle = 'Workshop Staff & Drivers';
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
            <li><a href="employees.php" class="sidebar-link active"><i class="fa-solid fa-id-badge"></i><span class="nav-text">Staff & Drivers</span></a></li>
            <li><a href="services.php" class="sidebar-link"><i class="fa-solid fa-tools"></i><span class="nav-text">Services Catalog</span></a></li>
            <li><a href="invoices.php" class="sidebar-link"><i class="fa-solid fa-file-invoice-dollar"></i><span class="nav-text">Invoices & Billing</span></a></li>
        </ul>
    </aside>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-white mb-1"><i class="fa-solid fa-id-badge text-danger me-2"></i>Staff & Fleet Specialists</h2>
                <p class="text-muted small mb-0">Certified workshop mechanics, diagnostic technicians, and doorstep pickup drivers.</p>
            </div>
            <button type="button" class="btn btn-red px-4" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                <i class="fa-solid fa-user-plus me-1"></i> Add Employee
            </button>
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
                            <th>Staff Member</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>Contact Phone</th>
                            <th>License / Certification</th>
                            <th>Status</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $emp): ?>
                            <tr>
                                <td><span class="badge bg-danger"><?= htmlspecialchars($emp['employee_code']) ?></span></td>
                                <td>
                                    <strong class="text-white"><?= htmlspecialchars($emp['name']) ?></strong>
                                    <div class="text-muted small"><?= htmlspecialchars($emp['email']) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $emp['department'] === 'workshop_technician' ? 'info' : 'primary' ?>">
                                        <?= $emp['department'] === 'workshop_technician' ? 'Workshop Mechanic' : 'Doorstep Pickup' ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($emp['designation']) ?></td>
                                <td><?= htmlspecialchars($emp['phone']) ?></td>
                                <td><small class="text-muted"><?= htmlspecialchars($emp['license_number'] ?: 'Standard Verified') ?></small></td>
                                <td>
                                    <span class="badge bg-success bg-opacity-25 text-success">
                                        <?= htmlspecialchars(ucfirst($emp['status'])) ?>
                                    </span>
                                </td>
                                <td><?= format_date($emp['joining_date'], 'd M Y') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Modal: Add Employee -->
<div class="modal fade" id="addStaffModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content card-dark border-secondary">
            <div class="modal-header border-dark">
                <h5 class="modal-title text-white fw-bold">Onboard New Employee</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="employees.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_employee">
                <div class="modal-body text-light">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Employee Code *</label>
                            <input type="text" name="employee_code" class="form-control form-control-dark" placeholder="e.g. EMP-TECH-205" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Full Name *</label>
                            <input type="text" name="name" class="form-control form-control-dark" placeholder="e.g. Ramesh Kumar" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Email Address *</label>
                            <input type="email" name="email" class="form-control form-control-dark" placeholder="ramesh@autopro.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Phone Number *</label>
                            <input type="tel" name="phone" class="form-control form-control-dark" placeholder="+91 98765 00010" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Department *</label>
                            <select name="department" class="form-select form-select-dark" required>
                                <option value="pickup_delivery">Doorstep Pickup & Delivery</option>
                                <option value="workshop_technician">Workshop Service Mechanic</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Designation *</label>
                            <input type="text" name="designation" class="form-control form-control-dark" placeholder="e.g. Senior Diagnostic Tech" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">License / Certification Number</label>
                            <input type="text" name="license_number" class="form-control form-control-dark" placeholder="DL or ASE Certification number">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-dark">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-red px-4">Create Staff Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
