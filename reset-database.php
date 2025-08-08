<?php
// Database Reset Script - Clears all users and related data for testing
session_start();

echo "<h2>🗑️ Database Reset Tool</h2>";
echo "<p><strong>Warning:</strong> This will delete ALL users and related data from the database!</p>";

// Safety check - require confirmation
if (!isset($_POST['confirm_reset'])) {
    ?>
    <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3>⚠️ Confirmation Required</h3>
        <p>This action will permanently delete:</p>
        <ul>
            <li>All user accounts</li>
            <li>All user sessions</li>
            <li>All subscription data</li>
            <li>All checkout sessions</li>
            <li>All bank scan data</li>
            <li>All reminder logs</li>
        </ul>
        <p><strong>This action cannot be undone!</strong></p>
        
        <form method="POST" style="margin-top: 20px;">
            <input type="hidden" name="confirm_reset" value="1">
            <button type="submit" style="background: #dc3545; color: white; padding: 12px 24px; border: none; border-radius: 6px; font-size: 16px; cursor: pointer;">
                🗑️ Yes, Delete All Users
            </button>
            <a href="index.php" style="margin-left: 15px; padding: 12px 24px; background: #6c757d; color: white; text-decoration: none; border-radius: 6px;">
                Cancel
            </a>
        </form>
    </div>
    <?php
} else {
    // Perform the reset
    try {
        // Connect to database
        $pdo = new PDO("mysql:host=45.82.188.227;port=3306;dbname=vxmjmwlj_;charset=utf8mb4", 
                       "123cashcontrol", 
                       "Welkom123!", 
                       [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        
        echo "<h3>🔄 Starting Database Reset...</h3>";
        
        // Start transaction
        $pdo->beginTransaction();
        
        // List of tables to clear (in order to avoid foreign key issues)
        $tables = [
            'user_sessions',
            'checkout_sessions', 
            'bank_scans',
            'reminder_logs',
            'subscriptions',
            'user_preferences',
            'users'
        ];
        
        $deletedCounts = [];
        
        foreach ($tables as $table) {
            try {
                // Count records before deletion
                $countStmt = $pdo->query("SELECT COUNT(*) FROM $table");
                $count = $countStmt->fetchColumn();
                
                if ($count > 0) {
                    // Delete all records
                    $deleteStmt = $pdo->prepare("DELETE FROM $table");
                    $deleteStmt->execute();
                    $deletedCounts[$table] = $count;
                    echo "<p>✅ Deleted $count records from <strong>$table</strong></p>";
                } else {
                    echo "<p>ℹ️ Table <strong>$table</strong> was already empty</p>";
                }
            } catch (Exception $e) {
                echo "<p>⚠️ Table <strong>$table</strong> doesn't exist or error: " . $e->getMessage() . "</p>";
            }
        }
        
        // Reset auto-increment counters
        echo "<h4>🔄 Resetting Auto-Increment Counters...</h4>";
        foreach ($tables as $table) {
            try {
                $pdo->exec("ALTER TABLE $table AUTO_INCREMENT = 1");
                echo "<p>✅ Reset auto-increment for <strong>$table</strong></p>";
            } catch (Exception $e) {
                echo "<p>⚠️ Could not reset auto-increment for <strong>$table</strong>: " . $e->getMessage() . "</p>";
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Clear current session
        session_unset();
        session_destroy();
        
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>✅ Database Reset Complete!</h3>";
        echo "<p><strong>Summary:</strong></p>";
        echo "<ul>";
        
        $totalDeleted = array_sum($deletedCounts);
        if ($totalDeleted > 0) {
            foreach ($deletedCounts as $table => $count) {
                echo "<li>$table: $count records deleted</li>";
            }
            echo "<li><strong>Total: $totalDeleted records deleted</strong></li>";
        } else {
            echo "<li>Database was already empty</li>";
        }
        
        echo "</ul>";
        echo "<p>✅ All auto-increment counters reset to 1</p>";
        echo "<p>✅ Current session cleared</p>";
        echo "</div>";
        
        echo "<h3>🚀 Ready for Testing!</h3>";
        echo "<p>You can now test different flows:</p>";
        echo "<ul>";
        echo "<li><a href='auth/signup.php'>Standard Signup Flow</a> - Test welcome email</li>";
        echo "<li><a href='auth/signin.php'>Google OAuth Flow</a> - Test Google signup + welcome email</li>";
        echo "<li><a href='index.php'>Homepage</a> - Start fresh</li>";
        echo "</ul>";
        
    } catch (Exception $e) {
        // Rollback on error
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>❌ Database Reset Failed!</h3>";
        echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
        echo "<p>No changes were made to the database.</p>";
        echo "</div>";
    }
}
?>

<style>
body { 
    font-family: Arial, sans-serif; 
    margin: 40px; 
    background: #f8f9fa; 
}
h2, h3, h4 { 
    color: #333; 
}
p { 
    margin: 10px 0; 
}
ul { 
    margin: 20px 0; 
}
li { 
    margin: 5px 0; 
}
a { 
    color: #10b981; 
    text-decoration: none; 
}
a:hover { 
    text-decoration: underline; 
}
button:hover {
    opacity: 0.9;
}
</style>
