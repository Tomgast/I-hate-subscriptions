<?php
/**
 * VERIFY GOCARDLESS READY
 * Final verification that all systems are ready for the 4th GoCardless connection
 */

session_start();
require_once 'config/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/signin.php');
    exit;
}

$userId = $_SESSION['user_id'];

echo "<h1>✅ GoCardless System Verification</h1>";
echo "<p><strong>User ID:</strong> $userId</p>";
echo "<p><strong>This is your final verification before the 4th connection attempt.</strong></p>";

$allChecks = [];

try {
    $pdo = getDBConnection();
    
    // Check 1: GoCardless Service Class
    echo "<h2>🔧 System Components</h2>";
    
    if (file_exists('includes/gocardless_financial_service.php')) {
        echo "<p>✅ GoCardless Financial Service: Found</p>";
        $allChecks['service'] = true;
    } else {
        echo "<p>❌ GoCardless Financial Service: Missing</p>";
        $allChecks['service'] = false;
    }
    
    if (file_exists('includes/gocardless_transaction_processor.php')) {
        echo "<p>✅ Transaction Processor: Found</p>";
        $allChecks['processor'] = true;
    } else {
        echo "<p>❌ Transaction Processor: Missing</p>";
        $allChecks['processor'] = false;
    }
    
    // Check 2: Database Tables
    echo "<h2>🗄️ Database Infrastructure</h2>";
    
    $requiredTables = [
        'bank_connections' => 'Bank connection storage',
        'bank_connection_sessions' => 'Session management',
        'bank_scans' => 'Scan results storage',
        'subscriptions' => 'Detected subscriptions'
    ];
    
    foreach ($requiredTables as $table => $description) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "<p>✅ $description ($table): Ready</p>";
                $allChecks["table_$table"] = true;
            } else {
                echo "<p>❌ $description ($table): Missing</p>";
                $allChecks["table_$table"] = false;
            }
        } catch (Exception $e) {
            echo "<p>❌ $description ($table): Error - " . $e->getMessage() . "</p>";
            $allChecks["table_$table"] = false;
        }
    }
    
    // Check 3: Transaction Processor Test
    echo "<h2>🔍 Transaction Processing Test</h2>";
    
    try {
        require_once 'includes/gocardless_transaction_processor.php';
        $processor = new GoCardlessTransactionProcessor($pdo);
        
        // Test with sample GoCardless transaction structure
        $sampleData = [
            'transactions' => [
                'booked' => [
                    [
                        'transactionId' => 'test-123',
                        'transactionAmount' => [
                            'amount' => '-15.99',
                            'currency' => 'EUR'
                        ],
                        'bookingDate' => '2024-12-01',
                        'creditorName' => 'Netflix',
                        'remittanceInformationUnstructured' => 'NETFLIX SUBSCRIPTION'
                    ]
                ]
            ]
        ];
        
        $result = $processor->processTransactions($userId, 'test-account', $sampleData);
        
        if ($result['success']) {
            echo "<p>✅ Transaction Processor: Working correctly</p>";
            echo "<p>   - Processed {$result['valid_transactions']} transactions</p>";
            $allChecks['processor_test'] = true;
        } else {
            echo "<p>❌ Transaction Processor: Failed - " . $result['error'] . "</p>";
            $allChecks['processor_test'] = false;
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Transaction Processor: Error - " . $e->getMessage() . "</p>";
        $allChecks['processor_test'] = false;
    }
    
    // Check 4: GoCardless Configuration
    echo "<h2>🔑 GoCardless Configuration</h2>";
    
    try {
        require_once 'includes/gocardless_financial_service.php';
        $gocardlessService = new GoCardlessFinancialService($pdo);
        echo "<p>✅ GoCardless Service: Initialized successfully</p>";
        $allChecks['config'] = true;
    } catch (Exception $e) {
        echo "<p>❌ GoCardless Service: Configuration error - " . $e->getMessage() . "</p>";
        $allChecks['config'] = false;
    }
    
    // Check 5: Expected Data Processing Flow
    echo "<h2>📊 Data Processing Flow</h2>";
    
    echo "<div style='background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
    echo "<h3>What Will Happen on Connection:</h3>";
    echo "<ol>";
    echo "<li><strong>Agreement Creation:</strong> Request 365 days of historical data</li>";
    echo "<li><strong>Bank Authorization:</strong> User authorizes access via bank portal</li>";
    echo "<li><strong>Transaction Fetch:</strong> API call with date range: ?date_from=" . date('Y-m-d', strtotime('-365 days')) . "&date_to=" . date('Y-m-d') . "</li>";
    echo "<li><strong>Data Processing:</strong> Handle both 'booked' and 'pending' transaction arrays</li>";
    echo "<li><strong>Field Extraction:</strong> Extract transactionAmount.amount, creditorName, bookingDate, etc.</li>";
    echo "<li><strong>Storage:</strong> Store in raw_transactions table with proper indexing</li>";
    echo "<li><strong>Analysis:</strong> Group by merchant, detect recurring patterns</li>";
    echo "<li><strong>Subscription Detection:</strong> Identify monthly, quarterly, yearly subscriptions</li>";
    echo "</ol>";
    echo "</div>";
    
    // Check 6: Expected Transaction Structure
    echo "<h2>📋 Expected GoCardless Response Structure</h2>";
    
    echo "<div style='background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
    echo "<h3>Supported Transaction Structures:</h3>";
    echo "<pre style='background: #ffffff; padding: 10px; border: 1px solid #ddd;'>";
    echo htmlspecialchars('{
  "transactions": {
    "booked": [
      {
        "transactionId": "2020111101899195-1",
        "transactionAmount": {
          "currency": "EUR",
          "amount": "-15.00"
        },
        "bookingDate": "2020-11-11",
        "creditorName": "Netflix",
        "remittanceInformationUnstructured": "NETFLIX SUBSCRIPTION"
      }
    ],
    "pending": [...]
  }
}');
    echo "</pre>";
    echo "<p><strong>OR flat array format:</strong></p>";
    echo "<pre style='background: #ffffff; padding: 10px; border: 1px solid #ddd;'>";
    echo htmlspecialchars('{
  "transactions": [
    {
      "transactionAmount": {"amount": "-15.00", "currency": "EUR"},
      "bookingDate": "2020-11-11",
      "creditorName": "Netflix"
    }
  ]
}');
    echo "</pre>";
    echo "</div>";
    
    // Overall Status
    echo "<h2>🎯 Overall System Status</h2>";
    
    $passedChecks = count(array_filter($allChecks));
    $totalChecks = count($allChecks);
    
    if ($passedChecks === $totalChecks) {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; margin: 15px 0; border-radius: 5px;'>";
        echo "<h3>🎉 SYSTEM READY!</h3>";
        echo "<p><strong>All systems are GO for your 4th GoCardless connection.</strong></p>";
        echo "<p>✅ Passed $passedChecks/$totalChecks checks</p>";
        echo "<p><strong>Expected outcome:</strong> Full year of transaction data will be fetched, processed, and analyzed for subscription patterns.</p>";
        echo "<p><a href='bank/unified-scan.php' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 18px;'>🏦 CONNECT BANK ACCOUNT</a></p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; margin: 15px 0; border-radius: 5px;'>";
        echo "<h3>⚠️ SYSTEM NOT READY</h3>";
        echo "<p><strong>Some components are missing or not working.</strong></p>";
        echo "<p>❌ Failed " . ($totalChecks - $passedChecks) . "/$totalChecks checks</p>";
        echo "<p><strong>Please fix the issues above before attempting the connection.</strong></p>";
        echo "</div>";
    }
    
    // Debug Information
    echo "<h2>🔧 Debug Information</h2>";
    echo "<details>";
    echo "<summary>Click to view technical details</summary>";
    echo "<pre>";
    echo "User ID: $userId\n";
    echo "Database Connection: " . ($pdo ? 'OK' : 'Failed') . "\n";
    echo "PHP Version: " . phpversion() . "\n";
    echo "Current Time: " . date('Y-m-d H:i:s') . "\n";
    echo "Date Range for Transactions: " . date('Y-m-d', strtotime('-365 days')) . " to " . date('Y-m-d') . "\n";
    echo "Expected Transaction Types: Outgoing payments (negative amounts)\n";
    echo "Subscription Detection: Monthly (28-32 days), Quarterly (85-95 days), Yearly (360-370 days)\n";
    echo "</pre>";
    echo "</details>";
    
} catch (Exception $e) {
    echo "<h2>❌ Critical Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='dashboard.php'>← Back to Dashboard</a></p>";
?>
