<?php
/**
 * 🎯 PRO USER TESTING SUITE
 * Comprehensive testing environment for Pro user functionality
 */

require_once 'config/db_config.php';
require_once 'includes/plan_manager.php';

echo "<h1>🎯 CashControl Pro User Testing Suite</h1>\n";

$testEmail = 'support@origens.nl';
$testName = 'Pro Test User';

try {
    echo "<h2>🔧 Step 1: Setup Pro Test Account</h2>\n";
    
    // Check/create user
    $stmt = $pdo->prepare("SELECT user_id, email, name FROM users WHERE email = ?");
    $stmt->execute([$testEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $stmt = $pdo->prepare("INSERT INTO users (email, name, google_id, created_at, updated_at) VALUES (?, ?, 'test_google_id', NOW(), NOW())");
        $stmt->execute([$testEmail, $testName]);
        $userId = $pdo->lastInsertId();
        echo "<div style='background: green; color: white; padding: 15px; margin: 10px;'>✅ Created test user: $testEmail</div>";
    } else {
        $userId = $user['user_id'];
        echo "<div style='background: blue; color: white; padding: 15px; margin: 10px;'>✅ Using existing user: {$user['email']}</div>";
    }
    
    // Setup Pro plan
    $stmt = $pdo->prepare("
        INSERT INTO user_plans (user_id, plan_type, is_active, expires_at, created_at, updated_at)
        VALUES (?, 'monthly', 1, DATE_ADD(NOW(), INTERVAL 1 MONTH), NOW(), NOW())
        ON DUPLICATE KEY UPDATE plan_type = 'monthly', is_active = 1, expires_at = DATE_ADD(NOW(), INTERVAL 1 MONTH), updated_at = NOW()
    ");
    $stmt->execute([$userId]);
    
    echo "<div style='background: darkgreen; color: white; padding: 15px; margin: 10px;'>✅ Pro Monthly Plan activated</div>";
    
    // Create session
    session_start();
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $testEmail;
    $_SESSION['user_name'] = $testName;
    
    echo "<div style='background: purple; color: white; padding: 15px; margin: 10px;'>✅ Test session created - you are now logged in as Pro user</div>";
    
    // Verify plan
    $planManager = getPlanManager();
    $userPlan = $planManager->getUserPlan($userId);
    
    echo "<div style='border: 2px solid #10b981; padding: 20px; margin: 20px; background: #f0fdf4;'>";
    echo "<h3>📋 Current Plan Status</h3>";
    echo "<p><strong>Plan Type:</strong> {$userPlan['plan_type']}</p>";
    echo "<p><strong>Active:</strong> " . ($userPlan['is_active'] ? 'Yes' : 'No') . "</p>";
    echo "<p><strong>Expires:</strong> {$userPlan['expires_at']}</p>";
    echo "</div>";
    
    echo "<h2>🚀 Step 2: Pro Feature Testing Links</h2>\n";
    
    $testingLinks = [
        'Core Features' => [
            'dashboard.php' => 'Main Pro Dashboard',
            'settings.php' => 'Account Settings',
            'upgrade.php' => 'Upgrade Page (should show current plan)'
        ],
        'Pro Features' => [
            'bank/scan.php' => 'Bank Account Scan',
            'export/index.php' => 'Export System',
            'export/pdf.php' => 'PDF Export',
            'export/csv.php' => 'CSV Export',
            'guides/index.php' => 'Unsubscribe Guides'
        ],
        'Payment System' => [
            'payment/success.php' => 'Payment Success',
            'payment/cancel.php' => 'Payment Cancel'
        ]
    ];
    
    foreach ($testingLinks as $category => $links) {
        echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 15px; background: #f9f9f9;'>";
        echo "<h3>🔧 $category</h3>";
        echo "<ul>";
        foreach ($links as $url => $description) {
            echo "<li><a href='$url' target='_blank' style='color: #10b981; font-weight: bold;'>$description</a></li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    echo "<h2>📝 Step 3: Quick Testing Checklist</h2>\n";
    
    echo "<div style='background: #f0f9ff; border: 2px solid #0ea5e9; padding: 20px; margin: 20px;'>";
    echo "<h3>📋 Pro User Testing Checklist</h3>";
    echo "<h4>Core Functionality</h4>";
    echo "<ul>";
    echo "<li>☐ Dashboard loads without redirect to upgrade</li>";
    echo "<li>☐ All Pro features are accessible</li>";
    echo "<li>☐ No 500 internal server errors</li>";
    echo "<li>☐ Session persists across pages</li>";
    echo "</ul>";
    
    echo "<h4>Pro Features</h4>";
    echo "<ul>";
    echo "<li>☐ Bank integration page loads</li>";
    echo "<li>☐ Export system works (PDF/CSV)</li>";
    echo "<li>☐ Unsubscribe guides accessible</li>";
    echo "<li>☐ Settings page functional</li>";
    echo "</ul>";
    
    echo "<h4>Payment System</h4>";
    echo "<ul>";
    echo "<li>☐ Upgrade page shows current plan</li>";
    echo "<li>☐ Payment success/cancel pages work</li>";
    echo "<li>☐ Plan status displayed correctly</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='background: #1f2937; color: white; padding: 20px; margin: 20px;'>";
    echo "<h3>🚀 Quick Test Commands</h3>";
    echo "<p><strong>Start Here:</strong></p>";
    echo "<p>• <a href='dashboard.php' style='color: #10b981;'>🏠 Test Dashboard</a> (should load without redirect)</p>";
    echo "<p>• <a href='bank/scan.php' style='color: #10b981;'>🏦 Test Bank Integration</a></p>";
    echo "<p>• <a href='export/index.php' style='color: #10b981;'>📄 Test Export System</a></p>";
    echo "<p>• <a href='guides/index.php' style='color: #10b981;'>📚 Test Unsubscribe Guides</a></p>";
    echo "<br>";
    echo "<p><strong>Expected Results:</strong></p>";
    echo "<p>✅ All pages load without 500 errors</p>";
    echo "<p>✅ No redirects to upgrade page</p>";
    echo "<p>✅ Full Pro functionality available</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: red; color: white; padding: 20px; margin: 20px;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
