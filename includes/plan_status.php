<?php
/**
 * PHASE 3B.4: PLAN STATUS COMPONENT
 * Displays user's current plan status, usage, and management options
 */

require_once __DIR__ . '/plan_manager.php';

/**
 * Display plan status widget for dashboard
 * @param int $userId User ID
 * @param bool $showUpgrade Whether to show upgrade options
 * @return string HTML output
 */
function displayPlanStatus($userId, $showUpgrade = true) {
    $planManager = getPlanManager();
    $userPlan = $planManager->getUserPlan($userId);
    
    if (!$userPlan || !$userPlan['is_active']) {
        return displayNoPlanStatus($showUpgrade);
    }
    
    $displayInfo = $planManager->getPlanDisplayInfo($userPlan['plan_type']);
    $upgradeSuggestions = $planManager->getUpgradeSuggestions($userId);
    
    ob_start();
    ?>
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Your Plan</h3>
            <span class="<?php echo $displayInfo['badge_class']; ?> text-sm font-medium px-3 py-1 rounded-full">
                <?php echo htmlspecialchars($displayInfo['name']); ?>
            </span>
        </div>
        
        <div class="space-y-4">
            <!-- Plan Details -->
            <div class="flex items-center justify-between">
                <span class="text-gray-600">Price</span>
                <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($displayInfo['price']); ?></span>
            </div>
            
            <?php if ($userPlan['plan_expires_at']): ?>
            <div class="flex items-center justify-between">
                <span class="text-gray-600">
                    <?php echo $userPlan['plan_type'] === 'monthly' ? 'Next billing' : 'Expires'; ?>
                </span>
                <span class="font-semibold text-gray-900">
                    <?php echo date('M j, Y', strtotime($userPlan['plan_expires_at'])); ?>
                </span>
            </div>
            <?php endif; ?>
            
            <!-- Usage Information -->
            <?php if ($userPlan['plan_type'] === 'onetime'): ?>
            <div class="flex items-center justify-between">
                <span class="text-gray-600">Bank scans</span>
                <span class="font-semibold <?php echo $userPlan['scans_remaining'] > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                    <?php echo $userPlan['scan_count']; ?> / <?php echo $userPlan['max_scans']; ?> used
                </span>
            </div>
            <?php else: ?>
            <div class="flex items-center justify-between">
                <span class="text-gray-600">Bank scans</span>
                <span class="font-semibold text-green-600">
                    <?php echo $userPlan['scan_count']; ?> / Unlimited
                </span>
            </div>
            <?php endif; ?>
            
            <!-- Features List -->
            <div class="border-t pt-4">
                <h4 class="text-sm font-medium text-gray-900 mb-3">Included Features</h4>
                <div class="space-y-2">
                    <?php
                    $features = [];
                    switch ($userPlan['plan_type']) {
                        case 'monthly':
                            $features = [
                                'Unlimited bank scans',
                                'Real-time analytics',
                                'Email notifications',
                                'Subscription management'
                            ];
                            break;
                        case 'yearly':
                            $features = [
                                'Unlimited bank scans',
                                'Real-time analytics',
                                'Email notifications',
                                'Subscription management',
                                'Priority support',
                                'Advanced reporting'
                            ];
                            break;
                        case 'onetime':
                            $features = [
                                'Single bank scan',
                                'PDF/CSV export',
                                'Unsubscribe guides'
                            ];
                            break;
                    }
                    
                    foreach ($features as $feature):
                    ?>
                    <div class="flex items-center text-sm">
                        <svg class="w-4 h-4 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-gray-700"><?php echo htmlspecialchars($feature); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="border-t pt-4 space-y-2">
                <?php if ($userPlan['stripe_subscription_id']): ?>
                <!-- Subscription Management -->
                <a href="account/manage.php" class="block w-full bg-gray-100 text-gray-800 text-center py-2 px-4 rounded-lg font-medium hover:bg-gray-200 transition-colors">
                    Manage Subscription
                </a>
                <?php endif; ?>
                
                <?php if (!empty($upgradeSuggestions) && $showUpgrade): ?>
                <!-- Upgrade Options -->
                <div class="space-y-2">
                    <?php foreach ($upgradeSuggestions as $planType => $suggestion): ?>
                    <a href="upgrade.php?from=<?php echo $userPlan['plan_type']; ?>&to=<?php echo $planType; ?>" 
                       class="block w-full bg-blue-50 text-blue-700 text-center py-2 px-4 rounded-lg font-medium hover:bg-blue-100 transition-colors text-sm">
                        <?php echo htmlspecialchars($suggestion); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Support -->
                <a href="mailto:support@123cashcontrol.com" class="block w-full text-gray-600 text-center py-2 px-4 rounded-lg font-medium hover:bg-gray-50 transition-colors text-sm">
                    Contact Support
                </a>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Display status for users without active plan
 * @param bool $showUpgrade Whether to show upgrade options
 * @return string HTML output
 */
function displayNoPlanStatus($showUpgrade = true) {
    ob_start();
    ?>
    <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-6">
        <div class="text-center">
            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-yellow-800 mb-2">No Active Plan</h3>
            <p class="text-yellow-700 mb-4">You need an active plan to access CashControl features.</p>
            
            <?php if ($showUpgrade): ?>
            <a href="upgrade.php" class="inline-block bg-yellow-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-yellow-700 transition-colors">
                Choose Your Plan
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Display compact plan badge for headers
 * @param int $userId User ID
 * @return string HTML output
 */
function displayPlanBadge($userId) {
    $planManager = getPlanManager();
    $userPlan = $planManager->getUserPlan($userId);
    
    if (!$userPlan || !$userPlan['is_active']) {
        return '<span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded">No Plan</span>';
    }
    
    $displayInfo = $planManager->getPlanDisplayInfo($userPlan['plan_type']);
    
    return '<span class="' . $displayInfo['badge_class'] . ' text-xs font-medium px-2.5 py-0.5 rounded">' . 
           htmlspecialchars($displayInfo['name']) . '</span>';
}

/**
 * Display usage warning for one-time users
 * @param int $userId User ID
 * @return string HTML output or empty string
 */
function displayUsageWarning($userId) {
    $planManager = getPlanManager();
    $userPlan = $planManager->getUserPlan($userId);
    
    if (!$userPlan || $userPlan['plan_type'] !== 'onetime') {
        return '';
    }
    
    if ($userPlan['scans_remaining'] <= 0) {
        ob_start();
        ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                <div>
                    <div class="font-medium">Scan limit reached</div>
                    <div class="text-sm">You've used your one-time bank scan. Upgrade for unlimited scans.</div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    return '';
}

/**
 * Display plan expiration warning
 * @param int $userId User ID
 * @return string HTML output or empty string
 */
function displayExpirationWarning($userId) {
    $planManager = getPlanManager();
    $userPlan = $planManager->getUserPlan($userId);
    
    if (!$userPlan || !$userPlan['plan_expires_at'] || $userPlan['plan_type'] === 'onetime') {
        return '';
    }
    
    $daysUntilExpiration = (strtotime($userPlan['plan_expires_at']) - time()) / (24 * 60 * 60);
    
    if ($daysUntilExpiration <= 7 && $daysUntilExpiration > 0) {
        ob_start();
        ?>
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-lg mb-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <div class="font-medium">Plan expires soon</div>
                    <div class="text-sm">
                        Your <?php echo $userPlan['plan_type']; ?> plan expires in <?php echo ceil($daysUntilExpiration); ?> days.
                        <a href="account/manage.php" class="underline hover:no-underline">Manage subscription</a>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    return '';
}
?>
