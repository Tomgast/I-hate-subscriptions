<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../config/secure_loader.php';

class StripeService {
    private $pdo;
    private $stripeSecretKey;
    private $stripePublishableKey;
    private $webhookSecret;
    
    public function __construct() {
        // Use consistent database connection
        $this->pdo = getDBConnection();
        
        // Load Stripe credentials securely using global getSecureConfig function
        $this->stripeSecretKey = getSecureConfig('STRIPE_SECRET_KEY');
        $this->stripePublishableKey = getSecureConfig('STRIPE_PUBLISHABLE_KEY');
        $this->webhookSecret = getSecureConfig('STRIPE_WEBHOOK_SECRET');
    }
    
    // Removed private getSecureConfig method - now using global function from secure_loader.php
    
    /**
     * Create a Stripe Checkout session for different pricing plans
     * @param string $userId User ID
     * @param string $userEmail User email
     * @param string $planType Plan type: 'monthly', 'yearly', or 'onetime'
     * @param string $successUrl Success URL
     * @param string $cancelUrl Cancel URL
     */
    public function createCheckoutSession($userId, $userEmail, $planType = 'onetime', $successUrl = null, $cancelUrl = null) {
        try {
            if (!$successUrl) {
                $successUrl = 'https://123cashcontrol.com/payment/success.php';
            }
            if (!$cancelUrl) {
                $cancelUrl = 'https://123cashcontrol.com/payment/cancel.php';
            }
            
            // Define pricing plans
            $plans = [
                'monthly' => [
                    'name' => 'CashControl Pro - Monthly Subscription',
                    'description' => 'Monthly subscription with unlimited bank scans and real-time analytics',
                    'amount' => 300, // €3.00 in cents
                    'mode' => 'subscription',
                    'recurring' => ['interval' => 'month']
                ],
                'yearly' => [
                    'name' => 'CashControl Pro - Yearly Subscription',
                    'description' => 'Yearly subscription with all features plus priority support (save €11)',
                    'amount' => 2500, // €25.00 in cents
                    'mode' => 'subscription',
                    'recurring' => ['interval' => 'year']
                ],
                'onetime' => [
                    'name' => 'CashControl - One-Time Bank Scan',
                    'description' => 'Single bank scan with PDF/CSV export and unsubscribe guides',
                    'amount' => 2500, // €25.00 in cents
                    'mode' => 'payment',
                    'recurring' => null
                ]
            ];
            
            if (!isset($plans[$planType])) {
                throw new Exception("Invalid plan type: $planType");
            }
            
            $plan = $plans[$planType];
            
            $lineItem = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $plan['name'],
                        'description' => $plan['description'],
                        'images' => ['https://123cashcontrol.com/assets/images/logo.svg']
                    ],
                    'unit_amount' => $plan['amount'],
                ],
                'quantity' => 1,
            ];
            
            // Add recurring data for subscriptions
            if ($plan['recurring']) {
                $lineItem['price_data']['recurring'] = $plan['recurring'];
            }
            
            $data = [
                'payment_method_types' => ['card', 'ideal', 'bancontact'],
                'line_items' => [$lineItem],
                'mode' => $plan['mode'],
                'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $cancelUrl,
                'customer_email' => $userEmail,
                'metadata' => [
                    'user_id' => $userId,
                    'plan_type' => $planType
                ]
            ];
            
            // Add payment intent metadata for one-time payments
            if ($plan['mode'] === 'payment') {
                $data['payment_intent_data'] = [
                    'metadata' => [
                        'user_id' => $userId,
                        'plan_type' => $planType
                    ]
                ];
            }
            
            $response = $this->makeStripeRequest('POST', 'checkout/sessions', $data);
            
            if ($response && isset($response['id'])) {
                // Save checkout session to database
                $this->saveCheckoutSession($userId, $response['id'], $response, $planType);
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
     * Record payment in database
     */
    private function recordPayment($userId, $session) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO payment_history 
                (user_id, stripe_session_id, stripe_payment_intent_id, amount, currency, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            // Get amount from session data
            $amount = 0;
            if (isset($session['amount_total'])) {
                $amount = $session['amount_total'];
            } elseif (isset($session['line_items']['data'][0]['amount_total'])) {
                $amount = $session['line_items']['data'][0]['amount_total'];
            }
            
            $stmt->execute([
                $userId,
                $session['id'],
                $session['payment_intent'] ?? null,
                $amount, // Dynamic amount from session
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
    private function saveCheckoutSession($userId, $sessionId, $sessionData, $planType = 'one_time_scan') {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO checkout_sessions 
                (user_id, stripe_session_id, session_data, plan_type, status, created_at) 
                VALUES (?, ?, ?, ?, 'pending', NOW())
            ");
            
            $stmt->execute([
                $userId,
                $sessionId,
                json_encode($sessionData),
                $planType
            ]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Save checkout session error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user has Pro access (any type)
     */
    public function hasProAccess($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT is_premium, subscription_type, subscription_status, 
                       premium_expires_at, has_scan_access, reminder_access_expires_at
                FROM users WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) return false;
            
            // Check for active subscription
            if ($user['subscription_type'] && $user['subscription_status'] === 'active') {
                // Check if subscription hasn't expired
                if (!$user['premium_expires_at'] || strtotime($user['premium_expires_at']) > time()) {
                    return true;
                }
            }
            
            // Check for one-time scan access
            if ($user['has_scan_access']) {
                return true;
            }
            
            // Legacy premium check
            return $user['is_premium'] == 1;
            
        } catch (Exception $e) {
            error_log("Pro access check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user has reminder access
     */
    public function hasReminderAccess($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT subscription_type, subscription_status, premium_expires_at, 
                       reminder_access_expires_at, has_scan_access
                FROM users WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) return false;
            
            // Active subscription users have reminder access
            if ($user['subscription_type'] && $user['subscription_status'] === 'active') {
                if (!$user['premium_expires_at'] || strtotime($user['premium_expires_at']) > time()) {
                    return true;
                }
            }
            
            // One-time scan users have reminder access for 1 year
            if ($user['has_scan_access'] && $user['reminder_access_expires_at']) {
                return strtotime($user['reminder_access_expires_at']) > time();
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Reminder access check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user's subscription details
     */
    public function getUserSubscriptionDetails($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT subscription_type, subscription_status, premium_expires_at,
                       has_scan_access, scan_access_type, reminder_access_expires_at,
                       is_premium
                FROM users WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) return null;
            
            return [
                'subscription_type' => $user['subscription_type'],
                'subscription_status' => $user['subscription_status'],
                'premium_expires_at' => $user['premium_expires_at'],
                'has_scan_access' => (bool)$user['has_scan_access'],
                'scan_access_type' => $user['scan_access_type'],
                'reminder_access_expires_at' => $user['reminder_access_expires_at'],
                'is_premium' => (bool)$user['is_premium'],
                'has_pro_access' => $this->hasProAccess($userId),
                'has_reminder_access' => $this->hasReminderAccess($userId)
            ];
            
        } catch (Exception $e) {
            error_log("Get subscription details error: " . $e->getMessage());
            return null;
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
     * Handle successful payment from Stripe checkout
     */
    public function handleSuccessfulPayment($sessionId) {
        try {
            // Retrieve the checkout session from Stripe
            $session = $this->makeStripeRequest('GET', "checkout/sessions/$sessionId");
            
            if (!$session || $session['payment_status'] !== 'paid') {
                error_log("Payment not completed for session: $sessionId");
                return false;
            }
            
            // Get user ID from session metadata or database
            $userId = null;
            if (isset($session['metadata']['user_id'])) {
                $userId = $session['metadata']['user_id'];
            } else {
                // Try to find user by email from session
                $customerEmail = $session['customer_details']['email'] ?? null;
                if ($customerEmail) {
                    $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$customerEmail]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    $userId = $user['id'] ?? null;
                }
            }
            
            if (!$userId) {
                error_log("Could not determine user ID for session: $sessionId");
                return false;
            }
            
            // Record the payment in payment history
            $this->recordPayment($userId, $session, $session['metadata']['plan_type'] ?? 'one_time_scan');
            
            // Update user to Pro status
            if ($session['metadata']['plan_type'] === 'one_time_scan') {
                $this->upgradeUserToOneTimeScan($userId, $session);
            } else {
                $this->upgradeUserToSubscription($userId, $session['metadata']['plan_type'], $session);
            }
            
            // Update checkout session status
            $stmt = $this->pdo->prepare("
                UPDATE checkout_sessions 
                SET status = 'completed', updated_at = NOW() 
                WHERE stripe_session_id = ?
            ");
            $stmt->execute([$sessionId]);
            
            // Send upgrade confirmation email
            $this->sendUpgradeConfirmationEmail($userId, $session);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Payment handling error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Handle successful payment after Stripe redirect
     * @param string $sessionId Stripe session ID
     * @return bool Success status
     */
    public function handleSuccessfulPayment($sessionId) {
        try {
            // Retrieve the session from Stripe to verify it was completed
            $response = $this->makeStripeRequest('GET', "checkout/sessions/{$sessionId}");
            
            if ($response && $response['payment_status'] === 'paid') {
                // Payment was successful - webhook should have already processed this
                // Just return true to show success page
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error handling successful payment: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user has Pro access
     * @param int $userId User ID
     * @return bool True if user has active subscription
     */
    public function hasProAccess($userId) {
        try {
            require_once __DIR__ . '/database_helper.php';
            $pdo = DatabaseHelper::getConnection();
            
            $stmt = $pdo->prepare("
                SELECT subscription_type, subscription_status 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if ($user) {
                // User has pro access if they have any paid subscription type
                return in_array($user['subscription_type'], ['monthly', 'yearly', 'one_time']) 
                       && $user['subscription_status'] === 'active';
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error checking pro access: " . $e->getMessage());
            return false;
        }
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
