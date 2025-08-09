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

// Get user's plan information
$planManager = getPlanManager();
$userPlan = $planManager->getUserPlan($userId);

// Verify user has one-time plan
if (!$userPlan || $userPlan['plan_type'] !== 'onetime' || !$userPlan['is_active']) {
    header('Location: upgrade.php?reason=invalid_plan');
    exit;
}

// Initialize Stripe financial service for verification
require_once 'includes/stripe_financial_service.php';
$stripeService = new StripeFinancialService($pdo);

// Verify one-time scan status using Stripe service
$connectionStatus = $stripeService->getConnectionStatus($userId);
$hasSuccessfulScan = $connectionStatus['has_connections'];
$canRetry = !$hasSuccessfulScan; // Can retry if no successful connections
$canScan = $planManager->hasScansRemaining($userId) || $canRetry;

// Handle bank scan request
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] === 'start_bank_scan' && $canScan) {
        // Don't increment scan count for retries
        if (!$canRetry) {
            $planManager->incrementScanCount($userId);
        }
        
        // Redirect to Stripe bank integration
        header('Location: bank/stripe-scan.php?plan=onetime');
        exit;
    } elseif ($_POST['action'] === 'retry_scan' && $canRetry) {
        // Retry failed scan without incrementing count
        header('Location: bank/stripe-scan.php?plan=onetime&retry=1');
        exit;
    } else {
        $error = "You have already completed your one-time bank scan successfully.";
    }
}

// Get existing scan results if available
$scanResults = [];
$exportData = null;
// Get scan statistics from Stripe service
$scanStats = [
    'total_scans' => $connectionStatus['scan_count'],
    'successful_scans' => $connectionStatus['has_connections'] ? 1 : 0,
    'failed_scans' => 0,
    'last_scan_date' => $connectionStatus['last_scan'],
    'total_subscriptions_found' => 0
];

