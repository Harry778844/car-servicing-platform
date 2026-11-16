<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * Multi-Point Digital Vehicle Inspection Checklist & Photos
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

require_role('technician', 'login.php');

$currentUser = current_user();
$userId = $currentUser['id'];
$employee = Database::fetchOne("SELECT * FROM employees WHERE user_id = ? LIMIT 1", [$userId]);
$employeeId = $employee['id'] ?? 0;

$bookingId = (int)($_GET['booking_id'] ?? 0);
$booking = Database::fetchOne("
    SELECT b.*, v.make, v.model, v.reg_number, v.year, u.name as customer_name
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    JOIN customers c ON b.customer_id = c.id
    JOIN users u ON c.user_id = u.id
    WHERE b.id = ? LIMIT 1
", [$bookingId]);

if (!$booking) {
    die("Invalid booking ID.");
}

$error = '';
$success = '';

// Check if inspection already exists
$existingInspection = Database::fetchOne("SELECT * FROM vehicle_inspections WHERE booking_id = ? LIMIT 1", [$bookingId]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_inspection') {
    $fuelLevel = $_POST['fuel_level'] ?? 'Half Tank';
    $odometer = (int)($_POST['odometer_reading'] ?? 0);
    $exterior = $_POST['exterior_condition'] ?? 'Good';
    $interior = $_POST['interior_condition'] ?? 'Clean';
    $tyre = $_POST['tyre_condition'] ?? 'Good';
    $battery = $_POST['battery_voltage'] ?? '12.6V (Normal)';
    $engineOil = $_POST['engine_oil_status'] ?? 'Good';
    $brakeFluid = $_POST['brake_fluid_status'] ?? 'Good';
    $coolant = $_POST['coolant_level'] ?? 'Adequate';
    $scratches = trim($_POST['scratches_dents_notes'] ?? '');
    $remarks = trim($_POST['general_remarks'] ?? '');

    try {
        if ($existingInspection) {
            Database::execute("
                UPDATE vehicle_inspections 
                SET fuel_level = ?, odometer_reading = ?, exterior_condition = ?, interior_condition = ?, tyre_condition = ?,
                    battery_voltage = ?, engine_oil_status = ?, brake_fluid_status = ?, coolant_level = ?,
                    scratches_dents_notes = ?, general_remarks = ?
                WHERE id = ?
            ", [$fuelLevel, $odometer, $exterior, $interior, $tyre, $battery, $engineOil, $brakeFluid, $coolant, $scratches, $remarks, $existingInspection['id']]);
            $inspectionId = $existingInspection['id'];
        } else {
            Database::execute("
                INSERT INTO vehicle_inspections (
                    booking_id, technician_id, fuel_level, odometer_reading, exterior_condition, interior_condition, tyre_condition,
                    battery_voltage, engine_oil_status, brake_fluid_status, coolant_level, scratches_dents_notes, general_remarks, inspection_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ", [$bookingId, $employeeId, $fuelLevel, $odometer, $exterior, $interior, $tyre, $battery, $engineOil, $brakeFluid, $coolant, $scratches, $remarks]);
            $inspectionId = Database::lastInsertId();

            // Insert default inspection photos
            Database::execute("
                INSERT INTO inspection_photos (inspection_id, photo_url, photo_type, caption, uploaded_by)
                VALUES (?, 'assets/img/demo/car-front.jpg', 'exterior', 'Front view inspection', ?)
            ", [$inspectionId, $userId]);
        }

        log_status_history($bookingId, 'Inspection in Progress', 'Multi-point technical inspection report completed by technician.', $userId);

        set_flash('success', 'Digital vehicle inspection report saved successfully!');
        header('Location: ' . BASE_URL . '/technician/estimate.php?booking_id=' . $bookingId);
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$pageTitle = '60-Point Vehicle Inspection Checklist';
$extraCss = 'assets/css/dashboard.css';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <aside class="dashboard-sidebar">
        <div class="sidebar-user">
            <div class="rounded-circle bg-info text-dark d-flex align-items-center justify-content-center fw-bold sidebar-avatar">
                <i class="fa-solid fa-screwdriver-wrench"></i>
            </div>
            <div class="user-info">
                <div class="fw-bold text-white small"><?= htmlspecialchars($currentUser['name']) ?></div>
                <span class="badge bg-info text-dark text-uppercase" style="font-size:0.65rem;">Technician</span>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li><a href="index.php" class="sidebar-link"><i class="fa-solid fa-screwdriver-wrench"></i><span class="nav-text">Workshop Bays</span></a></li>
            <li><a href="../track-service.php" class="sidebar-link"><i class="fa-solid fa-crosshairs"></i><span class="nav-text">Live Tracker</span></a></li>
        </ul>
    </aside>

    <main class="dashboard-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="index.php" class="small text-danger"><i class="fa-solid fa-arrow-left me-1"></i> Back to Bays</a>
                <h2 class="fw-bold text-white mb-1"><i class="fa-solid fa-clipboard-check text-danger me-2"></i>Multi-Point Inspection</h2>
                <p class="text-muted small mb-0">Record vehicle vitals, diagnostic sensor readings, fluid health, and exterior condition.</p>
            </div>
            <div class="text-end">
                <span class="badge bg-danger fs-6"><?= htmlspecialchars($booking['reg_number']) ?></span>
                <div class="text-white small fw-bold mt-1"><?= htmlspecialchars($booking['make'] . ' ' . $booking['model']) ?></div>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="inspection.php?booking_id=<?= $bookingId ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_inspection">

            <div class="card-dark p-4 p-md-5 mb-4">
                <h4 class="fw-bold text-white mb-3 border-bottom border-dark pb-2">
                    <i class="fa-solid fa-gauge-high text-danger me-2"></i>1. Odometer & Fuel Level
                </h4>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Odometer Reading (KM) *</label>
                        <input type="number" name="odometer_reading" class="form-control form-control-dark" value="<?= $existingInspection['odometer_reading'] ?? 35000 ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Fuel Level Gauge *</label>
                        <select name="fuel_level" class="form-select form-select-dark">
                            <option value="100% (Full)" <?= ($existingInspection['fuel_level'] ?? '') === '100% (Full)' ? 'selected' : '' ?>>100% (Full Tank)</option>
                            <option value="75% (3/4 Tank)" <?= ($existingInspection['fuel_level'] ?? '') === '75% (3/4 Tank)' ? 'selected' : '' ?>>75% (3/4 Tank)</option>
                            <option value="50% (Half Tank)" <?= ($existingInspection['fuel_level'] ?? '') === '50% (Half Tank)' ? 'selected' : '' ?>>50% (Half Tank)</option>
                            <option value="25% (1/4 Tank)" <?= ($existingInspection['fuel_level'] ?? '') === '25% (1/4 Tank)' ? 'selected' : '' ?>>25% (1/4 Tank)</option>
                            <option value="Reserve / Low" <?= ($existingInspection['fuel_level'] ?? '') === 'Reserve / Low' ? 'selected' : '' ?>>Reserve / Low</option>
                        </select>
                    </div>
                </div>

                <h4 class="fw-bold text-white mb-3 border-bottom border-dark pb-2">
                    <i class="fa-solid fa-car-burst text-danger me-2"></i>2. Bodywork, Tyres & Battery
                </h4>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Exterior Body Condition</label>
                        <select name="exterior_condition" class="form-select form-select-dark">
                            <option value="Good" <?= ($existingInspection['exterior_condition'] ?? '') === 'Good' ? 'selected' : '' ?>>Good (No Major Scratches)</option>
                            <option value="Scratched" <?= ($existingInspection['exterior_condition'] ?? '') === 'Scratched' ? 'selected' : '' ?>>Scratched</option>
                            <option value="Dented" <?= ($existingInspection['exterior_condition'] ?? '') === 'Dented' ? 'selected' : '' ?>>Dented</option>
                            <option value="Damaged" <?= ($existingInspection['exterior_condition'] ?? '') === 'Damaged' ? 'selected' : '' ?>>Damaged Panel</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Tyre Tread Health</label>
                        <select name="tyre_condition" class="form-select form-select-dark">
                            <option value="Good" <?= ($existingInspection['tyre_condition'] ?? '') === 'Good' ? 'selected' : '' ?>>Good (&gt; 4mm Tread)</option>
                            <option value="Worn Out" <?= ($existingInspection['tyre_condition'] ?? '') === 'Worn Out' ? 'selected' : '' ?>>Worn Out (2-3mm)</option>
                            <option value="Needs Replacement" <?= ($existingInspection['tyre_condition'] ?? '') === 'Needs Replacement' ? 'selected' : '' ?>>Needs Replacement (&lt; 2mm)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Battery Terminal & Voltage</label>
                        <select name="battery_voltage" class="form-select form-select-dark">
                            <option value="12.6V (Normal)" <?= ($existingInspection['battery_voltage'] ?? '') === '12.6V (Normal)' ? 'selected' : '' ?>>12.6V (Healthy / Normal)</option>
                            <option value="12.2V (Weak)" <?= ($existingInspection['battery_voltage'] ?? '') === '12.2V (Weak)' ? 'selected' : '' ?>>12.2V (Weak / Charging Needed)</option>
                            <option value="< 11.8V (Dead)" <?= ($existingInspection['battery_voltage'] ?? '') === '< 11.8V (Dead)' ? 'selected' : '' ?>>&lt; 11.8V (Dead / Replace)</option>
                        </select>
                    </div>
                </div>

                <h4 class="fw-bold text-white mb-3 border-bottom border-dark pb-2">
                    <i class="fa-solid fa-oil-can text-danger me-2"></i>3. Vital Fluids & Mechanical
                </h4>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Engine Oil Status</label>
                        <select name="engine_oil_status" class="form-select form-select-dark">
                            <option value="Good" <?= ($existingInspection['engine_oil_status'] ?? '') === 'Good' ? 'selected' : '' ?>>Good (Clean golden/amber)</option>
                            <option value="Low" <?= ($existingInspection['engine_oil_status'] ?? '') === 'Low' ? 'selected' : '' ?>>Low Level</option>
                            <option value="Degraded" <?= ($existingInspection['engine_oil_status'] ?? '') === 'Degraded' ? 'selected' : '' ?>>Degraded / Blackened</option>
                            <option value="Critical" <?= ($existingInspection['engine_oil_status'] ?? '') === 'Critical' ? 'selected' : '' ?>>Critical Sludge</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Brake Fluid Moisture</label>
                        <select name="brake_fluid_status" class="form-select form-select-dark">
                            <option value="Good" <?= ($existingInspection['brake_fluid_status'] ?? '') === 'Good' ? 'selected' : '' ?>>Good (&lt; 1% moisture)</option>
                            <option value="Low" <?= ($existingInspection['brake_fluid_status'] ?? '') === 'Low' ? 'selected' : '' ?>>Low Fluid Reservoir</option>
                            <option value="Needs Flush" <?= ($existingInspection['brake_fluid_status'] ?? '') === 'Needs Flush' ? 'selected' : '' ?>>Needs Complete Bleed & Flush</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Engine Coolant Level</label>
                        <select name="coolant_level" class="form-select form-select-dark">
                            <option value="Adequate" <?= ($existingInspection['coolant_level'] ?? '') === 'Adequate' ? 'selected' : '' ?>>Adequate (Between Min-Max)</option>
                            <option value="Low" <?= ($existingInspection['coolant_level'] ?? '') === 'Low' ? 'selected' : '' ?>>Low (Needs Top-up)</option>
                            <option value="Leaking" <?= ($existingInspection['coolant_level'] ?? '') === 'Leaking' ? 'selected' : '' ?>>Hose / Radiator Leakage</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Scratches, Dents or Damage Notes:</label>
                    <textarea name="scratches_dents_notes" class="form-control form-control-dark" rows="2" placeholder="e.g. Scratches on front left bumper; small ding on right rear door."><?= htmlspecialchars($existingInspection['scratches_dents_notes'] ?? '') ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold">Master Technician Remarks & Recommendations:</label>
                    <textarea name="general_remarks" class="form-control form-control-dark" rows="3" placeholder="e.g. Front brake pads are worn down to 2.5mm. Recommend pad renewal and rotor skimming."><?= htmlspecialchars($existingInspection['general_remarks'] ?? '') ?></textarea>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <a href="index.php" class="btn btn-outline-light">Cancel</a>
                    <button type="submit" class="btn btn-red btn-lg px-4">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Save Report & Proceed to Estimate
                    </button>
                </div>
            </div>
        </form>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
