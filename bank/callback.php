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
$bankService = new BankService();
$emailService = new EmailService();
$subscriptionManager = new SubscriptionManager();

try {
    // Get authorization code from callback
    $code = $_GET['code'] ?? null;
    $state = $_GET['state'] ?? null;
    
    if (!$code) {
        throw new Exception('Authorization failed - no authorization code received');
    }
    
    // Verify state parameter
    $stateData = json_decode(base64_decode($state), true);
    
    // Debug logging for state parameter issues
    error_log("Callback state validation - Session User ID: $userId, State User ID: " . ($stateData['user_id'] ?? 'null'));
    
    if (!$stateData) {
        throw new Exception('Invalid state parameter - could not decode state data');
    }
    
    if (!isset($stateData['user_id'])) {
        throw new Exception('Invalid state parameter - missing user_id in state');
    }
    
    // Convert both to integers for comparison to handle string vs int issues
    $sessionUserId = (int)$userId;
    $stateUserId = (int)$stateData['user_id'];
    
    if ($stateUserId !== $sessionUserId) {
        throw new Exception("Invalid state parameter - user ID mismatch (session: $sessionUserId, state: $stateUserId)");
    }
    
    // Exchange code for access token
    $tokenData = $bankService->exchangeCodeForToken($code);
    
    if (!$tokenData || !isset($tokenData['access_token'])) {
        throw new Exception('Failed to exchange authorization code for access token');
    }
    
    $accessToken = $tokenData['access_token'];
    $refreshToken = $tokenData['refresh_token'] ?? null;
    
    // Get user's bank accounts
    $accounts = $bankService->getBankAccounts($accessToken);
    
    if ($accounts === false || empty($accounts)) {
        throw new Exception('Failed to retrieve bank accounts or no accounts found');
    }
    
    $connectedAccounts = 0;
    $totalDetectedSubscriptions = 0;
    $allDetectedSubscriptions = [];
    
    foreach ($accounts as $account) {
        $connectedAccounts++;
        
        // Get recent transactions for subscription detection (last 3 months)
        $fromDate = date('Y-m-d', strtotime('-90 days'));
        $toDate = date('Y-m-d');
        
        $transactions = $bankService->getAccountTransactions($accessToken, $account['account_id'], $fromDate, $toDate);
        
        if ($transactions && !empty($transactions)) {
            // Detect potential subscriptions from transaction patterns
            $detectedSubscriptions = $bankService->detectSubscriptions($transactions);
            
            // Add detected subscriptions to user's account
            foreach ($detectedSubscriptions as $subscription) {
                // Only add high-confidence subscriptions automatically
                if ($subscription['confidence'] >= 70) {
                    try {
                        $subscriptionManager->addSubscription($userId, [
                            'name' => $subscription['name'],
                            'description' => 'Auto-detected from bank transactions',
                            'cost' => $subscription['amount'],
                            'currency' => $subscription['currency'],
                            'billing_cycle' => $subscription['billing_cycle'],
                            'next_payment_date' => $subscription['next_payment_date'],
                            'category' => 'Other',
                            'website_url' => '',
                            'logo_url' => ''
                        ]);
                        
                        $totalDetectedSubscriptions++;
                        $allDetectedSubscriptions[] = $subscription;
                    } catch (Exception $e) {
                        error_log("Failed to add detected subscription: " . $e->getMessage());
                    }
                }
            }
        }
    }
    
    // Save bank connection details
    $bankName = 'Connected Bank'; // TrueLayer doesn't always provide bank name
    $connectionId = $bankService->saveBankConnection($userId, $accessToken, $refreshToken, $bankName, $accounts);
    
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
