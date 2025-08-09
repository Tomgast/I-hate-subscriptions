<?php
/**
 * GOCARDLESS (NORDIGEN) FINANCIAL SERVICE
 * Handles EU bank connections using GoCardless Bank Account Data API (formerly Nordigen)
 * Provides same interface as StripeFinancialService for unified usage
 */

class GoCardlessFinancialService {
    private $pdo;
    private $secretId;
    private $secretKey;
    private $baseUrl;
    private $apiBaseUrl = 'https://bankaccountdata.gocardless.com/api/v2/';
    private $accessToken;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        
        // Load GoCardless credentials from secure config
        $this->loadSecureConfig();
        $this->baseUrl = $this->getBaseUrl();
        
        if (empty($this->secretId) || empty($this->secretKey)) {
            throw new Exception('GoCardless credentials not configured');
        }
        
        // Get access token
        $this->accessToken = $this->getAccessToken();
    }
    
    /**
     * Get access token for GoCardless API
     */
    private function getAccessToken() {
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->apiBaseUrl . 'token/new/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'secret_id' => $this->secretId,
                'secret_key' => $this->secretKey
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ]
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);
        
        if ($curlError) {
            throw new Exception('GoCardless API connection error: ' . $curlError);
        }
        
        if ($httpCode !== 200) {
            $errorMsg = 'Failed to get GoCardless access token (HTTP ' . $httpCode . ')';
            if ($response) {
                $errorData = json_decode($response, true);
                if (isset($errorData['detail'])) {
                    $errorMsg .= ': ' . $errorData['detail'];
                } elseif (isset($errorData['error'])) {
                    $errorMsg .= ': ' . $errorData['error'];
                } else {
                    $errorMsg .= ': ' . $response;
                }
            }
            throw new Exception($errorMsg);
        }
        
        $data = json_decode($response, true);
        if (!isset($data['access'])) {
            throw new Exception('Invalid GoCardless token response: ' . json_encode($data));
        }
        
        return $data['access'];
    }
    
    /**
     * Get list of supported institutions for a country
     */
    public function getInstitutions($country = 'NL') {
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->apiBaseUrl . 'institutions/?country=' . $country,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken,
                'Accept: application/json'
            ]
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($httpCode !== 200) {
            throw new Exception('Failed to get institutions: ' . $response);
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Create bank connection session (equivalent to Stripe's createBankConnectionSession)
     */
    public function createBankConnectionSession($userId, $options = []) {
        $country = $options['country'] ?? 'NL';
        $institutionId = $options['institution_id'] ?? null;
        
        // Validate that institution_id is provided
        if (!$institutionId) {
            throw new Exception("Institution ID is required. Please select a bank first.");
        }
        
        // Validate that the institution exists for the country
        $institutions = $this->getInstitutions($country);
        $validInstitution = false;
        foreach ($institutions as $institution) {
            if ($institution['id'] === $institutionId) {
                $validInstitution = true;
                break;
            }
        }
        
        if (!$validInstitution) {
            throw new Exception("Invalid institution ID '$institutionId' for country '$country'");
        }
        
        try {
            // Get user info
            $stmt = $this->pdo->prepare("SELECT email, name FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                throw new Exception('User not found');
            }
            
            // Create end user agreement
            $agreementData = [
                'institution_id' => $institutionId,
                'max_historical_days' => 90,
                'access_valid_for_days' => 90,
                'access_scope' => ['balances', 'details', 'transactions']
            ];
            
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $this->apiBaseUrl . 'agreements/enduser/',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($agreementData),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->accessToken,
                    'Content-Type: application/json',
                    'Accept: application/json'
                ]
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            
            if ($httpCode !== 201) {
                throw new Exception('Failed to create agreement: ' . $response);
            }
            
            $agreement = json_decode($response, true);
            $agreementId = $agreement['id'];
            
            // Create requisition (connection session)
            $requisitionData = [
                'redirect' => $this->baseUrl . '/bank/gocardless-callback.php',
                'institution_id' => $institutionId,
                'agreement' => $agreementId,
                'reference' => 'user_' . $userId . '_' . time(),
                'user_language' => 'EN'
            ];
            
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $this->apiBaseUrl . 'requisitions/',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($requisitionData),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->accessToken,
                    'Content-Type: application/json',
                    'Accept: application/json'
                ]
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            
            if ($httpCode !== 201) {
                throw new Exception('Failed to create requisition: ' . $response);
            }
            
            $requisition = json_decode($response, true);
            
            // Store session in database
            $stmt = $this->pdo->prepare("
                INSERT INTO bank_connection_sessions 
                (user_id, session_id, provider, status, created_at, expires_at, session_data) 
                VALUES (?, ?, 'gocardless', 'pending', NOW(), DATE_ADD(NOW(), INTERVAL 1 HOUR), ?)
            ");
            
            $sessionData = json_encode([
                'requisition_id' => $requisition['id'],
                'agreement_id' => $agreementId,
                'institution_id' => $institutionId,
                'country' => $country,
                'reference' => $requisitionData['reference']
            ]);
            
            $stmt->execute([$userId, $requisition['id'], $sessionData]);
            
            return [
                'success' => true,
                'session_id' => $requisition['id'],
                'auth_url' => $requisition['link'],
                'provider' => 'gocardless'
            ];
            
        } catch (Exception $e) {
            error_log("GoCardless session creation error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Handle callback from GoCardless (equivalent to Stripe's handleCallback)
     */
    public function handleCallback($requisitionId) {
        try {
            error_log("GoCardless handleCallback called with requisition ID: " . $requisitionId);
            
            // The $requisitionId is actually the reference ID from GoCardless callback
            // We need to look up the session by reference in session_data, not by session_id
            $stmt = $this->pdo->prepare("
                SELECT user_id, session_id, session_data, status, created_at, expires_at
                FROM bank_connection_sessions 
                WHERE provider = 'gocardless' 
                AND JSON_EXTRACT(session_data, '$.reference') = ?
            ");
            $stmt->execute([$requisitionId]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("Database session lookup by reference result: " . print_r($session, true));
            
            if (!$session) {
                // Try alternative lookup - maybe it's actually the requisition ID
                $stmt = $this->pdo->prepare("
                    SELECT user_id, session_id, session_data, status, created_at, expires_at
                    FROM bank_connection_sessions 
                    WHERE session_id = ? AND provider = 'gocardless'
                ");
                $stmt->execute([$requisitionId]);
                $session = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$session) {
                    // Log all sessions for debugging
                    $stmt = $this->pdo->prepare("
                        SELECT session_id, session_data, status, created_at, expires_at 
                        FROM bank_connection_sessions 
                        WHERE provider = 'gocardless' 
                        ORDER BY created_at DESC LIMIT 10
                    ");
                    $stmt->execute();
                    $allSessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    error_log("Recent GoCardless sessions: " . print_r($allSessions, true));
                    
                    throw new Exception('Session not found in database for reference/requisition ID: ' . $requisitionId);
                }
            }
            
            // Check if session has expired
            if ($session['expires_at'] && strtotime($session['expires_at']) < time()) {
                error_log("Session expired. Expires at: " . $session['expires_at'] . ", Current time: " . date('Y-m-d H:i:s'));
                throw new Exception('Session has expired');
            }
            
            $userId = $session['user_id'];
            $sessionData = json_decode($session['session_data'], true);
            
            // Get the actual requisition ID from session data
            $actualRequisitionId = $sessionData['requisition_id'] ?? $session['session_id'];
            
            error_log("Making API call to GoCardless for actual requisition: " . $actualRequisitionId);
            error_log("Session data: " . print_r($sessionData, true));
            
            // Get requisition details using the actual requisition ID
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $this->apiBaseUrl . 'requisitions/' . $actualRequisitionId . '/',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->accessToken,
                    'Accept: application/json'
                ]
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);
            
            error_log("GoCardless API response - HTTP Code: " . $httpCode . ", Response: " . $response);
            if ($curlError) {
                error_log("cURL error: " . $curlError);
            }
            
            if ($httpCode !== 200) {
                throw new Exception('Failed to get requisition: ' . $response);
            }
            
            $requisition = json_decode($response, true);
            
            // Update session status using the correct session_id
            $stmt = $this->pdo->prepare("
                UPDATE bank_connection_sessions 
                SET status = 'completed', updated_at = NOW() 
                WHERE session_id = ? AND provider = 'gocardless'
            ");
            $stmt->execute([$session['session_id']]);
            
            // Process connected accounts
            if (!empty($requisition['accounts'])) {
                foreach ($requisition['accounts'] as $accountId) {
                    $this->saveConnectedAccount($userId, $accountId, $sessionData);
                }
                
                // Start subscription scan
                $this->scanForSubscriptions($userId);
            }
            
            return [
                'success' => true,
                'accounts_connected' => count($requisition['accounts'] ?? []),
                'user_id' => $userId
            ];
            
        } catch (Exception $e) {
            error_log("GoCardless callback error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Save connected account to database
     */
    private function saveConnectedAccount($userId, $accountId, $sessionData) {
        // Get account details
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->apiBaseUrl . 'accounts/' . $accountId . '/details/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken,
                'Accept: application/json'
            ]
        ]);
        
        $response = curl_exec($curl);
        $accountDetails = json_decode($response, true);
        
        // Save to database
        $stmt = $this->pdo->prepare("
            INSERT INTO bank_connections 
            (user_id, account_id, provider, account_name, account_type, currency, status, created_at, connection_data) 
            VALUES (?, ?, 'gocardless', ?, 'checking', ?, 'active', NOW(), ?)
            ON DUPLICATE KEY UPDATE 
            status = 'active', updated_at = NOW(), connection_data = VALUES(connection_data)
        ");
        
        $accountName = $accountDetails['account']['name'] ?? 'GoCardless Account';
        $currency = $accountDetails['account']['currency'] ?? 'EUR';
        $connectionData = json_encode([
            'account_id' => $accountId,
            'institution_id' => $sessionData['institution_id'],
            'country' => $sessionData['country'],
            'account_details' => $accountDetails
        ]);
        
        $stmt->execute([$userId, $accountId, $accountName, $currency, $connectionData]);
    }
    
    /**
     * Scan for subscriptions (equivalent to Stripe's scanForSubscriptions)
     */
    public function scanForSubscriptions($userId) {
        try {
            // Get user's connected accounts
            $stmt = $this->pdo->prepare("
                SELECT account_id, connection_data 
                FROM bank_connections 
                WHERE user_id = ? AND provider = 'gocardless' AND status = 'active'
            ");
            $stmt->execute([$userId]);
            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $allSubscriptions = [];
            
            foreach ($accounts as $account) {
                $accountId = $account['account_id'];
                
                // Get transactions
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => $this->apiBaseUrl . 'accounts/' . $accountId . '/transactions/',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer ' . $this->accessToken,
                        'Accept: application/json'
                    ]
                ]);
                
                $response = curl_exec($curl);
                $transactions = json_decode($response, true);
                
                if (isset($transactions['transactions'])) {
                    $subscriptions = $this->analyzeTransactionsForSubscriptions($transactions['transactions']);
                    $allSubscriptions = array_merge($allSubscriptions, $subscriptions);
                }
            }
            
            // Save scan results
            $scanId = $this->saveScanResults($userId, $allSubscriptions);
            
            return [
                'success' => true,
                'scan_id' => $scanId,
                'subscriptions_found' => count($allSubscriptions)
            ];
            
        } catch (Exception $e) {
            error_log("GoCardless subscription scan error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Analyze transactions for subscription patterns
     */
    private function analyzeTransactionsForSubscriptions($transactions) {
        $subscriptions = [];
        $merchantGroups = [];
        
        // Group transactions by merchant
        foreach ($transactions as $transaction) {
            $merchant = $this->extractMerchantName($transaction);
            $amount = abs(floatval($transaction['transactionAmount']['amount']));
            $date = $transaction['bookingDate'];
            
            if ($amount > 0) {
                if (!isset($merchantGroups[$merchant])) {
                    $merchantGroups[$merchant] = [];
                }
                $merchantGroups[$merchant][] = [
                    'amount' => $amount,
                    'date' => $date,
                    'description' => $transaction['remittanceInformationUnstructured'] ?? ''
                ];
            }
        }
        
        // Analyze each merchant for subscription patterns
        foreach ($merchantGroups as $merchant => $transactions) {
            if (count($transactions) >= 2) {
                $subscription = $this->detectSubscriptionPattern($merchant, $transactions);
                if ($subscription) {
                    $subscriptions[] = $subscription;
                }
            }
        }
        
        return $subscriptions;
    }
    
    /**
     * Extract merchant name from transaction
     */
    private function extractMerchantName($transaction) {
        // Try different fields for merchant name
        if (!empty($transaction['creditorName'])) {
            return $transaction['creditorName'];
        }
        if (!empty($transaction['remittanceInformationUnstructured'])) {
            return substr($transaction['remittanceInformationUnstructured'], 0, 50);
        }
        return 'Unknown Merchant';
    }
    
    /**
     * Detect subscription patterns in merchant transactions
     */
    private function detectSubscriptionPattern($merchant, $transactions) {
        // Sort by date
        usort($transactions, function($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });
        
        // Look for recurring amounts
        $amounts = array_column($transactions, 'amount');
        $amountCounts = array_count_values($amounts);
        
        // Find most common amount
        $recurringAmount = array_keys($amountCounts, max($amountCounts))[0];
        $recurringTransactions = array_filter($transactions, function($t) use ($recurringAmount) {
            return $t['amount'] == $recurringAmount;
        });
        
        if (count($recurringTransactions) >= 2) {
            // Calculate frequency
            $dates = array_column($recurringTransactions, 'date');
            $intervals = [];
            for ($i = 1; $i < count($dates); $i++) {
                $interval = (strtotime($dates[$i]) - strtotime($dates[$i-1])) / (24 * 60 * 60);
                $intervals[] = $interval;
            }
            
            $avgInterval = array_sum($intervals) / count($intervals);
            
            // Determine billing cycle
            $billingCycle = 'unknown';
            if ($avgInterval >= 28 && $avgInterval <= 32) {
                $billingCycle = 'monthly';
            } elseif ($avgInterval >= 360 && $avgInterval <= 370) {
                $billingCycle = 'yearly';
            } elseif ($avgInterval >= 85 && $avgInterval <= 95) {
                $billingCycle = 'quarterly';
            }
            
            return [
                'merchant_name' => $merchant,
                'amount' => $recurringAmount,
                'currency' => 'EUR', // Default for EU
                'billing_cycle' => $billingCycle,
                'last_charge_date' => end($dates),
                'transaction_count' => count($recurringTransactions),
                'confidence' => min(100, count($recurringTransactions) * 25),
                'provider' => 'gocardless'
            ];
        }
        
        return null;
    }
    
    /**
     * Save scan results to database
     */
    private function saveScanResults($userId, $subscriptions) {
        // Create scan record
        $stmt = $this->pdo->prepare("
            INSERT INTO bank_scans (user_id, provider, status, subscriptions_found, created_at) 
            VALUES (?, 'gocardless', 'completed', ?, NOW())
        ");
        $stmt->execute([$userId, count($subscriptions)]);
        $scanId = $this->pdo->lastInsertId();
        
        // Save subscriptions
        foreach ($subscriptions as $subscription) {
            $stmt = $this->pdo->prepare("
                INSERT INTO subscriptions 
                (user_id, scan_id, merchant_name, amount, currency, billing_cycle, last_charge_date, confidence, provider, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'gocardless', NOW())
                ON DUPLICATE KEY UPDATE 
                amount = VALUES(amount), last_charge_date = VALUES(last_charge_date), 
                confidence = VALUES(confidence), updated_at = NOW()
            ");
            
            $stmt->execute([
                $userId,
                $scanId,
                $subscription['merchant_name'],
                $subscription['amount'],
                $subscription['currency'],
                $subscription['billing_cycle'],
                $subscription['last_charge_date'],
                $subscription['confidence']
            ]);
        }
        
        return $scanId;
    }
    
    /**
     * Get connection status (equivalent to Stripe's getConnectionStatus)
     */
    public function getConnectionStatus($userId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(DISTINCT bc.id) as connection_count,
                COUNT(DISTINCT bs.id) as scan_count,
                MAX(bs.created_at) as last_scan_date
            FROM bank_connections bc
            LEFT JOIN bank_scans bs ON bc.user_id = bs.user_id AND bs.provider = 'gocardless'
            WHERE bc.user_id = ? AND bc.provider = 'gocardless' AND bc.status = 'active'
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'has_connections' => $result['connection_count'] > 0,
            'connection_count' => intval($result['connection_count']),
            'scan_count' => intval($result['scan_count']),
            'last_scan_date' => $result['last_scan_date'],
            'provider' => 'gocardless'
        ];
    }
    
    /**
     * Get available scans for export
     */
    public function getAvailableScans($userId) {
        $stmt = $this->pdo->prepare("
            SELECT id, created_at, subscriptions_found, status 
            FROM bank_scans 
            WHERE user_id = ? AND provider = 'gocardless' 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get latest scan results
     */
    public function getLatestScanResults($userId) {
        $stmt = $this->pdo->prepare("
            SELECT s.*, bs.created_at as scan_date 
            FROM subscriptions s
            JOIN bank_scans bs ON s.scan_id = bs.id
            WHERE s.user_id = ? AND s.provider = 'gocardless'
            ORDER BY bs.created_at DESC, s.amount DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Load secure configuration
     */
    private function loadSecureConfig() {
        // Try secure config file (outside web root)
        static $secureConfig = null;
        if ($secureConfig === null) {
            // Try multiple possible paths for secure config
            $possiblePaths = [
                dirname(__DIR__) . '/../secure-config.php',  // Standard path
                '/var/www/vhosts/123cashcontrol.com/secure-config.php',  // Live server path
                dirname($_SERVER['DOCUMENT_ROOT']) . '/secure-config.php'  // Alternative live path
            ];
            
            $secureConfig = [];
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $secureConfig = include $path;
                    break;
                }
            }
        }
        
        $this->secretId = $secureConfig['GOCARDLESS_SECRET_ID'] ?? '';
        $this->secretKey = $secureConfig['GOCARDLESS_SECRET_KEY'] ?? '';
    }
    
    /**
     * Get the base URL for callbacks
     */
    private function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }
}
?>
