<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * Services Catalog Page
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/helpers.php';

$pageTitle = 'Our Car Servicing & Maintenance Packages';

// Fetch all active services
$services = [];
try {
    $services = Database::fetchAll("SELECT * FROM services WHERE is_active = 1 ORDER BY id ASC");
} catch (Exception $e) {
    $services = [];
}

// Inclusions map for rich details
$serviceInclusions = [
    'General Service' => ['60-Point Comprehensive Inspection', 'Engine Oil Flush & Replacement', 'Air Filter Clean / Replace', 'Spark Plug Check & Cleaning', 'Coolant & Brake Fluid Top-up', 'Complete Interior Vacuum & Body Wash'],
    'Oil Change' => ['Synthetic Engine Oil Replacement (up to 4L)', 'OEM Grade Oil Filter Replacement', 'Drain Plug Gasket Renewal', 'Free 15-Point Vital Fluid Check', 'Sump Leakage Inspection'],
    'Engine Repair' => ['Computerized OBD-II Scanner Diagnostics', 'Cylinder Compression & Leak-down Test', 'Fuel Injector Cleaning & Flow Test', 'Timing Belt & Tensioner Inspection', 'Spark Plugs & Ignition Coil Test'],
    'AC Service' => ['R134a / R1234yf Gas Refill & Leak Test', 'Cabin / Pollen Filter Cleaning or Replacement', 'High-pressure Condenser Wash', 'AC Blower Fan Cleaning & Duct Sanitization', 'Cooling Coil Inspection'],
    'Water Wash' => ['High-Pressure Snow Foam Exterior Body Wash', 'Underbody Chassis Washing', 'Wheel Rim De-greasing & Tyre Polish', 'Full Interior Vacuuming & Mat Cleaning', 'Dashboard & Trim UV Dressing'],
    'Breakdown Assistance' => ['Doorstep Battery Jumpstart', 'Flat Tyre Replacement / Emergency Puncture Fix', 'Emergency Fuel Delivery (up to 5L)', 'On-site Minor Electrical Diagnostics', '24x7 Flatbed Towing Service'],
    'Wheel Alignment' => ['Computerized 3D 4-Wheel Laser Alignment', 'Dynamic Electronic Wheel Balancing', 'Suspension Bushing & Ball Joint Check', 'Tyre Pressure & Tread Depth Inspection', 'Free Tyre Rotation'],
    'Brake Service' => ['Front & Rear Brake Pad Wear Inspection', 'Brake Caliper Degreasing & Slider Lubrication', 'Brake Disc Rotor Skimming', 'Brake Fluid Bleeding & Moisture Test', 'Handbrake Cable Tension Calibration'],
    'Battery Replacement' => ['Doorstep Battery Health Test', 'Terminal Cleaning & Anti-corrosion Grease', 'Alternator Output Voltage Analysis', 'Free Installation of New Exide/Amaron Battery', 'Up to 55 Months Warranty'],
    'Dent & Painting' => ['Panel Paintless Dent Removal (PDR)', 'Computerized OEM Spectrophotometer Paint Matching', 'Anti-Corrosion Primer Application', 'Two-Coat Bake-Oven Metallic Paint', 'Clear Coat Scratch-Resistant Finish']
];

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Banner -->
<section class="py-5 bg-black border-bottom border-dark">
    <div class="container text-center">
        <span class="hero-tag mb-2">Transparent Menu</span>
        <h1 class="fw-bold fs-1 text-white mb-2">SERVICES & <span class="text-danger">PACKAGES</span></h1>
        <p class="text-muted mx-auto" style="max-width: 650px;">
            Expert automotive care backed by upfront itemized estimates, OEM replacement spares, master technicians and our signature 6-Month / 10,000 KM service guarantee.
        </p>
    </div>
</section>

<!-- Services Grid -->
<section class="py-5">
    <div class="container py-4">
        <div class="row g-4">
            <?php if (!empty($services)): ?>
                <?php foreach ($services as $srv): ?>
                    <?php 
                        $inclusions = $serviceInclusions[$srv['name']] ?? [
                            'Complete Inspection', 'Genuine OEM Spares', 'Trained Mechanics', 'Post-Service Road Test'
                        ];
                    ?>
                    <div class="col-lg-6" id="<?= strtolower(explode(' ', $srv['name'])[0]) ?>">
                        <div class="card-dark p-4 h-100 d-flex flex-column justify-content-between position-relative">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="service-icon-wrap mb-0" style="width:48px;height:48px;font-size:1.25rem;">
                                            <i class="fa-solid <?= htmlspecialchars($srv['icon'] ?? 'fa-wrench') ?>"></i>
                                        </div>
                                        <div>
                                            <h4 class="fw-bold text-white mb-0"><?= htmlspecialchars($srv['name']) ?></h4>
                                            <span class="text-muted small"><i class="fa-solid fa-tag me-1 text-danger"></i><?= htmlspecialchars($srv['category']) ?></span>
                                        </div>
                                    </div>
                                    <?php if (!empty($srv['badge'])): ?>
                                        <span class="badge bg-danger text-uppercase px-2 py-1"><?= htmlspecialchars($srv['badge']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <p class="text-muted small mb-3">
                                    <?= htmlspecialchars($srv['description']) ?>
                                </p>

                                <div class="bg-surface p-3 rounded mb-3 border border-dark">
                                    <h6 class="text-white fw-bold small mb-2"><i class="fa-solid fa-circle-check text-success me-2"></i>What's Included:</h6>
                                    <ul class="list-unstyled mb-0 row g-2 small text-muted">
                                        <?php foreach ($inclusions as $inc): ?>
                                            <li class="col-sm-6 d-flex align-items-start gap-2">
                                                <i class="fa-solid fa-check text-danger mt-1" style="font-size: 0.75rem;"></i>
                                                <span><?= htmlspecialchars($inc) ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pt-3 border-top border-dark mt-2">
                                <div>
                                    <span class="text-muted small d-block"><i class="fa-solid fa-hourglass-start me-1"></i>Duration: <?= htmlspecialchars($srv['estimated_time']) ?></span>
                                    <span class="text-muted small">Starts from: </span>
                                    <span class="fw-bold fs-4 text-white"><?= format_currency($srv['base_price']) ?></span>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="book-service.php?service_id=<?= $srv['id'] ?>" class="btn btn-red px-3">
                                        <i class="fa-solid fa-calendar-plus me-1"></i> Book Now
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted">No services found. Run setup wizard to seed data.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Peace of Mind Guarantee Banner -->
<section class="py-5 bg-black border-top border-dark">
    <div class="container text-center">
        <h3 class="fw-bold text-white mb-2">EVERY SERVICE IS BACKED BY OUR 100% ASSURANCE</h3>
        <p class="text-muted mb-4 mx-auto" style="max-width: 600px;">If you face any issues within 6 months or 10,000 KM after service, we rectify it with zero labor charges.</p>
        <a href="book-service.php" class="btn btn-red btn-lg px-5">Book Your Service</a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
