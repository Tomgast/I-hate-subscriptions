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

// Get user's plan information using new Plan Manager
$planManager = getPlanManager();
$userPlan = $planManager->getUserPlan($userId);

// DEBUG: Check if user has an active plan
if (!$userPlan || !$userPlan['is_active']) {
    // TEMPORARY: Show debug info instead of redirecting
    echo "<div style='background: red; color: white; padding: 20px; margin: 20px;'>";
    echo "<h3>DEBUG: Plan Detection Issue</h3>";
    echo "<p>User Plan: " . print_r($userPlan, true) . "</p>";
    echo "<p>User ID: $userId</p>";
    echo "<p>Session: " . print_r($_SESSION, true) . "</p>";
    echo "<p><a href='upgrade.php?reason=no_plan' style='color: yellow;'>Continue to Upgrade Page</a></p>";
    echo "</div>";
    // Temporarily comment out the redirect
    // header('Location: upgrade.php?reason=no_plan');
    // exit;
}

// Route to appropriate dashboard based on plan type
if ($userPlan['plan_type'] === 'onetime') {
    // One-time users get limited dashboard
    header('Location: dashboard-onetime.php');
    exit;
} elseif (in_array($userPlan['plan_type'], ['monthly', 'yearly'])) {
    // Subscription users get full dashboard - continue with current page
    $isPaid = true; // Legacy compatibility
} else {
    // Unknown plan type - redirect to upgrade
    header('Location: upgrade.php?reason=unknown_plan');
    exit;
}

// Handle form submissions
if ($_POST && isset($_POST['action'])) {
    try {
        $pdo = getDBConnection();
        
        if ($_POST['action'] === 'add_subscription') {
            // Add new subscription
            $stmt = $pdo->prepare("INSERT INTO subscriptions (user_id, name, cost, billing_cycle, category, next_billing_date, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $nextBilling = null;
            
            // Calculate next billing date
            if ($_POST['billing_cycle'] === 'monthly') {
                $nextBilling = date('Y-m-d', strtotime('+1 month'));
            } elseif ($_POST['billing_cycle'] === 'yearly') {
                $nextBilling = date('Y-m-d', strtotime('+1 year'));
            } elseif ($_POST['billing_cycle'] === 'weekly') {
                $nextBilling = date('Y-m-d', strtotime('+1 week'));
            } elseif ($_POST['billing_cycle'] === 'daily') {
                $nextBilling = date('Y-m-d', strtotime('+1 day'));
            }
            
            $stmt->execute([
                $userId,
                $_POST['name'],
                floatval($_POST['cost']),
                $_POST['billing_cycle'],
                $_POST['category'],
                $nextBilling
            ]);
            
            $success = "Subscription added successfully!";
        } elseif ($_POST['action'] === 'delete_subscription') {
            // Delete subscription
            $stmt = $pdo->prepare("DELETE FROM subscriptions WHERE id = ? AND user_id = ?");
            $stmt->execute([$_POST['subscription_id'], $userId]);
            
            $success = "Subscription deleted successfully!";
        } elseif ($_POST['action'] === 'update_subscription') {
            // Update subscription
            $stmt = $pdo->prepare("UPDATE subscriptions SET name = ?, cost = ?, billing_cycle = ?, category = ?, status = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([
                $_POST['name'],
                floatval($_POST['cost']),
                $_POST['billing_cycle'],
                $_POST['category'],
                $_POST['status'],
                $_POST['subscription_id'],
                $userId
            ]);
            
            $success = "Subscription updated successfully!";
        }
    } catch (Exception $e) {
        error_log("Dashboard action error: " . $e->getMessage());
        error_log("Dashboard action error details: " . print_r($e, true));
        
        // Show more specific error in development/debugging
        if (isset($_GET['debug'])) {
            $error = "Database error: " . $e->getMessage();
        } else {
            $error = "An error occurred. Please try again.";
        }
    }
}

