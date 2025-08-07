<?php
/**
 * PHASE 4: PAYMENT CANCEL PAGE
 * Handle cancelled payment attempts with proper messaging and next steps
 */

session_start();
require_once '../includes/plan_manager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/signin.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'User';

// Get user's current plan
$planManager = getPlanManager();
$userPlan = $planManager->getUserPlan($userId);

$pageTitle = 'Payment Cancelled';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - CashControl</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <?php include '../includes/header.php'; ?>

    <div class="container mx-auto px-4 py-16">
        <div class="max-w-2xl mx-auto text-center">
            <!-- Cancel Icon -->
            <div class="mb-8">
                <div class="w-24 h-24 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-times-circle text-yellow-600 text-4xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-4">Payment Cancelled</h1>
                <p class="text-lg text-gray-600 mb-8">
                    No worries! Your payment was cancelled and no charges were made to your account.
                </p>
            </div>

            <!-- Current Plan Status -->
            <?php if ($userPlan && $userPlan['is_active']): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-green-900 mb-2">
                    <i class="fas fa-check-circle text-green-600 mr-2"></i>
                    You still have access
                </h3>
                <p class="text-green-800">
                    Your current <strong><?php echo ucfirst($userPlan['plan_type']); ?></strong> plan is still active.
                    <?php if ($userPlan['plan_expires_at']): ?>
                    It expires on <?php echo date('F j, Y', strtotime($userPlan['plan_expires_at'])); ?>.
                    <?php endif; ?>
                </p>
            </div>
            <?php else: ?>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-blue-900 mb-2">
                    <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                    Ready when you are
                </h3>
                <p class="text-blue-800">
                    You can try again anytime. Our secure payment system is always available.
                </p>
            </div>
            <?php endif; ?>

            <!-- What happened section -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8 text-left">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-question-circle text-gray-600 mr-2"></i>
                    What happened?
                </h3>
                <ul class="space-y-2 text-gray-700">
                    <li class="flex items-start">
                        <i class="fas fa-dot-circle text-gray-400 mr-3 mt-1 text-xs"></i>
                        You clicked "Cancel" or closed the payment window
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-dot-circle text-gray-400 mr-3 mt-1 text-xs"></i>
                        Your browser's back button was used during payment
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-dot-circle text-gray-400 mr-3 mt-1 text-xs"></i>
                        The payment session timed out
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-dot-circle text-gray-400 mr-3 mt-1 text-xs"></i>
                        There was a technical issue with the payment processor
                    </li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-4">
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="../upgrade.php" 
                       class="inline-flex items-center justify-center px-6 py-3 gradient-bg text-white rounded-lg hover:opacity-90 transition-opacity font-medium">
                        <i class="fas fa-credit-card mr-2"></i>
                        Try Payment Again
                    </a>
                    <a href="../dashboard.php" 
                       class="inline-flex items-center justify-center px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors font-medium">
                        <i class="fas fa-tachometer-alt mr-2"></i>
                        Go to Dashboard
                    </a>
                </div>
                
                <div class="text-center">
                    <a href="../index.php" class="text-gray-600 hover:text-gray-900 transition-colors">
                        <i class="fas fa-home mr-1"></i>
                        Back to Homepage
                    </a>
                </div>
            </div>

            <!-- Help Section -->
            <div class="mt-12 bg-gray-50 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">
                    <i class="fas fa-life-ring text-gray-600 mr-2"></i>
                    Need Help?
                </h3>
                <p class="text-gray-700 mb-4">
                    If you're experiencing issues with payment or have questions about our plans, we're here to help.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="mailto:support@123cashcontrol.com" 
                       class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                        <i class="fas fa-envelope mr-2"></i>
                        Email Support
                    </a>
                    <a href="../guides/index.php" 
                       class="inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm">
                        <i class="fas fa-question-circle mr-2"></i>
                        Browse Help Guides
                    </a>
                </div>
            </div>

            <!-- Security Notice -->
            <div class="mt-8 text-sm text-gray-500">
                <i class="fas fa-shield-alt mr-1"></i>
                Your payment information is always secure and encrypted. We never store your card details.
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-16">
        <div class="container mx-auto px-4 py-8 text-center text-gray-600">
            <p>&copy; <?php echo date('Y'); ?> CashControl. Secure subscription management.</p>
        </div>
    </footer>
</body>
</html>
