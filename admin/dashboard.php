<?php
session_start();
$password_correct = 'KenyaForest2024';

// Login logic
if (isset($_POST['login_password'])) {
    if ($_POST['login_password'] === $password_correct) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_name'] = 'Forest Admin';
    } else {
        $login_error = 'Invalid password. Please try again.';
    }
}
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
if (!isset($_SESSION['admin_logged_in'])) {
    // Clean login page (no password hint)
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Admin Login | Forest Watch</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body {
                background: linear-gradient(rgba(0,0,0,0.65), rgba(0,0,0,0.65)),
                            url('https://images.unsplash.com/photo-1542273917363-3b1817f69a2d?w=1920') no-repeat center center fixed;
                background-size: cover;
                min-height: 100vh;
                display: flex;
                align-items: center;
                font-family: 'Segoe UI', Roboto, system-ui;
            }
            .login-card {
                max-width: 400px;
                margin: auto;
                border-radius: 20px;
                background: white;
                box-shadow: 0 20px 40px rgba(0,0,0,0.3);
                overflow: hidden;
            }
            .login-header {
                background: #2d6a4f;
                color: white;
                padding: 1.5rem;
                text-align: center;
            }
            .login-header i { font-size: 2.5rem; margin-bottom: 0.5rem; }
            .login-header h3 { margin: 0; font-weight: 600; }
            .login-body { padding: 2rem; }
            .btn-login { background: #2d6a4f; border: none; width: 100%; padding: 0.6rem; font-weight: 600; }
            .btn-login:hover { background: #1b4332; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="login-card">
                <div class="login-header">
                    <i class="fas fa-tree"></i>
                    <h3>Forest Watch</h3>
                    <small>Administrator Login</small>
                </div>
                <div class="login-body">
                    <?php if(isset($login_error)): ?>
                        <div class="alert alert-danger"><?php echo $login_error; ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Admin Password</label>
                            <input type="password" name="login_password" class="form-control" placeholder="Enter password" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-login text-white"><i class="fas fa-lock me-2"></i>Access Dashboard</button>
                    </form>
                    <hr class="my-4">
                    <div class="text-center text-muted small"><i class="fas fa-shield-alt me-1"></i> Authorized personnel only</div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
    exit;
}

// ==================== DATABASE CONNECTION ====================
$conn = new mysqli('localhost', 'root', '', 'forest_watch');
if ($conn->connect_error) die("Database connection failed");

// ==================== AJAX HANDLERS (update status, delete) ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    if ($action === 'update_status') {
        $id = intval($_POST['id']);
        $status = $_POST['status'];
        $stmt = $conn->prepare("UPDATE reports SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        echo json_encode(['success' => $stmt->execute()]);
        exit;
    }
    if ($action === 'delete_report') {
        $id = intval($_POST['id']);
        $res = $conn->query("SELECT photo_path FROM reports WHERE id = $id");
        if ($row = $res->fetch_assoc() && $row['photo_path']) @unlink('../uploads/' . $row['photo_path']);
        $stmt = $conn->prepare("DELETE FROM reports WHERE id = ?");
        $stmt->bind_param("i", $id);
        echo json_encode(['success' => $stmt->execute()]);
        exit;
    }
    exit;
}

// ==================== FILTERS, PAGINATION ====================
$filter_status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$where = "1=1";
$params = [];
if ($filter_status !== 'all') {
    $where .= " AND status = ?";
    $params[] = $filter_status;
}
if ($search) {
    $where .= " AND (tracking_id LIKE ? OR violation_type LIKE ? OR description LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like]);
}

// Count total rows
$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM reports WHERE $where");
if ($params) $count_stmt->bind_param(str_repeat('s', count($params)), ...$params);
$count_stmt->execute();
$total_rows = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Fetch reports for current page
$sql = "SELECT id, tracking_id, violation_type, description, latitude, longitude, photo_path, status, created_at FROM reports WHERE $where ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat('s', count($params)-2) . 'ii', ...$params);
$stmt->execute();
$reports = $stmt->get_result();

// Statistics for cards
$total_all = $conn->query("SELECT COUNT(*) as c FROM reports")->fetch_assoc()['c'];
$pending = $conn->query("SELECT COUNT(*) as c FROM reports WHERE status='pending'")->fetch_assoc()['c'];
$investigating = $conn->query("SELECT COUNT(*) as c FROM reports WHERE status='investigating'")->fetch_assoc()['c'];
$resolved = $conn->query("SELECT COUNT(*) as c FROM reports WHERE status='resolved'")->fetch_assoc()['c'];

// Chart data
$monthly = [];
$res = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as total FROM reports WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY month ORDER BY month");
while ($row = $res->fetch_assoc()) $monthly[] = $row;

$violations = [];
$res = $conn->query("SELECT violation_type, COUNT(*) as count FROM reports GROUP BY violation_type");
while ($row = $res->fetch_assoc()) $violations[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forest Watch | Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        /* ========== BACKGROUND (SOFT FOREST GRADIENT + NOISE) ========== */
        body {
            background: linear-gradient(135deg, #e8f0e8 0%, #d4e4d4 100%);
            background-attachment: fixed;
            font-family: 'Segoe UI', Roboto, system-ui;
            position: relative;
        }
        body::before {
            content: "";
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" opacity="0.04"><path fill="none" stroke="%232d6a4f" stroke-width="0.5" d="M10 10 L90 10 M10 20 L90 20 M10 30 L90 30 M10 40 L90 40 M10 50 L90 50 M10 60 L90 60 M10 70 L90 70 M10 80 L90 80 M10 90 L90 90 M10 10 L10 90 M20 10 L20 90 M30 10 L30 90 M40 10 L40 90 M50 10 L50 90 M60 10 L60 90 M70 10 L70 90 M80 10 L80 90 M90 10 L90 90"/></svg>');
            background-repeat: repeat;
            pointer-events: none;
            z-index: -1;
        }

        /* ========== SIDEBAR (GLASS + ACTIVE INDICATOR) ========== */
        .sidebar {
            position: fixed; left: 0; top: 0; bottom: 0; width: 260px;
            background: rgba(30, 58, 47, 0.94);
            backdrop-filter: blur(8px);
            color: white;
            z-index: 1000;
            transition: all 0.3s;
            overflow-y: auto;
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            border-radius: 0 1.5rem 1.5rem 0;
        }
        .sidebar-header { padding: 1.5rem; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.2); }
        .sidebar-header i { font-size: 2rem; }
        .sidebar-header h4 { font-size: 1.2rem; margin-top: 0.5rem; font-weight: 600; }
        .sidebar-nav a {
            display: block; padding: 0.7rem 1.5rem; color: rgba(255,255,255,0.85);
            text-decoration: none; transition: all 0.2s; font-size: 0.9rem; font-weight: 500;
        }
        .sidebar-nav a i { width: 1.6rem; margin-right: 0.5rem; transition: transform 0.2s; }
        .sidebar-nav a:hover { background: rgba(255,255,255,0.15); color: white; padding-left: 1.8rem; }
        .sidebar-nav a:hover i { transform: translateX(4px); }
        .sidebar-nav a.active {
            background: rgba(255,255,255,0.15);
            border-left: 4px solid #ffc107;
            box-shadow: inset 0 0 8px rgba(0,0,0,0.1);
            color: white;
        }

        /* ========== MAIN CONTENT ========== */
        .main-content { margin-left: 260px; padding: 1.2rem; transition: 0.3s; }

        /* ========== GLASS CARDS ========== */
        .stat-card, .chart-card {
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(6px);
            border-radius: 1.25rem;
            border: 1px solid rgba(255,255,255,0.4);
            transition: all 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(0,0,0,0.05);
        }
        .stat-card { padding: 1rem; }
        .stat-card:hover {
            transform: translateY(-5px) scale(1.02);
            background: white;
            box-shadow: 0 20px 30px -10px rgba(0,0,0,0.15);
        }
        .stat-number { font-size: 1.8rem; font-weight: 800; line-height: 1.2; color: #2d6a4f; }
        .stat-label { font-size: 0.75rem; color: #4a5b52; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }

        .chart-card { padding: 1rem; height: 100%; }
        .chart-card:hover { box-shadow: 0 12px 20px -8px rgba(0,0,0,0.1); }

        /* ========== BUTTONS WITH RIPPLE EFFECT ========== */
        .btn {
            position: relative;
            overflow: hidden;
            transition: all 0.2s;
            border-radius: 2rem;
            font-weight: 500;
        }
        .btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transform: translate(-50%, -50%);
            transition: width 0.4s, height 0.4s;
        }
        .btn:active::after {
            width: 100%;
            height: 100%;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .btn-forest { background: #2d6a4f; color: white; border: none; }
        .btn-forest:hover { background: #1b4332; }

        /* ========== TABLE STYLES ========== */
        .table {
            background: white;
            border-radius: 1rem;
            overflow: hidden;
        }
        .table th {
            background: #f4f7f4;
            font-weight: 600;
            border-bottom: none;
            color: #2d4a3a;
        }
        .table-hover tbody tr {
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        .table-hover tbody tr:hover {
            background-color: #f8fff4;
            border-left-color: #2d6a4f;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        .badge {
            font-weight: 500;
            padding: 0.35rem 0.75rem;
            border-radius: 2rem;
            letter-spacing: 0.3px;
        }
        .badge.bg-warning { background: #ffc107 !important; color: #2d2a1a; }
        .badge.bg-info { background: #0dcaf0 !important; color: #032830; }
        .badge.bg-success { background: #198754 !important; color: white; }

        /* ========== TOPBAR USER AVATAR & DROPDOWN ========== */
        .user-avatar {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, #2d6a4f, #1b4332);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .dropdown-menu {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
            animation: fadeInUp 0.2s ease;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ========== PAGINATION ========== */
        .pagination .page-item .page-link {
            border: none;
            margin: 0 4px;
            border-radius: 2rem;
            color: #2d6a4f;
            font-weight: 500;
            transition: 0.2s;
        }
        .pagination .page-item.active .page-link {
            background: #2d6a4f;
            color: white;
            box-shadow: 0 2px 6px rgba(45,106,79,0.3);
        }
        .pagination .page-item .page-link:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: 260px; }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .stat-number { font-size: 1.4rem; }
        }
        @media print {
            .sidebar, .no-print, .stat-card, .chart-card, .navbar, .filters-bar, .pagination, .action-buttons { display: none !important; }
            .main-content { margin: 0; padding: 0; }
            .table { width: 100%; border-collapse: collapse; }
            .table th, .table td { border: 1px solid #aaa; padding: 6px; }
        }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-tree"></i>
            <h4>Forest Watch</h4>
            <small>Ministry of Environment</small>
        </div>
        <div class="sidebar-nav">
            <a href="?status=all" class="<?php echo $filter_status=='all'?'active':''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="?status=all" class="<?php echo $filter_status=='all'?'active':''; ?>"><i class="fas fa-list"></i> All Reports</a>
            <a href="?status=pending" class="<?php echo $filter_status=='pending'?'active':''; ?>"><i class="fas fa-clock"></i> Pending</a>
            <a href="?status=investigating" class="<?php echo $filter_status=='investigating'?'active':''; ?>"><i class="fas fa-search"></i> Investigating</a>
            <a href="?status=resolved" class="<?php echo $filter_status=='resolved'?'active':''; ?>"><i class="fas fa-check-circle"></i> Resolved</a>
            <hr style="border-color:rgba(255,255,255,0.2); margin:0.8rem 1rem;">
            <a href="?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <!-- Top bar -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <button class="btn btn-sm btn-outline-secondary d-md-none me-2" id="menuToggle"><i class="fas fa-bars"></i></button>
                <span class="fs-5 fw-semibold"><i class="fas fa-tree text-success me-2"></i>Admin Dashboard</span>
            </div>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-success dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['admin_name'], 0, 1)); ?></div>
                    <span><?php echo $_SESSION['admin_name']; ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="?logout=1"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>

        <!-- Stats cards row -->
        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="stat-card" onclick="window.location='?status=all'"><div class="stat-number"><?php echo $total_all; ?></div><div class="stat-label">Total Reports</div></div></div>
            <div class="col-md-3"><div class="stat-card" onclick="window.location='?status=pending'"><div class="stat-number text-warning"><?php echo $pending; ?></div><div class="stat-label">Pending</div></div></div>
            <div class="col-md-3"><div class="stat-card" onclick="window.location='?status=investigating'"><div class="stat-number text-info"><?php echo $investigating; ?></div><div class="stat-label">Investigating</div></div></div>
            <div class="col-md-3"><div class="stat-card" onclick="window.location='?status=resolved'"><div class="stat-number text-success"><?php echo $resolved; ?></div><div class="stat-label">Resolved</div></div></div>
        </div>

        <!-- Charts row -->
        <div class="row g-3 mb-4">
            <div class="col-md-7"><div class="chart-card"><h6 class="mb-2"><i class="fas fa-chart-line me-1"></i>Monthly Trends</h6><canvas id="trendsChart" height="180"></canvas></div></div>
            <div class="col-md-5"><div class="chart-card"><h6 class="mb-2"><i class="fas fa-chart-pie me-1"></i>Violation Distribution</h6><canvas id="violationChart" height="180"></canvas></div></div>
        </div>

        <!-- Filters & export -->
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-body py-3">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label class="small text-muted fw-semibold">Status</label>
                        <select name="status" class="form-select form-select-sm rounded-pill">
                            <option value="all" <?php if($filter_status=='all') echo 'selected'; ?>>All</option>
                            <option value="pending" <?php if($filter_status=='pending') echo 'selected'; ?>>Pending</option>
                            <option value="investigating" <?php if($filter_status=='investigating') echo 'selected'; ?>>Investigating</option>
                            <option value="resolved" <?php if($filter_status=='resolved') echo 'selected'; ?>>Resolved</option>
                        </select>
                    </div>
                    <div class="col">
                        <label class="small text-muted fw-semibold">Search</label>
                        <input type="text" name="search" class="form-control form-control-sm rounded-pill" placeholder="Tracking ID / violation / description" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-forest btn-sm px-4 rounded-pill"><i class="fas fa-search me-1"></i> Filter</button>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-success btn-sm rounded-pill" id="exportCsvBtn"><i class="fas fa-file-excel me-1"></i> CSV</button>
                        <button type="button" class="btn btn-info btn-sm rounded-pill text-white" id="printBtn"><i class="fas fa-print me-1"></i> Print</button>
                        <button type="button" class="btn btn-warning btn-sm rounded-pill" id="pdfBtn"><i class="fas fa-file-pdf me-1"></i> PDF</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reports table -->
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-0"><strong><i class="fas fa-list me-2"></i> Reports (<?php echo $total_rows; ?>)</strong></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="reports-table">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-3">Tracking ID</th>
                                <th>Violation</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th class="text-center" style="width:110px">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($reports->num_rows == 0): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">No reports found</td></tr>
                            <?php else: while ($row = $reports->fetch_assoc()): ?>
                                <tr id="row-<?php echo $row['id']; ?>">
                                    <td class="ps-3"><code class="fw-bold"><?php echo htmlspecialchars($row['tracking_id']); ?></code></td>
                                    <td><?php echo htmlspecialchars($row['violation_type']); ?></td>
                                    <td><?php echo round($row['latitude'],4).', '.round($row['longitude'],4); ?></td>
                                    <td><span class="badge bg-<?php echo $row['status']=='pending'?'warning':($row['status']=='investigating'?'info':'success'); ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary rounded-circle view-btn" style="width:32px;height:32px;" data-id="<?php echo $row['id']; ?>" data-tracking="<?php echo htmlspecialchars($row['tracking_id']); ?>" data-type="<?php echo htmlspecialchars($row['violation_type']); ?>" data-desc="<?php echo htmlspecialchars($row['description']); ?>" data-lat="<?php echo $row['latitude']; ?>" data-lng="<?php echo $row['longitude']; ?>" data-photo="<?php echo $row['photo_path']; ?>" data-status="<?php echo $row['status']; ?>" data-created="<?php echo $row['created_at']; ?>"><i class="fas fa-eye"></i></button>
                                        <button class="btn btn-sm btn-outline-warning rounded-circle edit-status" style="width:32px;height:32px;" data-id="<?php echo $row['id']; ?>" data-status="<?php echo $row['status']; ?>"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-outline-danger rounded-circle delete-report" style="width:32px;height:32px;" data-id="<?php echo $row['id']; ?>"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav class="mt-4"><ul class="pagination justify-content-center">
            <li class="page-item <?php if($page<=1) echo 'disabled'; ?>"><a class="page-link rounded-pill" href="?page=<?php echo $page-1; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>">Previous</a></li>
            <?php for($i=1;$i<=$total_pages;$i++): ?>
                <li class="page-item <?php if($i==$page) echo 'active'; ?>"><a class="page-link rounded-pill" href="?page=<?php echo $i; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a></li>
            <?php endfor; ?>
            <li class="page-item <?php if($page>=$total_pages) echo 'disabled'; ?>"><a class="page-link rounded-pill" href="?page=<?php echo $page+1; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>">Next</a></li>
        </ul></nav>
        <?php endif; ?>
    </div>

    <!-- Modals -->
    <div class="modal fade" id="viewModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content rounded-4"><div class="modal-header bg-success text-white"><h5 class="modal-title"><i class="fas fa-file-alt me-2"></i>Report Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="viewModalBody"></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div></div></div></div>
    <div class="modal fade" id="statusModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content rounded-4"><div class="modal-header bg-info text-white"><h5 class="modal-title"><i class="fas fa-edit me-2"></i>Update Status</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" id="status_report_id"><label class="form-label fw-semibold">New Status</label><select id="new_status" class="form-select rounded-pill"><option value="pending">Pending</option><option value="investigating">Investigating</option><option value="resolved">Resolved</option></select></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="confirmStatusUpdate">Update</button></div></div></div></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle for mobile
        document.getElementById('menuToggle')?.addEventListener('click', () => document.getElementById('sidebar').classList.toggle('show'));

        // Charts
        const monthlyData = <?php echo json_encode($monthly); ?>;
        const violationData = <?php echo json_encode($violations); ?>;
        new Chart(document.getElementById('trendsChart'), { type: 'line', data: { labels: monthlyData.map(m=>m.month), datasets: [{ label: 'Reports', data: monthlyData.map(m=>m.total), borderColor: '#2d6a4f', fill: true, backgroundColor: 'rgba(45,106,79,0.1)', tension: 0.3 }] }, options: { responsive: true, maintainAspectRatio: true } });
        new Chart(document.getElementById('violationChart'), { type: 'doughnut', data: { labels: violationData.map(v=>v.violation_type), datasets: [{ data: violationData.map(v=>v.count), backgroundColor: ['#2d6a4f','#40916c','#52b788','#74c69d','#95d5b2','#b7e4c7'] }] }, options: { responsive: true, maintainAspectRatio: true } });

        // View report
        const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const d = btn.dataset;
                let photoHtml = d.photo ? `<hr><div class="text-center"><strong>Evidence Photo</strong><br><img src="../uploads/${d.photo}" class="img-fluid rounded" style="max-height:250px"><br><a href="../uploads/${d.photo}" download class="btn btn-sm btn-secondary mt-2">Download</a></div>` : '<hr><div class="alert alert-secondary">No photo uploaded</div>';
                document.getElementById('viewModalBody').innerHTML = `
                    <div class="row"><div class="col-md-6"><strong>Tracking ID:</strong> ${d.tracking}<br><strong>Violation:</strong> ${d.type}<br><strong>Status:</strong> ${d.status}<br><strong>Reported:</strong> ${d.created}</div>
                    <div class="col-md-6"><strong>Location:</strong> ${d.lat}, ${d.lng}<br><a href="https://www.google.com/maps?q=${d.lat},${d.lng}" target="_blank" class="btn btn-sm btn-outline-info mt-2">View on Map</a></div></div>
                    <hr><strong>Description:</strong><br>${d.desc || '<em>No description</em>'}${photoHtml}`;
                viewModal.show();
            });
        });

        // Update status modal
        const statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
        let currentId = null;
        document.querySelectorAll('.edit-status').forEach(btn => {
            btn.addEventListener('click', () => {
                currentId = btn.dataset.id;
                document.getElementById('status_report_id').value = currentId;
                document.getElementById('new_status').value = btn.dataset.status;
                statusModal.show();
            });
        });
        document.getElementById('confirmStatusUpdate').addEventListener('click', async () => {
            const newStatus = document.getElementById('new_status').value;
            const resp = await fetch(window.location.href, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }, body: `action=update_status&id=${currentId}&status=${newStatus}` });
            const data = await resp.json();
            if (data.success) location.reload(); else alert('Update failed');
        });

        // Delete report
        document.querySelectorAll('.delete-report').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!confirm('Delete this report permanently?')) return;
                const id = btn.dataset.id;
                const resp = await fetch(window.location.href, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }, body: `action=delete_report&id=${id}` });
                const data = await resp.json();
                if (data.success) location.reload(); else alert('Delete failed');
            });
        });

        // Export CSV
        document.getElementById('exportCsvBtn').addEventListener('click', () => { window.open('?export_csv=1&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>', '_blank'); });

        // Print report
        document.getElementById('printBtn').addEventListener('click', () => {
            const printWindow = window.open('', '_blank');
            const tableContent = document.getElementById('reports-table').cloneNode(true);
            printWindow.document.write(`
                <html><head><title>Forest Watch Reports</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>body{padding:20px} h2{color:#2d6a4f} .footer{margin-top:20px; text-align:center; font-size:12px; color:gray;} table{width:100%; border-collapse:collapse;} th,td{border:1px solid #ddd; padding:6px;} th{background:#2d6a4f; color:white;}</style>
                </head><body>
                <h2>🌳 Forest Watch - Incident Reports</h2>
                <p>Generated: ${new Date().toLocaleString()}</p>
                <p>Status: <?php echo ucfirst($filter_status); ?> | Search: <?php echo htmlspecialchars($search); ?></p>
                ${tableContent.outerHTML}
                <div class="footer">Ministry of Environment, Climate Change & Forestry</div>
                </body></html>`);
            printWindow.document.close();
            printWindow.print();
        });

        // PDF export using html2pdf
        document.getElementById('pdfBtn').addEventListener('click', () => {
            const originalTable = document.getElementById('reports-table').cloneNode(true);
            const wrapper = document.createElement('div');
            wrapper.innerHTML = `
                <div style="font-family: 'Segoe UI', sans-serif; padding: 20px;">
                    <h2 style="color:#2d6a4f;">🌳 Forest Watch - Incident Reports</h2>
                    <p>Date: ${new Date().toLocaleString()}</p>
                    <p>Status: <?php echo ucfirst($filter_status); ?> | Search: <?php echo htmlspecialchars($search); ?></p>
                    ${originalTable.outerHTML}
                    <hr><p style="text-align:center; font-size:12px; color:gray;">Ministry of Environment, Climate Change & Forestry</p>
                </div>
            `;
            const opt = { margin: [0.5,0.5,0.5,0.5], filename: `forest_reports_${new Date().toISOString().slice(0,19)}.pdf`, image: { type: 'jpeg', quality: 0.98 }, html2canvas: { scale: 2 }, jsPDF: { unit: 'in', format: 'a4', orientation: 'landscape' } };
            html2pdf().set(opt).from(wrapper).save();
        });
    </script>
</body>
</html>
<?php
// CSV export (must be at the end, after all output)
if (isset($_GET['export_csv'])) {
    $csv_where = "1=1";
    $csv_params = [];
    if ($filter_status !== 'all') {
        $csv_where .= " AND status = ?";
        $csv_params[] = $filter_status;
    }
    if ($search) {
        $csv_where .= " AND (tracking_id LIKE ? OR violation_type LIKE ? OR description LIKE ?)";
        $like = "%$search%";
        $csv_params = array_merge($csv_params, [$like, $like, $like]);
    }
    $csv_sql = "SELECT tracking_id, violation_type, description, latitude, longitude, status, created_at FROM reports WHERE $csv_where ORDER BY created_at DESC";
    $csv_stmt = $conn->prepare($csv_sql);
    if ($csv_params) $csv_stmt->bind_param(str_repeat('s', count($csv_params)), ...$csv_params);
    $csv_stmt->execute();
    $csv_res = $csv_stmt->get_result();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="forest_reports_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Tracking ID', 'Violation Type', 'Description', 'Latitude', 'Longitude', 'Status', 'Reported Date']);
    while ($row = $csv_res->fetch_assoc()) fputcsv($out, $row);
    fclose($out);
    exit;
}
$conn->close();
?>