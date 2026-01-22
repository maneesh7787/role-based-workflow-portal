<?php
/**
 * Application Configuration
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Application settings
define('APP_NAME', 'Role-Based Workflow Portal');
define('BASE_URL', 'http://localhost/role-based-workflow-portal');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Email configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-password');
define('FROM_EMAIL', 'noreply@workflowportal.com');
define('FROM_NAME', 'Workflow Portal');

// Include database configuration
require_once __DIR__ . '/database.php';

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Helper function to check user role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Helper function to check if user has one of multiple roles
function hasAnyRole($roles) {
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    return isset($_SESSION['role']) && in_array($_SESSION['role'], $roles);
}

// Helper function to redirect
function redirect($page) {
    header("Location: $page");
    exit();
}

// Helper function to sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Helper function to log activity
function logActivity($user_id, $action, $details = null, $request_id = null) {
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    $stmt = $db->prepare("INSERT INTO activity_logs (user_id, request_id, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $user_id, $request_id, $action, $details, $ip);
    $stmt->execute();
    $stmt->close();
}

// Helper function to create notification
function createNotification($user_id, $request_id, $type, $message) {
    $db = getDB();
    
    $stmt = $db->prepare("INSERT INTO notifications (user_id, request_id, type, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $user_id, $request_id, $type, $message);
    $stmt->execute();
    $stmt->close();
}

// Helper function to send email notification (basic implementation)
function sendEmailNotification($to, $subject, $message) {
    // Basic email sending - in production, use PHPMailer or similar
    $headers = "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
