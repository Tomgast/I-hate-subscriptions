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

$guideSlug = $_GET['guide'] ?? '';
if (empty($guideSlug)) {
    header('Location: index.php');
    exit;
}

$pdo = getDBConnection();

// Get the specific guide
$stmt = $pdo->prepare("SELECT * FROM unsubscribe_guides WHERE service_slug = ? AND is_active = 1");
$stmt->execute([$guideSlug]);
$guide = $stmt->fetch();

if (!$guide) {
    header('Location: index.php?error=guide_not_found');
    exit;
}

// Decode JSON fields
$steps = json_decode($guide['steps'], true) ?? [];
$contactInfo = json_decode($guide['contact_info'], true) ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($guide['service_name']) ?> Unsubscribe Guide - CashControl</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include '../includes/header.php'; ?>
    
    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="index.php" class="inline-flex items-center text-green-600 hover:text-green-700 font-medium">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Guides
            </a>
        </div>

        <!-- Guide Header -->
        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <div class="flex items-start justify-between mb-6">
                <div class="flex items-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center mr-6">
                        <i class="fas fa-tv text-gray-600 text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">
                            How to Cancel <?= htmlspecialchars($guide['service_name']) ?>
                        </h1>
                        <p class="text-gray-600 text-lg"><?= htmlspecialchars($guide['description']) ?></p>
                    </div>
                </div>
                
                <?php if (!empty($guide['website_url'])): ?>
                    <a href="<?= htmlspecialchars($guide['website_url']) ?>" 
                       target="_blank"
                       class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-external-link-alt mr-2"></i>
                        Visit Site
                    </a>
                <?php endif; ?>
            </div>

            <!-- Metadata -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-gray-50 rounded-lg">
                <div class="text-center">
                    <div class="text-sm text-gray-500 mb-1">Difficulty</div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
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
                <div class="text-center">
                    <div class="text-sm text-gray-500 mb-1">Estimated Time</div>
                    <div class="font-medium text-gray-900">
                        <i class="fas fa-clock mr-1"></i>
                        <?= htmlspecialchars($guide['estimated_time']) ?>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-sm text-gray-500 mb-1">Category</div>
                    <div class="font-medium text-gray-900">
                        <i class="fas fa-tag mr-1"></i>
                        <?= htmlspecialchars($guide['category']) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Steps -->
        <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">
                <i class="fas fa-list-ol text-green-600 mr-3"></i>
                Step-by-Step Instructions
            </h2>
            
            <div class="space-y-6">
                <?php foreach ($steps as $index => $step): ?>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center font-bold mr-4">
                            <?= $index + 1 ?>
                        </div>
                        <div class="flex-1">
                            <p class="text-gray-900 text-lg leading-relaxed"><?= htmlspecialchars($step) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Tips and Warnings -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <?php if (!empty($guide['tips'])): ?>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-blue-900 mb-3">
                        <i class="fas fa-lightbulb mr-2"></i>
                        Helpful Tips
                    </h3>
                    <p class="text-blue-800"><?= htmlspecialchars($guide['tips']) ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($guide['warnings'])): ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-yellow-900 mb-3">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Important Notes
                    </h3>
                    <p class="text-yellow-800"><?= htmlspecialchars($guide['warnings']) ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Contact Information -->
        <?php if (!empty($contactInfo)): ?>
            <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">
                    <i class="fas fa-phone text-green-600 mr-3"></i>
                    Need Help? Contact <?= htmlspecialchars($guide['service_name']) ?>
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php if (!empty($contactInfo['phone']) && $contactInfo['phone'] !== 'Not available'): ?>
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <i class="fas fa-phone text-green-600 text-2xl mb-2"></i>
                            <div class="font-medium text-gray-900 mb-1">Phone</div>
                            <a href="tel:<?= htmlspecialchars($contactInfo['phone']) ?>" 
                               class="text-green-600 hover:text-green-700">
                                <?= htmlspecialchars($contactInfo['phone']) ?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($contactInfo['chat']) && $contactInfo['chat'] !== 'Not available'): ?>
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <i class="fas fa-comments text-green-600 text-2xl mb-2"></i>
                            <div class="font-medium text-gray-900 mb-1">Live Chat</div>
                            <div class="text-gray-600 text-sm"><?= htmlspecialchars($contactInfo['chat']) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($contactInfo['email']) && $contactInfo['email'] !== 'Not available'): ?>
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <i class="fas fa-envelope text-green-600 text-2xl mb-2"></i>
                            <div class="font-medium text-gray-900 mb-1">Email</div>
                            <div class="text-gray-600 text-sm"><?= htmlspecialchars($contactInfo['email']) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Related Guides -->
        <div class="bg-white rounded-lg shadow-sm p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">
                <i class="fas fa-book text-green-600 mr-3"></i>
                More Guides in <?= htmlspecialchars($guide['category']) ?>
            </h2>
            
            <?php
            // Get related guides from same category
            $stmt = $pdo->prepare("
                SELECT service_name, service_slug, description, difficulty, estimated_time 
                FROM unsubscribe_guides 
                WHERE category = ? AND service_slug != ? AND is_active = 1 
                ORDER BY service_name ASC 
                LIMIT 3
            ");
            $stmt->execute([$guide['category'], $guide['service_slug']]);
            $relatedGuides = $stmt->fetchAll();
            ?>
            
            <?php if (empty($relatedGuides)): ?>
                <p class="text-gray-600">No other guides available in this category.</p>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php foreach ($relatedGuides as $related): ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <h3 class="font-semibold text-gray-900 mb-2"><?= htmlspecialchars($related['service_name']) ?></h3>
                            <p class="text-gray-600 text-sm mb-3 line-clamp-2"><?= htmlspecialchars($related['description']) ?></p>
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs px-2 py-1 rounded-full
                                    <?php
                                    switch($related['difficulty']) {
                                        case 'Easy': echo 'bg-green-100 text-green-800'; break;
                                        case 'Medium': echo 'bg-yellow-100 text-yellow-800'; break;
                                        case 'Hard': echo 'bg-red-100 text-red-800'; break;
                                    }
                                    ?>">
                                    <?= htmlspecialchars($related['difficulty']) ?>
                                </span>
                                <span class="text-xs text-gray-500"><?= htmlspecialchars($related['estimated_time']) ?></span>
                            </div>
                            <a href="view.php?guide=<?= urlencode($related['service_slug']) ?>" 
                               class="text-green-600 hover:text-green-700 text-sm font-medium">
                                View Guide →
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
