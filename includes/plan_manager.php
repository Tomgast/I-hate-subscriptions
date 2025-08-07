<?php
/**
 * PHASE 3A.4: PLAN MANAGER CLASS
 * Handles all plan-based access control and business logic for three-tier system
 */

require_once __DIR__ . '/../config/secure_loader.php';

class PlanManager {
    private $pdo;
    
    public function __construct() {
        // Initialize database connection
        $dbPassword = getSecureConfig('DB_PASSWORD');
        $dbUser = getSecureConfig('DB_USER');
        $dsn = "mysql:host=45.82.188.227;port=3306;dbname=vxmjmwlj_;charset=utf8mb4";
        
        $this->pdo = new PDO($dsn, $dbUser, $dbPassword, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
    
    /**
     * Get user's current plan information
     * @param int $userId User ID
     * @return array|null Plan information or null if no plan
     */
    public function getUserPlan($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    plan_type,
                    plan_expires_at,
                    plan_purchased_at,
                    scan_count,
                    max_scans,
                    stripe_customer_id,
                    stripe_subscription_id
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $plan = $stmt->fetch();
            
            if (!$plan || !$plan['plan_type']) {
                return null;
            }
            
            // Add computed properties
            $plan['is_active'] = $this->isPlanActive($plan);
            $plan['is_subscription'] = in_array($plan['plan_type'], ['monthly', 'yearly']);
            $plan['is_onetime'] = $plan['plan_type'] === 'onetime';
            $plan['scans_remaining'] = $this->getScansRemaining($plan);
            $plan['can_scan'] = $this->canPerformScan($plan);
            
            return $plan;
        } catch (Exception $e) {
            error_log("Error getting user plan: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if user can access a specific feature
     * @param int $userId User ID
     * @param string $feature Feature name
     * @return bool
     */
    public function canAccessFeature($userId, $feature) {
        $plan = $this->getUserPlan($userId);
        
        if (!$plan || !$plan['is_active']) {
            return false;
        }
        
        // Define feature access by plan type
        $featureAccess = [
            'monthly' => [
                'bank_scan' => true,
                'unlimited_scans' => true,
                'real_time_analytics' => true,
                'email_notifications' => true,
                'subscription_management' => true,
                'export' => true,
                'advanced_reporting' => false,
                'priority_support' => false
            ],
            'yearly' => [
                'bank_scan' => true,
                'unlimited_scans' => true,
                'real_time_analytics' => true,
                'email_notifications' => true,
                'subscription_management' => true,
                'export' => true,
                'advanced_reporting' => true,
                'priority_support' => true
            ],
            'onetime' => [
                'bank_scan' => true,
                'unlimited_scans' => false,
                'real_time_analytics' => false,
                'email_notifications' => false,
                'subscription_management' => false,
                'export' => true,
                'advanced_reporting' => false,
                'priority_support' => false
            ]
        ];
        
        $planType = $plan['plan_type'];
        
        if (!isset($featureAccess[$planType])) {
            return false;
        }
        
        return $featureAccess[$planType][$feature] ?? false;
    }
    
    /**
     * Check if user has scans remaining
     * @param int $userId User ID
     * @return bool
     */
    public function hasScansRemaining($userId) {
        $plan = $this->getUserPlan($userId);
        
        if (!$plan || !$plan['is_active']) {
            return false;
        }
        
        return $plan['can_scan'];
    }
    
    /**
     * Increment user's scan count
     * @param int $userId User ID
     * @return bool Success
     */
    public function incrementScanCount($userId) {
        try {
            $plan = $this->getUserPlan($userId);
            
            if (!$plan || !$plan['can_scan']) {
                return false;
            }
            
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET scan_count = scan_count + 1 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            
            return true;
        } catch (Exception $e) {
            error_log("Error incrementing scan count: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Activate a plan for a user after successful payment
     * @param int $userId User ID
     * @param string $planType Plan type (monthly, yearly, onetime)
     * @param string|null $stripeCustomerId Stripe customer ID
     * @param string|null $stripeSubscriptionId Stripe subscription ID
     * @return bool Success
     */
    public function activatePlan($userId, $planType, $stripeCustomerId = null, $stripeSubscriptionId = null) {
        try {
            $this->pdo->beginTransaction();
            
            // Calculate expiration date
            $expiresAt = null;
            $maxScans = 0;
            
            switch ($planType) {
                case 'monthly':
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 month'));
                    $maxScans = -1; // Unlimited
                    break;
                case 'yearly':
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 year'));
                    $maxScans = -1; // Unlimited
                    break;
                case 'onetime':
                    $expiresAt = null; // No expiration for one-time
                    $maxScans = 1; // Single scan only
                    break;
                default:
                    throw new Exception("Invalid plan type: $planType");
            }
            
            // Update user plan
            $stmt = $this->pdo->prepare("
                UPDATE users SET
                    plan_type = ?,
                    plan_expires_at = ?,
                    plan_purchased_at = NOW(),
                    scan_count = 0,
                    max_scans = ?,
                    stripe_customer_id = ?,
                    stripe_subscription_id = ?,
                    is_pro = 1,
                    is_premium = 1
                WHERE id = ?
            ");
            
            $stmt->execute([
                $planType,
                $expiresAt,
                $maxScans,
                $stripeCustomerId,
                $stripeSubscriptionId,
                $userId
            ]);
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            error_log("Error activating plan: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get plan display information
     * @param string $planType Plan type
     * @return array Plan display info
     */
    public function getPlanDisplayInfo($planType) {
        $planInfo = [
            'monthly' => [
                'name' => 'Monthly Pro',
                'price' => '€3/month',
                'color' => 'green',
                'badge_class' => 'bg-green-100 text-green-800',
                'description' => 'Unlimited features'
            ],
            'yearly' => [
                'name' => 'Yearly Pro',
                'price' => '€25/year',
                'color' => 'blue',
                'badge_class' => 'bg-blue-100 text-blue-800',
                'description' => 'Best value + priority support'
            ],
            'onetime' => [
                'name' => 'One-Time Scan',
                'price' => '€25 once',
                'color' => 'purple',
                'badge_class' => 'bg-purple-100 text-purple-800',
                'description' => 'Single scan + export'
            ]
        ];
        
        return $planInfo[$planType] ?? [
            'name' => 'Unknown Plan',
            'price' => '',
            'color' => 'gray',
            'badge_class' => 'bg-gray-100 text-gray-800',
            'description' => ''
        ];
    }
    
    /**
     * Get upgrade suggestions for current plan
     * @param int $userId User ID
     * @return array Upgrade suggestions
     */
    public function getUpgradeSuggestions($userId) {
        $plan = $this->getUserPlan($userId);
        
        if (!$plan) {
            return [
                'monthly' => 'Start with monthly for full features',
                'yearly' => 'Best value - save €11 per year',
                'onetime' => 'Perfect for one-time audit'
            ];
        }
        
        $suggestions = [];
        
        switch ($plan['plan_type']) {
            case 'onetime':
                $suggestions['monthly'] = 'Upgrade to unlimited scans for €3/month';
                $suggestions['yearly'] = 'Best value - unlimited features for €25/year';
                break;
            case 'monthly':
                $suggestions['yearly'] = 'Save €11/year + get priority support';
                break;
            case 'yearly':
                // Already on best plan
                break;
        }
        
        return $suggestions;
    }
    
    // Private helper methods
    
    private function isPlanActive($plan) {
        if (!$plan['plan_type']) {
            return false;
        }
        
        // One-time plans don't expire
        if ($plan['plan_type'] === 'onetime') {
            return true;
        }
        
        // Check subscription expiration
        if ($plan['plan_expires_at']) {
            return strtotime($plan['plan_expires_at']) > time();
        }
        
        return false;
    }
    
    private function getScansRemaining($plan) {
        if (!$plan['is_active']) {
            return 0;
        }
        
        // Unlimited for subscriptions
        if (in_array($plan['plan_type'], ['monthly', 'yearly'])) {
            return -1; // Unlimited
        }
        
        // Limited for one-time
        if ($plan['plan_type'] === 'onetime') {
            return max(0, $plan['max_scans'] - $plan['scan_count']);
        }
        
        return 0;
    }
    
    private function canPerformScan($plan) {
        if (!$plan['is_active']) {
            return false;
        }
        
        // Unlimited for subscriptions
        if (in_array($plan['plan_type'], ['monthly', 'yearly'])) {
            return true;
        }
        
        // Check remaining scans for one-time
        if ($plan['plan_type'] === 'onetime') {
            return $plan['scan_count'] < $plan['max_scans'];
        }
        
        return false;
    }
}

// Helper functions for easy access

/**
 * Get user's plan manager instance
 * @return PlanManager
 */
function getPlanManager() {
    static $instance = null;
    if ($instance === null) {
        $instance = new PlanManager();
    }
    return $instance;
}

/**
 * Quick check if user can access feature
 * @param int $userId User ID
 * @param string $feature Feature name
 * @return bool
 */
function userCanAccess($userId, $feature) {
    return getPlanManager()->canAccessFeature($userId, $feature);
}

/**
 * Quick check if user has active plan
 * @param int $userId User ID
 * @return bool
 */
function userHasActivePlan($userId) {
    $plan = getPlanManager()->getUserPlan($userId);
    return $plan && $plan['is_active'];
}

/**
 * Get user's plan badge HTML
 * @param int $userId User ID
 * @return string HTML badge
 */
function getUserPlanBadge($userId) {
    $plan = getPlanManager()->getUserPlan($userId);
    
    if (!$plan || !$plan['is_active']) {
        return '<span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded">No Plan</span>';
    }
    
    $displayInfo = getPlanManager()->getPlanDisplayInfo($plan['plan_type']);
    
    return '<span class="' . $displayInfo['badge_class'] . ' text-xs font-medium px-2.5 py-0.5 rounded">' . 
           $displayInfo['name'] . '</span>';
}
?>
