<?php
/**
 * STRIPE BANK SCAN PAGE
 * New bank connection and scanning using Stripe Financial Connections
 */

session_start();
require_once '../config/db_config.php';
require_once '../includes/stripe_financial_service.php';
require_once '../includes/plan_manager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$planManager = new PlanManager($pdo);
$stripeService = new StripeFinancialService($pdo);

// Check if user can access bank scan feature
if (!$planManager->canAccessFeature($userId, 'bank_scan')) {
    header('Location: ../dashboard.php?error=no_plan');
    exit;
}

$error = '';
$success = '';
$connectionStatus = $stripeService->getConnectionStatus($userId);

// Handle form submissions
if ($_POST) {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'connect_bank':
                    $result = $stripeService->createBankConnectionSession($userId);
                    
                    if ($result['success']) {
                        // Redirect to Stripe's hosted auth flow
                        header('Location: ' . $result['auth_url']);
                        exit;
                    } else {
                        $error = $result['error'];
                    }
                    break;
                    
                case 'scan_subscriptions':
                    $result = $stripeService->scanForSubscriptions($userId);
                    
                    if ($result['success']) {
                        $success = "Scan completed! Found {$result['subscriptions_found']} subscriptions.";
                        
                        // Update user's scan count
                        $stmt = $pdo->prepare("UPDATE users SET scan_count = scan_count + 1 WHERE id = ?");
                        $stmt->execute([$userId]);
                        
                        // Redirect to dashboard to see results
                        if ($result['subscriptions_found'] > 0) {
                            header('Location: ../dashboard.php?scan_success=1&found=' . $result['subscriptions_found']);
                            exit;
                        }
                    } else {
                        $error = $result['error'];
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        error_log("Error in stripe-scan.php: " . $e->getMessage());
        $error = "An unexpected error occurred. Please try again.";
    }
}

