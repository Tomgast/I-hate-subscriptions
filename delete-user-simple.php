<?php
/**
 * Simple Delete Test User Script
 * Removes tom@degruijterweb.nl from existing tables only
 */

// Suppress HTTP_HOST warnings in CLI
if (php_sapi_name() === 'cli') {
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['REQUEST_URI'] = '/';
    $_SERVER['HTTPS'] = 'on';
}

require_once 'config/db_config.php';

echo "=== Simple Delete Test User Script ===\n\n";

$testEmail = 'tom@degruijterweb.nl';

try {
    $pdo = getDBConnection();
    
    // First, check what tables actually exist
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Existing tables in database:\n";
    foreach ($existingTables as $table) {
        echo "- $table\n";
    }
    echo "\n";
    
    // Find the user
    $stmt = $pdo->prepare("SELECT id, email, name, subscription_type FROM users WHERE email = ?");
    $stmt->execute([$testEmail]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "✅ User '$testEmail' not found in database - already clean for testing.\n";
        exit(0);
    }
    
    $userId = $user['id'];
    echo "Found user: {$user['name']} ({$user['email']}) - ID: $userId\n";
    echo "Current subscription: {$user['subscription_type']}\n\n";
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Delete from tables that exist and might have user data
    $tablesToCheck = [
        'reminder_logs' => 'user_id',
        'user_preferences' => 'user_id', 
        'subscriptions' => 'user_id',
        'bank_scans' => 'user_id',
        'bank_connections' => 'user_id',
        'checkout_sessions' => 'user_id'
    ];
    
    $totalDeleted = 0;
    
    foreach ($tablesToCheck as $table => $userColumn) {
        if (in_array($table, $existingTables)) {
            try {
                $stmt = $pdo->prepare("DELETE FROM $table WHERE $userColumn = ?");
                $stmt->execute([$userId]);
                $count = $stmt->rowCount();
                echo "Deleted $count entries from $table\n";
                $totalDeleted += $count;
            } catch (Exception $e) {
                echo "⚠️  Could not delete from $table: " . $e->getMessage() . "\n";
            }
        } else {
            echo "⚠️  Table $table does not exist - skipping\n";
        }
    }
    
    // Finally delete the user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userDeleted = $stmt->rowCount();
    
    if ($userDeleted === 1) {
        echo "Deleted user account\n";
        $totalDeleted++;
    } else {
        throw new Exception("Failed to delete user account");
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo "\n✅ SUCCESS: Deleted user '$testEmail' and $totalDeleted total database entries!\n";
    echo "\nThe system is now clean for fresh testing with this email address.\n";
    echo "\nYou can now:\n";
    echo "1. Go to https://123cashcontrol.com\n";
    echo "2. Sign up with tom@degruijterweb.nl\n";
    echo "3. Test the complete user journey from scratch\n";
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nTransaction rolled back - no data was deleted.\n";
    exit(1);
}

echo "\n=== End of Script ===\n";
?>
