<?php
/**
 * Stripe Webhook Handler for CashControl
 * Processes Stripe webhook events for payments, subscriptions, and customer updates
 */

// Disable output buffering and set content type
ob_clean();
header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../config/secure_loader.php';
require_once __DIR__ . '/../includes/email_service.php';

// Log webhook received
error_log("Stripe webhook received at " . date('Y-m-d H:i:s'));

try {
    // Get the webhook payload
    $payload = @file_get_contents('php://input');
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
    
    if (empty($payload) || empty($sig_header)) {
        error_log("Stripe webhook: Missing payload or signature");
        http_response_code(400);
        echo json_encode(['error' => 'Missing payload or signature']);
        exit;
    }
    
    // Get webhook secret
    $webhook_secret = getSecureConfig('STRIPE_WEBHOOK_SECRET');
    if (empty($webhook_secret)) {
        error_log("Stripe webhook: Webhook secret not configured");
        http_response_code(500);
        echo json_encode(['error' => 'Webhook secret not configured']);
        exit;
    }
    
    // Verify webhook signature
    $event = null;
    try {
        $event = verifyWebhookSignature($payload, $sig_header, $webhook_secret);
    } catch (Exception $e) {
        error_log("Stripe webhook signature verification failed: " . $e->getMessage());
        http_response_code(400);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }
    
    // Log the event type
    error_log("Stripe webhook event: " . $event['type']);
    
    // Get database connection
    $pdo = getDBConnection();
    
    // Process the event
    switch ($event['type']) {
        case 'checkout.session.completed':
            handleCheckoutSessionCompleted($pdo, $event['data']['object']);
            break;
            
        case 'checkout.session.expired':
            handleCheckoutSessionExpired($pdo, $event['data']['object']);
            break;
            
        case 'payment_intent.succeeded':
            handlePaymentIntentSucceeded($pdo, $event['data']['object']);
            break;
            
        case 'payment_intent.payment_failed':
            handlePaymentIntentFailed($pdo, $event['data']['object']);
            break;
            
        case 'customer.subscription.created':
            handleSubscriptionCreated($pdo, $event['data']['object']);
            break;
            
        case 'customer.subscription.updated':
            handleSubscriptionUpdated($pdo, $event['data']['object']);
            break;
            
        case 'customer.subscription.deleted':
            handleSubscriptionDeleted($pdo, $event['data']['object']);
            break;
            
        case 'invoice.payment_succeeded':
            handleInvoicePaymentSucceeded($pdo, $event['data']['object']);
            break;
            
        case 'invoice.payment_failed':
            handleInvoicePaymentFailed($pdo, $event['data']['object']);
            break;
            
        case 'customer.created':
            handleCustomerCreated($pdo, $event['data']['object']);
            break;
            
        case 'customer.updated':
            handleCustomerUpdated($pdo, $event['data']['object']);
            break;
            
        default:
            error_log("Stripe webhook: Unhandled event type: " . $event['type']);
            break;
    }
    
    // Return success response
    http_response_code(200);
    echo json_encode(['status' => 'success']);
    
} catch (Exception $e) {
    error_log("Stripe webhook error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

/**
 * Verify Stripe webhook signature
 */
function verifyWebhookSignature($payload, $sig_header, $webhook_secret) {
    $elements = explode(',', $sig_header);
    $signature = null;
    $timestamp = null;
    
    foreach ($elements as $element) {
        list($key, $value) = explode('=', $element, 2);
        if ($key === 'v1') {
            $signature = $value;
        } elseif ($key === 't') {
            $timestamp = $value;
        }
    }
    
    if (!$signature || !$timestamp) {
        throw new Exception('Invalid signature format');
    }
    
    // Check timestamp (prevent replay attacks)
    if (abs(time() - $timestamp) > 300) { // 5 minutes tolerance
        throw new Exception('Timestamp too old');
    }
    
    // Verify signature
    $signed_payload = $timestamp . '.' . $payload;
    $expected_signature = hash_hmac('sha256', $signed_payload, $webhook_secret);
    
    if (!hash_equals($expected_signature, $signature)) {
        throw new Exception('Signature verification failed');
    }
    
    return json_decode($payload, true);
}

/**
 * Handle successful checkout session
 */
function handleCheckoutSessionCompleted($pdo, $session) {
    error_log("Processing checkout.session.completed: " . $session['id']);
    
    try {
        // Get user ID from session metadata
        $userId = $session['metadata']['user_id'] ?? null;
        $planType = $session['metadata']['plan_type'] ?? 'monthly';
        
        if (!$userId) {
            // Try to find user by customer email
            $customerEmail = $session['customer_details']['email'] ?? null;
            if ($customerEmail) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$customerEmail]);
                $user = $stmt->fetch();
                $userId = $user['id'] ?? null;
            }
        }
        
        if (!$userId) {
            error_log("Could not determine user ID for session: " . $session['id']);
            return;
        }
        
        // Update user subscription based on plan type
        if ($planType === 'onetime') {
            // One-time scan: Set reminder access for 1 year
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 year'));
            $stmt = $pdo->prepare("
                UPDATE users 
                SET subscription_type = 'one_time', 
                    subscription_status = 'active',
                    reminder_access_expires_at = ?,
                    stripe_customer_id = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$expiresAt, $session['customer'], $userId]);
        } else {
            // Monthly/yearly subscription
            $expiresAt = null;
            if ($planType === 'monthly') {
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 month'));
            } elseif ($planType === 'yearly') {
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 year'));
            }
            
            $stmt = $pdo->prepare("
                UPDATE users 
                SET subscription_type = ?, 
                    subscription_status = 'active',
                    subscription_expires_at = ?,
                    reminder_access_expires_at = ?,
                    stripe_customer_id = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$planType, $expiresAt, $expiresAt, $session['customer'], $userId]);
        }
        
        // Record payment in payment history
        $stmt = $pdo->prepare("
            INSERT INTO payment_history (user_id, stripe_session_id, amount, currency, plan_type, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'completed', NOW())
            ON DUPLICATE KEY UPDATE status = 'completed', updated_at = NOW()
        ");
        $stmt->execute([
            $userId,
            $session['id'],
            $session['amount_total'],
            $session['currency'],
            $planType
        ]);
        
        // Send upgrade confirmation email
        $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user) {
            $emailService = new EmailService();
            $emailService->sendUpgradeConfirmation($user['email'], $user['name']);
        }
        
        error_log("Successfully processed checkout completion for user: $userId, plan: $planType");
        
    } catch (Exception $e) {
        error_log("Error processing checkout.session.completed: " . $e->getMessage());
    }
}

