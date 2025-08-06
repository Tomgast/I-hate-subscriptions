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

// Handle form submissions
if ($_POST && isset($_POST['action'])) {
    try {
        $pdo = getDBConnection();
        
        if ($_POST['action'] === 'add_subscription') {
            // Add new subscription
            $stmt = $pdo->prepare("INSERT INTO subscriptions (user_id, name, cost, billing_cycle, category, next_payment_date, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())");
            $nextPayment = null;
            
            // Calculate next payment date
            if ($_POST['billing_cycle'] === 'monthly') {
                $nextPayment = date('Y-m-d', strtotime('+1 month'));
            } elseif ($_POST['billing_cycle'] === 'yearly') {
                $nextPayment = date('Y-m-d', strtotime('+1 year'));
            } elseif ($_POST['billing_cycle'] === 'weekly') {
                $nextPayment = date('Y-m-d', strtotime('+1 week'));
            }
            
            $stmt->execute([
                $userId,
                $_POST['name'],
                floatval($_POST['cost']),
                $_POST['billing_cycle'],
                $_POST['category'],
                $nextPayment
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
        $error = "An error occurred. Please try again.";
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
        if ($subscription['status'] === 'active') {
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
            }
            
            $stats['monthly_total'] += $monthlyCost;
            $stats['yearly_total'] += $monthlyCost * 12;
            
            // Check for upcoming payments (next 7 days)
            if ($subscription['next_payment_date']) {
                $paymentDate = strtotime($subscription['next_payment_date']);
                $now = time();
                $sevenDaysFromNow = $now + (7 * 24 * 60 * 60);
                
                if ($paymentDate >= $now && $paymentDate <= $sevenDaysFromNow) {
                    $upcomingPayments[] = $subscription;
                }
                
                // Find next payment
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
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- Success/Error Messages -->
        <?php if (isset($success)): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
            ✅ <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
            ❌ <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <!-- Welcome Section -->
        <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Welcome back, <?php echo htmlspecialchars($userName); ?>!</h1>
                    <p class="text-gray-600 mt-1">
                        <?php if ($isPaid): ?>
                            You're on the <span class="text-green-600 font-semibold">Pro Plan</span> - enjoy all features!
                        <?php else: ?>
                            You're on the <span class="text-gray-600 font-semibold">Free Plan</span> - <a href="upgrade.php" class="text-green-600 hover:text-green-700 underline">upgrade to Pro</a> for more features
                        <?php endif; ?>
                    </p>
                </div>
                <div class="text-right">
                    <?php if (!$isPaid): ?>
                        <a href="upgrade.php" class="bg-green-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-green-700 transition-colors">
                            Upgrade to Pro
                        </a>
                    <?php else: ?>
                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">Pro User</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100">
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

            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Monthly Total</p>
                        <p class="text-2xl font-bold text-gray-900">€<?php echo number_format($stats['monthly_total'], 2); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100">
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

            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-orange-100">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Next Payment</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?php if ($stats['next_payment']): ?>
                                <?php echo date('M j', strtotime($stats['next_payment']['next_payment_date'])); ?>
                            <?php else: ?>
                                None
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pro Features Section -->
        <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Available Features</h2>
                <?php if (!$isPaid): ?>
                <span class="text-sm text-gray-500">Upgrade to unlock Pro features</span>
                <?php endif; ?>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Manual Add (Free Feature) -->
                <button onclick="openAddModal()" class="flex items-center p-4 border-2 border-green-200 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                    <div class="p-2 bg-green-100 rounded-lg mr-3">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </div>
                    <div class="text-left">
                        <p class="font-medium text-green-900">Add Subscription</p>
                        <p class="text-sm text-green-600">Manual entry (Free)</p>
                    </div>
                </button>
                
                <!-- Bank Integration (Pro Feature) -->
                <?php if ($isPaid): ?>
                <button onclick="startBankScan()" class="flex items-center p-4 border-2 border-blue-200 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                    <div class="p-2 bg-blue-100 rounded-lg mr-3">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                    </div>
                    <div class="text-left">
                        <p class="font-medium text-blue-900">Bank Scan</p>
                        <p class="text-sm text-blue-600">Auto-discover subscriptions</p>
                    </div>
                </button>
                <?php else: ?>
                <a href="upgrade.php" class="flex items-center p-4 border-2 border-gray-300 bg-gray-50 rounded-lg opacity-75 cursor-pointer hover:opacity-100 transition-opacity">
                    <div class="p-2 bg-gray-100 rounded-lg mr-3">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                    </div>
                    <div class="text-left">
                        <p class="font-medium text-gray-700">Bank Scan 🔒</p>
                        <p class="text-sm text-orange-600">Pro feature - Upgrade to unlock</p>
                    </div>
                </a>
                <?php endif; ?>
                
                <!-- Email Notifications (Pro Feature) -->
                <?php if ($isPaid): ?>
                <a href="settings.php" class="flex items-center p-4 border-2 border-purple-200 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                    <div class="p-2 bg-purple-100 rounded-lg mr-3">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="text-left">
                        <p class="font-medium text-purple-900">Email Reminders</p>
                        <p class="text-sm text-purple-600">Configure notifications</p>
                    </div>
                </a>
                <?php else: ?>
                <a href="upgrade.php" class="flex items-center p-4 border-2 border-gray-300 bg-gray-50 rounded-lg opacity-75 cursor-pointer hover:opacity-100 transition-opacity">
                    <div class="p-2 bg-gray-100 rounded-lg mr-3">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="text-left">
                        <p class="font-medium text-gray-700">Email Reminders 🔒</p>
                        <p class="text-sm text-orange-600">Pro feature - Upgrade to unlock</p>
                    </div>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Subscriptions List -->
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold text-gray-900">Your Subscriptions</h2>
                <div class="flex items-center space-x-3">
                    <select id="categoryFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm" onchange="filterSubscriptions()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['name']); ?>">
                            <?php echo $category['icon']; ?> <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button onclick="openAddModal()" class="bg-green-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-green-700 transition-colors">
                        Add Subscription
                    </button>
                </div>
            </div>
            
            <?php if (empty($subscriptions)): ?>
            <div class="text-center py-12">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No subscriptions yet</h3>
                <p class="text-gray-500 mb-6">Get started by adding your first subscription manually or with bank integration</p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <button onclick="openAddModal()" class="bg-green-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-green-700 transition-colors">
                        Add Manually (Free)
                    </button>
                    <?php if (!$isPaid): ?>
                    <a href="upgrade.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                        Upgrade for Bank Scan
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="subscriptionsList">
                <?php foreach ($subscriptions as $subscription): ?>
                <div class="subscription-card border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow" data-category="<?php echo htmlspecialchars($subscription['category']); ?>">
                    <div class="flex items-start justify-between mb-3">
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
                                <h4 class="font-semibold text-gray-900"><?php echo htmlspecialchars($subscription['name']); ?></h4>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($subscription['category']); ?></p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button onclick="editSubscription(<?php echo $subscription['id']; ?>)" class="text-gray-400 hover:text-blue-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            <button onclick="deleteSubscription(<?php echo $subscription['id']; ?>)" class="text-gray-400 hover:text-red-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-500">Cost:</span>
                            <span class="font-semibold text-gray-900">€<?php echo number_format($subscription['cost'], 2); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-500">Billing:</span>
                            <span class="text-sm text-gray-700 capitalize"><?php echo htmlspecialchars($subscription['billing_cycle']); ?></span>
                        </div>
                        <?php if ($subscription['next_payment_date']): ?>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-500">Next payment:</span>
                            <span class="text-sm text-gray-700"><?php echo date('M j, Y', strtotime($subscription['next_payment_date'])); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-500">Status:</span>
                            <span class="text-sm px-2 py-1 rounded-full <?php echo $subscription['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                <?php echo ucfirst($subscription['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

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
            <?php if ($isPaid): ?>
            alert('Bank scan feature coming soon! This will connect to your bank to automatically discover subscriptions.');
            <?php else: ?>
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
                    </button>
                </div>
                <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="subscriptionsList">
                    <?php foreach ($subscriptions as $subscription): ?>
                    <div class="subscription-card border border-gray-200 rounded-lg p-4" data-category="<?php echo htmlspecialchars($subscription['category']); ?>">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center">
                                <?php if ($subscription['category_icon']): ?>
                                <span style="font-size: 1.5rem; margin-right: 0.5rem;"><?php echo $subscription['category_icon']; ?></span>
                                <?php endif; ?>
                                <div>
                                    <h4 class="font-semibold text-gray-900"><?php echo htmlspecialchars($subscription['name']); ?></h4>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($subscription['category']); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-1">
                                <button onclick="editSubscription(<?php echo $subscription['id']; ?>)" class="text-gray-400 hover:text-blue-600">
                                    <span class="icon-settings"></span>
                                </button>
                                <button onclick="deleteSubscription(<?php echo $subscription['id']; ?>)" class="text-gray-400 hover:text-red-600">
                                    ❌
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="text-2xl font-bold text-gray-900">
                                €<?php echo number_format($subscription['cost'], 2); ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                per <?php echo $subscription['billing_cycle']; ?>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Next payment:</span>
                            <span class="font-medium">
                                <?php echo date('M j, Y', strtotime($subscription['next_payment_date'])); ?>
                            </span>
                        </div>
                        
                        <?php if ($subscription['description']): ?>
                        <div class="mt-2 text-sm text-gray-600">
                            <?php echo htmlspecialchars($subscription['description']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <button onclick="recordPayment(<?php echo $subscription['id']; ?>)" class="btn btn-secondary" style="width: 100%; font-size: 0.875rem; padding: 0.5rem;">
                                Mark as Paid
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add/Edit Subscription Modal -->
    <div id="subscriptionModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div class="modal-content" style="background: white; margin: 5% auto; padding: 2rem; width: 90%; max-width: 500px; border-radius: 0.5rem; position: relative;">
            <div class="flex justify-between items-center mb-4">
                <h2 id="modalTitle" class="text-xl font-bold text-gray-900">Add Subscription</h2>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600" style="font-size: 1.5rem;">&times;</button>
            </div>
            
            <form id="subscriptionForm">
                <input type="hidden" id="subscriptionId" name="id">
                
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Service Name</label>
                    <input type="text" id="name" name="name" class="form-input" placeholder="Netflix, Spotify, etc." required>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="cost" class="block text-sm font-medium text-gray-700 mb-2">Cost</label>
                        <input type="number" id="cost" name="cost" class="form-input" step="0.01" placeholder="9.99" required>
                    </div>
                    <div>
                        <label for="billing_cycle" class="block text-sm font-medium text-gray-700 mb-2">Billing Cycle</label>
                        <select id="billing_cycle" name="billing_cycle" class="form-input">
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                            <option value="weekly">Weekly</option>
                            <option value="daily">Daily</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select id="category" name="category" class="form-input">
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['name']); ?>">
                                <?php echo $category['icon']; ?> <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="next_payment_date" class="block text-sm font-medium text-gray-700 mb-2">Next Payment</label>
                        <input type="date" id="next_payment_date" name="next_payment_date" class="form-input" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description (Optional)</label>
                    <textarea id="description" name="description" class="form-input" rows="3" placeholder="Additional notes..."></textarea>
                </div>
                
                <div class="mb-6">
                    <label for="website_url" class="block text-sm font-medium text-gray-700 mb-2">Website (Optional)</label>
                    <input type="url" id="website_url" name="website_url" class="form-input" placeholder="https://netflix.com">
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Subscription</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function openAddSubscriptionModal() {
            document.getElementById('modalTitle').textContent = 'Add Subscription';
            document.getElementById('subscriptionForm').reset();
            document.getElementById('subscriptionId').value = '';
            
            // Set default next payment date to next month
            const nextMonth = new Date();
            nextMonth.setMonth(nextMonth.getMonth() + 1);
            document.getElementById('next_payment_date').value = nextMonth.toISOString().split('T')[0];
            
            document.getElementById('subscriptionModal').style.display = 'block';
        }
        
        function editSubscription(id) {
            // This would fetch subscription data and populate the form
            document.getElementById('modalTitle').textContent = 'Edit Subscription';
            document.getElementById('subscriptionId').value = id;
            document.getElementById('subscriptionModal').style.display = 'block';
            
            // TODO: Fetch and populate subscription data
        }
        
        function closeModal() {
            document.getElementById('subscriptionModal').style.display = 'none';
        }
        
        function deleteSubscription(id) {
            if (confirm('Are you sure you want to delete this subscription?')) {
                window.location.href = 'api/subscriptions.php?action=delete&id=' + id;
            }
        }
        
        function recordPayment(id) {
            if (confirm('Mark this subscription as paid and update the next payment date?')) {
                window.location.href = 'api/subscriptions.php?action=record_payment&id=' + id;
            }
        }
        
        // Category filter
        document.getElementById('categoryFilter').addEventListener('change', function() {
            const selectedCategory = this.value;
            const subscriptionCards = document.querySelectorAll('.subscription-card');
            
            subscriptionCards.forEach(card => {
                if (selectedCategory === '' || card.dataset.category === selectedCategory) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
        
        // Form submission
        document.getElementById('subscriptionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const action = document.getElementById('subscriptionId').value ? 'update' : 'add';
            
            fetch('api/subscriptions.php?action=' + action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error saving subscription');
                console.error('Error:', error);
            });
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('subscriptionModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>

</body>
</html>
