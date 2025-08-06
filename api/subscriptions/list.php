<?php
/**
 * Subscriptions List Endpoint
 * Returns all subscriptions for the current user
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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
    
    $userId = $auth->getCurrentUserId();
    
    // Get all active subscriptions for user
    $subscriptions = $db->fetchAll(
        "SELECT * FROM subscriptions 
         WHERE user_id = ? AND is_active = TRUE 
         ORDER BY next_billing_date ASC",
        [$userId]
    );
    
    // Calculate additional data for each subscription
    foreach ($subscriptions as &$subscription) {
        // Days until next billing
        $nextBilling = new DateTime($subscription['next_billing_date']);
        $today = new DateTime();
        $daysUntil = $today->diff($nextBilling)->days;
        
        if ($nextBilling < $today) {
            $daysUntil = -$daysUntil; // Overdue
        }
        
        $subscription['days_until_renewal'] = $daysUntil;
        
        // Convert cost to float
        $subscription['cost'] = (float)$subscription['cost'];
        
        // Convert is_active to boolean
        $subscription['is_active'] = (bool)$subscription['is_active'];
    }
    
    // Calculate summary statistics
    $totalMonthly = 0;
    $totalYearly = 0;
    $activeCount = count($subscriptions);
    
    foreach ($subscriptions as $sub) {
        switch ($sub['billing_cycle']) {
            case 'monthly':
                $totalMonthly += $sub['cost'];
                $totalYearly += $sub['cost'] * 12;
                break;
            case 'yearly':
                $totalYearly += $sub['cost'];
                $totalMonthly += $sub['cost'] / 12;
                break;
            case 'weekly':
                $totalMonthly += $sub['cost'] * 4.33;
                $totalYearly += $sub['cost'] * 52;
                break;
            case 'daily':
                $totalMonthly += $sub['cost'] * 30;
                $totalYearly += $sub['cost'] * 365;
                break;
        }
    }
    
    echo json_encode([
        'success' => true,
        'subscriptions' => $subscriptions,
        'summary' => [
            'total_count' => $activeCount,
            'total_monthly' => round($totalMonthly, 2),
            'total_yearly' => round($totalYearly, 2),
            'average_monthly' => $activeCount > 0 ? round($totalMonthly / $activeCount, 2) : 0
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
