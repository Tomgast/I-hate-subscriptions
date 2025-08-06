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

require_once '../config.php';

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
    
    // Check if user already exists (using global $pdo from config.php)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        throw new Exception('An account with this email already exists');
    }
    
    // Generate verification token
    $verificationToken = bin2hex(random_bytes(32));
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Create user
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password_hash, verification_token, email_verified, is_pro, created_at) 
        VALUES (?, ?, ?, ?, FALSE, FALSE, NOW())
    ");
    
    $success = $stmt->execute([$name, $email, $hashedPassword, $verificationToken]);
    
    if (!$success) {
        throw new Exception('Failed to create user account');
    }
    
    $userId = $pdo->lastInsertId();
    
    // Create default user preferences
    $stmt = $pdo->prepare("
        INSERT INTO user_preferences (user_id, reminder_days, reminder_frequency, preferred_time) 
        VALUES (?, '[7, 3, 1]', 'once', '09:00:00')
    ");
    $stmt->execute([$userId]);
    
    // Send verification email
    $emailSent = sendVerificationEmail($email, $name, $verificationToken);
    
    if (!$emailSent) {
        error_log("Failed to send verification email to: " . $email);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully! Please check your email to verify your account.',
        'redirect' => '/app/auth/signin.html?message=verify',
        'email_sent' => $emailSent
    ]);
    
} catch (Exception $e) {
    error_log("Signup error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
