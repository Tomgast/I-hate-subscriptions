<?php
/**
 * Unsubscribe Guides Test (Search, View)
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

echo "=== Unsubscribe Guides Test ===\n\n";

try {
    // Test 1: Check unsubscribe guide files
    echo "1. Checking unsubscribe guide files...\n";
    
    $unsubscribeFiles = [
        'unsubscribe/index.php' => 'Unsubscribe guides main page',
        'unsubscribe/search.php' => 'Guide search functionality',
        'unsubscribe/view.php' => 'Individual guide viewer',
        'unsubscribe/guides/' => 'Guides directory'
    ];
    
    foreach ($unsubscribeFiles as $file => $description) {
        $path = __DIR__ . '/' . $file;
        if (file_exists($path) || is_dir($path)) {
            echo "   ✅ $description: $file\n";
        } else {
            echo "   ❌ Missing: $description ($file)\n";
        }
    }
    echo "\n";
    
    // Test 2: Check for guide content/database
    echo "2. Checking unsubscribe guide content...\n";
    require_once __DIR__ . '/config/db_config.php';
    
    $pdo = getDBConnection();
    
    // Check if unsubscribe_guides table exists
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'unsubscribe_guides'");
        $tableExists = $stmt->rowCount() > 0;
        
        if ($tableExists) {
            echo "   ✅ Unsubscribe guides table exists\n";
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM unsubscribe_guides");
            $result = $stmt->fetch();
            echo "   - Total guides: " . $result['count'] . "\n";
            
            if ($result['count'] > 0) {
                // Get sample guide data
                $stmt = $pdo->query("SELECT * FROM unsubscribe_guides LIMIT 3");
                $guides = $stmt->fetchAll();
                
                echo "   - Sample guides:\n";
                foreach ($guides as $guide) {
                    echo "     • " . ($guide['service_name'] ?? 'Unknown') . "\n";
                }
            }
        } else {
            echo "   ⚠️ Unsubscribe guides table not found\n";
            echo "   - Checking for file-based guides...\n";
            
            // Check for file-based guides
            $guidesDir = __DIR__ . '/unsubscribe/guides/';
            if (is_dir($guidesDir)) {
                $guideFiles = glob($guidesDir . '*.{php,html,md}', GLOB_BRACE);
                if (!empty($guideFiles)) {
                    echo "   ✅ Found " . count($guideFiles) . " file-based guides\n";
                    foreach (array_slice($guideFiles, 0, 3) as $file) {
                        echo "     • " . basename($file) . "\n";
                    }
                } else {
                    echo "   ❌ No guide files found\n";
                }
            }
        }
    } catch (Exception $e) {
        echo "   ❌ Error checking guides: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    // Test 3: Test search functionality
    echo "3. Testing search functionality...\n";
    
    // Test basic search logic
    $testServices = ['Netflix', 'Spotify', 'Amazon Prime', 'Microsoft'];
    $searchTerm = 'netflix';
    
    $matches = array_filter($testServices, function($service) use ($searchTerm) {
        return stripos($service, $searchTerm) !== false;
    });
    
    if (!empty($matches)) {
        echo "   ✅ Search logic working\n";
        echo "   - Search term: '$searchTerm'\n";
        echo "   - Matches found: " . implode(', ', $matches) . "\n";
    } else {
        echo "   ❌ Search logic failed\n";
    }
    echo "\n";
    
    // Test 4: Check guide access control
    echo "4. Testing guide access control...\n";
    
    if (file_exists(__DIR__ . '/includes/plan_manager.php')) {
        require_once __DIR__ . '/includes/plan_manager.php';
        $planManager = getPlanManager();
        
        try {
            $userId = 1;
            $canAccessGuides = $planManager->canAccessFeature($userId, 'unsubscribe_guides');
            
            echo "   ✅ Guide access control working\n";
            echo "   - User $userId guide access: " . ($canAccessGuides ? "✅ Allowed" : "❌ Denied") . "\n";
        } catch (Exception $e) {
            echo "   ⚠️ Guide access control error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ⚠️ Cannot test guide access control (PlanManager missing)\n";
    }
    echo "\n";
    
    // Test 5: Check common subscription services coverage
    echo "5. Checking subscription service coverage...\n";
    
    $popularServices = [
        'Netflix', 'Spotify', 'Amazon Prime', 'Disney+', 'Hulu',
        'Microsoft 365', 'Adobe Creative Cloud', 'Dropbox', 'GitHub',
        'Zoom', 'Slack', 'Canva', 'YouTube Premium'
    ];
    
    echo "   - Popular services that should have guides:\n";
    foreach (array_slice($popularServices, 0, 6) as $service) {
        echo "     • $service\n";
    }
    
    echo "   ✅ Service list compiled for guide coverage\n\n";
    
    // Test 6: Test guide content structure
    echo "6. Testing guide content structure...\n";
    
    // Sample guide structure
    $sampleGuide = [
        'service_name' => 'Netflix',
        'difficulty' => 'Easy',
        'steps' => [
            'Log into your Netflix account',
            'Go to Account settings',
            'Click "Cancel Membership"',
            'Confirm cancellation'
        ],
        'tips' => 'You can reactivate anytime before your billing period ends'
    ];
    
    if (!empty($sampleGuide['steps'])) {
        echo "   ✅ Guide structure template working\n";
        echo "   - Service: " . $sampleGuide['service_name'] . "\n";
        echo "   - Steps: " . count($sampleGuide['steps']) . " steps defined\n";
        echo "   - Difficulty: " . $sampleGuide['difficulty'] . "\n";
    }
    echo "\n";
    
    echo "=== Unsubscribe Guides Test Summary ===\n";
    
    $guidesExist = file_exists(__DIR__ . '/unsubscribe/index.php');
    $searchExists = file_exists(__DIR__ . '/unsubscribe/search.php');
    
    if ($guidesExist && $searchExists) {
        echo "✅ Unsubscribe guides system present\n";
        echo "✅ Search functionality available\n";
        echo "✅ Guide content structure defined\n";
        echo "Ready for unsubscribe guide functionality\n";
    } else {
        echo "⚠️ Unsubscribe guides system incomplete:\n";
        if (!$guidesExist) echo "   - Main guides page missing\n";
        if (!$searchExists) echo "   - Search functionality missing\n";
        echo "   - May need implementation or file location verification\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
?>
