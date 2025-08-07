<?php
/**
 * PHASE 3C.4: UNSUBSCRIBE GUIDES INITIALIZATION
 * Initialize unsubscribe guides database tables and seed with popular services
 */

session_start();
require_once '../config/db_config.php';
require_once '../includes/unsubscribe_service.php';

// Simple admin authentication
$adminPassword = 'admin123';
$authenticated = false;

if ($_POST && isset($_POST['password'])) {
    if ($_POST['password'] === $adminPassword) {
        $authenticated = true;
        $_SESSION['unsubscribe_admin'] = true;
    } else {
        $error = 'Invalid password';
    }
} elseif (isset($_SESSION['unsubscribe_admin'])) {
    $authenticated = true;
}

// Handle initialization
$initResult = null;
if ($authenticated && $_POST && isset($_POST['action'])) {
    $unsubscribeService = getUnsubscribeService();
    
    switch ($_POST['action']) {
        case 'init_tables':
            $initResult = $unsubscribeService->initializeGuidesTable();
            break;
            
        case 'seed_guides':
            $unsubscribeService->seedPopularGuides();
            $initResult = true;
            break;
            
        case 'full_init':
            $tableResult = $unsubscribeService->initializeGuidesTable();
            if ($tableResult) {
                $unsubscribeService->seedPopularGuides();
                $initResult = true;
            } else {
                $initResult = false;
            }
            break;
    }
}

