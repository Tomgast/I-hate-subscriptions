<?php
require_once 'config/db_config.php';
require_once 'includes/gocardless_financial_service.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get database connection
$pdo = getDBConnection();
$gocardless = new GoCardlessFinancialService($pdo);

// Get the most recent scan
$stmt = $pdo->query("SELECT * FROM bank_scans WHERE status = 'completed' ORDER BY started_at DESC LIMIT 1");
$scan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$scan) {
    die("No completed scans found");
}

echo "<h1>Debugging Subscription Save Process</h1>";
echo "<p>Scan ID: " . htmlspecialchars($scan['id']) . "</p>";
echo "<p>User ID: " . htmlspecialchars($scan['user_id']) . "</p>";

// Simulate the subscription detection process
$subscriptions = [
    [
        'merchant_name' => 'Spotify',
        'amount' => 21.99,
        'currency' => 'EUR',
        'billing_cycle' => 'monthly',
        'last_charge_date' => '2025-08-11',
        'next_billing_date' => '2025-09-11',
        'transaction_count' => 1,
        'confidence' => 90,
        'provider' => 'gocardless'
    ],
    // Add more test subscriptions if needed
];

echo "<h2>Attempting to save test subscriptions...</h2>";

// Try to save the test subscriptions
try {
    $result = $gocardless->saveScanResults($scan['user_id'], $subscriptions);
    echo "<p>Save result: " . ($result ? 'Success' : 'Failed') . "</p>";
    
    // Check if any subscriptions were saved
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM subscriptions WHERE user_id = ?");
    $stmt->execute([$scan['user_id']]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<p>Subscriptions in database after save: " . $count . "</p>";
    
    if ($count > 0) {
        $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ?");
        $stmt->execute([$scan['user_id']]);
        $savedSubs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Saved Subscriptions:</h3>";
        echo "<pre>";
        print_r($savedSubs);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error saving subscriptions: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

// Add a button to check the database directly
echo "<div style='margin-top: 20px;'>";
echo "<a href='check_subscriptions.php' style='padding: 10px 15px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px;'>Check Subscriptions</a>";
echo "</div>";

// Add some basic CSS
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }
    .success { color: #4CAF50; }
</style>";
