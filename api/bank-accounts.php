<?php
/**
 * Bank Accounts Management API
 * Handles CRUD operations for multiple bank accounts
 */

session_start();
require_once __DIR__ . '/../includes/database_helper.php';
require_once __DIR__ . '/../includes/multi_bank_service.php';
require_once __DIR__ . '/../includes/auth_helper.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];
$multiBankService = new MultiBankService();

// Set content type
header('Content-Type: application/json');

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($method) {
        case 'GET':
            // Get all bank accounts for user
            $summary = $multiBankService->getBankAccountSummary($userId);
            echo json_encode([
                'success' => true,
                'data' => $summary
            ]);
            break;
            
        case 'POST':
            // Add new bank account (this would be called after successful bank connection)
            $accountData = $input['account_data'] ?? [];
            $provider = $input['provider'] ?? 'stripe';
            
            if (empty($accountData)) {
                throw new Exception('Account data is required');
            }
            
            $bankAccountId = $multiBankService->addBankAccount($userId, $accountData, $provider);
            
            if ($bankAccountId) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Bank account added successfully',
                    'bank_account_id' => $bankAccountId
                ]);
            } else {
                throw new Exception('Failed to add bank account');
            }
            break;
            
        case 'DELETE':
            // Disconnect/remove bank account
            $bankAccountId = $input['bank_account_id'] ?? null;
            
            if (!$bankAccountId) {
                throw new Exception('Bank account ID is required');
            }
            
            $success = $multiBankService->disconnectBankAccount($userId, $bankAccountId);
            
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Bank account disconnected successfully'
                ]);
            } else {
                throw new Exception('Failed to disconnect bank account');
            }
            break;
            
        case 'PUT':
            // Update bank account (for future use)
            echo json_encode(['error' => 'Update functionality not yet implemented']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Bank accounts API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
