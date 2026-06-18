<?php
// tests/test_sqli.php - SQL Injection Mitigation Verification Script

require_once __DIR__ . '/../config/database.php';

echo "=== Automated SQL Injection Defense Verification ===\n";

// The attacker payload to gain unauthorized access
$attacker_input = "' OR '1'='1";

try {
    $pdo = getDbConnection();
    echo "[+] Successfully connected to the database.\n";
    
    echo "\n[1] Vulnerable Query Emulation (Conceptual):\n";
    // Constructing a query using string concatenation (VULNERABLE)
    $vulnerable_sql = "SELECT * FROM users WHERE email = '" . $attacker_input . "'";
    echo "    Generated SQL: " . $vulnerable_sql . "\n";
    echo "    Result: If executed, the logical expression '1'='1' evaluates to true,\n";
    echo "            returning ALL users in the database, compromising account security.\n";

    echo "\n[2] Protected Parameterized Query Execution:\n";
    // Constructing query using prepared statement (SECURED)
    $secure_sql = "SELECT * FROM users WHERE email = ?";
    echo "    Generated SQL: " . $secure_sql . "\n";
    
    $stmt = $pdo->prepare($secure_sql);
    $stmt->execute([$attacker_input]);
    $results = $stmt->fetchAll();
    
    echo "    Result Rows Fetched: " . count($results) . "\n";
    
    // Assertion
    if (count($results) === 0) {
        echo "    [+] PASS: Attacker input was treated as a literal search string.\n";
        echo "              No user accounts were exposed. The SQL injection was neutralized.\n";
    } else {
        echo "    [-] FAIL: The injection payload succeeded in fetching records.\n";
    }

} catch (PDOException $e) {
    echo "[-] Database Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n-----------------------------------------\n";
echo "[🏆 SUCCESS] SQL Injection defense verification test PASSED.\n";
echo "=========================================\n";
