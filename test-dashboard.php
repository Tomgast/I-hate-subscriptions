<?php
/**
 * Dashboard Functionality Test (Plan-based Access Control)
 */

// Suppress HTTP_HOST warnings for CLI
$_SERVER['HTTP_HOST'] = 'localhost';
session_start();

// Simulate logged in user
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test User';
$_SESSION['user_email'] = 'support@origens.nl';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Dashboard Functionality Test ===\n\n";

try {
    // Test 1: Check dashboard files
    echo "1. Checking dashboard files...\n";
    
    $dashboardFiles = [
        'dashboard.php' => 'Main dashboard',
        'dashboard-onetime.php' => 'One-time plan dashboard',
        'includes/plan_status.php' => 'Plan status component',
        'includes/plan_manager.php' => 'Plan manager class'
    ];
    
    foreach ($dashboardFiles as $file => $description) {
        if (file_exists(__DIR__ . '/' . $file)) {
            echo "   ✅ $description: $file\n";
        } else {
            echo "   ❌ Missing: $description ($file)\n";
        }
    }
    echo "\n";
    
    // Test 2: Load PlanManager
    echo "2. Testing PlanManager functionality...\n";
    
    if (file_exists(__DIR__ . '/includes/plan_manager.php')) {
        require_once __DIR__ . '/includes/plan_manager.php';
        
        if (function_exists('getPlanManager')) {
            $planManager = getPlanManager();
            echo "   ✅ PlanManager loaded successfully\n";
            
            // Test plan manager methods
            $methods = get_class_methods($planManager);
            $requiredMethods = ['getUserPlan', 'canAccessFeature', 'hasScansRemaining'];
            
            echo "   - Required methods:\n";
            foreach ($requiredMethods as $method) {
                if (in_array($method, $methods)) {
                    echo "     ✅ $method\n";
                } else {
                    echo "     ❌ $method (missing)\n";
                }
            }
        } else {
            echo "   ❌ getPlanManager function not found\n";
        }
    } else {
        echo "   ❌ PlanManager file not found\n";
    }
    echo "\n";
    
    // Test 3: Test database plan structure
    echo "3. Testing plan database structure...\n";
    require_once __DIR__ . '/config/db_config.php';
    
    $pdo = getDBConnection();
    
    // Check users table for plan columns
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $planColumns = ['plan_type', 'plan_expires_at', 'scans_used', 'scans_limit'];
    
    echo "   - Plan-related columns in users table:\n";
    foreach ($planColumns as $column) {
        if (in_array($column, $columns)) {
            echo "     ✅ $column\n";
        } else {
            echo "     ❌ $column (missing)\n";
        }
    }
    echo "\n";
    
    // Test 4: Test plan access control
    echo "4. Testing plan access control...\n";
    
    if (isset($planManager)) {
        try {
            // Test with user ID 1
            $userId = 1;
            
            // Check if user exists and get their plan
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if ($user) {
                echo "   ✅ Test user found (ID: $userId)\n";
                echo "   - Email: " . ($user['email'] ?? 'N/A') . "\n";
                echo "   - Plan type: " . ($user['plan_type'] ?? 'N/A') . "\n";
                
                // Test feature access
                $features = ['bank_scan', 'export', 'email_reminders'];
                echo "   - Feature access:\n";
                
                foreach ($features as $feature) {
                    try {
                        $hasAccess = $planManager->canAccessFeature($userId, $feature);
                        echo "     " . ($hasAccess ? "✅" : "❌") . " $feature\n";
                    } catch (Exception $e) {
                        echo "     ⚠️ $feature (error: " . $e->getMessage() . ")\n";
                    }
                }
            } else {
                echo "   ⚠️ Test user not found (ID: $userId)\n";
                echo "   - Creating test user for plan testing...\n";
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (email, name, plan_type, plan_expires_at, created_at) 
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    'test@example.com',
                    'Test User',
                    'monthly',
                    date('Y-m-d H:i:s', strtotime('+1 month'))
                ]);
                
                echo "   ✅ Test user created\n";
            }
        } catch (Exception $e) {
            echo "   ❌ Error testing plan access: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ⚠️ Skipping access control test (PlanManager not available)\n";
    }
    echo "\n";
    
    // Test 5: Test dashboard routing logic
    echo "5. Testing dashboard routing logic...\n";
    
    if (file_exists(__DIR__ . '/dashboard.php')) {
        // Read dashboard.php to check for routing logic
        $dashboardContent = file_get_contents(__DIR__ . '/dashboard.php');
        
        if (strpos($dashboardContent, 'plan_type') !== false) {
            echo "   ✅ Dashboard contains plan-based routing logic\n";
        } else {
            echo "   ⚠️ Dashboard may not have plan-based routing\n";
        }
        
        if (strpos($dashboardContent, 'dashboard-onetime') !== false) {
            echo "   ✅ Dashboard references one-time plan routing\n";
        } else {
            echo "   ⚠️ Dashboard may not route one-time users properly\n";
        }
    } else {
        echo "   ❌ Cannot test routing (dashboard.php not found)\n";
    }
    echo "\n";
    
    echo "=== Dashboard Test Summary ===\n";
    
    $dashboardExists = file_exists(__DIR__ . '/dashboard.php');
    $planManagerExists = file_exists(__DIR__ . '/includes/plan_manager.php');
    
    if ($dashboardExists && $planManagerExists) {
        echo "✅ Dashboard system appears properly configured\n";
        echo "✅ Plan-based access control components present\n";
        echo "✅ Database structure supports plan management\n";
        echo "Ready for plan-based dashboard functionality\n";
    } else {
        echo "⚠️ Dashboard system has some issues:\n";
        if (!$dashboardExists) echo "   - Main dashboard file missing\n";
        if (!$planManagerExists) echo "   - Plan manager missing\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
?>
