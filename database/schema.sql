-- ===================================================================
-- AutoPro Car Servicing & Maintenance Platform
-- Database Schema: carservice_db
-- 17 Core Tables for Full Lifecycle Management
-- ===================================================================

CREATE DATABASE IF NOT EXISTS `carservice_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `carservice_db`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `invoices`;
DROP TABLE IF EXISTS `status_history`;
DROP TABLE IF EXISTS `service_records`;
DROP TABLE IF EXISTS `service_estimates`;
DROP TABLE IF EXISTS `inspection_photos`;
DROP TABLE IF EXISTS `vehicle_inspections`;
DROP TABLE IF EXISTS `pickup_requests`;
DROP TABLE IF EXISTS `booking_services`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `services`;
DROP TABLE IF EXISTS `vehicles`;
DROP TABLE IF EXISTS `employees`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users Table (Core Authentication)
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('customer', 'admin', 'pickup', 'technician') NOT NULL DEFAULT 'customer',
  `avatar` VARCHAR(255) DEFAULT 'assets/img/default-avatar.png',
  `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
  `reset_token` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Customers Table (Profile Details)
CREATE TABLE `customers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `address` TEXT DEFAULT NULL,
  `city` VARCHAR(80) DEFAULT NULL,
  `state` VARCHAR(80) DEFAULT NULL,
  `pincode` VARCHAR(12) DEFAULT NULL,
  `alternate_phone` VARCHAR(20) DEFAULT NULL,
  `emergency_contact` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Employees Table (Pickup Executives and Service Technicians)
CREATE TABLE `employees` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `employee_code` VARCHAR(30) NOT NULL UNIQUE,
  `designation` VARCHAR(80) NOT NULL,
  `department` ENUM('pickup_delivery', 'workshop_technician', 'management') NOT NULL,
  `license_number` VARCHAR(50) DEFAULT NULL,
  `qualification` VARCHAR(100) DEFAULT NULL,
  `joining_date` DATE NOT NULL,
  `status` ENUM('available', 'on_job', 'leave', 'inactive') NOT NULL DEFAULT 'available',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Vehicles Table
CREATE TABLE `vehicles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `make` VARCHAR(60) NOT NULL,
  `model` VARCHAR(60) NOT NULL,
  `year` INT NOT NULL,
  `reg_number` VARCHAR(30) NOT NULL UNIQUE,
  `fuel_type` ENUM('Petrol', 'Diesel', 'Electric', 'Hybrid', 'CNG') NOT NULL DEFAULT 'Petrol',
  `transmission` ENUM('Manual', 'Automatic', 'CVT', 'DCT') NOT NULL DEFAULT 'Manual',
  `color` VARCHAR(40) DEFAULT NULL,
  `mileage` INT DEFAULT 0,
  `rc_number` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
  INDEX `idx_vehicles_reg` (`reg_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Services Table
CREATE TABLE `services` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(30) NOT NULL UNIQUE,
  `name` VARCHAR(120) NOT NULL,
  `category` ENUM('Periodic Maintenance', 'Mechanical & Electrical', 'Body & Paint', 'Emergency Assistance', 'Detailing & Wash') NOT NULL,
  `description` TEXT NOT NULL,
  `estimated_time` VARCHAR(50) NOT NULL DEFAULT '2-3 Hours',
  `base_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `icon` VARCHAR(60) DEFAULT 'fa-wrench',
  `badge` VARCHAR(40) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Bookings Table (12 Status Workflow)
CREATE TABLE `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_number` VARCHAR(30) NOT NULL UNIQUE,
  `customer_id` INT NOT NULL,
  `vehicle_id` INT NOT NULL,
  `booking_date` DATE NOT NULL,
  `preferred_pickup_date` DATE NOT NULL,
  `preferred_pickup_time` VARCHAR(20) NOT NULL,
  `pickup_address` TEXT NOT NULL,
  `pickup_city` VARCHAR(80) NOT NULL,
  `pickup_pincode` VARCHAR(12) NOT NULL,
  `pickup_latitude` DECIMAL(10, 8) DEFAULT NULL,
  `pickup_longitude` DECIMAL(11, 8) DEFAULT NULL,
  `current_status` ENUM(
    'Booking Pending',
    'Booking Confirmed',
    'Pickup Assigned',
    'Vehicle Picked Up',
    'Vehicle Received at Workshop',
    'Inspection in Progress',
    'Waiting for Customer Approval',
    'Service in Progress',
    'Service Completed',
    'Ready for Delivery',
    'Out for Delivery',
    'Delivered'
  ) NOT NULL DEFAULT 'Booking Pending',
  `assigned_pickup_id` INT DEFAULT NULL,
  `assigned_technician_id` INT DEFAULT NULL,
  `customer_notes` TEXT DEFAULT NULL,
  `technician_notes` TEXT DEFAULT NULL,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` ENUM('Unpaid', 'Pending', 'Partially Paid', 'Paid') NOT NULL DEFAULT 'Unpaid',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`assigned_pickup_id`) REFERENCES `employees`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`assigned_technician_id`) REFERENCES `employees`(`id`) ON DELETE SET NULL,
  INDEX `idx_bookings_status` (`current_status`),
  INDEX `idx_bookings_number` (`booking_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Booking Services Mapping Table
CREATE TABLE `booking_services` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `service_id` INT NOT NULL,
  `service_name` VARCHAR(120) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('Pending', 'In Progress', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Pickup Requests Table
CREATE TABLE `pickup_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL UNIQUE,
  `pickup_executive_id` INT DEFAULT NULL,
  `scheduled_time` DATETIME NOT NULL,
  `actual_pickup_time` DATETIME DEFAULT NULL,
  `actual_drop_time` DATETIME DEFAULT NULL,
  `pickup_odometer` INT DEFAULT NULL,
  `drop_odometer` INT DEFAULT NULL,
  `pickup_fuel_level` VARCHAR(20) DEFAULT NULL,
  `pickup_notes` TEXT DEFAULT NULL,
  `drop_notes` TEXT DEFAULT NULL,
  `customer_signature_pickup` TEXT DEFAULT NULL,
  `customer_signature_drop` TEXT DEFAULT NULL,
  `status` ENUM('Pending', 'Assigned', 'Started', 'Picked Up', 'Delivered_To_Workshop', 'Drop_Assigned', 'Out_For_Delivery', 'Delivered') NOT NULL DEFAULT 'Pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`pickup_executive_id`) REFERENCES `employees`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Vehicle Inspections Table
CREATE TABLE `vehicle_inspections` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `technician_id` INT NOT NULL,
  `fuel_level` VARCHAR(30) DEFAULT 'Half Tank',
  `odometer_reading` INT DEFAULT 0,
  `exterior_condition` ENUM('Good', 'Scratched', 'Dented', 'Damaged') NOT NULL DEFAULT 'Good',
  `interior_condition` ENUM('Clean', 'Moderate', 'Dirty', 'Stained') NOT NULL DEFAULT 'Clean',
  `tyre_condition` ENUM('Good', 'Worn Out', 'Needs Replacement') NOT NULL DEFAULT 'Good',
  `battery_voltage` VARCHAR(20) DEFAULT '12.6V (Normal)',
  `engine_oil_status` ENUM('Good', 'Low', 'Degraded', 'Critical') NOT NULL DEFAULT 'Good',
  `brake_fluid_status` ENUM('Good', 'Low', 'Needs Flush') NOT NULL DEFAULT 'Good',
  `coolant_level` ENUM('Adequate', 'Low', 'Leaking') NOT NULL DEFAULT 'Adequate',
  `scratches_dents_notes` TEXT DEFAULT NULL,
  `general_remarks` TEXT DEFAULT NULL,
  `inspection_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`technician_id`) REFERENCES `employees`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Inspection Photos Table
CREATE TABLE `inspection_photos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `inspection_id` INT NOT NULL,
  `photo_url` VARCHAR(255) NOT NULL,
  `photo_type` ENUM('exterior', 'interior', 'damage', 'tyre', 'engine', 'odometer') NOT NULL DEFAULT 'exterior',
  `caption` VARCHAR(255) DEFAULT NULL,
  `uploaded_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`inspection_id`) REFERENCES `vehicle_inspections`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Service Estimates Table
