<?php
/**
 * STRIPE PHP SDK INSTALLER
 * Manual installation tool for Plesk/shared hosting environments
 */

session_start();
require_once 'config/db_config.php';

if (!isset($_SESSION['user_id'])) {
    die('Please log in first');
}

echo "<h1>📦 Stripe PHP SDK Installer</h1>";

echo "<div style='background: #e8f5e8; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<strong>🎯 Purpose:</strong><br>";
echo "This tool will help you install the Stripe PHP SDK on your Plesk hosting environment where Composer might not be available.";
echo "</div>";

// Check current status
echo "<h2>📊 Current Status</h2>";

$stripeClassExists = class_exists('Stripe\Stripe');
$vendorExists = file_exists(__DIR__ . '/vendor/autoload.php');
$stripeLibExists = file_exists(__DIR__ . '/lib/stripe-php/init.php');

echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<strong>Installation Status:</strong><br>";
echo "• Stripe Class Available: " . ($stripeClassExists ? "<span style='color: green;'>✅ Yes</span>" : "<span style='color: red;'>❌ No</span>") . "<br>";
echo "• Composer Vendor Directory: " . ($vendorExists ? "<span style='color: green;'>✅ Found</span>" : "<span style='color: orange;'>⚠️ Not Found</span>") . "<br>";
echo "• Manual Stripe Library: " . ($stripeLibExists ? "<span style='color: green;'>✅ Found</span>" : "<span style='color: orange;'>⚠️ Not Found</span>") . "<br>";
echo "</div>";

if ($stripeClassExists) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
    echo "<strong>✅ Stripe SDK Already Available!</strong><br>";
    echo "The Stripe PHP SDK is already installed and working on your system.";
    echo "</div>";
} else {
    echo "<h2>🔧 Installation Options</h2>";
    
    // Option 1: Try Composer
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>Option 1: Install via Composer (Recommended)</h3>";
    echo "<p>If Composer is available on your server:</p>";
    echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace;'>";
    echo "composer require stripe/stripe-php";
    echo "</div>";
    echo "<form method='POST'>";
    echo "<input type='hidden' name='action' value='try_composer'>";
    echo "<button type='submit' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Try Composer Install</button>";
    echo "</form>";
    echo "</div>";
    
    // Option 2: Manual Download
    echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>Option 2: Manual Download & Install</h3>";
    echo "<p>Download and install the Stripe PHP SDK manually (works on all hosting environments):</p>";
    echo "<form method='POST'>";
    echo "<input type='hidden' name='action' value='manual_install'>";
    echo "<button type='submit' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Download & Install Manually</button>";
    echo "</form>";
    echo "</div>";
    
    // Option 3: Create minimal Stripe wrapper
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>Option 3: Create Minimal Stripe Wrapper</h3>";
    echo "<p>Create a lightweight Stripe wrapper for basic Financial Connections functionality:</p>";
    echo "<form method='POST'>";
    echo "<input type='hidden' name='action' value='create_wrapper'>";
    echo "<button type='submit' style='background: #ffc107; color: black; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Create Stripe Wrapper</button>";
    echo "</form>";
    echo "</div>";
}

