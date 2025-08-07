<?php
/**
 * ASSIGN TEST PLAN - Create a valid plan for testing account
 * Assigns a monthly plan to support@origens.nl for testing purposes
 */

require_once 'config/db_config.php';

echo "<h1>🧪 Test Account Plan Assignment</h1>\n";
echo "<p>Assigning a valid plan to support@origens.nl for testing...</p>\n";

try {
    // Test email
    $testEmail = 'support@origens.nl';
    
    // First, check if user exists
    $stmt = $pdo->prepare("SELECT user_id, email, name FROM users WHERE email = ?");
    $stmt->execute([$testEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<div style='background: orange; color: white; padding: 20px; margin: 20px;'>";
        echo "<h3>⚠️ User Not Found</h3>";
        echo "<p>User with email '$testEmail' not found in database.</p>";
        echo "<p>Please sign in with Google OAuth first to create the user account.</p>";
        echo "</div>";
        exit;
    }
    
    echo "<div style='background: blue; color: white; padding: 20px; margin: 20px;'>";
    echo "<h3>✅ User Found</h3>";
    echo "<p>User ID: {$user['user_id']}</p>";
    echo "<p>Email: {$user['email']}</p>";
    echo "<p>Name: {$user['name']}</p>";
    echo "</div>";
    
    $userId = $user['user_id'];
    
    // Check if user already has a plan
    $stmt = $pdo->prepare("SELECT * FROM user_plans WHERE user_id = ?");
    $stmt->execute([$userId]);
    $existingPlan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingPlan) {
        echo "<div style='background: yellow; color: black; padding: 20px; margin: 20px;'>";
        echo "<h3>📋 Existing Plan Found</h3>";
        echo "<p>Plan Type: {$existingPlan['plan_type']}</p>";
        echo "<p>Status: " . ($existingPlan['is_active'] ? 'Active' : 'Inactive') . "</p>";
        echo "<p>Created: {$existingPlan['created_at']}</p>";
        echo "<p>Expires: {$existingPlan['expires_at']}</p>";
        echo "</div>";
        
        // Update existing plan to be active
        $stmt = $pdo->prepare("
            UPDATE user_plans 
            SET plan_type = 'monthly', 
                is_active = 1, 
                expires_at = DATE_ADD(NOW(), INTERVAL 1 MONTH),
                updated_at = NOW()
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        
        echo "<div style='background: green; color: white; padding: 20px; margin: 20px;'>";
        echo "<h3>✅ Plan Updated</h3>";
        echo "<p>Updated existing plan to: Monthly (Active)</p>";
        echo "<p>Expires: " . date('Y-m-d H:i:s', strtotime('+1 month')) . "</p>";
        echo "</div>";
        
    } else {
        // Create new plan
        $stmt = $pdo->prepare("
            INSERT INTO user_plans (user_id, plan_type, is_active, expires_at, created_at, updated_at)
            VALUES (?, 'monthly', 1, DATE_ADD(NOW(), INTERVAL 1 MONTH), NOW(), NOW())
        ");
        $stmt->execute([$userId]);
        
        echo "<div style='background: green; color: white; padding: 20px; margin: 20px;'>";
        echo "<h3>✅ New Plan Created</h3>";
        echo "<p>Created new plan: Monthly (Active)</p>";
        echo "<p>Expires: " . date('Y-m-d H:i:s', strtotime('+1 month')) . "</p>";
        echo "</div>";
    }
    
    // Verify the plan was created/updated
    $stmt = $pdo->prepare("SELECT * FROM user_plans WHERE user_id = ?");
    $stmt->execute([$userId]);
    $finalPlan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<div style='background: darkgreen; color: white; padding: 20px; margin: 20px;'>";
    echo "<h3>🎉 Final Plan Status</h3>";
    echo "<p>User: {$user['email']}</p>";
    echo "<p>Plan Type: {$finalPlan['plan_type']}</p>";
    echo "<p>Active: " . ($finalPlan['is_active'] ? 'Yes' : 'No') . "</p>";
    echo "<p>Expires: {$finalPlan['expires_at']}</p>";
    echo "<p>Created: {$finalPlan['created_at']}</p>";
    echo "<p>Updated: {$finalPlan['updated_at']}</p>";
    echo "</div>";
    
    echo "<div style='background: purple; color: white; padding: 20px; margin: 20px;'>";
    echo "<h3>🚀 Next Steps</h3>";
    echo "<p>1. <a href='dashboard.php' style='color: yellow;'>Try accessing the dashboard now</a></p>";
    echo "<p>2. The system should now recognize your active monthly plan</p>";
    echo "<p>3. You should have access to all monthly plan features</p>";
    echo "<p>4. If still redirected, check the debug output on dashboard.php</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: red; color: white; padding: 20px; margin: 20px;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<h3>🔧 Database Schema Check</h3>";
echo "<p>Checking if user_plans table exists and has correct structure...</p>";

try {
    $stmt = $pdo->query("DESCRIBE user_plans");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 20px;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div style='background: red; color: white; padding: 10px; margin: 20px;'>";
    echo "<p>Error checking user_plans table: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
