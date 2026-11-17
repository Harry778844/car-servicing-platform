/**
 * AutoPro - Ultra-Premium React 18 Application
 * Full Interactive Automotive Maintenance & Telematics Suite
 * Pure Light Luxury Styling & Automotive Color Palette Engine
 */

const { useState, useEffect, useMemo } = React;

// 10 Core Automotive Services
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

// The 12 Canonical Statuses
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

// Initial Seed Bookings
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
    estimate: {
      parts: 1200,
      labor: 998,
      discount: 0,
      approved: true
    }
  }
];

function AutoProApp() {
  const [bookings, setBookings] = useState(INITIAL_BOOKINGS);
  const [activeTrackingQuery, setActiveTrackingQuery] = useState('BK-2026-001');
  const [selectedCategory, setSelectedCategory] = useState('All');
  const [toast, setToast] = useState(null);

  // Modals state
  const [isBookModalOpen, setIsBookModalOpen] = useState(false);
  const [isPortalModalOpen, setIsPortalModalOpen] = useState(false);
  const [portalTab, setPortalTab] = useState('customer');
  const [isEstimateModalOpen, setIsEstimateModalOpen] = useState(false);
  const [isInvoiceModalOpen, setIsInvoiceModalOpen] = useState(false);

  // Booking Wizard Form State
  const [wizardStep, setWizardStep] = useState(1);
  const [wMake, setWMake] = useState('Hyundai Creta SX (O)');
  const [wReg, setWReg] = useState('HR 26 DQ 8899');
  const [wFuel, setWFuel] = useState('Diesel');
  const [wServices, setWServices] = useState([1, 8]);
  const [wDate, setWDate] = useState(() => {
    const d = new Date(); d.setDate(d.getDate() + 1); return d.toISOString().split('T')[0];
  });
  const [wTime, setWTime] = useState('09:00 AM - 11:00 AM');
  const [wAddress, setWAddress] = useState('Flat 402, Skyline Towers, Sector 45, Gurugram');

  // Active tracking booking
  const activeBooking = useMemo(() => {
    const cleanQ = activeTrackingQuery.trim().toUpperCase().replace(/\s/g, '');
    return bookings.find(b => 
      b.number.toUpperCase().replace(/\s/g, '') === cleanQ || 
      b.reg.toUpperCase().replace(/\s/g, '') === cleanQ
    ) || bookings[0];
  }, [bookings, activeTrackingQuery]);

  // Filtered services
  const filteredServices = useMemo(() => {
    if (selectedCategory === 'All') return INITIAL_SERVICES;
    return INITIAL_SERVICES.filter(s => s.category === selectedCategory);
  }, [selectedCategory]);

  // Wizard calculated totals
  const wizardTotals = useMemo(() => {
    let sub = 0;
    wServices.forEach(id => {
      const s = INITIAL_SERVICES.find(x => x.id === id);
      if (s) sub += s.price;
    });
    const tax = sub * 0.18;
    return { sub, tax, grand: sub + tax };
  }, [wServices]);

  const showToast = (msg) => {
    setToast(msg);
    setTimeout(() => setToast(null), 3500);
  };

  // Handle Wizard Submission
  const handleCreateBooking = (e) => {
    e.preventDefault();
    const newNum = 'BK-2026-00' + (bookings.length + 1);
    const chosenServiceNames = wServices.map(id => {
      const s = INITIAL_SERVICES.find(x => x.id === id);
      return `${s.name} (₹ ${s.price})`;
    });

    const newBooking = {
      number: newNum,
      car: wMake,
      year: 2022,
      reg: wReg.toUpperCase(),
      fuel: wFuel,
      color: 'Titanium Grey',
      customer: 'Rahul Sharma',
      phone: '+91 98765 00002',
      address: wAddress,
      status: 'Booking Confirmed',
      stepIndex: 2,
      driver: 'Vikram Singh (Assigned)',
      tech: 'Manoj Verma (Assigned)',
      services: chosenServiceNames,
      subtotal: wizardTotals.sub,
      tax: wizardTotals.tax,
      total: wizardTotals.grand,
      paymentStatus: 'Unpaid',
      remarks: 'Pickup slot scheduled for ' + wDate + ' (' + wTime + '). Driver assigned.',
      odometer: 32000,
      fuelLevel: '75%',
      estimate: { parts: wizardTotals.sub * 0.6, labor: wizardTotals.sub * 0.4, discount: 0, approved: false }
    };

    setBookings([newBooking, ...bookings]);
    setActiveTrackingQuery(newNum);
    setIsBookModalOpen(false);
    showToast(`Booking ${newNum} created successfully! Doorstep pickup scheduled.`);
  };

  // Handle Estimate Approval
  const handleApproveEstimate = (approved) => {
    const updated = bookings.map(b => {
      if (b.number === activeBooking.number) {
        return {
          ...b,
          status: approved ? 'Service in Progress' : 'Inspection in Progress',
          stepIndex: approved ? 8 : 6,
          remarks: approved 
            ? 'Customer approved service estimate. Mechanical work commenced in Bay 2.' 
            : 'Customer requested estimate revisions. Service advisor reviewing.'
        };
      }
      return b;
    });
    setBookings(updated);
    setIsEstimateModalOpen(false);
    showToast(approved ? 'Estimate approved! Bay technicians started servicing.' : 'Estimate revision requested.');
  };

  // Admin status update
  const handleAdminUpdateStatus = (bookingNumber, newStatus) => {
    const stepIdx = STATUS_PIPELINE.indexOf(newStatus) + 1;
    const updated = bookings.map(b => {
      if (b.number === bookingNumber) {
        return {
          ...b,
          status: newStatus,
          stepIndex: stepIdx,
          paymentStatus: newStatus === 'Delivered' ? 'Paid' : b.paymentStatus,
          remarks: `Status updated by Admin to ${newStatus}`
        };
      }
      return b;
    });
    setBookings(updated);
    showToast(`Updated ${bookingNumber} status to ${newStatus}`);
  };

  return (
    <div className="min-vh-100 d-flex flex-column">
      {/* Toast Notification */}
      {toast && (
        <div className="position-fixed bottom-0 end-0 p-3" style={{ zIndex: 9999 }}>
          <div className="toast show align-items-center shadow-lg p-2 rounded glass-panel">
            <div className="d-flex align-items-center">
              <i className="fa-solid fa-circle-check text-accent fs-5 me-2 ms-2"></i>
              <div className="toast-body fw-bold theme-text-main">{toast}</div>
            </div>
          </div>
        </div>
      )}

      {/* Top Utility Bar with Accent Palette Switcher */}
      <div className="top-bar-custom py-2">
        <div className="container d-flex flex-wrap justify-content-between align-items-center gap-2">
          <div>
            <span className="me-3">
              <i className="fa-solid fa-phone text-accent me-1"></i> Hotline: <a href="tel:+918004567890" className="fw-bold theme-text-main text-decoration-none">+91 (800) 456-7890</a>
            </span>
            <span className="theme-text-muted d-none d-md-inline">
              <i className="fa-solid fa-shield-halved text-accent me-1"></i> 100% Genuine OEM Spares &bull; 6-Month Warranty
            </span>
          </div>

          <div className="d-flex align-items-center gap-3">
            <span className="badge px-3 py-1 text-white d-none d-sm-inline-flex align-items-center" style={{ backgroundColor: '#590907', letterSpacing: '0.5px', fontSize: '0.75rem', borderRadius: '20px' }}>
              <i className="fa-solid fa-gem me-1"></i> Bespoke Edition
            </span>

            <a href="https://github.com/Harry778844/car-servicing-platform" target="_blank" className="theme-text-muted text-decoration-none small">
              <i className="fa-brands fa-github text-accent me-1"></i>GitHub
            </a>
          </div>
        </div>
      </div>

      {/* Luxury Navigation Header */}
      <nav className="premium-nav sticky-top">
        <div className="container d-flex justify-content-between align-items-center">
          <a href="#" className="brand-logo text-decoration-none d-flex align-items-center">
            <i className="fa-solid fa-car-tunnel me-2 text-accent"></i>AUTO<span>PRO</span>
          </a>

          <div className="d-none d-lg-flex align-items-center gap-4">
            <a href="#services" className="nav-link-custom">Services</a>
            <a href="#telematics" className="nav-link-custom">12-Stage Tracker</a>
            <a href="#workflow" className="nav-link-custom">Workflow</a>
            <a href="#guarantee" className="nav-link-custom">Warranty</a>
          </div>

          <div className="d-flex align-items-center gap-2">
            <button className="btn-glass btn-sm" onClick={() => setIsPortalModalOpen(true)}>
              <i className="fa-solid fa-gauge-high text-accent me-1"></i>Portals Demo
            </button>
            <button className="btn-accent btn-sm" onClick={() => { setWizardStep(1); setIsBookModalOpen(true); }}>
              <i className="fa-solid fa-calendar-check me-1"></i>Book Pickup
            </button>
          </div>
        </div>
      </nav>

      {/* Hero Section */}
      <section className="py-5 position-relative overflow-hidden" style={{ minHeight: '82vh', display: 'flex', alignItems: 'center' }}>
        <div className="container py-4">
          <div className="row align-items-center g-5">
            <div className="col-lg-7">
              <div className="hero-tag mb-3">
                <span className="live-tag-pulse"></span>
                <span>Autonomous Telematics & Doorstep Car Care</span>
              </div>

              <h1 className="display-4 fw-bold mb-3" style={{ lineHeight: 1.15 }}>
                PREMIUM CAR CARE. <br/><span className="text-accent">AT YOUR DOORSTEP.</span>
              </h1>

              <p className="lead theme-text-body mb-4 pe-lg-4" style={{ fontSize: '1.15rem' }}>
                Contactless vehicle pickup, computerized 60-point mechanical inspection, real-time estimate approval, and live status telematics. Experience dealership-grade servicing with total transparency.
              </p>

              <div className="d-flex flex-wrap gap-3 mb-5">
                <button className="btn-accent btn-lg px-4 py-3" onClick={() => { setWizardStep(1); setIsBookModalOpen(true); }}>
                  <i className="fa-solid fa-calendar-plus me-2"></i>Schedule Doorstep Pickup
                </button>
                <a href="#services" className="btn-glass btn-lg px-4 py-3 text-decoration-none">
                  <i className="fa-solid fa-list-check me-2"></i>Explore 10 Services
                </a>
              </div>

              {/* Quick Telematics Query Bar */}
              <div className="glass-panel p-3 p-md-4" style={{ maxWidth: '620px' }}>
                <form onSubmit={(e) => { e.preventDefault(); document.getElementById('telematics').scrollIntoView({ behavior: 'smooth' }); }} className="row g-2 align-items-center">
                  <div className="col-md-8">
                    <div className="input-group">
                      <span className="input-group-text bg-surface border-subtle text-accent"><i className="fa-solid fa-crosshairs"></i></span>
                      <input 
                        type="text" 
                        className="luxury-input w-100" 
                        placeholder="Booking Ref (e.g. BK-2026-001) or Reg No." 
                        value={activeTrackingQuery}
                        onChange={(e) => setActiveTrackingQuery(e.target.value)}
                      />
                    </div>
                  </div>
                  <div className="col-md-4">
                    <button type="submit" className="btn-accent w-100 py-2">
                      <i className="fa-solid fa-radar me-1"></i>Track Car
                    </button>
                  </div>
                </form>
                <div className="d-flex gap-2 mt-2 small theme-text-muted">
                  <span>Quick Demo:</span>
                  <span className="badge bg-surface text-accent border border-subtle cursor-pointer" onClick={() => setActiveTrackingQuery('BK-2026-001')}>BK-2026-001 (In Progress)</span>
                  <span className="badge bg-surface text-accent border border-subtle cursor-pointer" onClick={() => setActiveTrackingQuery('BK-2026-002')}>BK-2026-002 (Delivered)</span>
                </div>
              </div>
            </div>

            {/* Right Hero Visual Card */}
            <div className="col-lg-5">
              <div className="glass-panel p-4 position-relative overflow-hidden">
                <img 
                  src="https://images.unsplash.com/photo-1619642751034-765dfdf7c58e?auto=format&fit=crop&w=800&q=80" 
                  alt="Car Servicing Workshop" 
                  className="img-fluid rounded mb-4" 
                  style={{ maxHeight: '280px', width: '100%', objectFit: 'cover' }}
                />

                <div className="row g-2">
                  <div className="col-6">
                    <div className="kpi-card text-center">
                      <div className="text-accent fw-bold fs-3 display-font">15,000+</div>
                      <div className="theme-text-muted small text-uppercase">Cars Maintained</div>
                    </div>
                  </div>
                  <div className="col-6">
                    <div className="kpi-card text-center">
                      <div className="text-warning fw-bold fs-3 display-font"><i className="fa-solid fa-star me-1"></i>4.9 / 5</div>
                      <div className="theme-text-muted small text-uppercase">Owner Satisfaction</div>
                    </div>
                  </div>
                  <div className="col-6">
                    <div className="kpi-card text-center">
                      <div className="text-info fw-bold fs-3 display-font">100%</div>
                      <div className="theme-text-muted small text-uppercase">OEM Certified Spares</div>
                    </div>
                  </div>
                  <div className="col-6">
                    <div className="kpi-card text-center">
                      <div className="text-success fw-bold fs-3 display-font">6 Mos</div>
                      <div className="theme-text-muted small text-uppercase">Service Warranty</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* 4-Step Process Section */}
      <section className="py-5 section-alt" id="workflow">
        <div className="container py-4">
          <div className="text-center mb-5">
            <span className="hero-tag mb-2">Automated Lifecycle</span>
            <h2 className="fw-bold fs-1">THE AUTOPRO EXPERIENCE</h2>
            <p className="theme-text-muted">Four simple steps from doorstep pickup to key handover</p>
          </div>

          <div className="row g-4">
            {[
              { step: '01', title: 'Schedule Online', icon: 'fa-calendar-check', desc: 'Select car specifications, desired services, and choose a preferred doorstep pickup slot.' },
              { step: '02', title: 'Doorstep Collection', icon: 'fa-truck-pickup', desc: 'Our insured executive logs odometer, fuel levels, exterior scratches, and delivers vehicle to workshop.' },
              { step: '03', title: 'Inspection & Approval', icon: 'fa-clipboard-check', desc: 'Master technician conducts a 60-point digital checklist. You approve transparent estimates online.' },
              { step: '04', title: 'Sanitized Delivery', icon: 'fa-flag-checkered', desc: 'Vehicle road-tested, pressure-washed, and delivered to your doorstep. Pay via Razorpay or Cash.' }
            ].map((p, idx) => (
              <div key={idx} className="col-md-6 col-lg-3">
                <div className="glass-panel p-4 h-100 position-relative">
                  <span className="badge badge-accent position-absolute top-0 end-0 m-3 display-font">{p.step}</span>
                  <div className="service-icon-box"><i className={`fa-solid ${p.icon}`}></i></div>
                  <h5 className="fw-bold mb-2">{p.title}</h5>
                  <p className="theme-text-muted small mb-0">{p.desc}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* 10 Core Services Section */}
      <section className="py-5" id="services">
        <div className="container py-4">
          <div className="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
            <div>
              <span className="hero-tag mb-2">Master Capabilities</span>
              <h2 className="fw-bold fs-1">PRECISION SERVICE MENU</h2>
              <p className="theme-text-muted mb-0">Select from routine periodic maintenance to complete computerized diagnostics</p>
            </div>
            
            {/* Category Filter Pills */}
            <div className="d-flex flex-wrap gap-2 mt-3 mt-md-0">
              {['All', 'Periodic Maintenance', 'Mechanical & Electrical', 'Detailing & Wash', 'Emergency Assistance', 'Body & Paint'].map(cat => (
                <button 
                  key={cat} 
                  className={`btn-sm btn ${selectedCategory === cat ? 'btn-accent' : 'btn-glass'}`}
                  onClick={() => setSelectedCategory(cat)}
                >
                  {cat}
                </button>
              ))}
            </div>
          </div>

          <div className="row g-4">
            {filteredServices.map(s => (
              <div key={s.id} className="col-md-6 col-lg-4">
                <div className="premium-service-card">
                  <div className="d-flex justify-content-between align-items-start mb-3">
                    <div className="service-icon-box mb-0"><i className={`fa-solid ${s.icon}`}></i></div>
                    <span className="badge badge-accent text-uppercase px-2 py-1">{s.badge}</span>
                  </div>

                  <h4 className="fw-bold mb-1">{s.name}</h4>
                  <span className="theme-text-muted small mb-2 d-block"><i className="fa-solid fa-tag me-1 text-accent"></i>{s.category}</span>
                  <p className="theme-text-muted small flex-grow-1 mb-3">{s.desc}</p>

                  <div className="d-flex justify-content-between align-items-center pt-3 border-top border-subtle">
                    <div>
                      <small className="theme-text-muted d-block"><i className="fa-solid fa-clock me-1"></i>{s.duration}</small>
                      <strong className="fs-5 theme-text-main">₹ {s.price.toLocaleString()}</strong>
                    </div>
                    <button 
                      className="btn-accent btn-sm px-3"
                      onClick={() => {
                        setWServices([s.id]);
                        setWizardStep(1);
                        setIsBookModalOpen(true);
                      }}
                    >
                      Book Now
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* 12-Stage Live Telematics Tracker Section */}
      <section className="py-5 section-alt" id="telematics">
        <div className="container py-4">
          <div className="text-center mb-5">
            <span className="hero-tag mb-2"><i className="fa-solid fa-radar me-1"></i> Live Workshop Telematics</span>
            <h2 className="fw-bold fs-1">12-STAGE VEHICLE TRACKER</h2>
            <p className="theme-text-muted">Real-time telematics from vehicle pickup to service completion and doorstep return</p>
          </div>

          <div className="row g-4">
            {/* Left: 12-Stage Visual Stepper */}
            <div className="col-lg-7">
              <div className="glass-panel p-4 p-md-5">
                <div className="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom border-subtle">
                  <div>
                    <span className="badge badge-accent fs-6 me-2">{activeBooking.reg}</span>
                    <span className="badge bg-secondary">{activeBooking.number}</span>
                    <h3 className="fw-bold mb-0 mt-2">{activeBooking.car}</h3>
                  </div>
                  <div className="text-end">
                    <span className="badge bg-warning bg-opacity-25 text-warning fs-6 px-3 py-1 border border-warning">
                      {activeBooking.status}
                    </span>
                    <div className="theme-text-muted small mt-1">
                      Progress: <strong className="theme-text-main">{Math.round((activeBooking.stepIndex / 12) * 100)}%</strong>
                    </div>
                  </div>
                </div>

                {/* Animated Progress Bar */}
                <div className="progress mb-4" style={{ height: '8px', backgroundColor: 'var(--bg-surface-elevated)' }}>
                  <div 
                    className="progress-bar progress-bar-striped progress-bar-animated" 
                    style={{ width: `${Math.round((activeBooking.stepIndex / 12) * 100)}%`, backgroundColor: 'var(--accent)' }}
                  ></div>
                </div>

                {/* Customer Estimate Approval Action Card (if at approval stage) */}
                {activeBooking.status === 'Waiting for Customer Approval' && (
                  <div className="p-3 rounded bg-warning bg-opacity-10 border border-warning mb-4">
                    <div className="d-flex justify-content-between align-items-center">
                      <div>
                        <strong className="text-warning d-block"><i className="fa-solid fa-hourglass-half me-1"></i> Estimate Approval Required</strong>
                        <span className="small theme-text-body">Mechanic generated quote for parts & labor. Tap to review.</span>
                      </div>
                      <button className="btn btn-warning btn-sm fw-bold" onClick={() => setIsEstimateModalOpen(true)}>
                        Review & Approve
                      </button>
                    </div>
                  </div>
                )}

                <h5 className="fw-bold mb-4"><i className="fa-solid fa-timeline text-accent me-2"></i>12-Stage Audit Trail</h5>

                <div className="luxury-stepper">
                  {STATUS_PIPELINE.map((st, idx) => {
                    const stepNum = idx + 1;
                    const isDone = stepNum < activeBooking.stepIndex || activeBooking.status === 'Delivered';
                    const isCur = stepNum === activeBooking.stepIndex && activeBooking.status !== 'Delivered';
                    const cls = isDone ? 'completed' : (isCur ? 'active' : '');

                    return (
                      <div key={idx} className={`step-item ${cls}`} style={{ position: 'relative' }}>
                        <div className="stepper-node-lux">
                          {isDone ? <i className="fa-solid fa-check"></i> : (isCur ? <i className="fa-solid fa-gear fa-spin"></i> : stepNum)}
                        </div>
                        <div className="ms-2">
                          <h6 className={`fw-bold mb-1 ${isCur ? 'text-accent' : (isDone ? 'theme-text-main' : 'theme-text-muted')}`}>
                            {stepNum}. {st}
                          </h6>
                          {isCur && (
                            <p className="text-accent small mb-0"><i className="fa-solid fa-circle-dot me-1"></i> {activeBooking.remarks}</p>
                          )}
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            </div>

            {/* Right: Assigned Crew, Telematics & Invoice Box */}
            <div className="col-lg-5">
              <div className="glass-panel p-4 mb-4">
                <h5 className="fw-bold mb-3 border-bottom border-subtle pb-2">
                  <i className="fa-solid fa-user-shield text-accent me-2"></i>Assigned Workshop Crew
                </h5>
                <div className="p-3 rounded kpi-card mb-3 small">
                  <small className="theme-text-muted text-uppercase d-block" style={{ fontSize: '0.75rem' }}>Doorstep Driver</small>
                  <strong className="theme-text-main fs-6">{activeBooking.driver}</strong>
                </div>
                <div className="p-3 rounded kpi-card small">
                  <small className="theme-text-muted text-uppercase d-block" style={{ fontSize: '0.75rem' }}>Master Diagnostic Technician</small>
                  <strong className="theme-text-main fs-6">{activeBooking.tech}</strong>
                </div>
              </div>

              <div className="glass-panel p-4 mb-4">
                <h5 className="fw-bold mb-3 border-bottom border-subtle pb-2">
                  <i className="fa-solid fa-gauge-high text-accent me-2"></i>Vehicle Telematics
                </h5>
                <div className="row g-2 small theme-text-muted">
                  <div className="col-6">Odometer: <strong className="theme-text-main">{activeBooking.odometer.toLocaleString()} KM</strong></div>
                  <div className="col-6">Fuel Level: <strong className="theme-text-main">{activeBooking.fuelLevel}</strong></div>
                  <div className="col-6">Fuel Type: <strong className="theme-text-main">{activeBooking.fuel}</strong></div>
                  <div className="col-6">Color: <strong className="theme-text-main">{activeBooking.color}</strong></div>
                </div>
              </div>

              <div className="glass-panel p-4">
                <h5 className="fw-bold mb-3 border-bottom border-subtle pb-2">
                  <i className="fa-solid fa-file-invoice text-accent me-2"></i>Invoice Breakdown
                </h5>
                <div className="d-flex justify-content-between small theme-text-muted mb-2">
                  <span>Subtotal Labor & Parts:</span>
                  <strong className="theme-text-main">₹ {activeBooking.subtotal.toFixed(2)}</strong>
                </div>
                <div className="d-flex justify-content-between small theme-text-muted mb-2">
                  <span>GST (18% Standard):</span>
                  <strong className="theme-text-main">₹ {activeBooking.tax.toFixed(2)}</strong>
                </div>
                <div className="d-flex justify-content-between fs-4 fw-bold pt-2 border-top border-subtle mb-3">
                  <span>Total Amount:</span>
                  <span className="text-accent">₹ {activeBooking.total.toFixed(2)}</span>
                </div>
                <div className="d-grid gap-2">
                  <button className="btn-glass btn-sm" onClick={() => setIsInvoiceModalOpen(true)}>
                    <i className="fa-solid fa-print me-1"></i>View & Print Tax Invoice
                  </button>
                  {activeBooking.paymentStatus !== 'Paid' && (
                    <button className="btn-accent btn-sm" onClick={() => {
                      const updated = bookings.map(b => b.number === activeBooking.number ? { ...b, paymentStatus: 'Paid' } : b);
                      setBookings(updated);
                      showToast('Payment of ₹ ' + activeBooking.total.toFixed(2) + ' completed via Razorpay!');
                    }}>
                      <i className="fa-solid fa-bolt me-1"></i>Pay Now via Razorpay
                    </button>
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* MODAL: 4-STEP BOOKING WIZARD */}
      {isBookModalOpen && (
        <div className="modal show d-block" style={{ backgroundColor: 'var(--modal-backdrop)', backdropFilter: 'blur(10px)' }}>
          <div className="modal-dialog modal-lg modal-dialog-centered">
            <div className="modal-content luxury-modal p-4">
              <div className="d-flex justify-content-between align-items-center border-bottom border-subtle pb-3 mb-4">
                <h4 className="fw-bold mb-0"><i className="fa-solid fa-calendar-check text-accent me-2"></i>Book Car Servicing Pickup</h4>
                <button className="btn-close" onClick={() => setIsBookModalOpen(false)}></button>
              </div>

              {/* Wizard Steps Header */}
              <div className="d-flex justify-content-between mb-4 pb-2 border-bottom border-subtle small">
                <span className={`fw-bold ${wizardStep === 1 ? 'text-accent' : 'theme-text-muted'}`}>1. Vehicle</span>
                <span className={`fw-bold ${wizardStep === 2 ? 'text-accent' : 'theme-text-muted'}`}>2. Services</span>
                <span className={`fw-bold ${wizardStep === 3 ? 'text-accent' : 'theme-text-muted'}`}>3. Schedule</span>
                <span className={`fw-bold ${wizardStep === 4 ? 'text-accent' : 'theme-text-muted'}`}>4. Confirm</span>
              </div>

              {wizardStep === 1 && (
                <div>
                  <h6 className="fw-bold text-accent text-uppercase mb-3">Vehicle Information</h6>
                  <div className="row g-3 mb-4">
                    <div className="col-md-6">
                      <label className="small theme-text-muted mb-1">Make & Model *</label>
                      <input type="text" className="luxury-input w-100" value={wMake} onChange={(e) => setWMake(e.target.value)} required />
                    </div>
                    <div className="col-md-6">
                      <label className="small theme-text-muted mb-1">Registration Number *</label>
                      <input type="text" className="luxury-input w-100" value={wReg} onChange={(e) => setWReg(e.target.value)} required />
                    </div>
                    <div className="col-md-6">
                      <label className="small theme-text-muted mb-1">Fuel Type</label>
                      <select className="luxury-input w-100" value={wFuel} onChange={(e) => setWFuel(e.target.value)}>
                        <option>Petrol</option><option>Diesel</option><option>Electric</option><option>CNG</option>
                      </select>
                    </div>
                  </div>
                  <div className="d-flex justify-content-end">
                    <button className="btn-accent" onClick={() => setWizardStep(2)}>Next: Select Services &rarr;</button>
                  </div>
                </div>
              )}

              {wizardStep === 2 && (
                <div>
                  <h6 className="fw-bold text-accent text-uppercase mb-3">Select Services Needed</h6>
                  <div className="row g-2 mb-4" style={{ maxHeight: '300px', overflowY: 'auto' }}>
                    {INITIAL_SERVICES.map(s => {
                      const isSel = wServices.includes(s.id);
                      return (
                        <div key={s.id} className="col-md-6">
                          <label className={`p-2 rounded border d-flex justify-content-between align-items-center cursor-pointer ${isSel ? 'border-primary bg-primary bg-opacity-10' : 'border-subtle bg-surface'}`}>
                            <span>
                              <input 
                                type="checkbox" 
                                className="form-check-input me-2" 
                                checked={isSel} 
                                onChange={() => {
                                  if (isSel) setWServices(wServices.filter(id => id !== s.id));
                                  else setWServices([...wServices, s.id]);
                                }}
                              />
                              <strong className="theme-text-main small">{s.name}</strong>
                            </span>
                            <span className="text-accent fw-bold small">₹ {s.price}</span>
                          </label>
                        </div>
                      );
                    })}
                  </div>
                  <div className="d-flex justify-content-between align-items-center">
                    <button className="btn-glass" onClick={() => setWizardStep(1)}>&larr; Back</button>
                    <button className="btn-accent" onClick={() => setWizardStep(3)}>Next: Pickup Details &rarr;</button>
                  </div>
                </div>
              )}

              {wizardStep === 3 && (
                <div>
                  <h6 className="fw-bold text-accent text-uppercase mb-3">Doorstep Pickup Details</h6>
                  <div className="row g-3 mb-4">
                    <div className="col-md-6">
                      <label className="small theme-text-muted mb-1">Preferred Date *</label>
                      <input type="date" className="luxury-input w-100" value={wDate} onChange={(e) => setWDate(e.target.value)} required />
                    </div>
                    <div className="col-md-6">
                      <label className="small theme-text-muted mb-1">Time Slot *</label>
                      <select className="luxury-input w-100" value={wTime} onChange={(e) => setWTime(e.target.value)}>
                        <option>09:00 AM - 11:00 AM (Morning)</option>
                        <option>11:00 AM - 01:00 PM (Mid-day)</option>
                        <option>02:00 PM - 04:00 PM (Afternoon)</option>
                      </select>
                    </div>
                    <div className="col-12">
                      <label className="small theme-text-muted mb-1">Complete Pickup Address *</label>
                      <input type="text" className="luxury-input w-100" value={wAddress} onChange={(e) => setWAddress(e.target.value)} required />
                    </div>
                  </div>
                  <div className="d-flex justify-content-between align-items-center">
                    <button className="btn-glass" onClick={() => setWizardStep(2)}>&larr; Back</button>
                    <button className="btn-accent" onClick={() => setWizardStep(4)}>Review & Confirm &rarr;</button>
                  </div>
                </div>
              )}

              {wizardStep === 4 && (
                <div>
                  <h6 className="fw-bold text-accent text-uppercase mb-3">Review & Schedule</h6>
                  <div className="kpi-card mb-3">
                    <div className="d-flex justify-content-between mb-2">
                      <span className="theme-text-muted">Vehicle:</span>
                      <strong className="theme-text-main">{wMake} ({wReg})</strong>
                    </div>
                    <div className="d-flex justify-content-between mb-2">
                      <span className="theme-text-muted">Pickup Window:</span>
                      <strong className="theme-text-main">{wDate} at {wTime}</strong>
                    </div>
                    <div className="d-flex justify-content-between mb-2">
                      <span className="theme-text-muted">Address:</span>
                      <strong className="theme-text-main small">{wAddress}</strong>
                    </div>
                    <hr className="border-subtle my-2"/>
                    <div className="d-flex justify-content-between fs-5 fw-bold">
                      <span className="theme-text-main">Estimated Total:</span>
                      <span className="text-accent">₹ {wizardTotals.grand.toFixed(2)}</span>
                    </div>
                  </div>
                  <div className="d-flex justify-content-between align-items-center">
                    <button className="btn-glass" onClick={() => setWizardStep(3)}>&larr; Back</button>
                    <button className="btn-accent btn-lg px-4" onClick={handleCreateBooking}>
                      <i className="fa-solid fa-circle-check me-2"></i>Confirm Booking
                    </button>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      )}

      {/* MODAL: ESTIMATE REVIEW & APPROVAL */}
      {isEstimateModalOpen && (
        <div className="modal show d-block" style={{ backgroundColor: 'var(--modal-backdrop)', backdropFilter: 'blur(10px)' }}>
          <div className="modal-dialog modal-dialog-centered">
            <div className="modal-content luxury-modal p-4">
              <div className="d-flex justify-content-between align-items-center border-bottom border-subtle pb-3 mb-3">
                <h5 className="fw-bold mb-0"><i className="fa-solid fa-receipt text-accent me-2"></i>Technician Service Quotation</h5>
                <button className="btn-close" onClick={() => setIsEstimateModalOpen(false)}></button>
              </div>

              <div className="kpi-card mb-3">
                <div className="d-flex justify-content-between mb-2">
                  <span>Parts & Spares:</span>
                  <strong className="theme-text-main">₹ {activeBooking.estimate.parts.toFixed(2)}</strong>
                </div>
                <div className="d-flex justify-content-between mb-2">
                  <span>Certified Labor & Tuning:</span>
                  <strong className="theme-text-main">₹ {activeBooking.estimate.labor.toFixed(2)}</strong>
                </div>
                <div className="d-flex justify-content-between mb-2">
                  <span>GST (18%):</span>
                  <strong className="theme-text-main">₹ {((activeBooking.estimate.parts + activeBooking.estimate.labor) * 0.18).toFixed(2)}</strong>
                </div>
                <hr className="border-subtle my-2"/>
                <div className="d-flex justify-content-between fs-5 fw-bold text-accent">
                  <span>Grand Total:</span>
                  <strong>₹ {((activeBooking.estimate.parts + activeBooking.estimate.labor) * 1.18).toFixed(2)}</strong>
                </div>
              </div>

              <p className="small theme-text-muted mb-4">
                Our technicians will not proceed without your express digital authorization.
              </p>

              <div className="d-flex justify-content-between gap-2">
                <button className="btn btn-outline-danger flex-grow-1" onClick={() => handleApproveEstimate(false)}>
                  Request Changes
                </button>
                <button className="btn-accent flex-grow-1" onClick={() => handleApproveEstimate(true)}>
                  Approve & Begin Repairs
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* MODAL: TAX INVOICE PRINT VIEW */}
      {isInvoiceModalOpen && (
        <div className="modal show d-block" style={{ backgroundColor: 'var(--modal-backdrop)', backdropFilter: 'blur(10px)' }}>
          <div className="modal-dialog modal-lg modal-dialog-centered">
            <div className="modal-content luxury-modal p-4 p-md-5">
              <div className="d-flex justify-content-between align-items-center border-bottom border-subtle pb-3 mb-4">
                <div>
                  <h3 className="brand-logo mb-0">AUTO<span>PRO</span></h3>
                  <small className="theme-text-muted">Official GST Electronic Tax Invoice</small>
                </div>
                <div className="text-end">
                  <span className={`badge bg-${activeBooking.paymentStatus === 'Paid' ? 'success' : 'danger'} mb-1`}>
                    {activeBooking.paymentStatus === 'Paid' ? 'PAID' : 'PAYMENT DUE'}
                  </span>
                  <div className="small theme-text-muted">INV-{activeBooking.number}</div>
                </div>
              </div>

              <div className="row g-3 mb-4 small">
                <div className="col-6">
                  <strong className="text-accent text-uppercase d-block mb-1">Customer Details</strong>
                  <div className="theme-text-main fw-bold">{activeBooking.customer}</div>
                  <div className="theme-text-muted">{activeBooking.phone}</div>
                  <div className="theme-text-muted">{activeBooking.address}</div>
                </div>
                <div className="col-6 text-end">
                  <strong className="text-accent text-uppercase d-block mb-1">Vehicle Details</strong>
                  <div className="theme-text-main fw-bold">{activeBooking.car}</div>
                  <span className="badge badge-accent">{activeBooking.reg}</span>
                  <div className="theme-text-muted mt-1">Odometer: {activeBooking.odometer.toLocaleString()} KM</div>
                </div>
              </div>

              <div className="table-responsive mb-4">
                <table className="table table-custom">
                  <thead>
                    <tr><th>Item / Service</th><th className="text-end">Amount</th></tr>
                  </thead>
                  <tbody>
                    {activeBooking.services.map((srv, idx) => (
                      <tr key={idx}><td>{srv}</td><td className="text-end fw-bold theme-text-main">Included</td></tr>
                    ))}
                  </tbody>
                </table>
              </div>

              <div className="kpi-card mb-4 ms-auto" style={{ maxWidth: '350px' }}>
                <div className="d-flex justify-content-between small mb-1"><span>Subtotal:</span><strong>₹ {activeBooking.subtotal.toFixed(2)}</strong></div>
                <div className="d-flex justify-content-between small mb-1"><span>GST (18%):</span><strong>₹ {activeBooking.tax.toFixed(2)}</strong></div>
                <div className="d-flex justify-content-between fs-5 fw-bold text-accent border-top border-subtle pt-1"><span>Total:</span><strong>₹ {activeBooking.total.toFixed(2)}</strong></div>
              </div>

              <div className="d-flex justify-content-between">
                <button className="btn-glass" onClick={() => setIsInvoiceModalOpen(false)}>Close</button>
                <button className="btn-accent" onClick={() => window.print()}><i className="fa-solid fa-print me-1"></i>Print PDF</button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* MODAL: 4-PORTALS WORKSPACE DEMO */}
      {isPortalModalOpen && (
        <div className="modal show d-block" style={{ backgroundColor: 'var(--modal-backdrop)', backdropFilter: 'blur(10px)' }}>
          <div className="modal-dialog modal-xl modal-dialog-centered">
            <div className="modal-content luxury-modal p-4">
              <div className="d-flex justify-content-between align-items-center border-bottom border-subtle pb-3 mb-4">
                <h4 className="fw-bold mb-0"><i className="fa-solid fa-gauge-high text-accent me-2"></i>AutoPro Role Portals Command Center</h4>
                <button className="btn-close" onClick={() => setIsPortalModalOpen(false)}></button>
              </div>

              {/* Portal Selector Tabs */}
              <div className="d-flex flex-wrap gap-2 mb-4 pb-2 border-bottom border-subtle">
                {[
                  { id: 'customer', name: 'Customer Garage', icon: 'fa-user' },
                  { id: 'admin', name: 'Admin Pipeline Dispatch', icon: 'fa-shield-halved' },
                  { id: 'pickup', name: 'Pickup Executive Route', icon: 'fa-truck-pickup' },
                  { id: 'technician', name: 'Technician Bay Diagnostic', icon: 'fa-screwdriver-wrench' }
                ].map(tab => (
                  <button 
                    key={tab.id}
                    className={`btn btn-sm ${portalTab === tab.id ? 'btn-accent' : 'btn-glass'}`}
                    onClick={() => setPortalTab(tab.id)}
                  >
                    <i className={`fa-solid ${tab.icon} me-1`}></i>{tab.name}
                  </button>
                ))}
              </div>

              {/* Tab 1: Customer View */}
              {portalTab === 'customer' && (
                <div>
                  <div className="row g-3 mb-4">
                    <div className="col-3"><div className="kpi-card text-center"><div className="fs-3 fw-bold theme-text-main">{bookings.length}</div><small className="theme-text-muted">My Vehicles</small></div></div>
                    <div className="col-3"><div className="kpi-card text-center"><div className="fs-3 fw-bold text-warning">{bookings.filter(b=>b.status!=='Delivered').length}</div><small className="theme-text-muted">Active</small></div></div>
                    <div className="col-3"><div className="kpi-card text-center"><div className="fs-3 fw-bold text-success">{bookings.filter(b=>b.status==='Delivered').length}</div><small className="theme-text-muted">Completed</small></div></div>
                    <div className="col-3"><div className="kpi-card text-center"><div className="fs-4 fw-bold text-accent">₹ {bookings.reduce((a,c)=>a+c.total,0).toFixed(0)}</div><small className="theme-text-muted">Total Spent</small></div></div>
                  </div>
                  <div className="table-responsive">
                    <table className="table table-custom">
                      <thead><tr><th>Ref</th><th>Car</th><th>Status</th><th>Total</th><th>Payment</th><th>Action</th></tr></thead>
                      <tbody>
                        {bookings.map(b => (
                          <tr key={b.number}>
                            <td><strong>{b.number}</strong></td>
                            <td>{b.car} ({b.reg})</td>
                            <td><span className="badge bg-warning bg-opacity-25 text-warning border border-warning">{b.status}</span></td>
                            <td className="fw-bold theme-text-main">₹ {b.total.toFixed(2)}</td>
                            <td><span className={`badge bg-${b.paymentStatus==='Paid'?'success':'danger'}`}>{b.paymentStatus}</span></td>
                            <td>
                              <button className="btn-accent btn-sm py-1" onClick={() => { setActiveTrackingQuery(b.number); setIsPortalModalOpen(false); document.getElementById('telematics').scrollIntoView({ behavior: 'smooth' }); }}>
                                Track Live
                              </button>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}

              {/* Tab 2: Admin View */}
              {portalTab === 'admin' && (
                <div>
                  <div className="d-flex justify-content-between align-items-center mb-3">
                    <h6 className="fw-bold mb-0">12-Stage Booking Dispatch Controller</h6>
                    <span className="badge badge-accent">Full Control Active</span>
                  </div>
                  <div className="table-responsive">
                    <table className="table table-custom">
                      <thead><tr><th>Ref</th><th>Customer</th><th>Car</th><th>Current Status</th><th>Override Status</th></tr></thead>
                      <tbody>
                        {bookings.map(b => (
                          <tr key={b.number}>
                            <td><strong>{b.number}</strong></td>
                            <td>{b.customer}</td>
                            <td>{b.car}</td>
                            <td><span className="badge bg-secondary">{b.status}</span></td>
                            <td>
                              <select 
                                className="luxury-input py-1 px-2 small" 
                                value={b.status}
                                onChange={(e) => handleAdminUpdateStatus(b.number, e.target.value)}
                              >
                                {STATUS_PIPELINE.map(st => (
                                  <option key={st} value={st}>{st}</option>
                                ))}
                              </select>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}

              {/* Tab 3: Pickup Driver View */}
              {portalTab === 'pickup' && (
                <div>
                  <h6 className="fw-bold mb-3">Driver Route Tasks (Vikram Singh - EMP-DRV-101)</h6>
                  {bookings.map(b => (
                    <div key={b.number} className="kpi-card mb-3">
                      <div className="d-flex justify-content-between align-items-center mb-2">
                        <strong className="theme-text-main fs-6">{b.car} ({b.reg})</strong>
                        <span className="badge badge-accent">{b.status}</span>
                      </div>
                      <div className="small theme-text-muted mb-2"><i className="fa-solid fa-location-dot text-accent me-1"></i>{b.address}</div>
                      <div className="d-flex gap-2">
                        <button className="btn btn-sm btn-accent" onClick={() => { handleAdminUpdateStatus(b.number, 'Vehicle Picked Up'); }}>
                          Mark Picked Up from Doorstep
                        </button>
                        <button className="btn btn-sm btn-glass" onClick={() => { handleAdminUpdateStatus(b.number, 'Vehicle Received at Workshop'); }}>
                          Check-in at Workshop Bay
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              )}

              {/* Tab 4: Technician View */}
              {portalTab === 'technician' && (
                <div>
                  <h6 className="fw-bold mb-3">Service Bay Queue (Manoj Verma - Master Technician)</h6>
                  {bookings.map(b => (
                    <div key={b.number} className="kpi-card mb-3">
                      <div className="d-flex justify-content-between align-items-center mb-2">
                        <strong className="theme-text-main fs-6">{b.car} ({b.reg})</strong>
                        <span className="badge bg-info text-dark">{b.status}</span>
                      </div>
                      <div className="small theme-text-muted mb-2">Diagnostic Scan: OBD-II Active &bull; Odometer: {b.odometer} KM</div>
                      <div className="d-flex gap-2">
                        <button className="btn btn-sm btn-glass" onClick={() => { handleAdminUpdateStatus(b.number, 'Inspection in Progress'); }}>
                          Start 60-Point Inspection
                        </button>
                        <button className="btn btn-sm btn-accent" onClick={() => { handleAdminUpdateStatus(b.number, 'Service Completed'); }}>
                          Mark Service Completed
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              )}

            </div>
          </div>
        </div>
      )}

      {/* Footer */}
      <footer className="site-footer mt-auto" id="guarantee">
        <div className="container">
          <div className="row g-4 mb-4">
            <div className="col-lg-4">
              <h3 className="brand-logo mb-2">AUTO<span>PRO</span></h3>
              <p className="theme-text-muted small">India's premier digital car maintenance platform powered by React 18 and cloud telematics.</p>
            </div>
            <div className="col-lg-4 col-6">
              <h6 className="footer-title">10 Core Services</h6>
              <div className="small theme-text-muted">Periodic Maintenance &bull; Engine Diagnostics &bull; AC Recharge &bull; Foam Water Wash &bull; Brake Caliper Renewal &bull; Laser Wheel Alignment</div>
            </div>
            <div className="col-lg-4 col-6">
              <h6 className="footer-title">Official Repository</h6>
              <p className="small theme-text-muted mb-2">Full-stack source code with 17 MySQL tables, Dockerfile, and container configs.</p>
              <a href="https://github.com/Harry778844/car-servicing-platform" className="btn-accent btn-sm text-decoration-none d-inline-block" target="_blank">
                <i className="fa-brands fa-github me-1"></i>View on GitHub
              </a>
            </div>
          </div>
          <hr className="border-subtle my-3"/>
          <div className="d-flex justify-content-between small theme-text-muted">
            <span>&copy; 2026 AutoPro Car Care. Deployed live with React 18.</span>
            <span>https://harry778844.github.io/car-servicing-platform/</span>
          </div>
        </div>
      </footer>
    </div>
  );
}

// Mount React Root
const root = ReactDOM.createRoot(document.getElementById('react-root'));
root.render(<AutoProApp />);
