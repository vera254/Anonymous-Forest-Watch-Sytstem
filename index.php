<?php
// Fetch stats for the "Impact So Far" section
$conn = new mysqli('localhost', 'root', '', 'forest_watch');
$total_reports = 0;
$resolved_reports = 0;
if (!$conn->connect_error) {
    $total = $conn->query("SELECT COUNT(*) as c FROM reports")->fetch_assoc();
    $resolved = $conn->query("SELECT COUNT(*) as c FROM reports WHERE status='resolved'")->fetch_assoc();
    $total_reports = $total['c'] ?? 0;
    $resolved_reports = $resolved['c'] ?? 0;
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Forest Watch Kenya | Protect Our Forests</title>
    
    <!-- Bootstrap 5 + Icons + Leaflet + Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: #1f2e2a;
            min-height: 100vh;
            position: relative;
        }

        /* Full-screen background image (forest) */
        .fullscreen-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1542273917363-3b1817f69a2d?w=1920') no-repeat center center fixed;
            background-size: cover;
            z-index: -2;
        }

        /* Dark overlay for readability */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: -1;
        }

        /* Main content wrapper */
        .wrapper {
            position: relative;
            z-index: 1;
            padding: 1rem 0 2rem;
        }

        /* Navbar – glass effect */
        .navbar {
            background: rgba(30, 58, 47, 0.85);
            backdrop-filter: blur(12px);
            border-radius: 2rem;
            margin-top: 1rem;
            padding: 0.5rem 1.5rem;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .navbar-brand, .nav-link {
            color: white !important;
            font-weight: 600;
            letter-spacing: -0.2px;
        }

        .nav-link:hover {
            color: #d4ffd4 !important;
            transform: translateY(-1px);
            transition: 0.2s;
        }

        /* Glassmorphism cards */
        .glass-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(8px);
            border-radius: 1.5rem;
            border: none;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 40px rgba(0, 0, 0, 0.25);
        }

        .card-header {
            background: linear-gradient(135deg, #1f4e3c, #2a6b4e);
            color: white;
            border-radius: 1.5rem 1.5rem 0 0 !important;
            padding: 1rem 1.5rem;
            border: none;
            font-weight: 700;
        }

        /* Modern buttons */
        .btn-primary-custom {
            background: linear-gradient(135deg, #1f4e3c, #2a6b4e);
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 3rem;
            font-weight: 600;
            color: white;
            transition: all 0.2s;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .btn-primary-custom:hover {
            transform: scale(1.02);
            background: linear-gradient(135deg, #163f30, #1f5a41);
            color: white;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.15);
        }

        /* Map styling */
        #map {
            height: 320px;
            border-radius: 1rem;
            overflow: hidden;
            border: 2px solid #ffffff80;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* Feature icons */
        .feature-icon {
            font-size: 2.2rem;
            background: linear-gradient(135deg, #1f4e3c, #2a6b4e);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.5rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #1f4e3c, #2a6b4e);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        /* Footer */
        footer {
            background: rgba(27, 67, 50, 0.85);
            backdrop-filter: blur(8px);
            color: white;
            border-radius: 2rem 2rem 0 0;
            padding: 1rem 0;
            margin-top: 2rem;
            font-size: 0.85rem;
        }

        /* Emergency banner */
        .emergency-banner {
            background: rgba(255, 193, 7, 0.9);
            backdrop-filter: blur(4px);
            border-radius: 3rem;
            padding: 0.5rem 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 600;
            color: #3b2e00;
        }

        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fade {
            animation: fadeInUp 0.6s ease-out;
        }

        @media (max-width: 768px) {
            .glass-card { margin-bottom: 1rem; }
            .navbar { margin-top: 0.5rem; }
            .stat-number { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

<!-- Full-screen forest background -->
<div class="fullscreen-bg"></div>
<div class="overlay"></div>

<div class="wrapper">
    <div class="container">
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">
                    <i class="fas fa-tree me-2"></i>Forest Watch Kenya
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#trackModal">
                                <i class="fas fa-search me-1"></i>Track Report
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/dashboard.php">
                                <i class="fas fa-shield-alt me-1"></i>Admin Login
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="text-center my-4 animate-fade">
            <h1 class="display-4 fw-bold text-white">Protect Our Forests</h1>
            <p class="lead text-white-50">Report illegal logging, fires, and encroachment anonymously</p>
            <div class="emergency-banner mt-2">
                <i class="fas fa-phone-alt fa-fw"></i>
                <strong>Emergency? Call Kenya Forest Service: 0800 722 203</strong>
            </div>
        </div>

        <div class="row g-4 animate-fade">
            <!-- Submit Report (Left) -->
            <div class="col-lg-6">
                <div class="glass-card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-pen-alt me-2"></i>Submit Anonymous Report</h4>
                        <small>Your identity is protected by law</small>
                    </div>
                    <div class="card-body p-4">
                        <form id="reportForm" action="api/submit_report.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Violation Type *</label>
                                <select name="violation_type" id="violation_type" class="form-select" required>
                                    <option value="">Select violation type</option>
                                    <option value="Illegal Logging">🌳 Illegal Logging</option>
                                    <option value="Forest Fire">🔥 Forest Fire</option>
                                    <option value="Encroachment">🏠 Encroachment/Settlement</option>
                                    <option value="Charcoal Burning">🪵 Illegal Charcoal Burning</option>
                                    <option value="Poaching">🦒 Poaching</option>
                                    <option value="Waste Dumping">🗑️ Waste Dumping</option>
                                    <option value="Other">❓ Other</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Describe what you saw (location details, time, number of people involved, etc.)"></textarea>
                                <div class="form-text">Optional but helpful for investigation</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Location on Map *</label>
                                <div id="map"></div>
                                <input type="hidden" name="latitude" id="latitude" required>
                                <input type="hidden" name="longitude" id="longitude" required>
                                <div class="form-text">Click anywhere on the map to mark the exact location</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold"><i class="fas fa-camera"></i> Upload Evidence (Optional)</label>
                                <input type="file" name="photo" class="form-control" accept="image/*" onchange="previewImage(this)">
                                <div class="form-text">Max 5MB. JPG, PNG, or GIF only. Photos are private to ministry.</div>
                                <div id="imagePreview" class="mt-2"></div>
                            </div>

                            <button type="submit" class="btn btn-primary-custom w-100">
                                <i class="fas fa-paper-plane me-2"></i>Submit Anonymous Report
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- How It Works + Stats (Right) -->
            <div class="col-lg-6">
                <div class="glass-card h-100">
                    <div class="card-header bg-info text-white" style="background: linear-gradient(135deg, #1f5a7e, #2a7f6e);">
                        <h4 class="mb-0"><i class="fas fa-info-circle me-2"></i>How It Works</h4>
                    </div>
                    <div class="card-body p-4">
                        <div class="row text-center mb-4">
                            <div class="col-md-4">
                                <div class="feature-icon"><i class="fas fa-user-secret"></i></div>
                                <h6 class="fw-bold mt-2">100% Anonymous</h6>
                                <p class="small text-muted">No name, email, or phone required</p>
                            </div>
                            <div class="col-md-4">
                                <div class="feature-icon"><i class="fas fa-map-marker-alt"></i></div>
                                <h6 class="fw-bold mt-2">Pin Location</h6>
                                <p class="small text-muted">Click on the map to mark</p>
                            </div>
                            <div class="col-md-4">
                                <div class="feature-icon"><i class="fas fa-qrcode"></i></div>
                                <h6 class="fw-bold mt-2">Get Tracking ID</h6>
                                <p class="small text-muted">Track your report status</p>
                            </div>
                        </div>

                        <hr>

                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>What happens next?</strong><br>
                            Ministry officers receive your report instantly, verify the location, and dispatch rangers for investigation. You remain completely anonymous throughout the process.
                        </div>

                        <div class="alert alert-secondary">
                            <i class="fas fa-lock me-2"></i>
                            <strong>Your Privacy is Protected</strong><br>
                            ✓ No IP address logging<br>
                            ✓ Photo metadata removed automatically<br>
                            ✓ Encrypted data transmission<br>
                            ✓ Compliant with Kenya Data Protection Act
                        </div>

                        <!-- Impact Stats -->
                        <div class="bg-light p-3 rounded-4 text-center mt-3">
                            <h6 class="fw-bold"><i class="fas fa-chart-line me-1"></i>Impact So Far</h6>
                            <div class="row mt-2">
                                <div class="col-6">
                                    <div class="stat-number" id="totalReports"><?php echo $total_reports; ?></div>
                                    <small class="text-muted">Total Reports</small>
                                </div>
                                <div class="col-6">
                                    <div class="stat-number" id="resolvedReports"><?php echo $resolved_reports; ?></div>
                                    <small class="text-muted">Resolved</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="text-center mt-4">
            <div class="container">
                <p class="mb-1"><i class="fas fa-tree me-2"></i>Forest Watch Kenya</p>
                <p class="small mb-1">State Department for Forestry - Ministry of Environment, Climate Change & Forestry</p>
                <p class="small mb-0">
                    <a href="#" class="text-white text-decoration-none">Privacy Policy</a> | 
                    <a href="#" class="text-white text-decoration-none">Terms of Use</a> |
                    <a href="#" class="text-white text-decoration-none">Contact</a>
                </p>
            </div>
        </footer>
    </div>
</div>

<!-- Track Modal -->
<div class="modal fade" id="trackModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card" style="background: rgba(255,255,255,0.96);">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-search me-2"></i>Track Your Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tracking ID</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-qrcode"></i></span>
                        <input type="text" id="tracking_id" class="form-control" placeholder="Example: FR-260513-ABC7" required>
                    </div>
                    <div class="form-text">Enter the tracking ID you received after submission</div>
                </div>
                <button onclick="trackReport()" class="btn btn-primary-custom w-100">Check Status</button>
                <div id="trackResult" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-check-circle me-2"></i>Report Submitted!</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <i class="fas fa-tree fa-3x text-success mb-3"></i>
                <h5>Thank You for Reporting</h5>
                <p>Your anonymous report has been received.</p>
                <div class="alert alert-info">
                    <strong>Tracking ID:</strong>
                    <h3 id="successTrackingId" class="mt-2"></h3>
                </div>
                <p class="small text-muted">Save this ID to check the status of your report later.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // Map
    let map, marker;
    const kenyaCenter = [0.0236, 37.9062];

    function initMap() {
        map = L.map('map').setView(kenyaCenter, 6);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> contributors'
        }).addTo(map);
        map.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            if (marker) marker.setLatLng([lat, lng]);
            else marker = L.marker([lat, lng]).addTo(map);
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
            marker.bindPopup(`📍 Location: ${lat.toFixed(4)}, ${lng.toFixed(4)}`).openPopup();
        });
    }
    document.addEventListener('DOMContentLoaded', initMap);

    // Preview image
    function previewImage(input) {
        const preview = document.getElementById('imagePreview');
        preview.innerHTML = '';
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => preview.innerHTML = `<img src="${e.target.result}" class="img-fluid rounded mt-2" style="max-height:150px">`;
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Submit report
    document.getElementById('reportForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const lat = document.getElementById('latitude').value;
        const lng = document.getElementById('longitude').value;
        if (!lat || !lng) {
            alert('⚠️ Please click on the map to mark the location');
            return;
        }
        const btn = this.querySelector('button[type="submit"]');
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
        const formData = new FormData(this);
        try {
            const res = await fetch('api/submit_report.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                document.getElementById('successTrackingId').textContent = data.tracking_id;
                new bootstrap.Modal(document.getElementById('successModal')).show();
                this.reset();
                if (marker) { map.removeLayer(marker); marker = null; }
                document.getElementById('latitude').value = '';
                document.getElementById('longitude').value = '';
                document.getElementById('imagePreview').innerHTML = '';
                // update stats
                const statsRes = await fetch('api/stats.php');
                const stats = await statsRes.json();
                if (stats.total) document.getElementById('totalReports').innerText = stats.total;
                if (stats.resolved) document.getElementById('resolvedReports').innerText = stats.resolved;
            } else {
                alert('Error: ' + (data.error || 'Submission failed'));
            }
        } catch (err) {
            alert('Network error. Please try again.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    });

    // Track report
    async function trackReport() {
        const id = document.getElementById('tracking_id').value.trim();
        const resultDiv = document.getElementById('trackResult');
        if (!id) {
            resultDiv.innerHTML = '<div class="alert alert-danger">Please enter a tracking ID</div>';
            return;
        }
        resultDiv.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin text-success"></i> Checking...</div>';
        try {
            const res = await fetch(`api/track.php?id=${encodeURIComponent(id)}`);
            const data = await res.json();
            if (data.status) {
                const statusClass = data.status === 'pending' ? 'warning' : (data.status === 'investigating' ? 'info' : 'success');
                const statusText = data.status === 'pending' ? '⏳ Pending Review' : (data.status === 'investigating' ? '🔍 Under Investigation' : '✅ Resolved - Action Taken');
                resultDiv.innerHTML = `<div class="alert alert-${statusClass} mt-3">
                    <strong>Status:</strong> ${statusText}<br>
                    <strong>Submitted:</strong> ${data.created_at}<br>
                    <strong>Last Updated:</strong> ${data.updated_at}
                </div>`;
            } else {
                resultDiv.innerHTML = `<div class="alert alert-danger">${data.error || 'Report not found'}</div>`;
            }
        } catch (err) {
            resultDiv.innerHTML = '<div class="alert alert-danger">Network error. Please try again.</div>';
        }
    }

    // Initial stats refresh
    async function refreshStats() {
        try {
            const res = await fetch('api/stats.php');
            const stats = await res.json();
            if (stats.total) document.getElementById('totalReports').innerText = stats.total;
            if (stats.resolved) document.getElementById('resolvedReports').innerText = stats.resolved;
        } catch(e) {}
    }
    refreshStats();
</script>
</body>
</html>