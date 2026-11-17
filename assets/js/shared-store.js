/**
 * AutoPro - Shared Telematics & Data Store
 * Powers index.html, customer.html, employee.html, and admin.html
 * Synchronizes real-time state across browser tabs & pages via localStorage
 */

const INITIAL_SERVICES = [
  { id: 1, name: 'General Service', category: 'Periodic Maintenance', price: 2999, duration: '3-4 Hours', icon: 'fa-tools', badge: 'Bestseller', desc: 'Comprehensive 60-point checkup, engine oil flush, fluid top-ups and spark plug inspection.' },
  { id: 2, name: 'Oil Change', category: 'Periodic Maintenance', price: 1499, duration: '1-2 Hours', icon: 'fa-oil-can', badge: 'Popular', desc: 'Synthetic premium engine oil replacement, genuine OEM oil filter and sump plug renewal.' },
  { id: 3, name: 'Engine Repair', category: 'Mechanical & Electrical', price: 4999, duration: '5-8 Hours', icon: 'fa-cogs', badge: 'Specialist', desc: 'Computerized OBD-II scanner diagnostics, compression check, timing belt and injector tuning.' },
  { id: 4, name: 'AC Service', category: 'Periodic Maintenance', price: 1999, duration: '2-3 Hours', icon: 'fa-snowflake', badge: 'Trending', desc: 'AC refrigerant gas recharge, cabin pollen filter cleaning, condenser wash and sanitization.' },
  { id: 5, name: 'Water Wash', category: 'Detailing & Wash', price: 699, duration: '1 Hour', icon: 'fa-shower', badge: 'Quick Service', desc: 'High-pressure foam exterior wash, underbody chassis cleaning, tyre polish and vacuuming.' },
  { id: 6, name: 'Breakdown Assistance', category: 'Emergency Assistance', price: 999, duration: 'Within 45 Min', icon: 'fa-truck-pickup', badge: '24/7 Support', desc: '24x7 Roadside emergency assistance: battery jumpstart, tyre puncture repair and flatbed towing.' },
  { id: 7, name: 'Wheel Alignment', category: 'Periodic Maintenance', price: 899, duration: '1-2 Hours', icon: 'fa-compass', badge: 'Essential', desc: 'Computerized 3D 4-wheel laser alignment, electronic laser balancing and tyre rotation.' },
  { id: 8, name: 'Brake Service', category: 'Mechanical & Electrical', price: 1299, duration: '2-3 Hours', icon: 'fa-circle-notch', badge: 'Safety', desc: 'Front and rear brake pad inspection, caliper degreasing, disc skimming and fluid bleeding.' },
  { id: 9, name: 'Battery Replacement', category: 'Mechanical & Electrical', price: 3499, duration: '1 Hour', icon: 'fa-car-battery', badge: 'Warranty', desc: 'Automotive battery health check, terminal grease, alternator voltage test and new installation.' },
  { id: 10, name: 'Dent & Painting', category: 'Body & Paint', price: 2499, duration: '1-2 Days', icon: 'fa-spray-can', badge: 'Premium Finish', desc: 'Paintless dent removal, computerized spectrophotometer paint matching and bake-oven metallic painting.' }
];

const STATUS_PIPELINE = [
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
];

