<?php
session_start();
require_once '../includes/stripe_service.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/signin.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';

// Get selected plan type from URL parameter
$planType = $_GET['plan'] ?? 'yearly';
$validPlans = ['monthly', 'yearly', 'onetime'];
if (!in_array($planType, $validPlans)) {
    $planType = 'yearly'; // Default to yearly if invalid plan
}

// Define plan details for display
$planDetails = [
    'monthly' => [
        'name' => 'CashControl Pro - Monthly',
        'description' => 'Monthly subscription with full access',
        'price' => '€3.00',
        'billing' => 'per month',
        'features' => 'Unlimited bank scans + real-time analytics + email notifications'
    ],
    'yearly' => [
        'name' => 'CashControl Pro - Yearly',
        'description' => 'Best value - save €11 per year!',
        'price' => '€25.00',
        'billing' => 'per year',
        'features' => 'All monthly features + priority support + advanced reporting'
    ],
    'onetime' => [
        'name' => 'CashControl - One-Time Scan',
        'description' => 'Perfect for subscription audit',
        'price' => '€25.00',
        'billing' => 'one-time payment',
        'features' => 'Single bank scan + PDF/CSV export + unsubscribe guides'
    ]
];

$currentPlan = $planDetails[$planType];

// Initialize Stripe service
$stripeService = new StripeService();

// Check if user already has Pro access
if ($stripeService->hasProAccess($userId)) {
    header('Location: ../dashboard.php?message=already_pro');
    exit;
}

// Handle checkout session creation
if ($_POST && isset($_POST['checkout'])) {
    $session = $stripeService->createCheckoutSession(
        $userId, 
        $userEmail,
        $planType,
        'https://123cashcontrol.com/payment/success.php',
        'https://123cashcontrol.com/upgrade.php?cancelled=1'
    );
    
    if ($session && isset($session['url'])) {
        header('Location: ' . $session['url']);
        exit;
    } else {
        $error = "Failed to create payment session. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upgrade to Pro - CashControl</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../includes/header.php'; ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="max-w-2xl mx-auto">
            <!-- Back Link -->
            <div class="mb-6">
                <a href="../upgrade.php" class="inline-flex items-center text-gray-600 hover:text-gray-900 transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Back to Plans
                </a>
            </div>

            <!-- Checkout Card -->
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                <!-- Header -->
                <div class="gradient-bg px-8 py-6 text-center">
                    <div class="text-white">
                        <h1 class="text-3xl font-bold mb-2"><?php echo htmlspecialchars($currentPlan['name']); ?></h1>
                        <p class="text-green-100"><?php echo htmlspecialchars($currentPlan['description']); ?></p>
                    </div>
                </div>

                <!-- Content -->
                <div class="px-8 py-8">
                    <?php if (isset($error)): ?>
                        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="text-red-700"><?php echo htmlspecialchars($error); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Price Display -->
                    <div class="text-center mb-8">
                        <div class="text-5xl font-bold text-gray-900 mb-2">
                            <span class="text-green-600"><?php echo htmlspecialchars($currentPlan['price']); ?></span>
                        </div>
                        <div class="text-gray-600 text-lg"><?php echo htmlspecialchars($currentPlan['billing']); ?> • <?php echo htmlspecialchars($currentPlan['features']); ?></div>
                    </div>

                    <!-- Features List -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">What's included:</h3>
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700">Unlimited subscription tracking</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700">Bank account integration (one-time scan)</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700">Smart email reminders</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700">Export data (CSV, PDF)</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700">Priority support</span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Form -->
                    <form method="POST" action="" class="space-y-6">
                        <input type="hidden" name="checkout" value="1">
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($currentPlan['name']); ?></span>
                                <span class="text-gray-900 font-bold"><?php echo htmlspecialchars($currentPlan['price']); ?></span>
                            </div>
                            <div class="text-sm text-gray-600"><?php echo htmlspecialchars($currentPlan['billing']); ?> • <?php echo htmlspecialchars($currentPlan['features']); ?></div>
                        </div>
                        
                        <button type="submit" class="w-full gradient-bg text-white px-6 py-4 rounded-lg font-semibold text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            Proceed to Secure Checkout
                        </button>
                        
                        <div class="text-center text-sm text-gray-500">
                            <div class="flex items-center justify-center mb-2">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                                Secured by Stripe
                            </div>
                            <div>We accept all major credit cards, iDEAL, and Bancontact</div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Trust Indicators -->
            <div class="mt-8 text-center">
                <div class="flex items-center justify-center space-x-6 text-gray-500">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        <span class="text-sm">SSL Encrypted</span>
                    </div>
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span class="text-sm">Secure Payment</span>
                    </div>
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                        <span class="text-sm">30-day Support</span>
                    </div>
                </div>
            </div>
        </div>
    </div>


</body>
</html>
