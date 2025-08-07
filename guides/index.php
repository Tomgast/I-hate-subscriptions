<?php
/**
 * PHASE 3C.4: UNSUBSCRIBE GUIDES INTERFACE
 * Main interface for browsing and searching unsubscribe guides
 */

session_start();
require_once '../config/db_config.php';
require_once '../includes/plan_manager.php';
require_once '../includes/unsubscribe_service.php';

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

// Verify user has an active plan and can access guides
if (!$userPlan || !$userPlan['is_active'] || !$planManager->canAccessFeature($userId, 'unsubscribe_guides')) {
    header('Location: ../upgrade.php?reason=no_guides_access');
    exit;
}

// Initialize unsubscribe service
$unsubscribeService = getUnsubscribeService();

// Handle search and filtering
$searchQuery = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$guides = [];

if ($searchQuery) {
    $guides = $unsubscribeService->searchGuides($searchQuery);
} elseif ($category) {
    $guides = $unsubscribeService->getGuidesByCategory($category);
} else {
    $guides = $unsubscribeService->getPopularGuides(20);
}

// Get recent guides for this user
$recentGuides = $unsubscribeService->getUserRecentGuides($userId, 5);

// Get all categories for filter
$allGuides = $unsubscribeService->getAllGuides(100);
$categories = array_unique(array_column($allGuides, 'category'));
sort($categories);

$pageTitle = 'Unsubscribe Guides';
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
                        <i class="fas fa-times-circle text-red-600 mr-3"></i>
                        Unsubscribe Guides
                    </h1>
                    <p class="text-gray-600">Step-by-step instructions to cancel unwanted subscriptions</p>
                </div>
                <div class="text-right">
                    <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <i class="fas fa-crown mr-1"></i>
                        <?php echo ucfirst($userPlan['plan_type']); ?> Plan
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8">
            <div class="p-6">
                <form method="GET" class="space-y-4">
                    <div class="flex flex-col md:flex-row gap-4">
                        <!-- Search Input -->
                        <div class="flex-1">
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
                                Search Services
                            </label>
                            <div class="relative">
                                <input 
                                    type="text" 
                                    id="search" 
                                    name="search" 
                                    value="<?php echo htmlspecialchars($searchQuery); ?>"
                                    placeholder="Search for Netflix, Spotify, Amazon Prime..."
                                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                >
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            </div>
                        </div>
                        
                        <!-- Category Filter -->
                        <div class="md:w-48">
                            <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                                Category
                            </label>
                            <select 
                                id="category" 
                                name="category"
                                class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            >
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Search Button -->
                        <div class="md:w-32 flex items-end">
                            <button 
                                type="submit"
                                class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium"
                            >
                                <i class="fas fa-search mr-2"></i>
                                Search
                            </button>
                        </div>
                    </div>
                </form>
                
                <!-- Quick Clear -->
                <?php if ($searchQuery || $category): ?>
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <a href="index.php" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
                        <i class="fas fa-times mr-1"></i>
                        Clear filters and show popular guides
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Guides (if user has used any) -->
        <?php if (!empty($recentGuides) && !$searchQuery && !$category): ?>
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">
                <i class="fas fa-history text-blue-600 mr-2"></i>
                Recently Used Guides
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($recentGuides as $guide): ?>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold text-blue-900"><?php echo htmlspecialchars($guide['service_name']); ?></h3>
                        <span class="text-xs text-blue-600 bg-blue-100 px-2 py-1 rounded">
                            <?php echo htmlspecialchars($guide['category']); ?>
                        </span>
                    </div>
                    <p class="text-sm text-blue-700 mb-3"><?php echo htmlspecialchars($guide['description']); ?></p>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-blue-600">
                            Used <?php echo date('M j', strtotime($guide['used_at'])); ?>
                        </span>
                        <a href="view.php?id=<?php echo $guide['id']; ?>" class="text-sm text-blue-700 hover:text-blue-900 font-medium">
                            View Guide <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Results Header -->
        <div class="mb-6">
            <h2 class="text-xl font-semibold text-gray-900">
                <?php if ($searchQuery): ?>
                    Search Results for "<?php echo htmlspecialchars($searchQuery); ?>"
                <?php elseif ($category): ?>
                    <?php echo htmlspecialchars($category); ?> Services
                <?php else: ?>
                    Popular Unsubscribe Guides
                <?php endif; ?>
                <span class="text-sm font-normal text-gray-600 ml-2">(<?php echo count($guides); ?> guides)</span>
            </h2>
        </div>

        <!-- Guides Grid -->
        <?php if (empty($guides)): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-8 text-center">
            <i class="fas fa-search text-yellow-600 text-4xl mb-4"></i>
            <h3 class="text-lg font-semibold text-yellow-800 mb-2">No Guides Found</h3>
            <p class="text-yellow-700 mb-4">
                <?php if ($searchQuery): ?>
                    We couldn't find any guides matching "<?php echo htmlspecialchars($searchQuery); ?>".
                <?php else: ?>
                    No guides available for the selected category.
                <?php endif; ?>
            </p>
            <div class="space-x-4">
                <a href="index.php" class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
                    <i class="fas fa-home mr-2"></i>
                    View Popular Guides
                </a>
                <a href="request.php" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>
                    Request New Guide
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($guides as $guide): ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                <div class="p-6">
                    <!-- Service Header -->
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <?php echo htmlspecialchars($guide['service_name']); ?>
                        </h3>
                        <?php if ($guide['is_featured']): ?>
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                            <i class="fas fa-star mr-1"></i>
                            Featured
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Category and Difficulty -->
                    <div class="flex items-center gap-2 mb-3">
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800">
                            <?php echo htmlspecialchars($guide['category']); ?>
                        </span>
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
                    </div>
                    
                    <!-- Description -->
                    <p class="text-sm text-gray-600 mb-4">
                        <?php echo htmlspecialchars($guide['description']); ?>
                    </p>
                    
                    <!-- Time Estimate -->
                    <?php if ($guide['estimated_time']): ?>
                    <div class="flex items-center text-sm text-gray-500 mb-4">
                        <i class="fas fa-clock mr-2"></i>
                        <?php echo htmlspecialchars($guide['estimated_time']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Popularity Indicator -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center text-sm text-gray-500">
                            <i class="fas fa-users mr-1"></i>
                            <?php echo number_format($guide['usage_count'] ?? 0); ?> uses
                        </div>
                        <a href="view.php?id=<?php echo $guide['id']; ?>" 
                           class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium">
                            View Guide
                            <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Help Section -->
        <div class="mt-12 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-900 mb-3">
                <i class="fas fa-question-circle text-blue-600 mr-2"></i>
                Need Help?
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-blue-800">
                <div>
                    <h4 class="font-medium mb-2">Can't find your service?</h4>
                    <ul class="space-y-1">
                        <li>• Try searching with different keywords</li>
                        <li>• Check if it's listed under a different name</li>
                        <li>• <a href="request.php" class="text-blue-700 hover:text-blue-900 underline">Request a new guide</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-medium mb-2">Having trouble canceling?</h4>
                    <ul class="space-y-1">
                        <li>• Follow our step-by-step instructions</li>
                        <li>• Contact the service's customer support</li>
                        <li>• Consider disputing charges with your bank</li>
                    </ul>
                </div>
            </div>
        </div>

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
            <p>&copy; <?php echo date('Y'); ?> CashControl. Helping you take control of your subscriptions.</p>
        </div>
    </footer>
</body>
</html>
