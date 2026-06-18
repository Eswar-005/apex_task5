<?php
// admin/users.php - User Account Management Panel

require_once '../includes/auth.php';

// Enforce admin privilege level
require_admin();

$current_user_id = $_SESSION['secure_user_id'];
$username = $_SESSION['secure_username'] ?? 'Admin';
$success = get_flash_message('success');
$error = get_flash_message('error');

// Get search query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $pdo = getDbConnection();
    
    if ($search !== '') {
        // Query users using parameterized statements
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id OR name LIKE :search_name OR email LIKE :search_email OR role = :role ORDER BY id DESC");
        $searchId = is_numeric($search) ? intval($search) : -1;
        $searchLike = '%' . $search . '%';
        
        $stmt->bindValue(':id', $searchId, PDO::PARAM_INT);
        $stmt->bindValue(':search_name', $searchLike, PDO::PARAM_STR);
        $stmt->bindValue(':search_email', $searchLike, PDO::PARAM_STR);
        $stmt->bindValue(':role', $search, PDO::PARAM_STR);
        $stmt->execute();
        $users = $stmt->fetchAll();
    } else {
        $users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
    }
} catch (PDOException $e) {
    $error = 'Database error: Could not fetch user accounts.';
}

$avatar_initial = strtoupper(substr($username, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Manager - EmpPortal</title>
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
                <li><a href="users.php" class="nav-link-item active-nav" style="color: #fff; border-bottom: 2px solid #5b5ff0; padding-bottom: 4px;">User Manager</a></li>
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

    <div class="container">
        <!-- Success/Error Alerts -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <span>✅</span> <?php echo s($success); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <span>⚠️</span> <?php echo s($error); ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-header">
            <div class="dashboard-title">
                <h1>User Accounts</h1>
                <p>Manage system roles, permissions, and database operators.</p>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="search-container glass-panel">
            <form method="GET" action="users.php" class="search-form">
                <div class="search-input-group">
                    <span class="search-icon">🔍</span>
                    <input type="text" name="search" id="search" class="form-control search-input" placeholder="Search by ID, Name, Email, or Role (admin/user)..." value="<?php echo s($search); ?>">
                    <?php if ($search !== ''): ?>
                        <span class="clear-search-btn" onclick="window.location.href='users.php'" title="Clear Search">&times;</span>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-secondary search-submit-btn">Search Users</button>
            </form>
        </div>

        <!-- User Accounts Table -->
        <div class="table-container glass-panel" style="padding: 0;">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email Address</th>
                        <th>Role Badge</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                                No user accounts match your search filter.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo intval($user['id']); ?></td>
                                <td>
                                    <strong>
                                        <?php echo highlight($user['name'], $search); ?>
                                    </strong>
                                </td>
                                <td>
                                    <?php echo highlight($user['email'], $search); ?>
                                    <?php if ($user['id'] === $current_user_id): ?>
                                        <span style="font-size: 0.75rem; background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 2px 6px; border-radius: 4px; margin-left: 6px;">You</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="role-badge role-<?php echo s($user['role']); ?>">
                                        <?php echo s($user['role']); ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 0.5rem; justify-content: flex-end;">
                                        <a href="user_edit.php?id=<?php echo intval($user['id']); ?>" class="btn btn-secondary btn-sm">Edit Role</a>
                                        
                                        <?php if ($user['id'] !== $current_user_id): ?>
                                            <!-- CSRF-protected deletion form for other users -->
                                            <form action="user_delete.php" method="POST" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this user account? All access will be revoked immediately.');">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="id" value="<?php echo intval($user['id']); ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-danger btn-sm" disabled style="opacity: 0.3; cursor: not-allowed;" title="You cannot delete your own admin account.">Delete</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> EmpPortal. Secure User Directory.</p>
        </div>
    </footer>
</body>
</html>
