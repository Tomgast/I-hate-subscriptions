<?php
/**
 * PHASE 5: Simple File & Configuration Verification
 * Basic checks that can be run directly without complex automation
 */

echo "<h1>🔍 CashControl File & Configuration Check</h1>\n";
echo "<p>Simple verification of critical files and configurations.</p>\n";

// Critical files to check
$criticalFiles = [
    // Core pages
    'index.php' => 'Homepage',
    'dashboard.php' => 'Main Dashboard', 
    'dashboard-onetime.php' => 'One-time Dashboard',
    'upgrade.php' => 'Upgrade/Pricing Page',
    'settings.php' => 'Settings Page',
    
    // Authentication
    'auth/signin.php' => 'Sign In Page',
    'auth/signup.php' => 'Sign Up Page',
    'auth/logout.php' => 'Logout Handler',
    'auth/google-callback.php' => 'Google OAuth Callback',
    
    // Payment
    'payment/success.php' => 'Payment Success',
    'payment/cancel.php' => 'Payment Cancel',
    
    // Features
    'export/index.php' => 'Export Controller',
    'export/pdf.php' => 'PDF Export',
    'export/csv.php' => 'CSV Export',
    'guides/index.php' => 'Unsubscribe Guides',
    'bank/scan.php' => 'Bank Scan',
    
    // Core includes
    'includes/plan_manager.php' => 'Plan Manager',
    'includes/stripe_service.php' => 'Stripe Service',
    'includes/bank_service.php' => 'Bank Service',
    'includes/unsubscribe_service.php' => 'Unsubscribe Service',
    'includes/header.php' => 'Header Component',
    
    // Configuration
    'config/db_config.php' => 'Database Config',
    'config/secure_loader.php' => 'Secure Config Loader'
];

echo "<h2>📁 File Existence Check</h2>\n";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
echo "<tr><th>File</th><th>Status</th><th>Size</th><th>Modified</th></tr>\n";

$fileCount = 0;
$existingFiles = 0;

foreach ($criticalFiles as $file => $description) {
    $fileCount++;
    $exists = file_exists($file);
    $existingFiles += $exists ? 1 : 0;
    
    $status = $exists ? '✅ EXISTS' : '❌ MISSING';
    $size = $exists ? number_format(filesize($file)) . ' bytes' : '-';
    $modified = $exists ? date('Y-m-d H:i', filemtime($file)) : '-';
    
    $rowColor = $exists ? '#f0fdf4' : '#fef2f2';
    
    echo "<tr style='background: $rowColor;'>\n";
    echo "<td><strong>$file</strong><br><small>$description</small></td>\n";
    echo "<td>$status</td>\n";
    echo "<td>$size</td>\n";
    echo "<td>$modified</td>\n";
    echo "</tr>\n";
}

echo "</table>\n";
echo "<p><strong>Summary:</strong> $existingFiles of $fileCount critical files exist (" . round(($existingFiles/$fileCount)*100, 1) . "%)</p>\n";

// Configuration check
echo "<h2>⚙️ Configuration Check</h2>\n";