CREATE TABLE `service_estimates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `estimate_number` VARCHAR(30) NOT NULL UNIQUE,
  `parts_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `labor_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `grand_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `estimate_breakdown` JSON DEFAULT NULL,
  `customer_approval_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `customer_remarks` TEXT DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Service Records Table (Service History & Action log)
CREATE TABLE `service_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `technician_id` INT NOT NULL,
  `service_name` VARCHAR(150) NOT NULL,
  `action_taken` TEXT NOT NULL,
  `parts_replaced` TEXT DEFAULT NULL,
  `hours_spent` DECIMAL(4,1) DEFAULT 1.0,
  `completed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `notes` TEXT DEFAULT NULL,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`technician_id`) REFERENCES `employees`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Status History Table (Audit Trail of the 12 Statuses)
CREATE TABLE `status_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `status` VARCHAR(60) NOT NULL,
  `remarks` TEXT DEFAULT NULL,
  `updated_by_user_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`updated_by_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_status_hist_booking` (`booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Invoices Table
CREATE TABLE `invoices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_number` VARCHAR(30) NOT NULL UNIQUE,
  `booking_id` INT NOT NULL,
  `customer_id` INT NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax_rate` DECIMAL(5,2) NOT NULL DEFAULT 18.00,
  `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `final_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('unpaid', 'paid', 'partially_paid', 'cancelled') NOT NULL DEFAULT 'unpaid',
  `due_date` DATE NOT NULL,
  `issued_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE RESTRICT,
  INDEX `idx_invoices_num` (`invoice_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Payments Table
CREATE TABLE `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_id` INT NOT NULL,
  `booking_id` INT NOT NULL,
  `transaction_id` VARCHAR(100) NOT NULL UNIQUE,
  `payment_gateway` ENUM('razorpay', 'cash', 'upi', 'card', 'bank_transfer') NOT NULL DEFAULT 'razorpay',
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
  `status` ENUM('pending', 'successful', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
  `payment_response` TEXT DEFAULT NULL,
  `payment_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  INDEX `idx_payments_trans` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Reviews Table
CREATE TABLE `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `customer_id` INT NOT NULL,
  `rating` TINYINT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `title` VARCHAR(120) NOT NULL,
  `comment` TEXT NOT NULL,
  `response_from_admin` TEXT DEFAULT NULL,
  `is_published` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Notifications Table
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('info', 'success', 'warning', 'danger') NOT NULL DEFAULT 'info',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `link` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_notif_user` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
