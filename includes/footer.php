<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * Global Footer Component
 */
?>
<footer class="site-footer">
    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-lg-4 col-md-6">
                <a class="navbar-brand mb-3 d-inline-block" href="<?= BASE_URL ?>/index.php">
                    <i class="fa-solid fa-car-tunnel"></i> AUTO<span>PRO</span>
                </a>
                <p class="text-muted small pe-lg-4">
                    India's premier multi-brand car servicing network with contactless doorstep pickup & drop, real-time tracking, upfront transparent estimates, and ASE-certified workshop technicians.
                </p>
                <div class="d-flex gap-2 mt-3">
                    <a href="#" class="btn btn-dark-surface btn-sm rounded-circle" style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;"><i class="fa-brands fa-facebook-f text-white"></i></a>
                    <a href="#" class="btn btn-dark-surface btn-sm rounded-circle" style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;"><i class="fa-brands fa-instagram text-white"></i></a>
                    <a href="#" class="btn btn-dark-surface btn-sm rounded-circle" style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;"><i class="fa-brands fa-linkedin-in text-white"></i></a>
                    <a href="#" class="btn btn-dark-surface btn-sm rounded-circle" style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;"><i class="fa-brands fa-whatsapp text-white"></i></a>
                </div>
            </div>

            <div class="col-lg-2 col-md-6 col-6">
                <h5 class="footer-title">Quick Links</h5>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>/index.php">Home</a></li>
                    <li><a href="<?= BASE_URL ?>/services.php">All Services</a></li>
                    <li><a href="<?= BASE_URL ?>/book-service.php">Book Pickup</a></li>
                    <li><a href="<?= BASE_URL ?>/track-service.php">Track Vehicle</a></li>
                    <li><a href="<?= BASE_URL ?>/about.php">About AutoPro</a></li>
                    <li><a href="<?= BASE_URL ?>/contact.php">Contact & Support</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6 col-6">
                <h5 class="footer-title">Core Services</h5>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>/services.php#general">General Service</a></li>
                    <li><a href="<?= BASE_URL ?>/services.php#oil">Engine Oil Change</a></li>
                    <li><a href="<?= BASE_URL ?>/services.php#ac">AC Service & Gas Top-up</a></li>
                    <li><a href="<?= BASE_URL ?>/services.php#brake">Brakes & Suspension</a></li>
                    <li><a href="<?= BASE_URL ?>/services.php#dent">Dent & Painting</a></li>
                    <li><a href="<?= BASE_URL ?>/services.php#breakdown">24x7 Breakdown Roadside</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h5 class="footer-title">Workshop Central</h5>
                <p class="text-muted small mb-2">
                    <i class="fa-solid fa-location-dot text-danger me-2"></i><?= APP_ADDRESS ?>
                </p>
                <p class="text-muted small mb-2">
                    <i class="fa-solid fa-phone text-danger me-2"></i><?= APP_PHONE ?>
                </p>
                <p class="text-muted small mb-3">
                    <i class="fa-solid fa-envelope text-danger me-2"></i><?= APP_EMAIL ?>
                </p>
                <div class="p-2 rounded bg-dark border border-secondary text-center small">
                    <i class="fa-solid fa-clock text-warning me-1"></i> Workshop Hours: <strong>08:00 AM - 08:00 PM</strong> (Mon - Sun)
                </div>
            </div>
        </div>

        <hr class="border-secondary my-4">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small text-muted">
            <p class="mb-2 mb-md-0">&copy; <?= date('Y') ?> <?= APP_NAME ?>. All Rights Reserved. Built for high performance & reliability.</p>
            <div class="d-flex gap-3">
                <a href="<?= BASE_URL ?>/about.php" class="text-muted">Privacy Policy</a>
                <a href="<?= BASE_URL ?>/about.php" class="text-muted">Terms of Service</a>
                <a href="<?= BASE_URL ?>/config/setup.php" class="text-danger">Database Setup</a>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5.3 Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Main App Script -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<?php if (isset($extraJs)): ?>
    <script src="<?= BASE_URL ?>/<?= ltrim($extraJs, '/') ?>"></script>
<?php endif; ?>

</body>
</html>
