<?php
// config.php - Application Configuration with RFID Support

session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'components_db');

// Application settings
define('APP_NAME', 'MotorComp Pro');
define('APP_VERSION', '2.0');
define('ITEMS_PER_PAGE', 9);

// RFID Settings
define('RFID_ENABLED', true);
define('RFID_PREFIX', 'RFID-');

// Connect to database
function getDB() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
    }
    return $conn;
}

// Authentication functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUser() {
    return $_SESSION['user'] ?? null;
}

function requireAuth() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function redirect($url) {
    header("Location: $url");
    exit();
}

// Flash messages
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Format currency
function formatPrice($price) {
    return '₱' . number_format($price, 2);
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// RFID Functions
function validateRFID($rfid) {
    // Basic RFID validation - can be customized based on your RFID format
    return !empty($rfid) && strlen($rfid) >= 5;
}

function generateRFID() {
    // Generate a unique RFID tag
    return RFID_PREFIX . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function getComponentByRFID($rfid) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM components WHERE rfid_tag = ? OR rfid_epc = ?");
    $stmt->bind_param("ss", $rfid, $rfid);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function logRFIDScan($rfid_tag, $component_id, $scan_type, $user_id, $location = '', $notes = '') {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO rfid_scans (rfid_tag, component_id, scan_type, scanned_by, location, notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sisis", $rfid_tag, $component_id, $scan_type, $user_id, $location, $notes);
    return $stmt->execute();
}

function getRFIDScanHistory($rfid_tag = null, $limit = 50) {
    $db = getDB();
    if ($rfid_tag) {
        $stmt = $db->prepare("SELECT r.*, c.name as component_name, u.username as scanner_name 
                             FROM rfid_scans r 
                             LEFT JOIN components c ON r.component_id = c.id 
                             LEFT JOIN users u ON r.scanned_by = u.id 
                             WHERE r.rfid_tag = ? 
                             ORDER BY r.created_at DESC LIMIT ?");
        $stmt->bind_param("si", $rfid_tag, $limit);
    } else {
        $stmt = $db->prepare("SELECT r.*, c.name as component_name, u.username as scanner_name 
                             FROM rfid_scans r 
                             LEFT JOIN components c ON r.component_id = c.id 
                             LEFT JOIN users u ON r.scanned_by = u.id 
                             ORDER BY r.created_at DESC LIMIT ?");
        $stmt->bind_param("i", $limit);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>