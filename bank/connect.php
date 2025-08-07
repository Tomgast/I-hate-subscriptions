<?php
session_start();
require_once '../includes/bank_service.php';
require_once '../includes/plan_manager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/signin.php');
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $bankService = new BankService();
    $planManager = getPlanManager();
    
    // Check if user has access to bank scan feature
    if (!$planManager->canAccessFeature($userId, 'bank_scan')) {
        header('Location: ../upgrade.php?error=bank_scan_required');
        exit;
    }
    
    // Check if user has scans remaining
    if (!$planManager->hasScansRemaining($userId)) {
        header('Location: ../dashboard.php?error=scan_limit_reached');
        exit;
    }
    
    // Get user's plan type for scan initiation
    $userPlan = $planManager->getUserPlan($userId);
    $planType = $userPlan['plan_type'] ?? 'one_time_scan';
    
    // Initiate bank connection and get authorization URL
    $authUrl = $bankService->initiateBankConnection($userId, $planType);
    
    // Redirect to TrueLayer authorization
    header('Location: ' . $authUrl);
    exit;
    
} catch (Exception $e) {
    error_log('Bank connection error: ' . $e->getMessage());
    header('Location: ../dashboard.php?error=' . urlencode($e->getMessage()));
    exit;
}
?>
