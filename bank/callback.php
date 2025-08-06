<?php
session_start();
require_once '../includes/bank_integration.php';
require_once '../config/email.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/signin.php');
    exit;
}

$userId = $_SESSION['user_id'];
$bankIntegration = new BankIntegration();
$emailService = new EmailService();

try {
    // Get authorization code from callback
    $code = $_GET['code'] ?? null;
    $state = $_GET['state'] ?? null;
    
    if (!$code) {
        throw new Exception('Authorization failed');
    }
    
    // Verify state parameter
    $stateData = json_decode(base64_decode($state), true);
    if ($stateData['user_id'] != $userId) {
        throw new Exception('Invalid state parameter');
    }
    
    // Exchange code for access token
    $tokenResponse = $bankIntegration->exchangeCodeForToken($code);
    $accessToken = $tokenResponse['access_token'];
    
    // Get user's accounts
    $accountsResponse = $bankIntegration->getAccounts($accessToken);
    $accounts = $accountsResponse['results'] ?? [];
    
    $connectedAccounts = 0;
    
    foreach ($accounts as $account) {
        // Store account in database
        $bankIntegration->storeBankAccount($userId, $account, $accessToken);
        $connectedAccounts++;
        
        // Get recent transactions for subscription detection
        $fromDate = date('Y-m-d', strtotime('-3 months'));
        $transactionsResponse = $bankIntegration->getTransactions($accessToken, $account['account_id'], $fromDate);
        $transactions = $transactionsResponse['results'] ?? [];
        
        // Detect subscription payments
        $detectedPayments = $bankIntegration->detectSubscriptionPayments($userId, $transactions);
    }
    
    // Send success email
    $emailService->sendBankScanEmail($_SESSION['user_email'], $_SESSION['user_name'], $connectedAccounts, count($detectedPayments ?? []));
    
    $success = "Successfully connected $connectedAccounts bank account(s)!";
    
} catch (Exception $e) {
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
