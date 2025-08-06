<?php
// Test database connection and user lookup
require_once 'config/db_config.php';

echo "<h2>Database Connection Test</h2>";

try {
    $pdo = getDBConnection();
    echo "✅ Database connection successful!<br>";
    
    // Test if user exists
    $email = 'support@origens.nl';
    $stmt = $pdo->prepare("SELECT id, email, name, password_hash, is_pro FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ User found in database!<br>";
        echo "User ID: " . $user['id'] . "<br>";
        echo "Email: " . $user['email'] . "<br>";
        echo "Name: " . ($user['name'] ?: 'Not set') . "<br>";
        echo "Has password: " . ($user['password_hash'] ? 'Yes' : 'No') . "<br>";
        echo "Is pro: " . ($user['is_pro'] ? 'Yes' : 'No') . "<br>";
        
        // Test password verification
        $testPassword = '213412';
        if ($user['password_hash']) {
            $isValid = password_verify($testPassword, $user['password_hash']);
            echo "Password verification for '213412': " . ($isValid ? '✅ Valid' : '❌ Invalid') . "<br>";
        } else {
            echo "❌ No password set for this user<br>";
        }
    } else {
        echo "❌ User NOT found in database!<br>";
        
        // Show all users in database
        $stmt = $pdo->prepare("SELECT id, email, name FROM users LIMIT 10");
        $stmt->execute();
        $allUsers = $stmt->fetchAll();
        
        echo "<br><strong>All users in database:</strong><br>";
        if ($allUsers) {
            foreach ($allUsers as $u) {
                echo "- ID: {$u['id']}, Email: {$u['email']}, Name: " . ($u['name'] ?: 'Not set') . "<br>";
            }
        } else {
            echo "No users found in database.<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<br><a href='auth/signin.php'>← Back to Sign In</a>";
?>
