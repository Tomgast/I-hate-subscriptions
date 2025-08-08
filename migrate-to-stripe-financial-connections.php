<?php
/**
 * STRIPE FINANCIAL CONNECTIONS MIGRATION TOOL
 * Safely migrate from TrueLayer to Stripe Financial Connections
 */

session_start();
require_once 'config/db_config.php';

if (!isset($_SESSION['user_id'])) {
    die('Please log in first');
}

$userId = $_SESSION['user_id'];
$userEmail = $_SESSION['user_email'] ?? '';

echo "<h1>🔄 Stripe Financial Connections Migration</h1>";
echo "<p><strong>User ID:</strong> {$userId} | <strong>Email:</strong> {$userEmail}</p>";

echo "<div style='background: #e8f5e8; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<strong>🎯 Migration Overview:</strong><br>";
echo "This tool will safely migrate your bank integration from TrueLayer to Stripe Financial Connections. Your existing database structure and subscription data will remain intact.";
echo "</div>";

// Migration Steps
echo "<h2>🔧 Migration Steps</h2>";

// Step 1: Create Stripe Service
echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Step 1: Create Stripe Financial Connections Service</h3>";
echo "<p>Create a new bank service class that uses Stripe Financial Connections.</p>";
echo "<form method='POST'>";
echo "<input type='hidden' name='action' value='create_stripe_service'>";
echo "<button type='submit' style='background: #28a745; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer;'>Create Stripe Service</button>";
echo "</form>";
echo "</div>";

// Step 2: Update Database
echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Step 2: Update Database Schema</h3>";
echo "<p>Add support for Stripe Financial Connections session data.</p>";
echo "<form method='POST'>";
echo "<input type='hidden' name='action' value='update_database'>";
echo "<button type='submit' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Update Database</button>";
echo "</form>";
echo "</div>";

// Step 3: Create Pages
echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Step 3: Create Stripe Bank Pages</h3>";
echo "<p>Create new bank scan pages for Stripe integration.</p>";
echo "<form method='POST'>";
echo "<input type='hidden' name='action' value='create_pages'>";
echo "<button type='submit' style='background: #ffc107; color: black; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Create Pages</button>";
echo "</form>";
echo "</div>";

