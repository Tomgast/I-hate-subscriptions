<?php
session_start();
require_once '../includes/bank_service.php';
require_once '../includes/email_service.php';
require_once '../includes/subscription_manager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/signin.php');
    exit;
}

$userId = $_SESSION['user_id'];
require_once '../includes/stripe_financial_service.php';
$pdo = getDBConnection();
$stripeService = new StripeFinancialService($pdo);
$emailService = new EmailService();
$subscriptionManager = new SubscriptionManager();

try {
    // Get Stripe Financial Connections session ID from callback
    $sessionId = $_GET['session_id'] ?? null;
    
    if (!$sessionId) {
        throw new Exception('Authorization failed - no session ID received from Stripe');
    }
    
    // Handle the Stripe Financial Connections callback
    $result = $stripeService->handleCallback($sessionId);
    
    if (!$result['success']) {
        throw new Exception($result['error'] ?? 'Failed to process bank connection');
    }
    
    // Log successful callback
    error_log("Stripe Financial Connections callback successful - User ID: $userId, Session ID: $sessionId");
    
    // Check if bank accounts were connected successfully
    if ($result['status'] === 'completed' && isset($result['accounts_connected'])) {
        $connectedAccounts = $result['accounts_connected'];
        
        // Perform subscription scan on connected accounts
        $scanResult = $stripeService->scanForSubscriptions($userId);
        
        if ($scanResult['success']) {
            $totalDetectedSubscriptions = $scanResult['subscriptions_found'];
            $allDetectedSubscriptions = $scanResult['subscriptions'];
            
            // Success message
            $successMessage = "Successfully connected $connectedAccounts bank account(s) and detected $totalDetectedSubscriptions subscription(s).";
        } else {
            // Connection successful but scan failed
            $successMessage = "Successfully connected $connectedAccounts bank account(s). Subscription scan will be processed shortly.";
            $totalDetectedSubscriptions = 0;
            $allDetectedSubscriptions = [];
        }
    } else {
        throw new Exception('Bank connection was not completed successfully');
    }
    
    // Add detected subscriptions to user's account (if any were found)
    foreach ($allDetectedSubscriptions as $subscription) {
        try {
            $subscriptionManager->addSubscription($userId, [
                'name' => $subscription['name'],
                'description' => 'Auto-detected from Stripe bank scan',
                'cost' => $subscription['amount'],
                'currency' => $subscription['currency'] ?? 'USD',
                'billing_cycle' => $subscription['billing_cycle'],
                'next_payment_date' => $subscription['next_payment_date'],
                'category' => 'Other',
                'website_url' => '',
                'logo_url' => ''
            ]);
        } catch (Exception $e) {
            error_log("Failed to add detected subscription: " . $e->getMessage());
        }
    }
    
    if (!$connectionId) {
        error_log("Warning: Failed to save bank connection details for user $userId");
    }
    
    // Send success notification email
    if (isset($_SESSION['user_email']) && isset($_SESSION['user_name'])) {
        // Create a simple bank scan success email since the method might not exist
        $emailService->sendEmail(
            $_SESSION['user_email'],
            'Bank Connection Successful! 🏦',
            "<h2>Bank Connection Successful!</h2>
            <p>Hi {$_SESSION['user_name']},</p>
            <p>Great news! We've successfully connected your bank account to CashControl.</p>
            <ul>
                <li><strong>Connected Accounts:</strong> $connectedAccounts</li>
                <li><strong>Detected Subscriptions:</strong> $totalDetectedSubscriptions</li>
            </ul>
            <p>You can now view and manage all your subscriptions in your dashboard.</p>
            <p><a href='https://123cashcontrol.com/dashboard.php'>View Dashboard</a></p>
            <p>Best regards,<br>The CashControl Team</p>"
        );
    }
    
    $success = "Successfully connected $connectedAccounts bank account(s) and detected $totalDetectedSubscriptions subscriptions!";
    
} catch (Exception $e) {
    error_log("Bank callback error: " . $e->getMessage());
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Connection - CashControl</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="bg-gray-50">
    <div class="container py-6">
        <div class="max-w-md mx-auto">
            <div class="card">
                <div class="p-6 text-center">
                    <?php if (isset($success)): ?>
                    <div class="mb-4">
                        <span style="font-size: 4rem;">🏦</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-4">Bank Connected!</h1>
                    <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($success); ?></p>
                    
                    <?php if (isset($detectedPayments) && count($detectedPayments) > 0): ?>
                    <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded">
                        <p class="text-sm text-green-800">
                            🎉 Found <?php echo count($detectedPayments); ?> subscription payment(s) in your transaction history!
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <a href="../dashboard.php" class="btn btn-primary w-full">
                        Back to Dashboard
                    </a>
                    <?php else: ?>
                    <div class="mb-4">
                        <span style="font-size: 4rem;">❌</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-4">Connection Failed</h1>
                    <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($error ?? 'Unknown error'); ?></p>
                    
                    <a href="../dashboard.php" class="btn btn-primary w-full">
                        Try Again
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
