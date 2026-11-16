<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * Customer Registration Page
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth_middleware.php';

if (is_logged_in()) {
    redirect_by_role();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $city = trim($_POST['city'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $error = 'Please fill out all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters in length.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Check if email already exists
            $existing = Database::fetchOne("SELECT id FROM users WHERE email = ? LIMIT 1", [$email]);
            if ($existing) {
                $error = 'An account with this email address already exists. Please login instead.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                Database::execute(
                    "INSERT INTO users (name, email, phone, password_hash, role, status) VALUES (?, ?, ?, ?, 'customer', 'active')",
                    [$name, $email, $phone, $hash]
                );
                $userId = Database::lastInsertId();

                // Create customer record
                Database::execute(
                    "INSERT INTO customers (user_id, address, city) VALUES (?, ?, ?)",
                    [$userId, $address, $city]
                );

                // Auto-login newly registered customer
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'customer';
                $_SESSION['user_avatar'] = 'assets/img/default-avatar.png';

                // Send welcome notification
                send_notification(
                    $userId,
                    'Welcome to AutoPro Car Care',
                    'Your account has been created. Add your vehicle to schedule your first doorstep servicing pickup.',
                    'success',
                    'customer/vehicles.php'
                );

                set_flash('success', 'Registration successful! Welcome to AutoPro.');
                header('Location: ' . BASE_URL . '/customer/index.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Registration failed: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Create Customer Account';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-9">
            <div class="card-dark p-4 p-md-5">
                <div class="text-center mb-4">
                    <span class="hero-tag mb-2">Join AutoPro</span>
                    <h2 class="fw-bold mb-1">Customer Registration</h2>
                    <p class="text-muted small">Create an account to schedule doorstep pickup, view digital vehicle inspections and approve transparent estimates.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register.php">
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label class="form-label text-light small fw-semibold">Full Name *</label>
                            <input type="text" class="form-control form-control-dark" name="name" required placeholder="e.g. Rahul Sharma" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Email Address *</label>
                            <input type="email" class="form-control form-control-dark" name="email" required placeholder="e.g. rahul@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Mobile Number *</label>
                            <input type="tel" class="form-control form-control-dark" name="phone" required placeholder="e.g. +91 98765 43210" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">City</label>
                            <input type="text" class="form-control form-control-dark" name="city" placeholder="e.g. Gurugram, Delhi" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Residence Address</label>
                            <input type="text" class="form-control form-control-dark" name="address" placeholder="Flat, Street, Locality" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Password *</label>
                            <div class="input-group">
                                <input type="password" class="form-control form-control-dark" name="password" required placeholder="Min 6 characters">
                                <button class="btn btn-dark-surface toggle-password" type="button"><i class="fa-solid fa-eye text-muted"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-light small fw-semibold">Confirm Password *</label>
                            <div class="input-group">
                                <input type="password" class="form-control form-control-dark" name="confirm_password" required placeholder="Re-enter password">
                                <button class="btn btn-dark-surface toggle-password" type="button"><i class="fa-solid fa-eye text-muted"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input bg-dark border-secondary" type="checkbox" id="termsCheck" required checked>
                        <label class="form-check-label text-muted small" for="termsCheck">
                            I agree to the <a href="about.php" class="text-danger">Terms & Conditions</a> and free vehicle pickup policy.
                        </label>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-red btn-lg">
                            <i class="fa-solid fa-user-plus me-2"></i>Create Free Account
                        </button>
                    </div>

                    <div class="text-center text-muted small">
                        Already have an account? <a href="login.php" class="text-danger fw-bold">Sign In here</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
