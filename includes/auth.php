<?php
// includes/auth.php - Session Validation and Access Control (RBAC)

require_once __DIR__ . '/../config/database.php';

// Send security headers on every page load that includes auth.php
send_security_headers();

// Enforce Session Activity Timeout (15 minutes = 900 seconds)
$timeout_duration = 900; 

if (isset($_SESSION['secure_user_id'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        // Session has expired
        session_unset();
        session_destroy();
        
        // Start a new session to pass the timeout flash message
        session_start();
        set_flash_message('error', 'Your session has expired due to inactivity. Please log in again.');
        header('Location: login.php');
        exit();
    }
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

/**
 * Checks if the user is authenticated
 */
function is_authenticated() {
    return isset($_SESSION['secure_user_id']) && !empty($_SESSION['secure_user_id']);
}

/**
 * Gets the current user's role (defaults to 'user' if not authenticated)
 */
function get_user_role() {
    return $_SESSION['secure_role'] ?? 'guest';
}

/**
 * Checks if the current user is an Admin
 */
function is_admin() {
    return get_user_role() === 'admin';
}

/**
 * Require the user to be authenticated, redirects to login if not
 */
function require_auth() {
    if (!is_authenticated()) {
        set_flash_message('error', 'You must log in to access this page.');
        header('Location: login.php');
        exit();
    }
}

/**
 * Require the user to have a specific role, blocks access if not
 */
function require_role($required_role) {
    require_auth();
    
    $current_role = get_user_role();
    if ($current_role !== $required_role) {
        // If they are not authorized, redirect to dashboard with access denied
        set_flash_message('error', 'Unauthorized: You do not have the required permissions to access that page.');
        header('Location: dashboard.php');
        exit();
    }
}

/**
 * Enforces admin-only access
 */
function require_admin() {
    require_role('admin');
}

/**
 * XSS Sanitization helper for output HTML rendering
 */
function s($str) {
    if ($str === null) {
        return '';
    }
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Safely highlights matching search terms in HTML escaped text.
 * Splitting happens on raw text before escaping, avoiding corruption of entities.
 */
function highlight($text, $search) {
    if ($search === null || $search === '') {
        return s($text);
    }
    
    // Split text by search query (case-insensitive) using capture group
    $parts = preg_split('/(' . preg_quote($search, '/') . ')/i', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    $highlighted = '';
    foreach ($parts as $index => $part) {
        if ($index % 2 === 1) {
            // Matched part: escape and wrap in mark tag
            $highlighted .= '<mark class="search-highlight">' . s($part) . '</mark>';
        } else {
            // Unmatched part: escape normally
            $highlighted .= s($part);
        }
    }
    return $highlighted;
}


/**
 * Strict Input Validation Helpers
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validate_name($name) {
    // Only letters, spaces, and hyphens/dots allowed, length 2-100
    return preg_match("/^[a-zA-Z\s\.\-']{2,100}$/", $name);
}
