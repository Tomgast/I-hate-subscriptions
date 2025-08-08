<?php
/**
 * BANK SCAN SCHEDULER
 * Handles automatic weekly bank scans for subscription users
 */

require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/bank_service.php';
require_once __DIR__ . '/plan_manager.php';

class BankScheduler {
    private $pdo;
    private $bankService;
    private $planManager;
    
    public function __construct() {
        $this->pdo = getDBConnection();
        $this->bankService = new BankService();
        $this->planManager = getPlanManager();
    }
    
    /**
     * Check and schedule automatic scans for all subscription users
     * Should be called by cron job daily
     */
    public function processAutomaticScans() {
        $results = [
            'checked_users' => 0,
            'scans_scheduled' => 0,
            'errors' => []
        ];
        
        try {
            // Get all active subscription users (monthly/yearly)
            $stmt = $this->pdo->prepare("
                SELECT id, email, name, subscription_type 
                FROM users 
                WHERE subscription_type IN ('monthly', 'yearly') 
                AND subscription_status = 'active'
                AND subscription_expires_at > NOW()
            ");
            
            $stmt->execute();
            $users = $stmt->fetchAll();
            
            foreach ($users as $user) {
                $results['checked_users']++;
                
                try {
                    // Check if user needs automatic scan
                    if ($this->bankService->needsAutomaticScan($user['id'])) {
                        // Schedule automatic scan
                        if ($this->bankService->scheduleAutomaticScan($user['id'])) {
                            $results['scans_scheduled']++;
                            error_log("Scheduled automatic scan for user {$user['id']} ({$user['email']})");
                        } else {
                            $results['errors'][] = "Failed to schedule scan for user {$user['id']}";
                        }
                    }
                } catch (Exception $e) {
                    $results['errors'][] = "Error processing user {$user['id']}: " . $e->getMessage();
                }
            }
            
        } catch (Exception $e) {
            $results['errors'][] = "Database error: " . $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Process scheduled scans (background execution)
     * Should be called by separate background process
     */
    public function executeScheduledScans() {
        $results = [
            'processed_scans' => 0,
            'successful_scans' => 0,
            'failed_scans' => 0,
            'errors' => []
        ];
        
        try {
            // Get all scheduled scans
            $stmt = $this->pdo->prepare("
                SELECT bs.*, u.email, u.name 
                FROM bank_scans bs
                JOIN users u ON bs.user_id = u.id
                WHERE bs.status = 'scheduled'
                AND bs.started_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
                ORDER BY bs.started_at ASC
                LIMIT 10
            ");
            
            $stmt->execute();
            $scheduledScans = $stmt->fetchAll();
            
            foreach ($scheduledScans as $scan) {
                $results['processed_scans']++;
                
                try {
                    // Mark scan as in progress
                    $this->updateScanStatus($scan['id'], 'in_progress');
                    
                    // Execute automatic scan
                    $success = $this->executeAutomaticScan($scan['user_id'], $scan['id']);
                    
                    if ($success) {
                        $results['successful_scans']++;
                        error_log("Completed automatic scan for user {$scan['user_id']} ({$scan['email']})");
                    } else {
                        $results['failed_scans']++;
                        $this->bankService->markScanFailed($scan['id'], 'Automatic scan execution failed');
                    }
                    
                } catch (Exception $e) {
                    $results['failed_scans']++;
                    $results['errors'][] = "Error executing scan {$scan['id']}: " . $e->getMessage();
                    $this->bankService->markScanFailed($scan['id'], $e->getMessage());
                }
            }
            
        } catch (Exception $e) {
            $results['errors'][] = "Database error: " . $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Execute automatic scan for user
     * @param int $userId User ID
     * @param int $scanId Scan ID
     * @return bool Success
     */
    private function executeAutomaticScan($userId, $scanId) {
        try {
            // Get user's latest bank connection
            $stmt = $this->pdo->prepare("
                SELECT access_token, refresh_token, bank_name 
                FROM bank_connections 
                WHERE user_id = ? AND is_active = 1 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            
            $stmt->execute([$userId]);
            $connection = $stmt->fetch();
            
            if (!$connection) {
                // No active bank connection - mark as failed with specific message
                $this->bankService->markScanFailed($scanId, 'No active bank connection found. User needs to reconnect their bank.');
                return false;
            }
            
            // Get bank accounts
            $accounts = $this->bankService->getBankAccounts($connection['access_token']);
            
            if (!$accounts) {
                // Token might be expired, try refresh
                $newToken = $this->refreshBankToken($connection['refresh_token']);
                if ($newToken) {
                    $accounts = $this->bankService->getBankAccounts($newToken);
                    // Update stored token
                    $this->updateBankToken($userId, $newToken);
                } else {
                    $this->bankService->markScanFailed($scanId, 'Bank connection expired. User needs to reconnect their bank.');
                    return false;
                }
            }
            
            $allSubscriptions = [];
            
            // Analyze transactions from all accounts
            foreach ($accounts as $account) {
                $transactions = $this->bankService->getAccountTransactions(
                    $connection['access_token'], 
                    $account['account_id'],
                    date('Y-m-d', strtotime('-3 months')), // Last 3 months
                    date('Y-m-d')
                );
                
                if ($transactions) {
                    $subscriptions = $this->bankService->detectSubscriptions($transactions);
                    $allSubscriptions = array_merge($allSubscriptions, $subscriptions);
                }
            }
            
            // Complete scan with results
            return $this->bankService->completeScan($scanId, $allSubscriptions);
            
        } catch (Exception $e) {
            error_log("Error in automatic scan execution: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update scan status
     * @param int $scanId Scan ID
     * @param string $status New status
     */
    private function updateScanStatus($scanId, $status) {
        $stmt = $this->pdo->prepare("
            UPDATE bank_scans SET 
                status = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([$status, $scanId]);
    }
    
    /**
     * Refresh bank token
     * @param string $refreshToken Refresh token
     * @return string|false New access token or false
     */
    private function refreshBankToken($refreshToken) {
        // Implementation would depend on TrueLayer's token refresh API
        // This is a placeholder for the actual token refresh logic
        try {
            // TrueLayer token refresh API call would go here
            // For now, return false to indicate token refresh failed
            return false;
        } catch (Exception $e) {
            error_log("Token refresh failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update stored bank token
     * @param int $userId User ID
     * @param string $newToken New access token
     */
    private function updateBankToken($userId, $newToken) {
        $stmt = $this->pdo->prepare("
            UPDATE bank_connections SET 
                access_token = ?,
                updated_at = NOW()
            WHERE user_id = ? AND is_active = 1
        ");
        
        $stmt->execute([$newToken, $userId]);
    }
    
    /**
     * Get scheduler statistics
     * @return array Statistics
     */
    public function getSchedulerStats() {
        try {
            $stats = [];
            
            // Total subscription users
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total_subscription_users
                FROM users 
                WHERE subscription_type IN ('monthly', 'yearly') 
                AND subscription_status = 'active'
            ");
            $stmt->execute();
            $stats['total_subscription_users'] = $stmt->fetchColumn();
            
            // Pending scheduled scans
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as pending_scans
                FROM bank_scans 
                WHERE status = 'scheduled'
            ");
            $stmt->execute();
            $stats['pending_scans'] = $stmt->fetchColumn();
            
            // Scans completed in last 7 days
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as recent_scans
                FROM bank_scans 
                WHERE status = 'completed' 
                AND completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $stmt->execute();
            $stats['recent_scans'] = $stmt->fetchColumn();
            
            // Users needing scans
            $usersNeedingScans = 0;
            $stmt = $this->pdo->prepare("
                SELECT id FROM users 
                WHERE subscription_type IN ('monthly', 'yearly') 
                AND subscription_status = 'active'
            ");
            $stmt->execute();
            $users = $stmt->fetchAll();
            
            foreach ($users as $user) {
                if ($this->bankService->needsAutomaticScan($user['id'])) {
                    $usersNeedingScans++;
                }
            }
            
            $stats['users_needing_scans'] = $usersNeedingScans;
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Error getting scheduler stats: " . $e->getMessage());
            return [
                'total_subscription_users' => 0,
                'pending_scans' => 0,
                'recent_scans' => 0,
                'users_needing_scans' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
}

/**
 * CLI entry point for cron jobs
 */
if (php_sapi_name() === 'cli') {
    $scheduler = new BankScheduler();
    
    $action = $argv[1] ?? 'schedule';
    
    switch ($action) {
        case 'schedule':
            echo "Processing automatic scans...\n";
            $results = $scheduler->processAutomaticScans();
            echo "Checked {$results['checked_users']} users, scheduled {$results['scans_scheduled']} scans\n";
            if (!empty($results['errors'])) {
                echo "Errors: " . implode(', ', $results['errors']) . "\n";
            }
            break;
            
        case 'execute':
            echo "Executing scheduled scans...\n";
            $results = $scheduler->executeScheduledScans();
            echo "Processed {$results['processed_scans']} scans, {$results['successful_scans']} successful, {$results['failed_scans']} failed\n";
            if (!empty($results['errors'])) {
                echo "Errors: " . implode(', ', $results['errors']) . "\n";
            }
            break;
            
        case 'stats':
            echo "Scheduler statistics:\n";
            $stats = $scheduler->getSchedulerStats();
            foreach ($stats as $key => $value) {
                echo "  {$key}: {$value}\n";
            }
            break;
            
        default:
            echo "Usage: php bank_scheduler.php [schedule|execute|stats]\n";
            break;
    }
}
?>
