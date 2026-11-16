<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * Customer Garage & Vehicle Management
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

require_role('customer', 'login.php');

$currentUser = current_user();
$userId = $currentUser['id'];

// Fetch customer ID
$customer = Database::fetchOne("SELECT id FROM customers WHERE user_id = ? LIMIT 1", [$userId]);
if (!$customer) {
    Database::execute("INSERT INTO customers (user_id) VALUES (?)", [$userId]);
    $customer = Database::fetchOne("SELECT id FROM customers WHERE user_id = ? LIMIT 1", [$userId]);
}
$customerId = $customer['id'];

$error = '';
$success = '';

// Handle Add Vehicle Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_vehicle') {
        $make = trim($_POST['make'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $year = (int)($_POST['year'] ?? date('Y'));
        $regNumber = strtoupper(trim($_POST['reg_number'] ?? ''));
        $fuelType = trim($_POST['fuel_type'] ?? 'Petrol');
        $transmission = trim($_POST['transmission'] ?? 'Manual');
        $color = trim($_POST['color'] ?? '');
        $mileage = (int)($_POST['mileage'] ?? 0);
        $rcNumber = trim($_POST['rc_number'] ?? '');

        if (empty($make) || empty($model) || empty($regNumber)) {
            $error = 'Make, Model, and Registration Number are required.';
        } else {
            try {
                // Check if vehicle registration number already registered
                $existing = Database::fetchOne("SELECT id FROM vehicles WHERE reg_number = ? LIMIT 1", [$regNumber]);
                if ($existing) {
                    $error = 'A vehicle with registration number ' . htmlspecialchars($regNumber) . ' is already registered.';
                } else {
                    Database::execute("
                        INSERT INTO vehicles (customer_id, make, model, year, reg_number, fuel_type, transmission, color, mileage, rc_number, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ", [$customerId, $make, $model, $year, $regNumber, $fuelType, $transmission, $color, $mileage, $rcNumber]);

                    set_flash('success', "Vehicle {$make} {$model} ({$regNumber}) added to your garage successfully!");
                    header('Location: ' . BASE_URL . '/customer/vehicles.php');
                    exit;
                }
            } catch (Exception $e) {
                $error = 'Failed to register vehicle: ' . $e->getMessage();
            }
        }
    } elseif ($_POST['action'] === 'delete_vehicle') {
        $vehId = (int)$_POST['vehicle_id'];
        try {
            Database::execute("DELETE FROM vehicles WHERE id = ? AND customer_id = ?", [$vehId, $customerId]);
            set_flash('success', 'Vehicle removed from your garage.');
            header('Location: ' . BASE_URL . '/customer/vehicles.php');
            exit;
        } catch (Exception $e) {
            $error = 'Cannot delete vehicle associated with active servicing records.';
        }
    }
}

// Fetch all customer vehicles
$vehicles = Database::fetchAll("SELECT * FROM vehicles WHERE customer_id = ? ORDER BY id DESC", [$customerId]);

$pageTitle = 'My Garage & Vehicles';
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
            <li><a href="index.php" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i><span class="nav-text">Dashboard</span></a></li>
            <li><a href="vehicles.php" class="sidebar-link active"><i class="fa-solid fa-car"></i><span class="nav-text">My Garage</span></a></li>
            <li><a href="bookings.php" class="sidebar-link"><i class="fa-solid fa-calendar-check"></i><span class="nav-text">Service History</span></a></li>
            <li><a href="../book-service.php" class="sidebar-link text-danger"><i class="fa-solid fa-calendar-plus"></i><span class="nav-text">Book New Pickup</span></a></li>
            <li><a href="../track-service.php" class="sidebar-link"><i class="fa-solid fa-crosshairs"></i><span class="nav-text">Live Vehicle Tracker</span></a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-content">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <h2 class="fw-bold text-white mb-1"><i class="fa-solid fa-car text-danger me-2"></i>My Garage</h2>
                <p class="text-muted small mb-0">Manage your saved vehicles, track odometer readings, and schedule periodic services.</p>
            </div>
            <button type="button" class="btn btn-red mt-3 mt-md-0 px-4" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                <i class="fa-solid fa-plus me-1"></i> Add New Vehicle
            </button>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <?php if (!empty($vehicles)): ?>
                <?php foreach ($vehicles as $v): ?>
                    <div class="col-lg-6">
                        <div class="card-dark p-4 h-100 position-relative">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h4 class="fw-bold text-white mb-1"><?= htmlspecialchars($v['make'] . ' ' . $v['model']) ?></h4>
                                    <span class="badge bg-danger fs-6"><?= htmlspecialchars($v['reg_number']) ?></span>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-dark-surface btn-sm p-2 rounded-circle" type="button" data-bs-toggle="dropdown">
                                        <i class="fa-solid fa-ellipsis-vertical text-muted"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item text-danger small" href="#" onclick="if(confirm('Are you sure you want to remove this vehicle?')) { document.getElementById('del-form-<?= $v['id'] ?>').submit(); } return false;">
                                                <i class="fa-solid fa-trash me-2"></i> Remove Vehicle
                                            </a>
                                        </li>
                                    </ul>
                                    <form id="del-form-<?= $v['id'] ?>" method="POST" action="vehicles.php" style="display:none;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_vehicle">
                                        <input type="hidden" name="vehicle_id" value="<?= $v['id'] ?>">
                                    </form>
                                </div>
                            </div>

                            <div class="row g-2 small text-muted mb-4 bg-surface p-3 rounded border border-dark">
                                <div class="col-6"><i class="fa-solid fa-calendar me-1 text-danger"></i>Year: <strong class="text-white"><?= $v['year'] ?></strong></div>
                                <div class="col-6"><i class="fa-solid fa-gas-pump me-1 text-danger"></i>Fuel: <strong class="text-white"><?= htmlspecialchars($v['fuel_type']) ?></strong></div>
                                <div class="col-6"><i class="fa-solid fa-gears me-1 text-danger"></i>Transmission: <strong class="text-white"><?= htmlspecialchars($v['transmission']) ?></strong></div>
                                <div class="col-6"><i class="fa-solid fa-palette me-1 text-danger"></i>Color: <strong class="text-white"><?= htmlspecialchars($v['color'] ?: 'Standard') ?></strong></div>
                                <div class="col-6"><i class="fa-solid fa-gauge me-1 text-danger"></i>Odometer: <strong class="text-white"><?= number_format($v['mileage']) ?> KM</strong></div>
                                <div class="col-6"><i class="fa-solid fa-id-card me-1 text-danger"></i>RC: <strong class="text-white"><?= htmlspecialchars($v['rc_number'] ?: 'Registered') ?></strong></div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-dark mt-auto">
                                <a href="../track-service.php?booking=<?= urlencode($v['reg_number']) ?>" class="btn btn-outline-light btn-sm">
                                    <i class="fa-solid fa-clock-rotate-left me-1"></i> Track History
                                </a>
                                <a href="../book-service.php?vehicle_id=<?= $v['id'] ?>" class="btn btn-red btn-sm">
                                    <i class="fa-solid fa-calendar-plus me-1"></i> Schedule Service
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5 card-dark">
                    <i class="fa-solid fa-car-side fa-4x text-muted mb-3"></i>
                    <h4 class="text-white fw-bold mb-2">No Vehicles in Garage</h4>
                    <p class="text-muted mb-4">Add your car to quickly book periodic services and track maintenance history.</p>
                    <button type="button" class="btn btn-red px-4" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                        <i class="fa-solid fa-plus me-1"></i> Add My Car
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Modal: Add New Vehicle -->
<div class="modal fade" id="addVehicleModal" tabindex="-1" aria-labelledby="addVehicleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content card-dark border-secondary">
            <div class="modal-header border-dark">
                <h5 class="modal-title text-white fw-bold" id="addVehicleModalLabel"><i class="fa-solid fa-car text-danger me-2"></i>Add Vehicle to Garage</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="vehicles.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_vehicle">
                <div class="modal-body text-light">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Make / Brand *</label>
                            <input type="text" name="make" class="form-control form-control-dark" placeholder="e.g. Hyundai, Honda" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Model *</label>
                            <input type="text" name="model" class="form-control form-control-dark" placeholder="e.g. Creta SX, City ZX" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Manufacturing Year *</label>
                            <input type="number" name="year" class="form-control form-control-dark" min="2000" max="<?= date('Y') + 1 ?>" value="<?= date('Y') - 1 ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Registration Number *</label>
                            <input type="text" name="reg_number" class="form-control form-control-dark" placeholder="e.g. DL 03 CS 4521" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Fuel Type</label>
                            <select name="fuel_type" class="form-select form-select-dark">
                                <option value="Petrol">Petrol</option>
                                <option value="Diesel">Diesel</option>
                                <option value="Electric">Electric</option>
                                <option value="Hybrid">Hybrid</option>
                                <option value="CNG">CNG</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Transmission</label>
                            <select name="transmission" class="form-select form-select-dark">
                                <option value="Manual">Manual</option>
                                <option value="Automatic">Automatic</option>
                                <option value="CVT">CVT</option>
                                <option value="DCT">DCT</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Color</label>
                            <input type="text" name="color" class="form-control form-control-dark" placeholder="e.g. Phantom Black">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Current Odometer (KM)</label>
                            <input type="number" name="mileage" class="form-control form-control-dark" placeholder="e.g. 32000">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">RC / Chassis Number (Optional)</label>
                            <input type="text" name="rc_number" class="form-control form-control-dark" placeholder="e.g. RC-HR26-2021-0099">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-dark">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-red px-4"><i class="fa-solid fa-plus me-1"></i>Save Vehicle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
