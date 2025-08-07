<?php
/**
 * PHASE 3C.3: PDF EXPORT SYSTEM
 * Generate PDF reports for subscription scan results
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
$type = $_GET['type'] ?? 'scan'; // 'scan' or 'onetime'

// Initialize bank service and get data
$bankService = new BankService();
$exportData = null;

if ($scanId) {
    // Export specific scan
    $exportData = $bankService->getScanResults($userId, $scanId);
} else {
    // Export latest scan results
    $exportData = $bankService->getScanResults($userId);
}

if (!$exportData) {
    // No scan data available
    header('Location: ../dashboard.php?error=no_scan_data');
    exit;
}

// Simple HTML to PDF conversion (for basic hosting compatibility)
// In production, you might want to use a proper PDF library like TCPDF or mPDF

$filename = 'CashControl_Subscription_Report_' . date('Y-m-d') . '.pdf';

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Generate PDF content using HTML/CSS (basic approach)
// For production, consider using a proper PDF library
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>CashControl Subscription Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #10b981;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #10b981;
            margin-bottom: 10px;
        }
        .report-title {
            font-size: 20px;
            color: #374151;
            margin-bottom: 5px;
        }
        .report-date {
            color: #6b7280;
            font-size: 14px;
        }
        .summary-section {
            background-color: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .summary-title {
            font-size: 18px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 15px;
        }
        .summary-grid {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .summary-item {
            text-align: center;
            min-width: 150px;
            margin-bottom: 10px;
        }
        .summary-value {
            font-size: 24px;
            font-weight: bold;
            color: #10b981;
        }
        .summary-label {
            font-size: 14px;
            color: #6b7280;
            margin-top: 5px;
        }
        .subscriptions-section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 20px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 10px;
        }
        .subscription-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .subscription-table th,
        .subscription-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .subscription-table th {
            background-color: #f9fafb;
            font-weight: bold;
            color: #374151;
        }
        .subscription-table tr:hover {
            background-color: #f9fafb;
        }
        .cost-cell {
            font-weight: bold;
            color: #059669;
        }
        .category-badge {
            background-color: #e5e7eb;
            color: #374151;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
        .plan-info {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .plan-badge {
            background-color: #10b981;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        @media print {
            body { margin: 0; }
            .header { page-break-after: avoid; }
            .subscription-table { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="logo">💰 CashControl</div>
        <div class="report-title">Subscription Analysis Report</div>
        <div class="report-date">Generated on <?php echo date('F j, Y \a\t g:i A'); ?></div>
    </div>

    <!-- Plan Information -->
    <div class="plan-info">
        <strong>Account:</strong> <?php echo htmlspecialchars($userName); ?><br>
        <strong>Plan:</strong> <span class="plan-badge"><?php echo ucfirst($userPlan['plan_type']); ?></span><br>
        <strong>Scan Date:</strong> <?php echo date('F j, Y', strtotime($exportData['scan_date'])); ?>
    </div>

    <!-- Summary Section -->
    <div class="summary-section">
        <div class="summary-title">📊 Summary</div>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-value"><?php echo $exportData['subscriptions_found']; ?></div>
                <div class="summary-label">Subscriptions Found</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">€<?php echo number_format($exportData['monthly_total'], 2); ?></div>
                <div class="summary-label">Monthly Total</div>
            </div>
            <div class="summary-item">
                <div class="summary-value">€<?php echo number_format($exportData['yearly_total'], 2); ?></div>
                <div class="summary-label">Yearly Total</div>
            </div>
        </div>
    </div>

    <!-- Subscriptions Section -->
    <?php if (!empty($exportData['subscriptions'])): ?>
    <div class="subscriptions-section">
        <div class="section-title">📋 Discovered Subscriptions</div>
        
        <table class="subscription-table">
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Category</th>
                    <th>Billing Cycle</th>
                    <th>Cost</th>
                    <th>Monthly Cost</th>
                    <th>Next Payment</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Sort subscriptions by cost (highest first)
                usort($exportData['subscriptions'], function($a, $b) {
                    return $b['cost'] <=> $a['cost'];
                });
                
                foreach ($exportData['subscriptions'] as $sub): 
                    // Calculate monthly cost
                    $monthlyCost = $sub['cost'];
                    switch ($sub['billing_cycle']) {
                        case 'yearly':
                            $monthlyCost = $sub['cost'] / 12;
                            break;
                        case 'weekly':
                            $monthlyCost = $sub['cost'] * 4.33;
                            break;
                    }
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($sub['name']); ?></strong></td>
                    <td><span class="category-badge"><?php echo htmlspecialchars($sub['category']); ?></span></td>
                    <td><?php echo ucfirst($sub['billing_cycle']); ?></td>
                    <td class="cost-cell">€<?php echo number_format($sub['cost'], 2); ?></td>
                    <td class="cost-cell">€<?php echo number_format($monthlyCost, 2); ?></td>
                    <td><?php echo $sub['next_billing_date'] ? date('M j, Y', strtotime($sub['next_billing_date'])) : 'Unknown'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Category Breakdown -->
    <?php
    // Calculate category breakdown
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
    ?>
    
    <?php if (!empty($categoryTotals)): ?>
    <div class="subscriptions-section">
        <div class="section-title">📊 Spending by Category</div>
        
        <table class="subscription-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Subscriptions</th>
                    <th>Monthly Total</th>
                    <th>Yearly Total</th>
                    <th>% of Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categoryTotals as $category => $data): 
                    $percentage = ($data['total'] / $exportData['monthly_total']) * 100;
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($category); ?></strong></td>
                    <td><?php echo $data['count']; ?></td>
                    <td class="cost-cell">€<?php echo number_format($data['total'], 2); ?></td>
                    <td class="cost-cell">€<?php echo number_format($data['total'] * 12, 2); ?></td>
                    <td><?php echo number_format($percentage, 1); ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Recommendations -->
    <div class="subscriptions-section">
        <div class="section-title">💡 Money-Saving Recommendations</div>
        
        <div style="background-color: #fef3c7; border: 1px solid #f59e0b; border-radius: 6px; padding: 15px; margin-bottom: 15px;">
            <strong>💰 Potential Savings:</strong><br>
            <?php
            $potentialSavings = 0;
            $recommendations = [];
            
            // Find expensive subscriptions
            foreach ($exportData['subscriptions'] as $sub) {
                $monthlyCost = $sub['cost'];
                if ($sub['billing_cycle'] === 'yearly') {
                    $monthlyCost = $sub['cost'] / 12;
                }
                
                if ($monthlyCost > 10) {
                    $recommendations[] = "Consider if you really need " . $sub['name'] . " (€" . number_format($monthlyCost, 2) . "/month)";
                    $potentialSavings += $monthlyCost * 0.5; // Assume 50% could be saved
                }
            }
            
            // Find duplicate categories
            $categoryCount = array_count_values(array_column($exportData['subscriptions'], 'category'));
            foreach ($categoryCount as $category => $count) {
                if ($count > 2) {
                    $recommendations[] = "You have $count $category subscriptions - consider consolidating";
                }
            }
            
            if (empty($recommendations)) {
                $recommendations[] = "Your subscriptions look well-optimized!";
            }
            
            foreach ($recommendations as $rec) {
                echo "• " . htmlspecialchars($rec) . "<br>";
            }
            ?>
            <br>
            <strong>Estimated monthly savings potential: €<?php echo number_format($potentialSavings, 2); ?></strong>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>This report was generated by CashControl - Your Personal Subscription Manager</p>
        <p>Visit <strong>123cashcontrol.com</strong> to manage your subscriptions</p>
        <p>Report generated on <?php echo date('Y-m-d H:i:s'); ?> | Plan: <?php echo ucfirst($userPlan['plan_type']); ?></p>
    </div>
</body>
</html>

<?php
// For basic hosting, we'll output HTML that can be printed to PDF
// In production, you might want to use a proper PDF library like TCPDF or mPDF
// or a service like Puppeteer/Chrome headless for better PDF generation

// Note: This approach outputs HTML with PDF-friendly CSS
// Users can use their browser's "Print to PDF" function
// For automatic PDF generation, you'd need additional libraries
?>
