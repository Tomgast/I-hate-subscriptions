<?php
session_start();
require_once '../config/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Stripe secret key - replace with your actual key
$stripeSecretKey = 'sk_test_51QVHcmDnqVZfKHWYKxkxGZRJQOjUKjJjJMhJYmFJXZJ_YOUR_SECRET_KEY';

// Simple Stripe API call using cURL
function createPaymentIntent($amount, $currency = 'eur') {
    global $stripeSecretKey;
    
    $data = [
        'amount' => $amount,
        'currency' => $currency,
        'payment_method_types[]' => 'card',
        'metadata[user_id]' => $_SESSION['user_id']
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/payment_intents');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $stripeSecretKey,
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('Stripe API error: ' . $response);
    }
    
    return json_decode($response, true);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $amount = $input['amount'] ?? 2900; // Default €29.00
    
    $paymentIntent = createPaymentIntent($amount);
    
    echo json_encode([
        'client_secret' => $paymentIntent['client_secret']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
