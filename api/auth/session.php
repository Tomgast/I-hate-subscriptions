<?php
/**
 * Session Status Endpoint
 * Returns current user session information
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/auth.php';

try {
    if ($auth->isLoggedIn()) {
        $user = $auth->getCurrentUser();
        
        echo json_encode([
            'authenticated' => true,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'avatar_url' => $user['avatar_url'],
                'is_paid' => (bool)$user['is_paid'],
                'created_at' => $user['created_at']
            ],
            'csrf_token' => $auth->generateCsrfToken()
        ]);
    } else {
        echo json_encode([
            'authenticated' => false,
            'user' => null
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'authenticated' => false,
        'error' => $e->getMessage()
    ]);
}
?>
