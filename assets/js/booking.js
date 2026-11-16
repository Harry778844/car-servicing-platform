/**
 * AutoPro Car Servicing & Maintenance Platform
 * Multi-Step Booking Wizard Logic
 */

let currentStep = 1;
const totalSteps = 4;
let selectedServices = [];
let baseTotal = 0;

document.addEventListener('DOMContentLoaded', function () {
    const serviceCheckboxes = document.querySelectorAll('.service-checkbox');
    const vehicleRadios = document.querySelectorAll('input[name="vehicle_id"]');

    // Service checkbox event listeners
    serviceCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateServiceCalculation);
    });

    // Initialize initial calculation
    updateServiceCalculation();

    // Use My Current Location simulation
    const btnGeolocate = document.getElementById('btn-get-location');
    if (btnGeolocate) {
        btnGeolocate.addEventListener('click', function () {
            if (navigator.geolocation) {
                btnGeolocate.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Detecting...';
                navigator.geolocation.getCurrentPosition(
                    position => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        const latInput = document.getElementById('pickup_latitude');
                        const lngInput = document.getElementById('pickup_longitude');
                        if (latInput) latInput.value = lat.toFixed(6);
                        if (lngInput) lngInput.value = lng.toFixed(6);
                        btnGeolocate.innerHTML = '<i class="fa-solid fa-circle-check text-success me-1"></i> GPS Captured (' + lat.toFixed(4) + ', ' + lng.toFixed(4) + ')';
                        btnGeolocate.classList.replace('btn-outline-secondary', 'btn-outline-success');
                    },
                    error => {
                        // Fallback simulated coordinates
                        const latInput = document.getElementById('pickup_latitude');
                        const lngInput = document.getElementById('pickup_longitude');
                        if (latInput) latInput.value = "28.4595";
                        if (lngInput) lngInput.value = "77.0266";
                        btnGeolocate.innerHTML = '<i class="fa-solid fa-location-dot text-danger me-1"></i> Gurugram Workshop Area (Default)';
                    }
                );
            }
        });
    }
});

function goToStep(step) {
    // Validate current step before advancing
    if (step > currentStep) {
        if (currentStep === 1) {
            const selectedVehicle = document.querySelector('input[name="vehicle_id"]:checked');
            if (!selectedVehicle) {
                alert('Please select a vehicle or add a new one to continue.');
                return;
            }
        }
        if (currentStep === 2) {
            const checkedCount = document.querySelectorAll('.service-checkbox:checked').length;
            if (checkedCount === 0) {
                alert('Please select at least one service to proceed.');
                return;
            }
        }
        if (currentStep === 3) {
            const date = document.getElementById('preferred_pickup_date').value;
            const time = document.getElementById('preferred_pickup_time').value;
            const address = document.getElementById('pickup_address').value;
            const city = document.getElementById('pickup_city').value;
            const pincode = document.getElementById('pickup_pincode').value;

            if (!date || !time || !address || !city || !pincode) {
                alert('Please fill in all mandatory pickup scheduling fields.');
                return;
            }
            prepareReviewSummary();
        }
    }

    document.querySelectorAll('.wizard-step-content').forEach(el => el.classList.add('d-none'));
    const targetContent = document.getElementById('step-content-' + step);
    if (targetContent) {
        targetContent.classList.remove('d-none');
    }

    // Update step indicators
    for (let i = 1; i <= totalSteps; i++) {
        const ind = document.getElementById('step-indicator-' + i);
        if (ind) {
            ind.classList.remove('active', 'completed');
            if (i < step) ind.classList.add('completed');
            else if (i === step) ind.classList.add('active');
        }
    }

    currentStep = step;
    window.scrollTo({ top: 120, behavior: 'smooth' });
}

function updateServiceCalculation() {
    selectedServices = [];
    baseTotal = 0;

    const checkboxes = document.querySelectorAll('.service-checkbox:checked');
    const listSummary = document.getElementById('summary-services-list');
    if (listSummary) listSummary.innerHTML = '';

    checkboxes.forEach(cb => {
        const id = cb.value;
        const name = cb.getAttribute('data-name');
        const price = parseFloat(cb.getAttribute('data-price') || 0);

        selectedServices.push({ id, name, price });
        baseTotal += price;

        if (listSummary) {
            const li = document.createElement('li');
            li.className = 'd-flex justify-content-between align-items-center py-1 text-light border-bottom border-dark';
            li.innerHTML = `<span><i class="fa-solid fa-check text-danger me-2"></i>${name}</span> <strong>₹ ${price.toFixed(2)}</strong>`;
            listSummary.appendChild(li);
        }
    });

    const subtotalEl = document.getElementById('summary-subtotal');
    const taxEl = document.getElementById('summary-tax');
    const grandTotalEl = document.getElementById('summary-grand-total');

    if (subtotalEl) subtotalEl.textContent = '₹ ' + baseTotal.toFixed(2);
    const tax = baseTotal * 0.18; // 18% GST standard
    if (taxEl) taxEl.textContent = '₹ ' + tax.toFixed(2);
    const grandTotal = baseTotal + tax;
    if (grandTotalEl) grandTotalEl.textContent = '₹ ' + grandTotal.toFixed(2);

    const hiddenTotal = document.getElementById('hidden_total_amount');
    if (hiddenTotal) hiddenTotal.value = grandTotal.toFixed(2);
}

function prepareReviewSummary() {
    // Populate review labels on Step 4
    const selectedVeh = document.querySelector('input[name="vehicle_id"]:checked');
    if (selectedVeh) {
        document.getElementById('rev-vehicle-name').textContent = selectedVeh.getAttribute('data-title');
        document.getElementById('rev-vehicle-reg').textContent = selectedVeh.getAttribute('data-reg');
    }

    const dateVal = document.getElementById('preferred_pickup_date').value;
    const timeVal = document.getElementById('preferred_pickup_time').value;
    document.getElementById('rev-pickup-datetime').textContent = dateVal + ' at ' + timeVal;

    const addr = document.getElementById('pickup_address').value;
    const city = document.getElementById('pickup_city').value;
    const pin = document.getElementById('pickup_pincode').value;
    document.getElementById('rev-pickup-address').textContent = addr + ', ' + city + ' - ' + pin;
}
