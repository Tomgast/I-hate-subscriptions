<?php
// Fix database schema by adding missing password column
require_once 'config/db_config.php';

echo "<h2>Database Schema Fix</h2>";

try {
    $pdo = getDBConnection();
    echo "✅ Database connection successful!<br>";
    
    // Check current table structure
    echo "<br><strong>Checking current users table structure...</strong><br>";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    
    $hasPassword = false;
    echo "Current columns:<br>";
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")<br>";
        if ($column['Field'] === 'password') {
            $hasPassword = true;
        }
    }
    
    // Add password column if missing
    if (!$hasPassword) {
        echo "<br><strong>Adding missing password column...</strong><br>";
        $pdo->exec("ALTER TABLE users ADD COLUMN password VARCHAR(255) AFTER name");
        echo "✅ Password column added successfully!<br>";
    } else {
        echo "<br>✅ Password column already exists!<br>";
    }
    
    // Check if user exists and update/create with password
    echo "<br><strong>Checking/Creating user account...</strong><br>";
    $email = 'support@origens.nl';
    $password = '213412';
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("SELECT id, email, name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ User exists! Updating password...<br>";
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashedPassword, $email]);
        echo "✅ Password updated for existing user!<br>";
    } else {
        echo "❌ User doesn't exist. Creating new user...<br>";
        $stmt = $pdo->prepare("INSERT INTO users (email, name, password, is_paid) VALUES (?, ?, ?, ?)");
        $stmt->execute([$email, 'Support User', $hashedPassword, false]);
        echo "✅ New user created successfully!<br>";
    }
    
    // Verify the fix worked
    echo "<br><strong>Verification:</strong><br>";
    $stmt = $pdo->prepare("SELECT id, email, name, password, is_paid FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && $user['password']) {
        $isValid = password_verify($password, $user['password']);
        echo "✅ User found with password!<br>";
        echo "✅ Password verification: " . ($isValid ? 'PASSED' : 'FAILED') . "<br>";
        echo "User ID: " . $user['id'] . "<br>";
        echo "Email: " . $user['email'] . "<br>";
        echo "Name: " . ($user['name'] ?: 'Not set') . "<br>";
    } else {
        echo "❌ Something went wrong!<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br><a href='auth/signin.php'>→ Try Login Again</a>";
echo " | <a href='test_db.php'>→ Run Test Again</a>";
?>
