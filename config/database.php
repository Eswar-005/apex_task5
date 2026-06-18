<?php
// config/database.php - Secure Database Connection & Global Security Policies

// 1. Hardened Session Configuration (Must be set BEFORE session_start)
ini_set('session.cookie_httponly', 1); // Prevent access to session cookie via JavaScript (Mitigates Session Hijacking via XSS)
ini_set('session.use_only_cookies', 1); // Prevent session hijacking via URL session IDs
ini_set('session.cookie_samesite', 'Lax'); // Protect against CSRF attacks on cross-site requests

// Set cookie secure flag if HTTPS is enabled
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Database Connection Credentials
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');
define('DB_NAME', 'employee_management');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    
    // Create PDO instance with secure safeguards
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Fetch associative arrays
        PDO::ATTR_EMULATE_PREPARES => false, // Disable prepare emulation (forces real parameterized prepared statements in MySQL, neutralizing SQL Injection)
    ]);
} catch (PDOException $e) {
    // Graceful error display without exposing database stack traces or credentials
    die("<div style='font-family: Arial, sans-serif; padding: 20px; text-align: center; background: #fff5f5; color: #c53030; border: 1px solid #feb2b2; margin: 50px auto; max-width: 500px; border-radius: 8px;'>
            <h2 style='margin-top: 0;'>System Maintenance</h2>
            <p>We are currently experiencing database connection issues. Please try again later.</p>
         </div>");
}

/**
 * Returns the global PDO connection instance
 */
function getDbConnection() {
    global $pdo;
    return $pdo;
}

// 3. Security Headers Injection
function send_security_headers() {
    if (headers_sent()) {
        return;
    }
    
    // Prevent Clickjacking (disallow loading application in iframe)
    header("X-Frame-Options: DENY");
    
    // Mitigate MIME-sniffing vulnerability
    header("X-Content-Type-Options: nosniff");
    
    // Enable browser XSS protection mode block (for legacy browsers)
    header("X-XSS-Protection: 1; mode=block");
    
    // Direct browser to only pass referrer information for same-origin requests
    header("Referrer-Policy: strict-origin-when-cross-origin");
    
    // Force transport over TLS/HTTPS (HSTS header)
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
    
    // Content Security Policy: strictly restrict resource load paths
    header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; frame-ancestors 'none'; object-src 'none';");
}

// 4. CSRF Protection Helpers
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token); // Time-attack resilient string comparison
}

function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

// 5. Flash Notifications Helpers
function set_flash_message($type, $message) {
    $_SESSION['flash_messages'][$type] = $message;
}

function get_flash_message($type) {
    if (!isset($_SESSION['flash_messages'][$type])) {
        return '';
    }
    $message = $_SESSION['flash_messages'][$type];
    unset($_SESSION['flash_messages'][$type]);
    return $message;
}
