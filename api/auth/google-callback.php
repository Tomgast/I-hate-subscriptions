<?php
/**
 * Google OAuth Callback Handler
 * Handles Google OAuth token verification and user login
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/auth.php';

try {
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['token'])) {
        throw new Exception('Token is required');
    }
    
    $token = $input['token'];
    
    // Verify Google OAuth token
    $googleData = $auth->verifyGoogleToken($token);
    
    if (!$googleData) {
        throw new Exception('Invalid Google token');
    }
    
    // Create or update user
    $userId = $auth->createOrUpdateUser($googleData);
    
    if (!$userId) {
        throw new Exception('Failed to create user');
    }
    
    // Login user (create session)
    $auth->login($userId);
    
    // Get user data
    $user = $auth->getCurrentUser();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'avatar_url' => $user['avatar_url'],
            'is_paid' => (bool)$user['is_paid'],
            'created_at' => $user['created_at']
        ],
        'redirect' => '/dashboard'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
