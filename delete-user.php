<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Not logged in - cannot delete user');
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Unknown';

echo "<h2>Deleting User Account</h2>";
echo "<p>Deleting user: <strong>$userName</strong> (ID: $userId)</p>";

try {
    // Connect to database
    $pdo = new PDO("mysql:host=45.82.188.227;port=3306;dbname=vxmjmwlj_;charset=utf8mb4", 
                   "123cashcontrol", 
                   "Welkom123!", 
                   [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // Delete user from database
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $result = $stmt->execute([$userId]);
    
    if ($result) {
        echo "<p>✅ User deleted from database successfully</p>";
        
        // Clear session
        session_unset();
        session_destroy();
        
        echo "<p>✅ Session cleared successfully</p>";
        echo "<p><strong>Account deletion complete!</strong></p>";
        echo "<p>You can now:</p>";
        echo "<ul>";
        echo "<li>Go to <a href='auth/signup.php'>auth/signup.php</a> to create a new account</li>";
        echo "<li>Or go to <a href='index.php'>index.php</a> to start over</li>";
        echo "</ul>";
        
    } else {
        echo "<p>❌ Failed to delete user from database</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 40px; }
h2 { color: #333; }
p { margin: 10px 0; }
ul { margin: 20px 0; }
li { margin: 5px 0; }
a { color: #10b981; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
