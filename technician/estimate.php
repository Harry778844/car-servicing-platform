<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * Technician Service Estimate Generator & Customer Quotation
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

require_role('technician', 'login.php');

$currentUser = current_user();
$userId = $currentUser['id'];
$employee = Database::fetchOne("SELECT * FROM employees WHERE user_id = ? LIMIT 1", [$userId]);

$bookingId = (int)($_GET['booking_id'] ?? 0);
$booking = Database::fetchOne("
    SELECT b.*, v.make, v.model, v.reg_number, u.id as customer_user_id, u.name as customer_name
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    JOIN customers c ON b.customer_id = c.id
    JOIN users u ON c.user_id = u.id
    WHERE b.id = ? LIMIT 1
", [$bookingId]);

if (!$booking) {
    die("Invalid booking.");
}

$error = '';
$existingEstimate = Database::fetchOne("SELECT * FROM service_estimates WHERE booking_id = ? LIMIT 1", [$bookingId]);

// Handle Estimate Generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_estimate') {
    $partsTotal = (float)($_POST['parts_total'] ?? 0);
    $laborTotal = (float)($_POST['labor_total'] ?? 0);
    $discount = (float)($_POST['discount_amount'] ?? 0);

    $subtotal = ($partsTotal + $laborTotal) - $discount;
    $tax = $subtotal * 0.18; // 18% GST
    $grandTotal = $subtotal + $tax;

    $itemNames = $_POST['item_name'] ?? [];
    $itemCosts = $_POST['item_cost'] ?? [];
    $breakdownItems = [];
    for ($i = 0; $i < count($itemNames); $i++) {
        if (!empty($itemNames[$i])) {
            $breakdownItems[] = [
                'name' => trim($itemNames[$i]),
                'cost' => (float)($itemCosts[$i] ?? 0)
            ];
        }
    }
    $breakdownJson = json_encode(['items' => $breakdownItems]);

    try {
        if ($existingEstimate) {
            Database::execute("
                UPDATE service_estimates 
                SET parts_total = ?, labor_total = ?, tax_amount = ?, discount_amount = ?, grand_total = ?, estimate_breakdown = ?, customer_approval_status = 'pending'
                WHERE id = ?
            ", [$partsTotal, $laborTotal, $tax, $discount, $grandTotal, $breakdownJson, $existingEstimate['id']]);
        } else {
            $estNumber = generate_estimate_number();
            Database::execute("
                INSERT INTO service_estimates (
                    booking_id, estimate_number, parts_total, labor_total, tax_amount, discount_amount, grand_total, estimate_breakdown, customer_approval_status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ", [$bookingId, $estNumber, $partsTotal, $laborTotal, $tax, $discount, $grandTotal, $breakdownJson]);
        }

        // Update booking total amount and move to 'Waiting for Customer Approval'
        Database::execute("UPDATE bookings SET total_amount = ? WHERE id = ?", [$grandTotal, $bookingId]);

        log_status_history(
            $bookingId,
            'Waiting for Customer Approval',
            'Service estimate generated for ' . format_currency($grandTotal) . '. Dispatched to customer for approval.',
            $userId
        );

        // Notify customer
        send_notification(
            $booking['customer_user_id'],
            'Estimate Ready for Approval',
            'A servicing estimate of ' . format_currency($grandTotal) . ' has been generated for your car (' . $booking['reg_number'] . '). Tap to review and approve.',
            'warning',
            'track-service.php?booking=' . $booking['booking_number']
        );

        set_flash('success', 'Estimate sent to customer! Service status moved to "Waiting for Customer Approval".');
        header('Location: ' . BASE_URL . '/technician/index.php');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$pageTitle = 'Generate Service Estimate';
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
                <h2 class="fw-bold text-white mb-1"><i class="fa-solid fa-file-invoice-dollar text-danger me-2"></i>Service Estimate Generation</h2>
                <p class="text-muted small mb-0">Itemize required OEM spares and diagnostic labor to request customer approval before servicing.</p>
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

        <form method="POST" action="estimate.php?booking_id=<?= $bookingId ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_estimate">

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card-dark p-4 p-md-5 mb-4">
                        <h4 class="fw-bold text-white mb-3 border-bottom border-dark pb-2">
                            <i class="fa-solid fa-cubes-stacked text-danger me-2"></i>Itemized Spares & Labor Items
                        </h4>

                        <div id="items-container">
                            <div class="row g-2 mb-2 item-row">
                                <div class="col-8">
                                    <input type="text" name="item_name[]" class="form-control form-control-dark" placeholder="Item Name (e.g. Synthetic Engine Oil 5W30 4L)" value="Scheduled Periodic Maintenance & Engine Oil">
                                </div>
                                <div class="col-4">
                                    <input type="number" step="0.01" name="item_cost[]" class="form-control form-control-dark item-cost-input" placeholder="Cost (INR)" value="2499.00">
                                </div>
                            </div>
                            <div class="row g-2 mb-2 item-row">
                                <div class="col-8">
                                    <input type="text" name="item_name[]" class="form-control form-control-dark" placeholder="Item Name (e.g. Front Brake Pad Set)" value="Front Brake Pad Renewal (Bosch OEM)">
                                </div>
                                <div class="col-4">
                                    <input type="number" step="0.01" name="item_cost[]" class="form-control form-control-dark item-cost-input" placeholder="Cost (INR)" value="1450.00">
                                </div>
                            </div>
                            <div class="row g-2 mb-2 item-row">
                                <div class="col-8">
                                    <input type="text" name="item_name[]" class="form-control form-control-dark" placeholder="Item Name" value="Brake Disc Rotor Skimming & Caliper Grease">
                                </div>
                                <div class="col-4">
                                    <input type="number" step="0.01" name="item_cost[]" class="form-control form-control-dark item-cost-input" placeholder="Cost (INR)" value="750.00">
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="button" class="btn btn-dark-surface btn-sm" onclick="addItemRow()">
                                <i class="fa-solid fa-plus me-1 text-danger"></i> Add Another Item / Spare
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card-dark p-4 sticky-top" style="top: 100px;">
                        <h5 class="fw-bold text-white mb-3 border-bottom border-dark pb-2">
                            <i class="fa-solid fa-calculator text-danger me-2"></i>Cost Breakdown
                        </h5>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Parts & Spares Subtotal *</label>
                            <input type="number" step="0.01" id="parts_total" name="parts_total" class="form-control form-control-dark" value="<?= $existingEstimate['parts_total'] ?? 2499.00 ?>" required oninput="calcTotals()">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Labor & Diagnostic Charges *</label>
                            <input type="number" step="0.01" id="labor_total" name="labor_total" class="form-control form-control-dark" value="<?= $existingEstimate['labor_total'] ?? 2200.00 ?>" required oninput="calcTotals()">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Discount (Optional)</label>
                            <input type="number" step="0.01" id="discount_amount" name="discount_amount" class="form-control form-control-dark" value="<?= $existingEstimate['discount_amount'] ?? 0.00 ?>" oninput="calcTotals()">
                        </div>

                        <hr class="border-secondary">

                        <div class="d-flex justify-content-between small text-muted mb-2">
                            <span>GST (18% Standard):</span>
                            <strong class="text-white" id="disp_tax">₹ 845.82</strong>
                        </div>

                        <div class="d-flex justify-content-between fs-4 fw-bold text-white mb-4">
                            <span>Total Estimate:</span>
                            <span class="text-danger" id="disp_grand">₹ 5,544.82</span>
                        </div>

                        <button type="submit" class="btn btn-red btn-lg w-100">
                            <i class="fa-solid fa-paper-plane me-2"></i>Send to Customer for Approval
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </main>
</div>

<script>
function addItemRow() {
    const container = document.getElementById('items-container');
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 item-row';
    row.innerHTML = `
        <div class="col-8">
            <input type="text" name="item_name[]" class="form-control form-control-dark" placeholder="Item / Part Name">
        </div>
        <div class="col-4">
            <input type="number" step="0.01" name="item_cost[]" class="form-control form-control-dark item-cost-input" placeholder="Cost (INR)">
        </div>
    `;
    container.appendChild(row);
}

function calcTotals() {
    const parts = parseFloat(document.getElementById('parts_total').value) || 0;
    const labor = parseFloat(document.getElementById('labor_total').value) || 0;
    const disc = parseFloat(document.getElementById('discount_amount').value) || 0;

    const sub = (parts + labor) - disc;
    const tax = sub * 0.18;
    const grand = sub + tax;

    document.getElementById('disp_tax').textContent = '₹ ' + tax.toFixed(2);
    document.getElementById('disp_grand').textContent = '₹ ' + grand.toFixed(2);
}
document.addEventListener('DOMContentLoaded', calcTotals);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
