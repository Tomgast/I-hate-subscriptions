<?php
/**
 * BANK SCAN ADMINISTRATION
 * Admin interface for managing automatic bank scans
 */

session_start();
require_once '../config/db_config.php';
require_once '../includes/bank_scheduler.php';
require_once '../includes/bank_service.php';

// Simple admin authentication
$adminPassword = 'admin123';
$isAuthenticated = isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;

if ($_POST && isset($_POST['admin_password'])) {
    if ($_POST['admin_password'] === $adminPassword) {
        $_SESSION['admin_authenticated'] = true;
        $isAuthenticated = true;
    } else {
        $error = 'Invalid password';
    }
}

if ($_POST && isset($_POST['logout'])) {
    $_SESSION['admin_authenticated'] = false;
    $isAuthenticated = false;
}

if (!$isAuthenticated) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Scan Admin - CashControl</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-md w-96">
            <h1 class="text-2xl font-bold mb-6 text-center">Bank Scan Admin</h1>
            
            <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Admin Password:</label>
                    <input type="password" name="admin_password" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500" required>
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700">
                    Login
                </button>
            </form>
        </div>
    </div>
</body>
</html>
<?php
    exit;
}

// Handle admin actions
$message = '';
$scheduler = new BankScheduler();
$bankService = new BankService();

if ($_POST && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'process_automatic_scans':
            $results = $scheduler->processAutomaticScans();
            $message = "Processed {$results['checked_users']} users, scheduled {$results['scans_scheduled']} scans";
            if (!empty($results['errors'])) {
                $message .= ". Errors: " . implode(', ', $results['errors']);
            }
            break;
            
        case 'execute_scheduled_scans':
            $results = $scheduler->executeScheduledScans();
            $message = "Executed {$results['processed_scans']} scans: {$results['successful_scans']} successful, {$results['failed_scans']} failed";
            if (!empty($results['errors'])) {
                $message .= ". Errors: " . implode(', ', $results['errors']);
            }
            break;
    }
}

// Get current statistics
$stats = $scheduler->getSchedulerStats();

// Get recent scan activity
try {
    $pdo = getDBConnection();
    
    // Recent scans
    $stmt = $pdo->prepare("
        SELECT bs.*, u.email, u.name, u.subscription_type
        FROM bank_scans bs
        JOIN users u ON bs.user_id = u.id
        ORDER BY bs.started_at DESC
        LIMIT 20
    ");
    $stmt->execute();
    $recentScans = $stmt->fetchAll();
    
    // Users needing scans
    $stmt = $pdo->prepare("
        SELECT u.id, u.email, u.name, u.subscription_type, 
               MAX(bs.completed_at) as last_scan_date
        FROM users u
        LEFT JOIN bank_scans bs ON u.id = bs.user_id AND bs.status = 'completed'
        WHERE u.subscription_type IN ('monthly', 'yearly') 
        AND u.subscription_status = 'active'
        GROUP BY u.id
        HAVING last_scan_date IS NULL OR last_scan_date < DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY last_scan_date ASC
        LIMIT 10
    ");
    $stmt->execute();
    $usersNeedingScans = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Scan Admin - CashControl</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <h1 class="text-2xl font-bold text-gray-900">Bank Scan Administration</h1>
                    <form method="POST" class="inline">
                        <input type="hidden" name="logout" value="1">
                        <button type="submit" class="text-red-600 hover:text-red-800">Logout</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            
            <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <!-- Statistics Dashboard -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-2xl font-bold text-blue-600"><?php echo $stats['total_subscription_users']; ?></div>
                    <div class="text-gray-600">Subscription Users</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-2xl font-bold text-orange-600"><?php echo $stats['users_needing_scans']; ?></div>
                    <div class="text-gray-600">Need Scans</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-2xl font-bold text-purple-600"><?php echo $stats['pending_scans']; ?></div>
                    <div class="text-gray-600">Pending Scans</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-2xl font-bold text-green-600"><?php echo $stats['recent_scans']; ?></div>
                    <div class="text-gray-600">Recent Scans (7d)</div>
                </div>
            </div>

            <!-- Admin Actions -->
            <div class="bg-white rounded-lg shadow mb-8">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold">Admin Actions</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="process_automatic_scans">
                            <button type="submit" class="w-full bg-blue-600 text-white px-4 py-3 rounded-lg hover:bg-blue-700 font-semibold">
                                🔄 Process Automatic Scans
                            </button>
                        </form>
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="execute_scheduled_scans">
                            <button type="submit" class="w-full bg-green-600 text-white px-4 py-3 rounded-lg hover:bg-green-700 font-semibold">
                                ⚡ Execute Scheduled Scans
                            </button>
                        </form>
                    </div>
                    
                    <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                        <h3 class="font-semibold mb-2">Cron Job Setup:</h3>
                        <p class="text-sm text-gray-600 mb-2">Add these cron jobs to your server:</p>
                        <div class="bg-gray-800 text-green-400 p-3 rounded font-mono text-sm">
                            # Schedule scans daily at 2 AM<br>
                            0 2 * * * /usr/bin/php <?php echo __DIR__; ?>/../includes/bank_scheduler.php schedule<br><br>
                            # Execute scans every 30 minutes<br>
                            */30 * * * * /usr/bin/php <?php echo __DIR__; ?>/../includes/bank_scheduler.php execute
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Needing Scans -->
            <?php if (!empty($usersNeedingScans)): ?>
            <div class="bg-white rounded-lg shadow mb-8">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold">Users Needing Scans</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Scan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Days Since</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($usersNeedingScans as $user): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        <?php echo ucfirst($user['subscription_type']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $user['last_scan_date'] ? date('M j, Y', strtotime($user['last_scan_date'])) : 'Never'; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php 
                                    if ($user['last_scan_date']) {
                                        $days = floor((time() - strtotime($user['last_scan_date'])) / (60 * 60 * 24));
                                        echo $days . ' days';
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Scan Activity -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold">Recent Scan Activity</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Started</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subscriptions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($recentScans as $scan): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($scan['name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($scan['email']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        <?php echo ucfirst($scan['subscription_type']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statusColors = [
                                        'completed' => 'bg-green-100 text-green-800',
                                        'failed' => 'bg-red-100 text-red-800',
                                        'in_progress' => 'bg-blue-100 text-blue-800',
                                        'scheduled' => 'bg-yellow-100 text-yellow-800',
                                        'initiated' => 'bg-gray-100 text-gray-800'
                                    ];
                                    $colorClass = $statusColors[$scan['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $colorClass; ?>">
                                        <?php echo ucfirst($scan['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('M j, Y g:i A', strtotime($scan['started_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $scan['subscriptions_found'] ?? 0; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