// Get user's subscriptions
try {
    $pdo = getDBConnection();
    
    // Get subscriptions
    $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $subscriptions = $stmt->fetchAll();
    
    // Calculate stats
    $stats = [
        'total_active' => 0,
        'monthly_total' => 0,
        'yearly_total' => 0,
        'next_payment' => null
    ];
    
    $upcomingPayments = [];
    $nextPaymentDate = null;
    
    foreach ($subscriptions as $subscription) {
        if ($subscription['is_active'] == 1) {
            $stats['total_active']++;
            
            // Calculate monthly cost
            $monthlyCost = 0;
            switch ($subscription['billing_cycle']) {
                case 'monthly':
                    $monthlyCost = $subscription['cost'];
                    break;
                case 'yearly':
                    $monthlyCost = $subscription['cost'] / 12;
                    break;
                case 'weekly':
                    $monthlyCost = $subscription['cost'] * 4.33;
                    break;
                case 'daily':
                    $monthlyCost = $subscription['cost'] * 30;
                    break;
            }
            
            $stats['monthly_total'] += $monthlyCost;
            $stats['yearly_total'] += $monthlyCost * 12;
            
            // Check for upcoming payments (next 7 days)
            if ($subscription['next_billing_date']) {
                $paymentDate = strtotime($subscription['next_billing_date']);
                $sevenDaysFromNow = strtotime('+7 days');
                
                if ($paymentDate <= $sevenDaysFromNow && $paymentDate >= time()) {
                    $upcomingPayments[] = $subscription;
                }
                
                // Track next payment
                if (!$nextPaymentDate || $paymentDate < $nextPaymentDate) {
                    $nextPaymentDate = $paymentDate;
                    $stats['next_payment'] = $subscription;
                }
            }
        }
    }
    
    // Sort upcoming payments by date
    usort($upcomingPayments, function($a, $b) {
        return strtotime($a['next_payment_date']) - strtotime($b['next_payment_date']);
    });
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $subscriptions = [];
    $stats = ['total_active' => 0, 'monthly_total' => 0, 'yearly_total' => 0, 'next_payment' => null];
    $upcomingPayments = [];
}