/**
 * Handle expired checkout session
 */
function handleCheckoutSessionExpired($pdo, $session) {
    error_log("Processing checkout.session.expired: " . $session['id']);
    
    // Update checkout session status if exists
    $stmt = $pdo->prepare("
        UPDATE checkout_sessions 
        SET status = 'expired', updated_at = NOW() 
        WHERE stripe_session_id = ?
    ");
    $stmt->execute([$session['id']]);
}

/**
 * Handle successful payment intent (for one-time payments)
 */
function handlePaymentIntentSucceeded($pdo, $paymentIntent) {
    error_log("Processing payment_intent.succeeded: " . $paymentIntent['id']);
    
    // This is typically handled by checkout.session.completed
    // But we can log it for completeness
}

/**
 * Handle failed payment intent
 */
function handlePaymentIntentFailed($pdo, $paymentIntent) {
    error_log("Processing payment_intent.payment_failed: " . $paymentIntent['id']);
    
    // Log failed payment attempt
    $userId = $paymentIntent['metadata']['user_id'] ?? null;
    if ($userId) {
        $stmt = $pdo->prepare("
            INSERT INTO payment_history (user_id, stripe_payment_intent_id, amount, currency, status, created_at)
            VALUES (?, ?, ?, ?, 'failed', NOW())
        ");
        $stmt->execute([
            $userId,
            $paymentIntent['id'],
            $paymentIntent['amount'],
            $paymentIntent['currency']
        ]);
    }
}

/**
 * Handle subscription created
 */
function handleSubscriptionCreated($pdo, $subscription) {
    error_log("Processing customer.subscription.created: " . $subscription['id']);
    
    // Find user by Stripe customer ID
    $stmt = $pdo->prepare("SELECT id FROM users WHERE stripe_customer_id = ?");
    $stmt->execute([$subscription['customer']]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Update user subscription status
        $planType = count($subscription['items']['data']) > 0 ? 'monthly' : 'yearly'; // Determine from price
        
        $stmt = $pdo->prepare("
            UPDATE users 
            SET subscription_type = ?,
                subscription_status = 'active',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$planType, $user['id']]);
    }
}

/**
 * Handle subscription updated
 */
function handleSubscriptionUpdated($pdo, $subscription) {
    error_log("Processing customer.subscription.updated: " . $subscription['id']);
    
    // Find user by Stripe customer ID
    $stmt = $pdo->prepare("SELECT id FROM users WHERE stripe_customer_id = ?");
    $stmt->execute([$subscription['customer']]);
    $user = $stmt->fetch();
    
    if ($user) {
        $status = $subscription['status'] === 'active' ? 'active' : 'cancelled';
        $expiresAt = date('Y-m-d H:i:s', $subscription['current_period_end']);
        
        $stmt = $pdo->prepare("
            UPDATE users 
            SET subscription_status = ?,
                subscription_expires_at = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$status, $expiresAt, $user['id']]);
    }
}

