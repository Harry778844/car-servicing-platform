<?php
/**
 * AutoPro Car Servicing & Maintenance Platform
 * One-Click Database Setup & Seeder Wizard
 */

require_once __DIR__ . '/config.php';

$messages = [];
$status = 'idle';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install') {
    $engine = $_POST['engine'] ?? 'mysql';

    if ($engine === 'mysql') {
        try {
            $host = DB_HOST;
            $port = DB_PORT;
            $user = DB_USER;
            $pass = DB_PASS;
            $dbname = DB_NAME;

            // Connect to server without selecting database first
            $pdo = new PDO("mysql:host={$host};port={$port}", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $pdo->exec("USE `{$dbname}`;");

            // Read schema file
            $schemaFile = __DIR__ . '/../database/schema.sql';
            if (file_exists($schemaFile)) {
                $schemaSql = file_get_contents($schemaFile);
                $pdo->exec($schemaSql);
                $messages[] = ['type' => 'success', 'text' => "MySQL Database `{$dbname}` created and 17 tables generated successfully."];
            }

            // Read seed file
            $seedFile = __DIR__ . '/../database/seed.sql';
            if (file_exists($seedFile)) {
                $seedSql = file_get_contents($seedFile);
                $pdo->exec($seedSql);
                $messages[] = ['type' => 'success', 'text' => "Seed data injected successfully (Admin, Customers, Technicians, Pickups, Vehicles, Services & Bookings)."];
            }

            $status = 'success';
        } catch (Exception $e) {
            $messages[] = ['type' => 'danger', 'text' => "MySQL Setup Failed: " . $e->getMessage()];
            $status = 'failed';
        }
    } elseif ($engine === 'sqlite') {
        try {
            $sqlitePath = __DIR__ . '/../database/carservice.sqlite';
            if (file_exists($sqlitePath)) {
                @unlink($sqlitePath);
            }
            $pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $pdo->exec('PRAGMA foreign_keys = ON;');

            // Create SQLite compatible tables
            $sqliteSchema = "
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                phone TEXT NOT NULL,
                password_hash TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'customer',
                avatar TEXT DEFAULT 'assets/img/default-avatar.png',
                status TEXT NOT NULL DEFAULT 'active',
                reset_token TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE customers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL UNIQUE,
                address TEXT,
                city TEXT,
                state TEXT,
                pincode TEXT,
                alternate_phone TEXT,
                emergency_contact TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );
            CREATE TABLE employees (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL UNIQUE,
                employee_code TEXT NOT NULL UNIQUE,
                designation TEXT NOT NULL,
                department TEXT NOT NULL,
                license_number TEXT,
                qualification TEXT,
                joining_date DATE NOT NULL,
                status TEXT NOT NULL DEFAULT 'available',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );
            CREATE TABLE vehicles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                customer_id INTEGER NOT NULL,
                make TEXT NOT NULL,
                model TEXT NOT NULL,
                year INTEGER NOT NULL,
                reg_number TEXT NOT NULL UNIQUE,
                fuel_type TEXT NOT NULL DEFAULT 'Petrol',
                transmission TEXT NOT NULL DEFAULT 'Manual',
                color TEXT,
                mileage INTEGER DEFAULT 0,
                rc_number TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
            );
            CREATE TABLE services (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                code TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                category TEXT NOT NULL,
                description TEXT NOT NULL,
                estimated_time TEXT NOT NULL DEFAULT '2-3 Hours',
                base_price REAL NOT NULL DEFAULT 0.00,
                is_active INTEGER NOT NULL DEFAULT 1,
                icon TEXT DEFAULT 'fa-wrench',
                badge TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE bookings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                booking_number TEXT NOT NULL UNIQUE,
                customer_id INTEGER NOT NULL,
                vehicle_id INTEGER NOT NULL,
                booking_date DATE NOT NULL,
                preferred_pickup_date DATE NOT NULL,
                preferred_pickup_time TEXT NOT NULL,
                pickup_address TEXT NOT NULL,
                pickup_city TEXT NOT NULL,
                pickup_pincode TEXT NOT NULL,
                pickup_latitude REAL,
                pickup_longitude REAL,
                current_status TEXT NOT NULL DEFAULT 'Booking Pending',
                assigned_pickup_id INTEGER,
                assigned_technician_id INTEGER,
                customer_notes TEXT,
                technician_notes TEXT,
                total_amount REAL NOT NULL DEFAULT 0.00,
                payment_status TEXT NOT NULL DEFAULT 'Unpaid',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE booking_services (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                booking_id INTEGER NOT NULL,
                service_id INTEGER NOT NULL,
                service_name TEXT NOT NULL,
                price REAL NOT NULL DEFAULT 0.00,
                status TEXT NOT NULL DEFAULT 'Pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE pickup_requests (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                booking_id INTEGER NOT NULL UNIQUE,
                pickup_executive_id INTEGER,
                scheduled_time DATETIME NOT NULL,
                actual_pickup_time DATETIME,
                actual_drop_time DATETIME,
                pickup_odometer INTEGER,
                drop_odometer INTEGER,
                pickup_fuel_level TEXT,
                pickup_notes TEXT,
                drop_notes TEXT,
                customer_signature_pickup TEXT,
                customer_signature_drop TEXT,
                status TEXT NOT NULL DEFAULT 'Pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE vehicle_inspections (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                booking_id INTEGER NOT NULL,
                technician_id INTEGER NOT NULL,
                fuel_level TEXT DEFAULT 'Half Tank',
                odometer_reading INTEGER DEFAULT 0,
                exterior_condition TEXT DEFAULT 'Good',
                interior_condition TEXT DEFAULT 'Clean',
                tyre_condition TEXT DEFAULT 'Good',
                battery_voltage TEXT DEFAULT '12.6V',
                engine_oil_status TEXT DEFAULT 'Good',
                brake_fluid_status TEXT DEFAULT 'Good',
                coolant_level TEXT DEFAULT 'Adequate',
                scratches_dents_notes TEXT,
                general_remarks TEXT,
                inspection_date DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE inspection_photos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                inspection_id INTEGER NOT NULL,
                photo_url TEXT NOT NULL,
                photo_type TEXT DEFAULT 'exterior',
                caption TEXT,
                uploaded_by INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE service_estimates (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                booking_id INTEGER NOT NULL,
                estimate_number TEXT NOT NULL UNIQUE,
                parts_total REAL DEFAULT 0.00,
                labor_total REAL DEFAULT 0.00,
                tax_amount REAL DEFAULT 0.00,
                discount_amount REAL DEFAULT 0.00,
                grand_total REAL DEFAULT 0.00,
                estimate_breakdown TEXT,
                customer_approval_status TEXT DEFAULT 'pending',
                customer_remarks TEXT,
                approved_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE service_records (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                booking_id INTEGER NOT NULL,
                technician_id INTEGER NOT NULL,
                service_name TEXT NOT NULL,
                action_taken TEXT NOT NULL,
                parts_replaced TEXT,
                hours_spent REAL DEFAULT 1.0,
                completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                notes TEXT
            );
            CREATE TABLE status_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                booking_id INTEGER NOT NULL,
                status TEXT NOT NULL,
                remarks TEXT,
                updated_by_user_id INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE invoices (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                invoice_number TEXT NOT NULL UNIQUE,
                booking_id INTEGER NOT NULL,
                customer_id INTEGER NOT NULL,
                subtotal REAL DEFAULT 0.00,
                tax_rate REAL DEFAULT 18.00,
                tax_amount REAL DEFAULT 0.00,
                discount REAL DEFAULT 0.00,
                final_amount REAL DEFAULT 0.00,
                status TEXT DEFAULT 'unpaid',
                due_date DATE NOT NULL,
                issued_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE payments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                invoice_id INTEGER NOT NULL,
                booking_id INTEGER NOT NULL,
                transaction_id TEXT NOT NULL UNIQUE,
                payment_gateway TEXT DEFAULT 'razorpay',
                amount REAL DEFAULT 0.00,
                currency TEXT DEFAULT 'INR',
                status TEXT DEFAULT 'pending',
                payment_response TEXT,
                payment_date DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE reviews (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                booking_id INTEGER NOT NULL,
                customer_id INTEGER NOT NULL,
                rating INTEGER NOT NULL,
                title TEXT NOT NULL,
                comment TEXT NOT NULL,
                response_from_admin TEXT,
                is_published INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE notifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                message TEXT NOT NULL,
                type TEXT DEFAULT 'info',
                is_read INTEGER DEFAULT 0,
                link TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            ";

            $pdo->exec($sqliteSchema);
            $messages[] = ['type' => 'success', 'text' => "SQLite Database file initialized at `database/carservice.sqlite` with all 17 tables."];

            // Insert initial seed data into SQLite
            $hash = password_hash('Password@123', PASSWORD_BCRYPT);
            $pdo->exec("
                INSERT INTO users (id, name, email, phone, password_hash, role) VALUES
                (1, 'Super Administrator', 'admin@autopro.com', '+91 98765 00001', '{$hash}', 'admin'),
                (2, 'Rahul Sharma (Customer)', 'customer@example.com', '+91 98765 00002', '{$hash}', 'customer'),
                (3, 'Vikram Singh (Driver)', 'pickup@autopro.com', '+91 98765 00003', '{$hash}', 'pickup'),
                (4, 'Manoj Verma (Technician)', 'tech@autopro.com', '+91 98765 00004', '{$hash}', 'technician');

                INSERT INTO customers (id, user_id, address, city, state, pincode) VALUES
                (1, 2, 'Flat 402, Skyline Towers, Sector 45', 'Gurugram', 'Haryana', '122003');

                INSERT INTO employees (id, user_id, employee_code, designation, department, joining_date) VALUES
                (1, 3, 'EMP-DRV-101', 'Senior Pickup Specialist', 'pickup_delivery', '2023-01-15'),
                (2, 4, 'EMP-TECH-201', 'Master Automobile Technician', 'workshop_technician', '2022-03-10');

                INSERT INTO vehicles (id, customer_id, make, model, year, reg_number, fuel_type, transmission) VALUES
                (1, 1, 'Hyundai', 'Creta SX (O)', 2021, 'HR 26 DQ 8899', 'Diesel', 'Automatic'),
                (2, 1, 'Honda', 'City ZX', 2019, 'DL 3C CS 4521', 'Petrol', 'Manual');

                INSERT INTO services (id, code, name, category, description, estimated_time, base_price, icon, badge) VALUES
                (1, 'SRV-GEN', 'General Service', 'Periodic Maintenance', 'Comprehensive 60-point checkup, engine oil flush, fluid top-ups and spark plug inspection.', '3-4 Hours', 2999.00, 'fa-tools', 'Bestseller'),
                (2, 'SRV-OIL', 'Oil Change', 'Periodic Maintenance', 'Synthetic premium engine oil change, genuine OEM oil filter replacement and sump plug renewal.', '1-2 Hours', 1499.00, 'fa-oil-can', 'Popular'),
                (3, 'SRV-ENG', 'Engine Repair', 'Mechanical & Electrical', 'Complete computerized engine OBD-II diagnostics, compression check and injector tuning.', '5-8 Hours', 4999.00, 'fa-cogs', 'Specialist'),
                (4, 'SRV-AC', 'AC Service', 'Periodic Maintenance', 'AC gas recharge, cabin filter replacement, condenser wash and sanitization.', '2-3 Hours', 1999.00, 'fa-snowflake', 'Trending'),
                (5, 'SRV-WASH', 'Water Wash', 'Detailing & Wash', 'High-pressure foam exterior wash, underbody chassis cleaning, tyre polish and vacuuming.', '1 Hour', 699.00, 'fa-shower', 'Quick Service'),
                (6, 'SRV-BRKDWN', 'Breakdown Assistance', 'Emergency Assistance', '24x7 Roadside emergency assistance: battery jumpstart, tyre puncture, fuel delivery and towing.', 'Within 45 Min', 999.00, 'fa-truck-pickup', '24/7 Support'),
                (7, 'SRV-WHL', 'Wheel Alignment', 'Periodic Maintenance', '3D computerized wheel alignment, electronic laser dynamic wheel balancing and tyre rotation.', '1-2 Hours', 899.00, 'fa-compass', 'Essential'),
                (8, 'SRV-BRAKE', 'Brake Service', 'Mechanical & Electrical', 'Front and rear brake pad inspection, caliper degreasing, disc skimming and fluid bleeding.', '2-3 Hours', 1299.00, 'fa-circle-notch', 'Safety'),
                (9, 'SRV-BATT', 'Battery Replacement', 'Mechanical & Electrical', 'Battery health test, terminal grease, alternator voltage testing and doorstep new installation.', '1 Hour', 3499.00, 'fa-car-battery', 'Warranty'),
                (10, 'SRV-DENT', 'Dent & Painting', 'Body & Paint', 'Paintless dent removal, computerized spectrophotometer paint matching and bake-oven metallic painting.', '1-2 Days', 2499.00, 'fa-spray-can', 'Premium Finish');

                INSERT INTO bookings (id, booking_number, customer_id, vehicle_id, booking_date, preferred_pickup_date, preferred_pickup_time, pickup_address, pickup_city, pickup_pincode, current_status, assigned_pickup_id, assigned_technician_id, total_amount, payment_status) VALUES
                (1, 'BK-2026-001', 1, 1, '2026-11-14', '2026-11-15', '09:30 AM', 'Flat 402, Skyline Towers, Sector 45', 'Gurugram', '122003', 'Service in Progress', 1, 2, 4998.00, 'Unpaid'),
                (2, 'BK-2026-002', 1, 2, '2026-11-10', '2026-11-11', '10:00 AM', 'Flat 402, Skyline Towers, Sector 45', 'Gurugram', '122003', 'Delivered', 1, 2, 2198.00, 'Paid');

                INSERT INTO booking_services (booking_id, service_id, service_name, price) VALUES
                (1, 1, 'General Service', 2999.00),
                (1, 8, 'Brake Service', 1299.00),
                (1, 5, 'Water Wash', 699.00),
                (2, 2, 'Oil Change', 1499.00),
                (2, 5, 'Water Wash', 699.00);

                INSERT INTO status_history (booking_id, status, remarks, updated_by_user_id) VALUES
                (1, 'Booking Pending', 'Customer placed booking', 2),
                (1, 'Booking Confirmed', 'Admin approved', 1),
                (1, 'Pickup Assigned', 'Assigned to driver Vikram', 1),
                (1, 'Vehicle Picked Up', 'Vehicle picked up from home', 3),
                (1, 'Vehicle Received at Workshop', 'Arrived at workshop', 4),
                (1, 'Inspection in Progress', 'Multi-point checklist ongoing', 4),
                (1, 'Waiting for Customer Approval', 'Estimate sent', 4),
                (1, 'Service in Progress', 'Brake pads replacement in progress', 4);
            ");
            $messages[] = ['type' => 'success', 'text' => "Sample data seeded into SQLite database successfully."];
            $status = 'success';
        } catch (Exception $e) {
            $messages[] = ['type' => 'danger', 'text' => "SQLite Setup Failed: " . $e->getMessage()];
            $status = 'failed';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup Wizard - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background-color: #0f1015;
            color: #e2e8f0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .setup-card {
            background: #171922;
            border: 1px solid #282a36;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.6);
            width: 100%;
            max-width: 680px;
            padding: 2.5rem;
        }
        .brand-badge {
            color: #ff2a3a;
            font-size: 2.4rem;
            margin-bottom: 0.5rem;
        }
        .btn-red {
            background: linear-gradient(135deg, #e50914 0%, #b80710 100%);
            border: none;
            color: #fff;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .btn-red:hover {
            background: linear-gradient(135deg, #ff2a3a 0%, #d10813 100%);
            box-shadow: 0 4px 15px rgba(229, 9, 20, 0.4);
            color: #fff;
        }
        .card-inner {
            background: #1f222e;
            border: 1px solid #2e3244;
            border-radius: 10px;
            padding: 1.25rem;
        }
        .badge-step {
            background: #ff2a3a;
            color: white;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            margin-right: 8px;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="setup-card mx-auto">
        <div class="text-center mb-4">
            <i class="fa-solid fa-car-tunnel brand-badge"></i>
            <h2 class="fw-bold mb-1"><?= APP_NAME ?></h2>
            <p class="text-muted">Database Initialization & Seeder Wizard</p>
        </div>

        <?php if (!empty($messages)): ?>
            <?php foreach ($messages as $msg): ?>
                <div class="alert alert-<?= $msg['type'] ?> alert-dismissible fade show" role="alert">
                    <i class="fa-solid <?= $msg['type'] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?> me-2"></i>
                    <?= htmlspecialchars($msg['text']) ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($status === 'success'): ?>
            <div class="text-center my-4 p-4 rounded bg-success bg-opacity-10 border border-success">
                <i class="fa-solid fa-circle-check text-success fa-3x mb-3"></i>
                <h4 class="text-success fw-bold">Platform Ready!</h4>
                <p class="text-muted mb-4">All tables, constraints, services and demo accounts have been configured.</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="../login.php" class="btn btn-red"><i class="fa-solid fa-right-to-bracket me-2"></i>Go to Login</a>
                    <a href="../index.php" class="btn btn-outline-light"><i class="fa-solid fa-house me-2"></i>Homepage</a>
                </div>
            </div>
        <?php else: ?>
            <div class="card-inner mb-4">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-sliders text-danger me-2"></i>Current Configuration</h5>
                <ul class="list-unstyled mb-0 small">
                    <li class="mb-2"><strong>MySQL Host:</strong> <code><?= DB_HOST ?>:<?= DB_PORT ?></code></li>
                    <li class="mb-2"><strong>Database Name:</strong> <code><?= DB_NAME ?></code></li>
                    <li class="mb-2"><strong>MySQL User:</strong> <code><?= DB_USER ?></code></li>
                    <li class="mb-0"><strong>Tables to build:</strong> <code>17 relational tables with 12-stage statuses</code></li>
                </ul>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="install">
                
                <div class="mb-4">
                    <label class="form-label fw-semibold">Select Database Engine:</label>
                    <div class="form-check p-3 mb-2 rounded bg-dark border border-secondary">
                        <input class="form-check-input" type="radio" name="engine" id="engineMysql" value="mysql" checked>
                        <label class="form-check-label ms-2" for="engineMysql">
                            <strong>MySQL / MariaDB</strong> <span class="badge bg-danger ms-2">Recommended for XAMPP / WAMP</span>
                            <div class="text-muted small">Imports <code>database/schema.sql</code> and <code>database/seed.sql</code> into <?= DB_NAME ?>.</div>
                        </label>
                    </div>
                    <div class="form-check p-3 rounded bg-dark border border-secondary">
                        <input class="form-check-input" type="radio" name="engine" id="engineSqlite" value="sqlite">
                        <label class="form-check-label ms-2" for="engineSqlite">
                            <strong>SQLite Local File</strong> <span class="badge bg-secondary ms-2">Zero-config Local Dev</span>
                            <div class="text-muted small">Instantly creates <code>database/carservice.sqlite</code> without requiring MySQL daemon.</div>
                        </label>
                    </div>
                </div>

                <div class="card-inner mb-4">
                    <h6 class="fw-bold text-white mb-2"><i class="fa-solid fa-users me-2 text-danger"></i>Pre-configured Demo Accounts:</h6>
                    <div class="row small text-muted g-2">
                        <div class="col-6"><strong>Admin:</strong> admin@autopro.com</div>
                        <div class="col-6"><strong>Customer:</strong> customer@example.com</div>
                        <div class="col-6"><strong>Technician:</strong> tech@autopro.com</div>
                        <div class="col-6"><strong>Pickup Driver:</strong> pickup@autopro.com</div>
                        <div class="col-12 mt-2 text-info"><em>Default Password for all: <code>Password@123</code></em></div>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-red btn-lg">
                        <i class="fa-solid fa-play me-2"></i>Install Database & Seed Demo Data
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
