<?php
session_start();

// Site settings
define('SITE_NAME', 'Bulk Buying Store');
define('SITE_URL', 'http://localhost:8000');

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bulk_buying_db');

// Directory settings
define('ROOT_PATH', dirname(__DIR__));
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

// Product settings
define('MIN_ORDER_QUANTITY', 10);
define('MAX_ORDER_PERCENTAGE', 50);
define('MIN_VOTE_DEPOSIT', 5); // In South African Rands
define('VOTES_REQUIRED_FOR_ADMIN', 100);
define('CANCELLATION_FEE_PERCENTAGE', 5);

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOADS_PATH)) {
    mkdir(UPLOADS_PATH, 0777, true);
}

// Error handling
function handleError($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    switch ($errno) {
        case E_USER_ERROR:
            echo "<b>Error:</b> [$errno] $errstr<br>";
            echo "Fatal error on line $errline in file $errfile";
            exit(1);
            break;
        case E_USER_WARNING:
            echo "<b>Warning:</b> [$errno] $errstr<br>";
            break;
        case E_USER_NOTICE:
            echo "<b>Notice:</b> [$errno] $errstr<br>";
            break;
        default:
            echo "Unknown error type: [$errno] $errstr<br>";
            break;
    }
    return true;
}
set_error_handler("handleError");

// Helper functions
function redirect($path) {
    header("Location: " . SITE_URL . $path);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function generateToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        throw new Exception('Invalid CSRF token');
    }
    return true;
}

function formatPrice($price) {
    return 'R ' . number_format($price, 2);
}

function calculateAdminFee($amount) {
    global $db;
    $stmt = $db->prepare("SELECT admin_fee_percentage FROM admin_settings LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $fee_percentage = $row['admin_fee_percentage'];
    return ($amount * $fee_percentage) / 100;
}

function calculateShippingFee($weight) {
    global $db;
    $stmt = $db->prepare("SELECT shipping_fee_per_kg FROM admin_settings LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $fee_per_kg = $row['shipping_fee_per_kg'];
    return $weight * $fee_per_kg;
}

// Initialize database connection
require_once 'database.php';
$database = new Database();
$db = $database->getConnection();
?>
