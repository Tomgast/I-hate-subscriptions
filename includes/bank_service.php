<?php
/**
 * PHASE 3C.2: ENHANCED BANK SERVICE
 * Bank Integration Service for CashControl with plan-based usage tracking
 */
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/plan_manager.php';

class BankService {
    private $pdo;
    private $trueLayerClientId;
    private $trueLayerClientSecret;
    private $trueLayerEnvironment;
    private $planManager;
    
    public function __construct() {
        // Load secure configuration
        require_once __DIR__ . '/../config/secure_loader.php';
        
        $this->pdo = getDBConnection();
        $this->planManager = getPlanManager();
        
        // Load TrueLayer credentials securely
        $this->trueLayerClientId = getSecureConfig('TRUELAYER_CLIENT_ID');
        $this->trueLayerClientSecret = getSecureConfig('TRUELAYER_CLIENT_SECRET');
        $this->trueLayerEnvironment = getSecureConfig('TRUELAYER_ENVIRONMENT') ?: 'sandbox';
    }
    
    /**
     * Securely load configuration values from multiple sources
     * Priority: Plesk Environment Variables > Secure Config File > Default
     */
    private function getSecureConfig($key, $default = null) {
        // Try Plesk environment variables first
        $value = getenv($key) ?: $_SERVER[$key] ?? null;
        
        if ($value) {
            return $value;
        }
        
        // Try secure config file (outside web root)
        static $secureConfig = null;
        if ($secureConfig === null) {
            $configPath = dirname(__DIR__) . '/../secure-config.php';
            if (file_exists($configPath)) {
                $secureConfig = include $configPath;
            } else {
                $secureConfig = [];
            }
        }
        
        return $secureConfig[$key] ?? $default;
    }
    
    /**
     * Initiate bank connection with plan-based access control
     * @param int $userId User ID
     * @param string $planType User's plan type
     * @return string|false Authorization URL or false if not allowed
     */
    public function initiateBankConnection($userId, $planType) {
        // Check if user can perform bank scan
        if (!$this->planManager->canAccessFeature($userId, 'bank_scan')) {
            throw new Exception("Bank scan not available with your current plan.");
        }
        
        if (!$this->planManager->hasScansRemaining($userId)) {
            throw new Exception("You have reached your scan limit for this plan.");
        }
        
        // Create scan record
        $scanId = $this->createScanRecord($userId, $planType);
        
        if (!$scanId) {
            throw new Exception("Failed to initialize scan record.");
        }
        
        // Store scan ID in session for callback processing
        $_SESSION['current_scan_id'] = $scanId;
        
        // Generate authorization URL (without scan_id to match TrueLayer console registration)
        return $this->getBankAuthorizationUrl($userId);
    }
    
