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

// Get user's plan information using database-based detection
require_once 'includes/user_plan_helper.php';
$userPlan = UserPlanHelper::getUserPlanStatus($userId);

// Refresh session with current database state
UserPlanHelper::refreshUserSession($userId);

// Route to appropriate dashboard based on plan type
if ($userPlan['plan_type'] === 'one_time' && $userPlan['is_paid']) {
    // One-time users get limited dashboard
    header('Location: dashboard-onetime.php');
    exit;
} elseif (in_array($userPlan['plan_type'], ['monthly', 'yearly']) && $userPlan['is_paid']) {
    // Subscription users get full dashboard - continue with current page
    $isPaid = true;
} else {
    // Unpaid users must upgrade first - no free access
    header('Location: upgrade.php');
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
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            $stmt = $pdo->prepare("UPDATE subscriptions SET name = ?, cost = ?, billing_cycle = ?, category = ?, is_active = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([
                $_POST['name'],
                floatval($_POST['cost']),
                $_POST['billing_cycle'],
                $_POST['category'],
                $isActive,
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
    
    // Get all subscriptions (simple query that works reliably)
    $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $subscriptions = $stmt->fetchAll();
    
    // Debug: Log the number of subscriptions found
    error_log("Found " . count($subscriptions) . " subscriptions for user $userId");
    if (count($subscriptions) > 0) {
        error_log("First subscription: " . print_r($subscriptions[0], true));
    }
    
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
        // Check if subscription is active (using actual database fields)
        $isActive = (bool)($subscription['is_active'] ?? ($subscription['status'] === 'active'));
        
        if ($isActive) {
            $stats['total_active']++;
            
            // Get amount (using actual database fields)
            $amount = (float)($subscription['cost'] ?? $subscription['amount'] ?? 0);
            
            // Calculate monthly cost
            $monthlyCost = 0;
            $billingCycle = $subscription['billing_cycle'] ?? 'monthly';
            
            switch ($billingCycle) {
                case 'monthly':
                    $monthlyCost = $amount;
                    break;
                case 'yearly':
                    $monthlyCost = $amount / 12;
                    break;
                case 'weekly':
                    $monthlyCost = $amount * 4.33;
                    break;
                case 'daily':
                    $monthlyCost = $amount * 30;
                    break;
                default:
                    // If no billing cycle specified, assume monthly
                    $monthlyCost = $amount;
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

// Get comprehensive bank and sync status information
require_once 'includes/multi_bank_service.php';
$multiBankService = new MultiBankService();
$bankAccountSummary = $multiBankService->getBankAccountSummary($userId);

// Get enhanced bank sync status and recent activity
try {
    // Get last sync information with more details
    $stmt = $pdo->prepare("
        SELECT bsr.provider, bsr.status, bsr.last_sync_at, bsr.subscriptions_found, 
               bsr.created_at, bsr.scan_duration_seconds,
               COUNT(bc.id) as connected_accounts
        FROM bank_scan_results bsr
        LEFT JOIN bank_connections bc ON bc.user_id = bsr.user_id AND bc.provider = bsr.provider AND bc.status = 'active'
        WHERE bsr.user_id = ? 
        GROUP BY bsr.id
        ORDER BY bsr.created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $lastSyncInfo = $stmt->fetch();
    
    // Get detailed bank connection status
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_connections,
               SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_connections,
               MAX(last_sync_at) as last_sync_date,
               GROUP_CONCAT(DISTINCT provider) as providers
        FROM bank_connections 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $connectionStatus = $stmt->fetch();
    
    // Get recent transactions count and activity indicators
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as recent_transactions,
               COUNT(DISTINCT merchant_name) as unique_merchants,
               MIN(booking_date) as oldest_transaction,
               MAX(booking_date) as newest_transaction
        FROM raw_transactions 
        WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stmt->execute([$userId]);
    $recentActivity = $stmt->fetch();
    
    // Check for subscription detection issues (like the November 30 problem)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as problematic_dates
        FROM subscriptions 
        WHERE user_id = ? AND next_billing_date LIKE '%-11-30'
    ");
    $stmt->execute([$userId]);
    $dateIssues = $stmt->fetch();
    
} catch (Exception $e) {
    error_log("Error getting enhanced bank sync status: " . $e->getMessage());
    $lastSyncInfo = null;
    $connectionStatus = ['total_connections' => 0, 'active_connections' => 0, 'last_sync_date' => null, 'providers' => ''];
    $recentActivity = ['recent_transactions' => 0, 'unique_merchants' => 0, 'oldest_transaction' => null, 'newest_transaction' => null];
    $dateIssues = ['problematic_dates' => 0];
}

// ENHANCED DASHBOARD FEATURES
$priceChanges = [];
$duplicateSubscriptions = [];
$anomalies = [];
$potentialSubscriptions = [];
$categoryInsights = [];
$syncHealthStatus = [];

try {
    // 1. PRICE CHANGE DETECTION
    $stmt = $pdo->prepare("
        SELECT s.id, s.name, s.cost as current_cost, ph.old_cost, ph.new_cost, ph.change_date
        FROM subscriptions s
        JOIN (
            SELECT subscription_id, old_cost, new_cost, change_date,
                   ROW_NUMBER() OVER (PARTITION BY subscription_id ORDER BY change_date DESC) as rn
            FROM price_history
            WHERE change_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        ) ph ON s.id = ph.subscription_id AND ph.rn = 1
        WHERE s.user_id = ? AND s.is_active = 1
        ORDER BY ph.change_date DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $priceChanges = $stmt->fetchAll();
    
    // 2. DUPLICATE DETECTION
    $stmt = $pdo->prepare("
        SELECT s1.id, s1.name, s1.category, s1.cost,
               GROUP_CONCAT(s2.name SEPARATOR ', ') as similar_services,
               GROUP_CONCAT(s2.id SEPARATOR ',') as similar_ids
        FROM subscriptions s1
        JOIN subscriptions s2 ON s1.category = s2.category 
            AND s1.id != s2.id 
            AND s1.user_id = s2.user_id
        WHERE s1.user_id = ? AND s1.is_active = 1 AND s2.is_active = 1
        GROUP BY s1.id, s1.name, s1.category, s1.cost
        HAVING COUNT(s2.id) > 0
        ORDER BY s1.cost DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $duplicateSubscriptions = $stmt->fetchAll();
    
    // 3. ANOMALY DETECTION
    $stmt = $pdo->prepare("
        SELECT rt.merchant_name, rt.amount, rt.booking_date, 
               s.name as subscription_name, s.cost as expected_cost,
               ABS(ABS(rt.amount) - s.cost) as cost_difference
        FROM raw_transactions rt
        LEFT JOIN subscriptions s ON (
            LOWER(rt.merchant_name) LIKE CONCAT('%', LOWER(SUBSTRING(s.name, 1, 5)), '%')
            OR LOWER(s.name) LIKE CONCAT('%', LOWER(SUBSTRING(rt.merchant_name, 1, 5)), '%')
        ) AND s.user_id = rt.user_id AND s.is_active = 1
        WHERE rt.user_id = ? 
        AND rt.amount < 0
        AND rt.booking_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
        AND s.id IS NOT NULL
        AND ABS(ABS(rt.amount) - s.cost) > (s.cost * 0.1)
        ORDER BY cost_difference DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $anomalies = $stmt->fetchAll();
    
    // 4. POTENTIAL NEW SUBSCRIPTIONS
    $stmt = $pdo->prepare("
        SELECT merchant_name, COUNT(*) as frequency, AVG(ABS(amount)) as avg_amount,
               MIN(booking_date) as first_seen, MAX(booking_date) as last_seen
        FROM raw_transactions rt
        WHERE user_id = ? AND amount < 0
        AND booking_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        AND NOT EXISTS (
            SELECT 1 FROM subscriptions s 
            WHERE s.user_id = rt.user_id 
            AND (LOWER(rt.merchant_name) LIKE CONCAT('%', LOWER(SUBSTRING(s.name, 1, 5)), '%')
                 OR LOWER(s.name) LIKE CONCAT('%', LOWER(SUBSTRING(rt.merchant_name, 1, 5)), '%'))
        )
        GROUP BY merchant_name
        HAVING COUNT(*) >= 3
        ORDER BY frequency DESC, avg_amount DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $potentialSubscriptions = $stmt->fetchAll();
    
    // 5. CATEGORY INSIGHTS WITH TRANSACTION CATEGORIZATION
    $stmt = $pdo->prepare("
        SELECT 
            CASE 
                WHEN LOWER(s.name) LIKE '%netflix%' OR LOWER(s.name) LIKE '%disney%' OR LOWER(s.name) LIKE '%hbo%' OR LOWER(s.name) LIKE '%prime%' THEN 'Streaming'
                WHEN LOWER(s.name) LIKE '%spotify%' OR LOWER(s.name) LIKE '%apple music%' OR LOWER(s.name) LIKE '%youtube music%' THEN 'Music'
                WHEN LOWER(s.name) LIKE '%adobe%' OR LOWER(s.name) LIKE '%microsoft%' OR LOWER(s.name) LIKE '%google%' THEN 'Software'
                WHEN LOWER(s.name) LIKE '%gym%' OR LOWER(s.name) LIKE '%fitness%' OR LOWER(s.name) LIKE '%sport%' THEN 'Fitness'
                WHEN LOWER(s.name) LIKE '%news%' OR LOWER(s.name) LIKE '%times%' OR LOWER(s.name) LIKE '%guardian%' THEN 'News'
                ELSE COALESCE(s.category, 'Other')
            END as smart_category,
            COUNT(*) as count,
            SUM(s.cost) as total_monthly,
            AVG(s.cost) as avg_cost
        FROM subscriptions s
        WHERE s.user_id = ? AND s.is_active = 1
        GROUP BY smart_category
        ORDER BY total_monthly DESC
    ");
    $stmt->execute([$userId]);
    $categoryInsights = $stmt->fetchAll();
    
    // 6. REAL-TIME SYNC STATUS
    $stmt = $pdo->prepare("
        SELECT 
            bc.provider,
            bc.status,
            bc.last_sync_at,
            bc.account_name,
            COUNT(rt.id) as recent_transactions,
            CASE 
                WHEN bc.last_sync_at IS NULL THEN 'never'
                WHEN bc.last_sync_at < DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 'stale'
                WHEN bc.last_sync_at < DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 'outdated'
                ELSE 'fresh'
            END as sync_health
        FROM bank_connections bc
        LEFT JOIN raw_transactions rt ON bc.user_id = rt.user_id 
            AND rt.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        WHERE bc.user_id = ? AND bc.status = 'active'
        GROUP BY bc.id, bc.provider, bc.status, bc.last_sync_at, bc.account_name
        ORDER BY bc.last_sync_at DESC
    ");
    $stmt->execute([$userId]);
    $syncHealthStatus = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Enhanced dashboard features error: " . $e->getMessage());
}

// Categories for filtering (enhanced with smart categorization)
$categories = [
    ['name' => 'Streaming', 'icon' => '📺'],
    ['name' => 'Music', 'icon' => '🎵'],
    ['name' => 'Software', 'icon' => '💻'],
    ['name' => 'Gaming', 'icon' => '🎮'],
    ['name' => 'News', 'icon' => '📰'],
    ['name' => 'Fitness', 'icon' => '💪'],
    ['name' => 'Other', 'icon' => '📦']
];

// Auto-categorize subscriptions that are currently 'Other'
try {
    $stmt = $pdo->prepare("
        UPDATE subscriptions 
        SET category = CASE 
            WHEN LOWER(name) LIKE '%netflix%' OR LOWER(name) LIKE '%disney%' OR LOWER(name) LIKE '%hbo%' OR LOWER(name) LIKE '%prime%' THEN 'Streaming'
            WHEN LOWER(name) LIKE '%spotify%' OR LOWER(name) LIKE '%apple music%' OR LOWER(name) LIKE '%youtube music%' THEN 'Music'
            WHEN LOWER(name) LIKE '%adobe%' OR LOWER(name) LIKE '%microsoft%' OR LOWER(name) LIKE '%google%' THEN 'Software'
            WHEN LOWER(name) LIKE '%gym%' OR LOWER(name) LIKE '%fitness%' OR LOWER(name) LIKE '%sport%' THEN 'Fitness'
            WHEN LOWER(name) LIKE '%news%' OR LOWER(name) LIKE '%times%' OR LOWER(name) LIKE '%guardian%' THEN 'News'
            ELSE category
        END
        WHERE user_id = ? AND (category IS NULL OR category = 'Other')
    ");
    $stmt->execute([$userId]);
} catch (Exception $e) {
    error_log("Auto-categorization error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CashControl</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <?php if ($connectionStatus['active_connections'] > 0): ?>
                        <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">
                            🏦 <?php echo $connectionStatus['active_connections']; ?> Bank<?php echo $connectionStatus['active_connections'] > 1 ? 's' : ''; ?> Connected
                        </span>
                        <?php if ($lastSyncInfo && $lastSyncInfo['created_at']): ?>
                        <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded" title="Last sync: <?php echo $lastSyncInfo['created_at']; ?>">
                            ⏱️ Synced <?php echo date('M j, H:i', strtotime($lastSyncInfo['created_at'])); ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($recentActivity['recent_transactions'] > 0): ?>
                        <span class="bg-purple-100 text-purple-800 text-xs font-medium px-2.5 py-0.5 rounded">
                            📊 <?php echo $recentActivity['recent_transactions']; ?> new transactions
                        </span>
                        <?php endif; ?>
                        <?php if ($dateIssues['problematic_dates'] > 0): ?>
                        <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded" title="Some subscriptions may have incorrect next payment dates">
                            ⚠️ Date Issues
                        </span>
                        <?php endif; ?>
                        <?php if (count($priceChanges) > 0): ?>
                        <span class="bg-orange-100 text-orange-800 text-xs font-medium px-2.5 py-0.5 rounded" title="Recent price changes detected">
                            💰 Price Changes
                        </span>
                        <?php endif; ?>
                        <?php if (count($duplicateSubscriptions) > 0): ?>
                        <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded" title="Duplicate services detected">
                            🔄 Duplicates
                        </span>
                        <?php endif; ?>
                        <?php if (count($anomalies) > 0): ?>
                        <span class="bg-purple-100 text-purple-800 text-xs font-medium px-2.5 py-0.5 rounded" title="Unusual charges detected">
                            🚨 Anomalies
                        </span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded">No Banks Connected</span>
                        <a href="bank/connect.php" class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded hover:bg-green-200 transition-colors">
                            🔗 Connect Bank
                        </a>
                    <?php endif; ?>
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
                            <p class="text-2xl font-bold text-gray-900" data-stat="monthly">€<?php echo number_format($stats['monthly_total'], 2); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Yearly Total -->
{{ ... }}
                <div class="stat-card bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-purple-100">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Yearly Total</p>
                            <p class="text-2xl font-bold text-gray-900" data-stat="yearly">€<?php echo number_format($stats['yearly_total'], 2); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Total Subscriptions -->
                <div class="stat-card bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-indigo-100">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Total Subscriptions</p>
                            <p class="text-2xl font-bold text-gray-900" data-stat="total"><?php echo count($subscriptions); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Insights Section -->
    <?php if (count($priceChanges) > 0 || count($duplicateSubscriptions) > 0 || count($anomalies) > 0 || count($potentialSubscriptions) > 0): ?>
    <section class="py-8 bg-white border-t border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">🔍 Smart Insights</h2>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Price Changes -->
                <?php if (count($priceChanges) > 0): ?>
                <div class="bg-orange-50 border border-orange-200 rounded-xl p-6">
                    <div class="flex items-center mb-4">
                        <div class="p-2 bg-orange-100 rounded-lg">
                            <span class="text-orange-600 text-xl">💰</span>
                        </div>
                        <h3 class="ml-3 text-lg font-semibold text-orange-900">Recent Price Changes</h3>
                    </div>
                    <div class="space-y-3">
                        <?php foreach ($priceChanges as $change): ?>
                        <div class="flex items-center justify-between bg-white rounded-lg p-3 border border-orange-100">
                            <div>
                                <p class="font-medium text-gray-900"><?php echo htmlspecialchars($change['name']); ?></p>
                                <p class="text-sm text-gray-600"><?php echo date('M j, Y', strtotime($change['change_date'])); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500">€<?php echo number_format($change['old_cost'], 2); ?> → <span class="font-semibold text-orange-600">€<?php echo number_format($change['new_cost'], 2); ?></span></p>
                                <p class="text-xs <?php echo $change['new_cost'] > $change['old_cost'] ? 'text-red-600' : 'text-green-600'; ?>">
                                    <?php echo $change['new_cost'] > $change['old_cost'] ? '+' : ''; ?>€<?php echo number_format($change['new_cost'] - $change['old_cost'], 2); ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Duplicate Subscriptions -->
                <?php if (count($duplicateSubscriptions) > 0): ?>
                <div class="bg-red-50 border border-red-200 rounded-xl p-6">
                    <div class="flex items-center mb-4">
                        <div class="p-2 bg-red-100 rounded-lg">
                            <span class="text-red-600 text-xl">🔄</span>
                        </div>
                        <h3 class="ml-3 text-lg font-semibold text-red-900">Potential Duplicates</h3>
                    </div>
                    <div class="space-y-3">
                        <?php foreach ($duplicateSubscriptions as $duplicate): ?>
                        <div class="bg-white rounded-lg p-3 border border-red-100">
                            <div class="flex items-center justify-between mb-2">
                                <p class="font-medium text-gray-900"><?php echo htmlspecialchars($duplicate['name']); ?></p>
                                <span class="text-sm font-semibold text-red-600">€<?php echo number_format($duplicate['cost'], 2); ?></span>
                            </div>
                            <p class="text-sm text-gray-600 mb-2">Similar to: <?php echo htmlspecialchars($duplicate['similar_services']); ?></p>
                            <div class="flex space-x-2">
                                <a href="unsubscribe/index.php?search=<?php echo urlencode($duplicate['name']); ?>" class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded hover:bg-red-200 transition-colors">
                                    🚫 Cancel Guide
                                </a>
                                <button onclick="editSubscription(<?php echo $duplicate['id']; ?>)" class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded hover:bg-gray-200 transition-colors">
                                    ✏️ Edit
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Anomalies -->
                <?php if (count($anomalies) > 0): ?>
                <div class="bg-purple-50 border border-purple-200 rounded-xl p-6">
                    <div class="flex items-center mb-4">
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <span class="text-purple-600 text-xl">🚨</span>
                        </div>
                        <h3 class="ml-3 text-lg font-semibold text-purple-900">Unusual Charges</h3>
                    </div>
                    <div class="space-y-3">
                        <?php foreach ($anomalies as $anomaly): ?>
                        <div class="bg-white rounded-lg p-3 border border-purple-100">
                            <div class="flex items-center justify-between mb-1">
                                <p class="font-medium text-gray-900"><?php echo htmlspecialchars($anomaly['merchant_name']); ?></p>
                                <span class="text-sm text-purple-600"><?php echo date('M j', strtotime($anomaly['booking_date'])); ?></span>
                            </div>
                            <p class="text-sm text-gray-600">Expected: €<?php echo number_format($anomaly['expected_cost'], 2); ?> | Charged: €<?php echo number_format(abs($anomaly['actual_amount']), 2); ?></p>
                            <p class="text-xs text-purple-600">Difference: €<?php echo number_format($anomaly['cost_difference'], 2); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Potential New Subscriptions -->
                <?php if (count($potentialSubscriptions) > 0): ?>
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                    <div class="flex items-center mb-4">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <span class="text-blue-600 text-xl">🔍</span>
                        </div>
                        <h3 class="ml-3 text-lg font-semibold text-blue-900">Potential New Subscriptions</h3>
                    </div>
                    <div class="space-y-3">
                        <?php foreach ($potentialSubscriptions as $potential): ?>
                        <div class="bg-white rounded-lg p-3 border border-blue-100">
                            <div class="flex items-center justify-between mb-2">
                                <p class="font-medium text-gray-900"><?php echo htmlspecialchars($potential['merchant_name']); ?></p>
                                <span class="text-sm font-semibold text-blue-600">€<?php echo number_format($potential['avg_amount'], 2); ?></span>
                            </div>
                            <p class="text-sm text-gray-600"><?php echo $potential['frequency']; ?> charges since <?php echo date('M Y', strtotime($potential['first_seen'])); ?></p>
                            <button onclick="addSubscriptionFromPotential('<?php echo htmlspecialchars($potential['merchant_name']); ?>', <?php echo $potential['avg_amount']; ?>)" class="mt-2 text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded hover:bg-blue-200 transition-colors">
                                ➕ Add as Subscription
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Real-time Sync Status -->
    <?php if (count($syncHealthStatus) > 0): ?>
    <section class="py-6 bg-gray-50 border-t border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">🏦 Bank Sync Status</h2>
                <button onclick="window.location.href='bank/scan.php'" class="text-sm bg-green-100 text-green-700 px-3 py-1 rounded hover:bg-green-200 transition-colors">
                    🔄 Refresh Data
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($syncHealthStatus as $sync): ?>
                <div class="bg-white rounded-lg p-4 border border-gray-200">
                    <div class="flex items-center justify-between mb-2">
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($sync['account_name'] ?: $sync['provider']); ?></p>
                        <span class="text-xs px-2 py-1 rounded <?php 
                            echo $sync['sync_health'] === 'fresh' ? 'bg-green-100 text-green-700' : 
                                ($sync['sync_health'] === 'outdated' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700');
                        ?>">
                            <?php echo ucfirst($sync['sync_health']); ?>
                        </span>
                    </div>
                    <p class="text-sm text-gray-600">
                        <?php if ($sync['last_sync_at']): ?>
                            Last sync: <?php echo date('M j, H:i', strtotime($sync['last_sync_at'])); ?>
                        <?php else: ?>
                            Never synced
                        <?php endif; ?>
                    </p>
                    <p class="text-xs text-gray-500"><?php echo $sync['recent_transactions']; ?> transactions today</p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Subscriptions Section -->
    <section class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Subscription Management Header -->
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Your Subscriptions</h2>
                <p class="text-lg text-gray-600 mb-6">Manage all your recurring payments in one place</p>
                
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Your Subscriptions</h2>
                        <?php if (in_array($userPlan['plan_type'], ['monthly', 'yearly'])): ?>
                        <?php if ($bankAccountSummary['total_accounts'] > 0): ?>
                        <div class="mt-2 space-y-1">
                            <?php foreach ($bankAccountSummary['bank_accounts'] as $account): ?>
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center space-x-2">
                                    <span class="text-green-600">🏦 <?php echo htmlspecialchars($account['account_name']); ?></span>
                                    <span class="text-xs text-gray-500">(<?php echo ucfirst($account['provider']); ?>)</span>
                                    <?php if ($account['last_sync_at']): ?>
                                    <span class="text-xs text-blue-600" title="Last synced: <?php echo $account['last_sync_at']; ?>">
                                        ✓ <?php echo date('M j', strtotime($account['last_sync_at'])); ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="text-xs text-yellow-600" title="Never synced or sync data unavailable">
                                        ⚠️ No sync
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <button onclick="disconnectBankAccount(<?php echo $account['id']; ?>)" class="text-red-500 hover:text-red-700 text-xs">
                                    Disconnect
                                </button>
                            </div>
                            <?php endforeach; ?>
                            <div class="text-xs text-gray-600 mt-2 space-y-1">
                                <p>Monthly cost: €<?php echo number_format($bankAccountSummary['monthly_cost'], 2); ?> (€<?php echo number_format($bankAccountSummary['cost_per_account'], 2); ?> per account)</p>
                                <?php if ($lastSyncInfo): ?>
                                <p>Last scan: <?php echo date('M j, Y H:i', strtotime($lastSyncInfo['created_at'])); ?> - Found <?php echo $lastSyncInfo['subscriptions_found'] ?? 0; ?> subscriptions
                                <?php if ($lastSyncInfo['scan_duration_seconds']): ?>
                                (<?php echo $lastSyncInfo['scan_duration_seconds']; ?>s)
                                <?php endif; ?>
                                </p>
                                <?php endif; ?>
                                <?php if ($recentActivity['recent_transactions'] > 0): ?>
                                <p class="text-green-600">📊 <?php echo $recentActivity['recent_transactions']; ?> new transactions from <?php echo $recentActivity['unique_merchants']; ?> merchants this week</p>
                                <?php endif; ?>
                                <?php if ($recentActivity['newest_transaction']): ?>
                                <p>Latest transaction: <?php echo date('M j, Y', strtotime($recentActivity['newest_transaction'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php else: ?>
                        <p class="text-sm text-gray-500 mt-1">Add subscriptions manually or connect your bank</p>
                        <p class="text-xs text-gray-400 mt-1">€3/month per connected bank account</p>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="flex space-x-3">
                        <button onclick="openAddModal()" class="gradient-bg text-white px-6 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-200">
                            Add Subscription
                        </button>
                        <?php if (in_array($userPlan['plan_type'], ['monthly', 'yearly'])): ?>
                        <button onclick="startBankScan()" class="bg-blue-600 text-white px-4 py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                            <?php echo $bankAccountSummary['total_accounts'] > 0 ? 'Connect Another Bank' : 'Connect Bank'; ?>
                        </button>
                        <?php if ($bankAccountSummary['total_accounts'] > 0): ?>
                        <button onclick="disconnectAllBanks()" class="bg-red-600 text-white px-4 py-3 rounded-lg font-medium hover:bg-red-700 transition-colors text-sm">
                            Disconnect All
                        </button>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-medium text-gray-900">Filters & Display Options</h3>
                        <button onclick="resetFilters()" class="text-sm text-blue-600 hover:text-blue-800">Reset All</button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" id="includeInactive" class="rounded border-gray-300 text-blue-600" onchange="updateFilters()">
                                <span class="ml-2 text-sm text-gray-700">Include inactive</span>
                            </label>
                        </div>
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" id="excludeFromTotals" class="rounded border-gray-300 text-red-600" onchange="updateFilters()">
                                <span class="ml-2 text-sm text-gray-700">Exclude selected</span>
                            </label>
                        </div>
                        <div>
                            <select id="timeRange" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" onchange="updateFilters()">
                                <option value="6">Last 6 months</option>
                                <option value="12" selected>Last 12 months</option>
                                <option value="24">Last 24 months</option>
                            </select>
                        </div>
                        <div>
                            <select id="categoryFilterMain" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" onchange="updateFilters()">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['name']); ?>">
                                    <?php echo $category['icon']; ?> <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

            <!-- Charts Section -->
            <?php if (!empty($subscriptions)): ?>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Monthly Spending Chart -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Monthly Spending by Category</h3>
                    <canvas id="categoryChart" width="400" height="200"></canvas>
                </div>
                
                <!-- Spending Trend Chart -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Spending Trend</h3>
                    <canvas id="trendChart" width="400" height="200"></canvas>
                </div>
            </div>
            <?php endif; ?>

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
                <div class="subscription-card bg-white rounded-xl shadow-sm border border-gray-100 p-6 relative hover:shadow-md transition-shadow duration-200" 
                     data-category="<?php echo htmlspecialchars($subscription['category']); ?>">
                    <?php if (!empty($subscription['source']) && $subscription['source'] === 'bank'): ?>
                        <span class="absolute top-2 right-2 bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                            <i class="fas fa-university mr-1"></i> Bank
                        </span>
                    <?php endif; ?>
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
                                <h4 class="font-semibold text-gray-900 text-lg"><?php echo htmlspecialchars($subscription['name'] ?: $subscription['merchant_name'] ?: 'Unknown'); ?></h4>
                                <div class="flex items-center space-x-2">
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($subscription['category'] ?? 'Other'); ?></p>
                                    <?php if ($subscription['provider'] && $subscription['provider'] !== 'stripe'): ?>
                                    <span class="text-xs text-blue-600">• <?php echo ucfirst($subscription['provider']); ?> Bank</span>
                                    <?php endif; ?>
                                    <?php if ($subscription['confidence']): ?>
                                    <span class="text-xs text-gray-400" title="Detection confidence: <?php echo $subscription['confidence']; ?>%">
                                        <?php echo $subscription['confidence']; ?>% confidence
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <label class="flex items-center" title="Exclude from totals">
                                <input type="checkbox" class="exclude-checkbox rounded border-gray-300 text-red-600" data-id="<?php echo $subscription['id']; ?>" onchange="toggleExclude(<?php echo $subscription['id']; ?>)">
                                <span class="ml-1 text-xs text-gray-500">Exclude</span>
                            </label>
                            <button onclick="openEditModal(<?php echo $subscription['id']; ?>, '<?php echo htmlspecialchars($subscription['name'] ?: $subscription['merchant_name'] ?: 'Unknown', ENT_QUOTES); ?>', <?php echo $subscription['cost'] ?? $subscription['amount'] ?? 0; ?>, '<?php echo $subscription['billing_cycle'] ?? 'monthly'; ?>', '<?php echo $subscription['category'] ?? 'Other'; ?>', <?php echo $subscription['is_active'] ? 1 : 0; ?>)" class="text-gray-400 hover:text-blue-600 transition-colors" title="Edit">
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
                            €<?php echo number_format($subscription['cost'] ?? $subscription['amount'] ?? 0, 2); ?>
                        </div>
                        <div class="text-sm text-gray-500">
                            per <?php echo $subscription['billing_cycle'] ?? 'monthly'; ?>
                        </div>
                        <?php if ($subscription['last_charge_date']): ?>
                        <div class="text-xs text-gray-400 mt-1">
                            Last charged: <?php echo date('M j, Y', strtotime($subscription['last_charge_date'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($subscription['next_billing_date']): ?>
                    <div class="flex items-center justify-between text-sm mb-4">
                        <span class="text-gray-500">Next payment:</span>
                        <span class="font-medium text-gray-900">
                            <?php echo date('M j, Y', strtotime($subscription['next_billing_date'])); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo ($subscription['is_active'] ?? ($subscription['status'] === 'active')) ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo ($subscription['is_active'] ?? ($subscription['status'] === 'active')) ? 'Active' : 'Inactive'; ?>
                        </span>
                        <?php if ($subscription['provider'] && $subscription['provider'] !== 'stripe'): ?>
                        <span class="text-xs text-blue-600" title="Bank detected subscription">
                            🏦 <?php echo ucfirst($subscription['provider']); ?>
                        </span>
                        <?php endif; ?>
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

    <!-- Edit Subscription Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full mx-4 p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Edit Subscription</h2>
            
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="update_subscription">
                <input type="hidden" name="subscription_id" id="edit_subscription_id">
                
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label for="edit_name" class="block text-sm font-medium text-gray-700 mb-1">Service Name</label>
                        <input type="text" id="edit_name" name="name" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="edit_cost" class="block text-sm font-medium text-gray-700 mb-1">Cost (€)</label>
                            <input type="number" id="edit_cost" name="cost" step="0.01" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="edit_billing_cycle" class="block text-sm font-medium text-gray-700 mb-1">Billing</label>
                            <select id="edit_billing_cycle" name="billing_cycle" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                                <option value="weekly">Weekly</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label for="edit_category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select id="edit_category" name="category" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['name']); ?>">
                                <?php echo $category['icon']; ?> <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" id="edit_is_active" name="is_active" class="rounded border-gray-300 text-green-600 shadow-sm focus:border-green-300 focus:ring focus:ring-green-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">Active subscription</span>
                        </label>
                    </div>
                </div>
                
                <div class="flex space-x-3 mt-6">
                    <button type="button" onclick="closeEditModal()" class="flex-1 bg-gray-200 text-gray-800 px-4 py-2 rounded-lg font-medium hover:bg-gray-300 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                        Update Subscription
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

        function openEditModal(id, name, cost, billing_cycle, category, is_active) {
            document.getElementById('edit_subscription_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_cost').value = cost;
            document.getElementById('edit_billing_cycle').value = billing_cycle;
            document.getElementById('edit_category').value = category;
            document.getElementById('edit_is_active').checked = is_active == 1;
            
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('flex');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editModal').classList.remove('flex');
        }

        function startBankScan() {
            // Pro users (monthly/yearly) have unlimited bank scan access
            <?php if (in_array($userPlan['plan_type'], ['monthly', 'yearly']) && $userPlan['is_paid']): ?>
            // Redirect to bank integration with unlimited access
            window.location.href = 'bank/scan.php?plan=<?php echo $userPlan['plan_type']; ?>';
            <?php else: ?>
            alert('Bank scan not available with your current plan. Please upgrade.');
            window.location.href = 'upgrade.php';
            <?php endif; ?>
        }

        // Close modals when clicking outside
        document.getElementById('addModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddModal();
            }
        });
        
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
        
        // Global variables for charts and data
        let categoryChart, trendChart;
        let subscriptionsData = <?php echo json_encode($subscriptions); ?>;
        let excludedSubscriptions = JSON.parse(localStorage.getItem('excludedSubscriptions') || '[]');
        
        // Initialize charts if we have subscription data
        <?php if (!empty($subscriptions)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
            loadExcludedSubscriptions();
            updateFilters();
        });
        <?php endif; ?>
        
        function initializeCharts() {
            const filteredData = getFilteredData();
            
            // Category spending chart
            categoryChart = new Chart(document.getElementById('categoryChart'), {
                type: 'doughnut',
                data: filteredData.categoryData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            
            // Realistic trend chart based on actual subscription start dates
            trendChart = new Chart(document.getElementById('trendChart'), {
                type: 'line',
                data: filteredData.trendData,
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '€' + value.toFixed(2);
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
        
        function getFilteredData() {
            const includeInactive = document.getElementById('includeInactive')?.checked || false;
            const excludeFromTotals = document.getElementById('excludeFromTotals')?.checked || false;
            const timeRange = parseInt(document.getElementById('timeRange')?.value || '12');
            const categoryFilter = document.getElementById('categoryFilterMain')?.value || '';
            
            // Filter subscriptions
            let filtered = subscriptionsData.filter(sub => {
                if (!includeInactive && sub.is_active != 1) return false;
                if (excludeFromTotals && excludedSubscriptions.includes(sub.id.toString())) return false;
                if (categoryFilter && sub.category !== categoryFilter) return false;
                return true;
            });
            
            // Calculate category totals
            const categoryTotals = {};
            filtered.forEach(sub => {
                const category = sub.category || 'Other';
                let monthlyCost = 0;
                // Support both column names for compatibility
                const amount = parseFloat(sub.amount || sub.cost || 0);
                switch (sub.billing_cycle) {
                    case 'monthly': monthlyCost = amount; break;
                    case 'yearly': monthlyCost = amount / 12; break;
                    case 'weekly': monthlyCost = amount * 4.33; break;
                }
                categoryTotals[category] = (categoryTotals[category] || 0) + monthlyCost;
            });
            
            // Generate realistic trend data
            const months = [];
            const trendValues = [];
            const currentDate = new Date();
            
            for (let i = timeRange - 1; i >= 0; i--) {
                const date = new Date(currentDate.getFullYear(), currentDate.getMonth() - i, 1);
                months.push(date.toLocaleDateString('en-US', { month: 'short', year: '2-digit' }));
                
                // Calculate realistic spending for this month based on subscription lifecycle
                let monthlySpending = 0;
                filtered.forEach(sub => {
                    // Use created_at if available, otherwise estimate based on current date
                    let subStartDate;
                    if (sub.created_at && sub.created_at !== '0000-00-00 00:00:00') {
                        subStartDate = new Date(sub.created_at);
                    } else {
                        // Estimate start date: assume subscription started 6 months ago on average
                        subStartDate = new Date(currentDate.getFullYear(), currentDate.getMonth() - 6, 1);
                    }
                    
                    // Only include subscription cost if it was active during this month
                    if (subStartDate <= date) {
                        let monthlyCost = 0;
                        // Support both column names for compatibility
                        const amount = parseFloat(sub.amount || sub.cost || 0);
                        switch (sub.billing_cycle) {
                            case 'monthly': monthlyCost = amount; break;
                            case 'yearly': monthlyCost = amount / 12; break;
                            case 'weekly': monthlyCost = amount * 4.33; break;
                            default: monthlyCost = amount; break;
                        }
                        monthlySpending += monthlyCost;
                    }
                });
                
                // Ensure we don't have zero values - use current total if no historical data
                if (monthlySpending === 0 && i === 0) {
                    // For current month, use actual current totals
                    monthlySpending = Object.values(categoryTotals).reduce((a, b) => a + b, 0);
                }
                
                trendValues.push(Math.round(monthlySpending * 100) / 100); // Round to 2 decimals
            }
            
            return {
                categoryData: {
                    labels: Object.keys(categoryTotals),
                    datasets: [{
                        data: Object.values(categoryTotals),
                        backgroundColor: [
                            '#ef4444', '#10b981', '#3b82f6', '#6b7280', 
                            '#f59e0b', '#f97316', '#ec4899', '#8b5cf6'
                        ]
                    }]
                },
                trendData: {
                    labels: months,
                    datasets: [{
                        label: 'Monthly Spending',
                        data: trendValues,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                }
            };
        }
        
        function updateFilters() {
            if (categoryChart && trendChart) {
                const filteredData = getFilteredData();
                
                categoryChart.data = filteredData.categoryData;
                categoryChart.update();
                
                trendChart.data = filteredData.trendData;
                trendChart.update();
                
                updateTotalDisplays();
            }
        }
        
        function updateTotalDisplays() {
            const filteredData = getFilteredData();
            const monthlyTotal = filteredData.categoryData.datasets[0].data.reduce((a, b) => a + b, 0);
            const yearlyTotal = monthlyTotal * 12;
            const activeCount = getActiveSubscriptionCount();
            const totalCount = getTotalSubscriptionCount();
            
            // Update all stat cards
            const monthlyElement = document.querySelector('[data-stat="monthly"]');
            const yearlyElement = document.querySelector('[data-stat="yearly"]');
            const totalElement = document.querySelector('[data-stat="total"]');
            const activeElement = document.querySelector('p:contains("Active Subscriptions")');
            
            if (monthlyElement) monthlyElement.textContent = '€' + monthlyTotal.toFixed(2);
            if (yearlyElement) yearlyElement.textContent = '€' + yearlyTotal.toFixed(2);
            if (totalElement) totalElement.textContent = totalCount;
            
            // Update active subscriptions count
            const activeStatElement = document.querySelector('.stat-card:first-child .text-2xl');
            if (activeStatElement) activeStatElement.textContent = activeCount;
            
            // Update subscription cards visibility based on exclusions
            updateSubscriptionCardsVisibility();
        }
        
        function getActiveSubscriptionCount() {
            const includeInactive = document.getElementById('includeInactive')?.checked || false;
            const excludeFromTotals = document.getElementById('excludeFromTotals')?.checked || false;
            
            return subscriptionsData.filter(sub => {
                if (!includeInactive && sub.is_active != 1) return false;
                if (excludeFromTotals && excludedSubscriptions.includes(sub.id.toString())) return false;
                return sub.is_active == 1;
            }).length;
        }
        
        function getTotalSubscriptionCount() {
            const includeInactive = document.getElementById('includeInactive')?.checked || false;
            const excludeFromTotals = document.getElementById('excludeFromTotals')?.checked || false;
            
            return subscriptionsData.filter(sub => {
                if (!includeInactive && sub.is_active != 1) return false;
                if (excludeFromTotals && excludedSubscriptions.includes(sub.id.toString())) return false;
                return true;
            }).length;
        }
        
        function updateSubscriptionCardsVisibility() {
            const excludeFromTotals = document.getElementById('excludeFromTotals')?.checked || false;
            
            document.querySelectorAll('.subscription-card').forEach(card => {
                const subscriptionId = card.querySelector('.exclude-checkbox')?.getAttribute('data-id');
                
                if (excludeFromTotals && excludedSubscriptions.includes(subscriptionId)) {
                    card.style.opacity = '0.5';
                    card.style.border = '2px dashed #ef4444';
                } else {
                    card.style.opacity = '1';
                    card.style.border = '1px solid #e5e7eb';
                }
            });
        }
        
        function toggleExclude(subscriptionId) {
            const index = excludedSubscriptions.indexOf(subscriptionId.toString());
            if (index > -1) {
                excludedSubscriptions.splice(index, 1);
            } else {
                excludedSubscriptions.push(subscriptionId.toString());
            }
            localStorage.setItem('excludedSubscriptions', JSON.stringify(excludedSubscriptions));
            updateFilters();
        }
        
        function loadExcludedSubscriptions() {
            excludedSubscriptions.forEach(id => {
                const checkbox = document.querySelector(`[data-id="${id}"]`);
                if (checkbox) checkbox.checked = true;
            });
        }
        
        function resetFilters() {
            document.getElementById('includeInactive').checked = false;
            document.getElementById('excludeFromTotals').checked = false;
            document.getElementById('timeRange').value = '12';
            document.getElementById('categoryFilterMain').value = '';
            excludedSubscriptions = [];
            localStorage.removeItem('excludedSubscriptions');
            document.querySelectorAll('.exclude-checkbox').forEach(cb => cb.checked = false);
            updateFilters();
        }
        
        // Update subscription card visibility based on filters
        function updateSubscriptionVisibility() {
            const categoryFilter = document.getElementById('categoryFilterMain')?.value || '';
            const includeInactive = document.getElementById('includeInactive')?.checked || false;
            
            document.querySelectorAll('.subscription-card').forEach(card => {
                const category = card.getAttribute('data-category');
                const isActive = card.querySelector('.bg-green-100') !== null; // Check if has active badge
                
                let shouldShow = true;
                
                // Category filter
                if (categoryFilter && category !== categoryFilter) {
                    shouldShow = false;
                }
                
                // Inactive filter
                if (!includeInactive && !isActive) {
                    shouldShow = false;
                }
                
                card.style.display = shouldShow ? 'block' : 'none';
            });
        }
        
        function disconnectBankAccount(bankAccountId) {
            if (confirm('Are you sure you want to disconnect this bank account? This will stop monitoring for this account.')) {
                fetch('api/bank-accounts.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ bank_account_id: bankAccountId })
                }).then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.error || 'Error disconnecting bank account. Please try again.');
                    }
                }).catch(err => {
                    alert('Error disconnecting bank account. Please try again.');
                });
            }
        }
        
        function disconnectAllBanks() {
            if (confirm('Are you sure you want to disconnect ALL bank accounts? This will stop all automatic subscription monitoring.')) {
                fetch('bank/disconnect.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                }).then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.error || 'Error disconnecting bank accounts. Please try again.');
                    }
                }).catch(err => {
                    alert('Error disconnecting bank accounts. Please try again.');
                });
            }
        }
        
        // Legacy function for backward compatibility
        function disconnectBank() {
            disconnectAllBanks();
        }
        
        // ENHANCED DASHBOARD FUNCTIONS
        
        // Add subscription from potential detection
        function addSubscriptionFromPotential(merchantName, avgAmount) {
            // Pre-fill the add subscription form
            document.getElementById('subscriptionName').value = merchantName;
            document.getElementById('subscriptionCost').value = avgAmount.toFixed(2);
            document.getElementById('subscriptionCycle').value = 'monthly';
            
            // Auto-categorize based on merchant name
            const category = autoCategorizeMerchant(merchantName);
            document.getElementById('subscriptionCategory').value = category;
            
            // Show the add subscription modal
            showAddSubscriptionModal();
        }
        
        // Auto-categorize merchant based on name patterns
        function autoCategorizeMerchant(merchantName) {
            const name = merchantName.toLowerCase();
            if (name.includes('netflix') || name.includes('disney') || name.includes('hbo') || name.includes('prime')) return 'Streaming';
            if (name.includes('spotify') || name.includes('apple music') || name.includes('youtube music')) return 'Music';
            if (name.includes('adobe') || name.includes('microsoft') || name.includes('google')) return 'Software';
            if (name.includes('gym') || name.includes('fitness') || name.includes('sport')) return 'Fitness';
            if (name.includes('news') || name.includes('times') || name.includes('guardian')) return 'News';
            return 'Other';
        }
        
        // Enhanced edit subscription with cancellation assistant integration
        function editSubscription(subscriptionId) {
            // Get subscription data
            fetch(`api/subscriptions.php?id=${subscriptionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const sub = data.subscription;
                        
                        // Fill edit form
                        document.getElementById('editSubscriptionId').value = sub.id;
                        document.getElementById('editSubscriptionName').value = sub.name;
                        document.getElementById('editSubscriptionCost').value = sub.cost;
                        document.getElementById('editSubscriptionCycle').value = sub.billing_cycle;
                        document.getElementById('editSubscriptionCategory').value = sub.category || 'Other';
                        document.getElementById('editSubscriptionActive').checked = sub.is_active == 1;
                        
                        // Add cancellation assistant link if available
                        const cancelContainer = document.getElementById('cancellationAssistant');
                        if (cancelContainer) {
                            cancelContainer.innerHTML = `
                                <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                                    <h4 class="text-sm font-semibold text-red-900 mb-2">🚫 Need to Cancel?</h4>
                                    <a href="unsubscribe/index.php?search=${encodeURIComponent(sub.name)}" 
                                       class="text-sm text-red-700 hover:text-red-900 underline" target="_blank">
                                        View step-by-step cancellation guide
                                    </a>
                                </div>
                            `;
                        }
                        
                        showEditSubscriptionModal();
                    }
                })
                .catch(err => {
                    console.error('Error loading subscription:', err);
                    alert('Error loading subscription details.');
                });
        }
        
        // Show insights panel
        function toggleInsightsPanel() {
            const panel = document.getElementById('insightsPanel');
            if (panel) {
                panel.classList.toggle('hidden');
            }
        }
        
        // Mark anomaly as reviewed
        function markAnomalyReviewed(anomalyId) {
            fetch('api/anomalies.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    action: 'mark_reviewed', 
                    anomaly_id: anomalyId 
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error updating anomaly status.');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Error updating anomaly status.');
            });
        }
        
        // Trigger manual bank sync
        function triggerBankSync(provider = null) {
            const button = event.target;
            button.disabled = true;
            button.textContent = '🔄 Syncing...';
            
            const url = provider ? `bank/scan.php?provider=${provider}` : 'bank/scan.php';
            
            fetch(url, { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        alert(data.error || 'Sync failed. Please try again.');
                        button.disabled = false;
                        button.textContent = '🔄 Refresh Data';
                    }
                })
                .catch(err => {
                    console.error('Sync error:', err);
                    alert('Sync failed. Please try again.');
                    button.disabled = false;
                    button.textContent = '🔄 Refresh Data';
                });
        }
        
        // Enhanced subscription management with multi-account aggregation
        function showMultiAccountSummary() {
            fetch('api/multi-account-summary.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const modal = document.createElement('div');
                        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
                        modal.innerHTML = `
                            <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-96 overflow-y-auto">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold">🏦 Multi-Account Summary</h3>
                                    <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700">
                                        ✕
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    ${data.accounts.map(account => `
                                        <div class="border border-gray-200 rounded-lg p-4">
                                            <h4 class="font-semibold">${account.account_name}</h4>
                                            <p class="text-sm text-gray-600">${account.provider}</p>
                                            <p class="text-sm">Subscriptions: ${account.subscription_count}</p>
                                            <p class="text-sm">Monthly Total: €${account.monthly_total}</p>
                                            <p class="text-xs text-gray-500">Last sync: ${account.last_sync_at || 'Never'}</p>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        `;
                        document.body.appendChild(modal);
                    }
                })
                .catch(err => {
                    console.error('Error loading multi-account summary:', err);
                    alert('Error loading account summary.');
                });
        }
    </script>
</body>
</html>
