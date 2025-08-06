<?php
session_start();
require_once '../includes/bank_integration.php';

// Check if user is logged in and has Pro access
if (!isset($_SESSION['user_id']) || !$_SESSION['is_paid']) {
    header('Location: ../upgrade.php');
    exit;
}

$userId = $_SESSION['user_id'];
$bankIntegration = new BankIntegration();

// Get authorization URL and redirect to bank
$authUrl = $bankIntegration->getAuthUrl($userId);
header('Location: ' . $authUrl);
exit;
?>
