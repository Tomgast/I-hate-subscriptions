<?php
/**
 * ADVANCED DASHBOARD PROTOTYPE
 * State-of-the-art subscription management dashboard leveraging:
 * - Raw bank transaction data analysis
 * - Advanced subscription detection algorithms
 * - Real-time bank connection monitoring
 * - Predictive analytics and insights
 */

session_start();
require_once 'config/db_config.php';
require_once 'includes/subscription_manager.php';
require_once 'includes/multi_bank_service.php';
require_once 'includes/plan_manager.php';
require_once 'includes/bank_provider_router.php';
require_once 'improved-subscription-detection.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/signin.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'User';

// Initialize services
$pdo = getDBConnection();
$subscriptionManager = new SubscriptionManager();
$multiBankService = new MultiBankService();
$planManager = getPlanManager();
$providerRouter = new BankProviderRouter($pdo);

// Get user plan info
$userPlan = $planManager->getUserPlan($userId);
$hasValidPlan = $userPlan && $userPlan['is_active'];

// Advanced Analytics Functions
function getAdvancedAnalytics($pdo, $userId) {
    // Get subscription trends
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as new_subscriptions,
            SUM(cost) as monthly_cost_added
        FROM subscriptions 
        WHERE user_id = ? 
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
        LIMIT 6
    ");
    $stmt->execute([$userId]);
    $trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get category breakdown
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(category, 'Uncategorized') as category,
            COUNT(*) as count,
            SUM(cost) as total_cost,
            AVG(confidence) as avg_confidence
        FROM subscriptions 
        WHERE user_id = ? AND is_active = 1
        GROUP BY category
        ORDER BY total_cost DESC
    ");
    $stmt->execute([$userId]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return ['trends' => $trends, 'categories' => $categories];
}

function getTransactionInsights($pdo, $userId) {
    // Top recurring merchants (potential subscriptions)
    $stmt = $pdo->prepare("
        SELECT 
            merchant_name,
            COUNT(*) as transaction_count,
            AVG(ABS(amount)) as avg_amount,
            MIN(booking_date) as first_seen,
            MAX(booking_date) as last_seen,
            CASE 
                WHEN COUNT(*) >= 12 THEN 'Monthly Pattern'
                WHEN COUNT(*) >= 4 THEN 'Quarterly Pattern'
                WHEN COUNT(*) >= 2 THEN 'Recurring Pattern'
                ELSE 'Single Transaction'
            END as pattern_type
        FROM raw_transactions 
        WHERE user_id = ? 
        AND amount < 0  -- Only expenses
        AND booking_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY merchant_name
        HAVING COUNT(*) >= 2
        ORDER BY transaction_count DESC, avg_amount DESC
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getBankHealth($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT 
            bc.*,
            bsr.last_sync_at as last_scan,
            bsr.subscriptions_found,
            bsr.status as scan_status
        FROM bank_connections bc
        LEFT JOIN bank_scan_results bsr ON bc.user_id = bsr.user_id AND bc.provider = bsr.provider
        WHERE bc.user_id = ?
        ORDER BY bc.created_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get dashboard data
$subscriptions = $subscriptionManager->getUserSubscriptions($userId, false);
$stats = $subscriptionManager->getSubscriptionStats($userId);
$upcomingPayments = $subscriptionManager->getUpcomingPayments($userId, 30);
$bankSummary = $multiBankService->getBankAccountSummary($userId);

// Advanced analytics
$analytics = getAdvancedAnalytics($pdo, $userId);
$transactionInsights = getTransactionInsights($pdo, $userId);
$bankHealth = getBankHealth($pdo, $userId);

// Detect potential new subscriptions
$potentialSubscriptions = [];
foreach ($transactionInsights as $merchant) {
    // Check if already a known subscription
    $isKnown = false;
    foreach ($subscriptions as $sub) {
        if (stripos($sub['merchant_name'] ?? $sub['name'], $merchant['merchant_name']) !== false) {
            $isKnown = true;
            break;
        }
    }
    
    if (!$isKnown) {
        $pattern = [
            'merchant_name' => $merchant['merchant_name'],
            'amount' => $merchant['avg_amount'],
            'billing_cycle' => $merchant['pattern_type'] === 'Monthly Pattern' ? 'monthly' : 'unknown',
            'confidence' => min(90, $merchant['transaction_count'] * 8)
        ];
        
        $validation = ImprovedSubscriptionDetector::validateSubscriptionPattern($pattern);
        if ($validation['valid']) {
            $potentialSubscriptions[] = array_merge($merchant, [
                'validation_score' => $validation['score']
            ]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Dashboard - CashControl</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        body {
            background: var(--primary-gradient);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .metric-card {
            background: var(--primary-gradient);
            color: white;
            text-align: center;
            padding: 25px;
        }

        .metric-card.success { background: var(--success-gradient); }
        .metric-card.warning { background: var(--warning-gradient); }
        .metric-card.info { background: var(--info-gradient); }

        .metric-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .metric-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .subscription-card {
            border-left: 4px solid #007bff;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .subscription-card:hover {
            border-left-color: #28a745;
            background-color: rgba(40, 167, 69, 0.05);
        }

        .confidence-badge {
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 12px;
        }

        .confidence-high { background: #d4edda; color: #155724; }
        .confidence-medium { background: #fff3cd; color: #856404; }
        .confidence-low { background: #f8d7da; color: #721c24; }

        .bank-status {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .bank-status.connected {
            background: rgba(40, 167, 69, 0.1);
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .bank-status.disconnected {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .potential-subscription {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
        }

        .navbar-custom {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }

        .section-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
            margin-bottom: 0;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fas fa-chart-line me-2"></i>CashControl Pro
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($userName) ?>
                </span>
                <a href="dashboard.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Back to Classic
                </a>
            </div>
        </div>
    </nav>

    <div class="container" style="margin-top: 80px; padding: 20px 0;">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-4">
                        <h1 class="mb-2">
                            <i class="fas fa-rocket me-2 text-primary"></i>
                            Advanced Subscription Analytics
                        </h1>
                        <p class="text-muted mb-0">
                            AI-powered dashboard with real-time bank data analysis
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card metric-card">
                    <div class="metric-value"><?= $stats['total_active'] ?? 0 ?></div>
                    <div class="metric-label">Active Subscriptions</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card metric-card success">
                    <div class="metric-value">€<?= number_format($stats['monthly_total'] ?? 0, 2) ?></div>
                    <div class="metric-label">Monthly Cost</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card metric-card warning">
                    <div class="metric-value">€<?= number_format($stats['yearly_total'] ?? 0, 2) ?></div>
                    <div class="metric-label">Yearly Projection</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card metric-card info">
                    <div class="metric-value"><?= count($potentialSubscriptions) ?></div>
                    <div class="metric-label">AI Detected</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Category Analysis -->
                <div class="card mb-4">
                    <div class="section-header">
                        <h5 class="mb-0">
                            <i class="fas fa-pie-chart me-2"></i>
                            Subscription Categories & Confidence
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($analytics['categories'] as $category): ?>
                        <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                            <div>
                                <strong><?= htmlspecialchars($category['category']) ?></strong>
                                <small class="text-muted d-block"><?= $category['count'] ?> subscriptions</small>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold">€<?= number_format($category['total_cost'], 2) ?></div>
                                <div class="confidence-badge confidence-<?= $category['avg_confidence'] >= 80 ? 'high' : ($category['avg_confidence'] >= 60 ? 'medium' : 'low') ?>">
                                    <?= round($category['avg_confidence']) ?>% confidence
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- AI-Detected Potential Subscriptions -->
                <?php if (!empty($potentialSubscriptions)): ?>
                <div class="card mb-4">
                    <div class="section-header">
                        <h5 class="mb-0">
                            <i class="fas fa-robot me-2"></i>
                            AI-Detected Potential Subscriptions
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            Our AI analyzed your transactions and found these recurring payments:
                        </p>
                        <?php foreach (array_slice($potentialSubscriptions, 0, 5) as $potential): ?>
                        <div class="potential-subscription">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><?= htmlspecialchars($potential['merchant_name']) ?></h6>
                                    <small class="text-muted">
                                        <?= $potential['transaction_count'] ?> transactions • 
                                        <?= $potential['pattern_type'] ?> •
                                        Since <?= date('M Y', strtotime($potential['first_seen'])) ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold">€<?= number_format($potential['avg_amount'], 2) ?></div>
                                    <div class="confidence-badge confidence-<?= $potential['validation_score'] >= 80 ? 'high' : ($potential['validation_score'] >= 60 ? 'medium' : 'low') ?>">
                                        <?= $potential['validation_score'] ?>% match
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-outline-primary me-2">
                                    <i class="fas fa-plus me-1"></i>Add as Subscription
                                </button>
                                <button class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Ignore
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Current Subscriptions -->
                <div class="card">
                    <div class="section-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Current Subscriptions (<?= count($subscriptions) ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach (array_slice($subscriptions, 0, 8) as $subscription): ?>
                        <div class="subscription-card card <?= !$subscription['is_active'] ? 'inactive' : '' ?>">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($subscription['merchant_name'] ?? $subscription['name']) ?></h6>
                                        <small class="text-muted">
                                            <?= ucfirst($subscription['billing_cycle'] ?? 'monthly') ?> • 
                                            <?= htmlspecialchars($subscription['category'] ?? 'Other') ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold">€<?= number_format($subscription['cost'], 2) ?></div>
                                        <?php if (isset($subscription['confidence'])): ?>
                                        <div class="confidence-badge confidence-<?= $subscription['confidence'] >= 80 ? 'high' : ($subscription['confidence'] >= 60 ? 'medium' : 'low') ?>">
                                            <?= round($subscription['confidence']) ?>% confidence
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if (count($subscriptions) > 8): ?>
                        <div class="text-center mt-3">
                            <a href="dashboard.php" class="btn btn-outline-primary">
                                View All <?= count($subscriptions) ?> Subscriptions
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Bank Health -->
                <div class="card mb-4">
                    <div class="section-header">
                        <h5 class="mb-0">
                            <i class="fas fa-heartbeat me-2"></i>
                            Bank Connection Health
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($bankHealth)): ?>
                            <?php foreach ($bankHealth as $connection): ?>
                            <div class="bank-status <?= $connection['status'] === 'active' ? 'connected' : 'disconnected' ?>">
                                <div class="flex-grow-1">
                                    <div class="fw-bold"><?= htmlspecialchars($connection['provider']) ?></div>
                                    <small class="text-muted">
                                        <?= $connection['account_name'] ?? 'Connected Account' ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <i class="fas fa-<?= $connection['status'] === 'active' ? 'check-circle text-success' : 'exclamation-triangle text-warning' ?>"></i>
                                    <?php if ($connection['last_scan']): ?>
                                    <div class="small text-muted">
                                        <?= date('M j', strtotime($connection['last_scan'])) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-unlink fa-2x text-muted mb-2"></i>
                                <p class="text-muted">No bank connections</p>
                                <a href="bank/unified-scan.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus me-1"></i>Connect Bank
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card mb-4">
                    <div class="section-header">
                        <h5 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>
                            Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="bank/unified-scan.php" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Scan for New Subscriptions
                            </a>
                            <button class="btn btn-outline-success">
                                <i class="fas fa-plus me-2"></i>Add Manual Subscription
                            </button>
                            <button class="btn btn-outline-info">
                                <i class="fas fa-download me-2"></i>Export Data
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Payments -->
                <?php if (!empty($upcomingPayments)): ?>
                <div class="card">
                    <div class="section-header">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Upcoming Payments
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach (array_slice($upcomingPayments, 0, 5) as $payment): ?>
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <div class="fw-bold"><?= htmlspecialchars($payment['name']) ?></div>
                                <small class="text-muted">
                                    <?= isset($payment['next_payment_date']) ? date('M j', strtotime($payment['next_payment_date'])) : 'Soon' ?>
                                </small>
                            </div>
                            <div class="fw-bold">€<?= number_format($payment['cost'], 2) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
