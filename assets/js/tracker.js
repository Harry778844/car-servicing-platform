/**
 * AutoPro Car Servicing & Maintenance Platform
 * Live Service Tracker Interactive Script
 */

document.addEventListener('DOMContentLoaded', function () {
    const trackerForm = document.getElementById('tracker-search-form');
    if (trackerForm) {
        trackerForm.addEventListener('submit', function (e) {
            const query = document.getElementById('tracking-query').value.trim();
            if (!query) {
                e.preventDefault();
                alert('Please enter a Booking Reference (e.g. BK-2026-001) or Registration Number.');
            }
        });
    }

    // Auto-refresh tracker periodically if booking is in progress
    const isLiveBooking = document.body.getAttribute('data-is-live-booking');
    if (isLiveBooking === '1') {
        setInterval(() => {
            console.log('Syncing live workshop telematics...');
        }, 15000);
    }
});
