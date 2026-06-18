<?php
// login.php - Secure Login Handler

require_once 'includes/auth.php';

// Redirect if already authenticated
if (is_authenticated()) {
    header('Location: dashboard.php');
    exit();
}

$error = get_flash_message('error');
$success = get_flash_message('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        set_flash_message('error', 'Security Violation: Invalid or expired security token. Please try again.');
        header('Location: login.php');
        exit();
    }

    // 2. Retrieve and trim input fields
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // 3. Simple input validation check
    if (empty($email) || empty($password)) {
        $error = 'Both email and password are required.';
    } else {
        try {
            $pdo = getDbConnection();
            
            // 4. Query user via parameterized statement
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // 5. Verify password and authenticate (Use generic error to prevent email enumeration)
            if ($user && password_verify($password, $user['password'])) {
                // Mitigate session fixation by creating a fresh session ID on privilege escalation
                session_regenerate_id(true);

                // Set session variables
                $_SESSION['secure_user_id'] = $user['id'];
                $_SESSION['secure_email'] = $user['email'];
                $_SESSION['secure_username'] = $user['name'];
                $_SESSION['secure_role'] = $user['role'];
                $_SESSION['last_activity'] = time();

                set_flash_message('success', 'Welcome back, ' . $user['name'] . '! Access granted.');
                header('Location: dashboard.php');
                exit();
            } else {
                // Generic error message
                $error = 'Invalid email address or password.';
            }
        } catch (PDOException $e) {
            $error = 'System error. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In - EmpPortal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <div class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="brand">
                <span class="brand-icon">🛡️</span> EmpPortal
            </a>
            <ul class="nav-links">
                <li><a href="index.php" class="nav-link-item">Home</a></li>
                <li><a href="register.php" class="btn btn-secondary btn-sm">Register</a></li>
            </ul>
        </div>
    </div>

    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Welcome Back</h2>
                <p>Provide credentials to access your workspace</p>
            </div>

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

            <form method="POST" action="login.php" novalidate id="login-form">
                <?php echo csrf_field(); ?>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="name@company.com" required value="<?php echo isset($_POST['email']) ? s($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Log In</button>
            </form>

            <div class="form-footer">
                Don't have an account yet? <a href="register.php">Register here</a>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> EmpPortal. Secure User Directory.</p>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('login-form');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');

        form.addEventListener('submit', function(e) {
            let valid = true;

            // Mark fields as touched for CSS styling feedback
            emailInput.classList.add('touched');
            passwordInput.classList.add('touched');

            if (emailInput.value.trim() === '' || !emailInput.checkValidity()) {
                valid = false;
            }

            if (passwordInput.value === '') {
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
            }
        });
        
        // Dynamic input check
        [emailInput, passwordInput].forEach(input => {
            input.addEventListener('input', function() {
                input.classList.remove('touched');
            });
        });
    });
    </script>
</body>
</html>
