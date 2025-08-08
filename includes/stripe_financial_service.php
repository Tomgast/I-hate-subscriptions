<?php
/**
 * STRIPE FINANCIAL CONNECTIONS SERVICE
 * Replacement for TrueLayer bank integration
 */

require_once __DIR__ . '/stripe-sdk.php';
require_once __DIR__ . '/../config/db_config.php';

class StripeFinancialService {
    private $pdo;
    private $baseUrl;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->baseUrl = $this->getBaseUrl();
    }
    
    /**
     * Get the base URL for callbacks
     */
    private function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }
    
    /**
     * Create Financial Connections Session
     */
    public function createBankConnectionSession($userId) {
        try {
            // Create session in Stripe
            $session = \Stripe\FinancialConnections\Session::create([
                'account_holder' => [
                    'type' => 'consumer',
                ],
                'permissions' => [
                    'payment_method',
                    'balances',
                    'transactions'
                ],
                'filters' => [
                    'countries' => ['US', 'GB', 'NL', 'DE', 'FR', 'ES', 'IT'] // Add your supported countries
                ],
                'return_url' => $this->baseUrl . '/bank/stripe-callback.php'
            ]);
            
            // Store session in database
            $stmt = $this->pdo->prepare("
                INSERT INTO bank_connection_sessions 
                (user_id, stripe_session_id, session_url, status, created_at) 
                VALUES (?, ?, ?, 'created', NOW())
            ");
            $stmt->execute([
                $userId,
                $session->id,
                $session->hosted_auth_url
            ]);
            
            return [
                'success' => true,
                'session_id' => $session->id,
                'auth_url' => $session->hosted_auth_url,
                'message' => 'Bank connection session created successfully'
            ];
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log("Stripe Financial Connections error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to create bank connection: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            error_log("Database error in createBankConnectionSession: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Database error occurred'
            ];
        }
    }
    
    /**
     * Handle callback from Stripe Financial Connections
     */
    public function handleCallback($sessionId) {
        try {
            // Retrieve session from Stripe
            $session = \Stripe\FinancialConnections\Session::retrieve($sessionId);
            
            // Update session status in database
            $stmt = $this->pdo->prepare("
                UPDATE bank_connection_sessions 
                SET status = ?, updated_at = NOW() 
                WHERE stripe_session_id = ?
            ");
            $stmt->execute([$session->status, $sessionId]);
            
            if ($session->status === 'completed') {
                // Get connected accounts
                $accounts = $session->accounts->data;
                
                foreach ($accounts as $account) {
                    $this->storeBankAccount($sessionId, $account);
                }
                
                return [
                    'success' => true,
                    'status' => 'completed',
                    'accounts_connected' => count($accounts),
                    'message' => 'Bank accounts connected successfully'
                ];
            }
            
            return [
                'success' => true,
                'status' => $session->status,
                'message' => 'Session status: ' . $session->status
            ];
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log("Stripe callback error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to process callback: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Store bank account information
     */
    private function storeBankAccount($sessionId, $account) {
        try {
            // Get user ID from session
            $stmt = $this->pdo->prepare("SELECT user_id FROM bank_connection_sessions WHERE stripe_session_id = ?");
            $stmt->execute([$sessionId]);
            $session = $stmt->fetch();
            
            if (!$session) {
                throw new Exception('Session not found');
            }
            
            // Store or update bank connection
            $stmt = $this->pdo->prepare("
                INSERT INTO bank_connections 
                (user_id, provider, connection_id, account_id, account_name, account_type, 
                 balance, currency, status, connected_at, token_expires_at, connection_status) 
                VALUES (?, 'stripe', ?, ?, ?, ?, ?, ?, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY), 'connected')
                ON DUPLICATE KEY UPDATE
                account_name = VALUES(account_name),
                account_type = VALUES(account_type),
                balance = VALUES(balance),
                currency = VALUES(currency),
                status = VALUES(status),
                connection_status = VALUES(connection_status),
                token_expires_at = VALUES(token_expires_at)
            ");
            
            $balance = 0;
            $currency = 'USD';
            
            // Get balance if available
            if (isset($account->balance)) {
                $balance = $account->balance->current->amount ?? 0;
                $currency = $account->balance->current->currency ?? 'USD';
            }
            
            $stmt->execute([
                $session['user_id'],
                $sessionId,
                $account->id,
                $account->display_name ?? 'Connected Account',
                $account->subcategory ?? 'checking',
                $balance,
                strtoupper($currency)
            ]);
            
        } catch (Exception $e) {
            error_log("Error storing bank account: " . $e->getMessage());
        }
    }
    
    /**
     * Get user's bank connections
     */
    public function getUserBankConnections($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM bank_connections 
                WHERE user_id = ? AND provider = 'stripe' AND status = 'active'
                ORDER BY connected_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Error getting bank connections: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Scan for subscriptions in bank transactions
     */
    public function scanForSubscriptions($userId) {
        try {
            $connections = $this->getUserBankConnections($userId);
            $foundSubscriptions = [];
            
            foreach ($connections as $connection) {
                $transactions = $this->getAccountTransactions($connection['account_id']);
                $subscriptions = $this->analyzeTransactionsForSubscriptions($transactions, $userId, $connection['id']);
                $foundSubscriptions = array_merge($foundSubscriptions, $subscriptions);
            }
            
            // Store scan record
            $stmt = $this->pdo->prepare("
                INSERT INTO bank_scans 
                (user_id, provider, scan_type, subscriptions_found, scan_status, scanned_at) 
                VALUES (?, 'stripe', 'full', ?, 'completed', NOW())
            ");
            $stmt->execute([$userId, count($foundSubscriptions)]);
            
            return [
                'success' => true,
                'subscriptions_found' => count($foundSubscriptions),
                'subscriptions' => $foundSubscriptions,
                'message' => 'Subscription scan completed successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Error scanning for subscriptions: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to scan for subscriptions'
            ];
        }
    }
    
    /**
     * Get transactions for an account
     */
    private function getAccountTransactions($accountId, $limit = 100) {
        try {
            $transactions = \Stripe\FinancialConnections\Transaction::all([
                'account' => $accountId,
                'limit' => $limit
            ]);
            
            return $transactions->data;
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log("Error getting transactions: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Analyze transactions for subscription patterns
     */
    private function analyzeTransactionsForSubscriptions($transactions, $userId, $connectionId) {
        $subscriptions = [];
        $merchantGroups = [];
        
        // Group transactions by merchant
        foreach ($transactions as $transaction) {
            if ($transaction->amount < 0) { // Outgoing transactions only
                $merchant = $this->normalizeMerchantName($transaction->description);
                $amount = abs($transaction->amount) / 100; // Convert from cents
                $date = date('Y-m-d', $transaction->created);
                
                if (!isset($merchantGroups[$merchant])) {
                    $merchantGroups[$merchant] = [];
                }
                
                $merchantGroups[$merchant][] = [
                    'amount' => $amount,
                    'date' => $date,
                    'description' => $transaction->description
                ];
            }
        }
        
        // Analyze each merchant for subscription patterns
        foreach ($merchantGroups as $merchant => $transactions) {
            if (count($transactions) >= 2) { // Need at least 2 transactions to detect pattern
                $subscription = $this->detectSubscriptionPattern($merchant, $transactions, $userId, $connectionId);
                if ($subscription) {
                    $subscriptions[] = $subscription;
                }
            }
        }
        
        return $subscriptions;
    }
    
    /**
     * Normalize merchant name for grouping
     */
    private function normalizeMerchantName($description) {
        // Remove common payment processor prefixes
        $description = preg_replace('/^(PAYPAL|SQ|TST)\s*\*\s*/i', '', $description);
        
        // Remove transaction IDs and reference numbers
        $description = preg_replace('/\s+\d{6,}/', '', $description);
        $description = preg_replace('/\s+[A-Z0-9]{8,}/', '', $description);
        
        // Take first few words as merchant name
        $words = explode(' ', trim($description));
        return implode(' ', array_slice($words, 0, 3));
    }
    
    /**
     * Detect subscription pattern in merchant transactions
     */
    private function detectSubscriptionPattern($merchant, $transactions, $userId, $connectionId) {
        // Sort by date
        usort($transactions, function($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });
        
        // Look for recurring amounts
        $amounts = array_column($transactions, 'amount');
        $amountCounts = array_count_values($amounts);
        
        // Find most common amount (likely subscription amount)
        $subscriptionAmount = array_keys($amountCounts, max($amountCounts))[0];
        
        // Filter transactions with subscription amount
        $recurringTransactions = array_filter($transactions, function($t) use ($subscriptionAmount) {
            return $t['amount'] == $subscriptionAmount;
        });
        
        if (count($recurringTransactions) >= 2) {
            // Calculate billing cycle
            $dates = array_column($recurringTransactions, 'date');
            $intervals = [];
            
            for ($i = 1; $i < count($dates); $i++) {
                $interval = (strtotime($dates[$i]) - strtotime($dates[$i-1])) / (24 * 60 * 60);
                $intervals[] = $interval;
            }
            
            $avgInterval = array_sum($intervals) / count($intervals);
            $billingCycle = $this->determineBillingCycle($avgInterval);
            
            // Store subscription
            try {
                $stmt = $this->pdo->prepare("
                    INSERT INTO subscriptions 
                    (user_id, service_name, amount, currency, billing_cycle, status, 
                     next_payment_date, detected_via, bank_connection_id, created_at) 
                    VALUES (?, ?, ?, 'USD', ?, 'active', ?, 'stripe_scan', ?, NOW())
                    ON DUPLICATE KEY UPDATE
                    amount = VALUES(amount),
                    billing_cycle = VALUES(billing_cycle),
                    next_payment_date = VALUES(next_payment_date),
                    updated_at = NOW()
                ");
                
                $nextPayment = $this->calculateNextPayment(end($dates), $billingCycle);
                
                $stmt->execute([
                    $userId,
                    $merchant,
                    $subscriptionAmount,
                    $billingCycle,
                    $nextPayment,
                    $connectionId
                ]);
                
                return [
                    'service_name' => $merchant,
                    'amount' => $subscriptionAmount,
                    'billing_cycle' => $billingCycle,
                    'next_payment_date' => $nextPayment,
                    'transaction_count' => count($recurringTransactions)
                ];
                
            } catch (Exception $e) {
                error_log("Error storing subscription: " . $e->getMessage());
            }
        }
        
        return null;
    }
    
    /**
     * Determine billing cycle from average interval
     */
    private function determineBillingCycle($avgInterval) {
        if ($avgInterval >= 350 && $avgInterval <= 380) return 'yearly';
        if ($avgInterval >= 85 && $avgInterval <= 95) return 'quarterly';
        if ($avgInterval >= 28 && $avgInterval <= 32) return 'monthly';
        if ($avgInterval >= 13 && $avgInterval <= 15) return 'biweekly';
        if ($avgInterval >= 6 && $avgInterval <= 8) return 'weekly';
        
        return 'monthly'; // Default fallback
    }
    
    /**
     * Calculate next payment date
     */
    private function calculateNextPayment($lastPaymentDate, $billingCycle) {
        $date = new DateTime($lastPaymentDate);
        
        switch ($billingCycle) {
            case 'weekly':
                $date->add(new DateInterval('P7D'));
                break;
            case 'biweekly':
                $date->add(new DateInterval('P14D'));
                break;
            case 'monthly':
                $date->add(new DateInterval('P1M'));
                break;
            case 'quarterly':
                $date->add(new DateInterval('P3M'));
                break;
            case 'yearly':
                $date->add(new DateInterval('P1Y'));
                break;
        }
        
        return $date->format('Y-m-d');
    }
    
    /**
     * Get connection status
     */
    public function getConnectionStatus($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as connection_count,
                       MAX(connected_at) as last_connected
                FROM bank_connections 
                WHERE user_id = ? AND provider = 'stripe' AND status = 'active'
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return [
                'has_connections' => $result['connection_count'] > 0,
                'connection_count' => $result['connection_count'],
                'last_connected' => $result['last_connected']
            ];
            
        } catch (Exception $e) {
            error_log("Error getting connection status: " . $e->getMessage());
            return [
                'has_connections' => false,
                'connection_count' => 0,
                'last_connected' => null
            ];
        }
    }
}
?>
