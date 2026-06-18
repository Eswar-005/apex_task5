<?php
// setup_db.php - Automated database initializer and seeder

$host = '127.0.0.1';
$port = '3307';
$user = 'root';
$pass = '';

echo "=== Employee Management Database Setup ===\n";

try {
    // 1. Establish connection to MySQL server
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "[+] Connected to MySQL server successfully.\n";

    // 2. Read and execute setup.sql
    $sql = file_get_contents(__DIR__ . '/setup.sql');
    if ($sql === false) {
        throw new Exception("Could not read setup.sql file.");
    }
    
    // Split SQL file by semicolons to execute multiple statements (simple parser)
    // We remove comments and split by semi-colons
    $queries = explode(';', $sql);
    foreach ($queries as $query) {
        $trimmed = trim($query);
        if (!empty($trimmed)) {
            $pdo->exec($trimmed);
        }
    }
    echo "[+] Database and tables created successfully.\n";

    // Re-connect to the created database to perform seeding
    $pdo->exec("USE `employee_management`;");

    // 3. Seed Default Admin User
    $adminEmail = 'admin@portal.com';
    $adminName = 'System Admin';
    $adminPassword = 'AdminSecure123!';
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$adminEmail]);
    if ($stmt->fetch()) {
        echo "[.] Admin user '$adminEmail' already exists.\n";
    } else {
        $hashedPassword = password_hash($adminPassword, PASSWORD_BCRYPT);
        $insertStmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
        $insertStmt->execute([$adminName, $adminEmail, $hashedPassword]);
        echo "[+] Default admin user created:\n";
        echo "    Email: $adminEmail\n";
        echo "    Password: $adminPassword\n";
    }

    // 4. Seed Standard User (HR Staff)
    $hrEmail = 'hr@portal.com';
    $hrName = 'Sarah Jenkins';
    $hrPassword = 'HRPassword123!';
    
    $stmt->execute([$hrEmail]);
    if ($stmt->fetch()) {
        echo "[.] HR user '$hrEmail' already exists.\n";
    } else {
        $hashedPassword = password_hash($hrPassword, PASSWORD_BCRYPT);
        $insertStmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
        $insertStmt->execute([$hrName, $hrEmail, $hashedPassword]);
        echo "[+] Default HR user created:\n";
        echo "    Email: $hrEmail\n";
        echo "    Password: $hrPassword\n";
    }

    // 5. Seed Sample Employee Records for Pagination (10 records per page, let's create 25 records)
    $stmt = $pdo->query("SELECT COUNT(*) FROM records");
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        echo "[.] Records table already has $count records. Skipping sample seeding.\n";
    } else {
        $sampleRecords = [
            ['Alice Smith', 'Department: Cybersecurity | Email: alice.smith@portal.com | Title: Security Engineer', '2026-01-15'],
            ['Bob Johnson', 'Department: HR | Email: bob.johnson@portal.com | Title: HR Specialist', '2026-02-10'],
            ['Charlie Brown', 'Department: IT Support | Email: charlie.brown@portal.com | Title: Helpdesk Analyst', '2026-03-01'],
            ['Diana Prince', 'Department: Executive Office | Email: diana.prince@portal.com | Title: Chief Executive', '2025-06-20'],
            ['Evan Wright', 'Department: Cybersecurity | Email: evan.wright@portal.com | Title: Penetration Tester', '2026-04-12'],
            ['Fiona Gallagher', 'Department: Finance | Email: fiona.gallagher@portal.com | Title: Senior Accountant', '2025-11-05'],
            ['George Clark', 'Department: IT Support | Email: george.clark@portal.com | Title: Network Administrator', '2026-01-22'],
            ['Hannah Abbott', 'Department: Legal | Email: hannah.abbott@portal.com | Title: Legal Counsel', '2026-03-15'],
            ['Ian Malcolm', 'Department: Research | Email: ian.malcolm@portal.com | Title: Data Scientist', '2025-08-14'],
            ['Julia Roberts', 'Department: Marketing | Email: julia.roberts@portal.com | Title: Public Relations Manager', '2026-02-28'],
            ['Kevin Bacon', 'Department: Security | Email: kevin.bacon@portal.com | Title: Physical Security Guard', '2026-05-01'],
            ['Laura Croft', 'Department: Research | Email: laura.croft@portal.com | Title: Field Archaeologist', '2024-09-10'],
            ['Michael Scott', 'Department: Executive Office | Email: michael.scott@portal.com | Title: Regional Branch Manager', '2025-04-01'],
            ['Nancy Drew', 'Department: Security | Email: nancy.drew@portal.com | Title: Private Investigator', '2026-02-14'],
            ['Oscar Martinez', 'Department: Finance | Email: oscar.martinez@portal.com | Title: Chief Accountant', '2025-03-12'],
            ['Pam Beesly', 'Department: HR | Email: pam.beesly@portal.com | Title: Receptionist', '2025-05-18'],
            ['Quentin Tarantino', 'Department: Marketing | Email: quentin.tarantino@portal.com | Title: Creative Director', '2026-01-08'],
            ['Rachel Green', 'Department: Marketing | Email: rachel.green@portal.com | Title: Fashion Merchandiser', '2026-04-05'],
            ['Steve Rogers', 'Department: Security | Email: steve.rogers@portal.com | Title: Security Coordinator', '2025-07-04'],
            ['Tony Stark', 'Department: Research | Email: tony.stark@portal.com | Title: Lead Engineer', '2025-05-02'],
            ['Ursula Buffay', 'Department: Finance | Email: ursula.buffay@portal.com | Title: Financial Planner', '2026-03-30'],
            ['Victor Stone', 'Department: IT Support | Email: victor.stone@portal.com | Title: Systems Architect', '2026-02-12'],
            ['Wendy Darling', 'Department: HR | Email: wendy.darling@portal.com | Title: Recruiting Specialist', '2026-04-20'],
            ['Xavier Charles', 'Department: Executive Office | Email: xavier.charles@portal.com | Title: HR Director', '2025-01-01'],
            ['Yvonne Strahovski', 'Department: Cybersecurity | Email: yvonne.strahovski@portal.com | Title: Intelligence Analyst', '2026-05-10'],
        ];

        $insertRecord = $pdo->prepare("INSERT INTO records (title, description, created_at) VALUES (?, ?, ?)");
        foreach ($sampleRecords as $rec) {
            $insertRecord->execute($rec);
        }
        echo "[+] Seeded " . count($sampleRecords) . " sample employee records.\n";
    }

    echo "=== Setup Completed Successfully ===\n";

} catch (Exception $e) {
    echo "[-] Error: " . $e->getMessage() . "\n";
    exit(1);
}
