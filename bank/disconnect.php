<?php
/**
 * Bank Disconnect Endpoint
 * Handles disconnecting bank accounts from user's account
 */

session_start();
require_once __DIR__ . '/../includes/database_helper.php';
require_once __DIR__ . '/../includes/auth_helper.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $pdo = DatabaseHelper::getConnection();
    
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    $bankAccountId = $input['bank_account_id'] ?? null;
    
    if ($bankAccountId) {
        // Disconnect specific bank account
        $stmt = $pdo->prepare("
            UPDATE bank_accounts 
            SET status = 'revoked', updated_at = NOW() 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$bankAccountId, $userId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Bank account disconnected successfully']);
        } else {
            echo json_encode(['error' => 'Bank account not found or already disconnected']);
        }
    } else {
        // Disconnect all bank accounts for user
        $stmt = $pdo->prepare("
            UPDATE bank_accounts 
            SET status = 'revoked', updated_at = NOW() 
            WHERE user_id = ? AND status = 'active'
        ");
        $stmt->execute([$userId]);
        
        $disconnectedCount = $stmt->rowCount();
        
        if ($disconnectedCount > 0) {
            echo json_encode([
                'success' => true, 
                'message' => "Disconnected {$disconnectedCount} bank account(s) successfully"
            ]);
        } else {
            echo json_encode(['error' => 'No active bank accounts found to disconnect']);
        }
    }
    
} catch (Exception $e) {
    error_log("Bank disconnect error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to disconnect bank account. Please try again.']);
}
?>
