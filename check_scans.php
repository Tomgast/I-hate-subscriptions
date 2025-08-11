<?php
require_once 'config/db_config.php';

// Get database connection
$pdo = getDBConnection();

// Check for any bank scans
try {
    $stmt = $pdo->query("SELECT * FROM bank_scans ORDER BY started_at DESC LIMIT 5");
    $scans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h1>Recent Bank Scans</h1>";
    if (empty($scans)) {
        echo "<p>No scan records found in the database.</p>";
    } else {
        echo "<table border='1' cellpadding='8'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Status</th><th>Started At</th><th>Completed At</th><th>Subscriptions Found</th></tr>";
        foreach ($scans as $scan) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($scan['id']) . "</td>";
            echo "<td>" . htmlspecialchars($scan['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($scan['status']) . "</td>";
            echo "<td>" . htmlspecialchars($scan['started_at']) . "</td>";
            echo "<td>" . htmlspecialchars($scan['completed_at'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($scan['subscriptions_found'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p>Error checking scans: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Add some basic CSS
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; width: 100%; margin-top: 20px; }
    th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background-color: #f2f2f2; }
    tr:hover { background-color: #f5f5f5; }
</style>";
