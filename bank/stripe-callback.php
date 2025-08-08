<?php
/**
 * STRIPE FINANCIAL CONNECTIONS CALLBACK
 * Handles the return from Stripe's hosted auth flow
 */

session_start();
require_once '../config/db_config.php';
require_once '../includes/stripe_financial_service.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$error = '';
$success = '';
$sessionId = $_GET['session'] ?? '';

if (empty($sessionId)) {
    $error = 'No session ID provided';
} else {
    try {
        $stripeService = new StripeFinancialService($pdo);
        $result = $stripeService->handleCallback($sessionId);
        
        if ($result['success']) {
            if ($result['status'] === 'completed') {
                $success = $result['message'];
                $accountsConnected = $result['accounts_connected'] ?? 0;
            } else {
                $error = 'Connection was not completed. Status: ' . $result['status'];
            }
        } else {
            $error = $result['error'];
        }
    } catch (Exception $e) {
        error_log("Error in stripe-callback.php: " . $e->getMessage());
        $error = 'An unexpected error occurred while processing your bank connection.';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Connection Result - CashControl</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="../dashboard.php" class="text-xl font-bold text-blue-600">
                        CashControl
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="../logout.php" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-2xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-sm border p-8 text-center">
            <?php if ($success): ?>
                <!-- Success State -->
                <div class="mb-6">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                        <i class="fas fa-check text-green-600 text-2xl"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">
                        Bank Account Connected Successfully!
                    </h1>
                    <p class="text-gray-600 mb-6">
                        <?= htmlspecialchars($success) ?>
                    </p>
                    
                    <?php if (isset($accountsConnected) && $accountsConnected > 0): ?>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                            <div class="text-green-800">
                                <i class="fas fa-university mr-2"></i>
                                <strong><?= $accountsConnected ?></strong> bank account<?= $accountsConnected > 1 ? 's' : '' ?> connected
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Next Steps -->
                <div class="space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">What's Next?</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <a href="stripe-scan.php" class="bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition duration-200 font-medium">
                            <i class="fas fa-search mr-2"></i>
                            Scan for Subscriptions
                        </a>
                        
                        <a href="../dashboard.php" class="bg-gray-100 text-gray-700 py-3 px-6 rounded-lg hover:bg-gray-200 transition duration-200 font-medium">
                            <i class="fas fa-tachometer-alt mr-2"></i>
                            Go to Dashboard
                        </a>
                    </div>
                </div>

                <!-- Auto-redirect script -->
                <script>
                    // Auto-redirect to scan page after 5 seconds
                    let countdown = 5;
                    const countdownElement = document.createElement('p');
                    countdownElement.className = 'text-sm text-gray-500 mt-6';
                    countdownElement.innerHTML = `Automatically redirecting to scan page in <span id="countdown">${countdown}</span> seconds...`;
                    document.querySelector('.space-y-4').appendChild(countdownElement);
                    
                    const interval = setInterval(() => {
                        countdown--;
                        document.getElementById('countdown').textContent = countdown;
                        
                        if (countdown <= 0) {
                            clearInterval(interval);
                            window.location.href = 'stripe-scan.php';
                        }
                    }, 1000);
                    
                    // Cancel auto-redirect if user clicks anywhere
                    document.addEventListener('click', () => {
                        clearInterval(interval);
                        countdownElement.remove();
                    });
                </script>

            <?php else: ?>
                <!-- Error State -->
                <div class="mb-6">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                        <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">
                        Connection Failed
                    </h1>
                    <p class="text-gray-600 mb-6">
                        There was an issue connecting your bank account.
                    </p>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                            <div class="text-red-800 text-sm">
                                <i class="fas fa-info-circle mr-2"></i>
                                <?= htmlspecialchars($error) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Retry Options -->
                <div class="space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">What would you like to do?</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <a href="stripe-scan.php" class="bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition duration-200 font-medium">
                            <i class="fas fa-redo mr-2"></i>
                            Try Again
                        </a>
                        
                        <a href="../dashboard.php" class="bg-gray-100 text-gray-700 py-3 px-6 rounded-lg hover:bg-gray-200 transition duration-200 font-medium">
                            <i class="fas fa-tachometer-alt mr-2"></i>
                            Go to Dashboard
                        </a>
                    </div>
                    
                    <!-- Help Section -->
                    <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <h3 class="font-medium text-blue-900 mb-2">Need Help?</h3>
                        <div class="text-blue-800 text-sm space-y-1">
                            <p>• Make sure you completed the bank authorization process</p>
                            <p>• Check that your bank is supported by Stripe Financial Connections</p>
                            <p>• Try using a different browser or clearing your cache</p>
                            <p>• Contact support if the issue persists</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Security Notice -->
        <div class="mt-8 text-center">
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-center justify-center text-gray-600 text-sm">
                    <i class="fas fa-shield-alt mr-2"></i>
                    Your bank connection is secured by Stripe Financial Connections
                </div>
            </div>
        </div>
    </div>
</body>
</html>
