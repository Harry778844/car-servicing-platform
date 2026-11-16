<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * Global Header Component
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helpers.php';

$currentUser = current_user();
$unreadNotifications = 0;
if ($currentUser) {
    try {
        $notifCount = Database::fetchOne(
            "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0",
            [$currentUser['id']]
        );
        $unreadNotifications = $notifCount['cnt'] ?? 0;
    } catch (Exception $e) {
        $unreadNotifications = 0;
    }
}
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' . APP_NAME : APP_NAME . ' - Doorstep Car Servicing & Workshop' ?></title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6.5.1 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Custom Theme Stylesheet -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <?php if (isset($extraCss)): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/<?= ltrim($extraCss, '/') ?>">
    <?php endif; ?>
</head>
<body>

<!-- Top Notification Bar / Helpline -->
<div class="bg-black py-1 border-bottom border-dark small text-muted">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <span class="text-white me-3"><i class="fa-solid fa-phone text-danger me-1"></i> 24x7 Roadside Hotline: <a href="tel:<?= APP_PHONE ?>" class="text-white fw-bold"><?= APP_PHONE ?></a></span>
            <span class="d-none d-md-inline"><i class="fa-solid fa-location-dot text-danger me-1"></i> Free Doorstep Pickup & Drop in NCR</span>
        </div>
        <div>
            <a href="<?= BASE_URL ?>/track-service.php" class="text-muted hover-red me-3"><i class="fa-solid fa-crosshairs text-danger me-1"></i> Live Tracker</a>
            <?php if (!$currentUser): ?>
                <a href="<?= BASE_URL ?>/config/setup.php" class="badge bg-secondary text-decoration-none"><i class="fa-solid fa-database me-1"></i> Database Wizard</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Main Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">
            <i class="fa-solid fa-car-tunnel"></i> AUTO<span>PRO</span>
        </a>
        
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'services.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/services.php">Services</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'book-service.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/book-service.php">Book Service</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'track-service.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/track-service.php">Track Vehicle</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/about.php">About Us</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'contact.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/contact.php">Contact</a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <?php if ($currentUser): ?>
                    <!-- Notification Bell -->
                    <div class="dropdown me-2">
                        <button class="btn btn-dark-surface position-relative p-2 rounded-circle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-bell text-white"></i>
                            <?php if ($unreadNotifications > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-dark">
                                    <?= $unreadNotifications ?>
                                </span>
                            <?php endif; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow-lg" style="min-width: 280px; border-color: var(--border-color);">
                            <li class="dropdown-header text-uppercase small fw-bold">Recent Notifications</li>
                            <li><hr class="dropdown-divider border-dark"></li>
                            <li><a class="dropdown-item small py-2" href="<?= BASE_URL ?>/track-service.php"><i class="fa-solid fa-wrench text-danger me-2"></i>Service progress updated</a></li>
                            <li><a class="dropdown-item small py-2" href="<?= BASE_URL ?>/customer/index.php"><i class="fa-solid fa-file-invoice text-info me-2"></i>New invoice available</a></li>
                            <li><hr class="dropdown-divider border-dark"></li>
                            <li><span class="dropdown-item-text text-center text-muted small">Notifications are synced live</span></li>
                        </ul>
                    </div>

                    <!-- User Profile Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-dark-surface dropdown-toggle d-flex align-items-center gap-2 py-1 px-3 rounded-pill" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="badge bg-danger text-uppercase px-2 py-1" style="font-size: 0.7rem;"><?= htmlspecialchars($currentUser['role']) ?></span>
                            <span class="fw-semibold text-white small"><?= htmlspecialchars($currentUser['name']) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow-lg" style="border-color: var(--border-color);">
                            <?php if ($currentUser['role'] === 'admin'): ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/index.php"><i class="fa-solid fa-gauge-high me-2 text-danger"></i>Admin Portal</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/bookings.php"><i class="fa-solid fa-calendar-days me-2 text-danger"></i>Manage Bookings</a></li>
                            <?php elseif ($currentUser['role'] === 'technician'): ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/technician/index.php"><i class="fa-solid fa-screwdriver-wrench me-2 text-danger"></i>Technician Bay</a></li>
                            <?php elseif ($currentUser['role'] === 'pickup'): ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/pickup/index.php"><i class="fa-solid fa-truck-pickup me-2 text-danger"></i>Driver Tasks</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/customer/index.php"><i class="fa-solid fa-gauge me-2 text-danger"></i>My Dashboard</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/customer/vehicles.php"><i class="fa-solid fa-car me-2 text-danger"></i>My Garage</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/customer/bookings.php"><i class="fa-solid fa-calendar-check me-2 text-danger"></i>Service History</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider border-dark"></li>
                            <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Sign Out</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-red btn-sm px-3">
                        <i class="fa-solid fa-user me-1"></i> Sign In
                    </a>
                    <a href="<?= BASE_URL ?>/book-service.php" class="btn btn-red btn-sm px-3 d-none d-sm-inline-block">
                        <i class="fa-solid fa-calendar-plus me-1"></i> Book Now
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Global Flash Alert Notification -->
<?php if ($flash): ?>
    <div class="container mt-3">
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?> me-2"></i>
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
<?php endif; ?>
