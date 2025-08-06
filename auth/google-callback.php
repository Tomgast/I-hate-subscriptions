<?php
session_start();
require_once '../includes/google_oauth.php';

// Check for authorization code
if (!isset($_GET['code'])) {
    $error = $_GET['error'] ?? 'Authorization failed';
    header('Location: signin.php?error=' . urlencode($error));
    exit;
}

try {
    $googleOAuth = new GoogleOAuthService();
    
    // Exchange code for token
    $tokenData = $googleOAuth->exchangeCodeForToken($_GET['code']);
    
    if (!$tokenData || !isset($tokenData['access_token'])) {
        throw new Exception('Failed to get access token from Google');
    }
    
    // Get user profile
    $userProfile = $googleOAuth->getUserProfile($tokenData['access_token']);
    
    if (!$userProfile) {
        throw new Exception('Failed to get user profile from Google');
    }
    
    // Create or update user
    $userId = $googleOAuth->createOrUpdateUser($userProfile);
    
    if (!$userId) {
        throw new Exception('Failed to create or update user');
    }
    
    // Create session
    if ($googleOAuth->createUserSession($userId)) {
        // Redirect to dashboard
        header('Location: ../dashboard.php');
        exit;
    } else {
        throw new Exception('Failed to create user session');
    }
    
} catch (Exception $e) {
    error_log("Google OAuth callback error: " . $e->getMessage());
    header('Location: signin.php?error=' . urlencode('Authentication failed. Please try again.'));
    exit;
}
?>
