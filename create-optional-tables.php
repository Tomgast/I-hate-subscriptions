<?php
/**
 * CREATE OPTIONAL ENHANCEMENT TABLES
 * Quick tool to create subscription_categories table with default data
 */

session_start();
require_once 'config/db_config.php';

if (!isset($_SESSION['user_id'])) {
    die('Please log in first');
}

echo "<h1>🔧 Create Optional Enhancement Tables</h1>";

try {
    $pdo = getDBConnection();
    
    // Create subscription_categories table
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS subscription_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            icon VARCHAR(100) NULL,
            color VARCHAR(7) NULL,
            description TEXT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_categories_name (name),
            INDEX idx_categories_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($createTableSQL);
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
    echo "✅ <strong>subscription_categories</strong> table created successfully!";
    echo "</div>";
    
    // Add default categories
    $defaultCategories = [
        ['Entertainment', '🎬', '#e74c3c', 'Streaming services, movies, TV shows'],
        ['Software', '💻', '#3498db', 'Software subscriptions and SaaS tools'],
        ['Music', '🎵', '#9b59b6', 'Music streaming and audio services'],
        ['Gaming', '🎮', '#e67e22', 'Gaming subscriptions and platforms'],
        ['News', '📰', '#34495e', 'News and magazine subscriptions'],
        ['Fitness', '💪', '#27ae60', 'Gym memberships and fitness apps'],
        ['Education', '📚', '#f39c12', 'Online courses and educational platforms'],
        ['Business', '💼', '#2c3e50', 'Business tools and professional services'],
        ['Utilities', '⚡', '#95a5a6', 'Utilities and essential services'],
        ['Food', '🍕', '#ff6b6b', 'Food delivery and meal subscriptions'],
        ['Shopping', '🛒', '#4ecdc4', 'Shopping and retail subscriptions'],
        ['Other', '📦', '#7f8c8d', 'Miscellaneous subscriptions']
    ];
    
    $insertSQL = "INSERT IGNORE INTO subscription_categories (name, icon, color, description) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($insertSQL);
    
    $addedCount = 0;
    foreach ($defaultCategories as $category) {
        $result = $stmt->execute($category);
        if ($result && $stmt->rowCount() > 0) {
            $addedCount++;
        }
    }
    
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
    echo "✅ Added <strong>{$addedCount}</strong> default categories";
    echo "</div>";
    
    // Show categories
    $stmt = $pdo->query("SELECT * FROM subscription_categories ORDER BY name");
    $categories = $stmt->fetchAll();
    
    echo "<h2>📋 Available Categories</h2>";
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin: 20px 0;'>";
    
    foreach ($categories as $cat) {
        echo "<div style='background: white; padding: 10px; border-radius: 5px; border: 1px solid #ddd; text-align: center;'>";
        echo "<div style='font-size: 24px; margin-bottom: 5px;'>{$cat['icon']}</div>";
        echo "<div style='font-weight: bold; color: {$cat['color']};'>{$cat['name']}</div>";
        echo "<div style='font-size: 12px; color: #666; margin-top: 5px;'>{$cat['description']}</div>";
        echo "</div>";
    }
    
    echo "</div>";
    
    echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; color: #0c5460; margin: 20px 0;'>";
    echo "<h3>✨ Benefits of Categories:</h3>";
    echo "<ul>";
    echo "<li><strong>Better Organization:</strong> Users can easily filter and group subscriptions</li>";
    echo "<li><strong>Visual Appeal:</strong> Icons and colors make the dashboard more attractive</li>";
    echo "<li><strong>Analytics:</strong> Track spending by category (Entertainment vs Business)</li>";
    echo "<li><strong>Smart Detection:</strong> Auto-categorize detected subscriptions</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
    echo "❌ <strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<div style='margin: 20px 0;'>";
echo "<a href='test-complete-system.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Re-run System Test</a>";
echo "<a href='inject-test-subscriptions.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Inject Test Data</a>";
echo "</div>";
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}
h1, h2, h3 {
    color: #333;
}
</style>