const INITIAL_BOOKINGS = [
  {
    number: 'BK-2026-001',
    car: 'Hyundai Creta SX (O)',
    year: 2021,
    reg: 'HR 26 DQ 8899',
    fuel: 'Diesel',
    color: 'Phantom Black',
    customer: 'Rahul Sharma',
    phone: '+91 98765 00002',
    address: 'Flat 402, Skyline Towers, Sector 45, Gurugram',
    status: 'Service in Progress',
    stepIndex: 8,
    driver: 'Vikram Singh (EMP-DRV-101)',
    tech: 'Manoj Verma (EMP-TECH-201)',
    services: ['General Service (₹ 2,999)', 'Brake Service (₹ 1,299)', 'Water Wash (₹ 699)'],
    subtotal: 4997,
    tax: 899.46,
    total: 5896.46,
    paymentStatus: 'Unpaid',
    remarks: 'Customer approved estimate for OEM front brake pads renewal. Mechanical work underway on Bay 2.',
    odometer: 34210,
    fuelLevel: '75% (3/4 Tank)',
    date: '2026-11-18',
    time: '09:00 AM - 11:00 AM',
    estimate: {
      parts: 2499,
      labor: 2498,
      discount: 0,
      approved: true
    }
  },
  {
    number: 'BK-2026-002',
    car: 'Honda City ZX',
    year: 2019,
    reg: 'DL 3C CS 4521',
    fuel: 'Petrol',
    color: 'Radiant Red',
    customer: 'Rahul Sharma',
    phone: '+91 98765 00002',
    address: 'Flat 402, Skyline Towers, Sector 45, Gurugram',
    status: 'Delivered',
    stepIndex: 12,
    driver: 'Vikram Singh (EMP-DRV-101)',
    tech: 'Amit Kumar (EMP-TECH-202)',
    services: ['Oil Change (₹ 1,499)', 'Water Wash (₹ 699)'],
    subtotal: 2198,
    tax: 395.64,
    total: 2593.64,
    paymentStatus: 'Paid',
    remarks: 'Vehicle serviced, quality checked, washed, and delivered back to customer home. Payment settled via Razorpay.',
    odometer: 48512,
    fuelLevel: '50% (Half Tank)',
    date: '2026-11-15',
    time: '11:00 AM - 01:00 PM',
    estimate: {
      parts: 1200,
      labor: 998,
      discount: 0,
      approved: true
    }
  }
];

const INITIAL_VEHICLES = [
  { id: 1, make: 'Hyundai Creta SX (O)', reg: 'HR 26 DQ 8899', year: 2021, fuel: 'Diesel', color: 'Phantom Black', odometer: 34210, health: '94% (Good)' },
  { id: 2, make: 'Honda City ZX', reg: 'DL 3C CS 4521', year: 2019, fuel: 'Petrol', color: 'Radiant Red', odometer: 48512, health: '98% (Optimal)' },
  { id: 3, make: 'BMW 330i M Sport', reg: 'DL 1C AB 1008', year: 2022, fuel: 'Petrol', color: 'Portimao Blue', odometer: 18450, health: '99% (Pristine)' }
];

const INITIAL_EMPLOYEES = [
  { id: 'EMP-DRV-101', name: 'Vikram Singh', role: 'Pickup Executive', phone: '+91 98111 22334', status: 'On Route', activeJob: 'BK-2026-001' },
  { id: 'EMP-DRV-102', name: 'Ramesh Patel', role: 'Pickup Executive', phone: '+91 98111 55667', status: 'Available', activeJob: 'None' },
  { id: 'EMP-TECH-201', name: 'Manoj Verma', role: 'Master Technician', phone: '+91 98222 33445', status: 'Bay 2 (Active)', activeJob: 'BK-2026-001' },
  { id: 'EMP-TECH-202', name: 'Amit Kumar', role: 'Diagnostic Specialist', phone: '+91 98222 77889', status: 'Bay 1 (Available)', activeJob: 'None' }
];

// Helper functions for persistent storage
function loadBookings() {
  try {
    const data = localStorage.getItem('autopro_bookings');
    if (data) return JSON.parse(data);
  } catch (e) {
    console.error('Error loading bookings:', e);
  }
  return INITIAL_BOOKINGS;
}

function saveBookings(bookings) {
  try {
    localStorage.setItem('autopro_bookings', JSON.stringify(bookings));
    window.dispatchEvent(new Event('autopro_storage_change'));
  } catch (e) {
    console.error('Error saving bookings:', e);
  }
}

function loadVehicles() {
  try {
    const data = localStorage.getItem('autopro_vehicles');
    if (data) return JSON.parse(data);
  } catch (e) {
    console.error('Error loading vehicles:', e);
  }
  return INITIAL_VEHICLES;
}

function saveVehicles(vehicles) {
  try {
    localStorage.setItem('autopro_vehicles', JSON.stringify(vehicles));
    window.dispatchEvent(new Event('autopro_storage_change'));
  } catch (e) {
    console.error('Error saving vehicles:', e);
  }
}