// Get user plan info
$userPlan = $planManager->getUserPlan($userId);
$bankConnections = $stripeService->getUserBankConnections($userId);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Scan - CashControl</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="../dashboard.php" class="text-xl font-bold text-blue-600">
                        <i class="fas fa-arrow-left mr-2"></i>CashControl
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">
                        Plan: <span class="font-medium text-blue-600"><?= ucfirst($userPlan['plan_type'] ?? 'none') ?></span>
                    </span>
                    <a href="../logout.php" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-university text-blue-600 mr-3"></i>
                Bank Account Scan
            </h1>
            <p class="text-gray-600">
                Connect your bank account securely to automatically detect and track your subscriptions.
            </p>
        </div>

        <!-- Error/Success Messages -->
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <i class="fas fa-exclamation-circle text-red-400 mr-3 mt-0.5"></i>
                    <div class="text-red-700"><?= htmlspecialchars($error) ?></div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <i class="fas fa-check-circle text-green-400 mr-3 mt-0.5"></i>
                    <div class="text-green-700"><?= htmlspecialchars($success) ?></div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Security Notice -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
            <div class="flex items-start">
                <i class="fas fa-shield-alt text-blue-500 mr-3 mt-1"></i>
                <div>
                    <h3 class="font-semibold text-blue-900 mb-2">🔒 Bank-Level Security</h3>
                    <p class="text-blue-800 text-sm mb-3">
                        Your bank connection is secured by Stripe Financial Connections, the same technology used by leading financial apps.
                    </p>
                    <ul class="text-blue-700 text-sm space-y-1">
                        <li>• <strong>Read-only access</strong> - We can only view transactions, never move money</li>
                        <li>• <strong>Encrypted connection</strong> - All data is encrypted in transit and at rest</li>
                        <li>• <strong>No credentials stored</strong> - Your banking passwords never touch our servers</li>
                        <li>• <strong>Revoke anytime</strong> - You can disconnect your bank at any time</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Bank Connection Status -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">
                    <i class="fas fa-link text-blue-600 mr-2"></i>
                    Connection Status
                </h2>

                <?php if ($connectionStatus['has_connections']): ?>
                    <div class="space-y-4">
                        <div class="flex items-center text-green-600">
                            <i class="fas fa-check-circle mr-2"></i>
                            <span class="font-medium">Bank Connected</span>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-600 space-y-2">
                                <div>
                                    <strong>Connected Accounts:</strong> <?= $connectionStatus['connection_count'] ?>
                                </div>
                                <div>
                                    <strong>Last Connected:</strong> 
                                    <?= date('M j, Y g:i A', strtotime($connectionStatus['last_connected'])) ?>
                                </div>
                            </div>
                        </div>

                        <!-- Connected Accounts List -->
                        <?php if (!empty($bankConnections)): ?>
                            <div class="space-y-2">
                                <h4 class="font-medium text-gray-900">Connected Accounts:</h4>
                                <?php foreach ($bankConnections as $connection): ?>
                                    <div class="flex items-center justify-between bg-gray-50 rounded-lg p-3">
                                        <div class="flex items-center">
                                            <i class="fas fa-university text-gray-400 mr-3"></i>
                                            <div>
                                                <div class="font-medium text-gray-900">
                                                    <?= htmlspecialchars($connection['account_name']) ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?= ucfirst($connection['account_type']) ?> Account
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= $connection['currency'] ?> <?= number_format($connection['balance'] / 100, 2) ?>
                                            </div>
                                            <div class="text-xs text-green-600">
                                                <i class="fas fa-circle mr-1"></i>Connected
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Reconnect Option -->
                        <form method="POST" class="mt-4">
                            <input type="hidden" name="action" value="connect_bank">
                            <button type="submit" class="w-full bg-blue-100 text-blue-700 py-2 px-4 rounded-lg hover:bg-blue-200 transition duration-200">
                                <i class="fas fa-plus mr-2"></i>
                                Connect Another Account
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="text-center py-6">
                        <i class="fas fa-university text-gray-300 text-4xl mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Bank Connected</h3>
                        <p class="text-gray-600 mb-6">
                            Connect your bank account to start detecting subscriptions automatically.
                        </p>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="connect_bank">
                            <button type="submit" class="bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition duration-200 font-medium">
                                <i class="fas fa-link mr-2"></i>
                                Connect Bank Account
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Subscription Scanning -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">
                    <i class="fas fa-search text-green-600 mr-2"></i>
                    Subscription Scan
                </h2>

                <?php if ($connectionStatus['has_connections']): ?>
                    <div class="space-y-4">
                        <p class="text-gray-600">
                            Scan your connected bank accounts to automatically detect recurring subscriptions and payments.
                        </p>

                        <!-- Scan Statistics -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-sm text-gray-600 space-y-2">
                                <div>
                                    <strong>Scans Used:</strong> <?= $userPlan['scan_count'] ?? 0 ?>
                                    <?php if (isset($userPlan['max_scans']) && $userPlan['max_scans'] > 0): ?>
                                        / <?= $userPlan['max_scans'] ?>
                                    <?php else: ?>
                                        / Unlimited
                                    <?php endif; ?>
                                </div>
                                <?php if ($userPlan['scans_remaining'] !== null): ?>
                                    <div>
                                        <strong>Remaining:</strong> 
                                        <?= $userPlan['scans_remaining'] === -1 ? 'Unlimited' : $userPlan['scans_remaining'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Scan Button -->
                        <?php if ($userPlan['can_scan']): ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="scan_subscriptions">
                                <button type="submit" class="w-full bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 transition duration-200 font-medium">
                                    <i class="fas fa-search mr-2"></i>
                                    Scan for Subscriptions
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <p class="text-gray-600 mb-4">
                                    You've reached your scan limit for this plan.
                                </p>
                                <a href="../pricing.php" class="bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition duration-200">
                                    Upgrade Plan
                                </a>
                            </div>
                        <?php endif; ?>

                        <!-- Scan Info -->
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-start">
                                <i class="fas fa-info-circle text-yellow-500 mr-2 mt-0.5"></i>
                                <div class="text-yellow-800 text-sm">
                                    <strong>How it works:</strong> We analyze your recent transactions to identify recurring payments and subscriptions. The scan looks for patterns in payment amounts, dates, and merchant names.
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-6">
                        <i class="fas fa-search text-gray-300 text-4xl mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Connect Bank First</h3>
                        <p class="text-gray-600">
                            You need to connect a bank account before you can scan for subscriptions.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- How It Works -->
        <div class="bg-white rounded-lg shadow-sm border p-6 mt-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">
                <i class="fas fa-question-circle text-purple-600 mr-2"></i>
                How Bank Scanning Works
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="bg-blue-100 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-link text-blue-600"></i>
                    </div>
                    <h3 class="font-medium text-gray-900 mb-2">1. Connect Securely</h3>
                    <p class="text-sm text-gray-600">
                        Connect your bank account using Stripe's secure Financial Connections platform.
                    </p>
                </div>
                
                <div class="text-center">
                    <div class="bg-green-100 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-search text-green-600"></i>
                    </div>
                    <h3 class="font-medium text-gray-900 mb-2">2. Analyze Transactions</h3>
                    <p class="text-sm text-gray-600">
                        Our AI analyzes your transaction history to identify recurring subscription patterns.
                    </p>
                </div>
                
                <div class="text-center">
                    <div class="bg-purple-100 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-list text-purple-600"></i>
                    </div>
                    <h3 class="font-medium text-gray-900 mb-2">3. Track & Manage</h3>
                    <p class="text-sm text-gray-600">
                        View all detected subscriptions in your dashboard and manage them easily.
                    </p>
                </div>
            </div>
        </div>

        <!-- Back to Dashboard -->
        <div class="text-center mt-8">
            <a href="../dashboard.php" class="inline-flex items-center text-blue-600 hover:text-blue-800">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Dashboard
            </a>
        </div>
    </div>

    <script>
        // Auto-refresh connection status every 30 seconds if user is on the page
        let refreshInterval;
        
        function startRefresh() {
            refreshInterval = setInterval(() => {
                // Only refresh if no forms are being submitted
                if (!document.querySelector('form button[disabled]')) {
                    location.reload();
                }
            }, 30000);
        }
        
        // Start refresh when page loads
        document.addEventListener('DOMContentLoaded', startRefresh);
        
        // Stop refresh when user leaves page
        window.addEventListener('beforeunload', () => {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        });
        
        // Disable buttons and show loading state when forms are submitted
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const button = this.querySelector('button[type="submit"]');
                if (button) {
                    button.disabled = true;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
                }
            });
        });
    </script>
</body>
</html>
