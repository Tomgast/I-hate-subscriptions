<?php
session_start();
require_once '../includes/plan_manager.php';
require_once '../config/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/signin.php');
    exit;
}

$userId = $_SESSION['user_id'];
$planManager = getPlanManager();

// Check if user has access to unsubscribe guides
if (!$planManager->canAccessFeature($userId, 'unsubscribe_guides')) {
    header('Location: ../upgrade.php?error=guides_required');
    exit;
}

$pdo = getDBConnection();

// Get search query if provided
$searchQuery = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

// Build search conditions
$whereConditions = ['is_active = 1'];
$params = [];

if (!empty($searchQuery)) {
    $whereConditions[] = '(service_name LIKE ? OR description LIKE ?)';
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

if (!empty($category)) {
    $whereConditions[] = 'category = ?';
    $params[] = $category;
}

$whereClause = implode(' AND ', $whereConditions);

// Get guides
$stmt = $pdo->prepare("
    SELECT * FROM unsubscribe_guides 
    WHERE $whereClause 
    ORDER BY service_name ASC
");
$stmt->execute($params);
$guides = $stmt->fetchAll();

// Get categories for filter
$stmt = $pdo->query("SELECT DISTINCT category FROM unsubscribe_guides WHERE is_active = 1 ORDER BY category");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe Guides - CashControl</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include '../includes/header.php'; ?>
    
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-book-open text-green-600 mr-3"></i>
                Unsubscribe Guides
            </h1>
            <p class="text-gray-600">Step-by-step instructions to cancel your subscriptions</p>
        </div>

        <!-- Search and Filter -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <form method="GET" class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search Services</label>
                    <div class="relative">
                        <input type="text" 
                               id="search" 
                               name="search" 
                               value="<?= htmlspecialchars($searchQuery) ?>"
                               placeholder="Search for Netflix, Spotify, etc..."
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>
                
                <div class="md:w-48">
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select id="category" 
                            name="category"
                            class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="md:w-32 flex items-end">
                    <button type="submit" 
                            class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                </div>
            </form>
        </div>

        <!-- Results Summary -->
        <?php if (!empty($searchQuery) || !empty($category)): ?>
            <div class="mb-6">
                <p class="text-gray-600">
                    Found <?= count($guides) ?> guide<?= count($guides) !== 1 ? 's' : '' ?>
                    <?php if (!empty($searchQuery)): ?>
                        for "<?= htmlspecialchars($searchQuery) ?>"
                    <?php endif; ?>
                    <?php if (!empty($category)): ?>
                        in <?= htmlspecialchars($category) ?>
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Guides Grid -->
        <?php if (empty($guides)): ?>
            <div class="text-center py-12">
                <i class="fas fa-search text-gray-300 text-6xl mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No guides found</h3>
                <p class="text-gray-600 mb-4">Try adjusting your search terms or browse all categories.</p>
                <a href="index.php" class="text-green-600 hover:text-green-700 font-medium">
                    <i class="fas fa-arrow-left mr-2"></i>View all guides
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($guides as $guide): ?>
                    <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow border border-gray-200">
                        <div class="p-6">
                            <!-- Service Header -->
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-tv text-gray-600 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($guide['service_name']) ?></h3>
                                    <p class="text-sm text-gray-500"><?= htmlspecialchars($guide['category']) ?></p>
                                </div>
                            </div>

                            <!-- Description -->
                            <p class="text-gray-600 text-sm mb-4 line-clamp-2"><?= htmlspecialchars($guide['description']) ?></p>

                            <!-- Metadata -->
                            <div class="flex items-center justify-between mb-4 text-sm">
                                <div class="flex items-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php
                                        switch($guide['difficulty']) {
                                            case 'Easy': echo 'bg-green-100 text-green-800'; break;
                                            case 'Medium': echo 'bg-yellow-100 text-yellow-800'; break;
                                            case 'Hard': echo 'bg-red-100 text-red-800'; break;
                                        }
                                        ?>">
                                        <?= htmlspecialchars($guide['difficulty']) ?>
                                    </span>
                                </div>
                                <div class="text-gray-500">
                                    <i class="fas fa-clock mr-1"></i>
                                    <?= htmlspecialchars($guide['estimated_time']) ?>
                                </div>
                            </div>

                            <!-- Action Button -->
                            <a href="view.php?guide=<?= urlencode($guide['service_slug']) ?>" 
                               class="w-full bg-green-600 text-white text-center py-2 px-4 rounded-lg hover:bg-green-700 transition-colors inline-block">
                                <i class="fas fa-book-open mr-2"></i>
                                View Guide
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Popular Categories -->
        <?php if (empty($searchQuery) && empty($category)): ?>
            <div class="mt-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Browse by Category</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <?php foreach ($categories as $cat): ?>
                        <a href="?category=<?= urlencode($cat) ?>" 
                           class="bg-white p-4 rounded-lg shadow-sm hover:shadow-md transition-shadow text-center border border-gray-200">
                            <i class="fas fa-folder text-green-600 text-2xl mb-2"></i>
                            <p class="font-medium text-gray-900"><?= htmlspecialchars($cat) ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