// Handle form submissions
if ($_POST && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'create_stripe_service':
                echo "<h2>🔄 Creating Stripe Service</h2>";
                
                $serviceCode = '<?php
class StripeFinancialService {
    private $pdo;
    private $stripeSecretKey;
    
    public function __construct() {
        require_once __DIR__ . \'/../config/secure_loader.php\';
        $this->pdo = getDBConnection();
        $this->stripeSecretKey = getSecureConfig(\'STRIPE_SECRET_KEY\');
        \Stripe\Stripe::setApiKey($this->stripeSecretKey);
    }
    
    public function createFinancialConnectionsSession($userId, $returnUrl) {
        try {
            $session = \Stripe\FinancialConnections\Session::create([
                \'account_holder\' => [\'type\' => \'consumer\'],
                \'permissions\' => [\'payment_method\', \'balances\', \'transactions\'],
                \'filters\' => [\'countries\' => [\'US\', \'GB\', \'NL\', \'FR\', \'ES\']],
                \'return_url\' => $returnUrl,
            ]);
            
            return [
                \'success\' => true,
                \'session_id\' => $session->id,
                \'url\' => $session->url
            ];
        } catch (Exception $e) {
            return [\'success\' => false, \'error\' => $e->getMessage()];
        }
    }
    
    public function handleSessionCompletion($sessionId, $userId) {
        try {
            $session = \Stripe\FinancialConnections\Session::retrieve($sessionId);
            
            if ($session->status !== \'completed\') {
                throw new Exception(\'Session not completed\');
            }
            
            $accounts = $session->accounts->data;
            $totalSubscriptions = 0;
            
            foreach ($accounts as $account) {
                // Save connection and detect subscriptions
                $this->saveBankConnection($userId, $account, $session);
                $transactions = $this->getAccountTransactions($account->id);
                $subscriptions = $this->detectSubscriptions($transactions, $userId);
                $totalSubscriptions += count($subscriptions);
            }
            
            return [\'success\' => true, \'subscriptions\' => $totalSubscriptions];
        } catch (Exception $e) {
            return [\'success\' => false, \'error\' => $e->getMessage()];
        }
    }
    
    private function saveBankConnection($userId, $account, $session) {
        $sql = "INSERT INTO bank_connections (
            user_id, provider, connection_id, access_token, 
            bank_name, account_data, connection_status, is_active, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $userId, \'stripe_financial_connections\', $account->id, $session->id,
            $account->institution_name ?? \'Unknown\', json_encode($account),
            \'active\', 1
        ]);
    }
    
    private function getAccountTransactions($accountId) {
        return \Stripe\FinancialConnections\Transaction::all([
            \'account\' => $accountId, \'limit\' => 100
        ])->data;
    }
    
    private function detectSubscriptions($transactions, $userId) {
        // Subscription detection logic here
        return [];
    }
}
?>';
                
                file_put_contents(__DIR__ . '/includes/stripe_financial_service.php', $serviceCode);
                echo "<span style='color: green;'>✅ Stripe service created</span><br>";
                break;
                
            case 'update_database':
                echo "<h2>🔄 Updating Database</h2>";
                
                $pdo = getDBConnection();
                $sql = "CREATE TABLE IF NOT EXISTS bank_connection_sessions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    session_id VARCHAR(255) NOT NULL UNIQUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )";
                $pdo->exec($sql);
                
                echo "<span style='color: green;'>✅ Database updated</span><br>";
                break;
                
            case 'create_pages':
                echo "<h2>🔄 Creating Pages</h2>";
                
                // Create stripe-scan.php
                $scanPage = '<?php
session_start();
require_once \'../config/db_config.php\';
require_once \'../includes/stripe_financial_service.php\';

if (!isset($_SESSION[\'user_id\'])) {
    header(\'Location: ../auth/signin.php\');
    exit;
}

$userId = $_SESSION[\'user_id\'];
$service = new StripeFinancialService();

if ($_POST && $_POST[\'action\'] === \'connect\') {
    $returnUrl = \'https://\' . $_SERVER[\'HTTP_HOST\'] . \'/bank/stripe-callback.php\';
    $result = $service->createFinancialConnectionsSession($userId, $returnUrl);
    
    if ($result[\'success\']) {
        header(\'Location: \' . $result[\'url\']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Bank Scan - Stripe</title></head>
<body>
    <h1>Connect Your Bank Account</h1>
    <form method="POST">
        <input type="hidden" name="action" value="connect">
        <button type="submit">Connect Bank Account</button>
    </form>
</body>
</html>';
                
                file_put_contents(__DIR__ . '/bank/stripe-scan.php', $scanPage);
                
                // Create callback page
                $callbackPage = '<?php
session_start();
require_once \'../config/db_config.php\';
require_once \'../includes/stripe_financial_service.php\';

$userId = $_SESSION[\'user_id\'];
$sessionId = $_GET[\'session_id\'] ?? null;

if ($sessionId) {
    $service = new StripeFinancialService();
    $result = $service->handleSessionCompletion($sessionId, $userId);
    
    if ($result[\'success\']) {
        header(\'Location: ../dashboard.php?scan_complete=1\');
        exit;
    }
}

header(\'Location: stripe-scan.php?error=1\');
?>';
                
                file_put_contents(__DIR__ . '/bank/stripe-callback.php', $callbackPage);
                
                echo "<span style='color: green;'>✅ Pages created</span><br>";
                break;
        }
    } catch (Exception $e) {
        echo "<span style='color: red;'>❌ Error: " . $e->getMessage() . "</span><br>";
    }
}

echo "<h2>📋 Benefits</h2>";
echo "<ul>";
echo "<li>✅ Integrated with existing Stripe account</li>";
echo "<li>✅ No separate approval process needed</li>";
echo "<li>✅ Same database structure</li>";
echo "<li>✅ Better bank coverage</li>";
echo "</ul>";

echo "<div style='margin: 20px 0;'>";
echo "<a href='bank/stripe-scan.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Stripe Integration</a>";
echo "</div>";
?>

<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
h1, h2, h3 { color: #333; }
</style>
