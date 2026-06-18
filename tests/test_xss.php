<?php
// tests/test_xss.php - XSS Mitigation Proof Script

require_once __DIR__ . '/../includes/auth.php';

echo "=== Automated XSS Defense Verification ===\n";

// 1. Define typical XSS payloads
$payloads = [
    "<script>alert('test')</script>",
    "<img src=x onerror=alert(document.cookie)>",
    "javascript:alert(1)",
    "\" onmouseover=\"alert('xss')\" id=\"test"
];

$all_passed = true;

foreach ($payloads as $index => $payload) {
    echo "\nTest Case #" . ($index + 1) . ":\n";
    echo "  Original Payload: " . $payload . "\n";
    
    // Run sanitization helper
    $sanitized = s($payload);
    echo "  Escaped Output  : " . $sanitized . "\n";
    
    // Assert safety
    // A payload is safe if it doesn't contain raw <script, raw <img, or unescaped quotes that could alter HTML attributes
    $is_safe = true;
    if (strpos($sanitized, '<') !== false) {
        $is_safe = false;
        echo "  [-] FAIL: Output contains raw '<' character.\n";
    }
    if (strpos($sanitized, '>') !== false) {
        $is_safe = false;
        echo "  [-] FAIL: Output contains raw '>' character.\n";
    }
    if (strpos($sanitized, '"') !== false) {
        $is_safe = false;
        echo "  [-] FAIL: Output contains raw double quote '\"'.\n";
    }
    if (strpos($sanitized, "'") !== false) {
        $is_safe = false;
        echo "  [-] FAIL: Output contains raw single quote '\''.\n";
    }
    
    if ($is_safe) {
        echo "  [+] PASS: Special characters correctly converted to HTML entities.\n";
        echo "            Browser will display this as LITERAL text. No script execution possible.\n";
    } else {
        $all_passed = false;
    }
}

echo "\n-----------------------------------------\n";
if ($all_passed) {
    echo "[🏆 SUCCESS] All XSS defense verification tests PASSED.\n";
} else {
    echo "[❌ FAILURE] Some XSS defense tests FAILED.\n";
}
echo "=========================================\n";
