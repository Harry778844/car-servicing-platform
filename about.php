<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * About Us Page
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/helpers.php';

$pageTitle = 'About AutoPro | Certified Multi-Brand Workshop';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header Banner -->
<section class="py-5 bg-black border-bottom border-dark">
    <div class="container text-center">
        <span class="hero-tag mb-2">Our Mission</span>
        <h1 class="fw-bold fs-1 text-white mb-2">ABOUT <span class="text-danger">AUTOPRO</span></h1>
        <p class="text-muted mx-auto" style="max-width: 600px;">
            Redefining vehicle ownership experience through doorstep pickup, digital diagnostic reports, fair transparent pricing and OEM quality craftsmanship.
        </p>
    </div>
</section>

<!-- Company Vision & Story -->
<section class="py-5">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="hero-tag mb-2">The Story</span>
                <h2 class="fw-bold text-white mb-3">BUILDING A SMARTER, MORE HONEST AUTO WORKSHOP</h2>
                <p class="text-muted mb-3">
                    Founded with a singular mission: to eliminate the anxiety, inflated dealership bills, and lost weekends spent waiting in greasy workshop lounges. AutoPro combines state-of-the-art diagnostic bays with a seamless digital customer experience.
                </p>
                <p class="text-muted mb-4">
                    Every service request comes with free doorstep pickup, multi-point digital inspection reports with photographs, itemized estimate approvals before any wrench is turned, and guaranteed genuine spares with a 6-month warranty.
                </p>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-3 p-3 rounded card-dark">
                            <i class="fa-solid fa-certificate text-danger fs-3"></i>
                            <div>
                                <h6 class="fw-bold mb-0 text-white">ASE Certified</h6>
                                <small class="text-muted">Master Technicians</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-3 p-3 rounded card-dark">
                            <i class="fa-solid fa-gem text-danger fs-3"></i>
                            <div>
                                <h6 class="fw-bold mb-0 text-white">100% OEM Spares</h6>
                                <small class="text-muted">Direct from Manufacturers</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-3 p-3 rounded card-dark">
                            <i class="fa-solid fa-truck-pickup text-danger fs-3"></i>
                            <div>
                                <h6 class="fw-bold mb-0 text-white">Free Doorstep Pickup</h6>
                                <small class="text-muted">Insured Transit</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-3 p-3 rounded card-dark">
                            <i class="fa-solid fa-hand-holding-dollar text-danger fs-3"></i>
                            <div>
                                <h6 class="fw-bold mb-0 text-white">Transparent Pricing</h6>
                                <small class="text-muted">No Hidden Charges</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="p-2 card-dark rounded-4 border-secondary shadow-lg">
                    <img src="https://images.unsplash.com/photo-1486006920555-c77dce18193b?auto=format&fit=crop&w=800&q=80" alt="AutoPro Workshop Bay" class="img-fluid rounded-3" style="width: 100%; object-fit: cover; height: 380px;">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Core Metrics & Stats -->
<section class="py-5 bg-black border-top border-bottom border-dark">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-lg-3 col-6">
                <div class="p-3">
                    <h2 class="display-5 fw-bold text-danger mb-1">15,000+</h2>
                    <p class="text-muted text-uppercase small mb-0">Cars Maintained</p>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="p-3">
                    <h2 class="display-5 fw-bold text-white mb-1">98.4%</h2>
                    <p class="text-muted text-uppercase small mb-0">On-Time Delivery</p>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="p-3">
                    <h2 class="display-5 fw-bold text-danger mb-1">45+</h2>
                    <p class="text-muted text-uppercase small mb-0">Master Mechanics</p>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="p-3">
                    <h2 class="display-5 fw-bold text-white mb-1">4.9 / 5</h2>
                    <p class="text-muted text-uppercase small mb-0">Customer Rating</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Values Section -->
<section class="py-5">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="hero-tag mb-2">Our Foundation</span>
            <h2 class="fw-bold fs-1">CORE VALUES</h2>
            <p class="text-muted">Principles that guide every oil change, brake tune and customer interaction</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card-dark p-4 h-100 border-start border-danger border-3">
                    <h4 class="fw-bold text-white mb-2"><i class="fa-solid fa-eye text-danger me-2"></i>Radical Transparency</h4>
                    <p class="text-muted small mb-0">
                        We send photos of worn parts and detailed labor explanations before you spend a penny. No surprise additions on your final invoice.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-dark p-4 h-100 border-start border-danger border-3">
                    <h4 class="fw-bold text-white mb-2"><i class="fa-solid fa-bullseye text-danger me-2"></i>Precision Engineering</h4>
                    <p class="text-muted small mb-0">
                        We adhere to OEM service manual torque specifications, computerized laser wheel balancing, and brand-specific synthetic oil standards.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-dark p-4 h-100 border-start border-danger border-3">
                    <h4 class="fw-bold text-white mb-2"><i class="fa-solid fa-shield-heart text-danger me-2"></i>Safety First</h4>
                    <p class="text-muted small mb-0">
                        Brakes, tyres, steering and lighting are non-negotiable. Every vehicle passes a rigorous 20-point road safety audit before final delivery.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
