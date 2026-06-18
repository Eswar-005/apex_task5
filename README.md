# Secure Employee Management Portal

A full-stack, secure employee directory management web application developed as the capstone project for the ApexPlanet Cybersecurity Internship. 

This portal is engineered using **PHP**, **MariaDB/MySQL**, **HTML5**, **CSS3**, and **Vanilla Javascript** to model a hardened enterprise system. It incorporates robust authentication, Role-Based Access Control (RBAC), multi-field paginated searches, and comprehensive defense mechanisms against common web application vulnerabilities.

---

## ⚡ Key Features

1. **User Authentication Module**:
   - Secure account registration (validates names, emails, and enforces strong password complexity criteria).
   - Password encryption utilizing **BCrypt** hashing via PHP's `password_hash()`.
   - Access session isolation protecting against session fixation by regenerating session IDs upon privilege escalation (`session_regenerate_id(true)`).
   - Session activity auto-timeout logging users out after 15 minutes of inactivity.
   - Privilege Escalation mitigation by defaulting all self-registered accounts to the `'user'` role.

2. **Role-Based Access Control (RBAC) & CRUD**:
   - **Guest Users**: Restricted access; can only access registration and login portals.
   - **Standard Users (HR Staff)**: Authenticated. Can view directory records, search, paginate, add new records, and edit details. Restricted from deleting records and accessing user management.
   - **Administrators (HR Directors)**: Authenticated. Complete administrative authority. Permitted to add, edit, and delete employee records, access the User Manager interface to modify user roles, and delete operator accounts (with safeguards blocking self-demotion or self-deletion).

3. **Paginated Multi-Field Search**:
   - Integrates pagination displaying **10 records per page** to improve loading performance.
   - Advanced search bar enabling matches across **ID**, **Full Name**, **Email Address**, and **Department**.
   - Dynamic search keyword highlighting (`<mark class="search-highlight">`) in the rendered output cards.

4. **Web Security Hardening**:
   - **SQL Injection Prevention**: Forced database query parameterization via PDO prepared statements with SQL command compilation. Emulated prepared statements are explicitly disabled (`PDO::ATTR_EMULATE_PREPARES => false`).
   - **XSS Mitigation**: Strict sanitization of all dynamic rendering variables using custom HTML entity escaping helpers (`htmlspecialchars` with quotes option).
   - **CSRF Defense**: Generation and validation of cryptographic state-changing tokens in all record and user updates/deletions.
   - **HTTP Security Headers**: Injects key headers to restrict cross-site actions and frame rendering:
     - `Content-Security-Policy` (CSP)
     - `X-Frame-Options: DENY`
     - `X-Content-Type-Options: nosniff`
     - `Strict-Transport-Security` (HSTS)
     - `Referrer-Policy`
     - `X-XSS-Protection: 1; mode=block`

5. **Diagnostic Center**:
   - Custom `security_status.php` page visualizing active security headers, cookie flags, database emulation properties, and execution verification.

---

## 📂 Project Structure

```text
Apexplanet_task5/
├── config/
│   └── database.php       # Hardened database connections, CSRF, security headers
├── includes/
│   └── auth.php           # RBAC gates, registration checks, inactivity timeout
├── admin/
│   ├── users.php          # User accounts dashboard (Admin-only)
│   ├── user_edit.php      # User role modification form (Admin-only)
│   └── user_delete.php    # POST-only user deletion handler (Admin-only)
├── tests/
│   ├── test_xss.php       # Automated HTML escaping sanity check
│   └── test_sqli.php      # Automated prepared query parameterization check
├── index.php              # Landing page
├── login.php              # Secure login controller
├── register.php           # Secure account signup page
├── logout.php             # Session destroyer
├── dashboard.php          # Paginated search directory interface
├── record_add.php         # New employee record form
├── record_edit.php        # Record updates form
├── record_delete.php      # POST-only record deletion handler (Admin-only)
├── style.css              # Premium dark-theme glassmorphism stylesheet
├── setup.sql              # Database structure script
└── setup_db.php           # Automatic schema runner and data seeder
```

---

## 🛠️ Installation & Running Guide

### Prerequisites
- PHP 8.x
- MySQL / MariaDB (running on port `3307` or adjustable in `/config/database.php`)

### 1. Initialize & Seed Database
Ensure your MySQL server is active. To automatically execute the table schemas, create databases, and seed test accounts and records, run:
```powershell
C:\xampp\php\php.exe setup_db.php
```

### 2. Default Test Accounts (Auto-Seeded)
You can log in immediately using these credentials:
- **Administrator (Full Privileges)**:
  - **Email**: `admin@portal.com`
  - **Password**: `AdminSecure123!`
- **HR Staff (Standard User)**:
  - **Email**: `hr@portal.com`
  - **Password**: `HRPassword123!`

### 3. Start Development Server
From the project workspace root, start the PHP local built-in server:
```powershell
C:\xampp\php\php.exe -S localhost:8000
```
Open your browser and navigate to: [http://localhost:8000](http://localhost:8000)

---

## 🧪 Security Verification Proof Logs

You can verify the input sanitization and parameter compilation layers by executing the tests in your terminal:

### 1. SQL Injection Verification Test
Run:
```powershell
C:\xampp\php\php.exe tests/test_sqli.php
```
**Output Log**:
```text
=== Automated SQL Injection Defense Verification ===
[+] Successfully connected to the database.

[1] Vulnerable Query Emulation (Conceptual):
    Generated SQL: SELECT * FROM users WHERE email = '' OR '1'='1'
    Result: If executed, the logical expression '1'='1' evaluates to true,
            returning ALL users in the database, compromising account security.

[2] Protected Parameterized Query Execution:
    Generated SQL: SELECT * FROM users WHERE email = ?
    Result Rows Fetched: 0
    [+] PASS: Attacker input was treated as a literal search string.
              No user accounts were exposed. The SQL injection was neutralized.

-----------------------------------------
[🏆 SUCCESS] SQL Injection defense verification test PASSED.
=========================================
```

### 2. XSS Escape Verification Test
Run:
```powershell
C:\xampp\php\php.exe tests/test_xss.php
```
**Output Log**:
```text
=== Automated XSS Defense Verification ===

Test Case #1:
  Original Payload: <script>alert('test')</script>
  Escaped Output  : &lt;script&gt;alert(&#039;test&#039;)&lt;/script&gt;
  [+] PASS: Special characters correctly converted to HTML entities.
            Browser will display this as LITERAL text. No script execution possible.

Test Case #2:
  Original Payload: <img src=x onerror=alert(document.cookie)>
  Escaped Output  : &lt;img src=x onerror=alert(document.cookie)&gt;
  [+] PASS: Special characters correctly converted to HTML entities.
            Browser will display this as LITERAL text. No script execution possible.

Test Case #3:
  Original Payload: javascript:alert(1)
  Escaped Output  : javascript:alert(1)
  [+] PASS: Special characters correctly converted to HTML entities.
            Browser will display this as LITERAL text. No script execution possible.

Test Case #4:
  Original Payload: " onmouseover="alert('xss')" id="test
  Escaped Output  : &quot; onmouseover=&quot;alert(&#039;xss&#039;)&quot; id=&quot;test
  [+] PASS: Special characters correctly converted to HTML entities.
            Browser will display this as LITERAL text. No script execution possible.

-----------------------------------------
[🏆 SUCCESS] All XSS defense verification tests PASSED.
=========================================
```
