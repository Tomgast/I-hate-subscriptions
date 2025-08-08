<?php
/**
 * MOCK SUBSCRIPTION DATA INJECTION TOOL
 * Inject realistic test subscription data for comprehensive testing
 */

session_start();
require_once 'config/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Please log in first');
}

$userId = $_SESSION['user_id'];
$userEmail = $_SESSION['user_email'] ?? '';

echo "<h1>🧪 Mock Subscription Data Injection Tool</h1>";
echo "<p><strong>User ID:</strong> {$userId} | <strong>Email:</strong> {$userEmail}</p>";

echo "<div style='background: #e8f5e8; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<strong>🎯 Purpose:</strong><br>";
echo "This tool injects realistic subscription data to test your dashboard, export features, and subscription management functionality without needing real bank data.";
echo "</div>";

try {
    $pdo = getDBConnection();
    
    // Define realistic test subscriptions
    $testSubscriptions = [
        [
            'name' => 'Netflix',
            'description' => 'Video streaming service',
            'cost' => 12.99,
            'currency' => 'EUR',
            'billing_cycle' => 'monthly',
            'category' => 'Entertainment',
            'website_url' => 'https://netflix.com',
            'logo_url' => 'https://logos-world.net/wp-content/uploads/2020/04/Netflix-Logo.png',
            'status' => 'active',
            'cancellation_url' => 'https://netflix.com/cancel',
            'notes' => 'Standard plan with HD streaming'
        ],
        [
            'name' => 'Spotify Premium',
            'description' => 'Music streaming service',
            'cost' => 9.99,
            'currency' => 'EUR',
            'billing_cycle' => 'monthly',
            'category' => 'Music',
            'website_url' => 'https://spotify.com',
            'logo_url' => 'https://storage.googleapis.com/pr-newsroom-wp/1/2018/11/Spotify_Logo_RGB_Green.png',
            'status' => 'active',
            'cancellation_url' => 'https://spotify.com/account',
            'notes' => 'Premium individual plan'
        ],
        [
            'name' => 'Adobe Creative Cloud',
            'description' => 'Creative software suite',
            'cost' => 59.99,
            'currency' => 'EUR',
            'billing_cycle' => 'monthly',
            'category' => 'Software',
            'website_url' => 'https://adobe.com',
            'logo_url' => 'https://www.adobe.com/content/dam/cc/icons/Adobe_Corporate_Horizontal_Red_HEX.svg',
            'status' => 'active',
            'cancellation_url' => 'https://adobe.com/account',
            'notes' => 'All apps plan'
        ],
        [
            'name' => 'Microsoft 365',
            'description' => 'Office productivity suite',
            'cost' => 99.99,
            'currency' => 'EUR',
            'billing_cycle' => 'yearly',
            'category' => 'Business',
            'website_url' => 'https://microsoft.com',
            'logo_url' => 'https://img-prod-cms-rt-microsoft-com.akamaized.net/cms/api/am/imageFileData/RE1Mu3b',
            'status' => 'active',
            'cancellation_url' => 'https://account.microsoft.com',
            'notes' => 'Personal plan with 1TB OneDrive'
        ],
        [
            'name' => 'Amazon Prime',
            'description' => 'Shopping and streaming benefits',
            'cost' => 69.00,
            'currency' => 'EUR',
            'billing_cycle' => 'yearly',
            'category' => 'Shopping',
            'website_url' => 'https://amazon.com',
            'logo_url' => 'https://m.media-amazon.com/images/G/01/gc/designs/livepreview/amazon_dkblue_noto_email_v2016_us-main._CB468775337_.png',
            'status' => 'active',
            'cancellation_url' => 'https://amazon.com/prime',
            'notes' => 'Free shipping and Prime Video'
        ],
        [
            'name' => 'Gym Membership',
            'description' => 'Local fitness center membership',
            'cost' => 29.99,
            'currency' => 'EUR',
            'billing_cycle' => 'monthly',
            'category' => 'Fitness',
            'website_url' => 'https://basicfit.com',
            'logo_url' => 'https://www.basic-fit.com/on/demandware.static/-/Sites/default/dw8c0d1c9b/images/basic-fit-logo.svg',
            'status' => 'active',
            'cancellation_url' => 'https://basicfit.com/cancel',
            'notes' => 'Basic membership with access to all locations'
        ],
        [
            'name' => 'YouTube Premium',
            'description' => 'Ad-free video streaming',
            'cost' => 11.99,
            'currency' => 'EUR',
            'billing_cycle' => 'monthly',
            'category' => 'Entertainment',
            'website_url' => 'https://youtube.com',
            'logo_url' => 'https://www.youtube.com/img/desktop/yt_1200.png',
            'status' => 'active',
            'cancellation_url' => 'https://youtube.com/premium',
            'notes' => 'Individual plan with YouTube Music'
        ],
        [
            'name' => 'Dropbox Plus',
            'description' => 'Cloud storage service',
            'cost' => 119.88,
            'currency' => 'EUR',
            'billing_cycle' => 'yearly',
            'category' => 'Business',
            'website_url' => 'https://dropbox.com',
            'logo_url' => 'https://cfl.dropboxstatic.com/static/images/logo_catalog/dropbox_logo_glyph_blue_m1@2x-vflJ5vbsw.png',
            'status' => 'active',
            'cancellation_url' => 'https://dropbox.com/account',
            'notes' => '2TB storage with advanced features'
        ],
        [
            'name' => 'Disney+',
            'description' => 'Family entertainment streaming',
            'cost' => 8.99,
            'currency' => 'EUR',
            'billing_cycle' => 'monthly',
            'category' => 'Entertainment',
            'website_url' => 'https://disneyplus.com',
            'logo_url' => 'https://cnbl-cdn.bamgrid.com/assets/7ecc8bcb60ad77193058d63e321bd21cbac2fc67281dbd9f4cdd4bd49d4ade52/original',
            'status' => 'active',
            'cancellation_url' => 'https://disneyplus.com/account',
            'notes' => 'Standard plan'
        ],
        [
            'name' => 'Canva Pro',
            'description' => 'Design and graphics tool',
            'cost' => 109.99,
            'currency' => 'EUR',
            'billing_cycle' => 'yearly',
            'category' => 'Software',
            'website_url' => 'https://canva.com',
            'logo_url' => 'https://static.canva.com/static/images/canva_logo_white-d0e7ff5ae6.svg',
            'status' => 'active',
            'cancellation_url' => 'https://canva.com/account',
            'notes' => 'Pro plan with premium features'
        ],
        [
            'name' => 'The New York Times',
            'description' => 'Digital news subscription',
            'cost' => 4.25,
            'currency' => 'EUR',
            'billing_cycle' => 'weekly',
            'category' => 'News',
            'website_url' => 'https://nytimes.com',
            'logo_url' => 'https://static01.nyt.com/images/misc/nytlogo379x64.gif',
            'status' => 'active',
            'cancellation_url' => 'https://nytimes.com/subscription',
            'notes' => 'Digital subscription'
        ],
        [
            'name' => 'HelloFresh',
            'description' => 'Meal kit delivery service',
            'cost' => 59.94,
            'currency' => 'EUR',
            'billing_cycle' => 'weekly',
            'category' => 'Food',
            'website_url' => 'https://hellofresh.com',
            'logo_url' => 'https://img.hellofresh.com/f_auto,fl_lossy,q_auto,w_640/hellofresh_s3/image/HF_Y21_Refresh_Logo_Horizontal_Color.png',
            'status' => 'paused',
            'cancellation_url' => 'https://hellofresh.com/account',
            'notes' => '3 meals for 2 people per week'
        ]
    ];
    
    // Show current subscription count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM subscriptions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $currentCount = $stmt->fetch()['count'];
    
    echo "<h2>📊 Current Status</h2>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>Current subscriptions in database:</strong> {$currentCount}";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24;'>";
    echo "<strong>Database Connection Error:</strong> " . $e->getMessage();
    echo "</div>";
}

