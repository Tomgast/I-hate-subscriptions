<?php
session_start();
require_once 'config/db_config.php';
require_once 'includes/plan_manager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/signin.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';

// Check if this is a welcome flow for new users
$isWelcome = isset($_GET['welcome']) && $_GET['welcome'] == '1';

// Get user's current plan
$planManager = getPlanManager();
$userPlan = $planManager->getUserPlan($userId);

// If user already has an active plan, show upgrade options or redirect to dashboard
if ($userPlan && $userPlan['is_active']) {
    // Allow viewing upgrade options for plan changes
    $currentPlan = $userPlan['plan_type'];
} else {
    $currentPlan = null;
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
        .gradient-text {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Hero Section -->
        <div class="text-center mb-12">
            <?php if ($isWelcome): ?>
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                Welcome to <span class="gradient-text">CashControl</span>, <?php echo htmlspecialchars($userName); ?>!
            </h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto mb-4">Your account has been created successfully. Choose a plan to start tracking your subscriptions professionally.</p>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 max-w-2xl mx-auto">
                <p class="text-blue-800 text-sm">💡 <strong>No free tier:</strong> All plans include full access to professional features. Choose the option that works best for you.</p>
            </div>
            <?php elseif ($currentPlan): ?>
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                Upgrade Your <span class="gradient-text">CashControl Plan</span>
            </h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">You&rsquo;re currently on the <strong><?php echo ucfirst($currentPlan); ?></strong> plan. Upgrade for more features!</p>
            <?php else: ?>
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                Choose Your <span class="gradient-text">CashControl Plan</span>
            </h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">Professional subscription management with bank integration and advanced analytics</p>
            <?php endif; ?>
        </div>

        <!-- Pricing Cards -->
        <div class="max-w-6xl mx-auto mb-16">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                
                <!-- Monthly Plan -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden relative">
                    <div class="px-6 py-8 text-center">
                        <div class="mb-6">
                            <div class="text-4xl mb-4">📅</div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">Monthly</h3>
                            <p class="text-gray-600">Perfect for trying out Pro features</p>
                        </div>
                        
                        <div class="mb-6">
                            <div class="text-4xl font-bold text-gray-900 mb-2">
                                <span class="gradient-text">€3</span>
                            </div>
                            <div class="text-gray-600">per month</div>
                            <div class="text-sm text-gray-500 mt-1">Cancel anytime</div>
                        </div>
                        
                        <button onclick="startUpgrade('monthly')" class="w-full <?php echo $currentPlan === 'monthly' ? 'bg-green-100 text-green-800 cursor-not-allowed' : 'bg-gray-100 hover:bg-gray-200 text-gray-800'; ?> px-6 py-3 rounded-lg font-semibold transition-all duration-200 mb-4" <?php echo $currentPlan === 'monthly' ? 'disabled' : ''; ?>>
                            <?php echo $currentPlan === 'monthly' ? 'Current Plan' : 'Choose Monthly'; ?>
                        </button>
                        
                        <div class="text-xs text-gray-500">
                            Full access to all features
                        </div>
                    </div>
                </div>
                
                <!-- Yearly Plan -->
                <div class="bg-white rounded-2xl shadow-xl border-2 border-green-200 overflow-hidden relative">
                    <!-- Popular Badge -->
                    <div class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                        <div class="gradient-bg text-white px-4 py-1 rounded-full text-xs font-semibold shadow-lg">
                            🚀 Best Value
                        </div>
                    </div>
                    
                    <div class="px-6 py-8 text-center">
                        <div class="mb-6">
                            <div class="text-4xl mb-4">💎</div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">Yearly</h3>
                            <p class="text-gray-600">Save 31% with annual billing</p>
                        </div>
                        
                        <div class="mb-6">
                            <div class="text-4xl font-bold text-gray-900 mb-2">
                                <span class="gradient-text">€25</span>
                            </div>
                            <div class="text-gray-600">per year</div>
                            <div class="text-sm text-green-600 font-semibold mt-1">Save €11 vs monthly</div>
                        </div>
                        
                        <button onclick="startUpgrade('yearly')" class="w-full <?php echo $currentPlan === 'yearly' ? 'bg-green-100 text-green-800 cursor-not-allowed' : 'gradient-bg text-white shadow-lg hover:shadow-xl transform hover:-translate-y-1'; ?> px-6 py-3 rounded-lg font-semibold transition-all duration-200 mb-4" <?php echo $currentPlan === 'yearly' ? 'disabled' : ''; ?>>
                            <?php echo $currentPlan === 'yearly' ? 'Current Plan' : 'Choose Yearly - Save 31%'; ?>
                        </button>
                        
                        <div class="text-xs text-gray-500">
                            Full access to all features
                        </div>
                    </div>
                </div>
                
                <!-- One-Time Scan -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden relative">
                    <div class="px-6 py-8 text-center">
                        <div class="mb-6">
                            <div class="text-4xl mb-4">🔍</div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">One-Time Scan</h3>
                            <p class="text-gray-600">Perfect for subscription audit</p>
                        </div>
                        
                        <div class="mb-6">
                            <div class="text-4xl font-bold text-gray-900 mb-2">
                                <span class="gradient-text">€25</span>
                            </div>
                            <div class="text-gray-600">one-time payment</div>
                            <div class="text-sm text-gray-500 mt-1">Single scan + export + guides</div>
                        </div>
                        
                        <button onclick="startUpgrade('one_time')" class="w-full <?php echo $currentPlan === 'one_time' ? 'bg-green-100 text-green-800 cursor-not-allowed' : 'bg-gray-100 hover:bg-gray-200 text-gray-800'; ?> px-6 py-3 rounded-lg font-semibold transition-all duration-200 mb-4" <?php echo $currentPlan === 'one_time' ? 'disabled' : ''; ?>>
                            <?php echo $currentPlan === 'one_time' ? 'Current Plan' : 'Choose One-Time Scan'; ?>
                        </button>
                        
                        <div class="text-xs text-gray-500">
                            Single bank scan + PDF/CSV export + unsubscribe guides
                        </div>
                    </div>
                </div>
                
            </div>
        </div>

        <!-- Features Comparison -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-16">
            <!-- Monthly vs Yearly Comparison -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
                <div class="bg-green-50 px-6 py-4 border-b border-green-200">
                    <h3 class="text-xl font-bold text-green-900">Subscription Plans</h3>
                    <p class="text-green-700 text-sm mt-1">Full access with ongoing features</p>
                </div>
                <div class="p-6">
                    <div class="text-2xl font-bold text-green-900 mb-4">
                        Monthly: €3/mo • Yearly: €25/yr
                    </div>
                    <ul class="space-y-4">
                        <li class="flex items-center text-green-700">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Unlimited bank scans
                        </li>
                        <li class="flex items-center text-green-700">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Advanced analytics dashboard
                        </li>
                        <li class="flex items-center text-green-700">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            PDF/CSV export
                        </li>
                        <li class="flex items-center text-green-700">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Email notifications
                        </li>
                        <li class="flex items-center text-green-700">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Unsubscribe guides
                        </li>
                    </ul>
                    <div class="mt-6">
                        <div class="text-center py-3 bg-gray-100 rounded-lg text-gray-600 font-medium">
                            Current Plan
                        </div>
                    </div>
                </div>
            </div>

            <!-- One-Time Plan -->
            <div class="bg-white rounded-2xl shadow-xl border-2 border-blue-200 overflow-hidden">
                <div class="bg-blue-50 px-6 py-4 border-b border-blue-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-blue-900">One-Time Plan</h3>
                            <p class="text-blue-700 text-sm mt-1">Perfect for a quick analysis</p>
                        </div>
                        <div class="bg-blue-600 text-white px-3 py-1 rounded-full text-xs font-semibold">
                            Single Use
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="text-3xl font-bold text-blue-900 mb-4">
                        €25 <span class="text-lg font-normal text-blue-700">one-time</span>
                    </div>
                    <div class="text-sm text-green-600 font-medium mb-6">🎯 Lifetime access</div>
                    <ul class="space-y-4">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-gray-700 font-medium">All core features included</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <div>
                                <span class="text-gray-700 font-medium">Bank account integration</span>
                                <div class="text-sm text-gray-500">One-time scan to discover subscriptions</div>
                            </div>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <div>
                                <span class="text-gray-700 font-medium">Smart email reminders</span>
                                <div class="text-sm text-gray-500">Never miss a renewal again</div>
                            </div>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <div>
                                <span class="text-gray-700 font-medium">Export data (CSV, PDF)</span>
                                <div class="text-sm text-gray-500">Download your subscription data</div>
                            </div>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <div>
                                <span class="text-gray-700 font-medium">Priority support</span>
                                <div class="text-sm text-gray-500">Get help when you need it</div>
                            </div>
                        </li>
                    </ul>
                    <div class="mt-6">
                        <button onclick="startUpgrade()" class="w-full gradient-bg text-white px-6 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                            🚀 Upgrade Now
                        </button>
                    </div>
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
        <div class="mb-16">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Frequently Asked Questions</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Everything you need to know about upgrading to CashControl Pro</p>
            </div>
            <div class="max-w-3xl mx-auto space-y-6">
                <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
                    <button class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors" onclick="toggleFAQ(1)">
                        <h3 class="font-semibold text-gray-900">💳 How does the bank integration work?</h3>
                        <svg id="faq-icon-1" class="w-5 h-5 text-gray-500 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div id="faq-content-1" class="hidden px-6 pb-4">
                        <p class="text-gray-600">We use secure, read-only access to perform a one-time scan of your bank transactions to identify recurring payments and subscriptions. Your banking credentials are never stored on our servers - we use bank-grade security through our trusted partner TrueLayer.</p>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
                    <button class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors" onclick="toggleFAQ(2)">
                        <h3 class="font-semibold text-gray-900">🔒 Is my financial data safe?</h3>
                        <svg id="faq-icon-2" class="w-5 h-5 text-gray-500 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div id="faq-content-2" class="hidden px-6 pb-4">
                        <p class="text-gray-600">Absolutely. We use bank-level encryption and comply with PSD2 regulations. Your banking credentials are never stored - we only access transaction data through secure, regulated APIs. All data is encrypted and stored securely in our database.</p>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
                    <button class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors" onclick="toggleFAQ(3)">
                        <h3 class="font-semibold text-gray-900">💰 Why one-time payment instead of subscription?</h3>
                        <svg id="faq-icon-3" class="w-5 h-5 text-gray-500 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div id="faq-content-3" class="hidden px-6 pb-4">
                        <p class="text-gray-600">We believe in honest pricing. Pay once, own it forever. No recurring charges, no surprise bills, no subscription fatigue. It&rsquo;s ironic to charge a subscription for a tool that helps you manage subscriptions!</p>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
                    <button class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors" onclick="toggleFAQ(4)">
                        <h3 class="font-semibold text-gray-900">📱 Can I access it on multiple devices?</h3>
                        <svg id="faq-icon-4" class="w-5 h-5 text-gray-500 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div id="faq-content-4" class="hidden px-6 pb-4">
                        <p class="text-gray-600">Yes! Your account works on all devices - desktop, tablet, and mobile. Your subscription data is securely synced across all your devices so you can manage your subscriptions anywhere.</p>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
                    <button class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors" onclick="toggleFAQ(5)">
                        <h3 class="font-semibold text-gray-900">🎯 What if I need help?</h3>
                        <svg id="faq-icon-5" class="w-5 h-5 text-gray-500 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div id="faq-content-5" class="hidden px-6 pb-4">
                        <p class="text-gray-600">Pro users get priority email support. We typically respond within 24 hours and are here to help you get the most out of CashControl. You can reach us at support@123cashcontrol.com.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Final CTA Section -->
        <div class="text-center py-16 gradient-bg rounded-2xl">
            <div class="max-w-2xl mx-auto px-6">
                <h2 class="text-3xl font-bold text-white mb-4">
                    Ready to take control of your subscriptions?
                </h2>
                <p class="text-green-100 text-lg mb-8">
                    Join thousands of users who have saved money and gained peace of mind with CashControl Pro.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <button onclick="startUpgrade('yearly')" class="bg-white text-green-600 px-8 py-4 rounded-lg font-bold text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-200">
                        🚀 Get Started - €25/year
                    </button>
                    <div class="text-green-100 text-sm">
                        ✓ Best value • ✓ Full features • ✓ Cancel anytime
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function startUpgrade(planType = 'yearly') {
            window.location.href = 'payment/checkout.php?plan=' + planType;
        }

        function toggleFAQ(id) {
            const content = document.getElementById(`faq-content-${id}`);
            const icon = document.getElementById(`faq-icon-${id}`);
            
            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                icon.style.transform = 'rotate(180deg)';
            } else {
                content.classList.add('hidden');
                icon.style.transform = 'rotate(0deg)';
            }
        }

        // Add smooth scrolling for any anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add entrance animations
        document.addEventListener('DOMContentLoaded', function() {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Observe elements for animation
            document.querySelectorAll('.card, .bg-white').forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(el);
            });
        });
    </script>
</body>
</html>
