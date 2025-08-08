<?php
session_start();
require_once 'config/db_config.php';
require_once 'includes/subscription_manager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/signin.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'User';
$isPaid = $_SESSION['is_paid'] ?? false;

// Enforce paid-only access to analytics
if (!$isPaid) {
    header('Location: upgrade.php?reason=analytics_access');
    exit;
}

$subscriptionManager = new SubscriptionManager();

// Get analytics data
$stats = $subscriptionManager->getSubscriptionStats($userId);
$subscriptions = $subscriptionManager->getUserSubscriptions($userId, false);

// Calculate advanced analytics
$monthlyData = [];
$categoryData = [];
$yearlyProjection = 0;

// Process subscriptions for analytics
foreach ($subscriptions as $sub) {
    $cost = floatval($sub['cost']);
    
    // Monthly breakdown
    switch ($sub['billing_cycle']) {
        case 'monthly':
            $monthlyCost = $cost;
            break;
        case 'yearly':
            $monthlyCost = $cost / 12;
            break;
        case 'weekly':
            $monthlyCost = $cost * 4.33;
            break;
        case 'daily':
            $monthlyCost = $cost * 30;
            break;
        default:
            $monthlyCost = $cost;
    }
    
    $yearlyProjection += $monthlyCost * 12;
    
    // Category breakdown
    $category = $sub['category'] ?? 'Other';
    if (!isset($categoryData[$category])) {
        $categoryData[$category] = 0;
    }
    $categoryData[$category] += $monthlyCost;
}

// Get last 12 months data for trends
$monthlyTrends = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthlyTrends[$month] = 0;
    
    foreach ($subscriptions as $sub) {
        $startDate = $sub['created_at'] ?? date('Y-m-d');
        if (strtotime($startDate) <= strtotime($month . '-01')) {
            $cost = floatval($sub['cost']);
            switch ($sub['billing_cycle']) {
                case 'monthly':
                    $monthlyTrends[$month] += $cost;
                    break;
                case 'yearly':
                    $monthlyTrends[$month] += $cost / 12;
                    break;
                case 'weekly':
                    $monthlyTrends[$month] += $cost * 4.33;
                    break;
                case 'daily':
                    $monthlyTrends[$month] += $cost * 30;
                    break;
            }
        }
    }
}

