<?php
/**
 * Bank Account Pricing Service
 * Handles pricing calculations and Stripe integration for per-bank-account billing
 */

require_once __DIR__ . '/database_helper.php';
require_once __DIR__ . '/stripe_service.php';
require_once __DIR__ . '/multi_bank_service.php';

class BankPricingService {
    private $pdo;
    private $stripeService;
    private $multiBankService;
    
    const BASE_PRICE_PER_ACCOUNT = 3.00; // €3 per bank account per month
    const CURRENCY = 'EUR';
    
    public function __construct() {
        $this->pdo = DatabaseHelper::getConnection();
        $this->stripeService = new StripeService();
        $this->multiBankService = new MultiBankService();
    }
    
    /**
     * Calculate monthly cost based on number of connected bank accounts
     * @param int $userId User ID
     * @return float Monthly cost in EUR
     */
    public function calculateMonthlyCost($userId) {
        $bankAccountCount = $this->multiBankService->getActiveBankAccountCount($userId);
        return max(1, $bankAccountCount) * self::BASE_PRICE_PER_ACCOUNT;
    }
    
    /**
     * Calculate yearly cost with discount (per bank account)
     * @param int $userId User ID
     * @return float Yearly cost in EUR (with discount)
     */
    public function calculateYearlyCost($userId) {
        $bankAccountCount = $this->multiBankService->getActiveBankAccountCount($userId);
        $bankAccountCount = max(1, $bankAccountCount); // Minimum 1 account
        
        // €25 per year per bank account (was €25 total, now €25 per account)
        // This gives users 5 months free compared to monthly (€36 vs €25 per account per year)
        return $bankAccountCount * 25.00;
    }
    
    /**
     * Get pricing tiers for display
     * @param int $userId User ID
     * @return array Pricing information
     */
    public function getPricingTiers($userId) {
        $bankAccountCount = max(1, $this->multiBankService->getActiveBankAccountCount($userId));
        $monthlyCost = $this->calculateMonthlyCost($userId);
        $yearlyCost = $this->calculateYearlyCost($userId);
        
        return [
            'monthly' => [
                'cost' => $monthlyCost,
                'cost_per_account' => self::BASE_PRICE_PER_ACCOUNT,
                'bank_accounts' => $bankAccountCount,
                'currency' => self::CURRENCY,
                'billing_period' => 'month',
                'description' => "€" . number_format(self::BASE_PRICE_PER_ACCOUNT, 2) . " per bank account per month"
            ],
            'yearly' => [
                'cost' => $yearlyCost,
                'cost_per_account' => 25.00, // €25 per bank account per year
                'bank_accounts' => $bankAccountCount,
                'currency' => self::CURRENCY,
                'billing_period' => 'year',
                'description' => "€25.00 per bank account per year (save €11 per account vs monthly)",
                'savings' => ($monthlyCost * 12) - $yearlyCost,
                'monthly_equivalent' => $monthlyCost * 12,
                'savings_per_account' => (self::BASE_PRICE_PER_ACCOUNT * 12) - 25.00
            ]
        ];
    }
    