try {
    require_once 'config/secure_loader.php';
    $config = getSecureConfig();
    
    $configItems = [
        'DB_HOST' => 'Database Host',
        'DB_NAME' => 'Database Name', 
        'DB_USER' => 'Database User',
        'DB_PASSWORD' => 'Database Password',
        'STRIPE_SECRET_KEY' => 'Stripe Secret Key',
        'STRIPE_PUBLISHABLE_KEY' => 'Stripe Publishable Key',
        'SMTP_HOST' => 'SMTP Host',
        'SMTP_USERNAME' => 'SMTP Username',
        'SMTP_PASSWORD' => 'SMTP Password',
        'GOOGLE_CLIENT_ID' => 'Google OAuth Client ID',
        'GOOGLE_CLIENT_SECRET' => 'Google OAuth Secret'
    ];
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>Configuration</th><th>Status</th><th>Value Preview</th></tr>\n";
    
    foreach ($configItems as $key => $description) {
        $exists = isset($config[$key]) && !empty($config[$key]);
        $status = $exists ? '✅ SET' : '❌ MISSING';
        
        if ($exists) {
            $value = $config[$key];
            // Mask sensitive values
            if (strlen($value) > 10) {
                $preview = substr($value, 0, 6) . '...' . substr($value, -4);
            } else {
                $preview = str_repeat('*', strlen($value));
            }
        } else {
            $preview = 'Not set';
        }
        
        $rowColor = $exists ? '#f0fdf4' : '#fef2f2';
        
        echo "<tr style='background: $rowColor;'>\n";
        echo "<td><strong>$key</strong><br><small>$description</small></td>\n";
        echo "<td>$status</td>\n";
        echo "<td><code>$preview</code></td>\n";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    
} catch (Exception $e) {
    echo "<div style='background: #fef2f2; padding: 15px; border: 1px solid #dc2626; border-radius: 5px;'>\n";
    echo "<strong>❌ Configuration Error:</strong> " . htmlspecialchars($e->getMessage()) . "\n";
    echo "</div>\n";
}

// Database connection test
echo "<h2>🗄️ Database Connection Test</h2>\n";

try {
    require_once 'config/db_config.php';
    $pdo = getDBConnection();
    
    echo "<div style='background: #f0fdf4; padding: 15px; border: 1px solid #059669; border-radius: 5px;'>\n";
    echo "<strong>✅ Database Connected Successfully</strong><br>\n";
    
    // Test critical tables
    $tables = ['users', 'user_preferences', 'bank_scans', 'unsubscribe_guides'];
    echo "<br><strong>Table Check:</strong><br>\n";
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                // Get row count
                $countStmt = $pdo->query("SELECT COUNT(*) FROM $table");
                $count = $countStmt->fetchColumn();
                echo "✅ $table ($count rows)<br>\n";
            } else {
                echo "❌ $table (missing)<br>\n";
            }
        } catch (Exception $e) {
            echo "❌ $table (error: " . $e->getMessage() . ")<br>\n";
        }
    }
    
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div style='background: #fef2f2; padding: 15px; border: 1px solid #dc2626; border-radius: 5px;'>\n";
    echo "<strong>❌ Database Connection Failed:</strong> " . htmlspecialchars($e->getMessage()) . "\n";
    echo "</div>\n";
}

// Service initialization test
echo "<h2>🔧 Service Initialization Test</h2>\n";

$services = [
    'Plan Manager' => 'includes/plan_manager.php',
    'Stripe Service' => 'includes/stripe_service.php', 
    'Bank Service' => 'includes/bank_service.php',
    'Unsubscribe Service' => 'includes/unsubscribe_service.php'
];

foreach ($services as $serviceName => $file) {
    try {
        if (file_exists($file)) {
            require_once $file;
            echo "✅ $serviceName - File loaded successfully<br>\n";
        } else {
            echo "❌ $serviceName - File missing<br>\n";
        }
    } catch (Exception $e) {
        echo "❌ $serviceName - Error: " . htmlspecialchars($e->getMessage()) . "<br>\n";
    }
}

echo "<h2>📋 Next Steps</h2>\n";
echo "<div style='background: #fffbeb; padding: 15px; border: 1px solid #f59e0b; border-radius: 5px;'>\n";
echo "<p><strong>Manual Testing Required:</strong></p>\n";
echo "<ol>\n";
echo "<li><strong>Open PHASE5_TESTING_CHECKLIST.md</strong> - Use this as your testing guide</li>\n";
echo "<li><strong>Test User Registration:</strong> Visit /auth/signup.php and create test accounts</li>\n";
echo "<li><strong>Test Payment Processing:</strong> Visit /upgrade.php and test Stripe checkout</li>\n";
echo "<li><strong>Test Dashboard Access:</strong> Login and verify plan-based routing works</li>\n";
echo "<li><strong>Test Export System:</strong> Generate PDF/CSV exports</li>\n";
echo "<li><strong>Test Bank Integration:</strong> Try TrueLayer connection</li>\n";
echo "</ol>\n";
echo "<p><strong>Use Stripe Test Cards:</strong></p>\n";
echo "<ul>\n";
echo "<li>Success: 4242 4242 4242 4242</li>\n";
echo "<li>Decline: 4000 0000 0000 0002</li>\n";
echo "<li>Any future expiry date and CVC</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "<hr>\n";
echo "<p><em>File check completed at " . date('Y-m-d H:i:s') . "</em></p>\n";
?>
