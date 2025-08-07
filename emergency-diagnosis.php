<?php
/**
 * EMERGENCY DIAGNOSIS - Find the root cause of 500 errors
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>🚨 EMERGENCY DIAGNOSIS</h1>\n";
echo "<p>Identifying the root cause of internal server errors...</p>\n";

// Test 1: Basic PHP functionality
echo "<h2>Test 1: Basic PHP</h2>\n";
echo "✅ PHP is working - you can see this message<br>\n";
echo "PHP Version: " . PHP_VERSION . "<br>\n";

// Test 2: File system access
echo "<h2>Test 2: File System</h2>\n";
$currentDir = __DIR__;
echo "Current directory: $currentDir<br>\n";
if (is_readable($currentDir)) {
    echo "✅ Directory is readable<br>\n";
} else {
    echo "❌ Directory is NOT readable<br>\n";
}

// Test 3: Critical file existence
echo "<h2>Test 3: Critical Files</h2>\n";
$criticalFiles = [
    'config/secure_loader.php',
    'config/db_config.php',
    'includes/plan_manager.php',
    'includes/stripe_service.php'
];

foreach ($criticalFiles as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>\n";
        if (is_readable($file)) {
            echo "  → File is readable<br>\n";
        } else {
            echo "  → ❌ File is NOT readable<br>\n";
        }
    } else {
        echo "❌ $file MISSING<br>\n";
    }
}

// Test 4: Try to load secure config
echo "<h2>Test 4: Configuration Loading</h2>\n";
try {
    if (file_exists('config/secure_loader.php')) {
        require_once 'config/secure_loader.php';
        echo "✅ secure_loader.php loaded<br>\n";
        
        $config = getSecureConfig();
        if ($config) {
            echo "✅ getSecureConfig() works<br>\n";
            echo "Config keys found: " . implode(', ', array_keys($config)) . "<br>\n";
        } else {
            echo "❌ getSecureConfig() returned empty<br>\n";
        }
    } else {
        echo "❌ secure_loader.php missing<br>\n";
    }
} catch (Exception $e) {
    echo "❌ Configuration error: " . $e->getMessage() . "<br>\n";
}

// Test 5: Try database connection
echo "<h2>Test 5: Database Connection</h2>\n";
try {
    if (file_exists('config/db_config.php')) {
        require_once 'config/db_config.php';
        echo "✅ db_config.php loaded<br>\n";
        
        $pdo = getDBConnection();
        if ($pdo) {
            echo "✅ Database connection successful<br>\n";
            $stmt = $pdo->query("SELECT 1 as test");
            $result = $stmt->fetch();
            echo "✅ Database query works: " . $result['test'] . "<br>\n";
        } else {
            echo "❌ Database connection failed<br>\n";
        }
    } else {
        echo "❌ db_config.php missing<br>\n";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>\n";
}

// Test 6: Try to load plan manager
echo "<h2>Test 6: Plan Manager</h2>\n";
try {
    if (file_exists('includes/plan_manager.php')) {
        require_once 'includes/plan_manager.php';
        echo "✅ plan_manager.php loaded<br>\n";
        
        $planManager = getPlanManager();
        if ($planManager) {
            echo "✅ Plan Manager initialized<br>\n";
        } else {
            echo "❌ Plan Manager failed to initialize<br>\n";
        }
    } else {
        echo "❌ plan_manager.php missing<br>\n";
    }
} catch (Exception $e) {
    echo "❌ Plan Manager error: " . $e->getMessage() . "<br>\n";
}

// Test 7: Session functionality
echo "<h2>Test 7: Session</h2>\n";
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        echo "✅ Session started<br>\n";
    } else {
        echo "✅ Session already active<br>\n";
    }
    
    $_SESSION['test'] = 'working';
    if (isset($_SESSION['test'])) {
        echo "✅ Session variables work<br>\n";
    } else {
        echo "❌ Session variables not working<br>\n";
    }
} catch (Exception $e) {
    echo "❌ Session error: " . $e->getMessage() . "<br>\n";
}

// Test 8: Check for common syntax errors in key files
echo "<h2>Test 8: Syntax Check</h2>\n";
$keyFiles = ['index.php', 'dashboard.php', 'upgrade.php', 'auth/signin.php'];

foreach ($keyFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Check for common issues
        $issues = [];
        
        // Check for unclosed PHP tags
        if (substr_count($content, '<?php') !== substr_count($content, '?>') + 1) {
            $issues[] = "Mismatched PHP tags";
        }
        
        // Check for obvious syntax errors
        if (strpos($content, 'Parse error') !== false) {
            $issues[] = "Parse error detected";
        }
        
        // Check for missing semicolons (basic check)
        if (preg_match('/\$[a-zA-Z_][a-zA-Z0-9_]*\s*=\s*[^;]+\n/', $content)) {
            $issues[] = "Possible missing semicolon";
        }
        
        if (empty($issues)) {
            echo "✅ $file appears syntactically correct<br>\n";
        } else {
            echo "❌ $file has issues: " . implode(', ', $issues) . "<br>\n";
        }
    } else {
        echo "❌ $file missing<br>\n";
    }
}

// Test 9: Check error logs
echo "<h2>Test 9: Error Logs</h2>\n";
$errorLogPaths = [
    ini_get('error_log'),
    '/var/log/php_errors.log',
    'error_log',
    '../error_log'
];

foreach ($errorLogPaths as $logPath) {
    if ($logPath && file_exists($logPath)) {
        echo "✅ Found error log: $logPath<br>\n";
        $errors = file_get_contents($logPath);
        $recentErrors = array_slice(explode("\n", $errors), -10);
        echo "<pre style='background:#f5f5f5;padding:10px;'>" . implode("\n", $recentErrors) . "</pre>\n";
        break;
    }
}

echo "<h2>🔧 IMMEDIATE ACTION REQUIRED</h2>\n";
echo "<div style='background:#fef2f2;padding:15px;border:1px solid #dc2626;'>\n";
echo "<p><strong>Based on the results above, the most likely causes are:</strong></p>\n";
echo "<ol>\n";
echo "<li><strong>Missing secure-config.php file</strong> - Check if it exists in the root directory</li>\n";
echo "<li><strong>Database connection failure</strong> - Verify credentials in secure-config.php</li>\n";
echo "<li><strong>File permission issues</strong> - Check file/folder permissions</li>\n";
echo "<li><strong>PHP syntax errors</strong> - Look for parse errors in key files</li>\n";
echo "<li><strong>Missing PHP extensions</strong> - PDO, curl, etc.</li>\n";
echo "</ol>\n";
echo "</div>\n";

echo "<hr>\n";
echo "<p><em>Diagnosis completed at " . date('Y-m-d H:i:s') . "</em></p>\n";
?>
