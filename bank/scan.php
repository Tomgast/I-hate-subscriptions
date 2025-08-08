<?php
/**
 * PHASE 3C.1: BANK SCAN CONTROLLER
 * Handles plan-based bank scanning with proper limitations and usage tracking
 */

session_start();
require_once '../config/db_config.php';
require_once '../includes/plan_manager.php';
require_once '../includes/bank_service.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/signin.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'User';

// Get user's plan information using both systems for reliability
$planManager = getPlanManager();
$userPlan = $planManager->getUserPlan($userId);

// Also use UserPlanHelper as backup verification
require_once '../includes/user_plan_helper.php';
$userPlanHelper = UserPlanHelper::getUserPlanStatus($userId);

// Enhanced plan verification - check both systems
$hasValidPlan = false;
$planType = null;

// Check PlanManager first
if ($userPlan && $userPlan['is_active'] && in_array($userPlan['plan_type'], ['monthly', 'yearly', 'onetime'])) {
    $hasValidPlan = true;
    $planType = $userPlan['plan_type'];
}
// Fallback to UserPlanHelper
elseif ($userPlanHelper && $userPlanHelper['is_paid'] && in_array($userPlanHelper['plan_type'], ['monthly', 'yearly', 'one_time'])) {
    $hasValidPlan = true;
    $planType = $userPlanHelper['plan_type'] === 'one_time' ? 'onetime' : $userPlanHelper['plan_type'];
}
// Direct database check as final fallback
else {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT subscription_type, subscription_status, subscription_expires_at, plan_type 
            FROM users 
            WHERE id = ? AND (
                (subscription_type IN ('monthly', 'yearly') AND subscription_status = 'active') OR
                (plan_type = 'onetime')
            )
        ");
        $stmt->execute([$userId]);
        $dbPlan = $stmt->fetch();
        
        if ($dbPlan) {
            $hasValidPlan = true;
            $planType = $dbPlan['plan_type'] ?: $dbPlan['subscription_type'];
        }
    } catch (Exception $e) {
        error_log("Bank scan plan check error: " . $e->getMessage());
    }
}

// Verify user has a valid plan for bank scanning
if (!$hasValidPlan) {
    header('Location: ../upgrade.php?reason=no_plan');
    exit;
}

// Subscription users (monthly/yearly) get unlimited bank scans
// One-time users get limited scans with verification
if (!in_array($planType, ['monthly', 'yearly', 'onetime'])) {
    header('Location: ../upgrade.php?reason=invalid_plan');
    exit;
}

// Check scan limitations based on plan type
if (in_array($planType, ['monthly', 'yearly'])) {
    // Subscription users have unlimited scans
    $canScan = true;
    $scansRemaining = 'unlimited';
} else {
    // One-time users have limited scans
    $canScan = $planManager->hasScansRemaining($userId);
    $scansRemaining = $userPlan['scans_remaining'] ?? 0;
}

// Get plan type from URL parameter (for tracking)
$planType = $_GET['plan'] ?? $userPlan['plan_type'];

// Initialize Stripe financial service
require_once '../includes/stripe_financial_service.php';
$stripeService = new StripeFinancialService($pdo);

// Handle form submissions
$error = null;
$success = null;
$scanInProgress = false;
$scanResults = null;

