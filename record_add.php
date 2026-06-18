<?php
// record_add.php - Add Employee Record

require_once 'includes/auth.php';

// Require authenticated user
require_auth();

$user_role = get_user_role();
$username = $_SESSION['secure_username'] ?? 'User';
$is_admin = is_admin();

$error = get_flash_message('error');
$success = get_flash_message('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        set_flash_message('error', 'Security Violation: Invalid or expired security token.');
        header('Location: record_add.php');
        exit();
    }

    // 2. Fetch inputs and trim
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $department = isset($_POST['department']) ? trim($_POST['department']) : '';
    $job_title = isset($_POST['job_title']) ? trim($_POST['job_title']) : '';
    $created_at = isset($_POST['created_at']) ? trim($_POST['created_at']) : '';

    // 3. Validation Checks
    if (empty($name) || empty($email) || empty($department) || empty($job_title) || empty($created_at)) {
        $error = 'All fields are required.';
    } elseif (!validate_name($name)) {
        $error = 'Name contains invalid characters. Use letters, spaces, dots, or hyphens only.';
    } elseif (!validate_email($email)) {
        $error = 'Invalid email address format.';
    } elseif (!preg_match("/^[a-zA-Z\s\-&]{2,100}$/", $department)) {
        $error = 'Department contains invalid characters.';
    } elseif (!preg_match("/^[a-zA-Z\s\-&']{2,100}$/", $job_title)) {
        $error = 'Job title contains invalid characters.';
    } elseif (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $created_at)) {
        $error = 'Hire date must be in YYYY-MM-DD format.';
    } else {
        // Construct the description block conforming to schema constraints
        $description = "Department: $department | Email: $email | Title: $job_title";

        try {
            $pdo = getDbConnection();
            
            // Insert parameterized record
            $stmt = $pdo->prepare("INSERT INTO records (title, description, created_at) VALUES (?, ?, ?)");
            $stmt->execute([$name, $description, $created_at]);

            set_flash_message('success', 'Employee profile added successfully.');
            header('Location: dashboard.php');
            exit();
        } catch (PDOException $e) {
            $error = 'System error: Could not insert record.';
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
    <title>Add Employee - EmpPortal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="navbar">
        <div class="navbar-container">
            <a href="dashboard.php" class="brand">
                <span class="brand-icon">🛡️</span> EmpPortal
            </a>
            <ul class="nav-links">
                <li><a href="dashboard.php" class="nav-link-item">Dashboard</a></li>
                <?php if ($is_admin): ?>
                    <li><a href="admin/users.php" class="nav-link-item">User Manager</a></li>
                <?php endif; ?>
                <li><a href="security_status.php" class="nav-link-item">Security Audit</a></li>
                <li class="nav-user">
                    <div class="user-avatar" title="<?php echo s($username); ?>">
                        <?php echo s($avatar_initial); ?>
                    </div>
                </li>
                <li><a href="logout.php" class="btn btn-secondary btn-sm">Logout</a></li>
            </ul>
        </div>
    </div>

    <div class="container" style="max-width: 650px;">
        <div class="page-header">
            <a href="dashboard.php" class="back-link">&larr; Back to Dashboard</a>
        </div>

        <div class="glass-panel">
            <h1 class="page-title" style="font-size: 2rem; margin-bottom: 1.5rem;">Add Employee Record</h1>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <span>⚠️</span> <?php echo s($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="record_add.php" novalidate id="add-form">
                <?php echo csrf_field(); ?>

                <div class="form-group">
                    <label for="name" class="form-label">Employee Full Name</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Alice Cooper" required value="<?php echo isset($_POST['name']) ? s($_POST['name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Employee Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="alice.cooper@company.com" required value="<?php echo isset($_POST['email']) ? s($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="department" class="form-label">Department</label>
                    <input type="text" id="department" name="department" class="form-control" placeholder="e.g. Cybersecurity" required value="<?php echo isset($_POST['department']) ? s($_POST['department']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="job_title" class="form-label">Job Title (Position)</label>
                    <input type="text" id="job_title" name="job_title" class="form-control" placeholder="e.g. Security Architect" required value="<?php echo isset($_POST['job_title']) ? s($_POST['job_title']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="created_at" class="form-label">Hire Date</label>
                    <input type="date" id="created_at" name="created_at" class="form-control" required value="<?php echo isset($_POST['created_at']) ? s($_POST['created_at']) : date('Y-m-d'); ?>">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Save Record</button>
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
        const form = document.getElementById('add-form');
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const departmentInput = document.getElementById('department');
        const jobTitleInput = document.getElementById('job_title');
        const dateInput = document.getElementById('created_at');

        form.addEventListener('submit', function(e) {
            let valid = true;

            // Touch inputs
            nameInput.classList.add('touched');
            emailInput.classList.add('touched');
            departmentInput.classList.add('touched');
            jobTitleInput.classList.add('touched');
            dateInput.classList.add('touched');

            if (nameInput.value.trim() === '' || !/^[a-zA-Z\s\.\-']{2,100}$/.test(nameInput.value)) {
                valid = false;
            }

            if (emailInput.value.trim() === '' || !emailInput.checkValidity()) {
                valid = false;
            }

            if (departmentInput.value.trim() === '' || !/^[a-zA-Z\s\-&]{2,100}$/.test(departmentInput.value)) {
                valid = false;
            }

            if (jobTitleInput.value.trim() === '' || !/^[a-zA-Z\s\-&']{2,100}$/.test(jobTitleInput.value)) {
                valid = false;
            }

            if (dateInput.value.trim() === '' || !/^\d{4}-\d{2}-\d{2}$/.test(dateInput.value)) {
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
