<?php
/**
 * Multi-Bank Account Service
 * Handles multiple bank account connections with per-account pricing
 */

require_once __DIR__ . '/database_helper.php';
require_once __DIR__ . '/stripe_service.php';

class MultiBankService {
    private $pdo;
    private $stripeService;
    
    public function __construct() {
        $this->pdo = DatabaseHelper::getConnection();
        $this->stripeService = new StripeService();
    }
    
    /**
     * Get all active bank accounts for a user
     * @param int $userId User ID
     * @return array Array of bank accounts
     */
    public function getUserBankAccounts($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, account_id, account_name, account_type, provider, 
                       last_sync_at, status, created_at
                FROM bank_accounts 
                WHERE user_id = ? AND status = 'active'
                ORDER BY created_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting user bank accounts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get count of active bank accounts for a user
     * @param int $userId User ID
     * @return int Number of active bank accounts
     */
    public function getActiveBankAccountCount($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM bank_accounts 
                WHERE user_id = ? AND status = 'active'
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            return (int)$result['count'];
        } catch (Exception $e) {
            error_log("Error counting bank accounts: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Check if user can add another bank account based on their plan
     * @param int $userId User ID
     * @return array Result with can_add boolean and message
     */
    public function canAddBankAccount($userId) {
        try {
            // Get user's current plan
            $stmt = $this->pdo->prepare("
                SELECT subscription_type, subscription_status 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['can_add' => false, 'message' => 'User not found'];
            }
            
            // Check if user has active subscription
            if ($user['subscription_status'] !== 'active') {
                return ['can_add' => false, 'message' => 'Active subscription required'];
            }
            
            $currentBankCount = $this->getActiveBankAccountCount($userId);
            
            // For now, allow unlimited bank accounts (pricing will be per account)
            // In the future, you could add limits based on plan type
            return [
                'can_add' => true, 
                'message' => 'Can add bank account',
                'current_count' => $currentBankCount,
                'monthly_cost_per_account' => 3.00
            ];
            
        } catch (Exception $e) {
            error_log("Error checking bank account limits: " . $e->getMessage());
            return ['can_add' => false, 'message' => 'Error checking limits'];
        }
    }
    
    /**
     * Calculate monthly cost based on number of connected bank accounts
     * @param int $userId User ID
     * @return float Monthly cost in EUR
     */
    public function calculateMonthlyCost($userId) {
        $bankAccountCount = $this->getActiveBankAccountCount($userId);
        return max(1, $bankAccountCount) * 3.00; // €3 per bank account, minimum €3
    }
    
    /**
     * Add a new bank account for user
     * @param int $userId User ID
     * @param array $accountData Account data from bank API
     * @param string $provider Provider name (stripe, gocardless, etc.)
     * @return int|false Bank account ID or false on failure
     */
    public function addBankAccount($userId, $accountData, $provider = 'stripe') {
        try {
            // Check if user can add another bank account
            $canAdd = $this->canAddBankAccount($userId);
            if (!$canAdd['can_add']) {
                throw new Exception($canAdd['message']);
            }
            
            // Insert new bank account
            $stmt = $this->pdo->prepare("
                INSERT INTO bank_accounts (
                    user_id, account_id, account_name, account_type, provider,
                    access_token, refresh_token, token_expires_at, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
            ");
            
            $stmt->execute([
                $userId,
                $accountData['account_id'],
                $accountData['account_name'] ?? 'Bank Account',
                $accountData['account_type'] ?? 'checking',
                $provider,
                $accountData['access_token'] ?? null,
                $accountData['refresh_token'] ?? null,
                $accountData['token_expires_at'] ?? null
            ]);
            
            $bankAccountId = $this->pdo->lastInsertId();
            
            // Update user's subscription cost if they have an active subscription
            $this->updateSubscriptionCost($userId);
            
            return $bankAccountId;
            
        } catch (Exception $e) {
            error_log("Error adding bank account: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove/disconnect a bank account
     * @param int $userId User ID
     * @param int $bankAccountId Bank account ID
     * @return bool Success
     */
    public function disconnectBankAccount($userId, $bankAccountId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE bank_accounts 
                SET status = 'revoked', updated_at = NOW() 
                WHERE id = ? AND user_id = ? AND status = 'active'
            ");
            $stmt->execute([$bankAccountId, $userId]);
            
            if ($stmt->rowCount() > 0) {
                // Update subscription cost
                $this->updateSubscriptionCost($userId);
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Error disconnecting bank account: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update user's subscription cost based on number of connected bank accounts
     * @param int $userId User ID
     * @return bool Success
     */
    private function updateSubscriptionCost($userId) {
        try {
            // Get user's current subscription
            $stmt = $this->pdo->prepare("
                SELECT subscription_type, stripe_customer_id 
                FROM users 
                WHERE id = ? AND subscription_status = 'active'
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user || $user['subscription_type'] === 'one_time') {
                return true; // No subscription to update or one-time payment
            }
            
            $newMonthlyCost = $this->calculateMonthlyCost($userId);
            
            // Update Stripe subscription if user has monthly/yearly plan
            if ($user['stripe_customer_id'] && in_array($user['subscription_type'], ['monthly', 'yearly'])) {
                // Note: In a real implementation, you'd update the Stripe subscription here
                // For now, we'll just log the cost change
                error_log("User {$userId} subscription cost updated to €{$newMonthlyCost}/month for bank accounts");
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error updating subscription cost: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get bank account summary for user
     * @param int $userId User ID
     * @return array Summary data
     */
    public function getBankAccountSummary($userId) {
        try {
            $bankAccounts = $this->getUserBankAccounts($userId);
            $monthlyCost = $this->calculateMonthlyCost($userId);
            $canAdd = $this->canAddBankAccount($userId);
            
            return [
                'bank_accounts' => $bankAccounts,
                'total_accounts' => count($bankAccounts),
                'monthly_cost' => $monthlyCost,
                'cost_per_account' => 3.00,
                'can_add_more' => $canAdd['can_add'],
                'add_message' => $canAdd['message']
            ];
            
        } catch (Exception $e) {
            error_log("Error getting bank account summary: " . $e->getMessage());
            return [
                'bank_accounts' => [],
                'total_accounts' => 0,
                'monthly_cost' => 0,
                'cost_per_account' => 3.00,
                'can_add_more' => false,
                'add_message' => 'Error loading data'
            ];
        }
    }
    
    /**
     * Get pricing information for bank accounts
     * @return array Pricing structure
     */
    public function getPricingInfo() {
        return [
            'base_cost' => 3.00,
            'currency' => 'EUR',
            'billing_period' => 'monthly',
            'description' => '€3 per connected bank account per month',
            'minimum_cost' => 3.00
        ];
    }
}
?>
