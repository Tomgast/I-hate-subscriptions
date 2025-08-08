<?php
/**
 * PHASE 3C.3: CSV EXPORT SYSTEM
 * Generate CSV files for subscription scan results
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

// Get user's plan information
$planManager = getPlanManager();
$userPlan = $planManager->getUserPlan($userId);

// Verify user has an active plan and can export
if (!$userPlan || !$userPlan['is_active'] || !$planManager->canAccessFeature($userId, 'export')) {
    header('Location: ../upgrade.php?reason=no_export_access');
    exit;
}

// Get export parameters
$scanId = $_GET['scan_id'] ?? null;
$format = $_GET['format'] ?? 'detailed'; // 'detailed' or 'summary'

// Initialize Stripe financial service and get data
require_once '../includes/stripe_financial_service.php';
$pdo = getDBConnection();
$stripeService = new StripeFinancialService($pdo);
$exportData = null;

// Get bank connections and scan data from Stripe service
$connections = $stripeService->getUserBankConnections($userId);
$connectionStatus = $stripeService->getConnectionStatus($userId);

if (!empty($connections)) {
    // Prepare export data from Stripe connections
    $exportData = [
        'connections' => $connections,
        'scan_count' => $connectionStatus['scan_count'],
        'last_scan' => $connectionStatus['last_scan'],
        'has_data' => true
    ];
} else {
    $exportData = null;
}

if (!$exportData) {
    // No scan data available
    header('Location: ../dashboard.php?error=no_scan_data');
    exit;
}

// Generate filename
$filename = 'CashControl_Subscriptions_' . date('Y-m-d') . '.csv';

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Create file pointer for output
$output = fopen('php://output', 'w');

if ($format === 'summary') {
    // Summary format - just the key information
    fputcsv($output, ['CashControl Subscription Export - Summary']);
    fputcsv($output, ['Generated on', date('Y-m-d H:i:s')]);
    fputcsv($output, ['Account', $userName]);
    fputcsv($output, ['Plan', ucfirst($userPlan['plan_type'])]);
    fputcsv($output, ['Scan Date', date('Y-m-d', strtotime($exportData['scan_date']))]);
    fputcsv($output, []); // Empty row
    
    // Summary statistics
    fputcsv($output, ['SUMMARY STATISTICS']);
    fputcsv($output, ['Total Subscriptions Found', $exportData['subscriptions_found']]);
    fputcsv($output, ['Monthly Total Cost', '€' . number_format($exportData['monthly_total'], 2)]);
    fputcsv($output, ['Yearly Total Cost', '€' . number_format($exportData['yearly_total'], 2)]);
    fputcsv($output, []); // Empty row
    
    // Simple subscription list
    fputcsv($output, ['SUBSCRIPTIONS']);
    fputcsv($output, ['Service Name', 'Cost', 'Billing Cycle', 'Monthly Equivalent', 'Category']);
    
    foreach ($exportData['subscriptions'] as $sub) {
        // Calculate monthly equivalent
        $monthlyCost = $sub['cost'];
        switch ($sub['billing_cycle']) {
            case 'yearly':
                $monthlyCost = $sub['cost'] / 12;
                break;
            case 'weekly':
                $monthlyCost = $sub['cost'] * 4.33;
                break;
        }
        
        fputcsv($output, [
            $sub['name'],
            '€' . number_format($sub['cost'], 2),
            ucfirst($sub['billing_cycle']),
            '€' . number_format($monthlyCost, 2),
            $sub['category'] ?? 'Other'
        ]);
    }
    
} else {
    // Detailed format - comprehensive export
    fputcsv($output, ['CashControl Subscription Export - Detailed Report']);
    fputcsv($output, ['Generated on', date('Y-m-d H:i:s')]);
    fputcsv($output, ['Account', $userName]);
    fputcsv($output, ['Plan', ucfirst($userPlan['plan_type'])]);
    fputcsv($output, ['Scan Date', date('Y-m-d', strtotime($exportData['scan_date']))]);
    fputcsv($output, []); // Empty row
    
    // Summary statistics
    fputcsv($output, ['SUMMARY STATISTICS']);
    fputcsv($output, ['Metric', 'Value']);
    fputcsv($output, ['Total Subscriptions Found', $exportData['subscriptions_found']]);
    fputcsv($output, ['Monthly Total Cost', '€' . number_format($exportData['monthly_total'], 2)]);
    fputcsv($output, ['Yearly Total Cost', '€' . number_format($exportData['yearly_total'], 2)]);
    fputcsv($output, ['Average Cost per Subscription', '€' . number_format($exportData['monthly_total'] / max(1, $exportData['subscriptions_found']), 2)]);
    fputcsv($output, []); // Empty row
    
    // Detailed subscription list
    fputcsv($output, ['DETAILED SUBSCRIPTIONS']);
    fputcsv($output, [
        'Service Name',
        'Category',
        'Cost',
        'Billing Cycle',
        'Monthly Equivalent',
        'Yearly Equivalent',
        'Next Billing Date',
        'Description',
        'Status',
        'First Detected'
    ]);
    
    // Sort subscriptions by monthly cost (highest first)
    $sortedSubs = $exportData['subscriptions'];
    usort($sortedSubs, function($a, $b) {
        $aMonthlyCost = $a['cost'];
        $bMonthlyCost = $b['cost'];
        
        if ($a['billing_cycle'] === 'yearly') $aMonthlyCost = $a['cost'] / 12;
        if ($a['billing_cycle'] === 'weekly') $aMonthlyCost = $a['cost'] * 4.33;
        if ($b['billing_cycle'] === 'yearly') $bMonthlyCost = $b['cost'] / 12;
        if ($b['billing_cycle'] === 'weekly') $bMonthlyCost = $b['cost'] * 4.33;
        
        return $bMonthlyCost <=> $aMonthlyCost;
    });
    
    foreach ($sortedSubs as $sub) {
        // Calculate equivalents
        $monthlyCost = $sub['cost'];
        $yearlyCost = $sub['cost'] * 12;
        
        switch ($sub['billing_cycle']) {
            case 'yearly':
                $monthlyCost = $sub['cost'] / 12;
                $yearlyCost = $sub['cost'];
                break;
            case 'weekly':
                $monthlyCost = $sub['cost'] * 4.33;
                $yearlyCost = $sub['cost'] * 52;
                break;
        }
        
        fputcsv($output, [
            $sub['name'],
            $sub['category'] ?? 'Other',
            '€' . number_format($sub['cost'], 2),
            ucfirst($sub['billing_cycle']),
            '€' . number_format($monthlyCost, 2),
            '€' . number_format($yearlyCost, 2),
            $sub['next_billing_date'] ? date('Y-m-d', strtotime($sub['next_billing_date'])) : 'Unknown',
            $sub['description'] ?? '',
            $sub['status'] ?? 'Active',
            $sub['created_at'] ? date('Y-m-d', strtotime($sub['created_at'])) : date('Y-m-d')
        ]);
    }
    
    fputcsv($output, []); // Empty row
    
    // Category breakdown
    fputcsv($output, ['CATEGORY BREAKDOWN']);
    fputcsv($output, ['Category', 'Count', 'Monthly Total', 'Yearly Total', 'Percentage of Total']);
    
    // Calculate category totals
    $categoryTotals = [];
    foreach ($exportData['subscriptions'] as $sub) {
        $category = $sub['category'] ?? 'Other';
        $monthlyCost = $sub['cost'];
        
        switch ($sub['billing_cycle']) {
            case 'yearly':
                $monthlyCost = $sub['cost'] / 12;
                break;
            case 'weekly':
                $monthlyCost = $sub['cost'] * 4.33;
                break;
        }
        
        if (!isset($categoryTotals[$category])) {
            $categoryTotals[$category] = ['count' => 0, 'total' => 0];
        }
        $categoryTotals[$category]['count']++;
        $categoryTotals[$category]['total'] += $monthlyCost;
    }
    
    // Sort by total spending
    arsort($categoryTotals);
    
    foreach ($categoryTotals as $category => $data) {
        $percentage = ($data['total'] / max(1, $exportData['monthly_total'])) * 100;
        fputcsv($output, [
            $category,
            $data['count'],
            '€' . number_format($data['total'], 2),
            '€' . number_format($data['total'] * 12, 2),
            number_format($percentage, 1) . '%'
        ]);
    }
    
    fputcsv($output, []); // Empty row
    
    // Money-saving recommendations
    fputcsv($output, ['MONEY-SAVING RECOMMENDATIONS']);
    fputcsv($output, ['Recommendation Type', 'Description', 'Potential Monthly Savings']);
    
    $totalPotentialSavings = 0;
    
    // Find expensive subscriptions
    foreach ($exportData['subscriptions'] as $sub) {
        $monthlyCost = $sub['cost'];
        if ($sub['billing_cycle'] === 'yearly') {
            $monthlyCost = $sub['cost'] / 12;
        }
        
        if ($monthlyCost > 15) {
            $potentialSaving = $monthlyCost * 0.5; // Assume 50% could be saved
            $totalPotentialSavings += $potentialSaving;
            fputcsv($output, [
                'High Cost Service',
                'Consider if you really need ' . $sub['name'] . ' (€' . number_format($monthlyCost, 2) . '/month)',
                '€' . number_format($potentialSaving, 2)
            ]);
        }
    }
    
    // Find duplicate categories
    $categoryCount = array_count_values(array_column($exportData['subscriptions'], 'category'));
    foreach ($categoryCount as $category => $count) {
        if ($count > 2) {
            $avgCostInCategory = $categoryTotals[$category]['total'] / $count;
            $potentialSaving = $avgCostInCategory * ($count - 1) * 0.3; // Assume 30% savings from consolidation
            $totalPotentialSavings += $potentialSaving;
            fputcsv($output, [
                'Category Consolidation',
                "You have $count $category subscriptions - consider consolidating",
                '€' . number_format($potentialSaving, 2)
            ]);
        }
    }
    
    // Annual vs monthly billing optimization
    foreach ($exportData['subscriptions'] as $sub) {
        if ($sub['billing_cycle'] === 'monthly' && $sub['cost'] > 5) {
            $potentialSaving = $sub['cost'] * 2; // Assume 2 months free with annual billing
            fputcsv($output, [
                'Billing Optimization',
                'Consider annual billing for ' . $sub['name'] . ' to save money',
                '€' . number_format($potentialSaving, 2) . ' (yearly)'
            ]);
        }
    }
    
    fputcsv($output, []); // Empty row
    fputcsv($output, ['TOTAL ESTIMATED MONTHLY SAVINGS POTENTIAL', '€' . number_format($totalPotentialSavings, 2)]);
    fputcsv($output, ['TOTAL ESTIMATED YEARLY SAVINGS POTENTIAL', '€' . number_format($totalPotentialSavings * 12, 2)]);
    
    fputcsv($output, []); // Empty row
    fputcsv($output, ['Report generated by CashControl - 123cashcontrol.com']);
    fputcsv($output, ['Export completed on ' . date('Y-m-d H:i:s')]);
}

// Close the file pointer
fclose($output);
exit;
?>
