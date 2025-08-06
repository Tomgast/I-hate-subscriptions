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
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="flex items-center">
                        <i data-lucide="credit-card" class="w-8 h-8 text-blue-600 mr-3"></i>
                        <span class="text-xl font-bold text-gray-900">CashControl</span>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-700">Welcome, <?php echo htmlspecialchars($userName); ?></span>
                    <?php if (!$isPaid): ?>
                        <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full">Free Plan</span>
                    <?php else: ?>
                        <span class="px-2 py-1 bg-blue-100 text-blue-600 text-xs rounded-full">Pro Plan</span>
                    <?php endif; ?>
                    <a href="auth/logout.php" class="text-gray-500 hover:text-gray-700">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Welcome Section -->
        <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i data-lucide="user-circle" class="w-12 h-12 text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-2xl font-bold text-gray-900">Welcome back, <?php echo htmlspecialchars($userName); ?>!</h1>
                        <p class="text-gray-600">Manage your subscriptions and track your spending</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i data-lucide="credit-card" class="w-8 h-8 text-blue-600"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Active Subscriptions</dt>
                                <dd class="text-lg font-medium text-gray-900">0</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i data-lucide="euro" class="w-8 h-8 text-green-600"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Monthly Total</dt>
                                <dd class="text-lg font-medium text-gray-900">€0.00</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i data-lucide="calendar" class="w-8 h-8 text-orange-600"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Next Payment</dt>
                                <dd class="text-lg font-medium text-gray-900">None</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <button class="flex items-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition duration-200">
                        <i data-lucide="plus" class="w-6 h-6 text-blue-600 mr-3"></i>
                        <span class="text-sm font-medium text-gray-900">Add Subscription</span>
                    </button>
                    
                    <?php if (!$isPaid): ?>
                    <button class="flex items-center p-4 border border-blue-300 bg-blue-50 rounded-lg hover:bg-blue-100 transition duration-200">
                        <i data-lucide="star" class="w-6 h-6 text-blue-600 mr-3"></i>
                        <span class="text-sm font-medium text-blue-900">Upgrade to Pro</span>
                    </button>
                    <?php endif; ?>
                    
                    <button class="flex items-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition duration-200">
                        <i data-lucide="settings" class="w-6 h-6 text-gray-600 mr-3"></i>
                        <span class="text-sm font-medium text-gray-900">Settings</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Subscriptions List -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Your Subscriptions</h3>
                <div class="text-center py-12">
                    <i data-lucide="inbox" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
                    <h3 class="text-sm font-medium text-gray-900 mb-2">No subscriptions yet</h3>
                    <p class="text-sm text-gray-500 mb-4">Get started by adding your first subscription</p>
                    <button class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                        Add Your First Subscription
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
