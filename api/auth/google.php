<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../config/email.php';

// Google OAuth configuration
$googleClientId = '267507492904-hr7q0qi2655ne01tv2si5ienpi6el4cm.apps.googleusercontent.com';
$googleClientSecret = 'GOCSPX-your-client-secret'; // You'll need to add this to your .env

$action = $_GET['action'] ?? 'signin';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Redirect to Google OAuth
    $redirectUri = 'https://123cashcontrol.com/api/auth/google.php';
    $scope = 'openid email profile';
    $state = base64_encode(json_encode(['action' => $action]));
    
    $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id' => $googleClientId,
        'redirect_uri' => $redirectUri,
        'scope' => $scope,
        'response_type' => 'code',
        'state' => $state,
        'access_type' => 'offline',
        'prompt' => 'consent'
    ]);
    
    header('Location: ' . $authUrl);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Google token verification (for client-side flow)
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $idToken = $input['id_token'] ?? '';
        
        if (empty($idToken)) {
            throw new Exception('ID token is required');
        }
        
        // Verify Google ID token
        $tokenInfo = verifyGoogleToken($idToken, $googleClientId);
        
        if (!$tokenInfo) {
            throw new Exception('Invalid Google token');
        }
        
        $email = $tokenInfo['email'];
        $name = $tokenInfo['name'];
        $googleId = $tokenInfo['sub'];
        
        // Initialize database and auth
        $database = new Database();
        $auth = new Auth($database);
        
        // Check if user exists
        $users = $database->query(
            "SELECT id, name, email, is_paid FROM users WHERE email = ? OR google_id = ?",
            [$email, $googleId]
        );
        
        if (!empty($users)) {
            // User exists, sign them in
            $user = $users[0];
            
            // Update Google ID if not set
            if (empty($user['google_id'])) {
                $database->update('users', 
                    ['google_id' => $googleId, 'updated_at' => date('Y-m-d H:i:s')],
                    ['id' => $user['id']]
                );
            }
            
            $sessionData = $auth->createSession($user['id']);
            
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
        } else {
            // Create new user
            $userId = $database->insert('users', [
                'name' => $name,
                'email' => $email,
                'google_id' => $googleId,
                'is_paid' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            if (!$userId) {
                throw new Exception('Failed to create user account');
            }
            
            $sessionData = $auth->createSession($userId);
            
            // Send welcome email
            try {
                $emailService = new EmailService();
                $emailService->sendWelcomeEmail($email, $name);
            } catch (Exception $e) {
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
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function verifyGoogleToken($idToken, $clientId) {
    // Verify Google ID token
    $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . $idToken;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return false;
    }
    
    $tokenInfo = json_decode($response, true);
    
    // Verify the token is for our app
    if ($tokenInfo['aud'] !== $clientId) {
        return false;
    }
    
    // Check if token is expired
    if ($tokenInfo['exp'] < time()) {
        return false;
    }
    
    return $tokenInfo;
}
?>
