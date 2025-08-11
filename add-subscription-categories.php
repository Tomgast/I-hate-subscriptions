<?php
/**
 * Add Category Support to Subscriptions
 * Add category column and categorize existing subscriptions
 */

require_once 'includes/database_helper.php';
require_once 'includes/subscription_categorizer.php';

$userId = 2;

try {
    $pdo = DatabaseHelper::getConnection();
    
    echo "=== ADDING SUBSCRIPTION CATEGORY SUPPORT ===\n\n";
    
    // Check if category column already exists
    $stmt = $pdo->query("DESCRIBE subscriptions");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('category', $columns)) {
        echo "Adding 'category' column to subscriptions table...\n";
        $pdo->exec("ALTER TABLE subscriptions ADD COLUMN category VARCHAR(50) DEFAULT 'Other'");
        echo "✅ Category column added\n\n";
    } else {
        echo "✅ Category column already exists\n\n";
    }
    
    // Get current subscriptions
    $stmt = $pdo->prepare("
        SELECT id, merchant_name, amount, billing_cycle, category
        FROM subscriptions 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($subscriptions)) {
        echo "No subscriptions found to categorize.\n";
        echo "Categories will be automatically assigned when new subscriptions are detected.\n\n";
    } else {
        echo "Categorizing " . count($subscriptions) . " existing subscriptions...\n\n";
        
        $categorized = 0;
        $categoryStats = [];
        
        foreach ($subscriptions as $subscription) {
            // Skip if already categorized (not 'Other' or null)
            if ($subscription['category'] && $subscription['category'] !== 'Other') {
                continue;
            }
            
            // Auto-categorize based on merchant name
            $categoryResult = SubscriptionCategorizer::categorizeSubscription($subscription['merchant_name']);
            $category = $categoryResult['category'];
            
            // Update database
            $stmt = $pdo->prepare("UPDATE subscriptions SET category = ? WHERE id = ?");
            $stmt->execute([$category, $subscription['id']]);
            
            $categorized++;
            
            if (!isset($categoryStats[$category])) {
                $categoryStats[$category] = 0;
            }
            $categoryStats[$category]++;
            
            echo sprintf("✅ %-30s → %s\n", 
                substr($subscription['merchant_name'], 0, 29),
                $category
            );
        }
        
        echo "\n=== CATEGORIZATION RESULTS ===\n";
        echo "Subscriptions categorized: $categorized\n\n";
        
        if (!empty($categoryStats)) {
            echo "Categories assigned:\n";
            foreach ($categoryStats as $category => $count) {
                echo "- $category: $count subscriptions\n";
            }
        }
    }
    
    echo "\n=== AVAILABLE CATEGORIES ===\n";
    $categories = SubscriptionCategorizer::getAvailableCategories();
    foreach ($categories as $category) {
        $description = SubscriptionCategorizer::getCategoryDescription($category);
        echo "- $category: $description\n";
    }
    
    echo "\n=== TESTING CATEGORIZATION ===\n";
    $testMerchants = ['Spotify AB', 'Netflix', 'Adobe Systems', 'Microsoft', 'Unknown Merchant'];
    
    foreach ($testMerchants as $merchant) {
        $result = SubscriptionCategorizer::categorizeSubscription($merchant);
        echo sprintf("%-20s → %-20s (confidence: %s)\n", 
            $merchant, 
            $result['category'], 
            $result['confidence']
        );
    }
    
    echo "\n🎉 SUBSCRIPTION CATEGORIZATION SYSTEM READY!\n";
    echo "- New subscriptions will be automatically categorized\n";
    echo "- Users can manually change categories in the dashboard\n";
    echo "- Categories help organize and analyze subscription spending\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
