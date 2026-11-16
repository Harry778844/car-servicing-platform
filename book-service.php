<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * Online Service Booking & Doorstep Pickup Scheduling
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth_middleware.php';

// Require login for booking
if (!is_logged_in()) {
    set_flash('info', 'Please login or register to book a car service.');
    header('Location: ' . BASE_URL . '/login.php?redirect=book-service.php');
    exit;
}

$currentUser = current_user();
$userId = $currentUser['id'];

// Get customer profile
$customer = Database::fetchOne("SELECT * FROM customers WHERE user_id = ? LIMIT 1", [$userId]);
if (!$customer) {
    // Auto-create customer profile if missing
    Database::execute("INSERT INTO customers (user_id) VALUES (?)", [$userId]);
    $customer = Database::fetchOne("SELECT * FROM customers WHERE user_id = ? LIMIT 1", [$userId]);
}
$customerId = $customer['id'];

// Fetch customer's registered vehicles
$vehicles = Database::fetchAll("SELECT * FROM vehicles WHERE customer_id = ? ORDER BY id DESC", [$customerId]);

// Fetch all active services
$services = Database::fetchAll("SELECT * FROM services WHERE is_active = 1 ORDER BY id ASC");

// Pre-selected service from URL query param
$preselectedServiceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;

$error = '';
$success = '';

