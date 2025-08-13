<?php
session_start();
require_once 'config/db_config.php';
require_once 'includes/plan_manager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<h1>Please login first</h1>";
    echo "<p>Go to <a href='auth/signin.php'>Sign In</a> first, then return to this page.</p>";
    exit;
}

$userId = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Debug - CashControl</title>
    <style>
        body { font-family: monospace; margin: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
        .error { color: red; }
        .success { color: green; }
        .warning { color: orange; }
    </style>
</head>
<body>
    <h1>Dashboard Debug for User ID: <?php echo $userId; ?></h1>
    
    <div class="section">
        <h2>1. Session Information</h2>
        <p><strong>User ID:</strong> <?php echo $_SESSION['user_id'] ?? 'NOT SET'; ?></p>
        <p><strong>User Email:</strong> <?php echo $_SESSION['user_email'] ?? 'NOT SET'; ?></p>
        <p><strong>User Name:</strong> <?php echo $_SESSION['user_name'] ?? 'NOT SET'; ?></p>
    </div>
    
    <div class="section">
        <h2>2. User Plan Status</h2>
        <?php
        try {
            require_once 'includes/user_plan_helper.php';
            $userPlan = UserPlanHelper::getUserPlanStatus($userId);
            echo "<p><strong>Plan Type:</strong> " . ($userPlan['plan_type'] ?? 'UNKNOWN') . "</p>";
            echo "<p><strong>Is Paid:</strong> " . ($userPlan['is_paid'] ? 'YES' : 'NO') . "</p>";
            echo "<p><strong>Status:</strong> " . ($userPlan['status'] ?? 'UNKNOWN') . "</p>";
            
            if ($userPlan['plan_type'] === 'one_time' && $userPlan['is_paid']) {
                echo "<p class='warning'>⚠️ User would be redirected to dashboard-onetime.php</p>";
            } elseif (in_array($userPlan['plan_type'], ['monthly', 'yearly']) && $userPlan['is_paid']) {
                echo "<p class='success'>✅ User has access to full dashboard</p>";
            } else {
                echo "<p class='error'>❌ User would be redirected to upgrade.php</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>ERROR: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>3. Simple Subscription Query Test</h2>
        <?php
        try {
            $pdo = getDBConnection();
            
            echo "<p><strong>Query:</strong> SELECT * FROM subscriptions WHERE user_id = $userId ORDER BY created_at DESC</p>";
            $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$userId]);
            $simpleSubscriptions = $stmt->fetchAll();
            
            echo "<p class='success'>✅ Simple query returned: " . count($simpleSubscriptions) . " subscriptions</p>";
            
            if (count($simpleSubscriptions) > 0) {
                echo "<h3>Sample subscription:</h3>";
                $first = $simpleSubscriptions[0];
                echo "<ul>";
                echo "<li><strong>ID:</strong> {$first['id']}</li>";
                echo "<li><strong>Name:</strong> " . ($first['name'] ?: 'EMPTY') . "</li>";
                echo "<li><strong>Merchant Name:</strong> " . ($first['merchant_name'] ?: 'EMPTY') . "</li>";
                echo "<li><strong>Cost:</strong> €{$first['cost']}</li>";
                echo "<li><strong>Is Active:</strong> {$first['is_active']}</li>";
                echo "<li><strong>Status:</strong> {$first['status']}</li>";
                echo "</ul>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>ERROR: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>4. Complex JOIN Query Test (Current Dashboard Query)</h2>
        <?php
        try {
            $pdo = getDBConnection();
            
            $complexQuery = "
                SELECT s.*, 
                       COALESCE(s.amount, s.cost) as display_amount,
                       COALESCE(s.status = 'active', s.is_active = 1, s.status IS NULL) as is_active_status,
                       COALESCE(s.merchant_name, s.name) as display_name,
                       s.source,
                       s.bank_reference,
                       s.last_charge_date,
                       s.confidence,
                       bc.provider as bank_provider,
                       bc.account_name as bank_account_name,
                       bc.last_sync_at as bank_last_sync
                FROM subscriptions s
                LEFT JOIN bank_connections bc ON s.bank_reference = bc.account_id AND bc.user_id = s.user_id
                WHERE s.user_id = ? 
                ORDER BY s.created_at DESC
            ";
            
            echo "<p><strong>Complex JOIN Query:</strong></p>";
            echo "<pre>" . htmlspecialchars($complexQuery) . "</pre>";
            
            $stmt = $pdo->prepare($complexQuery);
            $stmt->execute([$userId]);
            $complexSubscriptions = $stmt->fetchAll();
            
            echo "<p class='success'>✅ Complex query returned: " . count($complexSubscriptions) . " subscriptions</p>";
            
            if (count($complexSubscriptions) > 0) {
                echo "<h3>Sample subscription from complex query:</h3>";
                $first = $complexSubscriptions[0];
                echo "<ul>";
                echo "<li><strong>ID:</strong> {$first['id']}</li>";
                echo "<li><strong>Display Name:</strong> " . ($first['display_name'] ?: 'EMPTY') . "</li>";
                echo "<li><strong>Display Amount:</strong> €{$first['display_amount']}</li>";
                echo "<li><strong>Is Active Status:</strong> {$first['is_active_status']}</li>";
                echo "<li><strong>Bank Provider:</strong> " . ($first['bank_provider'] ?: 'NULL') . "</li>";
                echo "<li><strong>Bank Account Name:</strong> " . ($first['bank_account_name'] ?: 'NULL') . "</li>";
                echo "</ul>";
            }
            
            if (count($simpleSubscriptions) != count($complexSubscriptions)) {
                echo "<p class='error'>⚠️ WARNING: Simple query returned " . count($simpleSubscriptions) . " but complex query returned " . count($complexSubscriptions) . " subscriptions!</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>ERROR: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>5. Bank Connections Check</h2>
        <?php
        try {
            $stmt = $pdo->prepare("SELECT * FROM bank_connections WHERE user_id = ?");
            $stmt->execute([$userId]);
            $bankConnections = $stmt->fetchAll();
            
            echo "<p>Found " . count($bankConnections) . " bank connections for this user:</p>";
            foreach ($bankConnections as $conn) {
                echo "<ul>";
                echo "<li><strong>ID:</strong> {$conn['id']}</li>";
                echo "<li><strong>Provider:</strong> {$conn['provider']}</li>";
                echo "<li><strong>Account ID:</strong> " . ($conn['account_id'] ?: 'NULL') . "</li>";
                echo "<li><strong>Account Name:</strong> " . ($conn['account_name'] ?: 'NULL') . "</li>";
                echo "<li><strong>Status:</strong> {$conn['status']}</li>";
                echo "</ul>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>ERROR: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>6. Stats Calculation Test</h2>
        <?php
        if (isset($simpleSubscriptions) && count($simpleSubscriptions) > 0) {
            $stats = [
                'total_active' => 0,
                'monthly_total' => 0,
                'yearly_total' => 0
            ];
            
            foreach ($simpleSubscriptions as $subscription) {
                $isActive = (bool)($subscription['is_active'] ?? ($subscription['status'] === 'active'));
                
                if ($isActive) {
                    $stats['total_active']++;
                    $amount = (float)($subscription['cost'] ?? $subscription['amount'] ?? 0);
                    $stats['monthly_total'] += $amount; // Assuming monthly for simplicity
                    $stats['yearly_total'] += $amount * 12;
                }
            }
            
            echo "<p class='success'>✅ Stats calculated successfully:</p>";
            echo "<ul>";
            echo "<li><strong>Total Active:</strong> {$stats['total_active']}</li>";
            echo "<li><strong>Monthly Total:</strong> €" . number_format($stats['monthly_total'], 2) . "</li>";
            echo "<li><strong>Yearly Total:</strong> €" . number_format($stats['yearly_total'], 2) . "</li>";
            echo "</ul>";
        } else {
            echo "<p class='error'>No subscriptions to calculate stats for</p>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>7. Current Dashboard.php Query Analysis</h2>
        <p>The current dashboard.php is using a complex JOIN query that might be causing issues. Based on the tests above:</p>
        <?php
        if (isset($simpleSubscriptions) && isset($complexSubscriptions)) {
            if (count($simpleSubscriptions) == count($complexSubscriptions)) {
                echo "<p class='success'>✅ Both queries return the same number of results</p>";
            } else {
                echo "<p class='error'>❌ Query mismatch! Simple: " . count($simpleSubscriptions) . ", Complex: " . count($complexSubscriptions) . "</p>";
                echo "<p><strong>Recommendation:</strong> Use the simple query that works reliably</p>";
            }
        }
        ?>
    </div>
    
    <p><a href="dashboard.php">← Back to Dashboard</a></p>
</body>
</html>
