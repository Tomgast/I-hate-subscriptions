<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/signin.php');
    exit;
}

$userName = $_SESSION['user_name'] ?? 'User';
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
        <!-- Welcome Section -->
        <div class="card mb-8">
            <div class="p-8 text-center">
                <div class="text-6xl mb-4">🎉</div>
                <h1 class="text-3xl font-bold text-gray-900 mb-4">
                    Welcome to CashControl, <?php echo htmlspecialchars($userName); ?>!
                </h1>
                <p class="text-lg text-gray-600 mb-6">
                    Your subscription tracking dashboard is ready. 
                    <?php if ($isPaid): ?>
                        You have Pro access with all features unlocked!
                    <?php else: ?>
                        You're on the Free plan. <a href="upgrade.php" class="text-blue-600 hover:text-blue-700 font-medium">Upgrade to Pro</a> for advanced features.
                    <?php endif; ?>
                </p>
                
                <div class="text-sm text-gray-500 bg-green-50 border border-green-200 rounded-lg p-4">
                    ✅ <strong>Login successful!</strong> Your dashboard is working properly.
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="card">
                <div class="p-6 text-center">
                    <div class="text-4xl mb-4">📊</div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Analytics</h3>
                    <p class="text-gray-600 mb-4">View your subscription insights</p>
                    <a href="analytics.php" class="btn btn-primary w-full">View Analytics</a>
                </div>
            </div>

            <div class="card">
                <div class="p-6 text-center">
                    <div class="text-4xl mb-4">⚙️</div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Settings</h3>
                    <p class="text-gray-600 mb-4">Manage your account preferences</p>
                    <a href="settings.php" class="btn btn-secondary w-full">Open Settings</a>
                </div>
            </div>

            <div class="card">
                <div class="p-6 text-center">
                    <div class="text-4xl mb-4"><?php echo $isPaid ? '🏦' : '💎'; ?></div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">
                        <?php echo $isPaid ? 'Bank Integration' : 'Upgrade to Pro'; ?>
                    </h3>
                    <p class="text-gray-600 mb-4">
                        <?php echo $isPaid ? 'Connect your bank account' : 'Get advanced features'; ?>
                    </p>
                    <?php if ($isPaid): ?>
                        <a href="bank/connect.php" class="btn btn-primary w-full">Connect Bank</a>
                    <?php else: ?>
                        <a href="upgrade.php" class="btn btn-primary w-full">Upgrade Now</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Status Information -->
        <div class="card">
            <div class="p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">System Status</h2>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Authentication</span>
                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm">✅ Working</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">User Session</span>
                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm">✅ Active</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Account Type</span>
                        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm">
                            <?php echo $isPaid ? '💎 Pro' : '🆓 Free'; ?>
                        </span>
                    </div>
                </div>
                
                <div class="mt-6 pt-4 border-t border-gray-200">
                    <p class="text-sm text-gray-500">
                        <strong>Debug Info:</strong> User ID: <?php echo $_SESSION['user_id']; ?> | 
                        Session: <?php echo isset($_SESSION['session_token']) ? 'Active' : 'Missing'; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