// Handle installation actions
if ($_POST && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'try_composer':
                echo "<h2>🔄 Trying Composer Installation</h2>";
                
                // Check if composer is available
                $output = [];
                $returnCode = 0;
                exec('composer --version 2>&1', $output, $returnCode);
                
                if ($returnCode === 0) {
                    echo "<span style='color: green;'>✅ Composer found: " . implode(' ', $output) . "</span><br>";
                    
                    // Try to install Stripe
                    echo "<p>Installing Stripe PHP SDK...</p>";
                    $installOutput = [];
                    $installCode = 0;
                    exec('cd ' . __DIR__ . ' && composer require stripe/stripe-php 2>&1', $installOutput, $installCode);
                    
                    if ($installCode === 0) {
                        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724;'>";
                        echo "<strong>✅ Stripe SDK Installed Successfully!</strong><br>";
                        echo "You can now use: <code>require_once 'vendor/autoload.php';</code>";
                        echo "</div>";
                    } else {
                        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24;'>";
                        echo "<strong>❌ Composer Install Failed:</strong><br>";
                        echo "<pre>" . implode("\n", $installOutput) . "</pre>";
                        echo "</div>";
                    }
                } else {
                    echo "<span style='color: red;'>❌ Composer not available on this server</span><br>";
                    echo "<p>Try the manual installation option instead.</p>";
                }
                break;
                
            case 'manual_install':
                echo "<h2>🔄 Manual Installation</h2>";
                
                // Create lib directory
                $libDir = __DIR__ . '/lib';
                if (!file_exists($libDir)) {
                    mkdir($libDir, 0755, true);
                    echo "<span style='color: green;'>✅ Created lib directory</span><br>";
                }
                
                // Download Stripe PHP SDK
                $stripeZipUrl = 'https://github.com/stripe/stripe-php/archive/refs/heads/master.zip';
                $zipFile = $libDir . '/stripe-php-master.zip';
                
                echo "<p>Downloading Stripe PHP SDK...</p>";
                
                $zipContent = file_get_contents($stripeZipUrl);
                if ($zipContent !== false) {
                    file_put_contents($zipFile, $zipContent);
                    echo "<span style='color: green;'>✅ Downloaded Stripe SDK</span><br>";
                    
                    // Extract ZIP
                    $zip = new ZipArchive;
                    if ($zip->open($zipFile) === TRUE) {
                        $zip->extractTo($libDir);
                        $zip->close();
                        
                        // Rename directory
                        $extractedDir = $libDir . '/stripe-php-master';
                        $finalDir = $libDir . '/stripe-php';
                        
                        if (file_exists($extractedDir)) {
                            rename($extractedDir, $finalDir);
                            echo "<span style='color: green;'>✅ Extracted and organized Stripe SDK</span><br>";
                            
                            // Clean up
                            unlink($zipFile);
                            
                            // Create autoloader
                            $autoloaderCode = '<?php
// Manual Stripe PHP SDK Autoloader
require_once __DIR__ . \'/stripe-php/init.php\';
?>';
                            file_put_contents($libDir . '/stripe-autoload.php', $autoloaderCode);
                            
                            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724;'>";
                            echo "<strong>✅ Stripe SDK Installed Successfully!</strong><br>";
                            echo "You can now use: <code>require_once 'lib/stripe-autoload.php';</code><br>";
                            echo "Installation location: <code>lib/stripe-php/</code>";
                            echo "</div>";
                        } else {
                            echo "<span style='color: red;'>❌ Extraction failed</span><br>";
                        }
                    } else {
                        echo "<span style='color: red;'>❌ Could not extract ZIP file</span><br>";
                    }
                } else {
                    echo "<span style='color: red;'>❌ Could not download Stripe SDK</span><br>";
                }
                break;
                
            case 'create_wrapper':
                echo "<h2>🔄 Creating Minimal Stripe Wrapper</h2>";
                
                $wrapperCode = '<?php
/**
 * MINIMAL STRIPE WRAPPER
 * Lightweight wrapper for Stripe Financial Connections
 */

class StripeWrapper {
    private $apiKey;
    private $apiBase = \'https://api.stripe.com\';
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    /**
     * Make API request to Stripe
     */
    private function makeRequest($method, $endpoint, $data = []) {
        $url = $this->apiBase . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            \'Authorization: Bearer \' . $this->apiKey,
            \'Content-Type: application/x-www-form-urlencoded\'
        ]);
        
        if ($method === \'POST\') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 400) {
            throw new Exception(\'Stripe API Error: \' . $response);
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Create Financial Connections Session
     */
    public function createFinancialConnectionsSession($params) {
        return $this->makeRequest(\'POST\', \'/v1/financial_connections/sessions\', $params);
    }
    
    /**
     * Retrieve Financial Connections Session
     */
    public function retrieveFinancialConnectionsSession($sessionId) {
        return $this->makeRequest(\'GET\', \'/v1/financial_connections/sessions/\' . $sessionId);
    }
    
    /**
     * List Financial Connections Accounts
     */
    public function listFinancialConnectionsAccounts($sessionId) {
        return $this->makeRequest(\'GET\', \'/v1/financial_connections/accounts?session=\' . $sessionId);
    }
    
    /**
     * List Financial Connections Transactions
     */
    public function listFinancialConnectionsTransactions($accountId, $limit = 100) {
        return $this->makeRequest(\'GET\', \'/v1/financial_connections/transactions?account=\' . $accountId . \'&limit=\' . $limit);
    }
    
    /**
     * Retrieve Account Info
     */
    public function retrieveAccount() {
        return $this->makeRequest(\'GET\', \'/v1/account\');
    }
}

// Usage example:
// require_once \'lib/stripe-wrapper.php\';
// $stripe = new StripeWrapper($your_stripe_secret_key);
// $session = $stripe->createFinancialConnectionsSession([...]);
?>';
                
                $libDir = __DIR__ . '/lib';
                if (!file_exists($libDir)) {
                    mkdir($libDir, 0755, true);
                }
                
                file_put_contents($libDir . '/stripe-wrapper.php', $wrapperCode);
                
                echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724;'>";
                echo "<strong>✅ Stripe Wrapper Created!</strong><br>";
                echo "You can now use: <code>require_once 'lib/stripe-wrapper.php';</code><br>";
                echo "This provides basic Stripe Financial Connections functionality without the full SDK.";
                echo "</div>";
                break;
        }
        
        // Refresh page to show updated status
        echo "<script>setTimeout(() => location.reload(), 3000);</script>";
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24;'>";
        echo "<strong>❌ Error:</strong> " . $e->getMessage();
        echo "</div>";
    }
}

echo "<h2>📋 Next Steps After Installation</h2>";
echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px;'>";
echo "<h3>🎯 Once Stripe SDK is installed:</h3>";
echo "<ol>";
echo "<li>Test the installation with the minimal test tool</li>";
echo "<li>Update your Stripe Financial Connections service</li>";
echo "<li>Test bank connection functionality</li>";
echo "<li>Proceed with the migration plan</li>";
echo "</ol>";
echo "</div>";

echo "<div style='margin: 20px 0;'>";
echo "<a href='stripe-migration-plan.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Back to Migration Plan</a>";
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
button {
    cursor: pointer;
    transition: opacity 0.2s;
}
button:hover {
    opacity: 0.9;
}
code {
    background: #f8f9fa;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
}
pre {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 5px;
    overflow-x: auto;
}
</style>
