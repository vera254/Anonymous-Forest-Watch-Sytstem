// Main JavaScript for Forest Watch System

// Global variables
let map, marker;
let currentPosition = null;

// Initialize map
function initMap() {
    const mapContainer = document.getElementById('map');
    if (!mapContainer) return;
    
    // Default center: Kenya
    const kenyaCenter = [0.0236, 37.9062];
    
    map = L.map('map').setView(kenyaCenter, 6);
    
    // Add tile layer
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> contributors',
        subdomains: 'abcd',
        maxZoom: 19,
        minZoom: 4
    }).addTo(map);
    
    // Add click handler
    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng]).addTo(map);
        }
        
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;
        
        marker.bindPopup(`
            <div class="text-center">
                <strong>📍 Selected Location</strong><br>
                Lat: ${lat.toFixed(6)}<br>
                Lng: ${lng.toFixed(6)}
            </div>
        `).openPopup();
    });
}

// Get user's current location
function getCurrentLocation() {
    if (!navigator.geolocation) {
        alert('Geolocation is not supported by your browser');
        return;
    }
    
    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng]).addTo(map);
            }
            
            map.setView([lat, lng], 13);
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
            
            marker.bindPopup('📍 Your current location').openPopup();
        },
        function(error) {
            let message = '';
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    message = 'Please allow location access to use this feature';
                    break;
                case error.POSITION_UNAVAILABLE:
                    message = 'Location information is unavailable';
                    break;
                case error.TIMEOUT:
                    message = 'Location request timed out';
                    break;
                default:
                    message = 'An unknown error occurred';
            }
            alert(message);
        }
    );
}

// Track report function
async function trackReport(event) {
    if (event) event.preventDefault();
    
    const trackingId = document.getElementById('tracking_id').value.trim();
    const resultDiv = document.getElementById('trackResult');
    
    if (!trackingId) {
        resultDiv.innerHTML = '<div class="alert alert-danger">Please enter a tracking ID</div>';
        return;
    }
    
    resultDiv.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x text-success"></i><br>Checking...</div>';
    
    try {
        const response = await fetch(`../api/track.php?id=${encodeURIComponent(trackingId)}`);
        const data = await response.json();
        
        if (data.status) {
            const statusColors = {
                'pending': 'warning',
                'investigating': 'info',
                'resolved': 'success'
            };
            
            const statusText = {
                'pending': '⏳ Pending Review',
                'investigating': '🔍 Under Investigation',
                'resolved': '✅ Resolved - Action Taken'
            };
            
            resultDiv.innerHTML = `
                <div class="alert alert-${statusColors[data.status]}">
                    <h6><i class="fas fa-info-circle me-2"></i>Report Status</h6>
                    <hr>
                    <strong>Tracking ID:</strong> ${data.id}<br>
                    <strong>Status:</strong> ${statusText[data.status]}<br>
                    <strong>Submitted:</strong> ${data.created_at}<br>
                    <strong>Last Updated:</strong> ${data.updated_at}
                </div>
            `;
        } else {
            resultDiv.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
        }
    } catch (error) {
        console.error('Error:', error);
        resultDiv.innerHTML = '<div class="alert alert-danger">Network error. Please try again.</div>';
    }
}

// Preview image before upload
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    if (!preview) return;
    
    preview.innerHTML = '';
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `
                <div class="mt-2">
                    <img src="${e.target.result}" style="max-width: 200px; max-height: 150px; border-radius: 8px; border: 1px solid #ddd;">
                    <br><small class="text-success">✓ Image ready for upload</small>
                </div>
            `;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Load dashboard statistics
async function loadDashboardStats() {
    try {
        const response = await fetch('../api/stats.php');
        const stats = await response.json();
        
        if (stats.total !== undefined) {
            document.getElementById('totalReports').textContent = stats.total;
            document.getElementById('resolvedReports').textContent = stats.resolved;
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

// Export reports to CSV
function exportToCSV(reports) {
    if (!reports || reports.length === 0) {
        alert('No data to export');
        return;
    }
    
    const headers = ['Tracking ID', 'Violation Type', 'Description', 'Latitude', 'Longitude', 'Status', 'Created At'];
    const csvRows = [];
    csvRows.push(headers.join(','));
    
    reports.forEach(report => {
        const row = [
            `"${report.tracking_id}"`,
            `"${report.violation_type}"`,
            `"${(report.description || '').replace(/"/g, '""')}"`,
            report.latitude,
            report.longitude,
            report.status,
            report.created_at
        ];
        csvRows.push(row.join(','));
    });
    
    const blob = new Blob([csvRows.join('\n')], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `forest_reports_${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initMap();
    
    // Add location button if exists
    const locationBtn = document.getElementById('getLocationBtn');
    if (locationBtn) {
        locationBtn.addEventListener('click', getCurrentLocation);
    }
    
    // Load stats on dashboard
    if (document.getElementById('totalReports')) {
        loadDashboardStats();
    }
});