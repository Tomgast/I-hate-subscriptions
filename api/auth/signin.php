<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';
require_once '../../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    
    // Validation
    if (empty($email)) {
        throw new Exception('Email is required');
    }
    
    if (empty($password)) {
        throw new Exception('Password is required');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Initialize database and auth
    $database = new Database();
    $auth = new Auth($database);
    
    // Get user by email
    $users = $database->query(
        "SELECT id, name, email, password, is_paid FROM users WHERE email = ?",
        [$email]
    );
    
    if (empty($users)) {
        throw new Exception('Invalid email or password');
    }
    
    $user = $users[0];
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        throw new Exception('Invalid email or password');
    }
    
    // Create user session
    $sessionData = $auth->createSession($user['id']);
    
    // Update last login
    $database->update('users', 
        ['updated_at' => date('Y-m-d H:i:s')],
        ['id' => $user['id']]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Signed in successfully',
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'is_paid' => (bool)$user['is_paid']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
