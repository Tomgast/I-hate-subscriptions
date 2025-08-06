<?php
session_start();
require_once 'config/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/signin.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';
$isPaid = $_SESSION['is_paid'] ?? false;

// If already pro, redirect to dashboard
if ($isPaid) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upgrade to Pro - CashControl</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-content">
            <div class="navbar-brand">
                <span class="icon-credit-card mr-3" style="font-size: 2rem;"></span>
                <span>CashControl</span>
            </div>
            
            <div class="navbar-user">
                <a href="dashboard.php" class="text-blue-600" style="text-decoration: none;">← Back to Dashboard</a>
                <span class="text-sm text-gray-700">Welcome, <?php echo htmlspecialchars($userName); ?></span>
                <span class="badge badge-gray">Free Plan</span>
                <a href="auth/logout.php" class="text-gray-500" style="text-decoration: none;">
                    <span class="icon-logout"></span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-6">
        <!-- Hero Section -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Upgrade to CashControl Pro</h1>
            <p class="text-lg text-gray-600">Unlock powerful features to take control of your subscriptions</p>
        </div>

        <!-- Pricing Card -->
        <div class="max-w-md mx-auto mb-8">
            <div class="card" style="border: 2px solid #2563eb;">
                <div class="p-6 text-center">
                    <div class="mb-4">
                        <span class="icon-star" style="font-size: 3rem; color: #2563eb;"></span>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Pro Plan</h2>
                    <div class="mb-4">
                        <span class="text-4xl font-bold text-blue-600">€29</span>
                        <span class="text-gray-500">/year</span>
                    </div>
                    <p class="text-gray-600 mb-6">Everything you need to master your subscriptions</p>
                    
                    <button onclick="startUpgrade()" class="btn btn-primary w-full mb-4" style="font-size: 1.1rem; padding: 1rem;">
                        Upgrade Now
                    </button>
                    
                    <p class="text-xs text-gray-500">30-day money-back guarantee</p>
                </div>
            </div>
        </div>

        <!-- Features Comparison -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Free Plan -->
            <div class="card">
                <div class="p-5">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Free Plan</h3>
                    <ul class="space-y-3">
                        <li class="flex items-center">
                            <span style="color: #10b981; margin-right: 0.5rem;">✓</span>
                            <span class="text-sm text-gray-600">Manual subscription tracking</span>
                        </li>
                        <li class="flex items-center">
                            <span style="color: #10b981; margin-right: 0.5rem;">✓</span>
                            <span class="text-sm text-gray-600">Basic dashboard</span>
                        </li>
                        <li class="flex items-center">
                            <span style="color: #10b981; margin-right: 0.5rem;">✓</span>
                            <span class="text-sm text-gray-600">Up to 10 subscriptions</span>
                        </li>
                        <li class="flex items-center">
                            <span style="color: #ef4444; margin-right: 0.5rem;">✗</span>
                            <span class="text-sm text-gray-400">Bank account integration</span>
                        </li>
                        <li class="flex items-center">
                            <span style="color: #ef4444; margin-right: 0.5rem;">✗</span>
                            <span class="text-sm text-gray-400">Automatic payment detection</span>
                        </li>
                        <li class="flex items-center">
                            <span style="color: #ef4444; margin-right: 0.5rem;">✗</span>
                            <span class="text-sm text-gray-400">Email reminders</span>
                        </li>
                        <li class="flex items-center">
                            <span style="color: #ef4444; margin-right: 0.5rem;">✗</span>
                            <span class="text-sm text-gray-400">Advanced analytics</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Pro Plan -->
            <div class="card" style="border: 2px solid #2563eb;">
                <div class="p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Pro Plan</h3>
                        <span class="badge badge-blue">Recommended</span>
                    </div>
                    <ul class="space-y-3">
                        <li class="flex items-center">
                            <span style="color: #10b981; margin-right: 0.5rem;">✓</span>
                            <span class="text-sm text-gray-600">Everything in Free</span>
                        </li>
                        <li class="flex items-center">
                            <span style="color: #10b981; margin-right: 0.5rem;">✓</span>
                            <span class="text-sm text-gray-600">Unlimited subscriptions</span>
                        </li>
                        <li class="flex items-center">
                            <span style="color: #10b981; margin-right: 0.5rem;">✓</span>
                            <span class="text-sm text-gray-600">Bank account integration</span>
                        </li>
                        <li class="flex items-center">
                            <span style="color: #10b981; margin-right: 0.5rem;">✓</span>
                            <span class="text-sm text-gray-600">Automatic payment detection</span>
                        </li>
                        <li class="flex items-center">
                            <span style="color: #10b981; margin-right: 0.5rem;">✓</span>
                            <span class="text-sm text-gray-600">Smart email reminders</span>
                        </li>
                        <li class="flex items-center">
                            <span style="color: #10b981; margin-right: 0.5rem;">✓</span>
                            <span class="text-sm text-gray-600">Advanced analytics & insights</span>
                        </li>
                        <li class="flex items-center">
                            <span style="color: #10b981; margin-right: 0.5rem;">✓</span>
                            <span class="text-sm text-gray-600">Export data (CSV, PDF)</span>
                        </li>
                        <li class="flex items-center">
                            <span style="color: #10b981; margin-right: 0.5rem;">✓</span>
                            <span class="text-sm text-gray-600">Priority support</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Benefits Section -->
        <div class="card mb-8">
            <div class="p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6 text-center">Why Upgrade to Pro?</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="mb-3">
                            <span style="font-size: 2.5rem;">🏦</span>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Bank Integration</h3>
                        <p class="text-sm text-gray-600">Connect your bank accounts and automatically detect subscription payments. No more manual tracking!</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="mb-3">
                            <span style="font-size: 2.5rem;">🔔</span>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Smart Reminders</h3>
                        <p class="text-sm text-gray-600">Get email notifications before payments are due. Never miss a renewal or forget to cancel again.</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="mb-3">
                            <span style="font-size: 2.5rem;">📊</span>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Advanced Analytics</h3>
                        <p class="text-sm text-gray-600">Detailed insights into your spending patterns, trends, and opportunities to save money.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="card">
            <div class="p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6 text-center">Frequently Asked Questions</h2>
                
                <div class="space-y-4">
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-2">Is my bank data secure?</h3>
                        <p class="text-sm text-gray-600">Yes! We use bank-grade encryption and never store your banking credentials. We only access transaction data through secure, read-only connections.</p>
                    </div>
                    
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-2">Can I cancel anytime?</h3>
                        <p class="text-sm text-gray-600">Absolutely! You can cancel your Pro subscription at any time. You'll continue to have Pro access until the end of your billing period.</p>
                    </div>
                    
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-2">What payment methods do you accept?</h3>
                        <p class="text-sm text-gray-600">We accept all major credit cards, PayPal, and SEPA direct debit for European customers.</p>
                    </div>
                    
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-2">Do you offer refunds?</h3>
                        <p class="text-sm text-gray-600">Yes! We offer a 30-day money-back guarantee. If you're not satisfied, we'll refund your payment in full.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function startUpgrade() {
            // Redirect to Stripe checkout
            window.location.href = 'payment/checkout.php';
        }
    </script>

</body>
</html>