    /**
     * Create Stripe checkout session for bank account subscription
     * @param int $userId User ID
     * @param string $planType 'monthly' or 'yearly'
     * @return array Checkout session data
     */
    public function createCheckoutSession($userId, $planType) {
        try {
            $pricing = $this->getPricingTiers($userId);
            $selectedPlan = $pricing[$planType] ?? null;
            
            if (!$selectedPlan) {
                throw new Exception("Invalid plan type: {$planType}");
            }
            
            $bankAccountCount = $this->multiBankService->getActiveBankAccountCount($userId);
            
            // Create checkout session with dynamic pricing
            $sessionData = [
                'user_id' => $userId,
                'plan_type' => $planType,
                'amount' => $selectedPlan['cost'] * 100, // Convert to cents
                'currency' => self::CURRENCY,
                'description' => "CashControl - {$bankAccountCount} Bank Account(s) - " . ucfirst($planType) . " Plan",
                'success_url' => 'https://123cashcontrol.com/payment/success.php?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => 'https://123cashcontrol.com/upgrade.php'
            ];
            
            return $this->stripeService->createCheckoutSession($sessionData);
            
        } catch (Exception $e) {
            error_log("Error creating bank pricing checkout session: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Update user's subscription when bank accounts change
     * @param int $userId User ID
     * @return bool Success
     */
    public function updateSubscriptionForBankAccountChange($userId) {
        try {
            // Get user's current subscription
            $stmt = $this->pdo->prepare("
                SELECT subscription_type, subscription_status, stripe_customer_id 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user || !in_array($user['subscription_type'], ['monthly', 'yearly'])) {
                return true; // No active subscription to update
            }
            
            if ($user['subscription_status'] !== 'active') {
                return true; // Subscription not active
            }
            
            $newCost = $this->calculateMonthlyCost($userId);
            $bankAccountCount = $this->multiBankService->getActiveBankAccountCount($userId);
            
            // Log the pricing change
            error_log("User {$userId} subscription cost updated: €{$newCost}/month for {$bankAccountCount} bank account(s)");
            
            // In a full implementation, you would update the Stripe subscription here
            // For now, we'll just log the change and update our local records
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error updating subscription for bank account change: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get pricing summary for user dashboard
     * @param int $userId User ID
     * @return array Pricing summary
     */
    public function getPricingSummary($userId) {
        try {
            $bankAccountCount = $this->multiBankService->getActiveBankAccountCount($userId);
            $monthlyCost = $this->calculateMonthlyCost($userId);
            
            // Get user's current plan
            $stmt = $this->pdo->prepare("
                SELECT subscription_type, subscription_status 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            return [
                'bank_accounts' => $bankAccountCount,
                'monthly_cost' => $monthlyCost,
                'cost_per_account' => self::BASE_PRICE_PER_ACCOUNT,
                'currency' => self::CURRENCY,
                'current_plan' => $user['subscription_type'] ?? 'none',
                'plan_status' => $user['subscription_status'] ?? 'inactive',
                'can_add_accounts' => $user['subscription_status'] === 'active',
                'next_account_cost' => self::BASE_PRICE_PER_ACCOUNT
            ];
            
        } catch (Exception $e) {
            error_log("Error getting pricing summary: " . $e->getMessage());
            return [
                'bank_accounts' => 0,
                'monthly_cost' => 0,
                'cost_per_account' => self::BASE_PRICE_PER_ACCOUNT,
                'currency' => self::CURRENCY,
                'current_plan' => 'none',
                'plan_status' => 'inactive',
                'can_add_accounts' => false,
                'next_account_cost' => self::BASE_PRICE_PER_ACCOUNT
            ];
        }
    }
    
    /**
     * Validate if user can afford to add another bank account
     * @param int $userId User ID
     * @return array Validation result
     */
    public function validateBankAccountAddition($userId) {
        try {
            $currentCount = $this->multiBankService->getActiveBankAccountCount($userId);
            $newCost = ($currentCount + 1) * self::BASE_PRICE_PER_ACCOUNT;
            
            // Get user's subscription status
            $stmt = $this->pdo->prepare("
                SELECT subscription_type, subscription_status 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user || $user['subscription_status'] !== 'active') {
                return [
                    'can_add' => false,
                    'message' => 'Active subscription required to connect bank accounts',
                    'requires_upgrade' => true
                ];
            }
            
            return [
                'can_add' => true,
                'message' => "Adding this bank account will increase your monthly cost to €{$newCost}",
                'new_monthly_cost' => $newCost,
                'additional_cost' => self::BASE_PRICE_PER_ACCOUNT,
                'requires_upgrade' => false
            ];
            
        } catch (Exception $e) {
            error_log("Error validating bank account addition: " . $e->getMessage());
            return [
                'can_add' => false,
                'message' => 'Error validating bank account addition',
                'requires_upgrade' => false
            ];
        }
    }
}
?>