// Get current status
$status = [];
if ($authenticated) {
    try {
        $db = getDbConnection();
        
        // Check if tables exist
        $stmt = $db->query("SHOW TABLES LIKE 'unsubscribe_guides'");
        $status['guides_table_exists'] = $stmt->rowCount() > 0;
        
        $stmt = $db->query("SHOW TABLES LIKE 'unsubscribe_guide_usage'");
        $status['usage_table_exists'] = $stmt->rowCount() > 0;
        
        // Count guides
        if ($status['guides_table_exists']) {
            $stmt = $db->query("SELECT COUNT(*) as count FROM unsubscribe_guides");
            $status['guides_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            $stmt = $db->query("SELECT COUNT(*) as count FROM unsubscribe_guides WHERE is_featured = 1");
            $status['featured_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        }
        
        // Count usage records
        if ($status['usage_table_exists']) {
            $stmt = $db->query("SELECT COUNT(*) as count FROM unsubscribe_guide_usage");
            $status['usage_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        }
        
    } catch (Exception $e) {
        $status['error'] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initialize Unsubscribe Guides - CashControl Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8">
                <div class="p-6">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-database text-blue-600 mr-3"></i>
                        Initialize Unsubscribe Guides System
                    </h1>
                    <p class="text-gray-600">Set up the database tables and seed with popular unsubscribe guides</p>
                </div>
            </div>

            <?php if (!$authenticated): ?>
            <!-- Authentication Form -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="p-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        <i class="fas fa-lock text-yellow-600 mr-2"></i>
                        Admin Authentication Required
                    </h2>
                    
                    <?php if (isset($error)): ?>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-600 mr-2"></i>
                            <span class="text-red-800"><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="space-y-4">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                Admin Password:
                            </label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Enter admin password"
                            >
                        </div>
                        <button 
                            type="submit"
                            class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium"
                        >
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Authenticate
                        </button>
                    </form>
                </div>
            </div>
            <?php else: ?>

            <!-- Initialization Results -->
            <?php if ($initResult !== null): ?>
            <div class="mb-8">
                <?php if ($initResult): ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-600 text-2xl mr-4"></i>
                        <div>
                            <h3 class="text-lg font-semibold text-green-900 mb-1">Success!</h3>
                            <p class="text-green-800">Unsubscribe guides system has been initialized successfully.</p>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-600 text-2xl mr-4"></i>
                        <div>
                            <h3 class="text-lg font-semibold text-red-900 mb-1">Error</h3>
                            <p class="text-red-800">Failed to initialize unsubscribe guides system. Check error logs.</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Current Status -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                        Current System Status
                    </h2>
                    
                    <?php if (isset($status['error'])): ?>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                        <p class="text-red-800">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Error checking status: <?php echo htmlspecialchars($status['error']); ?>
                        </p>
                    </div>
                    <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Database Tables -->
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-3">Database Tables</h3>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-700">Guides Table:</span>
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium <?php echo $status['guides_table_exists'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $status['guides_table_exists'] ? 'EXISTS' : 'MISSING'; ?>
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-700">Usage Table:</span>
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium <?php echo $status['usage_table_exists'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $status['usage_table_exists'] ? 'EXISTS' : 'MISSING'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Data Counts -->
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-3">Data Statistics</h3>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-700">Total Guides:</span>
                                    <span class="font-semibold text-gray-900">
                                        <?php echo $status['guides_count'] ?? 0; ?>
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-700">Featured Guides:</span>
                                    <span class="font-semibold text-gray-900">
                                        <?php echo $status['featured_count'] ?? 0; ?>
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-700">Usage Records:</span>
                                    <span class="font-semibold text-gray-900">
                                        <?php echo $status['usage_count'] ?? 0; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Initialization Actions -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        <i class="fas fa-cogs text-green-600 mr-2"></i>
                        Initialization Actions
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Initialize Tables -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-900 mb-2">1. Create Tables</h3>
                            <p class="text-sm text-gray-600 mb-4">Create the database tables for unsubscribe guides and usage tracking.</p>
                            <form method="POST">
                                <input type="hidden" name="action" value="init_tables">
                                <button 
                                    type="submit"
                                    class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium"
                                    <?php echo ($status['guides_table_exists'] && $status['usage_table_exists']) ? 'disabled' : ''; ?>
                                >
                                    <i class="fas fa-database mr-2"></i>
                                    Create Tables
                                </button>
                            </form>
                        </div>
                        
                        <!-- Seed Guides -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-900 mb-2">2. Seed Guides</h3>
                            <p class="text-sm text-gray-600 mb-4">Add popular unsubscribe guides (Netflix, Spotify, Amazon Prime, etc.).</p>
                            <form method="POST">
                                <input type="hidden" name="action" value="seed_guides">
                                <button 
                                    type="submit"
                                    class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium"
                                    <?php echo !($status['guides_table_exists'] ?? false) ? 'disabled' : ''; ?>
                                >
                                    <i class="fas fa-seedling mr-2"></i>
                                    Seed Guides
                                </button>
                            </form>
                        </div>
                        
                        <!-- Full Initialization -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-900 mb-2">3. Full Setup</h3>
                            <p class="text-sm text-gray-600 mb-4">Complete initialization: create tables and seed with popular guides.</p>
                            <form method="POST">
                                <input type="hidden" name="action" value="full_init">
                                <button 
                                    type="submit"
                                    class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm font-medium"
                                >
                                    <i class="fas fa-rocket mr-2"></i>
                                    Full Setup
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sample Guides Preview -->
            <?php if (($status['guides_count'] ?? 0) > 0): ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        <i class="fas fa-eye text-indigo-600 mr-2"></i>
                        Sample Guides Preview
                    </h2>
                    
                    <?php
                    try {
                        $stmt = getDbConnection()->query("SELECT * FROM unsubscribe_guides ORDER BY popularity DESC LIMIT 5");
                        $sampleGuides = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    } catch (Exception $e) {
                        $sampleGuides = [];
                    }
                    ?>
                    
                    <?php if (!empty($sampleGuides)): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-2 px-3 font-medium text-gray-900">Service</th>
                                    <th class="text-left py-2 px-3 font-medium text-gray-900">Category</th>
                                    <th class="text-left py-2 px-3 font-medium text-gray-900">Difficulty</th>
                                    <th class="text-left py-2 px-3 font-medium text-gray-900">Featured</th>
                                    <th class="text-left py-2 px-3 font-medium text-gray-900">Usage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sampleGuides as $guide): ?>
                                <tr class="border-b border-gray-100">
                                    <td class="py-2 px-3 font-medium text-gray-900">
                                        <?php echo htmlspecialchars($guide['service_name']); ?>
                                    </td>
                                    <td class="py-2 px-3 text-gray-600">
                                        <?php echo htmlspecialchars($guide['category']); ?>
                                    </td>
                                    <td class="py-2 px-3">
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium 
                                            <?php 
                                            switch($guide['difficulty']) {
                                                case 'Easy': echo 'bg-green-100 text-green-800'; break;
                                                case 'Medium': echo 'bg-yellow-100 text-yellow-800'; break;
                                                case 'Hard': echo 'bg-red-100 text-red-800'; break;
                                                default: echo 'bg-gray-100 text-gray-800';
                                            }
                                            ?>">
                                            <?php echo htmlspecialchars($guide['difficulty']); ?>
                                        </span>
                                    </td>
                                    <td class="py-2 px-3">
                                        <?php if ($guide['is_featured']): ?>
                                        <i class="fas fa-star text-yellow-500"></i>
                                        <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-2 px-3 text-gray-600">
                                        <?php echo number_format($guide['usage_count']); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-gray-600">No guides found in database.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Navigation Links -->
            <div class="text-center space-x-4">
                <a href="../guides/index.php" 
                   class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                    <i class="fas fa-list mr-2"></i>
                    View Guides Interface
                </a>
                <a href="../dashboard.php" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors font-medium">
                    <i class="fas fa-tachometer-alt mr-2"></i>
                    Back to Dashboard
                </a>
            </div>

            <?php endif; ?>
        </div>
    </div>
</body>
</html>
