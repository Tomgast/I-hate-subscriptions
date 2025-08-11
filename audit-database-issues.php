<?php
/**
 * Comprehensive Database Column Mismatch and Issues Audit
 * Checks for potential database-related problems throughout the application
 */

session_start();
require_once 'includes/database_helper.php';

echo "<h1>Database Issues Audit</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .error { color: red; }
    .success { color: green; }
    .info { color: blue; }
    .warning { color: orange; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .highlight { background-color: #fff3cd; padding: 10px; border-radius: 5px; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
    .issue { background-color: #ffebee; padding: 10px; margin: 5px 0; border-left: 4px solid #f44336; }
    .fixed { background-color: #e8f5e8; padding: 10px; margin: 5px 0; border-left: 4px solid #4caf50; }
</style>";

$userId = $_GET['user_id'] ?? 2; // Use user 2 as default since that's where the subscriptions are
$issues = [];
$fixes = [];

try {
    $pdo = DatabaseHelper::getConnection();
    
    echo "<div class='section'>";
    echo "<h2>1. Database Schema Analysis</h2>";
    
    // Check actual table schemas
    $tables = ['subscriptions', 'users', 'bank_accounts', 'transactions', 'scan_records'];
    $schemas = [];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $schemas[$table] = array_column($columns, 'Field');
            
            echo "<h3>$table table columns:</h3>";
            echo "<p class='info'>" . implode(', ', $schemas[$table]) . "</p>";
        } catch (Exception $e) {
            echo "<p class='warning'>Table '$table' does not exist or is not accessible</p>";
            $schemas[$table] = [];
        }
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>2. Subscription Data Analysis</h2>";
    
    // Check subscription data consistency
    if (!empty($schemas['subscriptions'])) {
        $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? LIMIT 5");
        $stmt->execute([$userId]);
        $sampleSubscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($sampleSubscriptions)) {
            echo "<h3>Sample Subscription Data:</h3>";
            echo "<table>";
            echo "<tr>";
            foreach (array_keys($sampleSubscriptions[0]) as $col) {
                echo "<th>$col</th>";
            }
            echo "</tr>";
            
            foreach ($sampleSubscriptions as $sub) {
                echo "<tr>";
                foreach ($sub as $value) {
                    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
            
            // Check for common column issues
            $firstSub = $sampleSubscriptions[0];
            
            // Check name vs merchant_name
            if (!isset($firstSub['name']) && isset($firstSub['merchant_name'])) {
                $issues[] = "Subscriptions use 'merchant_name' but code may expect 'name'";
            }
            if (!isset($firstSub['cost']) && isset($firstSub['amount'])) {
                $issues[] = "Subscriptions use 'amount' but code may expect 'cost'";
            }
            if (!isset($firstSub['is_active']) && isset($firstSub['status'])) {
                $issues[] = "Subscriptions use 'status' but code may expect 'is_active'";
            }
            
            // Check for empty/null values
            foreach ($firstSub as $col => $value) {
                if ($value === null || $value === '') {
                    $issues[] = "Column '$col' has null/empty values in sample data";
                }
            }
        } else {
            echo "<p class='warning'>No subscription data found for user $userId</p>";
        }
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>3. Code File Analysis</h2>";
    
    // Check key files for potential column mismatches
    $filesToCheck = [
        'dashboard.php' => 'Main dashboard display',
        'includes/subscription_manager.php' => 'Subscription management',
        'api/subscriptions/create.php' => 'Subscription creation API',
        'export/pdf.php' => 'PDF export functionality',
        'export/csv.php' => 'CSV export functionality'
    ];
    
    foreach ($filesToCheck as $file => $description) {
        $fullPath = __DIR__ . '/' . $file;
        if (file_exists($fullPath)) {
            echo "<h3>$file ($description)</h3>";
            $content = file_get_contents($fullPath);
            
            // Check for potential issues
            $fileIssues = [];
            
            // Check for old column references
            if (strpos($content, "\$subscription['cost']") !== false && 
                strpos($content, "\$subscription['amount']") === false) {
                $fileIssues[] = "Uses 'cost' column but not 'amount' (may not work with GoCardless)";
            }
            
            if (strpos($content, "\$subscription['name']") !== false && 
                strpos($content, "\$subscription['merchant_name']") === false) {
                $fileIssues[] = "Uses 'name' column but not 'merchant_name' (may not work with GoCardless)";
            }
            
            if (strpos($content, "\$subscription['is_active']") !== false && 
                strpos($content, "\$subscription['status']") === false) {
                $fileIssues[] = "Uses 'is_active' column but not 'status' (may not work with GoCardless)";
            }
            
            // Check for database connection issues
            if (strpos($content, 'getDBConnection()') !== false && 
                strpos($content, 'DatabaseHelper::getConnection()') !== false) {
                $fileIssues[] = "Uses both getDBConnection() and DatabaseHelper::getConnection() - inconsistent";
            }
            
            if (!empty($fileIssues)) {
                foreach ($fileIssues as $issue) {
                    echo "<div class='issue'>⚠️ $issue</div>";
                    $issues[] = "$file: $issue";
                }
            } else {
                echo "<div class='fixed'>✅ No obvious issues found</div>";
            }
        } else {
            echo "<h3>$file</h3>";
            echo "<p class='warning'>File not found</p>";
        }
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>4. JavaScript Data Issues</h2>";
    
    // Check dashboard JavaScript for data issues
    if (file_exists(__DIR__ . '/dashboard.php')) {
        $dashboardContent = file_get_contents(__DIR__ . '/dashboard.php');
        
        echo "<h3>JavaScript Subscription Data Handling:</h3>";
        
        // Look for JavaScript subscription data usage
        if (strpos($dashboardContent, 'subscriptionsData') !== false) {
            echo "<p class='info'>Found subscriptionsData usage in JavaScript</p>";
            
            // Check if it's properly populated
            if (strpos($dashboardContent, 'const subscriptionsData = <?php echo json_encode($subscriptions); ?>') !== false) {
                echo "<div class='fixed'>✅ subscriptionsData is properly populated from PHP</div>";
            } else {
                echo "<div class='issue'>⚠️ subscriptionsData may not be properly populated</div>";
                $issues[] = "JavaScript subscriptionsData may not be properly populated from PHP";
            }
        }
        
        // Check for column name usage in JavaScript
        if (strpos($dashboardContent, 'sub.cost') !== false) {
            if (strpos($dashboardContent, 'sub.amount') !== false) {
                echo "<div class='fixed'>✅ JavaScript handles both 'cost' and 'amount' columns</div>";
            } else {
                echo "<div class='issue'>⚠️ JavaScript only uses 'cost' column, may not work with GoCardless</div>";
                $issues[] = "JavaScript only uses 'cost' column in dashboard";
            }
        }
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>5. Export Functionality Check</h2>";
    
    // Check export files for column issues
    $exportFiles = ['export/pdf.php', 'export/csv.php', 'export/index.php'];
    
    foreach ($exportFiles as $exportFile) {
        $fullPath = __DIR__ . '/' . $exportFile;
        if (file_exists($fullPath)) {
            echo "<h3>$exportFile</h3>";
            $content = file_get_contents($fullPath);
            
            $exportIssues = [];
            
            // Check for subscription column usage
            if (strpos($content, 'subscription') !== false) {
                if (strpos($content, "['cost']") !== false && strpos($content, "['amount']") === false) {
                    $exportIssues[] = "Export uses 'cost' but not 'amount' - may not export GoCardless subscriptions correctly";
                }
                if (strpos($content, "['name']") !== false && strpos($content, "['merchant_name']") === false) {
                    $exportIssues[] = "Export uses 'name' but not 'merchant_name' - may not export GoCardless names correctly";
                }
            }
            
            if (!empty($exportIssues)) {
                foreach ($exportIssues as $issue) {
                    echo "<div class='issue'>⚠️ $issue</div>";
                    $issues[] = "$exportFile: $issue";
                }
            } else {
                echo "<div class='fixed'>✅ No obvious column issues found</div>";
            }
        } else {
            echo "<h3>$exportFile</h3>";
            echo "<p class='warning'>File not found</p>";
        }
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>6. API Endpoints Check</h2>";
    
    // Check API files for consistency
    $apiFiles = [
        'api/subscriptions/create.php',
        'api/subscriptions/update.php', 
        'api/subscriptions/delete.php',
        'api/bank-accounts.php'
    ];
    
    foreach ($apiFiles as $apiFile) {
        $fullPath = __DIR__ . '/' . $apiFile;
        if (file_exists($fullPath)) {
            echo "<h3>$apiFile</h3>";
            $content = file_get_contents($fullPath);
            
            $apiIssues = [];
            
            // Check for database connection consistency
            if (strpos($content, 'getDBConnection()') !== false && 
                strpos($content, 'DatabaseHelper::getConnection()') !== false) {
                $apiIssues[] = "Uses both database connection methods - should be consistent";
            }
            
            // Check for subscription column usage
            if (strpos($content, 'INSERT INTO subscriptions') !== false) {
                if (strpos($content, 'cost') !== false && strpos($content, 'amount') === false) {
                    $apiIssues[] = "INSERT uses 'cost' column but GoCardless uses 'amount'";
                }
                if (strpos($content, 'name') !== false && strpos($content, 'merchant_name') === false) {
                    $apiIssues[] = "INSERT uses 'name' column but GoCardless uses 'merchant_name'";
                }
            }
            
            if (!empty($apiIssues)) {
                foreach ($apiIssues as $issue) {
                    echo "<div class='issue'>⚠️ $issue</div>";
                    $issues[] = "$apiFile: $issue";
                }
            } else {
                echo "<div class='fixed'>✅ No obvious issues found</div>";
            }
        } else {
            echo "<h3>$apiFile</h3>";
            echo "<p class='warning'>File not found</p>";
        }
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>7. Summary of Issues Found</h2>";
    
    if (!empty($issues)) {
        echo "<div class='highlight'>";
        echo "<h3>🚨 Issues Requiring Attention:</h3>";
        echo "<ol>";
        foreach ($issues as $issue) {
            echo "<li class='error'>$issue</li>";
        }
        echo "</ol>";
        echo "</div>";
    } else {
        echo "<div class='highlight'>";
        echo "<h3>✅ No Critical Issues Found!</h3>";
        echo "<p class='success'>All checked components appear to be using compatible database column names and connections.</p>";
        echo "</div>";
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>8. Recommended Actions</h2>";
    echo "<div class='highlight'>";
    echo "<h3>🔧 Next Steps:</h3>";
    echo "<ol>";
    echo "<li><strong>Test Export Functions:</strong> Try exporting subscriptions to PDF/CSV to verify they work with GoCardless data</li>";
    echo "<li><strong>Test API Endpoints:</strong> Try creating/updating subscriptions via API to ensure compatibility</li>";
    echo "<li><strong>Check Other Dashboard Pages:</strong> Verify any other pages that display subscription data</li>";
    echo "<li><strong>Monitor Error Logs:</strong> Watch for any PHP errors related to undefined array keys</li>";
    echo "</ol>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<p class='error'>Audit Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "<p><a href='?user_id=$userId&refresh=1'>🔄 Refresh Audit</a></p>";
?>
