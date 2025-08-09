<?php
/**
 * STRIPE FINANCIAL CONNECTIONS HANDLER
 * Handles client-side Financial Connections flow for test mode
 */

session_start();
require_once '../config/secure_loader.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/signin.php');
    exit;
}

$sessionId = $_GET['session_id'] ?? null;
$clientSecret = $_GET['client_secret'] ?? null;

if (!$sessionId || !$clientSecret) {
    header('Location: unified-scan.php?error=invalid_session');
    exit;
}

$stripePublishableKey = getSecureConfig('STRIPE_PUBLISHABLE_KEY');
if (!$stripePublishableKey) {
    header('Location: unified-scan.php?error=stripe_config');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connect Your Bank - CashControl</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .connect-container {
            padding: 60px 0;
        }
        .connect-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 600px;
            margin: 0 auto;
        }
        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .status-message {
            padding: 20px;
            text-align: center;
        }
        .btn-connect {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            color: white;
            font-weight: 600;
            transition: transform 0.2s ease;
        }
        .btn-connect:hover {
            transform: translateY(-2px);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container connect-container">
        <div class="connect-card">
            <div class="card-header bg-primary text-white text-center py-4">
                <h2 class="mb-0">
                    <i class="fas fa-university me-2"></i>
                    Connect Your US Bank Account
                </h2>
                <p class="mb-0 mt-2">Secure connection powered by Stripe</p>
            </div>
            
            <div class="card-body p-4">
                <div id="loading-state" class="status-message">
                    <div class="loading-spinner"></div>
                    <h4>Initializing Bank Connection...</h4>
                    <p class="text-muted">Please wait while we set up your secure connection to Stripe Financial Connections.</p>
                </div>
                
                <div id="ready-state" class="status-message" style="display: none;">
                    <i class="fas fa-shield-alt fa-3x text-success mb-3"></i>
                    <h4>Ready to Connect</h4>
                    <p class="text-muted mb-4">Click the button below to securely connect your US bank account through Stripe Financial Connections.</p>
                    <button id="connect-button" class="btn btn-connect btn-lg">
                        <i class="fas fa-link me-2"></i>
                        Connect Bank Account
                    </button>
                </div>
                
                <div id="error-state" class="status-message" style="display: none;">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <h4>Connection Error</h4>
                    <p class="text-muted mb-4" id="error-message">There was an error setting up the bank connection.</p>
                    <a href="unified-scan.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>
                        Back to Bank Selection
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Stripe
        const stripe = Stripe('<?php echo $stripePublishableKey; ?>');
        
        // Session details
        const sessionId = '<?php echo htmlspecialchars($sessionId); ?>';
        const clientSecret = '<?php echo htmlspecialchars($clientSecret); ?>';
        
        console.log('Initializing Stripe Financial Connections...');
        console.log('Session ID:', sessionId);
        console.log('Client Secret:', clientSecret ? 'Present' : 'Missing');
        
        // Initialize Financial Connections
        let financialConnectionsSession = null;
        
        async function initializeConnection() {
            try {
                // Create Financial Connections session object
                financialConnectionsSession = stripe.financialConnections.session({
                    clientSecret: clientSecret
                });
                
                console.log('Financial Connections session created:', financialConnectionsSession);
                
                // Show ready state
                document.getElementById('loading-state').style.display = 'none';
                document.getElementById('ready-state').style.display = 'block';
                
                // Add click handler to connect button
                document.getElementById('connect-button').addEventListener('click', connectBank);
                
            } catch (error) {
                console.error('Error initializing Financial Connections:', error);
                showError('Failed to initialize bank connection: ' + error.message);
            }
        }
        
        async function connectBank() {
            try {
                console.log('Starting bank connection flow...');
                
                // Disable button and show loading
                const button = document.getElementById('connect-button');
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Connecting...';
                
                // Launch Financial Connections
                const {session, error} = await financialConnectionsSession.collect({
                    // Optional: specify which accounts to collect
                    // permissions: ['payment_method', 'balances', 'transactions']
                });
                
                if (error) {
                    console.error('Financial Connections error:', error);
                    showError('Bank connection failed: ' + error.message);
                    return;
                }
                
                console.log('Financial Connections completed:', session);
                
                // Redirect to callback handler
                window.location.href = 'stripe-callback.php?session_id=' + sessionId;
                
            } catch (error) {
                console.error('Error during bank connection:', error);
                showError('An unexpected error occurred: ' + error.message);
            }
        }
        
        function showError(message) {
            document.getElementById('loading-state').style.display = 'none';
            document.getElementById('ready-state').style.display = 'none';
            document.getElementById('error-message').textContent = message;
            document.getElementById('error-state').style.display = 'block';
        }
        
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', initializeConnection);
    </script>
</body>
</html>