// Calculate savings opportunities
$savingsOpportunities = [];
foreach ($categoryData as $category => $monthlyCost) {
    if ($monthlyCost > 50) { // Categories over €50/month
        $savingsOpportunities[] = [
            'category' => $category,
            'monthly_cost' => $monthlyCost,
            'potential_savings' => $monthlyCost * 0.2, // Assume 20% potential savings
            'suggestion' => 'Consider reviewing ' . $category . ' subscriptions for alternatives'
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - CashControl</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <div class="container py-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Analytics & Insights</h1>
            
            <div class="flex gap-2">
                <button onclick="exportData('csv')" class="btn btn-outline">
                    📊 Export CSV
                </button>
                <button onclick="exportData('pdf')" class="btn btn-outline">
                    📄 Export PDF
                </button>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="card">
                <div class="p-4 text-center">
                    <div class="text-2xl font-bold text-blue-600">€<?php echo number_format($stats['monthly_total'], 2); ?></div>
                    <div class="text-sm text-gray-600">Monthly Spending</div>
                </div>
            </div>
            
            <div class="card">
                <div class="p-4 text-center">
                    <div class="text-2xl font-bold text-green-600">€<?php echo number_format($yearlyProjection, 2); ?></div>
                    <div class="text-sm text-gray-600">Yearly Projection</div>
                </div>
            </div>
            
            <div class="card">
                <div class="p-4 text-center">
                    <div class="text-2xl font-bold text-purple-600"><?php echo $stats['active_count']; ?></div>
                    <div class="text-sm text-gray-600">Active Subscriptions</div>
                </div>
            </div>
            
            <div class="card">
                <div class="p-4 text-center">
                    <div class="text-2xl font-bold text-orange-600">€<?php echo number_format($stats['monthly_total'] / max($stats['active_count'], 1), 2); ?></div>
                    <div class="text-sm text-gray-600">Average per Service</div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Monthly Trends Chart -->
            <div class="card">
                <div class="p-5">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Monthly Spending Trends</h2>
                    <canvas id="trendsChart" width="400" height="200"></canvas>
                </div>
            </div>
            
            <!-- Category Breakdown Chart -->
            <div class="card">
                <div class="p-5">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Spending by Category</h2>
                    <canvas id="categoryChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Savings Opportunities -->
        <?php if (!empty($savingsOpportunities)): ?>
        <div class="card mb-6">
            <div class="p-5">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">💡 Savings Opportunities</h2>
                
                <div class="space-y-3">
                    <?php foreach ($savingsOpportunities as $opportunity): ?>
                    <div class="p-4 border border-yellow-200 bg-yellow-50 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($opportunity['category']); ?></h3>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($opportunity['suggestion']); ?></p>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-500">Current: €<?php echo number_format($opportunity['monthly_cost'], 2); ?>/month</div>
                                <div class="text-sm font-medium text-green-600">Potential savings: €<?php echo number_format($opportunity['potential_savings'], 2); ?>/month</div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Detailed Breakdown -->
        <div class="card">
            <div class="p-5">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Detailed Breakdown</h2>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-4 font-medium text-gray-900">Service</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-900">Category</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-900">Cost</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-900">Billing</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-900">Monthly Equivalent</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-900">Next Payment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subscriptions as $sub): ?>
                            <?php
                                $cost = floatval($sub['cost']);
                                switch ($sub['billing_cycle']) {
                                    case 'monthly': $monthlyEquiv = $cost; break;
                                    case 'yearly': $monthlyEquiv = $cost / 12; break;
                                    case 'weekly': $monthlyEquiv = $cost * 4.33; break;
                                    case 'daily': $monthlyEquiv = $cost * 30; break;
                                    default: $monthlyEquiv = $cost;
                                }
                            ?>
                            <tr class="border-b border-gray-100">
                                <td class="py-3 px-4">
                                    <div class="font-medium text-gray-900"><?php echo htmlspecialchars($sub['name']); ?></div>
                                    <?php if (!empty($sub['description'])): ?>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($sub['description']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="badge badge-gray"><?php echo htmlspecialchars($sub['category'] ?? 'Other'); ?></span>
                                </td>
                                <td class="py-3 px-4 font-medium">€<?php echo number_format($cost, 2); ?></td>
                                <td class="py-3 px-4"><?php echo ucfirst($sub['billing_cycle']); ?></td>
                                <td class="py-3 px-4 text-blue-600 font-medium">€<?php echo number_format($monthlyEquiv, 2); ?></td>
                                <td class="py-3 px-4"><?php echo date('M j, Y', strtotime($sub['next_payment_date'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Monthly Trends Chart
        const trendsCtx = document.getElementById('trendsChart').getContext('2d');
        new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($monthlyTrends)); ?>,
                datasets: [{
                    label: 'Monthly Spending (€)',
                    data: <?php echo json_encode(array_values($monthlyTrends)); ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '€' + value;
                            }
                        }
                    }
                }
            }
        });

        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_keys($categoryData)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($categoryData)); ?>,
                    backgroundColor: [
                        '#3b82f6', '#ef4444', '#10b981', '#f59e0b',
                        '#8b5cf6', '#06b6d4', '#84cc16', '#f97316',
                        '#ec4899', '#6b7280', '#14b8a6', '#f43f5e'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Export functionality
        function exportData(format) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'api/export.php';
            
            const formatInput = document.createElement('input');
            formatInput.type = 'hidden';
            formatInput.name = 'format';
            formatInput.value = format;
            
            form.appendChild(formatInput);
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
    </script>

</body>
</html>
