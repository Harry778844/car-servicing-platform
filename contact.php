<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * Contact Us & Workshop Location
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/helpers.php';

$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!empty($name) && !empty($email) && !empty($message)) {
        $sent = true;
        set_flash('success', 'Thank you, ' . htmlspecialchars($name) . '! Your enquiry has been received. Our workshop team will reach out shortly.');
    }
}

$pageTitle = 'Contact Us & Workshop Center';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Banner -->
<section class="py-5 bg-black border-bottom border-dark text-center">
    <div class="container">
        <span class="hero-tag mb-2">Get In Touch</span>
        <h1 class="fw-bold fs-1 text-white mb-2">CONTACT <span class="text-danger">AUTOPRO</span></h1>
        <p class="text-muted mx-auto" style="max-width: 600px;">
            Have questions about your car servicing, fleet bookings or emergency roadside support? We are always here to assist.
        </p>
    </div>
</section>

<section class="py-5">
    <div class="container py-4">
        <div class="row g-5">
            <!-- Left Contact Form -->
            <div class="col-lg-7">
                <div class="card-dark p-4 p-md-5">
                    <h3 class="fw-bold text-white mb-3"><i class="fa-solid fa-envelope text-danger me-2"></i>Send an Enquiry</h3>
                    <p class="text-muted small mb-4">Fill out the form below and an AutoPro technical consultant will respond within 30 minutes.</p>

                    <form method="POST" action="contact.php">
                        <?= csrf_field() ?>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-white small fw-bold">Full Name *</label>
                                <input type="text" name="name" class="form-control form-control-dark" required placeholder="e.g. Rahul Sharma">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-white small fw-bold">Email Address *</label>
                                <input type="email" name="email" class="form-control form-control-dark" required placeholder="name@example.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-white small fw-bold">Mobile Phone *</label>
                                <input type="tel" name="phone" class="form-control form-control-dark" required placeholder="+91 98765 43210">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-white small fw-bold">Car Make & Model</label>
                                <input type="text" name="car" class="form-control form-control-dark" placeholder="e.g. Honda City (2020)">
                            </div>
                            <div class="col-12">
                                <label class="form-label text-white small fw-bold">Your Message / Query *</label>
                                <textarea name="message" class="form-control form-control-dark" rows="4" required placeholder="Describe your service inquiry or question in detail..."></textarea>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-red btn-lg">
                                <i class="fa-solid fa-paper-plane me-2"></i>Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Location & Direct Info -->
            <div class="col-lg-5">
                <div class="card-dark p-4 mb-4">
                    <h5 class="fw-bold text-white mb-3 border-bottom border-dark pb-2">
                        <i class="fa-solid fa-headset text-danger me-2"></i>Direct Helplines
                    </h5>
                    
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center" style="width:44px;height:44px;font-size:1.1rem;">
                            <i class="fa-solid fa-phone"></i>
                        </div>
                        <div>
                            <small class="text-muted text-uppercase d-block" style="font-size:0.75rem;">Customer Support</small>
                            <a href="tel:<?= APP_PHONE ?>" class="text-white fw-bold fs-5"><?= APP_PHONE ?></a>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle bg-dark border border-secondary text-danger d-flex align-items-center justify-content-center" style="width:44px;height:44px;font-size:1.1rem;">
                            <i class="fa-solid fa-truck-pickup"></i>
                        </div>
                        <div>
                            <small class="text-muted text-uppercase d-block" style="font-size:0.75rem;">24x7 Roadside SOS</small>
                            <strong class="text-danger fs-5">+91 99999 88888</strong>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle bg-dark border border-secondary text-info d-flex align-items-center justify-content-center" style="width:44px;height:44px;font-size:1.1rem;">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                        <div>
                            <small class="text-muted text-uppercase d-block" style="font-size:0.75rem;">Email Enquiries</small>
                            <a href="mailto:<?= APP_EMAIL ?>" class="text-white"><?= APP_EMAIL ?></a>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-dark border border-secondary text-warning d-flex align-items-center justify-content-center" style="width:44px;height:44px;font-size:1.1rem;">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <div>
                            <small class="text-muted text-uppercase d-block" style="font-size:0.75rem;">Workshop Central</small>
                            <span class="text-white small"><?= APP_ADDRESS ?></span>
                        </div>
                    </div>
                </div>

                <!-- Google Maps Interactive Placeholder Box -->
                <div class="card-dark p-3 border-secondary text-center overflow-hidden position-relative">
                    <div style="height: 180px; background: url('https://images.unsplash.com/photo-1526778548025-fa2f459cd5c1?auto=format&fit=crop&w=800&q=80') center/cover; border-radius: 8px;" class="d-flex align-items-center justify-content-center">
                        <div class="p-2 px-3 rounded bg-black bg-opacity-75 border border-danger text-white small">
                            <i class="fa-solid fa-map-pin text-danger me-2"></i> AutoPro Hub - Gurugram (Google Maps Verified)
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQs Section -->
<section class="py-5 bg-black border-top border-dark">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="hero-tag mb-2">Got Questions?</span>
            <h2 class="fw-bold fs-1">FREQUENTLY ASKED QUESTIONS</h2>
            <p class="text-muted">Quick answers to common questions about our car pickup and servicing model</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion accordion-flush" id="faqAccordion">
                    <div class="accordion-item bg-dark border-secondary mb-3 rounded">
                        <h2 class="accordion-header">
                            <button class="accordion-button bg-dark text-white collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Is vehicle pickup and drop really 100% free?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted small">
                                Yes! For all periodic maintenance and mechanical repair bookings within our operational radius, doorstep pickup and drop-off are completely complimentary with zero extra surcharge.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item bg-dark border-secondary mb-3 rounded">
                        <h2 class="accordion-header">
                            <button class="accordion-button bg-dark text-white collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                What happens if additional repairs are needed during inspection?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted small">
                                Our technicians will never perform unapproved work. If we find worn brake pads, suspension wear or leaks, we upload photos and an itemized estimate to your customer dashboard. Work only commences after you tap "Approve".
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item bg-dark border-secondary mb-3 rounded">
                        <h2 class="accordion-header">
                            <button class="accordion-button bg-dark text-white collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                What warranty do you offer on serviced cars?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted small">
                                All our routine maintenance services come with a standard 6-Month or 10,000 KM warranty. New parts replaced carry official manufacturer OEM warranties up to 2 years.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
