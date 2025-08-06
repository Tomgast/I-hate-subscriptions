<?php
session_start();
require_once '../config/db_config.php';
require_once '../config/email.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/signin.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';

// Update user to Pro status
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE users SET is_pro = 1 WHERE id = ?");
    $stmt->execute([$userId]);
    
    // Update session
    $_SESSION['is_paid'] = true;
    
    // Send upgrade confirmation email
    $emailService = new EmailService();
    $emailService->sendUpgradeEmail($userEmail, $userName);
    
    $success = true;
} catch (Exception $e) {
    $success = false;
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - CashControl</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="bg-gray-50">
    <div class="container py-6">
        <div class="max-w-md mx-auto">
            <div class="card">
                <div class="p-6 text-center">
                    <?php if ($success): ?>
                    <div class="mb-4">
                        <span style="font-size: 4rem;">🎉</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-4">Welcome to Pro!</h1>
                    <p class="text-gray-600 mb-6">Your payment was successful. You now have access to all Pro features including bank integration and advanced analytics.</p>
                    
                    <div class="space-y-3 mb-6">
                        <div class="text-left">
                            <span style="color: #10b981; margin-right: 0.5rem;">✓</span>
                            <span class="text-sm">Unlimited subscriptions</span>
                        </div>
                        <div class="text-left">
                            <span style="color: #10b981; margin-right: 0.5rem;">✓</span>
                            <span class="text-sm">Bank account integration</span>
                        </div>
                        <div class="text-left">
                            <span style="color: #10b981; margin-right: 0.5rem;">✓</span>
                            <span class="text-sm">Email reminders</span>
                        </div>
                        <div class="text-left">
                            <span style="color: #10b981; margin-right: 0.5rem;">✓</span>
                            <span class="text-sm">Advanced analytics</span>
                        </div>
                    </div>
                    
                    <a href="../dashboard.php" class="btn btn-primary w-full">
                        Go to Dashboard
                    </a>
                    <?php else: ?>
                    <div class="mb-4">
                        <span style="font-size: 4rem;">❌</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-4">Payment Failed</h1>
                    <p class="text-gray-600 mb-6">There was an issue processing your payment. Please try again.</p>
                    
                    <a href="../upgrade.php" class="btn btn-primary w-full">
                        Try Again
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
