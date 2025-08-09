<?php
/**
 * TRANSACTION SYNC MANAGER
 * Handles delayed/syncing of historical bank transaction data
 */

class TransactionSyncManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Check if a bank connection has sufficient transaction data for analysis
     */
    public function hassufficientData($userId, $provider, $accountId) {
        try {
            // Get transaction count and quality metrics
            $metrics = $this->getTransactionMetrics($userId, $provider, $accountId);
            
            // Define minimum requirements for subscription analysis
            $requirements = [
                'min_transactions' => 10,
                'min_outgoing_transactions' => 5,
                'min_date_range_days' => 30,
                'min_unique_merchants' => 3
            ];
            
            return [
                'sufficient' => (
                    $metrics['total_transactions'] >= $requirements['min_transactions'] &&
                    $metrics['outgoing_transactions'] >= $requirements['min_outgoing_transactions'] &&
                    $metrics['date_range_days'] >= $requirements['min_date_range_days'] &&
                    $metrics['unique_merchants'] >= $requirements['min_unique_merchants']
                ),
                'metrics' => $metrics,
                'requirements' => $requirements,
                'missing' => $this->calculateMissing($metrics, $requirements)
            ];
            
        } catch (Exception $e) {
            error_log("TransactionSyncManager: Error checking data sufficiency: " . $e->getMessage());
            return [
                'sufficient' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get transaction metrics for a bank connection
     */
    private function getTransactionMetrics($userId, $provider, $accountId) {
        if ($provider === 'gocardless') {
            return $this->getGoCardlessMetrics($userId, $accountId);
        } elseif ($provider === 'stripe') {
            return $this->getStripeMetrics($userId, $accountId);
        }
        
        throw new Exception("Unsupported provider: $provider");
    }
    
    /**
     * Get GoCardless transaction metrics
     */
    private function getGoCardlessMetrics($userId, $accountId) {
        require_once 'gocardless_financial_service.php';
        
        $gocardlessService = new GoCardlessFinancialService($this->pdo);
        
        // Use reflection to access private methods
        $reflection = new ReflectionClass($gocardlessService);
        $getAccessTokenMethod = $reflection->getMethod('getAccessToken');
        $getAccessTokenMethod->setAccessible(true);
        $accessToken = $getAccessTokenMethod->invoke($gocardlessService);
        
        // Get transactions from GoCardless API
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://bankaccountdata.gocardless.com/api/v2/accounts/' . $accountId . '/transactions/?date_from=' . date('Y-m-d', strtotime('-365 days')),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/json'
            ]
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($httpCode !== 200) {
            throw new Exception("GoCardless API error: HTTP $httpCode");
        }
        
        $data = json_decode($response, true);
        $transactions = $data['transactions'] ?? [];
        
        // Analyze transaction quality
        $metrics = [
            'total_transactions' => 0,
            'valid_transactions' => 0,
            'outgoing_transactions' => 0,
            'incoming_transactions' => 0,
            'unique_merchants' => 0,
            'date_range_days' => 0,
            'oldest_transaction' => null,
            'newest_transaction' => null,
            'has_amounts' => false,
            'has_merchants' => false
        ];
        
        $merchants = [];
        $dates = [];
        
        foreach ($transactions as $transaction) {
            if ($transaction === null) continue;
            
            $metrics['valid_transactions']++;
            
            // Check for amount data
            if (isset($transaction['transactionAmount']['amount'])) {
                $metrics['has_amounts'] = true;
                $amount = floatval($transaction['transactionAmount']['amount']);
                
                if ($amount < 0) {
                    $metrics['outgoing_transactions']++;
                } else {
                    $metrics['incoming_transactions']++;
                }
            }
            
            // Check for merchant data
            $merchant = null;
            if (isset($transaction['creditorName']) && !empty($transaction['creditorName'])) {
                $merchant = $transaction['creditorName'];
                $metrics['has_merchants'] = true;
            } elseif (isset($transaction['remittanceInformationUnstructured']) && !empty($transaction['remittanceInformationUnstructured'])) {
                $merchant = substr($transaction['remittanceInformationUnstructured'], 0, 50);
                $metrics['has_merchants'] = true;
            }
            
            if ($merchant) {
                $merchants[$merchant] = true;
            }
            
            // Check for date data
            $date = $transaction['bookingDate'] ?? $transaction['valueDate'] ?? null;
            if ($date) {
                $dates[] = $date;
            }
        }
        
        $metrics['total_transactions'] = count($transactions);
        $metrics['unique_merchants'] = count($merchants);
        
        if (!empty($dates)) {
            sort($dates);
            $metrics['oldest_transaction'] = $dates[0];
            $metrics['newest_transaction'] = end($dates);
            $metrics['date_range_days'] = (strtotime($metrics['newest_transaction']) - strtotime($metrics['oldest_transaction'])) / (24 * 60 * 60);
        }
        
        return $metrics;
    }
    
    /**
     * Get Stripe transaction metrics (placeholder)
     */
    private function getStripeMetrics($userId, $accountId) {
        // TODO: Implement Stripe metrics when Stripe integration is ready
        return [
            'total_transactions' => 0,
            'valid_transactions' => 0,
            'outgoing_transactions' => 0,
            'incoming_transactions' => 0,
            'unique_merchants' => 0,
            'date_range_days' => 0,
            'oldest_transaction' => null,
            'newest_transaction' => null,
            'has_amounts' => false,
            'has_merchants' => false
        ];
    }
    
    /**
     * Calculate what's missing for sufficient data
     */
    private function calculateMissing($metrics, $requirements) {
        $missing = [];
        
        if ($metrics['total_transactions'] < $requirements['min_transactions']) {
            $missing[] = 'Need ' . ($requirements['min_transactions'] - $metrics['total_transactions']) . ' more transactions';
        }
        
        if ($metrics['outgoing_transactions'] < $requirements['min_outgoing_transactions']) {
            $missing[] = 'Need ' . ($requirements['min_outgoing_transactions'] - $metrics['outgoing_transactions']) . ' more outgoing payments';
        }
        
        if ($metrics['date_range_days'] < $requirements['min_date_range_days']) {
            $missing[] = 'Need ' . ($requirements['min_date_range_days'] - $metrics['date_range_days']) . ' more days of transaction history';
        }
        
        if ($metrics['unique_merchants'] < $requirements['min_unique_merchants']) {
            $missing[] = 'Need ' . ($requirements['min_unique_merchants'] - $metrics['unique_merchants']) . ' more unique merchants';
        }
        
        if (!$metrics['has_amounts']) {
            $missing[] = 'Transaction amounts not available yet';
        }
        
        if (!$metrics['has_merchants']) {
            $missing[] = 'Merchant information not available yet';
        }
        
        return $missing;
    }
    
    /**
     * Schedule a bank connection for re-scanning when more data becomes available
     */
    public function scheduleRescan($userId, $provider, $accountId, $delayHours = 24) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO transaction_sync_schedule 
                (user_id, provider, account_id, next_scan_at, created_at, status) 
                VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? HOUR), NOW(), 'pending')
                ON DUPLICATE KEY UPDATE 
                next_scan_at = DATE_ADD(NOW(), INTERVAL ? HOUR),
                status = 'pending',
                retry_count = retry_count + 1
            ");
            $stmt->execute([$userId, $provider, $accountId, $delayHours, $delayHours]);
            
            return [
                'success' => true,
                'next_scan' => date('Y-m-d H:i:s', strtotime("+$delayHours hours")),
                'message' => "Scheduled re-scan in $delayHours hours"
            ];
            
        } catch (Exception $e) {
            error_log("TransactionSyncManager: Error scheduling rescan: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get pending rescans that are ready to execute
     */
    public function getPendingRescans() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT user_id, provider, account_id, retry_count, created_at
                FROM transaction_sync_schedule 
                WHERE status = 'pending' 
                AND next_scan_at <= NOW()
                AND retry_count < 10
                ORDER BY next_scan_at ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("TransactionSyncManager: Error getting pending rescans: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mark a rescan as completed
     */
    public function markRescanCompleted($userId, $provider, $accountId, $success = true) {
        try {
            $status = $success ? 'completed' : 'failed';
            $stmt = $this->pdo->prepare("
                UPDATE transaction_sync_schedule 
                SET status = ?, completed_at = NOW() 
                WHERE user_id = ? AND provider = ? AND account_id = ?
            ");
            $stmt->execute([$status, $userId, $provider, $accountId]);
            
        } catch (Exception $e) {
            error_log("TransactionSyncManager: Error marking rescan completed: " . $e->getMessage());
        }
    }
    
    /**
     * Create the transaction sync schedule table if it doesn't exist
     */
    public function createSyncTable() {
        try {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS transaction_sync_schedule (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    provider VARCHAR(50) NOT NULL,
                    account_id VARCHAR(255) NOT NULL,
                    next_scan_at DATETIME NOT NULL,
                    created_at DATETIME NOT NULL,
                    completed_at DATETIME NULL,
                    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
                    retry_count INT DEFAULT 0,
                    UNIQUE KEY unique_account (user_id, provider, account_id),
                    INDEX idx_next_scan (next_scan_at, status)
                )
            ");
            
            return true;
            
        } catch (Exception $e) {
            error_log("TransactionSyncManager: Error creating sync table: " . $e->getMessage());
            return false;
        }
    }
}
?>
