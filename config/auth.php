<?php
/**
 * Authentication Configuration for CashControl
 * Google OAuth + PHP Session Management
 */

require_once 'database.php';

class Auth {
    private $db;
    private $google_client_id = '267507492904-hr7q0qi2655ne01tv2si5ienpi6el4cm.apps.googleusercontent.com';
    
    public function __construct() {
        $this->db = new Database();
        $this->startSession();
    }
    
    /**
     * Start secure PHP session
     */
    private function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure session configuration
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', 1);
            ini_set('session.use_strict_mode', 1);
            
            session_start();
        }
    }
    
    /**
     * Verify Google OAuth token
     */
    public function verifyGoogleToken($token) {
        // Verify token with Google
        $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . $token;
        $response = file_get_contents($url);
        
        if ($response === false) {
            return false;
        }
        
        $data = json_decode($response, true);
        
        // Verify token is for our app
        if (!isset($data['aud']) || $data['aud'] !== $this->google_client_id) {
            return false;
        }
        
        // Verify token is not expired
        if (!isset($data['exp']) || $data['exp'] < time()) {
            return false;
        }
        
        return [
            'email' => $data['email'],
            'name' => $data['name'],
            'picture' => $data['picture'] ?? null,
            'google_id' => $data['sub']
        ];
    }
    
    /**
     * Create or update user from Google OAuth
     */
    public function createOrUpdateUser($googleData) {
        try {
            // Check if user exists
            $existingUser = $this->db->fetch(
                "SELECT * FROM users WHERE email = ?",
                [$googleData['email']]
            );
            
            if ($existingUser) {
                // Update existing user
                $this->db->execute(
                    "UPDATE users SET name = ?, avatar_url = ?, updated_at = NOW() WHERE email = ?",
                    [$googleData['name'], $googleData['picture'], $googleData['email']]
                );
                return $existingUser['id'];
            } else {
                // Create new user
                $userId = $this->generateUuid();
                $this->db->execute(
                    "INSERT INTO users (id, email, name, avatar_url, is_paid, created_at) VALUES (?, ?, ?, ?, FALSE, NOW())",
                    [$userId, $googleData['email'], $googleData['name'], $googleData['picture']]
                );
                
                // Create default user preferences
                $this->createDefaultPreferences($userId);
                
                return $userId;
            }
        } catch (Exception $e) {
            error_log("User creation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create default user preferences
     */
    private function createDefaultPreferences($userId) {
        $prefId = $this->generateUuid();
        $defaultReminderDays = json_encode([1, 3, 7]);
        
        $this->db->execute(
            "INSERT INTO user_preferences (id, user_id, email_reminders, reminder_days, reminder_frequency, preferred_email_time, timezone) 
             VALUES (?, ?, TRUE, ?, 'once', '09:00:00', 'Europe/Amsterdam')",
            [$prefId, $userId, $defaultReminderDays]
        );
    }
    
    /**
     * Login user (create session)
     */
    public function login($userId) {
        $_SESSION['user_id'] = $userId;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        return true;
    }
    
    /**
     * Logout user (destroy session)
     */
    public function logout() {
        session_destroy();
        return true;
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Get current user ID
     */
    public function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current user data
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $this->db->fetch(
            "SELECT * FROM users WHERE id = ?",
            [$this->getCurrentUserId()]
        );
    }
    
    /**
     * Check if current user is premium
     */
    public function isPremiumUser() {
        $user = $this->getCurrentUser();
        return $user && $user['is_paid'] === 1;
    }
    
    /**
     * Upgrade user to premium
     */
    public function upgradeUser($userId) {
        return $this->db->execute(
            "UPDATE users SET is_paid = TRUE, updated_at = NOW() WHERE id = ?",
            [$userId]
        );
    }
    
    /**
     * Generate UUID v4
     */
    private function generateUuid() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public function verifyCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Require authentication (redirect if not logged in)
     */
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }
    }
    
    /**
     * Require premium (check if user has premium access)
     */
    public function requirePremium() {
        $this->requireAuth();
        
        if (!$this->isPremiumUser()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Premium subscription required']);
            exit;
        }
    }
}

// Global auth instance
$auth = new Auth();
?>
