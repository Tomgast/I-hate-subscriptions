<?php
session_start();
require_once '../config/db_config.php';

// Google OAuth configuration
$client_id = '267507492904-hr7q0qi2655ne01tv2si5ienpi6el4cm.apps.googleusercontent.com';
// Load secure configuration
require_once '../config/secure_loader.php';
$client_secret = getSecureConfig('GOOGLE_CLIENT_SECRET');
$redirect_uri = 'https://123cashcontrol.com/auth/google-callback.php';

// Build Google OAuth URL
$google_oauth_url = 'https://accounts.google.com/o/oauth2/auth?' . http_build_query([
    'client_id' => $client_id,
    'redirect_uri' => $redirect_uri,
    'scope' => 'email profile',
    'response_type' => 'code',
    'access_type' => 'offline',
    'prompt' => 'consent'
]);

// Redirect to Google OAuth
header('Location: ' . $google_oauth_url);
exit;
?>
