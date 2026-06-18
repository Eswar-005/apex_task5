<?php
// dashboard.php - Employee Records Dashboard

require_once 'includes/auth.php';

// Enforce authentication
require_auth();

$user_role = get_user_role();
$username = $_SESSION['secure_username'] ?? 'User';
$email = $_SESSION['secure_email'] ?? '';
$is_admin = is_admin();

$success = get_flash_message('success');
$error = get_flash_message('error');

// Get search query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination settings
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$records = [];
$total_records = 0;
$total_pages = 0;

try {
    $pdo = getDbConnection();
    
    if ($search !== '') {
        // Search query: search by ID, title (Name), or description (Email/Department/Details)
        // Count total results for pagination
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM records WHERE id = :id OR title LIKE :search_title OR description LIKE :search_desc");
        $searchId = is_numeric($search) ? intval($search) : -1;
        $searchLike = '%' . $search . '%';
        
        $countStmt->bindValue(':id', $searchId, PDO::PARAM_INT);
        $countStmt->bindValue(':search_title', $searchLike, PDO::PARAM_STR);
        $countStmt->bindValue(':search_desc', $searchLike, PDO::PARAM_STR);
        $countStmt->execute();
        $total_records = $countStmt->fetchColumn();
        
        $total_pages = ceil($total_records / $limit);
        $page = min($page, max(1, $total_pages)); // bound page number
        $offset = ($page - 1) * $limit;

        // Fetch records
        $stmt = $pdo->prepare("SELECT * FROM records WHERE id = :id OR title LIKE :search_title OR description LIKE :search_desc ORDER BY id DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':id', $searchId, PDO::PARAM_INT);
        $stmt->bindValue(':search_title', $searchLike, PDO::PARAM_STR);
        $stmt->bindValue(':search_desc', $searchLike, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $records = $stmt->fetchAll();
    } else {
        // No search: fetch all records
        $total_records = $pdo->query("SELECT COUNT(*) FROM records")->fetchColumn();
        $total_pages = ceil($total_records / $limit);
        $page = min($page, max(1, $total_pages)); // bound page number
        $offset = ($page - 1) * $limit;

        $stmt = $pdo->prepare("SELECT * FROM records ORDER BY id DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $records = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $error = 'Database error: Could not load records.';
}

$avatar_initial = strtoupper(substr($username, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Directory - EmpPortal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="navbar">
        <div class="navbar-container">
            <a href="dashboard.php" class="brand">
                <span class="brand-icon">🛡️</span> EmpPortal <span class="role-badge role-<?php echo s($user_role); ?>" style="margin-left: 8px;"><?php echo s(strtoupper($user_role)); ?></span>
            </a>
            <ul class="nav-links">
                <li><a href="dashboard.php" class="nav-link-item active-nav" style="color: #fff; border-bottom: 2px solid #5b5ff0; padding-bottom: 4px;">Dashboard</a></li>
                <?php if ($is_admin): ?>
                    <li><a href="admin/users.php" class="nav-link-item">User Manager</a></li>
                <?php endif; ?>
                <li><a href="security_status.php" class="nav-link-item">Security Audit</a></li>
                <li class="nav-user">
                    <div class="user-avatar" title="<?php echo s($username); ?> (<?php echo s($user_role); ?>)">
                        <?php echo s($avatar_initial); ?>
                    </div>
                    <span class="user-name">Hello, <strong><?php echo s($username); ?></strong></span>
                </li>
                <li><a href="logout.php" class="btn btn-secondary btn-sm">Logout</a></li>
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
                <h1>Employee Records</h1>
                <p>View, search, and manage directory contacts securely.</p>
            </div>
            <div>
                <a href="record_add.php" class="btn btn-primary">+ Add Employee Record</a>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="search-container glass-panel">
            <form method="GET" action="dashboard.php" class="search-form" id="search-form">
                <div class="search-input-group">
                    <span class="search-icon">🔍</span>
                    <input type="text" name="search" id="search" class="form-control search-input" placeholder="Search by ID, Name, Email, Department..." value="<?php echo s($search); ?>">
                    <?php if ($search !== ''): ?>
                        <span class="clear-search-btn" onclick="window.location.href='dashboard.php'" title="Clear Search">&times;</span>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-secondary search-submit-btn">Search</button>
            </form>
        </div>

        <!-- Search Results Count -->
        <?php if ($search !== ''): ?>
            <div class="search-results-indicator">
                <p>Search results for: "<strong><?php echo s($search); ?></strong>"</p>
                <p>Found <strong><?php echo intval($total_records); ?></strong> match(es).</p>
            </div>
        <?php endif; ?>

        <!-- Employee Directory Cards -->
        <?php if (empty($records)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📂</div>
                <h3>No Employee Records Found</h3>
                <p>Try refining your search terms or add a new employee profile to seed the registry.</p>
                <?php if ($search !== ''): ?>
                    <a href="dashboard.php" class="btn btn-secondary btn-sm">Clear Search Filter</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="records-grid">
                <?php foreach ($records as $record): ?>
                    <div class="record-card">
                        <div>
                            <div class="record-meta">
                                <span>🆔 ID: <?php echo intval($record['id']); ?></span>
                                <span class="meta-dot">&bull;</span>
                                <span>📅 Hired: <?php echo s($record['created_at']); ?></span>
                            </div>
                            
                            <h2 class="record-title">
                                <?php echo highlight($record['title'], $search); ?>
                            </h2>
                            
                            <div class="record-desc">
                                <?php echo highlight($record['description'], $search); ?>
                            </div>
                        </div>

                        <div class="record-actions">
                            <a href="record_edit.php?id=<?php echo intval($record['id']); ?>" class="btn btn-secondary btn-sm">Edit</a>
                            
                            <?php if ($is_admin): ?>
                                <!-- CSRF Protected Delete Form -->
                                <form action="record_delete.php" method="POST" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this employee record?');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo intval($record['id']); ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination Controls -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <!-- Previous Button -->
                    <?php if ($page > 1): ?>
                        <a href="dashboard.php?page=<?php echo ($page - 1); ?><?php echo ($search !== '') ? '&search=' . urlencode($search) : ''; ?>" class="pagination-btn">&laquo; Previous</a>
                    <?php else: ?>
                        <span class="pagination-btn disabled">&laquo; Previous</span>
                    <?php endif; ?>

                    <!-- Page Numbers -->
                    <?php 
                    for ($i = 1; $i <= $total_pages; $i++): 
                        if ($i === $page):
                    ?>
                            <span class="pagination-btn active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="dashboard.php?page=<?php echo $i; ?><?php echo ($search !== '') ? '&search=' . urlencode($search) : ''; ?>" class="pagination-btn"><?php echo $i; ?></a>
                        <?php 
                        endif;
                    endfor; 
                    ?>

                    <!-- Next Button -->
                    <?php if ($page < $total_pages): ?>
                        <a href="dashboard.php?page=<?php echo ($page + 1); ?><?php echo ($search !== '') ? '&search=' . urlencode($search) : ''; ?>" class="pagination-btn">Next &raquo;</a>
                    <?php else: ?>
                        <span class="pagination-btn disabled">Next &raquo;</span>
                    <?php endif; ?>
                </div>

                <div class="pagination-info">
                    Showing Page <span><?php echo intval($page); ?></span> of <span><?php echo intval($total_pages); ?></span> pages (Total <span><?php echo intval($total_records); ?></span> records)
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> EmpPortal. Secure User Directory.</p>
        </div>
    </footer>
</body>
</html>
