<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo - CashControl</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .hero-gradient {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 50%, #bbf7d0 100%);
        }
        .wave-animation {
            background: linear-gradient(-45deg, #10b981, #059669, #047857, #065f46);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .demo-card {
            transition: all 0.3s ease;
        }
        .demo-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body class="bg-white">
    <?php include 'includes/header.php'; ?>

    <!-- Demo Header -->
    <div class="hero-gradient py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl md:text-6xl font-bold text-gray-900 mb-6">CashControl Demo</h1>
                <p class="text-xl md:text-2xl text-gray-600 mb-8 max-w-3xl mx-auto">
                    See how CashControl helps you track and manage all your subscriptions in one beautiful dashboard
                </p>
                <div class="inline-flex items-center px-4 py-2 bg-white rounded-full shadow-lg">
                    <span class="w-3 h-3 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                    <span class="text-sm font-medium text-gray-700">Live Demo - Try it now!</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Demo Dashboard -->
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                <div class="demo-card bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Active Subscriptions</p>
                            <p class="text-3xl font-bold text-gray-900">12</p>
                            <p class="text-xs text-green-600 mt-1">+2 this month</p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="demo-card bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Monthly Spending</p>
                            <p class="text-3xl font-bold text-gray-900">€127.50</p>
                            <p class="text-xs text-blue-600 mt-1">€15 less than last month</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="demo-card bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Yearly Total</p>
                            <p class="text-3xl font-bold text-gray-900">€1,530</p>
                            <p class="text-xs text-purple-600 mt-1">Projected savings: €240</p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="demo-card bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Expiring Soon</p>
                            <p class="text-3xl font-bold text-orange-600">3</p>
                            <p class="text-xs text-orange-600 mt-1">Next 7 days</p>
                        </div>
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Demo Subscriptions List -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 mb-12">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Your Subscriptions</h2>
                        <p class="text-gray-600">Manage all your recurring payments in one place</p>
                    </div>
                    <div class="flex space-x-3">
                        <button class="gradient-bg text-white px-4 py-2 rounded-lg font-medium shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-200">
                            Add Subscription
                        </button>
                        <button class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium shadow-lg hover:shadow-xl hover:bg-blue-700 transform hover:-translate-y-1 transition-all duration-200">
                            Bank Scan (Pro)
                        </button>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Netflix -->
                    <div class="demo-card bg-gray-50 rounded-xl p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-red-500 rounded-lg flex items-center justify-center text-white font-bold text-lg mr-3">N</div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 text-lg">Netflix</h4>
                                    <p class="text-sm text-gray-500">Entertainment</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Active
                            </span>
                        </div>
                        <div class="mb-4">
                            <div class="text-2xl font-bold text-gray-900 mb-1">€12.99</div>
                            <div class="text-sm text-gray-500">per month</div>
                        </div>
                        <div class="text-sm text-gray-600">
                            <span class="font-medium">Next payment:</span> Dec 15, 2024
                        </div>
                    </div>

                    <!-- Spotify -->
                    <div class="demo-card bg-gray-50 rounded-xl p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center text-white font-bold text-lg mr-3">S</div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 text-lg">Spotify</h4>
                                    <p class="text-sm text-gray-500">Music</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Active
                            </span>
                        </div>
                        <div class="mb-4">
                            <div class="text-2xl font-bold text-gray-900 mb-1">€9.99</div>
                            <div class="text-sm text-gray-500">per month</div>
                        </div>
                        <div class="text-sm text-gray-600">
                            <span class="font-medium">Next payment:</span> Dec 22, 2024
                        </div>
                    </div>

                    <!-- Adobe -->
                    <div class="demo-card bg-gray-50 rounded-xl p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-red-600 rounded-lg flex items-center justify-center text-white font-bold text-lg mr-3">A</div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 text-lg">Adobe Creative</h4>
                                    <p class="text-sm text-gray-500">Design</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                Expiring Soon
                            </span>
                        </div>
                        <div class="mb-4">
                            <div class="text-2xl font-bold text-gray-900 mb-1">€59.99</div>
                            <div class="text-sm text-gray-500">per month</div>
                        </div>
                        <div class="text-sm text-gray-600">
                            <span class="font-medium">Next payment:</span> Jan 5, 2025
                        </div>
                    </div>

                    <!-- Microsoft 365 -->
                    <div class="demo-card bg-gray-50 rounded-xl p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-lg mr-3">M</div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 text-lg">Microsoft 365</h4>
                                    <p class="text-sm text-gray-500">Productivity</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Active
                            </span>
                        </div>
                        <div class="mb-4">
                            <div class="text-2xl font-bold text-gray-900 mb-1">€69.00</div>
                            <div class="text-sm text-gray-500">per year</div>
                        </div>
                        <div class="text-sm text-gray-600">
                            <span class="font-medium">Next payment:</span> Mar 15, 2025
                        </div>
                    </div>

                    <!-- GitHub -->
                    <div class="demo-card bg-gray-50 rounded-xl p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-gray-900 rounded-lg flex items-center justify-center text-white font-bold text-lg mr-3">G</div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 text-lg">GitHub Pro</h4>
                                    <p class="text-sm text-gray-500">Development</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Active
                            </span>
                        </div>
                        <div class="mb-4">
                            <div class="text-2xl font-bold text-gray-900 mb-1">€4.00</div>
                            <div class="text-sm text-gray-500">per month</div>
                        </div>
                        <div class="text-sm text-gray-600">
                            <span class="font-medium">Next payment:</span> Dec 28, 2024
                        </div>
                    </div>

                    <!-- Dropbox -->
                    <div class="demo-card bg-gray-50 rounded-xl p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center text-white font-bold text-lg mr-3">D</div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 text-lg">Dropbox Plus</h4>
                                    <p class="text-sm text-gray-500">Storage</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Active
                            </span>
                        </div>
                        <div class="mb-4">
                            <div class="text-2xl font-bold text-gray-900 mb-1">€9.99</div>
                            <div class="text-sm text-gray-500">per month</div>
                        </div>
                        <div class="text-sm text-gray-600">
                            <span class="font-medium">Next payment:</span> Jan 12, 2025
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pro Features Demo -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
                <div class="demo-card bg-white rounded-xl shadow-sm border border-gray-100 p-8">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Bank Integration</h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 ml-2">
                                Pro Feature
                            </span>
                        </div>
                    </div>
                    <p class="text-gray-600 mb-6">Automatically detect subscription payments from your bank transactions with our secure integration.</p>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-sm font-medium text-blue-800">Found 3 new subscriptions in your transaction history!</span>
                        </div>
                    </div>
                </div>

                <div class="demo-card bg-white rounded-xl shadow-sm border border-gray-100 p-8">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Smart Reminders</h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 ml-2">
                                Pro Feature
                            </span>
                        </div>
                    </div>
                    <p class="text-gray-600 mb-6">Get email notifications before renewals to avoid surprises and manage your budget effectively.</p>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a4 4 0 118 0v4m-4 8a2 2 0 100-4 2 2 0 000 4zm0 0v4a2 2 0 002 2h6a2 2 0 002-2v-4"></path>
                            </svg>
                            <span class="text-sm font-medium text-green-800">📅 Netflix renews in 3 days - €12.99</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Call to Action -->
            <div class="text-center">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6">Ready to Take Control?</h2>
                    <p class="text-xl text-gray-600 mb-8 max-w-2xl mx-auto">
                        Start tracking your subscriptions today and never miss a payment or waste money on unused services again!
                    </p>
                    
                    <div class="flex flex-col sm:flex-row gap-4 justify-center mb-8">
                        <a href="auth/signup.php" class="gradient-bg text-white px-8 py-4 rounded-lg font-semibold text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-200">
                            🚀 Get Started Free
                        </a>
                        <a href="upgrade.php" class="bg-blue-600 text-white px-8 py-4 rounded-lg font-semibold text-lg shadow-lg hover:shadow-xl hover:bg-blue-700 transform hover:-translate-y-1 transition-all duration-200">
                            💎 Upgrade to Pro - €29
                        </a>
                    </div>
                    
                    <div class="flex items-center justify-center space-x-8 text-sm text-gray-500">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Free plan includes basic tracking
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Pro adds bank integration & smart reminders
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

</body>
</html>