try {
    $pdo = getDBConnection();
    
    // Get scan results from Stripe service
    $scanResults = $stripeService->getUserBankConnections($userId);
    
    // Check for existing bank scan results
    $stmt = $pdo->prepare("SELECT * FROM bank_scans WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$userId]);
    $lastScan = $stmt->fetch();
    
    if ($lastScan) {
        // Get subscription data from the scan
        $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? AND source = 'bank_scan' ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $scanResults = $stmt->fetchAll();
        
        // Prepare export data
        $exportData = [
            'scan_date' => $lastScan['created_at'],
            'total_subscriptions' => count($scanResults),
            'monthly_total' => 0,
            'yearly_total' => 0,
            'subscriptions' => $scanResults
        ];
        
        // Calculate totals
        foreach ($scanResults as $sub) {
            $monthlyCost = 0;
            switch ($sub['billing_cycle']) {
                case 'monthly':
                    $monthlyCost = $sub['cost'];
                    break;
                case 'yearly':
                    $monthlyCost = $sub['cost'] / 12;
                    break;
                case 'weekly':
                    $monthlyCost = $sub['cost'] * 4.33;
                    break;
            }
            $exportData['monthly_total'] += $monthlyCost;
            $exportData['yearly_total'] += $monthlyCost * 12;
        }
    }
} catch (Exception $e) {
    error_log("One-time dashboard error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Subscription Audit - CashControl</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .hero-gradient {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .scan-card {
            transition: all 0.3s ease;
        }
        .scan-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <div class="hero-gradient py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">
                    Your Subscription Audit
                </h1>
                <p class="text-xl text-green-100 mb-2">
                    One-Time Bank Scan Results
                </p>
                <div class="inline-flex items-center bg-white/20 backdrop-blur-sm rounded-full px-4 py-2 text-white">
                    <span class="w-2 h-2 bg-green-300 rounded-full mr-2"></span>
                    <?php echo getUserPlanBadge($userId); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <?php if (isset($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-lg mb-8 shadow-sm">
            ❌ <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if (!$hasSuccessfulScan): ?>
        <!-- Bank Scan Section -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8 mb-8">
            <div class="text-center">
                <?php if ($canRetry): ?>
                <!-- Retry Scan -->
                <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Retry Bank Scan</h2>
                <p class="text-gray-600 mb-4">
                    Your previous scan didn't find subscriptions or encountered an issue.<br>
                    You can retry your scan at no additional cost.
                </p>
                
                <!-- Scan Status Info -->
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-6 text-left">
                    <h3 class="font-semibold text-orange-800 mb-2">Previous Scan Status:</h3>
                    <ul class="text-sm text-orange-700 space-y-1">
                        <?php if ($scanVerification['scan_date']): ?>
                        <li>• Last scan: <?php echo date('M j, Y \a\t g:i A', strtotime($scanVerification['scan_date'])); ?></li>
                        <?php endif; ?>
                        <li>• Subscriptions found: <?php echo $scanVerification['subscriptions_found'] ?? 0; ?></li>
                        <li>• Status: <?php echo $scanVerification['message']; ?></li>
                    </ul>
                </div>
                
                <form method="POST" class="inline-block">
                    <input type="hidden" name="action" value="retry_scan">
                    <button type="submit" class="bg-orange-600 text-white px-8 py-4 rounded-lg font-semibold text-lg shadow-lg hover:bg-orange-700 transform hover:-translate-y-0.5 transition-all duration-200 flex items-center mx-auto">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Retry Bank Scan
                    </button>
                </form>
                
                <?php else: ?>
                <!-- First Scan -->
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Ready to Scan Your Bank</h2>
                <p class="text-gray-600 mb-6">
                    Connect your bank account to automatically discover all your subscriptions.<br>
                    This is your <strong>one-time scan</strong> included with your plan.
                </p>
                
                <form method="POST" class="inline-block">
                    <input type="hidden" name="action" value="start_bank_scan">
                    <button type="submit" class="bg-green-600 text-white px-8 py-4 rounded-lg font-semibold text-lg shadow-lg hover:bg-green-700 transform hover:-translate-y-0.5 transition-all duration-200 flex items-center mx-auto">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Start Bank Scan
                    </button>
                </form>
                <?php endif; ?>
                
                <div class="mt-4 text-sm text-gray-500">
                    <div class="flex items-center justify-center space-x-4">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            Bank-grade security
                        </span>
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Read-only access
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        
        <!-- Scan Results Section -->
        <?php if ($exportData): ?>
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8 mb-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Your Subscription Audit Results</h2>
                    <p class="text-gray-600">Scanned on <?php echo date('F j, Y', strtotime($exportData['scan_date'])); ?></p>
                </div>
                <div class="flex space-x-3">
                    <a href="export/pdf.php?type=onetime" class="bg-red-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-red-700 transition-colors flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export PDF
                    </a>
                    <a href="export/csv.php?type=onetime" class="bg-green-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-green-700 transition-colors flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export CSV
                    </a>
                </div>
            </div>
            
            <!-- Summary Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-blue-50 rounded-lg p-6 text-center">
                    <div class="text-3xl font-bold text-blue-600"><?php echo count($scanResults['subscriptions']); ?></div>
                    <div class="text-blue-800 font-medium">Subscriptions</div>
                </div>
                <div class="bg-green-50 rounded-lg p-6 text-center">
                    <div class="text-3xl font-bold text-green-600">€<?php echo number_format($scanResults['monthly_total'] ?? 0, 2); ?></div>
                    <div class="text-green-800 font-medium">Monthly Total</div>
                </div>
                <div class="bg-purple-50 rounded-lg p-6 text-center">
                    <div class="text-3xl font-bold text-purple-600">€<?php echo number_format($scanResults['yearly_total'] ?? 0, 2); ?></div>
                    <div class="text-purple-800 font-medium">Yearly Total</div>
                </div>
            </div>
            
            <!-- Charts Section -->
            <?php if ($scanResults && !empty($scanResults['subscriptions'])): ?>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Category Breakdown Chart -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Spending by Category</h3>
                    <canvas id="categoryChart" width="400" height="200"></canvas>
                </div>
                
                <!-- Transaction Timeline -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Monthly Costs</h3>
                    <canvas id="costsChart" width="400" height="200"></canvas>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Subscription Summary -->
        <?php if ($scanResults && !empty($scanResults['subscriptions'])): ?>
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Discovered Subscriptions</h3>
                <?php foreach ($scanResults['subscriptions'] as $sub): ?>
                <div class="scan-card bg-gray-50 rounded-lg p-6 border border-gray-200 hover:border-gray-300 transition-colors">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center mr-4">
                                <span class="text-xl"><?php 
                                    $icons = ['Entertainment' => '🎬', 'Software' => '💻', 'Music' => '🎵', 'Gaming' => '🎮', 'News' => '📰', 'Fitness' => '💪'];
                                    echo $icons[$sub['category']] ?? '📦';
                                ?></span>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 text-lg"><?php echo htmlspecialchars($sub['name']); ?></div>
                                <div class="text-sm text-gray-600"><?php echo ucfirst($sub['billing_cycle']); ?> • <?php echo htmlspecialchars($sub['category']); ?></div>
                                <?php if (isset($sub['merchant_info'])): ?>
                                <div class="text-xs text-gray-500 mt-1">Merchant: <?php echo htmlspecialchars($sub['merchant_info']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-gray-900 text-xl">€<?php echo number_format($sub['cost'], 2); ?></div>
                            <div class="text-sm text-gray-600"><?php echo $sub['billing_cycle']; ?></div>
                            <?php if (isset($sub['next_payment'])): ?>
                            <div class="text-xs text-orange-600 mt-1">Next: <?php echo date('M j', strtotime($sub['next_payment'])); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Enhanced GoCardless Data -->
                    <?php if (isset($sub['transaction_details'])): ?>
                    <div class="bg-white rounded-lg p-4 border border-gray-100">
                        <h4 class="font-medium text-gray-900 mb-2">Transaction Details</h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <?php if (isset($sub['transaction_details']['creditor_name'])): ?>
                            <div>
                                <span class="text-gray-500">Creditor:</span>
                                <span class="font-medium"><?php echo htmlspecialchars($sub['transaction_details']['creditor_name']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (isset($sub['transaction_details']['booking_date'])): ?>
                            <div>
                                <span class="text-gray-500">Last Payment:</span>
                                <span class="font-medium"><?php echo date('M j, Y', strtotime($sub['transaction_details']['booking_date'])); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (isset($sub['transaction_details']['remittance_info'])): ?>
                            <div class="col-span-2">
                                <span class="text-gray-500">Description:</span>
                                <span class="font-medium"><?php echo htmlspecialchars($sub['transaction_details']['remittance_info']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Action Buttons -->
                    <div class="flex space-x-3 mt-4">
                        <a href="guides/unsubscribe.php?service=<?php echo urlencode(strtolower($sub['name'])); ?>" class="flex-1 bg-red-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-red-700 transition-colors text-center">
                            🚫 Cancel Subscription
                        </a>
                        <button onclick="showTransactionHistory('<?php echo $sub['id']; ?>')" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                            📊 View History
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <!-- No Results Yet -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-8 text-center">
            <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-yellow-800 mb-2">Scan Already Used</h3>
            <p class="text-yellow-700">You have used your one-time bank scan, but no results are available yet. This might be due to a technical issue.</p>
        </div>
        <?php endif; ?>
        <?php endif; ?>
        
        <!-- Unsubscribe Guides -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Unsubscribe Guides</h2>
            <p class="text-gray-600 mb-6">Get step-by-step instructions to cancel unwanted subscriptions.</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <a href="guides/unsubscribe.php?service=netflix" class="block bg-red-50 hover:bg-red-100 rounded-lg p-4 transition-colors">
                    <div class="flex items-center">
                        <span class="text-2xl mr-3">🎬</span>
                        <div>
                            <div class="font-semibold text-gray-900">Netflix</div>
                            <div class="text-sm text-gray-600">Cancel streaming subscription</div>
                        </div>
                    </div>
                </a>
                
                <a href="guides/unsubscribe.php?service=spotify" class="block bg-green-50 hover:bg-green-100 rounded-lg p-4 transition-colors">
                    <div class="flex items-center">
                        <span class="text-2xl mr-3">🎵</span>
                        <div>
                            <div class="font-semibold text-gray-900">Spotify</div>
                            <div class="text-sm text-gray-600">Cancel music subscription</div>
                        </div>
                    </div>
                </a>
                
                <a href="guides/unsubscribe.php?service=adobe" class="block bg-blue-50 hover:bg-blue-100 rounded-lg p-4 transition-colors">
                    <div class="flex items-center">
                        <span class="text-2xl mr-3">💻</span>
                        <div>
                            <div class="font-semibold text-gray-900">Adobe</div>
                            <div class="text-sm text-gray-600">Cancel Creative Cloud</div>
                        </div>
                    </div>
                </div>
                
                <a href="guides/unsubscribe.php" class="block bg-gray-50 hover:bg-gray-100 rounded-lg p-4 transition-colors md:col-span-2 lg:col-span-3">
                    <div class="flex items-center justify-center">
                        <span class="text-2xl mr-3">📚</span>
                        <div class="text-center">
                            <div class="font-semibold text-gray-900">View All Guides</div>
                            <div class="text-sm text-gray-600">Complete library of cancellation instructions</div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        
        <!-- Upgrade Suggestion -->
        <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl shadow-lg p-8 text-white text-center">
            <h2 class="text-2xl font-bold mb-4">Want Unlimited Features?</h2>
            <p class="text-blue-100 mb-6">
                Upgrade to a monthly or yearly plan for unlimited bank scans, real-time analytics, and automatic monitoring.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="upgrade.php?from=onetime" class="bg-white text-blue-600 px-6 py-3 rounded-lg font-semibold hover:bg-blue-50 transition-colors">
                    View Upgrade Options
                </a>
                <a href="mailto:support@123cashcontrol.com" class="bg-white/20 backdrop-blur-sm text-white px-6 py-3 rounded-lg font-semibold hover:bg-white/30 transition-colors">
                    Contact Support
                </a>
            </div>
        </div>
    </div>

    <script>
        // Initialize charts if we have scan results
        <?php if ($scanResults && !empty($scanResults['subscriptions'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Category breakdown chart
            const categoryData = {
                <?php 
                $categoryTotals = [];
                foreach ($scanResults['subscriptions'] as $sub) {
                    $category = $sub['category'] ?? 'Other';
                    $monthlyCost = 0;
                    switch ($sub['billing_cycle']) {
                        case 'monthly': $monthlyCost = $sub['cost']; break;
                        case 'yearly': $monthlyCost = $sub['cost'] / 12; break;
                        case 'weekly': $monthlyCost = $sub['cost'] * 4.33; break;
                    }
                    $categoryTotals[$category] = ($categoryTotals[$category] ?? 0) + $monthlyCost;
                }
                ?>
                labels: <?php echo json_encode(array_keys($categoryTotals)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($categoryTotals)); ?>,
                    backgroundColor: [
                        '#ef4444', '#10b981', '#3b82f6', '#6b7280', 
                        '#f59e0b', '#f97316', '#ec4899', '#8b5cf6'
                    ]
                }]
            };
            
            new Chart(document.getElementById('categoryChart'), {
                type: 'doughnut',
                data: categoryData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            
            // Monthly costs chart
            const costsData = {
                labels: <?php echo json_encode(array_keys($categoryTotals)); ?>,
                datasets: [{
                    label: 'Monthly Cost',
                    data: <?php echo json_encode(array_values($categoryTotals)); ?>,
                    backgroundColor: '#3b82f6',
                    borderColor: '#1d4ed8',
                    borderWidth: 2
                }]
            };
            
            new Chart(document.getElementById('costsChart'), {
                type: 'bar',
                data: costsData,
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
        });
        <?php endif; ?>
        
        // Transaction history modal
        function showTransactionHistory(subscriptionId) {
            alert('Transaction history feature coming soon! This will show detailed payment history from GoCardless data.');
        }
        
        // Smooth scroll for anchor links
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.querySelector(this.getAttribute('href')).scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });
        });
    </script>
</body>
</html>
