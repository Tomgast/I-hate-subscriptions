<?php
/**
 * PHASE 3C.4: INDIVIDUAL UNSUBSCRIBE GUIDE VIEWER
 * Display detailed step-by-step instructions for canceling specific services
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

// Get guide ID
$guideId = $_GET['id'] ?? null;
$serviceName = $_GET['service'] ?? null;

if (!$guideId && !$serviceName) {
    header('Location: index.php');
    exit;
}

// Initialize unsubscribe service
$unsubscribeService = getUnsubscribeService();

// Get guide data
if ($guideId) {
    // Get specific guide by ID
    $stmt = getDbConnection()->prepare("SELECT * FROM unsubscribe_guides WHERE id = ?");
    $stmt->execute([$guideId]);
    $guide = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    // Get guide by service name
    $guide = $unsubscribeService->getUnsubscribeGuide($serviceName);
}

if (!$guide) {
    header('Location: index.php?error=guide_not_found');
    exit;
}

// Track guide usage
if ($guide['id'] > 0) {
    $unsubscribeService->trackGuideUsage($guide['id'], $userId);
}

// Parse JSON fields
$steps = json_decode($guide['steps'] ?? '[]', true) ?: [];
$tips = json_decode($guide['tips'] ?? '[]', true) ?: [];
$commonIssues = json_decode($guide['common_issues'] ?? '[]', true) ?: [];
$contactInfo = json_decode($guide['contact_info'] ?? '{}', true) ?: [];

$pageTitle = 'How to Cancel ' . $guide['service_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - CashControl</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .step-number {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        .progress-bar {
            transition: width 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <?php include '../includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Back Navigation -->
        <div class="mb-6">
            <a href="index.php" class="inline-flex items-center text-gray-600 hover:text-gray-900 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Unsubscribe Guides
            </a>
        </div>

        <!-- Guide Header -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8">
            <div class="p-8">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">
                            How to Cancel <?php echo htmlspecialchars($guide['service_name']); ?>
                        </h1>
                        <p class="text-lg text-gray-600 mb-4">
                            <?php echo htmlspecialchars($guide['description']); ?>
                        </p>
                    </div>
                    <?php if ($guide['is_featured']): ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                        <i class="fas fa-star mr-1"></i>
                        Featured Guide
                    </span>
                    <?php endif; ?>
                </div>
                
                <!-- Guide Metadata -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600 mb-1">
                            <?php echo htmlspecialchars($guide['difficulty']); ?>
                        </div>
                        <div class="text-sm text-gray-600">Difficulty</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600 mb-1">
                            <?php echo htmlspecialchars($guide['estimated_time'] ?? '5-10 min'); ?>
                        </div>
                        <div class="text-sm text-gray-600">Est. Time</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600 mb-1">
                            <?php echo count($steps); ?>
                        </div>
                        <div class="text-sm text-gray-600">Steps</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600 mb-1">
                            <?php echo htmlspecialchars($guide['category']); ?>
                        </div>
                        <div class="text-sm text-gray-600">Category</div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Progress</span>
                        <span class="text-sm text-gray-500" id="progress-text">0 of <?php echo count($steps); ?> steps completed</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="progress-bar bg-green-600 h-2 rounded-full" style="width: 0%" id="progress-bar"></div>
                    </div>
                </div>

                <!-- Website Link -->
                <?php if (!empty($guide['website_url'])): ?>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-external-link-alt text-blue-600 mr-3"></i>
                        <div>
                            <p class="text-sm text-blue-800 mb-1">
                                <strong>Official Website:</strong>
                            </p>
                            <a href="<?php echo htmlspecialchars($guide['website_url']); ?>" 
                               target="_blank" 
                               class="text-blue-700 hover:text-blue-900 underline">
                                <?php echo htmlspecialchars($guide['website_url']); ?>
                                <i class="fas fa-external-link-alt ml-1 text-xs"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Step-by-Step Instructions -->
        <?php if (!empty($steps)): ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8">
            <div class="p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">
                    <i class="fas fa-list-ol text-green-600 mr-3"></i>
                    Step-by-Step Instructions
                </h2>
                
                <div class="space-y-6">
                    <?php foreach ($steps as $index => $step): ?>
                    <div class="flex items-start step-item" data-step="<?php echo $index + 1; ?>">
                        <!-- Step Number -->
                        <div class="flex-shrink-0 mr-4">
                            <div class="step-number w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                <?php echo $step['step'] ?? ($index + 1); ?>
                            </div>
                        </div>
                        
                        <!-- Step Content -->
                        <div class="flex-1">
                            <div class="bg-gray-50 rounded-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                    <?php echo htmlspecialchars($step['title']); ?>
                                </h3>
                                <p class="text-gray-700 mb-3">
                                    <?php echo htmlspecialchars($step['description']); ?>
                                </p>
                                <?php if (!empty($step['details'])): ?>
                                <div class="bg-white border border-gray-200 rounded p-3">
                                    <p class="text-sm text-gray-600">
                                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                                        <?php echo htmlspecialchars($step['details']); ?>
                                    </p>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Step Completion Checkbox -->
                                <div class="mt-4">
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" 
                                               class="step-checkbox form-checkbox h-5 w-5 text-green-600 rounded focus:ring-green-500" 
                                               data-step="<?php echo $index + 1; ?>">
                                        <span class="ml-2 text-sm text-gray-700">Mark as completed</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tips Section -->
        <?php if (!empty($tips)): ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8">
            <div class="p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">
                    <i class="fas fa-lightbulb text-yellow-500 mr-3"></i>
                    Helpful Tips
                </h2>
                
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                    <ul class="space-y-3">
                        <?php foreach ($tips as $tip): ?>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-yellow-600 mr-3 mt-0.5 flex-shrink-0"></i>
                            <span class="text-yellow-800"><?php echo htmlspecialchars($tip); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Common Issues Section -->
        <?php if (!empty($commonIssues)): ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8">
            <div class="p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">
                    <i class="fas fa-exclamation-triangle text-orange-500 mr-3"></i>
                    Common Issues & Solutions
                </h2>
                
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-6">
                    <ul class="space-y-4">
                        <?php foreach ($commonIssues as $issue): ?>
                        <li class="flex items-start">
                            <i class="fas fa-triangle-exclamation text-orange-600 mr-3 mt-0.5 flex-shrink-0"></i>
                            <span class="text-orange-800"><?php echo htmlspecialchars($issue); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Contact Information -->
        <?php if (!empty($contactInfo)): ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8">
            <div class="p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">
                    <i class="fas fa-phone text-blue-500 mr-3"></i>
                    Need More Help? Contact Support
                </h2>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <?php if (isset($contactInfo['phone'])): ?>
                    <div class="mb-4">
                        <strong class="text-blue-900">Phone:</strong>
                        <span class="text-blue-800 ml-2"><?php echo htmlspecialchars($contactInfo['phone']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($contactInfo['email'])): ?>
                    <div class="mb-4">
                        <strong class="text-blue-900">Email:</strong>
                        <a href="mailto:<?php echo htmlspecialchars($contactInfo['email']); ?>" 
                           class="text-blue-700 hover:text-blue-900 underline ml-2">
                            <?php echo htmlspecialchars($contactInfo['email']); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($contactInfo['chat_url'])): ?>
                    <div class="mb-4">
                        <strong class="text-blue-900">Live Chat:</strong>
                        <a href="<?php echo htmlspecialchars($contactInfo['chat_url']); ?>" 
                           target="_blank"
                           class="text-blue-700 hover:text-blue-900 underline ml-2">
                            Start Chat <i class="fas fa-external-link-alt ml-1 text-xs"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($contactInfo['details'])): ?>
                    <p class="text-blue-800 text-sm">
                        <i class="fas fa-info-circle mr-2"></i>
                        <?php echo htmlspecialchars($contactInfo['details']); ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Success Message -->
        <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-8" id="success-message" style="display: none;">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-600 text-2xl mr-4"></i>
                <div>
                    <h3 class="text-lg font-semibold text-green-900 mb-1">Congratulations!</h3>
                    <p class="text-green-800">
                        You've completed all steps to cancel <?php echo htmlspecialchars($guide['service_name']); ?>. 
                        Make sure to save any confirmation emails as proof of cancellation.
                    </p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="index.php" 
               class="inline-flex items-center justify-center px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors font-medium">
                <i class="fas fa-list mr-2"></i>
                View More Guides
            </a>
            <a href="../dashboard.php" 
               class="inline-flex items-center justify-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                <i class="fas fa-tachometer-alt mr-2"></i>
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

    <!-- JavaScript for Progress Tracking -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.step-checkbox');
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            const successMessage = document.getElementById('success-message');
            const totalSteps = checkboxes.length;
            
            function updateProgress() {
                const completedSteps = document.querySelectorAll('.step-checkbox:checked').length;
                const percentage = totalSteps > 0 ? (completedSteps / totalSteps) * 100 : 0;
                
                progressBar.style.width = percentage + '%';
                progressText.textContent = `${completedSteps} of ${totalSteps} steps completed`;
                
                // Show success message when all steps are completed
                if (completedSteps === totalSteps && totalSteps > 0) {
                    successMessage.style.display = 'block';
                    successMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    successMessage.style.display = 'none';
                }
            }
            
            // Add event listeners to checkboxes
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateProgress);
            });
            
            // Initialize progress
            updateProgress();
        });
    </script>
</body>
</html>
