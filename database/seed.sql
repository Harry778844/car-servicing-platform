-- ===================================================================
-- AutoPro Car Servicing & Maintenance Platform
-- Seed Data: Sample Users, Services, Vehicles, Bookings & Workflows
-- ===================================================================

USE `carservice_db`;

-- Default password for all seed accounts: Password@123
-- Hash generated via password_hash('Password@123', PASSWORD_BCRYPT)
-- $2y$10$EIXhI/7t1g2yQ5Lp01yZte5V876wz4kGfV8U4O8d4C2P9j5M4.h1e

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password_hash`, `role`, `status`) VALUES
(1, 'Super Administrator', 'admin@autopro.com', '+91 98765 00001', '$2y$10$1rYnU59m4zW7JtP3V1fHKeXvB9aKx2Vv7fB4b1C3d5E6F7G8H9I0.', 'admin', 'active'),
(2, 'Rahul Sharma (Customer)', 'customer@example.com', '+91 98765 00002', '$2y$10$1rYnU59m4zW7JtP3V1fHKeXvB9aKx2Vv7fB4b1C3d5E6F7G8H9I0.', 'customer', 'active'),
(3, 'Vikram Singh (Driver)', 'pickup@autopro.com', '+91 98765 00003', '$2y$10$1rYnU59m4zW7JtP3V1fHKeXvB9aKx2Vv7fB4b1C3d5E6F7G8H9I0.', 'pickup', 'active'),
(4, 'Manoj Verma (Technician)', 'tech@autopro.com', '+91 98765 00004', '$2y$10$1rYnU59m4zW7JtP3V1fHKeXvB9aKx2Vv7fB4b1C3d5E6F7G8H9I0.', 'technician', 'active'),
(5, 'Priya Patel (Customer)', 'priya@example.com', '+91 98765 00005', '$2y$10$1rYnU59m4zW7JtP3V1fHKeXvB9aKx2Vv7fB4b1C3d5E6F7G8H9I0.', 'customer', 'active'),
(6, 'Amit Kumar (Technician)', 'amit.tech@autopro.com', '+91 98765 00006', '$2y$10$1rYnU59m4zW7JtP3V1fHKeXvB9aKx2Vv7fB4b1C3d5E6F7G8H9I0.', 'technician', 'active'),
(7, 'Suresh Reddy (Driver)', 'suresh.pickup@autopro.com', '+91 98765 00007', '$2y$10$1rYnU59m4zW7JtP3V1fHKeXvB9aKx2Vv7fB4b1C3d5E6F7G8H9I0.', 'pickup', 'active');

-- Customers details
INSERT INTO `customers` (`id`, `user_id`, `address`, `city`, `state`, `pincode`, `alternate_phone`, `emergency_contact`) VALUES
(1, 2, 'Flat 402, Skyline Towers, Sector 45', 'Gurugram', 'Haryana', '122003', '+91 98111 22334', 'Dr. Sharma (+91 98111 22335)'),
(2, 5, 'B-12, Green Park Avenue', 'South Delhi', 'Delhi', '110016', '+91 98222 33445', 'Anand Patel (+91 98222 33446)');

-- Employees details
INSERT INTO `employees` (`id`, `user_id`, `employee_code`, `designation`, `department`, `license_number`, `qualification`, `joining_date`, `status`) VALUES
(1, 3, 'EMP-DRV-101', 'Senior Pickup Specialist', 'pickup_delivery', 'DL-04201800921', 'Commercial Heavy/LMV License', '2023-01-15', 'available'),
(2, 7, 'EMP-DRV-102', 'Pickup & Delivery Executive', 'pickup_delivery', 'DL-04202000543', 'LMV Transport Validated', '2023-06-20', 'available'),
(3, 4, 'EMP-TECH-201', 'Master Automobile Technician', 'workshop_technician', 'CERT-ASE-9921', 'B.Tech Automobile & Bosch Certified', '2022-03-10', 'available'),
(4, 6, 'EMP-TECH-202', 'Diagnostic & AC Specialist', 'workshop_technician', 'CERT-BOSCH-331', 'Diploma Auto Engg', '2022-08-01', 'available');

-- Vehicles
INSERT INTO `vehicles` (`id`, `customer_id`, `make`, `model`, `year`, `reg_number`, `fuel_type`, `transmission`, `color`, `mileage`, `rc_number`) VALUES
(1, 1, 'Hyundai', 'Creta SX (O)', 2021, 'HR 26 DQ 8899', 'Diesel', 'Automatic', 'Phantom Black', 34200, 'RC-HR26-2021-0099'),
(2, 1, 'Honda', 'City ZX', 2019, 'DL 3C CS 4521', 'Petrol', 'Manual', 'Radiant Red', 48500, 'RC-DL03-2019-8812'),
(3, 2, 'Tata', 'Nexon EV Empowered', 2023, 'DL 8C AR 1234', 'Electric', 'Automatic', 'Pristine White', 15800, 'RC-DL08-2023-4411');

-- The 10 Core Services Offered
INSERT INTO `services` (`id`, `code`, `name`, `category`, `description`, `estimated_time`, `base_price`, `is_active`, `icon`, `badge`) VALUES
(1, 'SRV-GEN', 'General Service', 'Periodic Maintenance', 'Comprehensive 60-point checkup, engine oil flush & replacement, oil filter, air filter cleaning, fluid top-ups and spark plug inspection.', '3-4 Hours', 2999.00, 1, 'fa-tools', 'Bestseller'),
(2, 'SRV-OIL', 'Oil Change', 'Periodic Maintenance', 'Synthetic or semi-synthetic premium engine oil change, genuine OEM oil filter replacement and sump plug washer renewal.', '1-2 Hours', 1499.00, 1, 'fa-oil-can', 'Popular'),
(3, 'SRV-ENG', 'Engine Repair', 'Mechanical & Electrical', 'Complete computerized engine OBD-II diagnostics, compression check, timing belt check, injector tuning and cylinder inspection.', '5-8 Hours', 4999.00, 1, 'fa-cogs', 'Specialist'),
(4, 'SRV-AC', 'AC Service', 'Periodic Maintenance', 'AC gas recharge (R134a/R1234yf), cabin filter cleaning/replacement, condenser coil wash and cooling duct antibacterial sanitization.', '2-3 Hours', 1999.00, 1, 'fa-snowflake', 'Trending'),
(5, 'SRV-WASH', 'Water Wash', 'Detailing & Wash', 'High-pressure foam exterior wash, underbody chassis cleaning, tyre dressing, interior vacuuming and dashboard polish.', '1 Hour', 699.00, 1, 'fa-shower', 'Quick Service'),
(6, 'SRV-BRKDWN', 'Breakdown Assistance', 'Emergency Assistance', '24x7 Roadside emergency assistance: battery jumpstart, flat tyre repair, emergency fuel delivery and towing to nearest workshop.', 'Within 45 Min', 999.00, 1, 'fa-truck-pickup', '24/7 Support'),
(7, 'SRV-WHL', 'Wheel Alignment', 'Periodic Maintenance', '3D computerized wheel alignment, electronic laser dynamic wheel balancing and tyre rotation for optimal tread life.', '1-2 Hours', 899.00, 1, 'fa-compass', 'Essential'),
(8, 'SRV-BRAKE', 'Brake Service', 'Mechanical & Electrical', 'Front and rear brake pad inspection, caliper degreasing, disc rotor skimming, brake fluid bleeding and handbrake calibration.', '2-3 Hours', 1299.00, 1, 'fa-circle-notch', 'Safety'),
(9, 'SRV-BATT', 'Battery Replacement', 'Mechanical & Electrical', 'Automotive battery health check, terminal grease coating, alternator voltage testing and doorstep new battery installation with warranty.', '1 Hour', 3499.00, 1, 'fa-car-battery', 'Warranty'),
(10, 'SRV-DENT', 'Dent & Painting', 'Body & Paint', 'Paintless dent removal, computerized spectrophotometer paint matching, grade-A primer, two-coat bake-oven metallic painting and clear gloss finish.', '1-2 Days', 2499.00, 1, 'fa-spray-can', 'Premium Finish');

-- Sample Bookings across key statuses
INSERT INTO `bookings` (`id`, `booking_number`, `customer_id`, `vehicle_id`, `booking_date`, `preferred_pickup_date`, `preferred_pickup_time`, `pickup_address`, `pickup_city`, `pickup_pincode`, `current_status`, `assigned_pickup_id`, `assigned_technician_id`, `customer_notes`, `technician_notes`, `total_amount`, `payment_status`) VALUES
(1, 'BK-2026-001', 1, 1, '2026-11-14', '2026-11-15', '09:30 AM', 'Flat 402, Skyline Towers, Sector 45', 'Gurugram', '122003', 'Service in Progress', 1, 3, 'Noticing slight vibration during braking above 70 km/h. Please check AC cooling as well.', 'Brake pads worn to 20%; discs cleaned and skimming required. AC filter clogged.', 4998.00, 'Unpaid'),
(2, 'BK-2026-002', 1, 2, '2026-11-10', '2026-11-11', '10:00 AM', 'Flat 402, Skyline Towers, Sector 45', 'Gurugram', '122003', 'Delivered', 1, 4, 'Annual scheduled oil change and foam water wash.', 'General service completed smoothly. Oil and filters replaced.', 2198.00, 'Paid'),
(3, 'BK-2026-003', 2, 3, '2026-11-16', '2026-11-17', '11:00 AM', 'B-12, Green Park Avenue', 'South Delhi', '110016', 'Booking Confirmed', 2, 3, 'Wheel alignment and full water wash requested before weekend trip.', NULL, 1598.00, 'Unpaid'),
(4, 'BK-2026-004', 1, 1, '2026-11-16', '2026-11-16', '02:00 PM', 'Sector 45 Market', 'Gurugram', '122003', 'Inspection in Progress', 1, 3, 'AC blowing warm air intermittently.', 'Checking refrigerant pressure and compressor clutch.', 1999.00, 'Unpaid');

-- Booking Services mappings
INSERT INTO `booking_services` (`id`, `booking_id`, `service_id`, `service_name`, `price`, `status`) VALUES
(1, 1, 1, 'General Service', 2999.00, 'In Progress'),
(2, 1, 8, 'Brake Service', 1299.00, 'In Progress'),
(3, 1, 5, 'Water Wash', 699.00, 'Pending'),
(4, 2, 2, 'Oil Change', 1499.00, 'Completed'),
(5, 2, 5, 'Water Wash', 699.00, 'Completed'),
(6, 3, 7, 'Wheel Alignment', 899.00, 'Pending'),
(7, 3, 5, 'Water Wash', 699.00, 'Pending'),
(8, 4, 4, 'AC Service', 1999.00, 'Pending');

-- Pickup Requests
INSERT INTO `pickup_requests` (`id`, `booking_id`, `pickup_executive_id`, `scheduled_time`, `actual_pickup_time`, `actual_drop_time`, `pickup_odometer`, `drop_odometer`, `pickup_fuel_level`, `pickup_notes`, `status`) VALUES
(1, 1, 1, '2026-11-15 09:30:00', '2026-11-15 09:42:00', NULL, 34210, NULL, '75% (3/4 Tank)', 'Vehicle in neat condition. Minor bumper scrape on front right noted.', 'Delivered_To_Workshop'),
(2, 2, 1, '2026-11-11 10:00:00', '2026-11-11 10:15:00', '2026-11-11 18:30:00', 48512, 48526, '50% (Half)', 'All personal belongings handed back to customer.', 'Delivered'),
(3, 3, 2, '2026-11-17 11:00:00', NULL, NULL, NULL, NULL, NULL, 'Scheduled for tomorrow morning.', 'Assigned');

-- Vehicle Inspections
INSERT INTO `vehicle_inspections` (`id`, `booking_id`, `technician_id`, `fuel_level`, `odometer_reading`, `exterior_condition`, `interior_condition`, `tyre_condition`, `battery_voltage`, `engine_oil_status`, `brake_fluid_status`, `coolant_level`, `scratches_dents_notes`, `general_remarks`) VALUES
(1, 1, 3, '75%', 34210, 'Scratched', 'Clean', 'Good', '12.6V (Normal)', 'Degraded', 'Low', 'Adequate', 'Minor surface scratches on passenger front fender. No major dents.', 'Front brake pad thickness is below 3mm. Recommend replacement along with disc rotor skimming.'),
(2, 2, 4, '50%', 48512, 'Good', 'Clean', 'Good', '12.8V (Healthy)', 'Degraded', 'Good', 'Adequate', 'No notable damages.', 'Vehicle well maintained.');

-- Inspection Photos
INSERT INTO `inspection_photos` (`id`, `inspection_id`, `photo_url`, `photo_type`, `caption`, `uploaded_by`) VALUES
(1, 1, 'assets/img/demo/car-front.jpg', 'exterior', 'Front view upon arrival at workshop', 3),
(2, 1, 'assets/img/demo/car-brake.jpg', 'damage', 'Front brake pad wear measurement', 4),
(3, 1, 'assets/img/demo/car-engine.jpg', 'engine', 'Engine bay inspection before wash', 4),
(4, 2, 'assets/img/demo/car-clean.jpg', 'exterior', 'Final inspection post water wash', 4);

-- Service Estimates
INSERT INTO `service_estimates` (`id`, `booking_id`, `estimate_number`, `parts_total`, `labor_total`, `tax_amount`, `discount_amount`, `grand_total`, `estimate_breakdown`, `customer_approval_status`, `customer_remarks`, `approved_at`) VALUES
(1, 1, 'EST-2026-001', 2800.00, 2198.00, 899.64, 200.00, 5697.64, '{"items": [{"name": "Synthetic 5W30 Engine Oil (4L)", "cost": 1800}, {"name": "Bosch Front Brake Pad Set", "cost": 1000}, {"name": "Labor & Diagnostics", "cost": 2198}]}', 'approved', 'Approved. Please proceed with brake pad replacement.', '2026-11-15 11:30:00'),
(2, 2, 'EST-2026-002', 1200.00, 998.00, 395.64, 0.00, 2593.64, '{"items": [{"name": "Genuine Honda Oil Filter", "cost": 350}, {"name": "Castrol Magnatec Engine Oil", "cost": 850}, {"name": "Service Labor", "cost": 998}]}', 'approved', 'Approved immediately.', '2026-11-11 11:00:00');

-- Service Records
INSERT INTO `service_records` (`id`, `booking_id`, `technician_id`, `service_name`, `action_taken`, `parts_replaced`, `hours_spent`, `completed_at`, `notes`) VALUES
(1, 1, 3, 'Oil & Filter Replacement', 'Drained old engine oil, installed OEM filter and refilled with 4.2L 5W30 synthetic oil.', 'Engine Oil Filter, Synthetic Oil 5W30', 1.5, '2026-11-15 14:00:00', 'Engine sounds smooth, no oil leaks observed.'),
(2, 2, 4, 'Standard Service & Pressure Wash', 'Changed engine oil and executed full chassis pressure wash with interior vacuuming.', 'Oil Filter', 2.0, '2026-11-11 16:30:00', 'All scheduled work completed.');

-- Status History (Audit Trail)
INSERT INTO `status_history` (`id`, `booking_id`, `status`, `remarks`, `updated_by_user_id`, `created_at`) VALUES
(1, 1, 'Booking Pending', 'Customer submitted online booking request', 2, '2026-11-14 18:20:00'),
(2, 1, 'Booking Confirmed', 'Admin confirmed booking and scheduled pickup driver', 1, '2026-11-14 19:00:00'),
(3, 1, 'Pickup Assigned', 'Assigned to Vikram Singh (EMP-DRV-101)', 1, '2026-11-15 08:30:00'),
(4, 1, 'Vehicle Picked Up', 'Driver collected vehicle from customer residence', 3, '2026-11-15 09:42:00'),
(5, 1, 'Vehicle Received at Workshop', 'Vehicle checked into Service Bay 2', 4, '2026-11-15 10:20:00'),
(6, 1, 'Inspection in Progress', 'Comprehensive multi-point inspection started', 4, '2026-11-15 10:45:00'),
(7, 1, 'Waiting for Customer Approval', 'Detailed estimate sent to customer for brake pad replacement', 4, '2026-11-15 11:15:00'),
(8, 1, 'Service in Progress', 'Customer approved estimate; mechanical works in progress', 4, '2026-11-15 11:35:00'),
(9, 2, 'Booking Pending', 'Booking initiated', 2, '2026-11-10 12:00:00'),
(10, 2, 'Booking Confirmed', 'Booking confirmed', 1, '2026-11-10 13:00:00'),
(11, 2, 'Pickup Assigned', 'Assigned to driver', 1, '2026-11-11 08:30:00'),
(12, 2, 'Vehicle Picked Up', 'Vehicle picked up', 3, '2026-11-11 10:15:00'),
(13, 2, 'Vehicle Received at Workshop', 'At workshop bay', 4, '2026-11-11 10:50:00'),
(14, 2, 'Inspection in Progress', 'Inspection completed', 4, '2026-11-11 11:00:00'),
(15, 2, 'Waiting for Customer Approval', 'Estimate sent', 4, '2026-11-11 11:05:00'),
(16, 2, 'Service in Progress', 'Service started', 4, '2026-11-11 11:20:00'),
(17, 2, 'Service Completed', 'All servicing complete', 4, '2026-11-11 16:30:00'),
(18, 2, 'Ready for Delivery', 'Vehicle clean and final quality check passed', 1, '2026-11-11 17:00:00'),
(19, 2, 'Out for Delivery', 'Driver en route to customer home', 3, '2026-11-11 17:30:00'),
(20, 2, 'Delivered', 'Vehicle safely handed over to customer', 3, '2026-11-11 18:30:00');

-- Invoices
INSERT INTO `invoices` (`id`, `invoice_number`, `booking_id`, `customer_id`, `subtotal`, `tax_rate`, `tax_amount`, `discount`, `final_amount`, `status`, `due_date`, `issued_at`) VALUES
(1, 'INV-2026-0001', 2, 1, 2198.00, 18.00, 395.64, 0.00, 2593.64, 'paid', '2026-11-11', '2026-11-11 16:45:00'),
(2, 'INV-2026-0002', 1, 1, 4998.00, 18.00, 899.64, 200.00, 5697.64, 'unpaid', '2026-11-16', '2026-11-15 17:00:00');

-- Payments
INSERT INTO `payments` (`id`, `invoice_id`, `booking_id`, `transaction_id`, `payment_gateway`, `amount`, `currency`, `status`, `payment_response`, `payment_date`) VALUES
(1, 1, 2, 'pay_RZP_992384102', 'razorpay', 2593.64, 'INR', 'successful', '{"id": "pay_RZP_992384102", "method": "upi", "bank": "HDFC", "vpa": "customer@okhdfcbank"}', '2026-11-11 17:15:00');

-- Reviews
INSERT INTO `reviews` (`id`, `booking_id`, `customer_id`, `rating`, `title`, `comment`, `response_from_admin`, `is_published`) VALUES
(1, 2, 1, 5, 'Superb Pickup & Drop Service!', 'The driver arrived right on time, courteous inspection, and vehicle was delivered completely sanitized with glossy finish. Very transparent pricing.', 'Thank you Rahul! We are delighted to keep your Honda City in peak performance.', 1);

-- Notifications
INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `link`) VALUES
(1, 2, 'Service In Progress', 'Technicians are currently working on your Hyundai Creta (BK-2026-001).', 'info', 0, 'track-service.php?booking=BK-2026-001'),
(2, 2, 'Invoice Generated', 'Invoice INV-2026-0002 for Rs. 5,697.64 is generated. You can pay online or via cash on delivery.', 'warning', 0, 'payment.php?booking=BK-2026-001'),
(3, 1, 'New Booking Received', 'New booking request BK-2026-004 from customer Rahul Sharma requires confirmation.', 'info', 0, 'admin/bookings.php'),
(4, 3, 'Pickup Assignment', 'You have been assigned vehicle pickup for booking BK-2026-001.', 'success', 1, 'pickup/index.php'),
(5, 4, 'Inspection Task Assigned', 'Hyundai Creta has arrived in Bay 2 for inspection.', 'info', 1, 'technician/index.php');
