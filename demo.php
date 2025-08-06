<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo - CashControl</title>
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
            
            <div class="flex items-center space-x-4">
                <a href="index.html" class="text-gray-600 hover:text-gray-900" style="text-decoration: none; padding: 0.5rem 1rem;">
                    ← Back to Home
                </a>
                <a href="auth/signup.php" class="btn btn-primary">
                    Sign Up
                </a>
            </div>
        </div>
    </nav>

    <!-- Demo Content -->
    <div class="container py-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-4">CashControl Demo</h1>
            <p class="text-lg text-gray-600">See how CashControl helps you track and manage subscriptions</p>
        </div>

        <!-- Demo Dashboard -->
        <div class="max-w-6xl mx-auto">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Monthly Spending</p>
                                <p class="text-2xl font-bold text-gray-900">€127.50</p>
                            </div>
                            <span class="text-3xl">💰</span>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Active Subscriptions</p>
                                <p class="text-2xl font-bold text-gray-900">12</p>
                            </div>
                            <span class="text-3xl">📱</span>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Yearly Total</p>
                                <p class="text-2xl font-bold text-gray-900">€1,530</p>
                            </div>
                            <span class="text-3xl">📊</span>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Potential Savings</p>
                                <p class="text-2xl font-bold text-green-600">€240</p>
                            </div>
                            <span class="text-3xl">💡</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Demo Subscriptions -->
            <div class="card mb-8">
                <div class="p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Your Subscriptions</h2>
                    
                    <div class="space-y-4">
                        <!-- Netflix -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-red-500 rounded-lg flex items-center justify-center text-white font-bold">N</div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">Netflix</h3>
                                    <p class="text-sm text-gray-600">Entertainment • Monthly</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-900">€12.99</p>
                                <p class="text-sm text-gray-600">Next: Dec 15</p>
                            </div>
                        </div>

                        <!-- Spotify -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center text-white font-bold">S</div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">Spotify Premium</h3>
                                    <p class="text-sm text-gray-600">Music • Monthly</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-900">€9.99</p>
                                <p class="text-sm text-gray-600">Next: Dec 22</p>
                            </div>
                        </div>

                        <!-- Adobe -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-red-600 rounded-lg flex items-center justify-center text-white font-bold">A</div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">Adobe Creative Cloud</h3>
                                    <p class="text-sm text-gray-600">Design • Monthly</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-900">€59.99</p>
                                <p class="text-sm text-gray-600">Next: Jan 5</p>
                            </div>
                        </div>

                        <!-- Office 365 -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold">O</div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">Microsoft 365</h3>
                                    <p class="text-sm text-gray-600">Productivity • Yearly</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-900">€69.00</p>
                                <p class="text-sm text-gray-600">Next: Mar 15</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Demo Features -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                <div class="card">
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">🏦 Bank Integration (Pro)</h3>
                        <p class="text-gray-600 mb-4">Automatically detect subscription payments from your bank transactions.</p>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                            <p class="text-sm text-blue-800">✅ Found 3 new subscriptions in your transaction history!</p>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">📧 Smart Reminders (Pro)</h3>
                        <p class="text-gray-600 mb-4">Get email notifications before renewals to avoid surprises.</p>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                            <p class="text-sm text-green-800">📅 Netflix renews in 3 days - €12.99</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Call to Action -->
            <div class="text-center">
                <div class="card">
                    <div class="p-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Ready to Take Control?</h2>
                        <p class="text-lg text-gray-600 mb-6">Start tracking your subscriptions today!</p>
                        
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <a href="auth/signup.php" class="btn btn-primary btn-lg">
                                🚀 Start Free Trial
                            </a>
                            <a href="upgrade.php" class="btn btn-secondary btn-lg">
                                💎 Upgrade to Pro - €29/year
                            </a>
                        </div>
                        
                        <div class="mt-4 text-sm text-gray-500">
                            ✨ Free plan includes basic tracking • Pro adds bank integration & smart reminders
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
