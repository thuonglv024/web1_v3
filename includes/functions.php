<?php
// Include database connection and constants
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Check if user is logged in
function is_logged_in(): bool { 
    // Returns true if user_id is set in session and greater than 0
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0; 
}

// Check if user is admin
function is_admin(): bool { 
    // Returns true if role is set in session and equals 'admin'
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; 
}

// Ensure user is admin, redirect otherwise
function ensure_admin(): void {
    // Redirect to login page if user is not admin
    if (!is_admin()) { redirect('auth/login.php'); }
}

// Ensure user is logged in, redirect otherwise
function ensure_login(): void {
    // Redirect to login page if user is not logged in
    if (!is_logged_in()) { redirect('auth/login.php'); }
}

// Redirect to a path
function redirect(string $path): void {
    // Set Location header and exit
    header('Location: ' . BASE_URL . ltrim($path, '/'));
    exit;
}

// Check if request method is POST
function isPost(): bool { 
    // Returns true if request method is POST, defaults to GET if not set
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; 
}

// Escape HTML entities
function e(string $str): string { 
    // Returns escaped string using htmlspecialchars
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); 
}

// Sanitize input data
function sanitize(string $data): string { 
    // Returns sanitized string using htmlspecialchars and trim
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8'); 
}

// Display alert message
function alert(string $msg, string $type = 'success'): void { 
    // Echo alert message with escaped string
    echo "<div class='alert alert-{$type}'>" . e($msg) . "</div>"; 
}

// Upload image file
function uploadImage(array $file, string $targetDir, array $allowedExt = ['jpg','jpeg','png','gif'], int $maxMB = 5){
    // Check if file is uploaded
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return false;
    // Create target directory if not exists
    if (!is_dir($targetDir)) @mkdir($targetDir, 0777, true);
    // Get file extension
    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    // Check if file extension is allowed
    if (!in_array($ext, $allowedExt)) return false;
    // Check if file size exceeds maximum allowed size
    if (($file['size'] ?? 0) > $maxMB * 1024 * 1024) return false;
    // Generate new file name
    $new = time() . '_' . mt_rand(1000,9999) . '.' . $ext;
    // Set destination path
    $dest = rtrim($targetDir,'/\\') . DIRECTORY_SEPARATOR . $new;
    // Move uploaded file to destination
    if (move_uploaded_file($file['tmp_name'], $dest)) return $new;
    return false;
}
?>
