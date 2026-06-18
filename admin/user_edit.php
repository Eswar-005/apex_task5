<?php
// admin/user_edit.php - Edit User Account and Role

require_once '../includes/auth.php';

// Enforce admin privilege level
require_admin();

$current_user_id = $_SESSION['secure_user_id'];
$username = $_SESSION['secure_username'] ?? 'Admin';
$error = get_flash_message('error');
$success = get_flash_message('success');

// 1. Retrieve and validate ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    set_flash_message('error', 'Invalid user ID.');
    header('Location: users.php');
    exit();
}

$user = null;
try {
    $pdo = getDbConnection();
    
    // Fetch user details using parameterized select statement
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $error = 'Database error: Could not fetch user details.';
}

if (!$user) {
    set_flash_message('error', 'User account not found.');
    header('Location: users.php');
    exit();
}

// 2. Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF Token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        set_flash_message('error', 'Security Violation: Invalid or expired security token.');
        header("Location: user_edit.php?id=$id");
        exit();
    }

    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $role = isset($_POST['role']) ? trim($_POST['role']) : '';

    // Validation checks
    if (empty($name) || empty($email) || empty($role)) {
        $error = 'All fields are required.';
    } elseif (!validate_name($name)) {
        $error = 'Name contains invalid characters.';
    } elseif (!validate_email($email)) {
        $error = 'Invalid email address format.';
    } elseif ($role !== 'admin' && $role !== 'user') {
        $error = 'Invalid role selected.';
    } elseif ($id === $current_user_id && $role !== 'admin') {
        // Demotion safeguard
        $error = 'Access Denied: You cannot demote your own administrator account to protect system integrity.';
    } else {
        try {
            // Check for email conflicts
            $conflictStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $conflictStmt->execute([$email, $id]);
            if ($conflictStmt->fetch()) {
                $error = 'An account with that email address already exists.';
            } else {
                // Execute parameterized update query
                $updateStmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
                $updateStmt->execute([$name, $email, $role, $id]);

                // Update session name if the user edited their own profile
                if ($id === $current_user_id) {
                    $_SESSION['secure_username'] = $name;
                    $_SESSION['secure_email'] = $email;
                }

                set_flash_message('success', 'User account updated successfully.');
                header('Location: users.php');
                exit();
            }
        } catch (PDOException $e) {
            $error = 'System error: Could not update user details.';
        }
    }
}

$avatar_initial = strtoupper(substr($username, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - EmpPortal</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="navbar">
        <div class="navbar-container">
            <a href="../dashboard.php" class="brand">
                <span class="brand-icon">🛡️</span> EmpPortal <span class="role-badge role-admin" style="margin-left: 8px;">ADMIN</span>
            </a>
            <ul class="nav-links">
                <li><a href="../dashboard.php" class="nav-link-item">Dashboard</a></li>
                <li><a href="users.php" class="nav-link-item">User Manager</a></li>
                <li><a href="../security_status.php" class="nav-link-item">Security Audit</a></li>
                <li class="nav-user">
                    <div class="user-avatar" title="<?php echo s($username); ?> (ADMIN)">
                        <?php echo s($avatar_initial); ?>
                    </div>
                </li>
                <li><a href="../logout.php" class="btn btn-secondary btn-sm">Logout</a></li>
            </ul>
        </div>
    </div>

    <div class="container" style="max-width: 600px;">
        <div class="page-header">
            <a href="users.php" class="back-link">&larr; Back to Users List</a>
        </div>

        <div class="glass-panel">
            <h1 class="page-title" style="font-size: 2rem; margin-bottom: 1.5rem;">Edit User Account</h1>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <span>⚠️</span> <?php echo s($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="user_edit.php?id=<?php echo intval($id); ?>" novalidate id="edit-user-form">
                <?php echo csrf_field(); ?>

                <div class="form-group">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="John Doe" required value="<?php echo isset($_POST['name']) ? s($_POST['name']) : s($user['name']); ?>">
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="john@portal.com" required value="<?php echo isset($_POST['email']) ? s($_POST['email']) : s($user['email']); ?>">
                </div>

                <div class="form-group">
                    <label for="role" class="form-label">System Role Access</label>
                    <select id="role" name="role" class="form-control" required <?php echo ($id === $current_user_id) ? 'disabled' : ''; ?>>
                        <option value="user" <?php echo ($user['role'] === 'user') ? 'selected' : ''; ?>>Standard User (HR Staff)</option>
                        <option value="admin" <?php echo ($user['role'] === 'admin') ? 'selected' : ''; ?>>Administrator (HR Director)</option>
                    </select>
                    <?php if ($id === $current_user_id): ?>
                        <input type="hidden" name="role" value="admin">
                        <small style="color: var(--text-muted); display: block; margin-top: 6px;">
                            💡 Demoting or changing your own role is disabled to prevent accidental lockout.
                        </small>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem;">Update Account Details</button>
            </form>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> EmpPortal. Secure User Directory.</p>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('edit-user-form');
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');

        form.addEventListener('submit', function(e) {
            let valid = true;

            nameInput.classList.add('touched');
            emailInput.classList.add('touched');

            if (nameInput.value.trim() === '' || !/^[a-zA-Z\s\.\-']{2,100}$/.test(nameInput.value)) {
                valid = false;
            }

            if (emailInput.value.trim() === '' || !emailInput.checkValidity()) {
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>