// Categories for filtering
$categories = [
    ['name' => 'Streaming', 'icon' => '📺'],
    ['name' => 'Music', 'icon' => '🎵'],
    ['name' => 'Software', 'icon' => '💻'],
    ['name' => 'Gaming', 'icon' => '🎮'],
    ['name' => 'News', 'icon' => '📰'],
    ['name' => 'Fitness', 'icon' => '💪'],
    ['name' => 'Other', 'icon' => '📦']
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CashControl</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .hero-gradient {
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #d1fae5 100%);
        }
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .subscription-card {
            transition: all 0.3s ease;
        }
        .subscription-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="bg-white">
    <?php include 'includes/header.php'; ?>

    <!-- Dashboard Content -->
    <div class="hero-gradient py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Dashboard Header -->
            <div class="text-center mb-12">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                    Welcome back, <?php echo htmlspecialchars($userName); ?>! 👋
                </h1>
                <p class="text-xl text-gray-600 mb-4">
                    Your Premium CashControl Dashboard
                </p>
                <div class="flex items-center justify-center space-x-4">
                    <?php echo getUserPlanBadge($userId); ?>
                    <?php if ($userPlan['plan_type'] === 'yearly'): ?>
                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">Priority Support</span>
                    <?php endif; ?>
                    <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Unlimited Scans</span>
                </div>
                <?php if ($isPaid): ?>
                    <p>You're on the <span class="text-green-600 font-semibold">Pro Plan</span> - all features unlocked!</p>
                <?php else: ?>
                    <p>You're on the <span class="text-gray-700 font-semibold">Basic Plan</span> - manage your subscriptions with ease</p>
                <a href="upgrade.php" class="gradient-bg text-white px-6 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-200">
                    Upgrade to Pro for Bank Integration
                </a>
                <?php endif; ?>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($success)): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-lg mb-8 shadow-sm">
                ✅ <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-lg mb-8 shadow-sm">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <!-- Stats Overview - Classic 4-Box Layout -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                <!-- Active Subscriptions -->
                <div class="stat-card bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-blue-100">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Active Subscriptions</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_active']; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Monthly Spending -->
                <div class="stat-card bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-green-100">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Monthly Spending</p>
                            <p class="text-2xl font-bold text-gray-900">€<?php echo number_format($stats['monthly_total'], 2); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Yearly Total -->
                <div class="stat-card bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-purple-100">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Yearly Total</p>
                            <p class="text-2xl font-bold text-gray-900">€<?php echo number_format($stats['yearly_total'], 2); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Expiring Subscriptions -->
                <div class="stat-card bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-orange-100">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Expiring Soon</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo count($upcomingPayments); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Subscriptions Section -->
    <section class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Subscription Management Header -->
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Your Subscriptions</h2>
                <p class="text-lg text-gray-600 mb-6">Manage all your recurring payments in one place</p>
                
                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <button onclick="openAddModal()" class="gradient-bg text-white px-6 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-200">
                        Add New Subscription
                    </button>
                    <?php if ($isPaid): ?>
                        <button onclick="startBankScan()" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl hover:bg-blue-700 transform hover:-translate-y-1 transition-all duration-200">
                            Scan Bank Account
                        </button>
                    <?php else: ?>
                        <a href="upgrade.php" class="bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl hover:bg-gray-700 transform hover:-translate-y-1 transition-all duration-200">
                            Upgrade for Bank Scan
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Filter and Search -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
                <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <label for="categoryFilter" class="text-sm font-medium text-gray-700">Filter by category:</label>
                        <select id="categoryFilter" class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500" onchange="filterSubscriptions()">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['name']); ?>">
                                <?php echo $category['icon']; ?> <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="text-sm text-gray-500">
                        Showing <?php echo count($subscriptions); ?> subscription<?php echo count($subscriptions) !== 1 ? 's' : ''; ?>
                    </div>
                </div>
            </div>
            <!-- Subscriptions List -->
            <?php if (empty($subscriptions)): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-4">No subscriptions yet</h3>
                <p class="text-gray-600 mb-8 max-w-md mx-auto">Get started by adding your first subscription manually or with bank integration</p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <button onclick="openAddModal()" class="gradient-bg text-white px-6 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-200">
                        Add Your First Subscription
                    </button>
                    <?php if (!$isPaid): ?>
                    <a href="upgrade.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl hover:bg-blue-700 transform hover:-translate-y-1 transition-all duration-200">
                        Upgrade for Bank Scan
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="subscriptionsList">
                <?php foreach ($subscriptions as $subscription): ?>
                <div class="subscription-card bg-white rounded-xl shadow-sm border border-gray-100 p-6" data-category="<?php echo htmlspecialchars($subscription['category']); ?>">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center">
                            <span class="text-2xl mr-3">
                                <?php 
                                $categoryIcon = '📦';
                                foreach ($categories as $cat) {
                                    if ($cat['name'] === $subscription['category']) {
                                        $categoryIcon = $cat['icon'];
                                        break;
                                    }
                                }
                                echo $categoryIcon;
                                ?>
                            </span>
                            <div>
                                <h4 class="font-semibold text-gray-900 text-lg"><?php echo htmlspecialchars($subscription['name']); ?></h4>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($subscription['category']); ?></p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button onclick="editSubscription(<?php echo $subscription['id']; ?>)" class="text-gray-400 hover:text-blue-600 transition-colors" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            <button onclick="deleteSubscription(<?php echo $subscription['id']; ?>)" class="text-gray-400 hover:text-red-600 transition-colors" title="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="text-3xl font-bold text-gray-900 mb-1">
                            €<?php echo number_format($subscription['cost'], 2); ?>
                        </div>
                        <div class="text-sm text-gray-500">
                            per <?php echo $subscription['billing_cycle']; ?>
                        </div>
                    </div>
                    
                    <?php if ($subscription['next_billing_date']): ?>
                    <div class="flex items-center justify-between text-sm mb-4">
                        <span class="text-gray-500">Next payment:</span>
                        <span class="font-medium text-gray-900">
                            <?php echo date('M j, Y', strtotime($subscription['next_billing_date'])); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="pt-4 border-t border-gray-100">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $subscription['is_active'] == 1 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo $subscription['is_active'] == 1 ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Add Subscription Modal -->
    <div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Add New Subscription</h3>
                <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_subscription">
                
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Service Name</label>
                        <input type="text" id="name" name="name" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="e.g., Netflix, Spotify">
                    </div>
                    
                    <div>
                        <label for="cost" class="block text-sm font-medium text-gray-700 mb-1">Cost (€)</label>
                        <input type="number" id="cost" name="cost" step="0.01" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="9.99">
                    </div>
                    
                    <div>
                        <label for="billing_cycle" class="block text-sm font-medium text-gray-700 mb-1">Billing Cycle</label>
                        <select id="billing_cycle" name="billing_cycle" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                            <option value="weekly">Weekly</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select id="category" name="category" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['name']); ?>">
                                <?php echo $category['icon']; ?> <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="flex space-x-3 mt-6">
                    <button type="button" onclick="closeAddModal()" class="flex-1 bg-gray-200 text-gray-800 px-4 py-2 rounded-lg font-medium hover:bg-gray-300 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-green-700 transition-colors">
                        Add Subscription
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.remove('hidden');
            document.getElementById('addModal').classList.add('flex');
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.add('hidden');
            document.getElementById('addModal').classList.remove('flex');
        }

        function filterSubscriptions() {
            const filter = document.getElementById('categoryFilter').value;
            const cards = document.querySelectorAll('.subscription-card');
            
            cards.forEach(card => {
                if (filter === '' || card.dataset.category === filter) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function deleteSubscription(id) {
            if (confirm('Are you sure you want to delete this subscription?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_subscription">
                    <input type="hidden" name="subscription_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function editSubscription(id) {
            // For now, just show an alert - you can implement edit modal later
            alert('Edit functionality coming soon! For now, you can delete and re-add the subscription.');
        }

        function startBankScan() {
            // Check if user can perform bank scan
            <?php if (userCanAccess($userId, 'bank_scan')): ?>
            // Redirect to bank integration with unlimited access
            window.location.href = 'bank/scan.php?plan=<?php echo $userPlan['plan_type']; ?>';
            <?php else: ?>
            alert('Bank scan not available with your current plan. Please upgrade.');
            window.location.href = 'upgrade.php';
            <?php endif; ?>
        }

        // Close modal when clicking outside
        document.getElementById('addModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddModal();
            }
        });
    </script>
</body>
</html>
