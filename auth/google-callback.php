<?php
session_start();
require_once '../config/db_config.php';

if (isset($_GET['code'])) {
    $code = $_GET['code'];
    
    // Google OAuth configuration
    $client_id = '267507492904-hr7q0qi2655ne01tv2si5ienpi6el4cm.apps.googleusercontent.com';
    // Load secure configuration
    require_once '../config/secure_loader.php';
    $client_secret = getSecureConfig('GOOGLE_CLIENT_SECRET');
    $redirect_uri = 'https://123cashcontrol.com/auth/google-callback.php';
    
    // Exchange code for access token
    $token_url = 'https://oauth2.googleapis.com/token';
    $token_data = [
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'code' => $code,
        'grant_type' => 'authorization_code',
        'redirect_uri' => $redirect_uri
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $token_info = json_decode($response, true);
    
    if (isset($token_info['access_token'])) {
        // Get user info from Google
        $user_info_url = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $token_info['access_token'];
        $user_info = json_decode(file_get_contents($user_info_url), true);
        
        if ($user_info && isset($user_info['email'])) {
            try {
                $pdo = getDBConnection();
                
                // Check if user exists
                $stmt = $pdo->prepare("SELECT id, email, name, is_pro FROM users WHERE email = ?");
                $stmt->execute([$user_info['email']]);
                $existing_user = $stmt->fetch();
                
                if ($existing_user) {
                    // User exists, log them in
                    $user_id = $existing_user['id'];
                } else {
                    // Create new user with free plan
                    $stmt = $pdo->prepare("INSERT INTO users (email, name, google_id, subscription_type, subscription_status) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$user_info['email'], $user_info['name'], $user_info['id'], 'free', 'active']);
                    $user_id = $pdo->lastInsertId();
                }
                
                // Create session
                $sessionToken = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                $stmt = $pdo->prepare("INSERT INTO user_sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $sessionToken, $expiresAt]);
                
                // Set session variables
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_email'] = $user_info['email'];
                $_SESSION['user_name'] = $user_info['name'];
                $_SESSION['is_paid'] = $existing_user['is_pro'] ?? 0;
                $_SESSION['session_token'] = $sessionToken;
                
                header('Location: ../dashboard.php');
                exit;
            } catch (Exception $e) {
                error_log("Google OAuth error: " . $e->getMessage());
                header('Location: signin.php?error=oauth_failed');
                exit;
            }
        }
    }
}
?>
