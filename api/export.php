<?php
session_start();
require_once '../config/db_config.php';
require_once '../includes/subscription_manager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'User';
$format = $_POST['format'] ?? 'csv';

$subscriptionManager = new SubscriptionManager();
$subscriptions = $subscriptionManager->getUserSubscriptions($userId, false);
$stats = $subscriptionManager->getSubscriptionStats($userId);

if ($format === 'csv') {
    // Generate CSV export
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="cashcontrol_subscriptions_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV Headers
    fputcsv($output, [
        'Service Name',
        'Description', 
        'Cost',
        'Currency',
        'Billing Cycle',
        'Category',
        'Next Payment Date',
        'Monthly Equivalent',
        'Website URL',
        'Status',
        'Created Date'
    ]);
    
    // CSV Data
    foreach ($subscriptions as $sub) {
        $cost = floatval($sub['cost']);
        switch ($sub['billing_cycle']) {
            case 'monthly': $monthlyEquiv = $cost; break;
            case 'yearly': $monthlyEquiv = $cost / 12; break;
            case 'weekly': $monthlyEquiv = $cost * 4.33; break;
            case 'daily': $monthlyEquiv = $cost * 30; break;
            default: $monthlyEquiv = $cost;
        }
        
        fputcsv($output, [
            $sub['name'],
            $sub['description'] ?? '',
            $cost,
            $sub['currency'] ?? 'EUR',
            $sub['billing_cycle'],
            $sub['category'] ?? 'Other',
            $sub['next_payment_date'],
            round($monthlyEquiv, 2),
            $sub['website_url'] ?? '',
            $sub['is_active'] ? 'Active' : 'Inactive',
            $sub['created_at'] ?? ''
        ]);
    }
    
    fclose($output);
    exit;
    
} elseif ($format === 'pdf') {
    // Generate PDF export using basic HTML to PDF
    require_once '../vendor/autoload.php'; // If you have TCPDF or similar
    
    // For now, create a simple HTML version that can be printed to PDF
    header('Content-Type: text/html');
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>CashControl Subscription Report</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            .stats { display: flex; justify-content: space-around; margin-bottom: 30px; }
            .stat-box { text-align: center; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f5f5f5; }
            .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
            @media print { .no-print { display: none; } }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>CashControl Subscription Report</h1>
            <p>Generated on ' . date('F j, Y \a\t g:i A') . '</p>
            <p>User: ' . htmlspecialchars($userName) . '</p>
        </div>
        
        <div class="stats">
            <div class="stat-box">
                <h3>€' . number_format($stats['monthly_total'], 2) . '</h3>
                <p>Monthly Total</p>
            </div>
            <div class="stat-box">
                <h3>€' . number_format($stats['yearly_total'], 2) . '</h3>
                <p>Yearly Total</p>
            </div>
            <div class="stat-box">
                <h3>' . $stats['active_count'] . '</h3>
                <p>Active Subscriptions</p>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Cost</th>
                    <th>Billing</th>
                    <th>Category</th>
                    <th>Next Payment</th>
                    <th>Monthly Equiv.</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($subscriptions as $sub) {
        $cost = floatval($sub['cost']);
        switch ($sub['billing_cycle']) {
            case 'monthly': $monthlyEquiv = $cost; break;
            case 'yearly': $monthlyEquiv = $cost / 12; break;
            case 'weekly': $monthlyEquiv = $cost * 4.33; break;
            case 'daily': $monthlyEquiv = $cost * 30; break;
            default: $monthlyEquiv = $cost;
        }
        
        echo '<tr>
            <td>' . htmlspecialchars($sub['name']) . '</td>
            <td>€' . number_format($cost, 2) . '</td>
            <td>' . ucfirst($sub['billing_cycle']) . '</td>
            <td>' . htmlspecialchars($sub['category'] ?? 'Other') . '</td>
            <td>' . date('M j, Y', strtotime($sub['next_payment_date'])) . '</td>
            <td>€' . number_format($monthlyEquiv, 2) . '</td>
        </tr>';
    }
    
    echo '</tbody>
        </table>
        
        <div class="footer">
            <p>Generated by CashControl - Never miss a renewal again</p>
            <p class="no-print">Press Ctrl+P to print or save as PDF</p>
        </div>
        
        <script class="no-print">
            // Auto-trigger print dialog
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        </script>
    </body>
    </html>';
    
    exit;
}

// Invalid format
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid export format']);
?>
