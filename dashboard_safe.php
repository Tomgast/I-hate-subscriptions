<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
    header('Location: auth/signin.php');
    exit;
}

$userName = $_SESSION['user_name'] ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';
$isPaid = $_SESSION['is_paid'] ?? false;
$userId = $_SESSION['user_id'];

// Initialize variables with defaults
$subscriptions = [];
$stats = [
    'total_active' => 0,
    'monthly_total' => 0,
    'yearly_total' => 0,
    'next_payment' => null,
    'category_breakdown' => []
];
$upcomingPayments = [];
$categories = [];
$error = null;

try {
    require_once 'config/db_config.php';
    $pdo = getDBConnection();
    
    // Simple query to test database
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM subscriptions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    
    if ($result) {
        // Database is working, try to load subscription manager
        try {
            require_once 'includes/subscription_manager.php';
            $subscriptionManager = new SubscriptionManager();
            
            $subscriptions = $subscriptionManager->getUserSubscriptions($userId);
            $stats = $subscriptionManager->getSubscriptionStats($userId);
            $upcomingPayments = $subscriptionManager->getUpcomingPayments($userId, 7);
            $categories = $subscriptionManager->getCategories();
        } catch (Exception $e) {
            $error = "Subscription manager error: " . $e->getMessage();
        }
    }
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
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
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-content">
            <div class="navbar-brand">
                <img src="assets/images/logo.svg" alt="CashControl Logo" class="h-8 w-auto mr-3">
                <span>CashControl</span>
            </div>
            
            <div class="flex items-center space-x-4">
                <span class="text-gray-600">Welcome, <?php echo htmlspecialchars($userName); ?></span>
                <?php if ($isPaid): ?>
                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm font-medium">Pro</span>
                <?php else: ?>
                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm font-medium">Free</span>
                <?php endif; ?>
                <a href="auth/logout.php" class="btn btn-secondary btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-8">
        <?php if ($error): ?>
        <!-- Error Display -->
        <div class="card mb-8">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <span class="text-4xl mr-4">⚠️</span>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">System Setup Required</h2>
                        <p class="text-gray-600">We're setting up your dashboard. This may take a moment.</p>
                    </div>
                </div>
                
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                    <p class="text-sm text-yellow-800">
                        <strong>Technical Details:</strong> <?php echo htmlspecialchars($error); ?>
                    </p>
                </div>
                
                <div class="flex space-x-4">
                    <a href="test_dashboard.php" class="btn btn-primary">Run Diagnostics</a>
                    <a href="dashboard.php" class="btn btn-secondary">Retry Dashboard</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Welcome Section -->
        <div class="card mb-8">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 mb-2">
                            Welcome back, <?php echo htmlspecialchars($userName); ?>! 👋
                        </h1>
                        <p class="text-gray-600">
                            <?php if ($isPaid): ?>
                                You're on the Pro plan with full access to all features.
                            <?php else: ?>
                                You're on the Free plan. <a href="upgrade.php" class="text-blue-600 hover:text-blue-700 font-medium">Upgrade to Pro</a> for bank integration and smart reminders.
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="text-6xl">💰</div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="card">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Active Subscriptions</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_active']; ?></p>
                        </div>
                        <span class="text-3xl">📱</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Monthly Spending</p>
                            <p class="text-2xl font-bold text-gray-900">€<?php echo number_format($stats['monthly_total'], 2); ?></p>
                        </div>
                        <span class="text-3xl">💳</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Yearly Total</p>
                            <p class="text-2xl font-bold text-gray-900">€<?php echo number_format($stats['yearly_total'], 2); ?></p>
                        </div>
                        <span class="text-3xl">📊</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Account Type</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $isPaid ? 'Pro' : 'Free'; ?></p>
                        </div>
                        <span class="text-3xl"><?php echo $isPaid ? '💎' : '🆓'; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="card">
                <div class="p-6 text-center">
                    <div class="text-4xl mb-4">➕</div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Add Subscription</h3>
                    <p class="text-gray-600 mb-4">Track a new subscription service</p>
                    <button onclick="alert('Add subscription feature coming soon!')" class="btn btn-primary w-full">Add New</button>
                </div>
            </div>

            <div class="card">
                <div class="p-6 text-center">
                    <div class="text-4xl mb-4">📊</div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">View Analytics</h3>
                    <p class="text-gray-600 mb-4">See detailed spending insights</p>
                    <a href="analytics.php" class="btn btn-secondary w-full">View Analytics</a>
                </div>
            </div>

            <div class="card">
                <div class="p-6 text-center">
                    <div class="text-4xl mb-4"><?php echo $isPaid ? '🏦' : '💎'; ?></div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">
                        <?php echo $isPaid ? 'Bank Integration' : 'Upgrade to Pro'; ?>
                    </h3>
                    <p class="text-gray-600 mb-4">
                        <?php echo $isPaid ? 'Connect your bank account' : 'Get bank integration & more'; ?>
                    </p>
                    <?php if ($isPaid): ?>
                        <a href="bank/connect.php" class="btn btn-primary w-full">Connect Bank</a>
                    <?php else: ?>
                        <a href="upgrade.php" class="btn btn-primary w-full">Upgrade Now</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Subscriptions -->
        <?php if (!empty($subscriptions)): ?>
        <div class="card">
            <div class="p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Your Subscriptions</h2>
                <div class="space-y-4">
                    <?php foreach (array_slice($subscriptions, 0, 5) as $subscription): ?>
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center text-white font-bold">
                                <?php echo strtoupper(substr($subscription['name'], 0, 1)); ?>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($subscription['name']); ?></h3>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($subscription['category'] ?? 'Other'); ?> • <?php echo ucfirst($subscription['billing_cycle']); ?></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-gray-900">€<?php echo number_format($subscription['cost'], 2); ?></p>
                            <p class="text-sm text-gray-600">Next: <?php echo date('M j', strtotime($subscription['next_payment_date'])); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($subscriptions) > 5): ?>
                <div class="mt-4 text-center">
                    <button onclick="alert('Full subscription list coming soon!')" class="text-blue-600 hover:text-blue-700 font-medium">
                        View All <?php echo count($subscriptions); ?> Subscriptions
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="p-8 text-center">
                <div class="text-6xl mb-4">🎯</div>
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Ready to Start Tracking?</h2>
                <p class="text-gray-600 mb-6">Add your first subscription to see your spending insights.</p>
                <button onclick="alert('Add subscription feature coming soon!')" class="btn btn-primary btn-lg">Add Your First Subscription</button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
