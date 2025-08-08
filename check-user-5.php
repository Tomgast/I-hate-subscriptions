<?php
require_once 'config/secure_loader.php';
require_once 'includes/database_helper.php';

$pdo = DatabaseHelper::getConnection();
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = 5');
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo "User 5 Database Record:\n";
if ($user) {
    foreach ($user as $key => $value) {
        echo "$key: " . ($value ?? 'NULL') . "\n";
    }
} else {
    echo "User not found\n";
}

// Check current time vs expiration
if ($user && $user['subscription_expires_at']) {
    $expires = strtotime($user['subscription_expires_at']);
    $now = time();
    echo "\nExpiration Analysis:\n";
    echo "Expires: " . $user['subscription_expires_at'] . "\n";
    echo "Current: " . date('Y-m-d H:i:s') . "\n";
    echo "Status: " . ($expires > $now ? 'ACTIVE' : 'EXPIRED') . "\n";
}
?>
