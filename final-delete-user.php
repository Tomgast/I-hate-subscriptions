<?php
require_once 'config/secure_loader.php';
require_once 'includes/database_helper.php';

$pdo = DatabaseHelper::getConnection();
$stmt = $pdo->prepare('DELETE FROM users WHERE id = 9');
$stmt->execute();
echo 'User 9 deleted: ' . $stmt->rowCount() . ' records';
?>
