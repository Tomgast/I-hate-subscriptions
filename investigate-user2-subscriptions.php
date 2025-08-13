<?php
/**
 * Investigate why User 2's subscriptions are not showing
 */

require_once __DIR__ . '/config/db_config.php';

echo "=== Investigating User 2 Subscription Issue ===\n\n";

$pdo = getDBConnection();

// Get User 2 details
echo "--- User 2 Information ---\n";
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = 2 OR email = 'support@origens.nl'");
$stmt->execute();
$user2 = $stmt->fetch();

if ($user2) {
    echo "User ID: {$user2['id']}\n";
    echo "Email: {$user2['email']}\n";
    echo "Name: " . ($user2['name'] ?? 'Not set') . "\n";
    echo "Pro Status: " . ($user2['is_pro'] ? 'Yes' : 'No') . "\n";
    echo "Created: {$user2['created_at']}\n";
} else {
    echo "User 2 not found!\n";
    exit(1);
}

$userId = $user2['id'];

// Check bank connections
echo "\n--- Bank Connections for User $userId ---\n";
$stmt = $pdo->prepare("SELECT * FROM bank_connections WHERE user_id = ?");
$stmt->execute([$userId]);
$connections = $stmt->fetchAll();

if (empty($connections)) {
    echo "No bank connections found for user $userId\n";
} else {
    foreach ($connections as $conn) {
        echo "Connection ID: {$conn['id']}\n";
        echo "Provider: {$conn['provider']}\n";
        echo "Status: {$conn['status']}\n";
        echo "Created: {$conn['created_at']}\n";
        echo "---\n";
    }
}

// Check bank scans (not bank_scan_data)
echo "\n--- Bank Scans for User $userId ---\n";
$stmt = $pdo->prepare("SELECT * FROM bank_scans WHERE user_id = ?");
$stmt->execute([$userId]);
$scans = $stmt->fetchAll();

if (empty($scans)) {
    echo "No bank scans found for user $userId\n";
} else {
    foreach ($scans as $scan) {
        echo "Scan ID: {$scan['id']}\n";
        echo "Status: {$scan['status']}\n";
        echo "Provider: " . ($scan['provider'] ?? 'Not set') . "\n";
        echo "Created: {$scan['created_at']}\n";
        echo "Updated: {$scan['updated_at']}\n";
        echo "---\n";
    }
}

// Check raw transactions
echo "\n--- Raw Transactions for User $userId ---\n";
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM raw_transactions WHERE user_id = ?");
$stmt->execute([$userId]);
$rawCount = $stmt->fetch();
echo "Total raw transactions: {$rawCount['count']}\n";

if ($rawCount['count'] > 0) {
    $stmt = $pdo->prepare("SELECT * FROM raw_transactions WHERE user_id = ? ORDER BY transaction_date DESC LIMIT 5");
    $stmt->execute([$userId]);
    $rawTrans = $stmt->fetchAll();
    
    echo "Recent raw transactions:\n";
    foreach ($rawTrans as $trans) {
        echo "- {$trans['transaction_date']}: {$trans['description']} ({$trans['amount']})\n";
    }
}

// Check bank transactions
echo "\n--- Bank Transactions for User $userId ---\n";
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM bank_transactions WHERE user_id = ?");
$stmt->execute([$userId]);
$bankCount = $stmt->fetch();
echo "Total bank transactions: {$bankCount['count']}\n";

if ($bankCount['count'] > 0) {
    $stmt = $pdo->prepare("SELECT * FROM bank_transactions WHERE user_id = ? ORDER BY transaction_date DESC LIMIT 5");
    $stmt->execute([$userId]);
    $bankTrans = $stmt->fetchAll();
    
    echo "Recent bank transactions:\n";
    foreach ($bankTrans as $trans) {
        echo "- {$trans['transaction_date']}: {$trans['description']} ({$trans['amount']})\n";
    }
}

// Check subscriptions
echo "\n--- Subscriptions for User $userId ---\n";
$stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ?");
$stmt->execute([$userId]);
$subscriptions = $stmt->fetchAll();

if (empty($subscriptions)) {
    echo "No subscriptions found for user $userId\n";
} else {
    foreach ($subscriptions as $sub) {
        echo "Subscription: {$sub['name']}\n";
        echo "Amount: {$sub['amount']}\n";
        echo "Frequency: {$sub['frequency']}\n";
        echo "Status: {$sub['status']}\n";
        echo "---\n";
    }
}

echo "\n=== Investigation Complete ===\n";
?>