// Injection Actions
echo "<h2>🚀 Injection Options</h2>";

// Option 1: Inject realistic subscriptions
echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Option 1: Inject Realistic Subscriptions</h3>";
echo "<p>Adds 12 realistic subscriptions with proper costs, billing cycles, and company information.</p>";
echo "<ul>";
echo "<li><strong>Entertainment:</strong> Netflix, Disney+, YouTube Premium</li>";
echo "<li><strong>Software:</strong> Adobe Creative Cloud, Canva Pro</li>";
echo "<li><strong>Business:</strong> Microsoft 365, Dropbox Plus</li>";
echo "<li><strong>Music:</strong> Spotify Premium</li>";
echo "<li><strong>Fitness:</strong> Gym Membership</li>";
echo "<li><strong>Food:</strong> HelloFresh (paused)</li>";
echo "<li><strong>News:</strong> The New York Times</li>";
echo "<li><strong>Shopping:</strong> Amazon Prime</li>";
echo "</ul>";
echo "<form method='POST' style='display: inline;'>";
echo "<input type='hidden' name='action' value='inject_realistic'>";
echo "<button type='submit' style='background: #28a745; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;'>Inject Realistic Subscriptions</button>";
echo "</form>";
echo "</div>";

// Option 2: Create full scan simulation
echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Option 2: Full Bank Scan Simulation</h3>";
echo "<p>Creates a complete bank scan record with subscriptions found during the scan.</p>";
echo "<ul>";
echo "<li>Creates bank scan record with 'completed' status</li>";
echo "<li>Adds subscriptions as if found during bank analysis</li>";
echo "<li>Sets realistic scan metadata and totals</li>";
echo "<li>Simulates complete bank integration workflow</li>";
echo "</ul>";
echo "<form method='POST' style='display: inline;'>";
echo "<input type='hidden' name='action' value='simulate_scan'>";
echo "<button type='submit' style='background: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;'>Simulate Full Bank Scan</button>";
echo "</form>";
echo "</div>";

