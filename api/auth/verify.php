<?php
require_once '../config.php';

// Get token from URL parameter
$token = $_GET['token'] ?? '';

if (empty($token)) {
    http_response_code(400);
    echo "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Invalid Verification Link - CashControl</title>
        <script src='https://cdn.tailwindcss.com'></script>
    </head>
    <body class='bg-gray-50 min-h-screen flex items-center justify-center'>
        <div class='max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center'>
            <div class='w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4'>
                <svg class='w-8 h-8 text-red-500' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                    <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'></path>
                </svg>
            </div>
            <h1 class='text-2xl font-bold text-gray-900 mb-2'>Invalid Verification Link</h1>
            <p class='text-gray-600 mb-6'>The verification link is invalid or has expired.</p>
            <a href='/app/auth/signin.html' class='inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors'>
                Go to Sign In
            </a>
        </div>
    </body>
    </html>
    ";
    exit;
}

try {
    // Find user with this verification token
    $stmt = $pdo->prepare("SELECT id, name, email, email_verified FROM users WHERE verification_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('Invalid verification token');
    }
    
    if ($user['email_verified']) {
        // Already verified - show success message
        echo "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Already Verified - CashControl</title>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gray-50 min-h-screen flex items-center justify-center'>
            <div class='max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center'>
                <div class='w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4'>
                    <svg class='w-8 h-8 text-blue-500' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'></path>
                    </svg>
                </div>
                <h1 class='text-2xl font-bold text-gray-900 mb-2'>Email Already Verified</h1>
                <p class='text-gray-600 mb-6'>Your email address has already been verified. You can now sign in to your account.</p>
                <a href='/app/auth/signin.html' class='inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors'>
                    Sign In to CashControl
                </a>
            </div>
        </body>
        </html>
        ";
        exit;
    }
    
    // Verify the email
    $stmt = $pdo->prepare("UPDATE users SET email_verified = TRUE, verification_token = NULL WHERE id = ?");
    $success = $stmt->execute([$user['id']]);
    
    if (!$success) {
        throw new Exception('Failed to verify email');
    }
    
    // Success page
    echo "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Email Verified - CashControl</title>
        <script src='https://cdn.tailwindcss.com'></script>
    </head>
    <body class='bg-gray-50 min-h-screen flex items-center justify-center'>
        <div class='max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center'>
            <div class='w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4'>
                <svg class='w-8 h-8 text-green-500' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                    <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'></path>
                </svg>
            </div>
            <h1 class='text-2xl font-bold text-gray-900 mb-2'>Email Verified Successfully!</h1>
            <p class='text-gray-600 mb-2'>Welcome to CashControl, " . htmlspecialchars($user['name']) . "!</p>
            <p class='text-gray-600 mb-6'>Your email has been verified. You can now sign in and start tracking your subscriptions.</p>
            <a href='/app/auth/signin.html?verified=1' class='inline-block bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors'>
                Sign In to CashControl
            </a>
        </div>
    </body>
    </html>
    ";
    
} catch (Exception $e) {
    error_log("Email verification error: " . $e->getMessage());
    http_response_code(400);
    echo "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Verification Failed - CashControl</title>
        <script src='https://cdn.tailwindcss.com'></script>
    </head>
    <body class='bg-gray-50 min-h-screen flex items-center justify-center'>
        <div class='max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center'>
            <div class='w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4'>
                <svg class='w-8 h-8 text-red-500' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                    <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'></path>
                </svg>
            </div>
            <h1 class='text-2xl font-bold text-gray-900 mb-2'>Verification Failed</h1>
            <p class='text-gray-600 mb-6'>There was an error verifying your email. Please try again or contact support.</p>
            <a href='/app/auth/signin.html' class='inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors'>
                Go to Sign In
            </a>
        </div>
    </body>
    </html>
    ";
}
?>
