<?php
session_start();
require_once '../config/db_config.php';
require_once '../includes/subscription_manager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];
$subscriptionManager = new SubscriptionManager();

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $data = [
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'cost' => floatval($_POST['cost'] ?? 0),
                'currency' => $_POST['currency'] ?? 'EUR',
                'billing_cycle' => $_POST['billing_cycle'] ?? 'monthly',
                'next_payment_date' => $_POST['next_payment_date'] ?? '',
                'category' => $_POST['category'] ?? 'Other',
                'website_url' => $_POST['website_url'] ?? '',
                'logo_url' => $_POST['logo_url'] ?? ''
            ];
            
            // Validate required fields
            if (empty($data['name']) || $data['cost'] <= 0 || empty($data['next_payment_date'])) {
                throw new Exception('Please fill in all required fields');
            }
            
            $result = $subscriptionManager->addSubscription($userId, $data);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Subscription added successfully']);
            } else {
                throw new Exception('Failed to add subscription');
            }
            break;
            
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $subscriptionId = intval($_POST['id'] ?? 0);
            if (!$subscriptionId) {
                throw new Exception('Invalid subscription ID');
            }
            
            $data = [
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'cost' => floatval($_POST['cost'] ?? 0),
                'currency' => $_POST['currency'] ?? 'EUR',
                'billing_cycle' => $_POST['billing_cycle'] ?? 'monthly',
                'next_payment_date' => $_POST['next_payment_date'] ?? '',
                'category' => $_POST['category'] ?? 'Other',
                'website_url' => $_POST['website_url'] ?? '',
                'logo_url' => $_POST['logo_url'] ?? ''
            ];
            
            // Validate required fields
            if (empty($data['name']) || $data['cost'] <= 0 || empty($data['next_payment_date'])) {
                throw new Exception('Please fill in all required fields');
            }
            
            $result = $subscriptionManager->updateSubscription($subscriptionId, $userId, $data);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Subscription updated successfully']);
            } else {
                throw new Exception('Failed to update subscription');
            }
            break;
            
        case 'delete':
            $subscriptionId = intval($_GET['id'] ?? 0);
            if (!$subscriptionId) {
                throw new Exception('Invalid subscription ID');
            }
            
            $result = $subscriptionManager->deleteSubscription($subscriptionId, $userId);
            
            if ($result) {
                header('Location: ../dashboard.php?message=Subscription deleted successfully');
            } else {
                throw new Exception('Failed to delete subscription');
            }
            break;
            
        case 'toggle':
            $subscriptionId = intval($_GET['id'] ?? 0);
            if (!$subscriptionId) {
                throw new Exception('Invalid subscription ID');
            }
            
            $result = $subscriptionManager->toggleSubscription($subscriptionId, $userId);
            
            if ($result) {
                header('Location: ../dashboard.php?message=Subscription status updated');
            } else {
                throw new Exception('Failed to update subscription status');
            }
            break;
            
        case 'record_payment':
            $subscriptionId = intval($_GET['id'] ?? 0);
            if (!$subscriptionId) {
                throw new Exception('Invalid subscription ID');
            }
            
            // Get subscription details to record payment
            $subscriptions = $subscriptionManager->getUserSubscriptions($userId, false);
            $subscription = null;
            
            foreach ($subscriptions as $sub) {
                if ($sub['id'] == $subscriptionId) {
                    $subscription = $sub;
                    break;
                }
            }
            
            if (!$subscription) {
                throw new Exception('Subscription not found');
            }
            
            $result = $subscriptionManager->recordPayment($subscriptionId, $subscription['cost']);
            
            if ($result) {
                header('Location: ../dashboard.php?message=Payment recorded successfully');
            } else {
                throw new Exception('Failed to record payment');
            }
            break;
            
        case 'get':
            $subscriptionId = intval($_GET['id'] ?? 0);
            if (!$subscriptionId) {
                throw new Exception('Invalid subscription ID');
            }
            
            $subscriptions = $subscriptionManager->getUserSubscriptions($userId, false);
            $subscription = null;
            
            foreach ($subscriptions as $sub) {
                if ($sub['id'] == $subscriptionId) {
                    $subscription = $sub;
                    break;
                }
            }
            
            if ($subscription) {
                echo json_encode(['success' => true, 'data' => $subscription]);
            } else {
                throw new Exception('Subscription not found');
            }
            break;
            
        case 'stats':
            $stats = $subscriptionManager->getSubscriptionStats($userId);
            echo json_encode(['success' => true, 'data' => $stats]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    if ($action === 'delete' || $action === 'toggle' || $action === 'record_payment') {
        header('Location: ../dashboard.php?error=' . urlencode($e->getMessage()));
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
