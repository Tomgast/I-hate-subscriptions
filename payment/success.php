<?php
// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start();
require_once '../includes/stripe_service.php';
require_once '../includes/plan_manager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/signin.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';

// Get session ID from URL
$sessionId = $_GET['session_id'] ?? null;

if (!$sessionId) {
    header('Location: ../dashboard.php?error=invalid_session');
    exit;
}

// Handle payment with StripeService
try {
    $stripeService = new StripeService();
    $success = $stripeService->handleSuccessfulPayment($sessionId);
    
    if ($success) {
        // Get updated plan information directly from database
        require_once '../includes/database_helper.php';
        $pdo = DatabaseHelper::getConnection();
        
        $stmt = $pdo->prepare("
            SELECT subscription_type, subscription_status 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        // Update session
        $_SESSION['is_premium'] = true;
        $_SESSION['is_paid'] = true;
        
        // Set success message based on plan type
        if ($user && isset($user['subscription_type'])) {
            switch ($user['subscription_type']) {
                case 'monthly':
                    $message = "Welcome to CashControl Pro! Your monthly subscription (€3/month) has been activated.";
                    break;
                case 'yearly':
                    $message = "Welcome to CashControl Pro! Your yearly subscription (€25/year) has been activated. You saved 31%!";
                    break;
                case 'one_time':
                    $message = "Welcome to CashControl! Your one-time bank scan (€25) has been activated.";
                    break;
                default:
                    $message = "Welcome to CashControl Pro! Your subscription has been activated.";
            }
        } else {
            $message = "Welcome to CashControl Pro! Your subscription has been activated.";
        }
    } else {
        $error = "Payment verification failed. Please contact support.";
    }
    
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .celebration-animation {
            animation: bounce 1s ease-in-out infinite alternate;
        }
        @keyframes bounce {
            0% { transform: translateY(0px); }
            100% { transform: translateY(-10px); }
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../includes/header.php'; ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="max-w-2xl mx-auto">
            <?php if ($success): ?>
                <!-- Success Card -->
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                    <!-- Success Header -->
                    <div class="gradient-bg px-8 py-8 text-center">
                        <div class="celebration-animation text-6xl mb-4">🎉</div>
                        <h1 class="text-3xl font-bold text-white mb-2">Welcome to CashControl Pro!</h1>
                        <p class="text-green-100 text-lg">Your payment was successful</p>
                    </div>

                    <!-- Success Content -->
                    <div class="px-8 py-8">
                        <div class="text-center mb-8">
                            <div class="inline-flex items-center px-4 py-2 bg-green-50 border border-green-200 rounded-full text-green-700 font-medium mb-4">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Payment Confirmed
                            </div>
                            <p class="text-gray-600 text-lg">You now have lifetime access to all Pro features!</p>
                        </div>

                        <!-- Features Unlocked -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 text-center">🚀 Features Unlocked</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex items-center p-4 bg-green-50 rounded-lg border border-green-100">
                                    <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700 font-medium">Unlimited subscription tracking</span>
                                </div>
                                <div class="flex items-center p-4 bg-green-50 rounded-lg border border-green-100">
                                    <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700 font-medium">Bank account integration</span>
                                </div>
                                <div class="flex items-center p-4 bg-green-50 rounded-lg border border-green-100">
                                    <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700 font-medium">Smart email reminders</span>
                                </div>
                                <div class="flex items-center p-4 bg-green-50 rounded-lg border border-green-100">
                                    <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700 font-medium">Export data (CSV, PDF)</span>
                                </div>
                                <div class="flex items-center p-4 bg-green-50 rounded-lg border border-green-100">
                                    <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700 font-medium">Priority support</span>
                                </div>
                                <div class="flex items-center p-4 bg-green-50 rounded-lg border border-green-100">
                                    <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-700 font-medium">Lifetime access</span>
                                </div>
                            </div>
                        </div>

                        <!-- Next Steps -->
                        <div class="bg-blue-50 rounded-lg p-6 mb-6">
                            <h4 class="font-semibold text-blue-900 mb-2">🎯 Next Steps</h4>
                            <ul class="text-blue-800 space-y-1 text-sm">
                                <li>• Head to your dashboard to start tracking subscriptions</li>
                                <li>• Connect your bank account for automatic discovery</li>
                                <li>• Set up email reminders for upcoming renewals</li>
                            </ul>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row gap-4">
                            <a href="../dashboard.php" class="flex-1 gradient-bg text-white px-6 py-4 rounded-lg font-semibold text-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                Go to Dashboard
                            </a>
                            <a href="../bank/connect.php" class="flex-1 bg-white border-2 border-gray-200 text-gray-700 px-6 py-4 rounded-lg font-semibold text-center hover:border-green-300 hover:bg-green-50 transition-all duration-200 flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                Connect Bank
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Error Card -->
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                    <!-- Error Header -->
                    <div class="bg-red-500 px-8 py-8 text-center">
                        <div class="text-6xl mb-4">❌</div>
                        <h1 class="text-3xl font-bold text-white mb-2">Payment Failed</h1>
                        <p class="text-red-100 text-lg">There was an issue processing your payment</p>
                    </div>

                    <!-- Error Content -->
                    <div class="px-8 py-8 text-center">
                        <div class="mb-8">
                            <p class="text-gray-600 text-lg mb-4">Don&rsquo;t worry, no charges were made to your account.</p>
                            <p class="text-gray-500">Please try again or contact support if the problem persists.</p>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-4">
                            <a href="../upgrade.php" class="flex-1 gradient-bg text-white px-6 py-4 rounded-lg font-semibold text-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                                Try Again
                            </a>
                            <a href="../dashboard.php" class="flex-1 bg-white border-2 border-gray-200 text-gray-700 px-6 py-4 rounded-lg font-semibold text-center hover:border-gray-300 hover:bg-gray-50 transition-all duration-200">
                                Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Support Info -->
            <div class="mt-8 text-center">
                <p class="text-gray-500 text-sm mb-2">Need help? Contact our support team</p>
                <div class="flex items-center justify-center space-x-4 text-gray-400">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span class="text-sm">info@123cashcontrol.com</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
