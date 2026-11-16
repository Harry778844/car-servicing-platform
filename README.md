# **AutoPro – Car Servicing & Maintenance Platform**

> A full-stack, responsive, multi-role car servicing and maintenance platform built with **PHP**, **MySQL**, **Bootstrap 5**, **JavaScript**, and **AJAX**, featuring contactless doorstep vehicle pickup & drop, real-time 12-stage telematics tracking, digital vehicle inspections, transparent estimate approvals, Razorpay payment processing, and role-based management portals.

---

## **Table of Contents**
1. [Project Overview & Business Model](#1-project-overview--business-model)
2. [Technology Stack](#2-technology-stack)
3. [System User Roles & Demo Credentials](#3-system-user-roles--demo-credentials)
4. [12-Stage Booking Lifecycle Management](#4-12-stage-booking-lifecycle-management)
5. [Database Architecture (17 Tables)](#5-database-architecture-17-tables)
6. [Website Modules & Pages Built](#6-website-modules--pages-built)
7. [Installation & Setup Instructions](#7-installation--setup-instructions)
8. [API & Gateway Integrations](#8-api--gateway-integrations)
9. [Security & Architectural Highlights](#9-security--architectural-highlights)

---

## **1. Project Overview & Business Model**

AutoPro solves the friction of traditional car repair by offering a **digital doorstep pickup & delivery car maintenance ecosystem**:
1. **Customer Registration & Login**: Customers register and maintain their digital garage (make, model, fuel type, transmission, reg number).
2. **Service Selection**: Choose from 10 distinct packages (General Service, Oil Change, Engine Repair, AC Service, Water Wash, Breakdown Assistance, Wheel Alignment, Brake Service, Battery Replacement, Dent & Painting).
3. **Pickup Scheduling**: Schedule preferred date, time window, doorstep address, and GPS coordinates.
4. **Admin Confirmation & Dispatch**: Admin assigns a dedicated Pickup Executive and Workshop Technician.
5. **Doorstep Collection**: Pickup driver logs odometer, fuel gauge, and exterior damage notes.
6. **Workshop Digital Inspection**: Master technicians perform a 60-point mechanical, fluid, and electrical inspection.
7. **Transparent Estimate Approval**: Itemized estimates are sent to the customer dashboard; work only starts upon customer digital approval.
8. **Precision Servicing**: Technicians execute repairs using OEM spares and log hours and parts replaced.
9. **Invoicing & Digital Checkout**: GST-compliant tax invoices generated with instant Razorpay online payment or Cash on Delivery.
10. **Delivery Handover & Review**: Vehicle is sanitized, washed, road-tested, and returned to the customer's doorstep.

---

## **2. Technology Stack**

| Technology | Purpose |
|---|---|
| **HTML5 & CSS3** | Semantic structure and custom **Premium Dark Theme** (`#0b0c10` black, carbon grey, `#ff2a3a` red accents, white) |
| **JavaScript (ES6+)** | Multi-step booking wizard, live price calculator, live telematics polling |
| **Bootstrap 5.3** | Fluid responsive layouts, grid systems, modals, and toasts |
| **Font Awesome 6.5** | High-density automotive iconography |
| **PHP 7.4 / 8.x** | Object-Oriented backend, PDO database layer, session auth, RBAC middleware |
| **MySQL / MariaDB** | Relational database with foreign key constraints, indexes, and UTF8MB4 encoding |
| **AJAX / Fetch API** | Asynchronous status queries and telematics polling |
| **Razorpay API** | Online checkout gateway simulation with UPI, Cards, and Netbanking |
| **Google Maps API** | GPS geocoding and workshop bay locator integration |
| **SMTP / Mailer** | In-app notification center and transaction logging |

---

## **3. System User Roles & Demo Credentials**

The platform features a **1-Click Demo Evaluation Switcher** on the login page (`login.php`) allowing instant testing of all 4 roles:

| Role | Demo Email | Password | Description & Permissions |
|---|---|---|---|
| **Administrator** | `admin@autopro.com` | `Password@123` | Full control over bookings queue, customer fleet, technician bay assignments, catalog pricing, and invoice settlements. |
| **Customer** | `customer@example.com` | `Password@123` | Book doorstep services, manage personal vehicle garage, track live 12-stage telematics, approve estimates, view invoices, and pay online. |
| **Pickup Executive** | `pickup@autopro.com` | `Password@123` | View assigned pickup and delivery routes, log doorstep odometer/fuel level, record damage notes, and mark vehicle drop-off handovers. |
| **Service Technician** | `tech@autopro.com` | `Password@123` | View vehicles on service bays, execute 60-point inspection checklists, formulate parts + labor estimates, and log completed repairs. |

---

## **4. 12-Stage Booking Lifecycle Management**

Every service order progresses through a strictly tracked audit pipeline:

```
[1. Booking Pending] ──> [2. Booking Confirmed] ──> [3. Pickup Assigned]
                                                                │
[6. Inspection in Progress] <── [5. Vehicle Received] <── [4. Vehicle Picked Up]
         │
         ▼
[7. Waiting for Customer Approval] ──(Approved)──> [8. Service in Progress]
                                                                │
[11. Out for Delivery] <── [10. Ready for Delivery] <── [9. Service Completed]
         │
         ▼
[12. Delivered] ──> Customer Review & Completed Tax Invoice
```

1. **`Booking Pending`**: Customer placed order online; awaiting workshop slot allocation.
2. **`Booking Confirmed`**: Admin validated requirements and scheduled the job.
3. **`Pickup Assigned`**: Pickup Executive assigned with scheduled time slot.
4. **`Vehicle Picked Up`**: Driver recorded initial odometer, fuel, and drove car to hub.
5. **`Vehicle Received at Workshop`**: Vehicle safely parked in designated service bay.
6. **`Inspection in Progress`**: Diagnostic scanner hooked up, fluids and mechanical check ongoing.
7. **`Waiting for Customer Approval`**: Itemized estimate dispatched to customer phone & portal.
8. **`Service in Progress`**: Estimate approved; master mechanics conducting repairs.
9. **`Service Completed`**: All scheduled works finished, fluids topped, post-service test passed.
10. **`Ready for Delivery`**: Exterior foam wash, interior vacuuming, and final quality audit approved.
11. **`Out for Delivery`**: Delivery driver en route back to customer's home/office.
12. **`Delivered`**: Handover verified with customer, payment settled, key returned.

---

## **5. Database Architecture (17 Tables)**

The system implements 17 relational tables in `database/schema.sql`:

1. **`users`**: Central authentication table (name, email, phone, password_hash, role: `admin`, `customer`, `pickup`, `technician`).
2. **`customers`**: Customer profile details (user_id, address, city, state, pincode, alternate_phone).
3. **`employees`**: Staff profiles (user_id, employee_code, designation, department: `pickup_delivery`, `workshop_technician`, status).
4. **`vehicles`**: Customer garage (make, model, year, reg_number, fuel_type, transmission, mileage, rc_number).
5. **`services`**: Service catalog (code, name, category, description, estimated_time, base_price, icon, badge).
6. **`bookings`**: Central booking order (booking_number, customer_id, vehicle_id, preferred_pickup_date, pickup_address, current_status, total_amount, payment_status).
7. **`booking_services`**: Many-to-many service mapping per booking order with individual package pricing.
8. **`pickup_requests`**: Doorstep logistics tracking (scheduled_time, pickup_odometer, drop_odometer, fuel_level, driver signature).
9. **`vehicle_inspections`**: Comprehensive 60-point technical checklist (fuel, odometer, exterior, tyres, battery voltage, oil status, coolant, remarks).
10. **`inspection_photos`**: High-resolution damage, engine bay, and underbody verification photos.
11. **`service_estimates`**: Quotation breakdown (parts_total, labor_total, tax_amount, discount, grand_total, customer_approval_status).
12. **`service_records`**: Work log (technician_id, action_taken, parts_replaced, labor hours spent).
13. **`status_history`**: Immutable audit log of all 12 transitions with timestamps and user remarks.
14. **`invoices`**: GST compliant tax invoices (subtotal, CGST 9%, SGST 9%, discount, final_amount, status).
15. **`payments`**: Transaction records (transaction_id, payment_gateway: `razorpay`, `cash`, `upi`, amount, status).
16. **`reviews`**: Verified customer ratings (1-5 stars, feedback comments, admin responses).
17. **`notifications`**: Real-time user alert dispatch system (title, message, read flag, route link).

---

## **6. Website Modules & Pages Built**

### **Public & Customer Facing Pages**
- **`index.php`**: High-impact automotive homepage with quick vehicle tracker, service grid, 4-step workflow, emergency SOS helpline, and customer testimonials.
- **`about.php`**: Workshop credentials, ASE technician standards, core values, and workshop bay gallery.
- **`services.php`**: Full catalog of all 10 service categories with itemized "What's Included" checklists, duration benchmarks, and base pricing.
- **`book-service.php`**: 4-step interactive wizard:
  - *Step 1*: Vehicle Selection (Choose from garage or add new).
  - *Step 2*: Multi-Service Selection with real-time dynamic total & GST computation.
  - *Step 3*: Pickup Schedule, Address, and GPS Geolocation detection.
  - *Step 4*: Review & Confirmation with zero upfront charge assurance.
- **`track-service.php`**: 12-stage interactive visual timeline showing live step pulse, assigned crew phone numbers, inspection results, and pending estimate approval triggers.
- **`payment.php`**: Secure 256-bit SSL encrypted checkout featuring Razorpay Quick Checkout simulation and Cash on Delivery option.
- **`invoice.php`**: GST-compliant printable & downloadable tax invoice with dedicated `@media print` styling.
- **`contact.php`**: Direct contact enquiry form, emergency 24x7 breakdown contacts, Google Maps location box, and interactive FAQ accordion.
- **`login.php`**: Unified secure login with 1-click evaluation demo account buttons.
- **`register.php`**: Customer registration with client & server-side validation.
- **`logout.php`**: Secure session invalidation and cookie clearing.

### **Customer Portal (`/customer`)**
- **`customer/index.php`**: Dashboard displaying garage overview, active servicing progress bar, quick actions, and recent service history.
- **`customer/vehicles.php`**: Digital garage management to register, review, and delete vehicles.
- **`customer/bookings.php`**: Complete service orders list with status filter tabs (`All`, `Active`, `Completed`) and invoice download buttons.

### **Admin Command Center (`/admin`)**
- **`admin/index.php`**: Executive KPI cards (Total Revenue, Active Jobs, Bookings, Registered Customers), pipeline breakdown, and recent orders.
- **`admin/bookings.php`**: Order management pipeline with search, status filters, staff assignment dropdowns, and manual status override modal.
- **`admin/customers.php`**: Customer directory displaying contact details, registered vehicles, and booking history.
- **`admin/employees.php`**: Staff directory for technicians and pickup drivers with availability tracking and new employee onboarding.
- **`admin/services.php`**: Pricing and catalog manager to adjust base rates, duration, and descriptions.
- **`admin/invoices.php`**: Invoicing and payment settlement terminal with mark-as-paid actions.

### **Pickup Executive Terminal (`/pickup`)**
- **`pickup/index.php`**: Route assignments, doorstep navigation details, odometer/fuel gauge entry modal, and handover verification.

### **Service Technician Bay Terminal (`/technician`)**
- **`technician/index.php`**: Service bay queue, technician actions (`Start Inspection`, `Create Estimate`, `Mark Completed`).
- **`technician/inspection.php`**: Multi-point inspection checklist for brakes, oil, battery, tyres, and scratches.
- **`technician/estimate.php`**: Interactive quotation builder calculating parts, labor, discount, and 18% GST before customer dispatch.

---

## **7. Installation & Setup Instructions**

### **Option A: 1-Click Browser Setup (Recommended)**
1. Place the project folder into your web root (e.g., `C:/xampp/htdocs/Project` or `/var/www/html/Project`).
2. Start **Apache** and **MySQL** in your XAMPP or WAMP control panel.
3. Open your web browser and navigate to:
   ```
   http://localhost/Project/config/setup.php
   ```
4. Select **MySQL** (or **SQLite** for zero-configuration standalone testing) and click:
   **"Install Database & Seed Demo Data"**.
5. All 17 tables and seed data will be created in seconds!
6. Click **"Go to Login"** and use any of the 1-click demo buttons to start evaluating.

### **Option B: Manual MySQL Import via phpMyAdmin / CLI**
1. Open phpMyAdmin (`http://localhost/phpmyadmin`).
2. Create a database named `carservice_db` (or let `schema.sql` create it).
3. Import `database/schema.sql`.
4. Import `database/seed.sql`.
5. Verify database credentials in `config/config.php`:
   ```php
   define('DB_HOST', '127.0.0.1');
   define('DB_PORT', '3306');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'carservice_db');
   ```

---

## **8. API & Gateway Integrations**

- **Razorpay Checkout (`api/payments.php`)**: Standard integration using `RAZORPAY_KEY_ID` and signature verification simulation.
- **Google Maps (`config/config.php` & `book-service.php`)**: Geolocation API integration to capture customer parking coordinates.
- **Telematics API (`api/bookings.php`)**: Real-time JSON endpoint for live status polling on the vehicle tracking page.

---

## **9. Security & Architectural Highlights**

- **PDO Prepared Statements**: 100% of database queries use parameterized prepared statements preventing SQL injection.
- **CSRF Protection**: All POST forms include cryptographic anti-CSRF token verification (`verify_csrf()`).
- **Role-Based Access Control (RBAC)**: Enforced via `require_role()` middleware on all administrative, driver, technician, and customer endpoints.
- **Password Security**: Passwords hashed using industry-standard `PASSWORD_BCRYPT` with salting.
- **Input Sanitization**: Multi-level sanitization using `htmlspecialchars()` with UTF-8 encoding.
- **Zero-Friction Fallback**: Intelligent dual-driver architecture (`Database.php`) supporting both MySQL and SQLite local preview.

---

*Developed for AutoPro Car Servicing & Maintenance Platform.*
