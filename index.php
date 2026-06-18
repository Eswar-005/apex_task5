<?php
// index.php - Premium Landing Page
require_once 'includes/auth.php';

// If already authenticated, redirect to dashboard
if (is_authenticated()) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Optimization -->
    <title>Secure Employee Management Portal - ApexPlanet Cybersecurity</title>
    <meta name="description" content="A hardened full-stack PHP web application for secure employee records management. Featuring prepared SQL queries, CSRF validation, XSS sanitization, and Role-Based Access Control.">
    
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="brand" id="brand-logo">
                <span class="brand-icon">🛡️</span> EmpPortal <span style="font-size: 0.8rem; background: #5b5ff0; color: #fff; padding: 2px 8px; border-radius: 6px; margin-left: 8px; font-weight: 700; letter-spacing: 0.05em;">SECURE</span>
            </a>
            <ul class="nav-links">
                <li><a href="login.php" class="nav-link-item" id="login-nav-btn">Log In</a></li>
                <li><a href="register.php" class="btn btn-primary btn-sm" id="register-nav-btn">Get Started</a></li>
            </ul>
        </div>
    </div>

    <div class="container" style="flex-grow: 1; display: flex; align-items: center; justify-content: center;">
        <div class="glass-panel" style="max-width: 800px; text-align: center; padding: 4rem 3rem; width: 100%;">
            <div style="font-size: 4rem; margin-bottom: 1.5rem;">🔐</div>
            <h1 style="font-size: 3rem; margin-bottom: 1rem; background: linear-gradient(135deg, #ffffff 0%, #a5b4fc 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                Secure Employee Management Portal
            </h1>
            <p style="color: var(--text-secondary); font-size: 1.15rem; margin-bottom: 2.5rem; max-width: 600px; margin-left: auto; margin-right: auto;">
                A military-grade web application built to protect corporate directory records. Engineered with parameterized database layers, strict access gates, CSRF token tracking, and security headers.
            </p>
            
            <div style="display: flex; gap: 1rem; justify-content: center; margin-bottom: 3rem; flex-wrap: wrap;">
                <a href="login.php" class="btn btn-primary" style="padding: 0.8rem 2rem; font-size: 1rem;">Log In to Portal</a>
                <a href="register.php" class="btn btn-secondary" style="padding: 0.8rem 2rem; font-size: 1rem;">Register Account</a>
            </div>

            <div style="border-top: 1px solid rgba(255, 255, 255, 0.08); padding-top: 2rem;">
                <h3 style="margin-bottom: 1rem; font-size: 1.1rem; color: var(--text-primary);">Test Credentials (Auto-Seeded)</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; text-align: left;">
                    <div style="background: rgba(10, 10, 20, 0.4); padding: 1rem; border-radius: 10px; border: 1px solid rgba(255,255,255,0.03);">
                        <p style="font-weight: 700; color: #fb7185; margin-bottom: 0.25rem;">Administrator Account</p>
                        <p style="font-size: 0.85rem; color: var(--text-secondary);">Email: <strong style="color: #fff;">admin@portal.com</strong></p>
                        <p style="font-size: 0.85rem; color: var(--text-secondary);">Password: <strong style="color: #fff;">AdminSecure123!</strong></p>
                        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">Permission: Full CRUD, Delete, User Management</p>
                    </div>
                    <div style="background: rgba(10, 10, 20, 0.4); padding: 1rem; border-radius: 10px; border: 1px solid rgba(255,255,255,0.03);">
                        <p style="font-weight: 700; color: #60a5fa; margin-bottom: 0.25rem;">HR Staff Account</p>
                        <p style="font-size: 0.85rem; color: var(--text-secondary);">Email: <strong style="color: #fff;">hr@portal.com</strong></p>
                        <p style="font-size: 0.85rem; color: var(--text-secondary);">Password: <strong style="color: #fff;">HRPassword123!</strong></p>
                        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">Permission: Create, Read, Update. No deletion/admin page.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> EmpPortal. Developed under ApexPlanet Cybersecurity Internship Program. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html>
