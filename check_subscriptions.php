<?php
require_once 'config/db_config.php';

// Get database connection
$pdo = getDBConnection();

// Get all subscriptions
$stmt = $pdo->query("SELECT * FROM subscriptions ORDER BY next_billing_date DESC LIMIT 50");
$subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get count of subscriptions
$countStmt = $pdo->query("SELECT COUNT(*) as count FROM subscriptions");
$count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];

echo "<h1>Subscriptions in Database ($count total)</h1>";

if (empty($subscriptions)) {
    echo "<p>No subscriptions found in the database.</p>";
} else {
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Name</th><th>Amount</th><th>Billing Cycle</th><th>Next Billing</th><th>Status</th><th>Source</th></tr>";
    
    foreach ($subscriptions as $sub) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($sub['id']) . "</td>";
        echo "<td>" . htmlspecialchars($sub['user_id']) . "</td>";
        echo "<td>" . htmlspecialchars($sub['name']) . "</td>";
        echo "<td>" . htmlspecialchars($sub['amount'] . ' ' . $sub['currency']) . "</td>";
        echo "<td>" . htmlspecialchars($sub['billing_cycle']) . "</td>";
        echo "<td>" . htmlspecialchars($sub['next_billing_date']) . "</td>";
        echo "<td>" . htmlspecialchars($sub['status']) . "</td>";
        echo "<td>" . htmlspecialchars($sub['source'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Add some basic CSS
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; width: 100%; margin-top: 20px; }
    th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background-color: #f2f2f2; position: sticky; top: 0; }
    tr:hover { background-color: #f5f5f5; }
    .warning { color: #d32f2f; font-weight: bold; }
</style>";

// Check for potential issues
if ($count > 0 && $count != 36) {
    echo "<p class='warning'>Warning: There are $count subscriptions in the database but the last scan found 36. There might be a filtering issue.</p>";
}

// Add a button to force a rescan
echo "<div style='margin-top: 20px;'>";
echo "<form method='post' action='bank/scan.php'>";
echo "<input type='hidden' name='action' value='scan'>";
echo "<button type='submit' style='padding: 10px 15px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;'>Trigger New Scan</button>";
echo "</form>";
echo "</div>";
