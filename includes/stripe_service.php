<?php
require_once __DIR__ . '/../config/db_config.php';

class StripeService {
    private $pdo;
    private $stripeSecretKey;
    private $stripePublishableKey;
    private $webhookSecret;
    
    public function __construct() {
        // Load secure configuration
        require_once __DIR__ . '/../config/secure_loader.php';
        
        $this->pdo = getDBConnection();
        
        // Load Stripe credentials securely
        $this->stripeSecretKey = getSecureConfig('STRIPE_SECRET_KEY');
        $this->stripePublishableKey = getSecureConfig('STRIPE_PUBLISHABLE_KEY');
        $this->webhookSecret = getSecureConfig('STRIPE_WEBHOOK_SECRET');
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
     * Create a Stripe Checkout session for €29 one-time payment
     */
    public function createCheckoutSession($userId, $userEmail, $successUrl = null, $cancelUrl = null) {
        try {
            if (!$successUrl) {
                $successUrl = 'https://123cashcontrol.com/payment/success.php';
            }
            if (!$cancelUrl) {
                $cancelUrl = 'https://123cashcontrol.com/payment/cancel.php';
            }
            
            $data = [
                'payment_method_types' => ['card', 'ideal', 'bancontact'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'eur',
                            'product_data' => [
                                'name' => 'CashControl Pro - Lifetime Access',
                                'description' => 'One-time payment for lifetime access to all Pro features',
                                'images' => ['https://123cashcontrol.com/images/logo.png']
                            ],
                            'unit_amount' => 2900, // €29.00 in cents
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $cancelUrl,
                'customer_email' => $userEmail,
                'metadata' => [
                    'user_id' => $userId,
                    'product' => 'cashcontrol_pro_lifetime'
                ],
                'payment_intent_data' => [
                    'metadata' => [
                        'user_id' => $userId,
                        'product' => 'cashcontrol_pro_lifetime'
                    ]
                ]
            ];
            
            $response = $this->makeStripeRequest('POST', 'checkout/sessions', $data);
            
            if ($response && isset($response['id'])) {
                // Save checkout session to database
                $this->saveCheckoutSession($userId, $response['id'], $response);
                return $response;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Stripe checkout session creation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Retrieve a Stripe Checkout session
     */
    public function getCheckoutSession($sessionId) {
        try {
            return $this->makeStripeRequest('GET', "checkout/sessions/$sessionId");
        } catch (Exception $e) {
            error_log("Stripe session retrieval error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Handle successful payment and upgrade user to Pro
     */
    public function handleSuccessfulPayment($sessionId) {
        try {
            $session = $this->getCheckoutSession($sessionId);
            
            if (!$session || $session['payment_status'] !== 'paid') {
                return false;
            }
            
            $userId = $session['metadata']['user_id'] ?? null;
            if (!$userId) {
                error_log("No user_id in session metadata");
                return false;
            }
            
            // Upgrade user to Pro
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET is_premium = 1, premium_expires_at = NULL, updated_at = NOW() 
                WHERE id = ?
            ");
            $upgraded = $stmt->execute([$userId]);
            
            if ($upgraded) {
                // Record payment in payment history
                $this->recordPayment($userId, $session);
                
                // Send upgrade confirmation email
                $this->sendUpgradeConfirmationEmail($userId, $session);
                
                error_log("User $userId successfully upgraded to Pro");
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Payment handling error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Record payment in database
     */
    private function recordPayment($userId, $session) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO payment_history 
                (user_id, stripe_session_id, stripe_payment_intent_id, amount, currency, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $userId,
                $session['id'],
                $session['payment_intent'] ?? null,
                2900, // €29.00 in cents
                'eur',
                'completed'
            ]);
            
        } catch (Exception $e) {
            error_log("Payment recording error: " . $e->getMessage());
        }
    }
    
    /**
     * Send upgrade confirmation email
     */
    private function sendUpgradeConfirmationEmail($userId, $session) {
        try {
            // Get user details
            $stmt = $this->pdo->prepare("SELECT email, name FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                require_once __DIR__ . '/email_service.php';
                $emailService = new EmailService();
                $emailService->sendUpgradeConfirmationEmail($user['email'], $user['name']);
            }
            
        } catch (Exception $e) {
            error_log("Upgrade email error: " . $e->getMessage());
        }
    }
    
    /**
     * Save checkout session to database
     */
    private function saveCheckoutSession($userId, $sessionId, $sessionData) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO checkout_sessions 
                (user_id, stripe_session_id, session_data, status, created_at) 
                VALUES (?, ?, ?, 'pending', NOW())
                ON DUPLICATE KEY UPDATE 
                session_data = VALUES(session_data), 
                updated_at = NOW()
            ");
            
            $stmt->execute([
                $userId,
                $sessionId,
                json_encode($sessionData)
            ]);
            
        } catch (Exception $e) {
            error_log("Checkout session save error: " . $e->getMessage());
        }
    }
    
    /**
     * Check if user has Pro access
     */
    public function hasProAccess($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT is_premium FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $user && $user['is_premium'] == 1;
            
        } catch (Exception $e) {
            error_log("Pro access check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user's payment history
     */
    public function getPaymentHistory($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM payment_history 
                WHERE user_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Payment history error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Make HTTP request to Stripe API
     */
    private function makeStripeRequest($method, $endpoint, $data = null) {
        $url = "https://api.stripe.com/v1/$endpoint";
        
        $headers = [
            'Authorization: Bearer ' . $this->stripeSecretKey,
            'Content-Type: application/x-www-form-urlencoded'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->flattenArray($data)));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        } else {
            error_log("Stripe API error: HTTP $httpCode - $response");
            return false;
        }
    }
    
    /**
     * Flatten nested array for Stripe API
     */
    private function flattenArray($array, $prefix = '') {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix ? $prefix . '[' . $key . ']' : $key;
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        return $result;
    }
    
    /**
     * Test Stripe configuration
     */
    public function testConfiguration() {
        $errors = [];
        
        if (empty($this->stripeSecretKey)) {
            $errors[] = "Stripe Secret Key is not configured";
        } elseif (!str_starts_with($this->stripeSecretKey, 'sk_')) {
            $errors[] = "Stripe Secret Key format is invalid";
        }
        
        if (empty($this->stripePublishableKey)) {
            $errors[] = "Stripe Publishable Key is not configured";
        } elseif (!str_starts_with($this->stripePublishableKey, 'pk_')) {
            $errors[] = "Stripe Publishable Key format is invalid";
        }
        
        if (empty($errors)) {
            // Test API connection
            $testResult = $this->makeStripeRequest('GET', 'account');
            if (!$testResult) {
                $errors[] = "Failed to connect to Stripe API";
            }
        }
        
        return [
            'configured' => empty($errors),
            'errors' => $errors,
            'publishable_key' => $this->stripePublishableKey
        ];
    }
}
?>
