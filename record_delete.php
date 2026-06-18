<?php
// record_delete.php - Secure POST-only Record Deletion

require_once 'includes/auth.php';

// Enforce admin privileges (RBAC check)
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Prevent GET-based deletion requests
    set_flash_message('error', 'Invalid request method: Record deletion must be performed via POST.');
    header('Location: dashboard.php');
    exit();
}

// Verify CSRF Token (Mitigates Cross-Site Request Forgery)
$csrf_token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf_token)) {
    set_flash_message('error', 'Security Violation: Invalid or expired security token. Deletion aborted.');
    header('Location: dashboard.php');
    exit();
}

// Retrieve and validate ID
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id <= 0) {
    set_flash_message('error', 'Invalid record ID.');
    header('Location: dashboard.php');
    exit();
}

try {
    $pdo = getDbConnection();
    
    // Execute parameterized delete query
    $stmt = $pdo->prepare("DELETE FROM records WHERE id = ?");
    $stmt->execute([$id]);

    set_flash_message('success', 'Employee record ID ' . $id . ' deleted successfully.');
} catch (PDOException $e) {
    set_flash_message('error', 'System error: Could not delete record.');
}

header('Location: dashboard.php');
exit();
