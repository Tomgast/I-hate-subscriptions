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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .hero-gradient {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 50%, #bbf7d0 100%);
            position: relative;
            overflow: hidden;
        }
        .hero-gradient::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="%2310b981" opacity="0.1"><path d="M0,20 Q250,0 500,20 T1000,20 L1000,100 L0,100 Z"/></svg>') repeat-x;
            background-size: 1000px 100px;
            animation: wave 20s linear infinite;
        }
        @keyframes wave {
            0% { transform: translateX(0); }
            100% { transform: translateX(-1000px); }
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
            backdrop-filter: blur(10px);
        }
        .demo-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .floating-animation {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .pulse-glow {
            animation: pulse-glow 2s ease-in-out infinite;
        }
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(16, 185, 129, 0.3); }
            50% { box-shadow: 0 0 30px rgba(16, 185, 129, 0.6); }
        }
    </style>
</head>
<body class="bg-white">
    <?php include 'includes/header.php'; ?>

    <!-- Demo Header -->
    <div class="hero-gradient py-20 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center">
                <div class="floating-animation inline-block mb-8">
                    <div class="w-20 h-20 gradient-bg rounded-3xl flex items-center justify-center shadow-xl pulse-glow">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </div>
                </div>
                <h1 class="text-5xl md:text-7xl font-bold text-gray-900 mb-6 bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent">
                    CashControl Demo
                </h1>
                <p class="text-xl md:text-2xl text-gray-600 mb-8 max-w-4xl mx-auto leading-relaxed">
                    Experience how CashControl transforms subscription chaos into organized financial clarity. 
                    <span class="text-green-600 font-semibold">See your money, save your money.</span>
                </p>
                
                <!-- Interactive Demo Badge -->
                <div class="inline-flex items-center px-6 py-3 bg-white/90 backdrop-blur-sm rounded-full shadow-xl border border-green-100 mb-8">
                    <div class="flex items-center mr-4">
                        <span class="w-3 h-3 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                        <span class="text-sm font-semibold text-gray-700">Interactive Demo</span>
                    </div>
                    <div class="h-4 w-px bg-gray-300 mr-4"></div>
                    <div class="flex items-center space-x-4 text-xs text-gray-600">
                        <span class="flex items-center">
                            <svg class="w-3 h-3 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            No signup required
                        </span>
                        <span class="flex items-center">
                            <svg class="w-3 h-3 mr-1 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            Real data simulation
                        </span>
                    </div>
                </div>
                
                <!-- Quick Navigation -->
                <div class="flex flex-wrap justify-center gap-4 text-sm">
                    <a href="#dashboard" class="inline-flex items-center px-4 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Dashboard Overview
                    </a>
                    <a href="#subscriptions" class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        Subscription List
                    </a>
                    <a href="#features" class="inline-flex items-center px-4 py-2 bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Pro Features
                    </a>
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
                            🚀 Get Started Today
                        </a>
                        <a href="upgrade.php" class="bg-blue-600 text-white px-8 py-4 rounded-lg font-semibold text-lg shadow-lg hover:shadow-xl hover:bg-blue-700 transform hover:-translate-y-1 transition-all duration-200">
                            💎 View Plans & Pricing
                        </a>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600 max-w-4xl mx-auto">
                        <div class="flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Monthly: €3/month - Full access
                        </div>
                        <div class="flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Yearly: €25/year - Save 31%
                        </div>
                        <div class="flex items-center justify-center">
                            <svg class="w-4 h-4 text-purple-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            One-time: €25 - Single scan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Upgrade Call-to-Action Section -->
    <section class="py-20 bg-gradient-to-br from-green-50 to-blue-50 relative overflow-hidden">
        <div class="absolute inset-0 bg-white/50"></div>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            <div class="mb-8">
                <div class="inline-flex items-center px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-semibold mb-6">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    Ready to take control?
                </div>
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
                    Start Managing Your Subscriptions 
                    <span class="text-green-600">Professionally</span>
                </h2>
                <p class="text-xl text-gray-600 mb-8 max-w-3xl mx-auto">
                    This demo shows just a glimpse of CashControl's power. Get full access to bank integration, 
                    smart analytics, automated reminders, and professional reporting tools.
                </p>
            </div>

            <!-- Pricing Cards Mini -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-6 hover:shadow-xl transition-shadow duration-300">
                    <div class="text-3xl mb-3">📅</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Monthly</h3>
                    <div class="text-3xl font-bold text-green-600 mb-3">€3<span class="text-lg text-gray-500">/month</span></div>
                    <p class="text-gray-600 text-sm mb-4">Perfect for trying Pro features</p>
                    <a href="auth/signin.php" class="block w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors duration-200 font-medium">
                        Get Started
                    </a>
                </div>
                
                <div class="bg-white rounded-2xl shadow-lg border-2 border-green-500 p-6 hover:shadow-xl transition-shadow duration-300 relative">
                    <div class="absolute -top-3 left-1/2 transform -translate-x-1/2">
                        <span class="bg-green-500 text-white px-4 py-1 rounded-full text-sm font-semibold">Most Popular</span>
                    </div>
                    <div class="text-3xl mb-3">🎯</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Yearly</h3>
                    <div class="text-3xl font-bold text-green-600 mb-3">€25<span class="text-lg text-gray-500">/year</span></div>
                    <p class="text-gray-600 text-sm mb-4">Best value - 2 months free!</p>
                    <a href="auth/signin.php" class="block w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors duration-200 font-medium">
                        Get Started
                    </a>
                </div>
                
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-6 hover:shadow-xl transition-shadow duration-300">
                    <div class="text-3xl mb-3">⚡</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">One-Time</h3>
                    <div class="text-3xl font-bold text-purple-600 mb-3">€25<span class="text-lg text-gray-500">/scan</span></div>
                    <p class="text-gray-600 text-sm mb-4">Single bank scan + dashboard</p>
                    <a href="auth/signin.php" class="block w-full bg-purple-600 text-white py-2 px-4 rounded-lg hover:bg-purple-700 transition-colors duration-200 font-medium">
                        Get Started
                    </a>
                </div>
            </div>

            <!-- Key Benefits -->
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-8 mb-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-6">Why Choose CashControl?</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-left">
                    <div class="flex items-start">
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mr-4 mt-1">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-1">No Free Tier Limitations</h4>
                            <p class="text-gray-600 text-sm">All plans include full professional features - no artificial restrictions</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-4 mt-1">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-1">Bank-Grade Security</h4>
                            <p class="text-gray-600 text-sm">Your financial data is protected with enterprise-level encryption</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center mr-4 mt-1">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-1">Smart Analytics</h4>
                            <p class="text-gray-600 text-sm">AI-powered insights to optimize your subscription spending</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center mr-4 mt-1">
                            <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-1">Automated Reminders</h4>
                            <p class="text-gray-600 text-sm">Never miss a renewal or cancellation deadline again</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Final CTA -->
            <div class="text-center">
                <p class="text-gray-600 mb-6">Join professionals who've taken control of their subscriptions</p>
                <a href="auth/signin.php" class="inline-flex items-center px-8 py-4 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors duration-200 font-semibold text-lg shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                    Start Your Professional Journey
                </a>
                <p class="text-sm text-gray-500 mt-4">✨ Setup takes less than 2 minutes</p>
            </div>
        </div>
    </section>

</body>
</html>