if ($_POST && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'start_scan':
                if (!$canScan) {
                    throw new Exception("You have reached your scan limit for this plan.");
                }
                
                // Start Stripe Financial Connections process
                $result = $stripeService->createBankConnectionSession($userId);
                
                if ($result['success']) {
                    // Redirect to Stripe Financial Connections authorization
                    header('Location: ' . $result['auth_url']);
                    exit;
                } else {
                    throw new Exception($result['error'] ?? "Failed to initiate bank connection. Please try again.");
                }
                break;
                
            case 'retry_scan':
                // Allow retry for failed scans (doesn't count against limit)
                $result = $stripeService->createBankConnectionSession($userId);
                
                if ($result['success']) {
                    header('Location: ' . $result['auth_url']);
                    exit;
                } else {
                    throw new Exception("Failed to retry bank connection. Please contact support.");
                }
                break;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Check for existing scan results
try {
    $pdo = getDBConnection();
    
    // Get latest scan for this user
    $stmt = $pdo->prepare("
        SELECT * FROM bank_scans 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $latestScan = $stmt->fetch();
    
    if ($latestScan) {
        if ($latestScan['status'] === 'in_progress') {
            $scanInProgress = true;
        } elseif ($latestScan['status'] === 'completed') {
            // Get scan results
            $scanResults = $bankService->getScanResults($userId, $latestScan['id']);
        }
    }
} catch (Exception $e) {
    error_log("Bank scan controller error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Scan - CashControl</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .hero-gradient {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .scan-animation {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../includes/header.php'; ?>

    <!-- Hero Section -->
    <div class="hero-gradient py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">
                    Bank Account Scan
                </h1>
                <p class="text-xl text-green-100 mb-2">
                    Automatically discover your subscriptions
                </p>
                <div class="inline-flex items-center bg-white/20 backdrop-blur-sm rounded-full px-4 py-2 text-white">
                    <span class="w-2 h-2 bg-green-300 rounded-full mr-2"></span>
                    <?php echo getUserPlanBadge($userId); ?>
                    <?php if ($userPlan['plan_type'] === 'onetime'): ?>
                    <span class="ml-2 text-sm">• <?php echo $scansRemaining; ?> scan remaining</span>
                    <?php else: ?>
                    <span class="ml-2 text-sm">• Unlimited scans</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Back Link -->
        <div class="mb-6">
            <a href="<?php echo $userPlan['plan_type'] === 'onetime' ? '../dashboard-onetime.php' : '../dashboard.php'; ?>" 
               class="inline-flex items-center text-gray-600 hover:text-gray-900 transition-colors duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Back to Dashboard
            </a>
        </div>
        
        <?php if (isset($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-lg mb-8 shadow-sm">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                <div>
                    <div class="font-medium">Scan Error</div>
                    <div class="text-sm"><?php echo htmlspecialchars($error); ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-lg mb-8 shadow-sm">
            ✅ <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <?php if ($scanInProgress): ?>
        <!-- Scan In Progress -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8 text-center">
            <div class="scan-animation w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Scan In Progress</h2>
            <p class="text-gray-600 mb-6">
                We're analyzing your bank account to discover subscriptions.<br>
                This usually takes 1-2 minutes.
            </p>
            <div class="bg-blue-50 rounded-lg p-4 mb-6">
                <div class="text-sm text-blue-800">
                    <div class="font-medium mb-2">What we're doing:</div>
                    <ul class="list-disc list-inside space-y-1 text-left max-w-md mx-auto">
                        <li>Securely connecting to your bank</li>
                        <li>Analyzing transaction patterns</li>
                        <li>Identifying recurring payments</li>
                        <li>Categorizing subscriptions</li>
                    </ul>
                </div>
            </div>
            <button onclick="location.reload()" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                Refresh Status
            </button>
        </div>
        
        <?php elseif ($scanResults): ?>
        <!-- Scan Results -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8 mb-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Scan Results</h2>
                    <p class="text-gray-600">Found <?php echo count($scanResults['subscriptions']); ?> subscriptions</p>
                </div>
                <div class="flex space-x-3">
                    <a href="../export/pdf.php?scan_id=<?php echo $scanResults['scan_id']; ?>" 
                       class="bg-red-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-red-700 transition-colors flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export PDF
                    </a>
                    <a href="../export/csv.php?scan_id=<?php echo $scanResults['scan_id']; ?>" 
                       class="bg-green-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-green-700 transition-colors flex items-center">
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
                    <div class="text-3xl font-bold text-blue-600 mb-2"><?php echo count($scanResults['subscriptions']); ?></div>
                    <div class="text-blue-800 font-medium">Subscriptions</div>
                </div>
                <div class="bg-green-50 rounded-lg p-6 text-center">
                    <div class="text-3xl font-bold text-green-600 mb-2">€<?php echo number_format($scanResults['monthly_total'], 2); ?></div>
                    <div class="text-green-800 font-medium">Monthly Total</div>
                </div>
                <div class="bg-purple-50 rounded-lg p-6 text-center">
                    <div class="text-3xl font-bold text-purple-600 mb-2">€<?php echo number_format($scanResults['yearly_total'], 2); ?></div>
                    <div class="text-purple-800 font-medium">Yearly Total</div>
                </div>
            </div>
            
            <!-- Subscriptions List -->
            <?php if (!empty($scanResults['subscriptions'])): ?>
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Discovered Subscriptions</h3>
                <?php foreach ($scanResults['subscriptions'] as $sub): ?>
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 hover:border-gray-300 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-gray-200 rounded-lg flex items-center justify-center mr-3">
                                <span class="text-lg"><?php 
                                    $icons = ['Entertainment' => '🎬', 'Software' => '💻', 'Music' => '🎵', 'Gaming' => '🎮', 'News' => '📰', 'Fitness' => '💪'];
                                    echo $icons[$sub['category']] ?? '📦';
                                ?></span>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($sub['name']); ?></div>
                                <div class="text-sm text-gray-600"><?php echo ucfirst($sub['billing_cycle']); ?> • <?php echo htmlspecialchars($sub['category']); ?></div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-semibold text-gray-900">€<?php echo number_format($sub['cost'], 2); ?></div>
                            <div class="text-sm text-gray-600"><?php echo $sub['billing_cycle']; ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <?php else: ?>
        <!-- Start Scan -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
            <?php if ($canScan): ?>
            <div class="text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Ready to Scan Your Bank</h2>
                <p class="text-gray-600 mb-6">
                    Connect your bank account to automatically discover all your subscriptions.<br>
                    <?php if ($userPlan['plan_type'] === 'onetime'): ?>
                    This is your <strong>one-time scan</strong> included with your plan.
                    <?php else: ?>
                    You have <strong>unlimited scans</strong> with your <?php echo $userPlan['plan_type']; ?> plan.
                    <?php endif; ?>
                </p>
                
                <form method="POST" class="inline-block">
                    <input type="hidden" name="action" value="start_scan">
                    <button type="submit" class="bg-green-600 text-white px-8 py-4 rounded-lg font-semibold text-lg shadow-lg hover:bg-green-700 transform hover:-translate-y-0.5 transition-all duration-200 flex items-center mx-auto">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Start Bank Scan
                    </button>
                </form>
                
                <div class="mt-6 text-sm text-gray-500">
                    <div class="flex items-center justify-center space-x-6">
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
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            No passwords stored
                        </span>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- Scan limit reached -->
            <div class="text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Scan Limit Reached</h2>
                <p class="text-gray-600 mb-6">
                    You have used all available scans for your current plan.<br>
                    Upgrade to get unlimited bank scans and more features.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="../upgrade.php?from=<?php echo $userPlan['plan_type']; ?>" 
                       class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                        Upgrade Plan
                    </a>
                    <a href="<?php echo $userPlan['plan_type'] === 'onetime' ? '../dashboard-onetime.php' : '../dashboard.php'; ?>" 
                       class="bg-gray-200 text-gray-800 px-6 py-3 rounded-lg font-semibold hover:bg-gray-300 transition-colors">
                        Back to Dashboard
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- How It Works -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8 mt-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">How Bank Scanning Works</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-2xl">🔐</span>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2">Secure Connection</h3>
                    <p class="text-sm text-gray-600">We use bank-grade security to safely connect to your account</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-2xl">🔍</span>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2">Smart Analysis</h3>
                    <p class="text-sm text-gray-600">Our AI analyzes your transactions to identify recurring payments</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-2xl">📊</span>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2">Instant Results</h3>
                    <p class="text-sm text-gray-600">Get a complete overview of all your subscriptions in seconds</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh for scan in progress
        <?php if ($scanInProgress): ?>
        setTimeout(function() {
            location.reload();
        }, 30000); // Refresh every 30 seconds
        <?php endif; ?>
    </script>
</body>
</html>
