<?php
/**
 * Bank Integration for CashControl
 * Uses TrueLayer API for European bank connections
 */

class BankIntegration {
    private $client_id = 'your_truelayer_client_id';
    private $client_secret = 'your_truelayer_client_secret';
    private $redirect_uri = 'https://123cashcontrol.com/bank/callback.php';
    private $environment = 'sandbox'; // or 'live'
    private $db;
    
    public function __construct() {
        require_once '../config/database.php';
        $this->db = new Database();
    }
    
    /**
     * Get authorization URL for bank connection
     */
    public function getAuthUrl($userId) {
        $state = base64_encode(json_encode(['user_id' => $userId, 'timestamp' => time()]));
        
        $params = [
            'response_type' => 'code',
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'scope' => 'accounts transactions',
            'state' => $state,
            'providers' => 'uk-ob-all eu-ob-all'
        ];
        
        $baseUrl = $this->environment === 'live' 
            ? 'https://auth.truelayer.com' 
            : 'https://auth.truelayer-sandbox.com';
            
        return $baseUrl . '?' . http_build_query($params);
    }
    
    /**
     * Exchange authorization code for access token
     */
    public function exchangeCodeForToken($code) {
        $url = $this->environment === 'live' 
            ? 'https://auth.truelayer.com/connect/token'
            : 'https://auth.truelayer-sandbox.com/connect/token';
            
        $data = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'redirect_uri' => $this->redirect_uri,
            'code' => $code
        ];
        
        return $this->makeApiCall($url, $data, 'POST');
    }
    
    /**
     * Get user's bank accounts
     */
    public function getAccounts($accessToken) {
        $url = $this->environment === 'live'
            ? 'https://api.truelayer.com/data/v1/accounts'
            : 'https://api.truelayer-sandbox.com/data/v1/accounts';
            
        return $this->makeApiCall($url, null, 'GET', [
            'Authorization: Bearer ' . $accessToken
        ]);
    }
    
    /**
     * Get transactions for an account
     */
    public function getTransactions($accessToken, $accountId, $from = null, $to = null) {
        $url = $this->environment === 'live'
            ? "https://api.truelayer.com/data/v1/accounts/{$accountId}/transactions"
            : "https://api.truelayer-sandbox.com/data/v1/accounts/{$accountId}/transactions";
            
        $params = [];
        if ($from) $params['from'] = $from;
        if ($to) $params['to'] = $to;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $this->makeApiCall($url, null, 'GET', [
            'Authorization: Bearer ' . $accessToken
        ]);
    }
    
    /**
     * Store bank account in database
     */
    public function storeBankAccount($userId, $accountData, $accessToken) {
        $pdo = $this->db->connect();
        
        $stmt = $pdo->prepare("
            INSERT INTO bank_accounts (user_id, account_id, account_name, account_type, currency, balance, access_token, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            account_name = VALUES(account_name),
            balance = VALUES(balance),
            access_token = VALUES(access_token),
            updated_at = NOW()
        ");
        
        return $stmt->execute([
            $userId,
            $accountData['account_id'],
            $accountData['display_name'],
            $accountData['account_type'],
            $accountData['currency'],
            $accountData['balance']['current'] ?? 0,
            $accessToken
        ]);
    }
    
    /**
     * Detect subscription payments in transactions
     */
    public function detectSubscriptionPayments($userId, $transactions) {
        $pdo = $this->db->connect();
        
        // Get user's subscriptions for matching
        $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? AND is_active = 1");
        $stmt->execute([$userId]);
        $subscriptions = $stmt->fetchAll();
        
        $detectedPayments = [];
        
        foreach ($transactions as $transaction) {
            if ($transaction['transaction_type'] !== 'DEBIT') continue;
            
            $amount = abs($transaction['amount']);
            $description = strtolower($transaction['description']);
            
            foreach ($subscriptions as $subscription) {
                $subAmount = floatval($subscription['cost']);
                $subName = strtolower($subscription['name']);
                
                // Match by amount and description similarity
                if (abs($amount - $subAmount) < 0.01 && 
                    (strpos($description, $subName) !== false || 
                     strpos($subName, $description) !== false)) {
                    
                    $detectedPayments[] = [
                        'subscription_id' => $subscription['id'],
                        'transaction_id' => $transaction['transaction_id'],
                        'amount' => $amount,
                        'date' => $transaction['timestamp'],
                        'description' => $transaction['description']
                    ];
                    
                    // Store detected payment
                    $this->storeDetectedPayment($userId, $subscription['id'], $transaction);
                }
            }
        }
        
        return $detectedPayments;
    }
    
    /**
     * Store detected payment in database
     */
    private function storeDetectedPayment($userId, $subscriptionId, $transaction) {
        $pdo = $this->db->connect();
        
        $stmt = $pdo->prepare("
            INSERT INTO bank_transactions (user_id, subscription_id, transaction_id, amount, description, transaction_date, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE updated_at = NOW()
        ");
        
        return $stmt->execute([
            $userId,
            $subscriptionId,
            $transaction['transaction_id'],
            abs($transaction['amount']),
            $transaction['description'],
            $transaction['timestamp']
        ]);
    }
    
    /**
     * Make API call to TrueLayer
     */
    private function makeApiCall($url, $data = null, $method = 'GET', $headers = []) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge([
            'Content-Type: application/json'
        ], $headers));
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 400) {
            throw new Exception("API Error: HTTP $httpCode - $response");
        }
        
        return json_decode($response, true);
    }
}
?>
