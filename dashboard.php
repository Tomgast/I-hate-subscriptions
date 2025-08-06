<?php
session_start();
require_once 'config/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
    header('Location: auth/signin.php');
    exit;
}

// Verify session token
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT user_id FROM user_sessions WHERE session_token = ? AND expires_at > NOW()");
    $stmt->execute([$_SESSION['session_token']]);
    
    if (!$stmt->fetch()) {
        // Invalid or expired session
        session_destroy();
        header('Location: auth/signin.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Session verification error: " . $e->getMessage());
    header('Location: auth/signin.php');
    exit;
}

$userName = $_SESSION['user_name'] ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';
$isPaid = $_SESSION['is_paid'] ?? false;
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
                <span class="icon-credit-card mr-3" style="font-size: 2rem;"></span>
                <span>CashControl</span>
            </div>
            
            <div class="navbar-user">
                <span class="text-sm text-gray-700">Welcome, <?php echo htmlspecialchars($userName); ?></span>
                <?php if (!$isPaid): ?>
                    <span class="badge badge-gray">Free Plan</span>
                <?php else: ?>
                    <span class="badge badge-blue">Pro Plan</span>
                <?php endif; ?>
                <a href="auth/logout.php" class="text-gray-500" style="text-decoration: none;">
                    <span class="icon-logout"></span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-6">
        <!-- Welcome Section -->
        <div class="card mb-6">
            <div class="p-5">
                <div class="flex items-center">
                    <div style="flex-shrink: 0;">
                        <span class="icon-user" style="font-size: 3rem; color: #2563eb;"></span>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-2xl font-bold text-gray-900">Welcome back, <?php echo htmlspecialchars($userName); ?>!</h1>
                        <p class="text-gray-600">Manage your subscriptions and track your spending</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-3 gap-6 mb-6">
            <div class="card">
                <div class="stat-card">
                    <div class="flex items-center">
                        <div style="flex-shrink: 0;">
                            <span class="icon-credit-card stat-icon" style="color: #2563eb;"></span>
                        </div>
                        <div class="ml-5" style="flex: 1;">
                            <div class="text-sm font-medium text-gray-500">Active Subscriptions</div>
                            <div class="text-lg font-medium text-gray-900">0</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="stat-card">
                    <div class="flex items-center">
                        <div style="flex-shrink: 0;">
                            <span class="icon-euro stat-icon" style="color: #059669;"></span>
                        </div>
                        <div class="ml-5" style="flex: 1;">
                            <div class="text-sm font-medium text-gray-500">Monthly Total</div>
                            <div class="text-lg font-medium text-gray-900">€0.00</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="stat-card">
                    <div class="flex items-center">
                        <div style="flex-shrink: 0;">
                            <span class="icon-calendar stat-icon" style="color: #ea580c;"></span>
                        </div>
                        <div class="ml-5" style="flex: 1;">
                            <div class="text-sm font-medium text-gray-500">Next Payment</div>
                            <div class="text-lg font-medium text-gray-900">None</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mb-6">
            <div class="p-5">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-3 gap-4">
                    <button class="flex items-center p-4 border border-gray-300 rounded-lg" style="background: white; cursor: pointer;">
                        <span class="icon-plus mr-3" style="color: #2563eb;"></span>
                        <span class="text-sm font-medium text-gray-900">Add Subscription</span>
                    </button>
                    
                    <?php if (!$isPaid): ?>
                    <button class="flex items-center p-4 border border-blue-300 bg-blue-50 rounded-lg" style="cursor: pointer;">
                        <span class="icon-star mr-3" style="color: #2563eb;"></span>
                        <span class="text-sm font-medium text-blue-900">Upgrade to Pro</span>
                    </button>
                    <?php endif; ?>
                    
                    <button class="flex items-center p-4 border border-gray-300 rounded-lg" style="background: white; cursor: pointer;">
                        <span class="icon-settings mr-3" style="color: #4b5563;"></span>
                        <span class="text-sm font-medium text-gray-900">Settings</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Subscriptions List -->
        <div class="card">
            <div class="p-5">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Your Subscriptions</h3>
                <div class="text-center py-12">
                    <span class="icon-inbox" style="font-size: 3rem; color: #9ca3af; display: block; margin: 0 auto 1rem;"></span>
                    <h3 class="text-sm font-medium text-gray-900 mb-2">No subscriptions yet</h3>
                    <p class="text-sm text-gray-500 mb-4">Get started by adding your first subscription</p>
                    <button class="btn btn-primary">
                        <span class="icon-plus mr-2"></span>
                        Add Your First Subscription
                    </button>
                </div>
            </div>
        </div>
    </div>


</body>
</html>
