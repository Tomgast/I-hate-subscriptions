<?php
// Bank Integration Service for CashControl - European Banks via PSD2
require_once __DIR__ . '/../config/db_config.php';

class BankService {
    private $pdo;
    private $trueLayerClientId;
    private $trueLayerClientSecret;
    private $trueLayerEnvironment;
    
    public function __construct() {
        $this->pdo = getDBConnection();
        
        // Load TrueLayer credentials securely
        $this->trueLayerClientId = $this->getSecureConfig('TRUELAYER_CLIENT_ID');
        $this->trueLayerClientSecret = $this->getSecureConfig('TRUELAYER_CLIENT_SECRET');
        $this->trueLayerEnvironment = $this->getSecureConfig('TRUELAYER_ENVIRONMENT', 'sandbox');
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
     * Get TrueLayer authorization URL for bank connection
     */
    public function getBankAuthorizationUrl($userId, $redirectUri = null) {
        if (!$redirectUri) {
            $redirectUri = 'https://I-hate-subscriptions.com/bank/callback.php';
        }
        
        $state = base64_encode(json_encode(['user_id' => $userId, 'timestamp' => time()]));
        
        $params = [
            'response_type' => 'code',
            'client_id' => $this->trueLayerClientId,
            'scope' => 'info accounts balance transactions offline_access',
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'providers' => 'uk-ob-all uk-oauth-all'
        ];
        
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
