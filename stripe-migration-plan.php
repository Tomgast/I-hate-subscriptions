<?php
/**
 * COMPREHENSIVE STRIPE FINANCIAL CONNECTIONS MIGRATION PLAN
 * Safe, step-by-step migration from TrueLayer to Stripe Financial Connections
 */

session_start();
require_once 'config/db_config.php';

if (!isset($_SESSION['user_id'])) {
    die('Please log in first');
}

$userId = $_SESSION['user_id'];
$userEmail = $_SESSION['user_email'] ?? '';

echo "<h1>📋 Stripe Financial Connections Migration Plan</h1>";
echo "<p><strong>User ID:</strong> {$userId} | <strong>Email:</strong> {$userEmail}</p>";

echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<strong>⚠️ Critical Migration Strategy:</strong><br>";
echo "TrueLayer is deeply integrated into your system. We'll migrate safely by creating parallel Stripe integration, testing thoroughly, then switching over only when everything works perfectly.";
echo "</div>";

echo "<h2>🔍 Current System Analysis</h2>";

try {
    $pdo = getDBConnection();
    
    // Analyze current TrueLayer integration
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>📊 TrueLayer Integration Points:</h3>";
    
    // Check bank_connections table
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM bank_connections WHERE user_id = ?");
    $stmt->execute([$userId]);
    $bankConnections = $stmt->fetch()['count'];
    
    // Check bank_scans table
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM bank_scans WHERE user_id = ?");
    $stmt->execute([$userId]);
    $bankScans = $stmt->fetch()['count'];
    
    echo "<strong>Current Data:</strong><br>";
    echo "• Bank Connections: {$bankConnections}<br>";
    echo "• Bank Scans: {$bankScans}<br>";
    echo "• Status: " . ($bankConnections > 0 ? "<span style='color: orange;'>Has TrueLayer data</span>" : "<span style='color: green;'>Clean slate</span>");
    echo "</div>";
    
    // Check for TrueLayer files
    $trueLayerFiles = [
        'includes/bank_service.php' => 'Core TrueLayer service',
        'bank/scan.php' => 'Bank scan page',
        'bank/callback.php' => 'TrueLayer callback',
        'debug-truelayer.php' => 'Debug tools'
    ];
    
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>📁 TrueLayer Files Found:</h3>";
    foreach ($trueLayerFiles as $file => $description) {
        $exists = file_exists(__DIR__ . '/' . $file);
        $status = $exists ? "<span style='color: green;'>✅ Found</span>" : "<span style='color: red;'>❌ Missing</span>";
        echo "• <strong>{$file}</strong>: {$description} - {$status}<br>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24;'>";
    echo "<strong>Analysis Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<h2>🛡️ Safe Migration Strategy</h2>";

echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>🎯 Migration Principles:</h3>";
echo "<ol>";
echo "<li><strong>Parallel Development</strong> - Build Stripe integration alongside TrueLayer</li>";
echo "<li><strong>Non-Breaking Changes</strong> - Keep existing system working</li>";
echo "<li><strong>Incremental Testing</strong> - Test each component thoroughly</li>";
echo "<li><strong>Rollback Plan</strong> - Always able to revert to TrueLayer</li>";
echo "<li><strong>Data Preservation</strong> - Never lose existing subscription data</li>";
echo "</ol>";
echo "</div>";

echo "<h2>📋 Detailed Migration Plan</h2>";

$migrationSteps = [
    [
        'phase' => 'Phase 1: Foundation',
        'color' => '#e8f5e8',
        'border' => '#c3e6cb',
        'steps' => [
            'Install Stripe PHP SDK (if not already installed)',
            'Create StripeFinancialService class (separate from existing BankService)',
            'Add bank_connection_sessions table for Stripe session tracking',
            'Create Stripe configuration validation tool',
            'Test basic Stripe API connectivity'
        ]
    ],
    [
        'phase' => 'Phase 2: Core Integration',
        'color' => '#e7f3ff',
        'border' => '#b3d9ff',
        'steps' => [
            'Build Stripe Financial Connections session creation',
            'Build transaction retrieval and parsing',
            'Build subscription detection algorithm for Stripe data',
            'Create database storage methods for Stripe connections',
            'Test subscription detection with mock Stripe data'
        ]
    ],
    [
        'phase' => 'Phase 3: User Interface',
        'color' => '#fff3cd',
        'border' => '#ffeaa7',
        'steps' => [
            'Create new bank scan page (bank/stripe-scan.php)',
            'Create Stripe callback handler (bank/stripe-callback.php)',
            'Create Stripe connection management interface',
            'Add Stripe connection status to dashboard',
            'Test complete user flow with Stripe'
        ]
    ],
    [
        'phase' => 'Phase 4: Integration & Testing',
        'color' => '#f8d7da',
        'border' => '#f5c6cb',
        'steps' => [
            'Update main bank scan page to offer both TrueLayer and Stripe',
            'Test parallel operation of both systems',
            'Verify data consistency between systems',
            'Test error handling and edge cases',
            'Performance testing with real bank connections'
        ]
    ],
    [
        'phase' => 'Phase 5: Migration & Cleanup',
        'color' => '#e2e3e5',
        'border' => '#d6d8db',
        'steps' => [
            'Switch default bank integration to Stripe',
            'Migrate existing TrueLayer connections (if any)',
            'Update all references to use Stripe by default',
            'Disable TrueLayer integration (keep as backup)',
            'Remove TrueLayer code only after 30-day verification period'
        ]
    ]
];

foreach ($migrationSteps as $phase) {
    echo "<div style='background: {$phase['color']}; border: 1px solid {$phase['border']}; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h3>{$phase['phase']}</h3>";
    echo "<ol>";
    foreach ($phase['steps'] as $step) {
        echo "<li>{$step}</li>";
    }
    echo "</ol>";
    echo "</div>";
}

echo "<h2>🔧 Risk Mitigation</h2>";

echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>🛡️ Safety Measures:</h3>";
echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 15px;'>";

echo "<div style='background: white; padding: 10px; border-radius: 5px; border: 1px solid #ddd;'>";
echo "<h4>📋 Before Each Step:</h4>";
echo "<ul style='font-size: 14px;'>";
echo "<li>Backup database</li>";
echo "<li>Test on staging environment</li>";
echo "<li>Document changes made</li>";
echo "<li>Verify existing functionality</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: white; padding: 10px; border-radius: 5px; border: 1px solid #ddd;'>";
echo "<h4>🚨 Rollback Triggers:</h4>";
echo "<ul style='font-size: 14px;'>";
echo "<li>Any existing functionality breaks</li>";
echo "<li>Data loss or corruption</li>";
echo "<li>User authentication issues</li>";
echo "<li>Payment processing problems</li>";
echo "</ul>";
echo "</div>";

echo "</div>";
echo "</div>";

echo "<h2>🧪 Testing Strategy</h2>";

echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>📊 Testing Phases:</h3>";
echo "<ol>";
echo "<li><strong>Unit Testing:</strong> Test each Stripe service method individually</li>";
echo "<li><strong>Integration Testing:</strong> Test Stripe + database + UI together</li>";
echo "<li><strong>User Flow Testing:</strong> Complete bank connection → subscription detection → dashboard display</li>";
echo "<li><strong>Parallel Testing:</strong> Run both TrueLayer and Stripe simultaneously</li>";
echo "<li><strong>Production Testing:</strong> Test with real bank account (small scale)</li>";
echo "</ol>";
echo "</div>";

echo "<h2>⚡ Quick Start Actions</h2>";

echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>🚀 Immediate Next Steps:</h3>";
echo "<ol>";
echo "<li><strong>Diagnose Current Error:</strong> Fix the stripe-scan.php internal server error</li>";
echo "<li><strong>Validate Stripe Setup:</strong> Ensure Stripe SDK and credentials are working</li>";
echo "<li><strong>Create Minimal Stripe Test:</strong> Simple connection test without full integration</li>";
echo "<li><strong>Plan Phase 1:</strong> Start with foundation components</li>";
echo "</ol>";

echo "<form method='POST' style='margin: 15px 0;'>";
echo "<input type='hidden' name='action' value='diagnose_error'>";
echo "<button type='submit' style='background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>🔍 Diagnose Current Error</button>";
echo "</form>";

echo "<form method='POST' style='margin: 15px 0;'>";
echo "<input type='hidden' name='action' value='validate_stripe'>";
echo "<button type='submit' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>✅ Validate Stripe Setup</button>";
echo "</form>";

echo "<form method='POST' style='margin: 15px 0;'>";
echo "<input type='hidden' name='action' value='create_minimal_test'>";
echo "<button type='submit' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>🧪 Create Minimal Stripe Test</button>";
echo "</form>";
echo "</div>";

// Handle diagnostic actions
if ($_POST && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'diagnose_error':
                echo "<h2>🔍 Diagnosing stripe-scan.php Error</h2>";
                
                // Check if Stripe SDK is available
                if (!class_exists('Stripe\Stripe')) {
                    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; color: #721c24;'>";
                    echo "❌ <strong>Issue Found:</strong> Stripe PHP SDK not installed<br>";
                    echo "<strong>Solution:</strong> Install Stripe SDK via Composer or download manually";
                    echo "</div>";
                } else {
                    echo "<span style='color: green;'>✅ Stripe SDK available</span><br>";
                }
                
                // Check if stripe-scan.php exists and has correct path
                $stripeScanPath = __DIR__ . '/bank/stripe-scan.php';
                if (file_exists($stripeScanPath)) {
                    echo "<span style='color: green;'>✅ stripe-scan.php exists</span><br>";
                    
                    // Check for syntax errors
                    $output = [];
                    $returnCode = 0;
                    exec("php -l {$stripeScanPath} 2>&1", $output, $returnCode);
                    
                    if ($returnCode === 0) {
                        echo "<span style='color: green;'>✅ No syntax errors</span><br>";
                    } else {
                        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; color: #721c24;'>";
                        echo "❌ <strong>Syntax Error Found:</strong><br>";
                        echo "<pre>" . implode("\n", $output) . "</pre>";
                        echo "</div>";
                    }
                } else {
                    echo "<span style='color: red;'>❌ stripe-scan.php not found</span><br>";
                }
                
                // Check include paths
                $requiredFiles = [
                    '../config/db_config.php',
                    '../includes/stripe_financial_service.php'
                ];
                
                foreach ($requiredFiles as $file) {
                    $fullPath = __DIR__ . '/bank/' . $file;
                    if (file_exists($fullPath)) {
                        echo "<span style='color: green;'>✅ {$file} found</span><br>";
                    } else {
                        echo "<span style='color: red;'>❌ {$file} missing</span><br>";
                    }
                }
                break;
                
            case 'validate_stripe':
                echo "<h2>✅ Validating Stripe Setup</h2>";
                
                require_once 'config/secure_loader.php';
                $stripeSecret = getSecureConfig('STRIPE_SECRET_KEY');
                $stripePublishable = getSecureConfig('STRIPE_PUBLISHABLE_KEY');
                
                if ($stripeSecret && $stripePublishable) {
                    echo "<span style='color: green;'>✅ Stripe credentials found</span><br>";
                    
                    // Test API connection
                    if (class_exists('Stripe\Stripe')) {
                        \Stripe\Stripe::setApiKey($stripeSecret);
                        
                        try {
                            $account = \Stripe\Account::retrieve();
                            echo "<span style='color: green;'>✅ Stripe API connection successful</span><br>";
                            echo "<span style='color: blue;'>ℹ️ Account ID: {$account->id}</span><br>";
                        } catch (Exception $e) {
                            echo "<span style='color: red;'>❌ Stripe API error: {$e->getMessage()}</span><br>";
                        }
                    } else {
                        echo "<span style='color: red;'>❌ Stripe SDK not available</span><br>";
                    }
                } else {
                    echo "<span style='color: red;'>❌ Stripe credentials missing</span><br>";
                }
                break;
                
            case 'create_minimal_test':
                echo "<h2>🧪 Creating Minimal Stripe Test</h2>";
                
                $minimalTest = '<?php
// Minimal Stripe Financial Connections Test
session_start();

echo "<h1>Stripe Financial Connections - Minimal Test</h1>";

// Check if Stripe SDK is available
if (!class_exists("Stripe\Stripe")) {
    echo "<p style=\"color: red;\">❌ Stripe SDK not found. Please install Stripe PHP SDK.</p>";
    echo "<p>Install via Composer: <code>composer require stripe/stripe-php</code></p>";
    exit;
}

// Load configuration
require_once "../config/secure_loader.php";
$stripeSecret = getSecureConfig("STRIPE_SECRET_KEY");

if (!$stripeSecret) {
    echo "<p style=\"color: red;\">❌ STRIPE_SECRET_KEY not found in configuration.</p>";
    exit;
}

\Stripe\Stripe::setApiKey($stripeSecret);

try {
    // Test basic API connectivity
    $account = \Stripe\Account::retrieve();
    echo "<p style=\"color: green;\">✅ Stripe API connection successful!</p>";
    echo "<p>Account ID: " . $account->id . "</p>";
    echo "<p>Country: " . $account->country . "</p>";
    
    // Test Financial Connections availability
    echo "<h2>Financial Connections Test</h2>";
    
    if (isset($_POST["test_session"])) {
        $session = \Stripe\FinancialConnections\Session::create([
            "account_holder" => ["type" => "consumer"],
            "permissions" => ["payment_method", "balances"],
            "filters" => ["countries" => ["US", "GB", "NL"]],
            "return_url" => "https://" . $_SERVER["HTTP_HOST"] . "/test-complete.html",
        ]);
        
        echo "<p style=\"color: green;\">✅ Financial Connections session created!</p>";
        echo "<p>Session ID: " . $session->id . "</p>";
        echo "<p><a href=\"" . $session->url . "\" target=\"_blank\">Test Connection</a></p>";
    } else {
        echo "<form method=\"POST\">";
        echo "<input type=\"hidden\" name=\"test_session\" value=\"1\">";
        echo "<button type=\"submit\">Create Test Session</button>";
        echo "</form>";
    }
    
} catch (Exception $e) {
    echo "<p style=\"color: red;\">❌ Error: " . $e->getMessage() . "</p>";
}
?>';
                
                file_put_contents(__DIR__ . '/bank/stripe-minimal-test.php', $minimalTest);
                echo "<span style='color: green;'>✅ Minimal test created: bank/stripe-minimal-test.php</span><br>";
                echo "<p><a href='bank/stripe-minimal-test.php' target='_blank'>🧪 Run Minimal Test</a></p>";
                break;
        }
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24;'>";
        echo "<strong>❌ Error:</strong> " . $e->getMessage();
        echo "</div>";
    }
}

echo "<h2>📞 Support & Next Steps</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>This migration plan ensures:</strong></p>";
echo "<ul>";
echo "<li>✅ Your existing system stays working throughout migration</li>";
echo "<li>✅ Each step is tested before proceeding</li>";
echo "<li>✅ You can rollback at any point</li>";
echo "<li>✅ No data loss or service interruption</li>";
echo "<li>✅ Gradual transition with parallel systems</li>";
echo "</ul>";
echo "<p><strong>Start with diagnosing the current error, then we'll proceed step by step through the migration plan.</strong></p>";
echo "</div>";
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}
h1, h2, h3 {
    color: #333;
}
button {
    cursor: pointer;
    transition: opacity 0.2s;
}
button:hover {
    opacity: 0.9;
}
pre {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 5px;
    overflow-x: auto;
}
</style>