// Handle Booking Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_booking') {
    $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
    $isNewVehicle = isset($_POST['is_new_vehicle']) && $_POST['is_new_vehicle'] === '1';

    // If customer entered a new vehicle in-line
    if ($isNewVehicle || $vehicleId === 0) {
        $make = trim($_POST['new_make'] ?? '');
        $model = trim($_POST['new_model'] ?? '');
        $year = (int)($_POST['new_year'] ?? date('Y'));
        $regNumber = strtoupper(trim($_POST['new_reg_number'] ?? ''));
        $fuelType = trim($_POST['new_fuel_type'] ?? 'Petrol');
        $transmission = trim($_POST['new_transmission'] ?? 'Manual');

        if (empty($make) || empty($model) || empty($regNumber)) {
            $error = 'Please provide Make, Model, and Registration Number for your vehicle.';
        } else {
            // Check if vehicle already registered
            $existingVeh = Database::fetchOne("SELECT id FROM vehicles WHERE reg_number = ? LIMIT 1", [$regNumber]);
            if ($existingVeh) {
                $vehicleId = $existingVeh['id'];
            } else {
                Database::execute(
                    "INSERT INTO vehicles (customer_id, make, model, year, reg_number, fuel_type, transmission) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$customerId, $make, $model, $year, $regNumber, $fuelType, $transmission]
                );
                $vehicleId = Database::lastInsertId();
            }
        }
    }

    $selectedServiceIds = $_POST['services'] ?? [];
    $pickupDate = trim($_POST['preferred_pickup_date'] ?? '');
    $pickupTime = trim($_POST['preferred_pickup_time'] ?? '');
    $pickupAddress = trim($_POST['pickup_address'] ?? '');
    $pickupCity = trim($_POST['pickup_city'] ?? '');
    $pickupPincode = trim($_POST['pickup_pincode'] ?? '');
    $pickupLat = !empty($_POST['pickup_latitude']) ? (float)$_POST['pickup_latitude'] : null;
    $pickupLng = !empty($_POST['pickup_longitude']) ? (float)$_POST['pickup_longitude'] : null;
    $customerNotes = trim($_POST['customer_notes'] ?? '');

    if (empty($error)) {
        if ($vehicleId <= 0) {
            $error = 'Please select or add a vehicle.';
        } elseif (empty($selectedServiceIds)) {
            $error = 'Please choose at least one service.';
        } elseif (empty($pickupDate) || empty($pickupTime) || empty($pickupAddress) || empty($pickupCity) || empty($pickupPincode)) {
            $error = 'Please fill out all required pickup schedule and location details.';
        } else {
            try {
                // Calculate base total amount
                $inQuery = implode(',', array_map('intval', $selectedServiceIds));
                $chosenServices = Database::fetchAll("SELECT * FROM services WHERE id IN ({$inQuery})");
                
                $subtotal = 0;
                foreach ($chosenServices as $srv) {
                    $subtotal += (float)$srv['base_price'];
                }
                $tax = $subtotal * 0.18; // 18% GST
                $totalAmount = $subtotal + $tax;

                $bookingNumber = generate_booking_number();

                // Insert into bookings table
                Database::execute("
                    INSERT INTO bookings (
                        booking_number, customer_id, vehicle_id, booking_date,
                        preferred_pickup_date, preferred_pickup_time,
                        pickup_address, pickup_city, pickup_pincode,
                        pickup_latitude, pickup_longitude, current_status,
                        customer_notes, total_amount, payment_status, created_at
                    ) VALUES (?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, 'Booking Pending', ?, ?, 'Unpaid', NOW())",
                    [
                        $bookingNumber, $customerId, $vehicleId,
                        $pickupDate, $pickupTime,
                        $pickupAddress, $pickupCity, $pickupPincode,
                        $pickupLat, $pickupLng,
                        $customerNotes, $totalAmount
                    ]
                );
                $bookingId = Database::lastInsertId();

                // Insert booking services
                foreach ($chosenServices as $cs) {
                    Database::execute(
                        "INSERT INTO booking_services (booking_id, service_id, service_name, price, status) VALUES (?, ?, ?, ?, 'Pending')",
                        [$bookingId, $cs['id'], $cs['name'], $cs['base_price']]
                    );
                }

                // Insert pickup request
                $scheduledDateTime = $pickupDate . ' ' . date('H:i:s', strtotime($pickupTime));
                Database::execute(
                    "INSERT INTO pickup_requests (booking_id, scheduled_time, status) VALUES (?, ?, 'Pending')",
                    [$bookingId, $scheduledDateTime]
                );

                // Insert status history
                log_status_history($bookingId, 'Booking Pending', 'Booking placed by customer online', $userId);

                // In-app notifications
                send_notification(
                    $userId,
                    'Booking Received (' . $bookingNumber . ')',
                    'Your car servicing booking has been received. Our team is assigning a pickup driver.',
                    'info',
                    'track-service.php?booking=' . $bookingNumber
                );

                // Admin notification
                $admins = Database::fetchAll("SELECT id FROM users WHERE role = 'admin'");
                foreach ($admins as $adm) {
                    send_notification(
                        $adm['id'],
                        'New Booking: ' . $bookingNumber,
                        'Customer booked servicing for preferred pickup on ' . $pickupDate . '.',
                        'warning',
                        'admin/bookings.php'
                    );
                }

                set_flash('success', "Booking {$bookingNumber} created successfully! Our pickup coordinator will confirm your driver shortly.");
                header('Location: ' . BASE_URL . '/track-service.php?booking=' . $bookingNumber);
                exit;

            } catch (Exception $e) {
                $error = 'Booking failed: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Schedule Car Service & Doorstep Pickup';
$extraJs = 'assets/js/booking.js';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <!-- Header title -->
    <div class="text-center mb-5">
        <span class="hero-tag mb-2"><i class="fa-solid fa-calendar-check me-2"></i>Easy 4-Step Booking</span>
        <h1 class="fw-bold fs-1 text-white mb-2">SCHEDULE CAR <span class="text-danger">SERVICING</span></h1>
        <p class="text-muted mx-auto" style="max-width: 650px;">
            Select your car, pick your required maintenance packages, choose a convenient doorstep pickup slot, and track everything live.
        </p>
    </div>

    <!-- Stepper Navigation Header -->
    <div class="row justify-content-center mb-4">
        <div class="col-lg-10">
            <div class="d-flex justify-content-between position-relative p-3 rounded card-dark border-secondary">
                <div class="d-flex align-items-center gap-2">
                    <span id="step-indicator-1" class="badge-step active">1</span>
                    <span class="fw-bold text-white small d-none d-md-inline">Vehicle Info</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span id="step-indicator-2" class="badge-step">2</span>
                    <span class="fw-bold text-white small d-none d-md-inline">Select Services</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span id="step-indicator-3" class="badge-step">3</span>
                    <span class="fw-bold text-white small d-none d-md-inline">Pickup & Address</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span id="step-indicator-4" class="badge-step">4</span>
                    <span class="fw-bold text-white small d-none d-md-inline">Review & Book</span>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="row justify-content-center mb-4">
            <div class="col-lg-10">
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" action="book-service.php" id="bookingWizardForm">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create_booking">
        <input type="hidden" name="hidden_total_amount" id="hidden_total_amount" value="0.00">

        <div class="row justify-content-center">
            <!-- Left Side / Wizard Steps -->
            <div class="col-lg-7">
                
                <!-- STEP 1: VEHICLE SELECTION -->
                <div id="step-content-1" class="wizard-step-content card-dark p-4 p-md-5 mb-4">
                    <h3 class="fw-bold text-white mb-3"><i class="fa-solid fa-car text-danger me-2"></i>Step 1: Select Your Vehicle</h3>
                    <p class="text-muted small mb-4">Choose a saved vehicle from your garage, or quickly enter vehicle details.</p>

                    <?php if (!empty($vehicles)): ?>
                        <div class="mb-4">
                            <label class="form-label text-white small fw-bold">Select Saved Vehicle:</label>
                            <div class="row g-3">
                                <?php foreach ($vehicles as $index => $v): ?>
                                    <div class="col-md-6">
                                        <label class="d-block p-3 rounded border border-secondary bg-surface cursor-pointer h-100 position-relative">
                                            <input type="radio" name="vehicle_id" value="<?= $v['id'] ?>" class="form-check-input me-2" <?= ($index === 0) ? 'checked' : '' ?> data-title="<?= htmlspecialchars($v['make'] . ' ' . $v['model'] . ' (' . $v['year'] . ')') ?>" data-reg="<?= htmlspecialchars($v['reg_number']) ?>">
                                            <strong class="text-white d-block"><?= htmlspecialchars($v['make'] . ' ' . $v['model']) ?></strong>
                                            <span class="badge bg-danger mt-1"><?= htmlspecialchars($v['reg_number']) ?></span>
                                            <div class="text-muted small mt-2">
                                                <i class="fa-solid fa-gas-pump me-1"></i><?= htmlspecialchars($v['fuel_type']) ?> &bull; <?= htmlspecialchars($v['transmission']) ?>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="text-center my-3">
                            <span class="text-muted small">&mdash; OR ADD ANOTHER VEHICLE &mdash;</span>
                        </div>
                    <?php endif; ?>

                    <!-- In-line Add Vehicle Accordion -->
                    <div class="p-3 rounded bg-surface border border-secondary mb-4">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_new_vehicle" value="1" id="is_new_vehicle" <?= empty($vehicles) ? 'checked' : '' ?>>
                            <label class="form-check-label text-white fw-bold" for="is_new_vehicle">
                                <?= empty($vehicles) ? 'Add Vehicle Details (Required)' : 'Register a New Vehicle for this booking' ?>
                            </label>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small text-muted">Car Brand / Make *</label>
                                <input type="text" name="new_make" class="form-control form-control-dark" placeholder="e.g. Hyundai, Honda, Maruti">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted">Model Name *</label>
                                <input type="text" name="new_model" class="form-control form-control-dark" placeholder="e.g. Creta, City, Swift">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted">Manufacturing Year</label>
                                <input type="number" name="new_year" class="form-control form-control-dark" value="<?= date('Y') - 2 ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted">Registration Number *</label>
                                <input type="text" name="new_reg_number" class="form-control form-control-dark" placeholder="DL 01 AB 1234">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted">Fuel Type</label>
                                <select name="new_fuel_type" class="form-select form-select-dark">
                                    <option value="Petrol">Petrol</option>
                                    <option value="Diesel">Diesel</option>
                                    <option value="Electric">Electric</option>
                                    <option value="Hybrid">Hybrid</option>
                                    <option value="CNG">CNG</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-red px-4" onclick="goToStep(2)">
                            Next: Select Services <i class="fa-solid fa-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>

                <!-- STEP 2: SELECT SERVICES -->
                <div id="step-content-2" class="wizard-step-content card-dark p-4 p-md-5 mb-4 d-none">
                    <h3 class="fw-bold text-white mb-3"><i class="fa-solid fa-wrench text-danger me-2"></i>Step 2: Choose Services Required</h3>
                    <p class="text-muted small mb-4">Select all required service packages. You can select multiple items.</p>

                    <div class="row g-3 mb-4">
                        <?php foreach ($services as $srv): ?>
                            <?php $isChecked = ($preselectedServiceId > 0 && $preselectedServiceId == $srv['id']) || ($srv['code'] === 'SRV-GEN'); ?>
                            <div class="col-md-6">
                                <label class="p-3 rounded border border-secondary bg-surface d-block h-100 cursor-pointer position-relative hover-border-red">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <input type="checkbox" name="services[]" value="<?= $srv['id'] ?>" class="form-check-input service-checkbox" data-name="<?= htmlspecialchars($srv['name']) ?>" data-price="<?= $srv['base_price'] ?>" <?= $isChecked ? 'checked' : '' ?>>
                                            <strong class="text-white"><?= htmlspecialchars($srv['name']) ?></strong>
                                        </div>
                                        <span class="badge bg-danger"><?= format_currency($srv['base_price']) ?></span>
                                    </div>
                                    <p class="text-muted small mb-1" style="font-size:0.8rem;"><?= htmlspecialchars(substr($srv['description'], 0, 75)) ?>...</p>
                                    <span class="text-secondary small"><i class="fa-solid fa-clock me-1"></i><?= htmlspecialchars($srv['estimated_time']) ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white small fw-bold">Specific Issues or Symptoms (Optional):</label>
                        <textarea name="customer_notes" class="form-control form-control-dark" rows="3" placeholder="e.g. Squeaking noise when applying brakes, AC cooling slows down at idle..."></textarea>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-light px-3" onclick="goToStep(1)">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back
                        </button>
                        <button type="button" class="btn btn-red px-4" onclick="goToStep(3)">
                            Next: Pickup Schedule <i class="fa-solid fa-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>

                <!-- STEP 3: PICKUP SCHEDULING & LOCATION -->
                <div id="step-content-3" class="wizard-step-content card-dark p-4 p-md-5 mb-4 d-none">
                    <h3 class="fw-bold text-white mb-3"><i class="fa-solid fa-location-dot text-danger me-2"></i>Step 3: Schedule Pickup & Location</h3>
                    <p class="text-muted small mb-4">Our pickup driver will arrive at your specified address within your chosen time window.</p>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-white small fw-bold">Preferred Pickup Date *</label>
                            <input type="date" id="preferred_pickup_date" name="preferred_pickup_date" class="form-control form-control-dark" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white small fw-bold">Preferred Time Window *</label>
                            <select id="preferred_pickup_time" name="preferred_pickup_time" class="form-select form-select-dark" required>
                                <option value="09:00 AM - 11:00 AM">09:00 AM - 11:00 AM (Morning)</option>
                                <option value="11:00 AM - 01:00 PM">11:00 AM - 01:00 PM (Mid-day)</option>
                                <option value="02:00 PM - 04:00 PM">02:00 PM - 04:00 PM (Afternoon)</option>
                                <option value="04:00 PM - 06:00 PM">04:00 PM - 06:00 PM (Evening)</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label text-white small fw-bold">Complete Doorstep Address *</label>
                            <textarea id="pickup_address" name="pickup_address" class="form-control form-control-dark" rows="3" placeholder="Building/Villa name, Flat number, Street, Landmark" required><?= htmlspecialchars($customer['address'] ?? '') ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-white small fw-bold">City *</label>
                            <input type="text" id="pickup_city" name="pickup_city" class="form-control form-control-dark" placeholder="e.g. Gurugram, Delhi, Noida" value="<?= htmlspecialchars($customer['city'] ?? 'Gurugram') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-white small fw-bold">Pincode *</label>
                            <input type="text" id="pickup_pincode" name="pickup_pincode" class="form-control form-control-dark" placeholder="e.g. 122003" value="<?= htmlspecialchars($customer['pincode'] ?? '122003') ?>" required>
                        </div>

                        <div class="col-12">
                            <div class="p-3 rounded bg-surface border border-secondary d-flex justify-content-between align-items-center">
                                <div>
                                    <strong class="text-white small d-block"><i class="fa-solid fa-map-location-dot text-danger me-1"></i> Precise GPS Coordinates (Google Maps)</strong>
                                    <span class="text-muted small">Helps our pickup driver navigate straight to your parking bay.</span>
                                </div>
                                <button type="button" id="btn-get-location" class="btn btn-outline-secondary btn-sm">
                                    <i class="fa-solid fa-location-crosshairs me-1"></i> Detect My Location
                                </button>
                            </div>
                            <input type="hidden" name="pickup_latitude" id="pickup_latitude" value="28.4595">
                            <input type="hidden" name="pickup_longitude" id="pickup_longitude" value="77.0266">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-light px-3" onclick="goToStep(2)">
                            <i class="fa-solid fa-arrow-left me-1"></i> Back
                        </button>
                        <button type="button" class="btn btn-red px-4" onclick="goToStep(4)">
                            Review Booking <i class="fa-solid fa-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>

                <!-- STEP 4: REVIEW & CONFIRM -->
                <div id="step-content-4" class="wizard-step-content card-dark p-4 p-md-5 mb-4 d-none">
                    <h3 class="fw-bold text-white mb-3"><i class="fa-solid fa-file-contract text-danger me-2"></i>Step 4: Review & Confirm Booking</h3>
                    <p class="text-muted small mb-4">Please verify your booking details. No immediate payment is required now!</p>

                    <div class="p-3 rounded bg-surface border border-secondary mb-3">
                        <h6 class="text-danger fw-bold text-uppercase small mb-2">Vehicle Details</h6>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-white fw-bold fs-5" id="rev-vehicle-name">Selected Vehicle</span>
                            <span class="badge bg-danger" id="rev-vehicle-reg">REG NO</span>
                        </div>
                    </div>

                    <div class="p-3 rounded bg-surface border border-secondary mb-3">
                        <h6 class="text-danger fw-bold text-uppercase small mb-2">Pickup Schedule</h6>
                        <div class="mb-1 text-white"><i class="fa-solid fa-clock text-muted me-2"></i><span id="rev-pickup-datetime">Date & Time</span></div>
                        <div class="text-muted small"><i class="fa-solid fa-location-dot text-muted me-2"></i><span id="rev-pickup-address">Address</span></div>
                    </div>

                    <div class="alert alert-info bg-dark border-info text-info small mb-4">
                        <i class="fa-solid fa-shield-check me-2"></i> <strong>Zero Upfront Payment:</strong> You only pay after your vehicle is inspected and you approve the transparent estimate. Pay online via Razorpay or Cash upon delivery.
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-light px-3" onclick="goToStep(3)">
                            <i class="fa-solid fa-arrow-left me-1"></i> Edit Details
                        </button>
                        <button type="submit" class="btn btn-red btn-lg px-4">
                            <i class="fa-solid fa-circle-check me-2"></i>Confirm & Place Booking
                        </button>
                    </div>
                </div>

            </div>

            <!-- Right Side / Live Price Summary Sticky Box -->
            <div class="col-lg-5">
                <div class="card-dark p-4 sticky-top" style="top: 100px;">
                    <h5 class="fw-bold text-white mb-3 border-bottom border-dark pb-2">
                        <i class="fa-solid fa-receipt text-danger me-2"></i>Estimate Summary
                    </h5>

                    <ul class="list-unstyled mb-3" id="summary-services-list">
                        <!-- Populated dynamically via JS -->
                    </ul>

                    <div class="d-flex justify-content-between py-2 border-top border-dark text-muted small">
                        <span>Subtotal:</span>
                        <strong class="text-white" id="summary-subtotal">₹ 0.00</strong>
                    </div>

                    <div class="d-flex justify-content-between py-2 text-muted small">
                        <span>GST (18% Standard):</span>
                        <strong class="text-white" id="summary-tax">₹ 0.00</strong>
                    </div>

                    <div class="d-flex justify-content-between py-2 text-muted small">
                        <span>Doorstep Pickup & Drop:</span>
                        <span class="badge bg-success bg-opacity-25 text-success">FREE</span>
                    </div>

                    <div class="d-flex justify-content-between py-3 border-top border-secondary fs-5 text-white fw-bold">
                        <span>Estimated Total:</span>
                        <span class="text-danger" id="summary-grand-total">₹ 0.00</span>
                    </div>

                    <div class="p-3 rounded bg-surface border border-secondary mt-3 small text-muted">
                        <i class="fa-solid fa-circle-info text-danger me-2"></i>
                        Final bill may vary depending on parts replacement required during technician bay inspection.
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
