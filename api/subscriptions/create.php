<?php
/**
 * Create Subscription Endpoint
 * Creates a new subscription for the current user
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/auth.php';
require_once '../../config/database.php';

try {
    // Require authentication
    $auth->requireAuth();
    
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $requiredFields = ['name', 'cost', 'billing_cycle', 'next_billing_date'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            throw new Exception("Field '{$field}' is required");
        }
    }
    
    // Validate billing cycle
    $validCycles = ['monthly', 'yearly', 'weekly', 'daily'];
    if (!in_array($input['billing_cycle'], $validCycles)) {
        throw new Exception('Invalid billing cycle');
    }
    
    // Validate cost
    $cost = floatval($input['cost']);
    if ($cost <= 0) {
        throw new Exception('Cost must be greater than 0');
    }
    
    // Validate date
    $nextBillingDate = DateTime::createFromFormat('Y-m-d', $input['next_billing_date']);
    if (!$nextBillingDate) {
        throw new Exception('Invalid date format. Use YYYY-MM-DD');
    }
    
    $userId = $auth->getCurrentUserId();
    $subscriptionId = $auth->generateUuid();
    
    // Insert subscription
    $db->execute(
        "INSERT INTO subscriptions (id, user_id, name, cost, billing_cycle, next_billing_date, category, description, is_active, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, TRUE, NOW())",
        [
            $subscriptionId,
            $userId,
            $input['name'],
            $cost,
            $input['billing_cycle'],
            $input['next_billing_date'],
            $input['category'] ?? null,
            $input['description'] ?? null
        ]
    );
    
    // Get the created subscription
    $subscription = $db->fetch(
        "SELECT * FROM subscriptions WHERE id = ?",
        [$subscriptionId]
    );
    
    // Convert types
    $subscription['cost'] = (float)$subscription['cost'];
    $subscription['is_active'] = (bool)$subscription['is_active'];
    
    echo json_encode([
        'success' => true,
        'subscription' => $subscription,
        'message' => 'Subscription created successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
