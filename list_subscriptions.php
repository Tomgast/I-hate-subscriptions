<?php
require_once 'config/db_config.php';

// Get database connection
$pdo = getDBConnection();

// Get all subscriptions
$stmt = $pdo->query("SELECT * FROM subscriptions ORDER BY created_at DESC");
$subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output as JSON
header('Content-Type: application/json');
echo json_encode([
    'count' => count($subscriptions),
    'subscriptions' => $subscriptions
], JSON_PRETTY_PRINT);
