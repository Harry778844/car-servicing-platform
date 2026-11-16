<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * Homepage
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/helpers.php';

$pageTitle = 'Doorstep Car Servicing & Maintenance Platform';

// Fetch active services
$services = [];
try {
    $services = Database::fetchAll("SELECT * FROM services WHERE is_active = 1 ORDER BY id ASC LIMIT 10");
} catch (Exception $e) {
    $services = [];
}

// Fetch published reviews
$reviews = [];
try {
    $reviews = Database::fetchAll("
        SELECT r.*, u.name as customer_name, v.make, v.model 
        FROM reviews r
        JOIN customers c ON r.customer_id = c.id
        JOIN users u ON c.user_id = u.id
        LEFT JOIN bookings b ON r.booking_id = b.id
        LEFT JOIN vehicles v ON b.vehicle_id = v.id
        WHERE r.is_published = 1
        ORDER BY r.id DESC LIMIT 3
    ");
} catch (Exception $e) {
    $reviews = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Banner Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="hero-tag mb-3"><i class="fa-solid fa-shield-halved me-2"></i>Certified Multi-Brand Workshop Network</span>
                <h1 class="hero-title text-white mb-3">
                    PROFESSIONAL CAR CARE, <br><span class="text-danger">AT YOUR DOORSTEP.</span>
                </h1>
                <p class="lead text-muted mb-4 pe-lg-4">
                    Book routine car maintenance, engine diagnostics or emergency roadside assistance in seconds. We pick up your car, service it with genuine OEM parts, share real-time inspection photos, and drop it back sparkling clean.
                </p>

                <div class="d-flex flex-wrap gap-3 mb-5">
                    <a href="book-service.php" class="btn btn-red btn-lg px-4 py-3">
                        <i class="fa-solid fa-calendar-check me-2"></i>Schedule Doorstep Pickup
                    </a>
                    <a href="services.php" class="btn btn-outline-red btn-lg px-4 py-3">
                        <i class="fa-solid fa-list-check me-2"></i>Explore Services
                    </a>
                </div>

                <!-- Live Quick Tracking Bar -->
                <div class="card-dark p-3 p-md-4 border-danger border-opacity-50">
                    <form action="track-service.php" method="GET" class="row g-2 align-items-center">
                        <div class="col-md-8">
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-danger"><i class="fa-solid fa-crosshairs"></i></span>
                                <input type="text" name="booking" class="form-control form-control-dark" placeholder="Enter Booking ID (e.g. BK-2026-001) or Reg No." required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-danger w-100 py-2">
                                <i class="fa-solid fa-radar me-1"></i> Track Live
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-5 text-center position-relative">
                <div class="p-4 rounded-4 card-dark border-secondary shadow-lg">
                    <img src="https://images.unsplash.com/photo-1619642751034-765dfdf7c58e?auto=format&fit=crop&w=800&q=80" alt="Car Servicing Workshop" class="img-fluid rounded-3 mb-4 shadow" style="object-fit:cover; max-height: 280px; width: 100%;">
                    
                    <div class="row g-2 text-start">
                        <div class="col-6">
                            <div class="p-2 rounded bg-surface border border-dark">
                                <div class="text-danger fw-bold fs-4">15,000+</div>
                                <div class="text-muted small">Vehicles Serviced</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-surface border border-dark">
                                <div class="text-warning fw-bold fs-4"><i class="fa-solid fa-star me-1"></i>4.9 / 5</div>
                                <div class="text-muted small">Customer Rating</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-surface border border-dark">
                                <div class="text-info fw-bold fs-4">100%</div>
                                <div class="text-muted small">OEM Spares Used</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-surface border border-dark">
                                <div class="text-success fw-bold fs-4">6 Months</div>
                                <div class="text-muted small">Service Warranty</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 4-Step Process Section -->
<section class="py-5 bg-black border-bottom border-dark">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="hero-tag mb-2">Effortless Workflow</span>
            <h2 class="fw-bold fs-1">HOW AUTOPRO WORKS</h2>
            <p class="text-muted">Experience complete peace of mind with our contactless, digitized car care workflow</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-3 col-md-6 text-center">
                <div class="card-dark p-4 h-100 position-relative">
                    <div class="service-icon-wrap mx-auto">
                        <i class="fa-solid fa-calendar-day"></i>
                    </div>
                    <span class="badge bg-danger rounded-pill position-absolute top-0 end-0 m-3">Step 1</span>
                    <h5 class="fw-bold mb-2">Book Online</h5>
                    <p class="text-muted small mb-0">Select your car make/model, choose required services, and schedule a pickup slot with address.</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 text-center">
                <div class="card-dark p-4 h-100 position-relative">
                    <div class="service-icon-wrap mx-auto">
                        <i class="fa-solid fa-truck-pickup"></i>
                    </div>
                    <span class="badge bg-danger rounded-pill position-absolute top-0 end-0 m-3">Step 2</span>
                    <h5 class="fw-bold mb-2">Doorstep Pickup</h5>
                    <p class="text-muted small mb-0">Our verified executive inspects the vehicle at your doorstep, notes odometer/fuel, and drives it safely to the workshop.</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 text-center">
                <div class="card-dark p-4 h-100 position-relative">
                    <div class="service-icon-wrap mx-auto">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </div>
                    <span class="badge bg-danger rounded-pill position-absolute top-0 end-0 m-3">Step 3</span>
                    <h5 class="fw-bold mb-2">Inspection & Approval</h5>
                    <p class="text-muted small mb-0">Master technician performs a 60-point digital inspection. You review the estimate and approve it online before work begins.</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 text-center">
                <div class="card-dark p-4 h-100 position-relative">
                    <div class="service-icon-wrap mx-auto">
                        <i class="fa-solid fa-car-side"></i>
                    </div>
                    <span class="badge bg-danger rounded-pill position-absolute top-0 end-0 m-3">Step 4</span>
                    <h5 class="fw-bold mb-2">Drop-off & Payment</h5>
                    <p class="text-muted small mb-0">Vehicle serviced, quality-tested, washed and delivered back to your home. Pay seamlessly via Razorpay, UPI or Cash.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services Offered Section -->
<section class="py-5" style="background-color: #0b0c10;">
    <div class="container py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-5">
            <div>
                <span class="hero-tag mb-2">Our Capabilities</span>
                <h2 class="fw-bold fs-1">SERVICES WE OFFER</h2>
                <p class="text-muted mb-0">Comprehensive mechanical, electrical, bodywork and periodic maintenance services.</p>
            </div>
            <a href="services.php" class="btn btn-outline-red mt-3 mt-md-0">
                View Full Catalog <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            <?php if (!empty($services)): ?>
                <?php foreach ($services as $srv): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="service-box">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="service-icon-wrap">
                                    <i class="fa-solid <?= htmlspecialchars($srv['icon'] ?? 'fa-wrench') ?>"></i>
                                </div>
                                <?php if (!empty($srv['badge'])): ?>
                                    <span class="badge bg-danger text-uppercase px-2 py-1"><?= htmlspecialchars($srv['badge']) ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <h4 class="fw-bold text-white mb-2"><?= htmlspecialchars($srv['name']) ?></h4>
                            <p class="text-muted small flex-grow-1 mb-3">
                                <?= htmlspecialchars(substr($srv['description'], 0, 110)) ?>...
                            </p>

                            <div class="d-flex justify-content-between align-items-center pt-3 border-top border-dark">
                                <div>
                                    <span class="text-muted small d-block"><i class="fa-solid fa-clock me-1"></i><?= htmlspecialchars($srv['estimated_time']) ?></span>
                                    <span class="fw-bold fs-5 text-white"><?= format_currency($srv['base_price']) ?></span>
                                </div>
                                <a href="book-service.php?service_id=<?= $srv['id'] ?>" class="btn btn-red btn-sm px-3">
                                    Book Now
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted">Services list is loading or database requires initialization.</p>
                    <a href="config/setup.php" class="btn btn-danger">Initialize Database</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- 24x7 Roadside Assistance Banner -->
<section class="py-5" style="background: linear-gradient(135deg, #1b1214 0%, #0d0e12 100%); border-top: 1px solid #381518; border-bottom: 1px solid #381518;">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="badge bg-danger px-3 py-1 text-uppercase fw-bold mb-2">Emergency Assistance</span>
                <h2 class="fw-bold text-white mb-2">STRANDED ON THE ROAD? WE HAVE GOT YOUR BACK.</h2>
                <p class="text-muted mb-0">
                    Battery jumpstart, flat tyre replacement, emergency fuel delivery, and flatbed towing dispatched within 30-45 minutes anywhere across the region.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="tel:<?= APP_PHONE ?>" class="btn btn-red btn-lg px-4 py-3">
                    <i class="fa-solid fa-phone-volume me-2"></i>Call SOS: <?= APP_PHONE ?>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Customer Reviews Section -->
<section class="py-5 bg-black">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="hero-tag mb-2">Social Proof</span>
            <h2 class="fw-bold fs-1">WHAT CAR OWNERS SAY</h2>
            <p class="text-muted">Thousands of verified reviews from real car enthusiasts and daily commuters</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card-dark p-4 h-100">
                    <div class="text-warning mb-2">
                        <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                    </div>
                    <h5 class="fw-bold text-white mb-2">"Flawless Pickup & Delivery"</h5>
                    <p class="text-muted small mb-3">
                        "The technician sent me high-res photos of my brake pads worn out before charging me a single rupee. The car was dropped back sanitized and runs silky smooth."
                    </p>
                    <div class="d-flex align-items-center gap-3 pt-3 border-top border-dark">
                        <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center fw-bold" style="width:40px;height:40px;">RS</div>
                        <div>
                            <div class="text-white fw-bold small">Rahul Sharma</div>
                            <div class="text-muted" style="font-size: 0.75rem;">Hyundai Creta SX (O)</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card-dark p-4 h-100">
                    <div class="text-warning mb-2">
                        <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                    </div>
                    <h5 class="fw-bold text-white mb-2">"30% Cheaper than Dealership"</h5>
                    <p class="text-muted small mb-3">
                        "Authorized showroom gave me a ₹ 14,000 estimate for AC cooling coil repair. AutoPro diagnosed it, repaired the leak, recharged R134a for less than half."
                    </p>
                    <div class="d-flex align-items-center gap-3 pt-3 border-top border-dark">
                        <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center fw-bold" style="width:40px;height:40px;">PP</div>
                        <div>
                            <div class="text-white fw-bold small">Priya Patel</div>
                            <div class="text-muted" style="font-size: 0.75rem;">Tata Nexon EV</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card-dark p-4 h-100">
                    <div class="text-warning mb-2">
                        <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                    </div>
                    <h5 class="fw-bold text-white mb-2">"Transparent Live Status"</h5>
                    <p class="text-muted small mb-3">
                        "Tracking each step from 'Pickup Assigned' to 'Vehicle in Bay' right on my phone is awesome. You always know what is happening with your pride and joy."
                    </p>
                    <div class="d-flex align-items-center gap-3 pt-3 border-top border-dark">
                        <div class="rounded-circle bg-info text-dark d-flex align-items-center justify-content-center fw-bold" style="width:40px;height:40px;">AK</div>
                        <div>
                            <div class="text-white fw-bold small">Amitav Roy</div>
                            <div class="text-muted" style="font-size: 0.75rem;">Honda City ZX</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call To Action Section -->
<section class="py-5 bg-surface text-center border-top border-dark">
    <div class="container py-4">
        <h2 class="fw-bold text-white mb-3">READY TO EXPERIENCE ZERO-HASSLE CAR SERVICE?</h2>
        <p class="text-muted mb-4 mx-auto" style="max-width: 650px;">
            Book an appointment online in under 2 minutes. Pick your slot, specify your car, and let our master technicians handle everything.
        </p>
        <div class="d-flex justify-content-center gap-3">
            <a href="book-service.php" class="btn btn-red btn-lg px-5">Book Service Now</a>
            <a href="contact.php" class="btn btn-outline-light btn-lg px-4">Contact Workshop</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
