<?php
/**
 * BANK DATA PROCESSING TEST TOOL
 * Test subscription detection and data processing with TrueLayer sandbox data
 */

session_start();
require_once 'config/db_config.php';
require_once 'includes/bank_service.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Please log in first');
}

$userId = $_SESSION['user_id'];
$userEmail = $_SESSION['user_email'] ?? '';

echo "<h1>Bank Data Processing Test Tool</h1>";
echo "<p><strong>User ID:</strong> {$userId} | <strong>Email:</strong> {$userEmail}</p>";

$bankService = new BankService();

// Test 1: Check Recent Bank Scans
echo "<h2>📊 Recent Bank Scans</h2>";

try {
    $pdo = getDBConnection();
    
    // Get recent bank scans for this user
    $stmt = $pdo->prepare("
        SELECT * FROM bank_scans 
        WHERE user_id = ? 
        ORDER BY started_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $recentScans = $stmt->fetchAll();
    
    if (empty($recentScans)) {
        echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; color: #856404;'>";
        echo "⚠️ No bank scans found. Complete a bank scan first to test data processing.";
        echo "</div>";
    } else {
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
        echo "<strong>Found " . count($recentScans) . " recent scan(s):</strong><br><br>";
        
        foreach ($recentScans as $scan) {
            $statusColor = [
                'completed' => 'green',
                'in_progress' => 'blue',
                'failed' => 'red',
                'initiated' => 'orange'
            ][$scan['status']] ?? 'gray';
            
            echo "<div style='background: white; padding: 10px; border-radius: 5px; margin: 5px 0; border-left: 4px solid {$statusColor};'>";
            echo "<strong>Scan ID:</strong> {$scan['id']}<br>";
            echo "<strong>Status:</strong> <span style='color: {$statusColor};'>" . ucfirst($scan['status']) . "</span><br>";
            echo "<strong>Started:</strong> {$scan['started_at']}<br>";
            if ($scan['completed_at']) {
                echo "<strong>Completed:</strong> {$scan['completed_at']}<br>";
            }
            echo "<strong>Subscriptions Found:</strong> {$scan['subscriptions_found']}<br>";
            echo "<strong>Monthly Cost:</strong> €" . number_format($scan['total_monthly_cost'], 2) . "<br>";
            
            if ($scan['scan_data']) {
                echo "<details style='margin-top: 10px;'>";
                echo "<summary style='cursor: pointer; color: #007bff;'>View Raw Scan Data</summary>";
                echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; font-size: 12px; margin-top: 10px;'>";
                echo json_encode(json_decode($scan['scan_data']), JSON_PRETTY_PRINT);
                echo "</pre>";
                echo "</details>";
            }
            
            if ($scan['error_message']) {
                echo "<div style='color: red; margin-top: 5px;'><strong>Error:</strong> {$scan['error_message']}</div>";
            }
            echo "</div>";
        }
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24;'>";
    echo "<strong>Database Error:</strong> " . $e->getMessage();
    echo "</div>";
}

// Test 2: Check Bank Connections
echo "<h2>🔗 Bank Connections</h2>";

try {
    $stmt = $pdo->prepare("
        SELECT * FROM bank_connections 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 3
    ");
    $stmt->execute([$userId]);
    $connections = $stmt->fetchAll();
    
    if (empty($connections)) {
        echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; color: #856404;'>";
        echo "⚠️ No bank connections found.";
        echo "</div>";
    } else {
        echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px;'>";
        echo "<strong>Found " . count($connections) . " bank connection(s):</strong><br><br>";
        
        foreach ($connections as $conn) {
            $statusColor = $conn['is_active'] ? 'green' : 'red';
            
            echo "<div style='background: white; padding: 10px; border-radius: 5px; margin: 5px 0; border-left: 4px solid {$statusColor};'>";
            echo "<strong>Connection ID:</strong> {$conn['id']}<br>";
            echo "<strong>Status:</strong> <span style='color: {$statusColor};'>" . ($conn['is_active'] ? 'Active' : 'Inactive') . "</span><br>";
            echo "<strong>Bank Name:</strong> " . ($conn['bank_name'] ?: 'Not specified') . "<br>";
            echo "<strong>Created:</strong> {$conn['created_at']}<br>";
            echo "<strong>Expires:</strong> " . ($conn['expires_at'] ?: 'Not set') . "<br>";
            
            if ($conn['account_data']) {
                echo "<details style='margin-top: 10px;'>";
                echo "<summary style='cursor: pointer; color: #007bff;'>View Account Data</summary>";
                echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; font-size: 12px; margin-top: 10px;'>";
                echo json_encode(json_decode($conn['account_data']), JSON_PRETTY_PRINT);
                echo "</pre>";
                echo "</details>";
            }
            echo "</div>";
        }
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24;'>";
    echo "<strong>Database Error:</strong> " . $e->getMessage();
    echo "</div>";
}

// Test 3: Check Detected Subscriptions
echo "<h2>📋 Detected Subscriptions</h2>";

try {
    $stmt = $pdo->prepare("
        SELECT * FROM subscriptions 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $subscriptions = $stmt->fetchAll();
    
    if (empty($subscriptions)) {
        echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; color: #856404;'>";
        echo "⚠️ No subscriptions detected yet. This could mean:<br>";
        echo "• The bank scan is still processing<br>";
        echo "• No recurring transactions were found in the sandbox data<br>";
        echo "• The subscription detection algorithm needs tuning<br>";
        echo "</div>";
    } else {
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
        echo "<strong>Found " . count($subscriptions) . " detected subscription(s):</strong><br><br>";
        
        $totalMonthlyCost = 0;
        
        foreach ($subscriptions as $sub) {
            $monthlyCost = $sub['billing_cycle'] === 'yearly' ? $sub['cost'] / 12 : $sub['cost'];
            $totalMonthlyCost += $monthlyCost;
            
            echo "<div style='background: white; padding: 10px; border-radius: 5px; margin: 5px 0; border-left: 4px solid #28a745;'>";
            echo "<strong>Name:</strong> " . htmlspecialchars($sub['name']) . "<br>";
            echo "<strong>Cost:</strong> €" . number_format($sub['cost'], 2) . " (" . $sub['billing_cycle'] . ")<br>";
            echo "<strong>Monthly Equivalent:</strong> €" . number_format($monthlyCost, 2) . "<br>";
            echo "<strong>Next Payment:</strong> " . ($sub['next_payment_date'] ?: 'Not set') . "<br>";
            echo "<strong>Category:</strong> " . ($sub['category'] ?: 'Uncategorized') . "<br>";
            echo "<strong>Status:</strong> " . ucfirst($sub['status']) . "<br>";
            echo "<strong>Added:</strong> {$sub['created_at']}<br>";
            echo "</div>";
        }
        
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin-top: 15px;'>";
        echo "<strong>📊 Summary:</strong><br>";
        echo "Total Subscriptions: " . count($subscriptions) . "<br>";
        echo "Total Monthly Cost: €" . number_format($totalMonthlyCost, 2) . "<br>";
        echo "Total Yearly Cost: €" . number_format($totalMonthlyCost * 12, 2);
        echo "</div>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24;'>";
    echo "<strong>Database Error:</strong> " . $e->getMessage();
    echo "</div>";
}

// Test 4: Test Subscription Detection Algorithm
echo "<h2>🔍 Test Subscription Detection Algorithm</h2>";

echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px;'>";
echo "<h3>Sample Transaction Analysis</h3>";
echo "<p>Testing the subscription detection algorithm with sample TrueLayer sandbox transactions:</p>";

// Sample transactions that should be detected as subscriptions
$sampleTransactions = [
    [
        'transaction_id' => 'test_1',
        'amount' => -9.99,
        'currency' => 'EUR',
        'description' => 'NETFLIX.COM',
        'timestamp' => date('Y-m-d', strtotime('-30 days')),
        'merchant_name' => 'Netflix'
    ],
    [
        'transaction_id' => 'test_2',
        'amount' => -9.99,
        'currency' => 'EUR',
        'description' => 'NETFLIX.COM',
        'timestamp' => date('Y-m-d', strtotime('-60 days')),
        'merchant_name' => 'Netflix'
    ],
    [
        'transaction_id' => 'test_3',
        'amount' => -4.99,
        'currency' => 'EUR',
        'description' => 'SPOTIFY PREMIUM',
        'timestamp' => date('Y-m-d', strtotime('-28 days')),
        'merchant_name' => 'Spotify'
    ],
    [
        'transaction_id' => 'test_4',
        'amount' => -4.99,
        'currency' => 'EUR',
        'description' => 'SPOTIFY PREMIUM',
        'timestamp' => date('Y-m-d', strtotime('-56 days')),
        'merchant_name' => 'Spotify'
    ]
];

echo "<div style='background: white; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
echo "<strong>Sample Transactions:</strong><br>";
foreach ($sampleTransactions as $trans) {
    echo "• {$trans['description']} - €" . number_format(abs($trans['amount']), 2) . " on {$trans['timestamp']}<br>";
}
echo "</div>";

// Test if detectSubscriptions method exists
if (method_exists($bankService, 'detectSubscriptions')) {
    try {
        $detectedSubs = $bankService->detectSubscriptions($sampleTransactions);
        
        if (empty($detectedSubs)) {
            echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; color: #856404;'>";
            echo "⚠️ No subscriptions detected from sample transactions. The detection algorithm may need adjustment.";
            echo "</div>";
        } else {
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724;'>";
            echo "<strong>✅ Detected " . count($detectedSubs) . " subscription(s):</strong><br><br>";
            
            foreach ($detectedSubs as $sub) {
                echo "<div style='background: #f8f9fa; padding: 8px; border-radius: 3px; margin: 5px 0;'>";
                echo "<strong>Name:</strong> " . htmlspecialchars($sub['name']) . "<br>";
                echo "<strong>Amount:</strong> €" . number_format($sub['amount'], 2) . "<br>";
                echo "<strong>Billing Cycle:</strong> " . $sub['billing_cycle'] . "<br>";
                echo "<strong>Confidence:</strong> " . $sub['confidence'] . "%<br>";
                echo "</div>";
            }
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24;'>";
        echo "<strong>Detection Algorithm Error:</strong> " . $e->getMessage();
        echo "</div>";
    }
} else {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; color: #856404;'>";
    echo "⚠️ detectSubscriptions method not found in BankService. This method needs to be implemented.";
    echo "</div>";
}

echo "</div>";

// Test 5: Export Functionality Test
echo "<h2>📄 Export Functionality Test</h2>";

if (!empty($recentScans)) {
    $latestScan = $recentScans[0];
    
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
    echo "<strong>Test Export with Latest Scan (ID: {$latestScan['id']}):</strong><br><br>";
    
    echo "<div style='display: flex; gap: 10px; margin: 10px 0;'>";
    echo "<a href='export/pdf.php?scan_id={$latestScan['id']}' target='_blank' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test PDF Export</a>";
    echo "<a href='export/csv.php?scan_id={$latestScan['id']}' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test CSV Export</a>";
    echo "</div>";
    
    echo "<p style='color: #666; font-size: 14px;'>Click the buttons above to test if the export functionality works with your detected data.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; color: #856404;'>";
    echo "⚠️ No scan data available for export testing. Complete a bank scan first.";
    echo "</div>";
}

echo "<hr>";
echo "<h2>🧪 Quick Actions</h2>";
echo "<div style='display: flex; gap: 10px; flex-wrap: wrap;'>";
echo "<a href='bank/scan.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Run New Bank Scan</a>";
echo "<a href='dashboard.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Back to Dashboard</a>";
echo "<a href='debug-truelayer.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>TrueLayer Debug</a>";
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
details summary {
    cursor: pointer;
    user-select: none;
}
details[open] summary {
    margin-bottom: 10px;
}
</style>
