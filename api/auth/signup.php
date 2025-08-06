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
require_once '../../config/email.php';

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
    
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $confirmPassword = $input['confirmPassword'] ?? '';
    
    // Validation
    if (empty($name)) {
        throw new Exception('Name is required');
    }
    
    if (empty($email)) {
        throw new Exception('Email is required');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    if (strlen($password) < 8) {
        throw new Exception('Password must be at least 8 characters');
    }
    
    if ($password !== $confirmPassword) {
        throw new Exception('Passwords do not match');
    }
    
    // Initialize database and auth
    $database = new Database();
    $auth = new Auth($database);
    
    // Check if user already exists
    $existingUser = $database->query(
        "SELECT id FROM users WHERE email = ?",
        [$email]
    );
    
    if (!empty($existingUser)) {
        throw new Exception('An account with this email already exists');
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Create user
    $userId = $database->insert('users', [
        'name' => $name,
        'email' => $email,
        'password' => $hashedPassword,
        'is_paid' => 0,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    if (!$userId) {
        throw new Exception('Failed to create user account');
    }
    
    // Create user session
    $sessionData = $auth->createSession($userId);
    
    // Send welcome email
    try {
        $emailService = new EmailService();
        $emailService->sendWelcomeEmail($email, $name);
    } catch (Exception $e) {
        // Log email error but don't fail the signup
        error_log("Welcome email failed: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully',
        'user' => [
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'is_paid' => false
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
