<?php
/**
 * User Plan Helper - Database-based plan detection that actually works
 * Reads from actual database schema (subscription_type, subscription_status, subscription_expires_at)
 */

require_once 'database_helper.php';

class UserPlanHelper {
    
    /**
     * Get user's actual plan status from database (not session)
     * @param int $userId User ID
     * @return array Plan status information
     */
    public static function getUserPlanStatus($userId) {
        try {
            $pdo = DatabaseHelper::getConnection();
            
            $stmt = $pdo->prepare("
                SELECT 
                    id,
                    email,
                    name,
                    subscription_type,
                    subscription_status,
                    subscription_expires_at,
                    stripe_customer_id,
                    created_at
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return [
                    'exists' => false,
                    'plan_type' => 'none',
                    'status' => 'inactive',
                    'is_paid' => false,
                    'expires_at' => null,
                    'is_expired' => true,
                    'error' => 'User not found'
                ];
            }
            
            // Analyze subscription data
            $subscriptionType = $user['subscription_type'] ?? 'none';
            $subscriptionStatus = $user['subscription_status'] ?? 'inactive';
            $expiresAt = $user['subscription_expires_at'];
            
            // Determine if subscription is active and valid
            $isExpired = false;
            $isPaid = false;
            
            if ($subscriptionType === 'free' || empty($subscriptionType)) {
                // Free user
                $isPaid = false;
            } elseif ($subscriptionStatus !== 'active') {
                // Inactive subscription
                $isPaid = false;
            } elseif ($expiresAt && strtotime($expiresAt) < time()) {
                // Expired subscription
                $isExpired = true;
                $isPaid = false;
            } elseif (in_array($subscriptionType, ['monthly', 'yearly'])) {
                // Valid ongoing subscription
                $isPaid = true;
            } elseif ($subscriptionType === 'one_time') {
                // One-time payment - check if still valid
                $isPaid = true; // One-time payments don't expire unless specifically set
            } else {
                // Unknown subscription type
                $isPaid = false;
            }
            
            return [
                'exists' => true,
                'user_id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'plan_type' => $subscriptionType,
                'status' => $subscriptionStatus,
                'is_paid' => $isPaid,
                'expires_at' => $expiresAt,
                'is_expired' => $isExpired,
                'stripe_customer_id' => $user['stripe_customer_id'],
                'created_at' => $user['created_at'],
                'display_status' => $isPaid ? self::getPlanDisplayName($subscriptionType, $isPaid) : 'Unpaid',
                'plan_display' => self::getPlanDisplayName($subscriptionType, $isPaid)
            ];
            
        } catch (Exception $e) {
            error_log("Error getting user plan status: " . $e->getMessage());
            return [
                'exists' => false,
                'plan_type' => 'none',
                'status' => 'error',
                'is_paid' => false,
                'expires_at' => null,
                'is_expired' => true,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get display name for plan type
     * @param string $planType Plan type from database
     * @param bool $isPaid Whether user is currently paid
     * @return string Display name
     */
    private static function getPlanDisplayName($planType, $isPaid) {
        if (!$isPaid) {
            return 'No Active Plan';
        }
        
        switch ($planType) {
            case 'monthly':
                return 'Monthly Pro';
            case 'yearly':
                return 'Yearly Pro';
            case 'one_time':
                return 'One-Time Scan';
            default:
                return 'Unknown Plan';
        }
    }
    
    /**
     * Update user session with current database plan status
     * @param int $userId User ID
     * @return bool Success
     */
    public static function refreshUserSession($userId) {
        try {
            $planStatus = self::getUserPlanStatus($userId);
            
            if ($planStatus['exists']) {
                $_SESSION['user_id'] = $planStatus['user_id'];
                $_SESSION['user_email'] = $planStatus['email'];
                $_SESSION['user_name'] = $planStatus['name'];
                $_SESSION['subscription_type'] = $planStatus['plan_type'];
                $_SESSION['user_status'] = $planStatus['status'];
                $_SESSION['is_paid'] = $planStatus['is_paid'];
                $_SESSION['plan_display'] = $planStatus['plan_display'];
                $_SESSION['expires_at'] = $planStatus['expires_at'];
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error refreshing user session: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user can access upgrade page (should be unpaid users only)
     * @param int $userId User ID
     * @return bool Can access upgrade
     */
    public static function canAccessUpgrade($userId) {
        $planStatus = self::getUserPlanStatus($userId);
        return !$planStatus['is_paid']; // Only unpaid users should see upgrade page
    }
    
    /**
     * Get appropriate dashboard redirect for user
     * @param int $userId User ID
     * @return string Dashboard URL
     */
    public static function getDashboardUrl($userId) {
        $planStatus = self::getUserPlanStatus($userId);
        
        if (!$planStatus['is_paid']) {
            return 'upgrade.php'; // Unpaid users must upgrade first
        }
        
        switch ($planStatus['plan_type']) {
            case 'one_time':
                return 'dashboard-onetime.php'; // One-time users get limited dashboard
            case 'monthly':
            case 'yearly':
                return 'dashboard.php'; // Subscription users get full dashboard
            default:
                return 'dashboard.php';
        }
    }
}
?>
