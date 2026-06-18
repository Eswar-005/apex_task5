<?php
// register.php - Secure User Registration

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
        set_flash_message('error', 'Security Violation: Invalid or expired security token.');
        header('Location: register.php');
        exit();
    }

    // 2. Retrieve inputs and clean
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // 3. Server-side Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (!validate_name($name)) {
        $error = 'Name must be between 2 and 100 characters and contain only letters, spaces, hyphens, or periods.';
    } elseif (!validate_email($email)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif (!preg_match("/[A-Z]/", $password) || !preg_match("/[a-z]/", $password) || !preg_match("/[0-9]/", $password) || !preg_match("/[^A-Za-z0-9]/", $password)) {
        $error = 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $pdo = getDbConnection();
            
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'An account with that email address already exists.';
            } else {
                // Hash the password securely using BCrypt
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                
                // Insert new user with default role 'user' (never trust role from inputs)
                $insertStmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
                $insertStmt->execute([$name, $email, $hashedPassword]);
                
                set_flash_message('success', 'Registration successful! You can now log in.');
                header('Location: login.php');
                exit();
            }
        } catch (PDOException $e) {
            $error = 'System error. Registration failed. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - EmpPortal</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .requirement-item {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .requirement-item.valid {
            color: #10b981;
        }
    </style>
</head>
<body class="auth-page">
    <div class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="brand">
                <span class="brand-icon">🛡️</span> EmpPortal
            </a>
            <ul class="nav-links">
                <li><a href="index.php" class="nav-link-item">Home</a></li>
                <li><a href="login.php" class="btn btn-secondary btn-sm">Log In</a></li>
            </ul>
        </div>
    </div>

    <div class="auth-wrapper" style="min-height: calc(100vh - 120px);">
        <div class="auth-card" style="margin-top: 1.5rem; margin-bottom: 1.5rem;">
            <div class="auth-header">
                <h2>Create Account</h2>
                <p>Register a standard user account to view directories</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <span>⚠️</span> <?php echo s($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php" novalidate id="register-form">
                <?php echo csrf_field(); ?>

                <div class="form-group">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="John Doe" required value="<?php echo isset($_POST['name']) ? s($_POST['name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="name@company.com" required value="<?php echo isset($_POST['email']) ? s($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                    
                    <!-- Real-time Password Checklist -->
                    <div style="margin-top: 0.75rem;" class="password-requirements">
                        <div class="requirement-item" id="req-length">⚪ Minimum 8 characters</div>
                        <div class="requirement-item" id="req-upper">⚪ At least one uppercase letter</div>
                        <div class="requirement-item" id="req-lower">⚪ At least one lowercase letter</div>
                        <div class="requirement-item" id="req-num">⚪ At least one number</div>
                        <div class="requirement-item" id="req-special">⚪ At least one special character</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Register Account</button>
            </form>

            <div class="form-footer">
                Already have an account? <a href="login.php">Log in here</a>
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
        const form = document.getElementById('register-form');
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');

        // Requirement nodes
        const reqLength = document.getElementById('req-length');
        const reqUpper = document.getElementById('req-upper');
        const reqLower = document.getElementById('req-lower');
        const reqNum = document.getElementById('req-num');
        const reqSpecial = document.getElementById('req-special');

        function validatePassword(pwd) {
            const hasLength = pwd.length >= 8;
            const hasUpper = /[A-Z]/.test(pwd);
            const hasLower = /[a-z]/.test(pwd);
            const hasNum = /[0-9]/.test(pwd);
            const hasSpecial = /[^A-Za-z0-9]/.test(pwd);

            // Update UI indicator helper
            updateIndicator(reqLength, hasLength);
            updateIndicator(reqUpper, hasUpper);
            updateIndicator(reqLower, hasLower);
            updateIndicator(reqNum, hasNum);
            updateIndicator(reqSpecial, hasSpecial);

            return hasLength && hasUpper && hasLower && hasNum && hasSpecial;
        }

        function updateIndicator(element, isValid) {
            if (isValid) {
                element.classList.add('valid');
                element.innerHTML = '✔ ' + element.innerHTML.slice(2);
            } else {
                element.classList.remove('valid');
                element.innerHTML = '⚪ ' + element.innerHTML.slice(2);
            }
        }

        passwordInput.addEventListener('input', function() {
            validatePassword(passwordInput.value);
        });

        form.addEventListener('submit', function(e) {
            let valid = true;

            // Touch fields
            nameInput.classList.add('touched');
            emailInput.classList.add('touched');
            passwordInput.classList.add('touched');
            confirmPasswordInput.classList.add('touched');

            if (nameInput.value.trim() === '' || !/^[a-zA-Z\s\.\-']{2,100}$/.test(nameInput.value)) {
                valid = false;
            }

            if (emailInput.value.trim() === '' || !emailInput.checkValidity()) {
                valid = false;
            }

            if (!validatePassword(passwordInput.value)) {
                valid = false;
            }

            if (passwordInput.value !== confirmPasswordInput.value || confirmPasswordInput.value === '') {
                valid = false;
                confirmPasswordInput.style.borderColor = '#f43f5e';
            } else {
                confirmPasswordInput.style.borderColor = '';
            }

            if (!valid) {
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>
