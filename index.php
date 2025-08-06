<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CashControl - Subscription Management Made Simple</title>
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
        .wave-1 {
            animation: wave-move-1 20s ease-in-out infinite;
        }
        .wave-2 {
            animation: wave-move-2 25s ease-in-out infinite;
        }
        .wave-3 {
            animation: wave-move-3 15s ease-in-out infinite;
        }
        @keyframes wave-move-1 {
            0%, 100% { transform: translateX(-1200px); }
            50% { transform: translateX(1200px); }
        }
        @keyframes wave-move-2 {
            0%, 100% { transform: translateX(-1600px); }
            50% { transform: translateX(1600px); }
        }
        @keyframes wave-move-3 {
            0%, 100% { transform: translateX(-800px); }
            50% { transform: translateX(800px); }
        }
        .hero-gradient {
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #d1fae5 100%);
        }
        .feature-card {
            transition: all 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .plan-card {
            transition: all 0.3s ease;
        }
        .plan-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .plan-card.featured {
            border: 2px solid #10b981;
            position: relative;
        }
        .plan-card.featured::before {
            content: 'Most Popular';
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 4px 16px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-white">
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero-gradient relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="text-center">
                <h1 class="text-5xl md:text-6xl font-bold text-gray-900 mb-6">
                    Subscription Management
                    <span class="gradient-text block">Made Simple</span>
                </h1>
                <p class="text-xl text-gray-600 mb-8 max-w-3xl mx-auto">
                    Take control of your subscriptions with our intelligent tracking system. 
                    Connect your bank account once to discover all your subscriptions and manage them effortlessly.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="auth/signup.php" class="gradient-bg text-white px-8 py-4 rounded-lg font-semibold text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-200">
                        Start Free Today
                    </a>
                    <a href="demo.php" class="bg-white text-gray-700 px-8 py-4 rounded-lg font-semibold text-lg border-2 border-gray-200 hover:border-green-500 transition-all duration-200">
                        View Demo
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Everything You Need</h2>
                <p class="text-xl text-gray-600">Powerful features to help you manage your subscriptions</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="feature-card bg-white p-8 rounded-xl shadow-sm">
                    <div class="w-12 h-12 gradient-bg rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Bank Integration</h3>
                    <p class="text-gray-600">Securely connect your bank account once to automatically discover all your subscriptions and recurring payments.</p>
                </div>
                
                <div class="feature-card bg-white p-8 rounded-xl shadow-sm">
                    <div class="w-12 h-12 gradient-bg rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Subscription Dashboard</h3>
                    <p class="text-gray-600">View all your subscriptions in one place with clear insights into your monthly and yearly spending.</p>
                </div>
                
                <div class="feature-card bg-white p-8 rounded-xl shadow-sm">
                    <div class="w-12 h-12 gradient-bg rounded-lg flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Email Notifications</h3>
                    <p class="text-gray-600">Get timely email reminders before your subscriptions renew so you never get surprised by unexpected charges.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Simple, Transparent Pricing</h2>
                <p class="text-xl text-gray-600">Choose the plan that works best for you</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <!-- Free Plan -->
                <div class="plan-card bg-white p-8 rounded-xl shadow-sm border-2 border-gray-200">
                    <div class="text-center mb-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Free</h3>
                        <div class="text-4xl font-bold text-gray-900 mb-2">€0</div>
                        <p class="text-gray-600">Forever free</p>
                    </div>
                    
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Manual subscription tracking
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Basic dashboard
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Up to 10 subscriptions
                        </li>
                    </ul>
                    
                    <a href="auth/signup.php" class="w-full bg-gray-100 text-gray-700 px-6 py-3 rounded-lg font-semibold text-center block hover:bg-gray-200 transition-colors duration-200">
                        Get Started Free
                    </a>
                </div>
                
                <!-- Pro Plan -->
                <div class="plan-card featured bg-white p-8 rounded-xl shadow-sm">
                    <div class="text-center mb-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Pro</h3>
                        <div class="text-4xl font-bold text-gray-900 mb-2">€29</div>
                        <p class="text-gray-600">One-time payment</p>
                    </div>
                    
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Everything in Free
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Bank integration (one-time scan)
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Email notifications
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Unlimited subscriptions
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Lifetime access
                        </li>
                    </ul>
                    
                    <a href="upgrade.php" class="w-full gradient-bg text-white px-6 py-3 rounded-lg font-semibold text-center block hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200">
                        Upgrade to Pro
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Privacy Section -->
    <section class="py-16 bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold text-gray-900 mb-6">Your Data is Secure</h2>
            <p class="text-lg text-gray-600 mb-8">
                Your subscription data is securely stored in our database and encrypted for your protection. 
                We use bank-grade security to ensure your information stays safe and accessible across all your devices.
            </p>
            <div class="flex justify-center space-x-8">
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    <span class="text-gray-700 font-medium">Bank-grade encryption</span>
                </div>
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    <span class="text-gray-700 font-medium">Secure database storage</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <div class="flex justify-center mb-6">
                    <img src="assets/images/logo.svg" alt="CashControl" class="h-8 opacity-80">
                </div>
                <p class="text-gray-400 mb-6">Take control of your subscriptions with CashControl</p>
                <div class="flex justify-center space-x-6">
                    <a href="auth/signin.php" class="text-gray-400 hover:text-white transition-colors duration-200">Sign In</a>
                    <a href="auth/signup.php" class="text-gray-400 hover:text-white transition-colors duration-200">Sign Up</a>
                    <a href="demo.php" class="text-gray-400 hover:text-white transition-colors duration-200">Demo</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
</body>
</html>
