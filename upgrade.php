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
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                Upgrade to <span class="gradient-text">CashControl Pro</span>
            </h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">Unlock powerful features and take complete control of your subscription spending</p>
        </div>

        <!-- Pricing Card -->
        <div class="max-w-lg mx-auto mb-16">
            <div class="bg-white rounded-2xl shadow-xl border-2 border-green-200 overflow-hidden relative">
                <!-- Popular Badge -->
                <div class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                    <div class="gradient-bg text-white px-6 py-2 rounded-full text-sm font-semibold shadow-lg">
                        🚀 Most Popular
                    </div>
                </div>
                
                <div class="px-8 py-10 text-center">
                    <div class="mb-6">
                        <div class="text-6xl mb-4">💎</div>
                        <h2 class="text-3xl font-bold text-gray-900 mb-2">CashControl Pro</h2>
                        <p class="text-gray-600">Lifetime access to all premium features</p>
                    </div>
                    
                    <div class="mb-8">
                        <div class="text-6xl font-bold text-gray-900 mb-2">
                            <span class="gradient-text">€29</span>
                        </div>
                        <div class="text-lg text-gray-600">One-time payment • No recurring fees</div>
                        <div class="text-sm text-green-600 font-medium mt-2">✨ Lifetime access guaranteed</div>
                    </div>
                    
                    <button onclick="startUpgrade()" class="w-full gradient-bg text-white px-8 py-4 rounded-xl font-bold text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-200 mb-4">
                        🎯 Upgrade to Pro Now
                    </button>
                    
                    <div class="flex items-center justify-center text-sm text-gray-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        Secure payment with Stripe
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Comparison -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-16">
            <!-- Free Plan -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <h3 class="text-xl font-bold text-gray-900">Free Plan</h3>
                    <p class="text-gray-600 text-sm mt-1">Perfect for getting started</p>
                </div>
                <div class="p-6">
                    <div class="text-3xl font-bold text-gray-900 mb-4">
                        €0 <span class="text-lg font-normal text-gray-500">forever</span>
                    </div>
                    <ul class="space-y-4">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-gray-700">Manual subscription tracking</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-gray-700">Basic dashboard with monthly/yearly totals</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-gray-700">Unlimited subscriptions</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-gray-300 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span class="text-gray-400">Bank account integration</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-gray-300 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span class="text-gray-400">Email reminders</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-gray-300 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span class="text-gray-400">Data export (CSV, PDF)</span>
                        </li>
                    </ul>
                    <div class="mt-6">
                        <div class="text-center py-3 bg-gray-100 rounded-lg text-gray-600 font-medium">
                            Current Plan
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pro Plan -->
            <div class="bg-white rounded-2xl shadow-xl border-2 border-green-300 overflow-hidden relative">
                <div class="absolute top-0 right-0 bg-green-500 text-white px-3 py-1 text-xs font-semibold rounded-bl-lg">
                    RECOMMENDED
                </div>
                <div class="gradient-bg px-6 py-4 border-b border-green-200">
                    <h3 class="text-xl font-bold text-white">Pro Plan</h3>
                    <p class="text-green-100 text-sm mt-1">Everything you need to master subscriptions</p>
                </div>
                <div class="p-6">
                    <div class="text-3xl font-bold text-gray-900 mb-1">
                        €29 <span class="text-lg font-normal text-gray-500">one-time</span>
                    </div>
                    <div class="text-sm text-green-600 font-medium mb-6">🎯 Lifetime access</div>
                    <ul class="space-y-4">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-gray-700 font-medium">Everything in Free Plan</span>
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
                        <p class="text-gray-600">We believe in honest pricing. Pay once, own it forever. No recurring charges, no surprise bills, no subscription fatigue. It's ironic to charge a subscription for a tool that helps you manage subscriptions!</p>
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
                    <button onclick="startUpgrade()" class="bg-white text-green-600 px-8 py-4 rounded-lg font-bold text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-200">
                        🚀 Upgrade to Pro - €29
                    </button>
                    <div class="text-green-100 text-sm">
                        ✓ One-time payment • ✓ Lifetime access • ✓ 30-second setup
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function startUpgrade() {
            window.location.href = '/payment/checkout.php';
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
