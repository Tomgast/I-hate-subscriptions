<?php
/**
 * PHASE 3C.3: EXPORT CONTROLLER
 * Main export interface for subscription scan results
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

// Initialize bank service and get available scans
$bankService = new BankService();
$availableScans = $bankService->getUserScans($userId);
$latestScan = $bankService->getScanResults($userId);

// Handle export requests
if ($_POST && isset($_POST['export_type'])) {
    $exportType = $_POST['export_type'];
    $scanId = $_POST['scan_id'] ?? null;
    $format = $_POST['format'] ?? 'detailed';
    
    switch ($exportType) {
        case 'pdf':
            $url = 'pdf.php';
            if ($scanId) $url .= '?scan_id=' . urlencode($scanId);
            header('Location: ' . $url);
            exit;
            
        case 'csv':
            $url = 'csv.php';
            $params = [];
            if ($scanId) $params['scan_id'] = $scanId;
            if ($format) $params['format'] = $format;
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            header('Location: ' . $url);
            exit;
    }
}

$pageTitle = 'Export Subscription Data';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - CashControl</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <?php include '../includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-download text-green-600 mr-3"></i>
                        Export Subscription Data
                    </h1>
                    <p class="text-gray-600">Download your subscription analysis in PDF or CSV format</p>
                </div>
                <div class="text-right">
                    <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <i class="fas fa-crown mr-1"></i>
                        <?php echo ucfirst($userPlan['plan_type']); ?> Plan
                    </div>
                </div>
            </div>
        </div>

        <?php if (!$latestScan): ?>
        <!-- No Scan Data -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
            <i class="fas fa-exclamation-triangle text-yellow-600 text-4xl mb-4"></i>
            <h3 class="text-lg font-semibold text-yellow-800 mb-2">No Scan Data Available</h3>
            <p class="text-yellow-700 mb-4">You need to complete a bank scan before you can export your subscription data.</p>
            <a href="../bank/scan.php" class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
                <i class="fas fa-search mr-2"></i>
                Start Bank Scan
            </a>
        </div>
        <?php else: ?>

        <!-- Scan Summary -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">
                    <i class="fas fa-chart-bar text-green-600 mr-2"></i>
                    Latest Scan Summary
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-green-600 mb-1">
                            <?php echo $latestScan['subscriptions_found']; ?>
                        </div>
                        <div class="text-sm text-gray-600">Subscriptions Found</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-green-600 mb-1">
                            €<?php echo number_format($latestScan['monthly_total'], 2); ?>
                        </div>
                        <div class="text-sm text-gray-600">Monthly Total</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-green-600 mb-1">
                            €<?php echo number_format($latestScan['yearly_total'], 2); ?>
                        </div>
                        <div class="text-sm text-gray-600">Yearly Total</div>
                    </div>
                </div>
                
                <div class="text-sm text-gray-600">
                    <i class="fas fa-calendar mr-1"></i>
                    Last scanned: <?php echo date('F j, Y \a\t g:i A', strtotime($latestScan['scan_date'])); ?>
                </div>
            </div>
        </div>

        <!-- Export Options -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- PDF Export -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-file-pdf text-red-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">PDF Report</h3>
                            <p class="text-sm text-gray-600">Professional formatted report</p>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h4 class="font-medium text-gray-900 mb-2">Includes:</h4>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Summary statistics</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Detailed subscription list</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Category breakdown</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Money-saving recommendations</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Professional formatting</li>
                        </ul>
                    </div>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="export_type" value="pdf">
                        
                        <?php if (count($availableScans) > 1): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Scan:</label>
                            <select name="scan_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                <option value="">Latest Scan</option>
                                <?php foreach ($availableScans as $scan): ?>
                                <option value="<?php echo $scan['id']; ?>">
                                    <?php echo date('M j, Y', strtotime($scan['created_at'])); ?> 
                                    (<?php echo $scan['subscriptions_found']; ?> subscriptions)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <button type="submit" class="w-full flex items-center justify-center px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium">
                            <i class="fas fa-download mr-2"></i>
                            Download PDF Report
                        </button>
                    </form>
                </div>
            </div>

            <!-- CSV Export -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-file-csv text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">CSV Data</h3>
                            <p class="text-sm text-gray-600">Spreadsheet-compatible format</p>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h4 class="font-medium text-gray-900 mb-2">Perfect for:</h4>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Excel/Google Sheets analysis</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Custom calculations</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Data integration</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Budgeting tools</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i>Financial planning</li>
                        </ul>
                    </div>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="export_type" value="csv">
                        
                        <?php if (count($availableScans) > 1): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Scan:</label>
                            <select name="scan_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                <option value="">Latest Scan</option>
                                <?php foreach ($availableScans as $scan): ?>
                                <option value="<?php echo $scan['id']; ?>">
                                    <?php echo date('M j, Y', strtotime($scan['created_at'])); ?> 
                                    (<?php echo $scan['subscriptions_found']; ?> subscriptions)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Export Format:</label>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="radio" name="format" value="detailed" checked class="text-green-600 focus:ring-green-500">
                                    <span class="ml-2 text-sm text-gray-700">
                                        <strong>Detailed</strong> - Complete data with recommendations
                                    </span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="format" value="summary" class="text-green-600 focus:ring-green-500">
                                    <span class="ml-2 text-sm text-gray-700">
                                        <strong>Summary</strong> - Basic subscription list only
                                    </span>
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="w-full flex items-center justify-center px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                            <i class="fas fa-download mr-2"></i>
                            Download CSV Data
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Export Tips -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-900 mb-3">
                <i class="fas fa-lightbulb text-blue-600 mr-2"></i>
                Export Tips
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-blue-800">
                <div>
                    <h4 class="font-medium mb-2">PDF Reports:</h4>
                    <ul class="space-y-1">
                        <li>• Perfect for sharing with financial advisors</li>
                        <li>• Professional presentation format</li>
                        <li>• Includes visual charts and recommendations</li>
                        <li>• Ready to print or email</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-medium mb-2">CSV Data:</h4>
                    <ul class="space-y-1">
                        <li>• Import into Excel or Google Sheets</li>
                        <li>• Create custom charts and analysis</li>
                        <li>• Integrate with budgeting software</li>
                        <li>• Perform advanced calculations</li>
                    </ul>
                </div>
            </div>
        </div>

        <?php endif; ?>

        <!-- Back to Dashboard -->
        <div class="mt-8 text-center">
            <a href="../dashboard.php" class="inline-flex items-center px-4 py-2 text-gray-600 hover:text-gray-900 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-16">
        <div class="container mx-auto px-4 py-8 text-center text-gray-600">
            <p>&copy; <?php echo date('Y'); ?> CashControl. Your subscription data, securely managed.</p>
        </div>
    </footer>
</body>
</html>
