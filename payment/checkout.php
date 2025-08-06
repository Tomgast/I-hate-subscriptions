<?php
session_start();
require_once '../includes/stripe_service.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/signin.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';

// Initialize Stripe service
$stripeService = new StripeService();

// Check if user already has Pro access
if ($stripeService->hasProAccess($userId)) {
    header('Location: ../dashboard.php?message=already_pro');
    exit;
}

// Handle checkout session creation
if ($_POST['action'] ?? '' === 'create_checkout') {
    $session = $stripeService->createCheckoutSession(
        $userId, 
        $userEmail,
        'https://I-hate-subscriptions.com/payment/success.php',
        'https://I-hate-subscriptions.com/payment/cancel.php'
    );
    
    if ($session && isset($session['url'])) {
        header('Location: ' . $session['url']);
        exit;
    } else {
        $error = "Failed to create payment session. Please try again.";
    }
}

// Stripe configuration
$stripePublishableKey = 'pk_test_51QVHcmDnqVZfKHWYKxkxGZRJQOjUKjJjJMhJYmFJXZJ';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upgrade to Pro - CashControl</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body class="bg-gray-50">
    <div class="container py-6">
        <div class="max-w-md mx-auto">
            <div class="card">
                <div class="p-6 text-center">
                    <h1 class="text-2xl font-bold text-gray-900 mb-4">Upgrade to Pro</h1>
                    <div class="mb-4">
                        <span class="text-4xl font-bold text-blue-600">€29</span>
                        <span class="text-gray-500">/year</span>
                    </div>
                    
                    <form id="payment-form">
                        <div id="payment-element"></div>
                        <button id="submit" class="btn btn-primary w-full mt-4">
                            <div class="spinner hidden" id="spinner"></div>
                            <span id="button-text">Pay Now</span>
                        </button>
                        <div id="payment-message" class="hidden"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const stripe = Stripe('<?php echo $stripePublishableKey; ?>');
        
        let elements;
        
        initialize();
        
        async function initialize() {
            const response = await fetch('create-payment-intent.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ amount: 2900 }) // €29.00
            });
            
            const { client_secret } = await response.json();
            
            elements = stripe.elements({ clientSecret: client_secret });
            
            const paymentElement = elements.create('payment');
            paymentElement.mount('#payment-element');
        }
        
        async function handleSubmit(e) {
            e.preventDefault();
            setLoading(true);
            
            const { error } = await stripe.confirmPayment({
                elements,
                confirmParams: {
                    return_url: window.location.origin + '/payment/success.php',
                },
            });
            
            if (error) {
                showMessage(error.message);
            }
            
            setLoading(false);
        }
        
        function showMessage(messageText) {
            const messageContainer = document.querySelector('#payment-message');
            messageContainer.classList.remove('hidden');
            messageContainer.textContent = messageText;
        }
        
        function setLoading(isLoading) {
            if (isLoading) {
                document.querySelector('#submit').disabled = true;
                document.querySelector('#spinner').classList.remove('hidden');
                document.querySelector('#button-text').classList.add('hidden');
            } else {
                document.querySelector('#submit').disabled = false;
                document.querySelector('#spinner').classList.add('hidden');
                document.querySelector('#button-text').classList.remove('hidden');
            }
        }
        
        document.querySelector('#payment-form').addEventListener('submit', handleSubmit);
    </script>
</body>
</html>
