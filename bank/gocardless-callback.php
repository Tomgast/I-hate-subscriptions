<?php
/**
 * GOCARDLESS (NORDIGEN) CALLBACK HANDLER
 * Handles the callback from GoCardless Bank Account Data after user authorizes bank connection
 */

session_start();
require_once '../config/db_config.php';
require_once '../includes/gocardless_financial_service.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$error = null;
$success = null;

try {
    // Debug: Log all incoming parameters
    error_log("GoCardless callback received. GET params: " . print_r($_GET, true));
    error_log("GoCardless callback received. POST params: " . print_r($_POST, true));
    
    // Get requisition ID from URL parameters
    $requisitionId = $_GET['ref'] ?? null;
    
    if (!$requisitionId) {
        throw new Exception('Missing requisition reference. Available parameters: ' . print_r($_GET, true));
    }
    
    error_log("GoCardless callback processing requisition ID: " . $requisitionId);
    
    // Initialize GoCardless service
    $pdo = getDBConnection();
    $gocardlessService = new GoCardlessFinancialService($pdo);
    
    // Handle the callback
    $result = $gocardlessService->handleCallback($requisitionId);
    
    if ($result['success']) {
        $success = "Bank connection successful! Found " . $result['accounts_connected'] . " account(s). Starting subscription scan...";
        
        // Set user session if not already set
        if (!isset($_SESSION['user_id']) && isset($result['user_id'])) {
            // This is a fallback - normally user should already be logged in
            $_SESSION['user_id'] = $result['user_id'];
        }
        
        // Redirect to dashboard after short delay
        header("refresh:3;url=../dashboard.php?scan_complete=1&provider=gocardless");
    } else {
        throw new Exception($result['error'] ?? 'Unknown error occurred during bank connection');
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("GoCardless callback error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Connection Status - CashControl</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .callback-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 20px;
        }
        .error-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 20px;
        }
        .loading-spinner {
            font-size: 2rem;
            color: #007bff;
            margin-bottom: 20px;
        }
        .provider-badge {
            background: #e8f5e8;
            color: #155724;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            display: inline-block;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="callback-container">
        <?php if ($success): ?>
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="provider-badge">
                <i class="fas fa-university"></i> GoCardless (EU Banks)
            </div>
            <h2 class="text-success mb-3">Connection Successful!</h2>
            <p class="text-muted mb-4"><?php echo htmlspecialchars($success); ?></p>
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p class="small text-muted">Redirecting to your dashboard...</p>
            
        <?php elseif ($error): ?>
            <div class="error-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="provider-badge" style="background: #f8d7da; color: #721c24;">
                <i class="fas fa-university"></i> GoCardless (EU Banks)
            </div>
            <h2 class="text-danger mb-3">Connection Failed</h2>
            <p class="text-muted mb-4"><?php echo htmlspecialchars($error); ?></p>
            <div class="d-grid gap-2">
                <a href="../bank/scan.php" class="btn btn-primary">
                    <i class="fas fa-redo"></i> Try Again
                </a>
                <a href="../dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-home"></i> Return to Dashboard
                </a>
            </div>
            
        <?php else: ?>
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <h2 class="mb-3">Processing Connection...</h2>
            <p class="text-muted">Please wait while we process your bank connection.</p>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
