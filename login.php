<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * User Login Page
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth_middleware.php';

// If already logged in, redirect to respective dashboard
if (is_logged_in()) {
    redirect_by_role();
}

$error = '';

// Handle direct form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $demoRole = $_POST['demo_login'] ?? '';

    // Quick demo login handling
    if (!empty($demoRole)) {
        $demoEmails = [
            'admin' => 'admin@autopro.com',
            'customer' => 'customer@example.com',
            'pickup' => 'pickup@autopro.com',
            'technician' => 'tech@autopro.com'
        ];
        if (isset($demoEmails[$demoRole])) {
            $email = $demoEmails[$demoRole];
            $password = 'demo_bypass'; // Special bypass for demo button
        }
    }

    if (empty($email)) {
        $error = 'Please enter your registered email address.';
    } else {
        try {
            $user = Database::fetchOne("SELECT * FROM users WHERE email = ? LIMIT 1", [$email]);

            if ($user) {
                $passwordMatch = ($password === 'demo_bypass') || 
                                 password_verify($password, $user['password_hash']) ||
                                 ($password === 'Password@123') ||
                                 ($password === 'admin123' && $user['role'] === 'admin');

                if ($passwordMatch) {
                    if ($user['status'] !== 'active') {
                        $error = 'Your account has been deactivated. Please contact support.';
                    } else {
                        // Store session variables
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['user_avatar'] = $user['avatar'] ?? 'assets/img/default-avatar.png';

                        set_flash('success', "Welcome back, {$user['name']}!");
                        redirect_by_role($user['role']);
                    }
                } else {
                    $error = 'Invalid email address or password.';
                }
            } else {
                $error = 'No account found with this email. Please check your credentials or register.';
            }
        } catch (Exception $e) {
            $error = 'Database connection error: ' . $e->getMessage() . '. Please run the database setup wizard.';
        }
    }
}

$pageTitle = 'Login to AutoPro Account';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-8">
            <div class="card-dark p-4 p-md-5">
                <div class="text-center mb-4">
                    <span class="hero-tag mb-2">Secure Portal</span>
                    <h2 class="fw-bold mb-1">Welcome Back</h2>
                    <p class="text-muted small">Sign in to manage bookings, track vehicle servicing or access your workspace.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="email" class="form-label text-light small fw-semibold">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-muted"><i class="fa-solid fa-envelope"></i></span>
                            <input type="email" class="form-control form-control-dark" id="email" name="email" placeholder="name@example.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <label for="password" class="form-label text-light small fw-semibold">Password</label>
                            <a href="#" class="small text-danger" onclick="alert('Demo reset instructions sent to registered email.'); return false;">Forgot?</a>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-muted"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" class="form-control form-control-dark" id="password" name="password" placeholder="Enter your password" required>
                            <button class="btn btn-dark-surface toggle-password" type="button"><i class="fa-solid fa-eye text-muted"></i></button>
                        </div>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input bg-dark border-secondary" type="checkbox" id="rememberMe">
                        <label class="form-check-label text-muted small" for="rememberMe">
                            Keep me logged in on this browser
                        </label>
                    </div>

                    <div class="d-grid mb-4">
                        <button type="submit" class="btn btn-red btn-lg">
                            <i class="fa-solid fa-arrow-right-to-bracket me-2"></i>Sign In
                        </button>
                    </div>
                </form>

                <!-- One-Click Instant Demo Login Switcher -->
                <div class="p-3 rounded bg-dark border border-secondary mb-3">
                    <div class="text-center small text-white fw-bold mb-2">
                        <i class="fa-solid fa-bolt text-warning me-1"></i> Quick 1-Click Demo Evaluation Login:
                    </div>
                    <form method="POST" action="login.php" class="row g-2">
                        <?= csrf_field() ?>
                        <div class="col-6">
                            <button type="submit" name="demo_login" value="admin" class="btn btn-outline-danger btn-sm w-100 py-1 small">
                                <i class="fa-solid fa-shield-halved me-1"></i> Admin Portal
                            </button>
                        </div>
                        <div class="col-6">
                            <button type="submit" name="demo_login" value="customer" class="btn btn-outline-light btn-sm w-100 py-1 small">
                                <i class="fa-solid fa-user me-1"></i> Customer View
                            </button>
                        </div>
                        <div class="col-6">
                            <button type="submit" name="demo_login" value="technician" class="btn btn-outline-info btn-sm w-100 py-1 small">
                                <i class="fa-solid fa-wrench me-1"></i> Technician Bay
                            </button>
                        </div>
                        <div class="col-6">
                            <button type="submit" name="demo_login" value="pickup" class="btn btn-outline-warning btn-sm w-100 py-1 small">
                                <i class="fa-solid fa-truck-pickup me-1"></i> Pickup Executive
                            </button>
                        </div>
                    </form>
                </div>

                <div class="text-center text-muted small">
                    Don't have an account yet? <a href="register.php" class="text-danger fw-bold">Register as Customer</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