/**
 * Handle subscription deleted/cancelled
 */
function handleSubscriptionDeleted($pdo, $subscription) {
    error_log("Processing customer.subscription.deleted: " . $subscription['id']);
    
    // Find user by Stripe customer ID
    $stmt = $pdo->prepare("SELECT id FROM users WHERE stripe_customer_id = ?");
    $stmt->execute([$subscription['customer']]);
    $user = $stmt->fetch();
    
    if ($user) {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET subscription_status = 'cancelled',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$user['id']]);
    }
}

/**
 * Handle successful invoice payment (recurring payments)
 */
function handleInvoicePaymentSucceeded($pdo, $invoice) {
    error_log("Processing invoice.payment_succeeded: " . $invoice['id']);
    
    // Find user by Stripe customer ID
    $stmt = $pdo->prepare("SELECT id FROM users WHERE stripe_customer_id = ?");
    $stmt->execute([$invoice['customer']]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Record successful recurring payment
        $stmt = $pdo->prepare("
            INSERT INTO payment_history (user_id, stripe_invoice_id, amount, currency, status, created_at)
            VALUES (?, ?, ?, ?, 'completed', NOW())
        ");
        $stmt->execute([
            $user['id'],
            $invoice['id'],
            $invoice['amount_paid'],
            $invoice['currency']
        ]);
        
        // Update subscription status for recurring payment
        if ($invoice['subscription']) {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET subscription_status = 'active',
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$user['id']]);
        }
    }
}

/**
 * Handle failed invoice payment
 */
function handleInvoicePaymentFailed($pdo, $invoice) {
    error_log("Processing invoice.payment_failed: " . $invoice['id']);
    
    // Find user by Stripe customer ID
    $stmt = $pdo->prepare("SELECT id FROM users WHERE stripe_customer_id = ?");
    $stmt->execute([$invoice['customer']]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Record failed payment
        $stmt = $pdo->prepare("
            INSERT INTO payment_history (user_id, stripe_invoice_id, amount, currency, status, created_at)
            VALUES (?, ?, ?, ?, 'failed', NOW())
        ");
        $stmt->execute([
            $user['id'],
            $invoice['id'],
            $invoice['amount_due'],
            $invoice['currency']
        ]);
        
        // Update subscription status to past_due
        $stmt = $pdo->prepare("
            UPDATE users 
            SET subscription_status = 'past_due',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$user['id']]);
    }
}

/**
 * Handle customer created
 */
function handleCustomerCreated($pdo, $customer) {
    error_log("Processing customer.created: " . $customer['id']);
    
    // Update user with Stripe customer ID if email matches
    if (!empty($customer['email'])) {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET stripe_customer_id = ?,
                updated_at = NOW()
            WHERE email = ? AND stripe_customer_id IS NULL
        ");
        $stmt->execute([$customer['id'], $customer['email']]);
    }
}

/**
 * Handle customer updated
 */
function handleCustomerUpdated($pdo, $customer) {
    error_log("Processing customer.updated: " . $customer['id']);
    
    // Update user information if needed
    if (!empty($customer['email'])) {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET updated_at = NOW()
            WHERE stripe_customer_id = ?
        ");
        $stmt->execute([$customer['id']]);
    }
}
?>
