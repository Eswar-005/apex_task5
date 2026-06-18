<?php
// admin/user_delete.php - Secure POST-only User Deletion

require_once '../includes/auth.php';

// Enforce admin privilege level
require_admin();

$current_user_id = $_SESSION['secure_user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash_message('error', 'Invalid request method: User deletion must be executed via POST.');
    header('Location: users.php');
    exit();
}

// Verify CSRF Token (Mitigates Cross-Site Request Forgery)
$csrf_token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf_token)) {
    set_flash_message('error', 'Security Violation: Invalid or expired security token. Deletion aborted.');
    header('Location: users.php');
    exit();
}

// Retrieve and validate ID
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id <= 0) {
    set_flash_message('error', 'Invalid user ID.');
    header('Location: users.php');
    exit();
}

// Demotion/Self-deletion Safeguard
if ($id === $current_user_id) {
    set_flash_message('error', 'Access Denied: You cannot delete your own admin account.');
    header('Location: users.php');
    exit();
}

try {
    $pdo = getDbConnection();
    
    // Execute parameterized delete statement
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);

    set_flash_message('success', 'User account ID ' . $id . ' deleted successfully.');
} catch (PDOException $e) {
    set_flash_message('error', 'System error: Could not delete user account.');
}

header('Location: users.php');
exit();
