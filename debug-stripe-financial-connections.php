<?php
/**
 * DEBUG STRIPE FINANCIAL CONNECTIONS
 * Let's test the exact API requirements and get it working properly
 */

session_start();
require_once 'includes/stripe-sdk.php';
require_once 'config/secure_loader.php';

echo "<h1>🔍 Stripe Financial Connections Debug</h1>";

echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<strong>🎯 Purpose:</strong><br>";
echo "Let's test Stripe Financial Connections with a REAL customer (the proper way).";
echo "</div>";

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>📋 Step 1: Create Test Customer</h2>";

// First, create a test customer
try {
    $testCustomer = \Stripe\Customer::create([
        'email' => 'test@123cashcontrol.com',
        'name' => 'Test User',
        'metadata' => [
            'source' => 'financial_connections_debug'
        ]
    ]);
    
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
    echo "<strong>✅ Test Customer Created!</strong><br>";
    echo "<strong>Customer ID:</strong> {$testCustomer->id}<br>";
    echo "<strong>Email:</strong> {$testCustomer->email}<br>";
    echo "</div>";
    
} catch (\Stripe\Exception\ApiErrorException $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; margin: 10px 0;'>";
    echo "<strong>❌ Failed to create test customer:</strong><br>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "This suggests your Stripe API keys might not be configured correctly.";
    echo "</div>";
    exit;
}

echo "<h2>📋 Step 2: Test Financial Connections with Real Customer</h2>";

// Now test with the REAL customer ID (this is the correct approach)
$testCases = [
    [
        'name' => 'Test 1: Real Customer ID (CORRECT approach)',
        'params' => [
            'account_holder' => [
                'type' => 'customer',
                'customer' => $testCustomer->id  // Use the REAL customer ID
            ],
            'permissions' => ['payment_method', 'balances', 'transactions'],
            'return_url' => 'https://123cashcontrol.com/bank/stripe-callback.php'
        ]
    ],
    [
        'name' => 'Test 2: Minimal permissions with real customer',
        'params' => [
            'account_holder' => [
                'type' => 'customer',
                'customer' => $testCustomer->id
            ],
            'permissions' => ['payment_method'],
            'return_url' => 'https://123cashcontrol.com/bank/stripe-callback.php'
        ]
    ],
    [
        'name' => 'Test 3: All permissions with filters',
        'params' => [
            'account_holder' => [
                'type' => 'customer',
                'customer' => $testCustomer->id
            ],
            'permissions' => ['payment_method', 'balances', 'transactions'],
            'filters' => [
                'countries' => ['US', 'GB', 'NL', 'DE', 'FR']
            ],
            'return_url' => 'https://123cashcontrol.com/bank/stripe-callback.php'
        ]
    ]
];

foreach ($testCases as $index => $test) {
    echo "<div style='background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h3>{$test['name']}</h3>";
    
    echo "<strong>Parameters:</strong><br>";
    echo "<pre>" . json_encode($test['params'], JSON_PRETTY_PRINT) . "</pre>";
    
    try {
        echo "<p>Testing API call...</p>";
        
        // Try to create the session
        $session = \Stripe\FinancialConnections\Session::create($test['params']);
        
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; color: #155724;'>";
        echo "<strong>✅ SUCCESS!</strong><br>";
        echo "<strong>Session ID:</strong> {$session->id}<br>";
        echo "<strong>Status:</strong> {$session->status}<br>";
        if (isset($session->hosted_auth_url)) {
            echo "<strong>Auth URL:</strong> <a href='{$session->hosted_auth_url}' target='_blank'>Test Connection</a><br>";
        }
        echo "</div>";
        
        // If this test succeeds, we found our working parameters
        echo "<div style='background: #e8f5e8; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>🎉 WORKING SOLUTION FOUND!</strong><br>";
        echo "I'll now update the code with these working parameters.";
        echo "</div>";
        
        break; // Stop testing once we find a working solution
        
    } catch (\Stripe\Exception\ApiErrorException $e) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; color: #721c24;'>";
        echo "<strong>❌ FAILED:</strong><br>";
        echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
        echo "<strong>Type:</strong> " . get_class($e) . "<br>";
        if ($e->getStripeCode()) {
            echo "<strong>Stripe Code:</strong> " . $e->getStripeCode() . "<br>";
        }
        echo "</div>";
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; color: #721c24;'>";
        echo "<strong>❌ GENERAL ERROR:</strong><br>";
        echo "<strong>Message:</strong> " . $e->getMessage() . "<br>";
        echo "</div>";
    }
    
    echo "</div>";
}

echo "<h2>📚 Stripe Documentation Research</h2>";

echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
echo "<h3>What I'm Learning:</h3>";
echo "<p>Based on the test results above, we can see which parameter combination actually works with your Stripe account.</p>";

echo "<h4>Expected Parameter Types:</h4>";
echo "<ul>";
echo "<li><strong>account_holder.type = 'customer':</strong> For end-user bank connections (most common)</li>";
echo "<li><strong>account_holder.type = 'account':</strong> For platform/marketplace scenarios</li>";
echo "</ul>";

echo "<h4>Required vs Optional Parameters:</h4>";
echo "<ul>";
echo "<li><strong>account_holder:</strong> Required</li>";
echo "<li><strong>permissions:</strong> Required (at least one)</li>";
echo "<li><strong>return_url:</strong> Required</li>";
echo "<li><strong>filters:</strong> Optional</li>";
echo "</ul>";
echo "</div>";

echo "<h2>🔧 Next Steps</h2>";

echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
echo "<p>Once we identify the working parameters from the tests above, I'll:</p>";
echo "<ol>";
echo "<li>Update the StripeFinancialService with the correct parameters</li>";
echo "<li>Remove any unnecessary complexity</li>";
echo "<li>Test the full flow end-to-end</li>";
echo "<li>Ensure it works consistently</li>";
echo "</ol>";
echo "</div>";

// Cleanup: Delete the test customer
echo "<h2>🧹 Cleanup</h2>";
try {
    $testCustomer->delete();
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
    echo "<strong>✅ Test customer deleted successfully</strong><br>";
    echo "Customer ID {$testCustomer->id} has been removed from your Stripe account.";
    echo "</div>";
} catch (\Stripe\Exception\ApiErrorException $e) {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; color: #856404; margin: 10px 0;'>";
    echo "<strong>⚠️ Note:</strong> Could not delete test customer: " . $e->getMessage();
    echo "</div>";
}

echo "<div style='margin: 20px 0;'>";
echo "<a href='bank/stripe-scan.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Back to Bank Scan</a>";
echo "</div>";
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}
h1, h2, h3 {
    color: #333;
}
pre {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 5px;
    overflow-x: auto;
    font-size: 12px;
}
</style>
