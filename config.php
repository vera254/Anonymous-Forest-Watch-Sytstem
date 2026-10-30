<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'forest_watch');

// Application configuration
define('APP_NAME', 'Forest Watch System');
define('APP_VERSION', '1.0.0');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Create database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone
date_default_timezone_set('Africa/Nairobi');

// Function to sanitize input
function sanitize($input) {
    global $conn;
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)));
}

// Function to generate CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Function to verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Function to log admin activity
function logActivity($admin_id, $action, $report_id = null) {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (admin_id, action, report_id, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isiss", $admin_id, $action, $report_id, $ip, $user_agent);
    return $stmt->execute();
}

// Function to upload and compress image
function uploadImage($file, $tracking_id) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $filename = $file['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        return false;
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }
    
    $new_filename = $tracking_id . '_' . time() . '.' . $ext;
    $target_path = UPLOAD_DIR . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        // Compress image if GD is available
        if (function_exists('imagecreatefromjpeg')) {
            compressImage($target_path, $ext);
        }
        return $new_filename;
    }
    
    return false;
}

function compressImage($path, $ext) {
    list($width, $height) = getimagesize($path);
    $max_width = 1200;
    $max_height = 1200;
    
    if ($width > $max_width || $height > $max_height) {
        $ratio = min($max_width / $width, $max_height / $height);
        $new_width = $width * $ratio;
        $new_height = $height * $ratio;
        
        $src = null;
        switch($ext) {
            case 'jpg':
            case 'jpeg':
                $src = imagecreatefromjpeg($path);
                break;
            case 'png':
                $src = imagecreatefrompng($path);
                break;
            case 'gif':
                $src = imagecreatefromgif($path);
                break;
        }
        
        if ($src) {
            $dst = imagecreatetruecolor($new_width, $new_height);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            imagejpeg($dst, $path, 85);
            imagedestroy($src);
            imagedestroy($dst);
        }
    }
}
?>