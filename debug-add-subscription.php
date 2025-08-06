<?php
session_start();
require_once 'config/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$userId = $_SESSION['user_id'];

echo "<h2>Debug: Add Subscription Issue</h2>";

try {
    $pdo = getDBConnection();
    echo "✅ Database connection successful<br>";
    
    // Check if subscriptions table exists and get its structure
    $stmt = $pdo->query("DESCRIBE subscriptions");
    $columns = $stmt->fetchAll();
    
    echo "<h3>Subscriptions table structure:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test the exact query that's failing
    echo "<h3>Testing the INSERT query:</h3>";
    
    // Simulate the data that would be sent
    $testData = [
        'name' => 'Test Subscription',
        'cost' => 9.99,
        'billing_cycle' => 'monthly',
        'category' => 'Entertainment',
        'next_payment_date' => date('Y-m-d', strtotime('+1 month'))
    ];
    
    echo "Test data: " . print_r($testData, true) . "<br>";
    
    // Try the exact query from dashboard.php
    $stmt = $pdo->prepare("INSERT INTO subscriptions (user_id, name, cost, billing_cycle, category, next_payment_date, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())");
    
    echo "Query prepared successfully<br>";
    
    // Show what parameters we're trying to execute
    $params = [
        $userId,
        $testData['name'],
        floatval($testData['cost']),
        $testData['billing_cycle'],
        $testData['category'],
        $testData['next_payment_date']
    ];
    
    echo "Parameters: " . print_r($params, true) . "<br>";
    
    // Try to execute
    $result = $stmt->execute($params);
    
    if ($result) {
        echo "✅ Test INSERT successful! Last insert ID: " . $pdo->lastInsertId() . "<br>";
        
        // Clean up test data
        $deleteStmt = $pdo->prepare("DELETE FROM subscriptions WHERE id = ?");
        $deleteStmt->execute([$pdo->lastInsertId()]);
        echo "✅ Test data cleaned up<br>";
    } else {
        echo "❌ Test INSERT failed<br>";
        echo "Error info: " . print_r($stmt->errorInfo(), true) . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Error details: " . print_r($e, true) . "<br>";
}

echo "<h3>Current session data:</h3>";
echo "User ID: " . $userId . "<br>";
echo "User Name: " . ($_SESSION['user_name'] ?? 'Not set') . "<br>";
echo "User Email: " . ($_SESSION['user_email'] ?? 'Not set') . "<br>";
echo "Is Paid: " . ($_SESSION['is_paid'] ? 'Yes' : 'No') . "<br>";
?>
