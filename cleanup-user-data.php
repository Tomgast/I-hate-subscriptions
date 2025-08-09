<?php
/**
 * CLEANUP USER DATA
 * Remove all bank and subscription data for support@origens.nl to start fresh
 */

session_start();
require_once 'config/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/signin.php');
    exit;
}

$userId = $_SESSION['user_id'];

echo "<h1>🧹 User Data Cleanup</h1>";
echo "<p><strong>User ID:</strong> $userId</p>";

try {
    $pdo = getDBConnection();
    
    // Get user email to confirm
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<p>❌ User not found</p>";
        exit;
    }
    
    echo "<p><strong>Email:</strong> " . htmlspecialchars($user['email']) . "</p>";
    
    if (isset($_POST['confirm_cleanup'])) {
        echo "<h2>🗑️ Starting Cleanup Process</h2>";
        
        $pdo->beginTransaction();
        
        try {
            // 1. Delete bank connection sessions
            $stmt = $pdo->prepare("DELETE FROM bank_connection_sessions WHERE user_id = ?");
            $stmt->execute([$userId]);
            $deleted = $stmt->rowCount();
            echo "<p>✅ Deleted $deleted bank connection sessions</p>";
            
            // 2. Delete bank connections
            $stmt = $pdo->prepare("DELETE FROM bank_connections WHERE user_id = ?");
            $stmt->execute([$userId]);
            $deleted = $stmt->rowCount();
            echo "<p>✅ Deleted $deleted bank connections</p>";
            
            // 3. Delete bank scans
            $stmt = $pdo->prepare("DELETE FROM bank_scans WHERE user_id = ?");
            $stmt->execute([$userId]);
            $deleted = $stmt->rowCount();
            echo "<p>✅ Deleted $deleted bank scans</p>";
            
            // 4. Delete subscriptions
            $stmt = $pdo->prepare("DELETE FROM subscriptions WHERE user_id = ?");
            $stmt->execute([$userId]);
            $deleted = $stmt->rowCount();
            echo "<p>✅ Deleted $deleted subscriptions</p>";
            
            // 5. Delete reminder logs
            $stmt = $pdo->prepare("DELETE FROM reminder_logs WHERE user_id = ?");
            $stmt->execute([$userId]);
            $deleted = $stmt->rowCount();
            echo "<p>✅ Deleted $deleted reminder logs</p>";
            
            // 6. Delete user preferences (optional - keeps user settings)
            $stmt = $pdo->prepare("DELETE FROM user_preferences WHERE user_id = ?");
            $stmt->execute([$userId]);
            $deleted = $stmt->rowCount();
            echo "<p>✅ Deleted $deleted user preferences</p>";
            
            // 7. Check for any other user-related data
            $tables_to_check = [
                'payment_history',
                'bank_accounts',
                'export_logs'
            ];
            
            foreach ($tables_to_check as $table) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM $table WHERE user_id = ?");
                    $stmt->execute([$userId]);
                    $deleted = $stmt->rowCount();
                    if ($deleted > 0) {
                        echo "<p>✅ Deleted $deleted records from $table</p>";
                    }
                } catch (Exception $e) {
                    // Table might not exist, that's ok
                    echo "<p>ℹ️ Table $table not found or no records</p>";
                }
            }
            
            $pdo->commit();
            
            echo "<h2>✅ Cleanup Complete!</h2>";
            echo "<p>All bank connections, scans, and subscription data have been removed.</p>";
            echo "<p>You can now start fresh with the fixed subscription detection.</p>";
            echo "<p><a href='bank/unified-scan.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏦 Connect Bank Account</a></p>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<p>❌ Error during cleanup: " . $e->getMessage() . "</p>";
        }
        
    } else {
        // Show confirmation form
        echo "<h2>⚠️ Confirm Data Cleanup</h2>";
        echo "<p>This will permanently delete ALL of the following data for user <strong>" . htmlspecialchars($user['email']) . "</strong>:</p>";
        echo "<ul>";
        echo "<li>🏦 Bank connections and sessions</li>";
        echo "<li>🔍 Bank scans and results</li>";
        echo "<li>💳 Detected subscriptions</li>";
        echo "<li>📧 Reminder logs</li>";
        echo "<li>⚙️ User preferences</li>";
        echo "<li>📊 Payment history</li>";
        echo "</ul>";
        
        echo "<p><strong>This action cannot be undone!</strong></p>";
        
        // Show current data counts
        echo "<h3>📊 Current Data Summary</h3>";
        
        $tables = [
            'bank_connection_sessions' => 'Bank Connection Sessions',
            'bank_connections' => 'Bank Connections',
            'bank_scans' => 'Bank Scans',
            'subscriptions' => 'Subscriptions',
            'reminder_logs' => 'Reminder Logs',
            'user_preferences' => 'User Preferences'
        ];
        
        foreach ($tables as $table => $label) {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM $table WHERE user_id = ?");
                $stmt->execute([$userId]);
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                echo "<p><strong>$label:</strong> $count records</p>";
            } catch (Exception $e) {
                echo "<p><strong>$label:</strong> Table not found</p>";
            }
        }
        
        echo "<form method='POST' style='margin: 20px 0;'>";
        echo "<button type='submit' name='confirm_cleanup' style='background: #dc3545; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>🗑️ YES, DELETE ALL DATA</button>";
        echo "</form>";
        
        echo "<p><a href='dashboard.php'>← Cancel and go back to dashboard</a></p>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