    /**
     * Create scan record in database
     * @param int $userId User ID
     * @param string $planType Plan type
     * @return int|false Scan ID or false on failure
     */
    private function createScanRecord($userId, $planType) {
        try {
            // Create bank_scans table if it doesn't exist
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS bank_scans (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    plan_type VARCHAR(50) NOT NULL,
                    status ENUM('initiated', 'in_progress', 'completed', 'failed') DEFAULT 'initiated',
                    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    completed_at TIMESTAMP NULL,
                    subscriptions_found INT DEFAULT 0,
                    total_monthly_cost DECIMAL(10,2) DEFAULT 0,
                    scan_data JSON NULL,
                    error_message TEXT NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_user_scans (user_id, started_at)
                )
            ");
            
            // Insert scan record
            $stmt = $this->pdo->prepare("
                INSERT INTO bank_scans (user_id, plan_type, status) 
                VALUES (?, ?, 'initiated')
            ");
            $stmt->execute([$userId, $planType]);
            
            return $this->pdo->lastInsertId();
        } catch (Exception $e) {
            error_log("Error creating scan record: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get TrueLayer authorization URL for bank connection
     */
    public function getBankAuthorizationUrl($userId, $redirectUri = null) {
        if (!$redirectUri) {
            $redirectUri = 'https://123cashcontrol.com/bank/callback.php';
        }
        
        // Create state parameter with user ID and timestamp
        $state = base64_encode(json_encode(['user_id' => $userId, 'timestamp' => time()]));
        
        // Build OAuth parameters
        $params = [
            'response_type' => 'code',
            'client_id' => $this->trueLayerClientId,
            'scope' => 'info accounts balance transactions offline_access',
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'providers' => 'uk-ob-all uk-oauth-all'
        ];
        
        // Use correct TrueLayer authorization endpoint
        $baseUrl = $this->trueLayerEnvironment === 'live' 
            ? 'https://auth.truelayer.com' 
            : 'https://auth.truelayer-sandbox.com';
            
        return $baseUrl . '?' . http_build_query($params);
    }
    
    /**
     * Exchange authorization code for access token
     */
    public function exchangeCodeForToken($code, $redirectUri = null) {
        if (!$redirectUri) {
            $redirectUri = 'https://123cashcontrol.com/bank/callback.php';
        }
        
        $tokenUrl = $this->trueLayerEnvironment === 'live' 
            ? 'https://auth.truelayer.com/connect/token' 
            : 'https://auth.truelayer-sandbox.com/connect/token';
        
        $postData = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->trueLayerClientId,
            'client_secret' => $this->trueLayerClientSecret,
            'code' => $code,
            'redirect_uri' => $redirectUri
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        }
        
        return false;
    }
    
    /**
     * Get bank accounts for user
     */
    public function getBankAccounts($accessToken) {
        $apiUrl = $this->trueLayerEnvironment === 'live' 
            ? 'https://api.truelayer.com/data/v1/accounts' 
            : 'https://api.truelayer-sandbox.com/data/v1/accounts';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return $data['results'] ?? [];
        }
        
        return false;
    }
    
    /**
     * Get transactions for a bank account
     */
    public function getAccountTransactions($accessToken, $accountId, $fromDate = null, $toDate = null) {
        if (!$fromDate) {
            $fromDate = date('Y-m-d', strtotime('-90 days'));
        }
        if (!$toDate) {
            $toDate = date('Y-m-d');
        }
        
        $apiUrl = $this->trueLayerEnvironment === 'live' 
            ? "https://api.truelayer.com/data/v1/accounts/{$accountId}/transactions" 
            : "https://api.truelayer-sandbox.com/data/v1/accounts/{$accountId}/transactions";
        
        $params = [
            'from' => $fromDate,
            'to' => $toDate
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl . '?' . http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return $data['results'] ?? [];
        }
        
        return false;
    }
    
    /**
     * Analyze transactions to detect subscriptions
     */
    public function detectSubscriptions($transactions) {
        $potentialSubscriptions = [];
        $merchantTransactions = [];
        
        // Group transactions by merchant
        foreach ($transactions as $transaction) {
            if ($transaction['amount'] < 0) { // Only outgoing payments
                $merchant = $this->normalizeMerchantName($transaction['description']);
                if (!isset($merchantTransactions[$merchant])) {
                    $merchantTransactions[$merchant] = [];
                }
                $merchantTransactions[$merchant][] = $transaction;
            }
        }
        
        // Analyze each merchant for recurring patterns
        foreach ($merchantTransactions as $merchant => $merchantTxns) {
            if (count($merchantTxns) >= 2) { // At least 2 transactions to detect pattern
                $subscription = $this->analyzeRecurringPattern($merchant, $merchantTxns);
                if ($subscription) {
                    $potentialSubscriptions[] = $subscription;
                }
            }
        }
        
        return $potentialSubscriptions;
    }
    
    /**
     * Normalize merchant name for better matching
     */
    private function normalizeMerchantName($description) {
        // Remove common payment processor prefixes
        $description = preg_replace('/^(PAYPAL \*|STRIPE \*|SQ \*|AMZN Mktp |GOOGLE \*)/i', '', $description);
        
        // Remove transaction IDs and dates
        $description = preg_replace('/\d{4,}/', '', $description);
        $description = preg_replace('/\d{2}\/\d{2}/', '', $description);
        
        // Clean up and normalize
        $description = trim($description);
        $description = strtoupper($description);
        
        return $description;
    }
    
    /**
     * Analyze transactions for recurring patterns
     */
    private function analyzeRecurringPattern($merchant, $transactions) {
        // Sort transactions by date
        usort($transactions, function($a, $b) {
            return strtotime($a['timestamp']) - strtotime($b['timestamp']);
        });
        
        $amounts = [];
        $intervals = [];
        
        // Analyze amounts and intervals
        for ($i = 0; $i < count($transactions); $i++) {
            $amounts[] = abs($transactions[$i]['amount']);
            
            if ($i > 0) {
                $prevDate = strtotime($transactions[$i-1]['timestamp']);
                $currDate = strtotime($transactions[$i]['timestamp']);
                $daysDiff = ($currDate - $prevDate) / (24 * 60 * 60);
                $intervals[] = $daysDiff;
            }
        }
        
        // Check if amounts are consistent (within 10% variance)
        $avgAmount = array_sum($amounts) / count($amounts);
        $amountVariance = 0;
        foreach ($amounts as $amount) {
            $amountVariance += abs($amount - $avgAmount) / $avgAmount;
        }
        $amountVariance /= count($amounts);
        
        if ($amountVariance > 0.1) {
            return null; // Too much variance in amounts
        }
        
        // Check if intervals suggest a recurring pattern
        if (empty($intervals)) {
            return null;
        }
        
        $avgInterval = array_sum($intervals) / count($intervals);
        $billingCycle = $this->determineBillingCycle($avgInterval);
        
        if (!$billingCycle) {
            return null; // No clear recurring pattern
        }
        
        // Calculate next payment date
        $lastTransaction = end($transactions);
        $lastDate = strtotime($lastTransaction['timestamp']);
        $nextPaymentDate = $this->calculateNextPaymentDate($lastDate, $billingCycle);
        
        return [
            'name' => $this->beautifyMerchantName($merchant),
            'merchant' => $merchant,
            'amount' => round($avgAmount, 2),
            'currency' => $transactions[0]['currency'] ?? 'EUR',
            'billing_cycle' => $billingCycle,
            'next_payment_date' => date('Y-m-d', $nextPaymentDate),
            'confidence' => $this->calculateConfidence($transactions, $amountVariance, $intervals),
            'transaction_count' => count($transactions),
            'last_transaction_date' => date('Y-m-d', $lastDate)
        ];
    }
    
    /**
     * Determine billing cycle from average interval
     */
    private function determineBillingCycle($avgInterval) {
        if ($avgInterval >= 25 && $avgInterval <= 35) {
            return 'monthly';
        } elseif ($avgInterval >= 350 && $avgInterval <= 380) {
            return 'yearly';
        } elseif ($avgInterval >= 6 && $avgInterval <= 8) {
            return 'weekly';
        }
        
        return null;
    }
    
    /**
     * Calculate next payment date based on billing cycle
     */
    private function calculateNextPaymentDate($lastDate, $billingCycle) {
        switch ($billingCycle) {
            case 'monthly':
                return strtotime('+1 month', $lastDate);
            case 'yearly':
                return strtotime('+1 year', $lastDate);
            case 'weekly':
                return strtotime('+1 week', $lastDate);
            default:
                return strtotime('+1 month', $lastDate);
        }
    }
    
    /**
     * Calculate confidence score for subscription detection
     */
    private function calculateConfidence($transactions, $amountVariance, $intervals) {
        $baseScore = 50;
        
        // More transactions = higher confidence
        $baseScore += min(count($transactions) * 10, 30);
        
        // Lower amount variance = higher confidence
        $baseScore += (1 - $amountVariance) * 20;
        
        // Consistent intervals = higher confidence
        if (!empty($intervals)) {
            $avgInterval = array_sum($intervals) / count($intervals);
            $intervalVariance = 0;
            foreach ($intervals as $interval) {
                $intervalVariance += abs($interval - $avgInterval) / $avgInterval;
            }
            $intervalVariance /= count($intervals);
            $baseScore += (1 - min($intervalVariance, 1)) * 20;
        }
        
        return min(100, max(0, $baseScore));
    }
    
    /**
     * Beautify merchant name for display
     */
    private function beautifyMerchantName($merchant) {
        // Common subscription service mappings
        $knownServices = [
            'NETFLIX' => 'Netflix',
            'SPOTIFY' => 'Spotify',
            'AMAZON PRIME' => 'Amazon Prime',
            'MICROSOFT' => 'Microsoft',
            'ADOBE' => 'Adobe',
            'DROPBOX' => 'Dropbox',
            'GITHUB' => 'GitHub',
            'ZOOM' => 'Zoom'
        ];
        
        foreach ($knownServices as $key => $name) {
            if (strpos($merchant, $key) !== false) {
                return $name;
            }
        }
        
        // Default: capitalize first letter of each word
        return ucwords(strtolower($merchant));
    }
    
    /**
     * Save bank connection for user
     */
    public function saveBankConnection($userId, $accessToken, $refreshToken, $bankName, $accounts) {
        try {
            // Create bank_connections table if it doesn't exist
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS bank_connections (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    bank_name VARCHAR(255),
                    access_token TEXT,
                    refresh_token TEXT,
                    expires_at TIMESTAMP,
                    connected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    last_sync TIMESTAMP NULL,
                    is_active BOOLEAN DEFAULT TRUE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ");
            
            // Save connection
            $stmt = $this->pdo->prepare("
                INSERT INTO bank_connections (user_id, bank_name, access_token, refresh_token, expires_at) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour')); // TrueLayer tokens typically expire in 1 hour
            
            $stmt->execute([
                $userId,
                $bankName,
                $accessToken,
                $refreshToken,
                $expiresAt
            ]);
            
            return $this->pdo->lastInsertId();
            
        } catch (Exception $e) {
            error_log("Error saving bank connection: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Complete bank scan and update usage tracking
     * @param int $scanId Scan ID
     * @param array $subscriptions Found subscriptions
     * @return bool Success
     */
    public function completeScan($scanId, $subscriptions) {
        try {
            $this->pdo->beginTransaction();
            
            // Get scan record
            $stmt = $this->pdo->prepare("SELECT * FROM bank_scans WHERE id = ?");
            $stmt->execute([$scanId]);
            $scan = $stmt->fetch();
            
            if (!$scan) {
                throw new Exception("Scan record not found");
            }
            
            // Calculate totals
            $monthlyTotal = 0;
            foreach ($subscriptions as $sub) {
                $monthlyCost = $this->calculateMonthlyCost($sub['cost'], $sub['billing_cycle']);
                $monthlyTotal += $monthlyCost;
            }
            
            // Update scan record
            $stmt = $this->pdo->prepare("
                UPDATE bank_scans SET
                    status = 'completed',
                    completed_at = NOW(),
                    subscriptions_found = ?,
                    total_monthly_cost = ?,
                    scan_data = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                count($subscriptions),
                $monthlyTotal,
                json_encode($subscriptions),
                $scanId
            ]);
            
            // Increment user's scan count (for plan tracking)
            $this->planManager->incrementScanCount($scan['user_id']);
            
            // Save subscriptions to user's account
            foreach ($subscriptions as $sub) {
                $this->saveSubscription($scan['user_id'], $sub, $scanId);
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            error_log("Error completing scan: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get scan results for user
     * @param int $userId User ID
     * @param int|null $scanId Specific scan ID (optional)
     * @return array|null Scan results
     */
    public function getScanResults($userId, $scanId = null) {
        try {
            if ($scanId) {
                $stmt = $this->pdo->prepare("
                    SELECT * FROM bank_scans 
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([$scanId, $userId]);
            } else {
                $stmt = $this->pdo->prepare("
                    SELECT * FROM bank_scans 
                    WHERE user_id = ? AND status = 'completed'
                    ORDER BY completed_at DESC 
                    LIMIT 1
                ");
                $stmt->execute([$userId]);
            }
            
            $scan = $stmt->fetch();
            
            if (!$scan || $scan['status'] !== 'completed') {
                return null;
            }
            
            // Get associated subscriptions
            $stmt = $this->pdo->prepare("
                SELECT * FROM subscriptions 
                WHERE user_id = ? AND scan_id = ?
                ORDER BY cost DESC
            ");
            $stmt->execute([$userId, $scan['id']]);
            $subscriptions = $stmt->fetchAll();
            
            return [
                'scan_id' => $scan['id'],
                'scan_date' => $scan['completed_at'],
                'plan_type' => $scan['plan_type'],
                'subscriptions_found' => $scan['subscriptions_found'],
                'monthly_total' => $scan['total_monthly_cost'],
                'yearly_total' => $scan['total_monthly_cost'] * 12,
                'subscriptions' => $subscriptions
            ];
            
        } catch (Exception $e) {
            error_log("Error getting scan results: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Save subscription from scan to user's account
     * @param int $userId User ID
     * @param array $subscription Subscription data
     * @param int $scanId Scan ID
     * @return bool Success
     */
    private function saveSubscription($userId, $subscription, $scanId) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO subscriptions (
                    user_id, name, cost, billing_cycle, category, 
                    next_billing_date, is_active, source, scan_id, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, 1, 'bank_scan', ?, NOW())
            ");
            
            $nextBilling = $this->calculateNextPaymentDate(
                $subscription['last_payment'] ?? date('Y-m-d'),
                $subscription['billing_cycle']
            );
            
            $stmt->execute([
                $userId,
                $subscription['name'],
                $subscription['cost'],
                $subscription['billing_cycle'],
                $subscription['category'] ?? 'Other',
                $nextBilling,
                $scanId
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Error saving subscription: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calculate monthly cost from any billing cycle
     * @param float $cost Cost amount
     * @param string $billingCycle Billing cycle
     * @return float Monthly cost
     */
    private function calculateMonthlyCost($cost, $billingCycle) {
        switch ($billingCycle) {
            case 'monthly':
                return $cost;
            case 'yearly':
                return $cost / 12;
            case 'weekly':
                return $cost * 4.33; // Average weeks per month
            case 'daily':
                return $cost * 30; // Average days per month
            default:
                return $cost; // Assume monthly if unknown
        }
    }
    
    /**
     * Mark scan as failed
     * @param int $scanId Scan ID
     * @param string $errorMessage Error message
     * @return bool Success
     */
    public function markScanFailed($scanId, $errorMessage) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE bank_scans SET
                    status = 'failed',
                    completed_at = NOW(),
                    error_message = ?
                WHERE id = ?
            ");
            
            $stmt->execute([$errorMessage, $scanId]);
            return true;
        } catch (Exception $e) {
            error_log("Error marking scan as failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if one-time scan was successful and has valid results
     * @param int $userId User ID
     * @return array Status information
     */
    public function verifyOnetimeScanSuccess($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT bs.*, COUNT(s.id) as subscriptions_saved
                FROM bank_scans bs
                LEFT JOIN subscriptions s ON bs.id = s.scan_id
                WHERE bs.user_id = ? AND bs.status = 'completed'
                ORDER BY bs.completed_at DESC
                LIMIT 1
            ");
            
            $stmt->execute([$userId]);
            $scan = $stmt->fetch();
            
            if (!$scan) {
                return [
                    'has_successful_scan' => false,
                    'can_retry' => true,
                    'message' => 'No completed scan found'
                ];
            }
            
            // Consider scan successful if it found at least 1 subscription OR completed without errors
            $isSuccessful = ($scan['subscriptions_found'] > 0 || empty($scan['error_message']));
            
            return [
                'has_successful_scan' => $isSuccessful,
                'can_retry' => !$isSuccessful,
                'scan_date' => $scan['completed_at'],
                'subscriptions_found' => $scan['subscriptions_found'],
                'subscriptions_saved' => $scan['subscriptions_saved'],
                'message' => $isSuccessful ? 'Scan completed successfully' : 'Scan completed but may need retry'
            ];
            
        } catch (Exception $e) {
            error_log("Error verifying one-time scan: " . $e->getMessage());
            return [
                'has_successful_scan' => false,
                'can_retry' => true,
                'message' => 'Error checking scan status'
            ];
        }
    }
    
    /**
     * Check if subscription user needs automatic scan
     * @param int $userId User ID
     * @return bool True if scan is needed
     */
    public function needsAutomaticScan($userId) {
        try {
            // Get user's plan
            $planManager = getPlanManager();
            $userPlan = $planManager->getUserPlan($userId);
            
            // Only subscription users get automatic scans
            if (!$userPlan || !in_array($userPlan['plan_type'], ['monthly', 'yearly'])) {
                return false;
            }
            
            // Check last scan date
            $stmt = $this->pdo->prepare("
                SELECT completed_at 
                FROM bank_scans 
                WHERE user_id = ? AND status = 'completed'
                ORDER BY completed_at DESC 
                LIMIT 1
            ");
            
            $stmt->execute([$userId]);
            $lastScan = $stmt->fetch();
            
            if (!$lastScan) {
                return true; // No previous scan, needs initial scan
            }
            
            // Check if last scan was more than 7 days ago
            $lastScanDate = new DateTime($lastScan['completed_at']);
            $now = new DateTime();
            $daysSinceLastScan = $now->diff($lastScanDate)->days;
            
            return $daysSinceLastScan >= 7;
            
        } catch (Exception $e) {
            error_log("Error checking automatic scan need: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Schedule automatic scan for subscription users
     * @param int $userId User ID
     * @return bool Success
     */
    public function scheduleAutomaticScan($userId) {
        try {
            // Create automatic scan record
            $scanId = $this->createScanRecord($userId, 'automatic');
            
            if (!$scanId) {
                return false;
            }
            
            // Mark as scheduled for background processing
            $stmt = $this->pdo->prepare("
                UPDATE bank_scans SET
                    status = 'scheduled',
                    scan_data = JSON_OBJECT('type', 'automatic', 'scheduled_at', NOW())
                WHERE id = ?
            ");
            
            $stmt->execute([$scanId]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error scheduling automatic scan: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get scan statistics for user
     * @param int $userId User ID
     * @return array Scan statistics
     */
    public function getScanStatistics($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_scans,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as successful_scans,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_scans,
                    MAX(completed_at) as last_scan_date,
                    SUM(subscriptions_found) as total_subscriptions_found
                FROM bank_scans 
                WHERE user_id = ?
            ");
            
            $stmt->execute([$userId]);
            $stats = $stmt->fetch();
            
            return [
                'total_scans' => (int)$stats['total_scans'],
                'successful_scans' => (int)$stats['successful_scans'],
                'failed_scans' => (int)$stats['failed_scans'],
                'last_scan_date' => $stats['last_scan_date'],
                'total_subscriptions_found' => (int)$stats['total_subscriptions_found']
            ];
            
        } catch (Exception $e) {
            error_log("Error getting scan statistics: " . $e->getMessage());
            return [
                'total_scans' => 0,
                'successful_scans' => 0,
                'failed_scans' => 0,
                'last_scan_date' => null,
                'total_subscriptions_found' => 0
            ];
        }
    }
    
    /**
     * Test bank service configuration
     */
    public function testConfiguration() {
        $errors = [];
        
        if (empty($this->trueLayerClientId)) {
            $errors[] = "TrueLayer Client ID is not configured";
        }
        
        if (empty($this->trueLayerClientSecret)) {
            $errors[] = "TrueLayer Client Secret is not configured";
        }
        
        return [
            'configured' => empty($errors),
            'errors' => $errors,
            'environment' => $this->trueLayerEnvironment,
            'client_id' => $this->trueLayerClientId ? substr($this->trueLayerClientId, 0, 10) . '...' : 'Not set'
        ];
    }
}
?>