// Option 3: Clear test data
echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Option 3: Clear Test Data</h3>";
echo "<p>Remove all test subscriptions and scan data for this user.</p>";
echo "<form method='POST' style='display: inline;'>";
echo "<input type='hidden' name='action' value='clear_data'>";
echo "<button type='submit' style='background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;' onclick='return confirm(\"Are you sure you want to clear all test data?\")'>Clear Test Data</button>";
echo "</form>";
echo "</div>";

// Handle form submissions
if ($_POST && isset($_POST['action'])) {
    try {
        $pdo = getDBConnection();
        
        switch ($_POST['action']) {
            case 'inject_realistic':
                echo "<h2>🔄 Injecting Realistic Subscriptions</h2>";
                
                $injectedCount = 0;
                foreach ($testSubscriptions as $sub) {
                    // Calculate next payment date based on billing cycle
                    $nextPayment = null;
                    switch ($sub['billing_cycle']) {
                        case 'monthly':
                            $nextPayment = date('Y-m-d', strtotime('+1 month'));
                            break;
                        case 'yearly':
                            $nextPayment = date('Y-m-d', strtotime('+1 year'));
                            break;
                        case 'weekly':
                            $nextPayment = date('Y-m-d', strtotime('+1 week'));
                            break;
                        case 'daily':
                            $nextPayment = date('Y-m-d', strtotime('+1 day'));
                            break;
                    }
                    
                    $sql = "INSERT INTO subscriptions (
                        user_id, name, description, cost, currency, billing_cycle, 
                        next_payment_date, category, website_url, logo_url, 
                        status, cancellation_url, notes, created_at
                    ) VALUES (
                        :user_id, :name, :description, :cost, :currency, :billing_cycle,
                        :next_payment_date, :category, :website_url, :logo_url,
                        :status, :cancellation_url, :notes, NOW()
                    )";
                    
                    $stmt = $pdo->prepare($sql);
                    $result = $stmt->execute([
                        'user_id' => $userId,
                        'name' => $sub['name'],
                        'description' => $sub['description'],
                        'cost' => $sub['cost'],
                        'currency' => $sub['currency'],
                        'billing_cycle' => $sub['billing_cycle'],
                        'next_payment_date' => $nextPayment,
                        'category' => $sub['category'],
                        'website_url' => $sub['website_url'],
                        'logo_url' => $sub['logo_url'],
                        'status' => $sub['status'],
                        'cancellation_url' => $sub['cancellation_url'],
                        'notes' => $sub['notes']
                    ]);
                    
                    if ($result) {
                        $injectedCount++;
                        echo "<span style='color: green;'>✅ Added: {$sub['name']} (€{$sub['cost']}/{$sub['billing_cycle']})</span><br>";
                    }
                }
                
                echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 20px 0;'>";
                echo "<strong>✅ Successfully injected {$injectedCount} realistic subscriptions!</strong>";
                echo "</div>";
                break;
                
            case 'simulate_scan':
                echo "<h2>🔄 Simulating Full Bank Scan</h2>";
                
                // First inject subscriptions
                $injectedCount = 0;
                $totalMonthlyCost = 0;
                
                foreach ($testSubscriptions as $sub) {
                    // Calculate next payment date
                    $nextPayment = null;
                    switch ($sub['billing_cycle']) {
                        case 'monthly':
                            $nextPayment = date('Y-m-d', strtotime('+1 month'));
                            $monthlyCost = $sub['cost'];
                            break;
                        case 'yearly':
                            $nextPayment = date('Y-m-d', strtotime('+1 year'));
                            $monthlyCost = $sub['cost'] / 12;
                            break;
                        case 'weekly':
                            $nextPayment = date('Y-m-d', strtotime('+1 week'));
                            $monthlyCost = $sub['cost'] * 4.33;
                            break;
                        case 'daily':
                            $nextPayment = date('Y-m-d', strtotime('+1 day'));
                            $monthlyCost = $sub['cost'] * 30;
                            break;
                    }
                    
                    $totalMonthlyCost += $monthlyCost;
                    
                    $sql = "INSERT INTO subscriptions (
                        user_id, name, description, cost, currency, billing_cycle, 
                        next_payment_date, category, website_url, logo_url, 
                        status, cancellation_url, notes, created_at
                    ) VALUES (
                        :user_id, :name, :description, :cost, :currency, :billing_cycle,
                        :next_payment_date, :category, :website_url, :logo_url,
                        :status, :cancellation_url, :notes, NOW()
                    )";
                    
                    $stmt = $pdo->prepare($sql);
                    $result = $stmt->execute([
                        'user_id' => $userId,
                        'name' => $sub['name'],
                        'description' => $sub['description'],
                        'cost' => $sub['cost'],
                        'currency' => $sub['currency'],
                        'billing_cycle' => $sub['billing_cycle'],
                        'next_payment_date' => $nextPayment,
                        'category' => $sub['category'],
                        'website_url' => $sub['website_url'],
                        'logo_url' => $sub['logo_url'],
                        'status' => $sub['status'],
                        'cancellation_url' => $sub['cancellation_url'],
                        'notes' => $sub['notes']
                    ]);
                    
                    if ($result) {
                        $injectedCount++;
                        echo "<span style='color: green;'>✅ Found: {$sub['name']} (€{$sub['cost']}/{$sub['billing_cycle']})</span><br>";
                    }
                }
                
                // Create bank scan record
                $scanData = [
                    'scan_method' => 'mock_injection',
                    'subscriptions_detected' => $injectedCount,
                    'total_monthly_cost' => round($totalMonthlyCost, 2),
                    'categories_found' => ['Entertainment', 'Software', 'Business', 'Music', 'Fitness', 'Food', 'News', 'Shopping'],
                    'scan_summary' => 'Mock bank scan simulation with realistic subscription data'
                ];
                
                $sql = "INSERT INTO bank_scans (
                    user_id, plan_type, status, started_at, completed_at, 
                    subscriptions_found, total_monthly_cost, scan_data, updated_at
                ) VALUES (
                    :user_id, :plan_type, 'completed', NOW(), NOW(),
                    :subscriptions_found, :total_monthly_cost, :scan_data, NOW()
                )";
                
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([
                    'user_id' => $userId,
                    'plan_type' => 'mock_test',
                    'subscriptions_found' => $injectedCount,
                    'total_monthly_cost' => round($totalMonthlyCost, 2),
                    'scan_data' => json_encode($scanData)
                ]);
                
                if ($result) {
                    $scanId = $pdo->lastInsertId();
                    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 20px 0;'>";
                    echo "<strong>✅ Full Bank Scan Simulation Complete!</strong><br>";
                    echo "• Scan ID: {$scanId}<br>";
                    echo "• Subscriptions Found: {$injectedCount}<br>";
                    echo "• Total Monthly Cost: €" . round($totalMonthlyCost, 2) . "<br>";
                    echo "• Scan Status: Completed";
                    echo "</div>";
                }
                break;
                
            case 'clear_data':
                echo "<h2>🔄 Clearing Test Data</h2>";
                
                // Clear subscriptions
                $stmt = $pdo->prepare("DELETE FROM subscriptions WHERE user_id = ?");
                $result1 = $stmt->execute([$userId]);
                $deletedSubs = $stmt->rowCount();
                
                // Clear bank scans
                $stmt = $pdo->prepare("DELETE FROM bank_scans WHERE user_id = ?");
                $result2 = $stmt->execute([$userId]);
                $deletedScans = $stmt->rowCount();
                
                echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 20px 0;'>";
                echo "<strong>🗑️ Test Data Cleared!</strong><br>";
                echo "• Deleted Subscriptions: {$deletedSubs}<br>";
                echo "• Deleted Bank Scans: {$deletedScans}";
                echo "</div>";
                break;
        }
        
        // Refresh page to show updated counts
        echo "<script>setTimeout(() => location.reload(), 3000);</script>";
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 20px 0;'>";
        echo "<strong>❌ Error:</strong> " . $e->getMessage();
        echo "</div>";
    }
}

echo "<hr>";
echo "<h2>🧪 Next Steps</h2>";
echo "<div style='display: flex; gap: 10px; flex-wrap: wrap;'>";
echo "<a href='dashboard.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View Dashboard</a>";
echo "<a href='test-bank-data-processing.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Data Processing</a>";
echo "<a href='test-complete-system.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Re-run System Test</a>";
echo "</div>";
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}
h1, h2, h3 {
    color: #333;
}
button {
    cursor: pointer;
    transition: opacity 0.2s;
}
button:hover {
    opacity: 0.9;
}
</style>
