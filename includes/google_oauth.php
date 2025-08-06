<?php
// Google OAuth Service for CashControl
require_once __DIR__ . '/../config/db_config.php';

class GoogleOAuthService {
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $pdo;
    
    public function __construct() {
        // Load secure configuration
        require_once __DIR__ . '/../config/secure_loader.php';
        
        $this->pdo = getDBConnection();
        
        // Load Google OAuth credentials securely
        $this->clientId = getSecureConfig('GOOGLE_CLIENT_ID') ?: '267507492904-hr7q0qi2655ne01tv2si5ienpi6el4cm.apps.googleusercontent.com';
        $this->clientSecret = getSecureConfig('GOOGLE_CLIENT_SECRET');
        $this->redirectUri = getSecureConfig('GOOGLE_REDIRECT_URI') ?: 'https://123cashcontrol.com/auth/google-callback.php';
    }
    
    /**
     * Get Google OAuth authorization URL
     */
    public function getAuthorizationUrl($state = null) {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => 'email profile',
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent'
        ];
        
        if ($state) {
            $params['state'] = $state;
        }
        
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }
    
    /**
     * Exchange authorization code for access token
     */
    public function exchangeCodeForToken($code) {
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        
        $postData = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        }
        
        return false;
    }
    
    /**
     * Get user profile information from Google
     */
    public function getUserProfile($accessToken) {
        $profileUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $profileUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        }
        
        return false;
    }
    
    /**
     * Create or update user from Google profile
     */
    public function createOrUpdateUser($googleProfile) {
        try {
            // Check if user exists by Google ID
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE google_id = ?");
            $stmt->execute([$googleProfile['id']]);
            $existingUser = $stmt->fetch();
            
            if ($existingUser) {
                // Update existing user
                $stmt = $this->pdo->prepare("
                    UPDATE users 
                    SET name = ?, email = ?, email_verified = TRUE, updated_at = NOW() 
                    WHERE google_id = ?
                ");
                $stmt->execute([
                    $googleProfile['name'],
                    $googleProfile['email'],
                    $googleProfile['id']
                ]);
                
                return $existingUser['id'];
            } else {
                // Check if user exists by email
                $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$googleProfile['email']]);
                $existingEmailUser = $stmt->fetch();
                
                if ($existingEmailUser) {
                    // Link Google account to existing email user
                    $stmt = $this->pdo->prepare("
                        UPDATE users 
                        SET google_id = ?, name = ?, email_verified = TRUE, updated_at = NOW() 
                        WHERE email = ?
                    ");
                    $stmt->execute([
                        $googleProfile['id'],
                        $googleProfile['name'],
                        $googleProfile['email']
                    ]);
                    
                    return $existingEmailUser['id'];
                } else {
                    // Create new user
                    $stmt = $this->pdo->prepare("
                        INSERT INTO users (email, name, google_id, email_verified, is_pro) 
                        VALUES (?, ?, ?, TRUE, FALSE)
                    ");
                    $stmt->execute([
                        $googleProfile['email'],
                        $googleProfile['name'],
                        $googleProfile['id']
                    ]);
                    
                    $userId = $this->pdo->lastInsertId();
                    
                    // Create default user preferences
                    $this->createDefaultUserPreferences($userId);
                    
                    // Send welcome email
                    if (class_exists('EmailService')) {
                        $emailService = new EmailService();
                        $emailService->sendWelcomeEmail($googleProfile['email'], $googleProfile['name']);
                    }
                    
                    return $userId;
                }
            }
        } catch (Exception $e) {
            error_log("Error creating/updating user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create default user preferences
     */
    private function createDefaultUserPreferences($userId) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO user_preferences (user_id, email_reminders, reminder_days_before, preferred_currency, timezone) 
                VALUES (?, TRUE, ?, 'EUR', 'Europe/Amsterdam')
            ");
            $stmt->execute([$userId, json_encode([7, 3, 1])]);
        } catch (Exception $e) {
            error_log("Error creating user preferences: " . $e->getMessage());
        }
    }
    
    /**
     * Create user session
     */
    public function createUserSession($userId) {
        try {
            // Clean up old sessions
            $stmt = $this->pdo->prepare("DELETE FROM user_sessions WHERE user_id = ? OR expires_at < NOW()");
            $stmt->execute([$userId]);
            
            // Create new session
            $sessionToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            $stmt = $this->pdo->prepare("
                INSERT INTO user_sessions (user_id, session_token, expires_at) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$userId, $sessionToken, $expiresAt]);
            
            // Get user details
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['is_paid'] = $user['is_pro'];
            $_SESSION['session_token'] = $sessionToken;
            
            return true;
        } catch (Exception $e) {
            error_log("Error creating user session: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Test Google OAuth configuration
     */
    public function testConfiguration() {
        $errors = [];
        
        if (empty($this->clientId)) {
            $errors[] = "Google Client ID is not configured";
        }
        
        if (empty($this->clientSecret)) {
            $errors[] = "Google Client Secret is not configured";
        }
        
        if (empty($this->redirectUri)) {
            $errors[] = "Google Redirect URI is not configured";
        }
        
        return [
            'configured' => empty($errors),
            'errors' => $errors,
            'client_id' => $this->clientId ? substr($this->clientId, 0, 10) . '...' : 'Not set',
            'redirect_uri' => $this->redirectUri
        ];
    }
}
?>
