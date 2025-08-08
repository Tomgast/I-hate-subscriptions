<?php
/**
 * STRIPE ERROR DIAGNOSTIC TOOL
 * Diagnose 500 errors and path issues
 */

echo "<h1>🔍 Stripe Error Diagnostic</h1>";

echo "<div style='background: #e8f5e8; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<strong>🎯 Purpose:</strong><br>";
echo "This tool will diagnose the 500 error and path issues with your Stripe integration.";
echo "</div>";

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>📁 Step 1: Check File Paths</h2>";

$currentDir = __DIR__;
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<strong>Current directory:</strong> <code>{$currentDir}</code><br>";
echo "<strong>Looking for Stripe at:</strong> <code>{$currentDir}/stripe-php/init.php</code><br>";

// Check if the file exists
$stripePath = $currentDir . '/stripe-php/init.php';
$exists = file_exists($stripePath);
echo "<strong>File exists:</strong> " . ($exists ? "<span style='color: green;'>✅ Yes</span>" : "<span style='color: red;'>❌ No</span>") . "<br>";

if ($exists) {
    echo "<strong>File size:</strong> " . filesize($stripePath) . " bytes<br>";
    echo "<strong>File permissions:</strong> " . substr(sprintf('%o', fileperms($stripePath)), -4) . "<br>";
    echo "<strong>Is readable:</strong> " . (is_readable($stripePath) ? "<span style='color: green;'>✅ Yes</span>" : "<span style='color: red;'>❌ No</span>") . "<br>";
} else {
    // Check what's actually in the directory
    echo "<br><strong>Contents of current directory:</strong><br>";
    $files = scandir($currentDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $fullPath = $currentDir . '/' . $file;
            $type = is_dir($fullPath) ? '[DIR]' : '[FILE]';
            echo "• {$type} {$file}<br>";
            
            // If it's a directory that might contain Stripe
            if (is_dir($fullPath) && (strpos($file, 'stripe') !== false || $file === 'lib' || $file === 'vendor')) {
                echo "  <small>Checking {$file} for Stripe files...</small><br>";
                $subFiles = scandir($fullPath);
                foreach ($subFiles as $subFile) {
                    if ($subFile !== '.' && $subFile !== '..' && strpos($subFile, 'init') !== false) {
                        echo "    • Found: {$file}/{$subFile}<br>";
                    }
                }
            }
        }
    }
}

echo "</div>";

echo "<h2>🧪 Step 2: Test Stripe SDK Loading</h2>";

if ($exists) {
    try {
        echo "<p>Attempting to load Stripe SDK...</p>";
        
        // Capture any output/errors
        ob_start();
        $errorBefore = error_get_last();
        
        require_once $stripePath;
        
        $output = ob_get_clean();
        $errorAfter = error_get_last();
        
        if ($output) {
            echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>Output from init.php:</strong><br>";
            echo "<pre>" . htmlspecialchars($output) . "</pre>";
            echo "</div>";
        }
        
        if ($errorAfter && $errorAfter !== $errorBefore) {
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>Error loading Stripe SDK:</strong><br>";
            echo "<pre>" . htmlspecialchars($errorAfter['message']) . "</pre>";
            echo "<strong>File:</strong> " . $errorAfter['file'] . "<br>";
            echo "<strong>Line:</strong> " . $errorAfter['line'] . "<br>";
            echo "</div>";
        } else {
            echo "<span style='color: green;'>✅ Stripe SDK loaded without errors</span><br>";
            
            // Check if classes are available
            if (class_exists('Stripe\Stripe')) {
                echo "<span style='color: green;'>✅ Stripe\Stripe class available</span><br>";
                
                // Try to get version
                try {
                    $version = \Stripe\Stripe::VERSION;
                    echo "<span style='color: blue;'>ℹ️ Stripe SDK Version: {$version}</span><br>";
                } catch (Exception $e) {
                    echo "<span style='color: orange;'>⚠️ Could not get version: {$e->getMessage()}</span><br>";
                }
            } else {
                echo "<span style='color: red;'>❌ Stripe\Stripe class not available</span><br>";
                
                // Check what classes are available
                $classes = get_declared_classes();
                $stripeClasses = array_filter($classes, function($class) {
                    return strpos($class, 'Stripe') !== false;
                });
                
                if (!empty($stripeClasses)) {
                    echo "<strong>Available Stripe classes:</strong><br>";
                    foreach ($stripeClasses as $class) {
                        echo "• {$class}<br>";
                    }
                } else {
                    echo "<span style='color: red;'>❌ No Stripe classes found</span><br>";
                }
            }
        }
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24;'>";
        echo "<strong>❌ Exception loading Stripe SDK:</strong><br>";
        echo "<strong>Message:</strong> " . $e->getMessage() . "<br>";
        echo "<strong>File:</strong> " . $e->getFile() . "<br>";
        echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
        echo "<strong>Stack trace:</strong><br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
        echo "</div>";
    }
} else {
    echo "<p style='color: red;'>Cannot test SDK loading - file not found</p>";
}

echo "<h2>🔧 Step 3: Test Configuration Loading</h2>";

try {
    if (file_exists('config/secure_loader.php')) {
        echo "<span style='color: green;'>✅ secure_loader.php found</span><br>";
        
        require_once 'config/secure_loader.php';
        echo "<span style='color: green;'>✅ secure_loader.php loaded</span><br>";
        
        if (function_exists('getSecureConfig')) {
            echo "<span style='color: green;'>✅ getSecureConfig function available</span><br>";
            
            $stripeSecret = getSecureConfig('STRIPE_SECRET_KEY');
            if ($stripeSecret) {
                $maskedKey = substr($stripeSecret, 0, 7) . '...' . substr($stripeSecret, -4);
                echo "<span style='color: green;'>✅ STRIPE_SECRET_KEY found: {$maskedKey}</span><br>";
            } else {
                echo "<span style='color: red;'>❌ STRIPE_SECRET_KEY not found</span><br>";
            }
        } else {
            echo "<span style='color: red;'>❌ getSecureConfig function not available</span><br>";
        }
    } else {
        echo "<span style='color: red;'>❌ secure_loader.php not found</span><br>";
    }
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>❌ Error loading configuration:</strong><br>";
    echo $e->getMessage();
    echo "</div>";
}

echo "<h2>🔍 Step 4: Test Database Connection</h2>";

try {
    if (file_exists('config/db_config.php')) {
        echo "<span style='color: green;'>✅ db_config.php found</span><br>";
        
        require_once 'config/db_config.php';
        echo "<span style='color: green;'>✅ db_config.php loaded</span><br>";
        
        if (isset($pdo)) {
            echo "<span style='color: green;'>✅ PDO connection available</span><br>";
            
            // Test query
            $stmt = $pdo->query("SELECT 1");
            if ($stmt) {
                echo "<span style='color: green;'>✅ Database query successful</span><br>";
            } else {
                echo "<span style='color: red;'>❌ Database query failed</span><br>";
            }
        } else {
            echo "<span style='color: red;'>❌ PDO connection not available</span><br>";
        }
    } else {
        echo "<span style='color: red;'>❌ db_config.php not found</span><br>";
    }
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>❌ Database error:</strong><br>";
    echo $e->getMessage();
    echo "</div>";
}

echo "<h2>🧪 Step 5: Test stripe-scan.php</h2>";

try {
    echo "<p>Checking stripe-scan.php file...</p>";
    
    $scanFile = __DIR__ . '/bank/stripe-scan.php';
    if (file_exists($scanFile)) {
        echo "<span style='color: green;'>✅ stripe-scan.php exists</span><br>";
        echo "<strong>File size:</strong> " . filesize($scanFile) . " bytes<br>";
        echo "<strong>Is readable:</strong> " . (is_readable($scanFile) ? "<span style='color: green;'>✅ Yes</span>" : "<span style='color: red;'>❌ No</span>") . "<br>";
        
        // Check for syntax errors
        $output = [];
        $returnCode = 0;
        exec("php -l " . escapeshellarg($scanFile) . " 2>&1", $output, $returnCode);
        
        if ($returnCode === 0) {
            echo "<span style='color: green;'>✅ No syntax errors in stripe-scan.php</span><br>";
        } else {
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>❌ Syntax errors in stripe-scan.php:</strong><br>";
            echo "<pre>" . implode("\n", $output) . "</pre>";
            echo "</div>";
        }
    } else {
        echo "<span style='color: red;'>❌ stripe-scan.php not found</span><br>";
    }
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>❌ Error checking stripe-scan.php:</strong><br>";
    echo $e->getMessage();
    echo "</div>";
}

echo "<h2>🔧 Step 6: Recommended Fixes</h2>";

echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Based on the diagnostic results:</h3>";

if (!$exists) {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>⚠️ Stripe SDK Path Issue:</strong><br>";
    echo "The Stripe SDK was not found at the expected location. Please:<br>";
    echo "1. Verify you uploaded the stripe-php folder to your website root<br>";
    echo "2. Make sure the init.php file is inside the stripe-php folder<br>";
    echo "3. Check file permissions (should be 644 for files, 755 for folders)<br>";
    echo "</div>";
}

echo "<form method='POST' style='margin: 20px 0;'>";
echo "<h4>Manual Path Test:</h4>";
echo "<input type='text' name='manual_path' placeholder='Enter path to init.php (e.g., stripe-php/init.php)' style='width: 300px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;'>";
echo "<button type='submit' name='test_manual' style='background: #007bff; color: white; padding: 8px 16px; border: none; border-radius: 4px; margin-left: 10px; cursor: pointer;'>Test Path</button>";
echo "</form>";

echo "</div>";

// Handle manual path testing
if (isset($_POST['test_manual']) && !empty($_POST['manual_path'])) {
    $manualPath = $_POST['manual_path'];
    $fullManualPath = __DIR__ . '/' . ltrim($manualPath, '/');
    
    echo "<h2>🧪 Manual Path Test Results</h2>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>Testing path:</strong> <code>{$manualPath}</code><br>";
    echo "<strong>Full path:</strong> <code>{$fullManualPath}</code><br>";
    
    if (file_exists($fullManualPath)) {
        echo "<span style='color: green;'>✅ File found!</span><br>";
        
        try {
            require_once $fullManualPath;
            
            if (class_exists('Stripe\Stripe')) {
                echo "<span style='color: green;'>✅ Stripe SDK loaded successfully from manual path!</span><br>";
                
                echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
                echo "<strong>🎉 SUCCESS!</strong><br>";
                echo "I can now create the correct include file for you.<br>";
                echo "<form method='POST' style='margin-top: 10px;'>";
                echo "<input type='hidden' name='create_include' value='1'>";
                echo "<input type='hidden' name='correct_path' value='{$manualPath}'>";
                echo "<button type='submit' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Create Correct Include File</button>";
                echo "</form>";
                echo "</div>";
            } else {
                echo "<span style='color: red;'>❌ File loaded but Stripe classes not available</span><br>";
            }
        } catch (Exception $e) {
            echo "<span style='color: red;'>❌ Error loading file: {$e->getMessage()}</span><br>";
        }
    } else {
        echo "<span style='color: red;'>❌ File not found at this path</span><br>";
    }
    echo "</div>";
}

// Handle include file creation
if (isset($_POST['create_include']) && !empty($_POST['correct_path'])) {
    $correctPath = $_POST['correct_path'];
    
    echo "<h2>🔄 Creating Correct Include File</h2>";
    
    $includeContent = '<?php
/**
 * STRIPE SDK INCLUDE
 * Include this file to use Stripe functionality
 */

// Include Stripe SDK
require_once __DIR__ . \'/../' . $correctPath . '\';

// Load Stripe configuration
require_once __DIR__ . \'/../config/secure_loader.php\';

// Set Stripe API key
$stripeSecretKey = getSecureConfig(\'STRIPE_SECRET_KEY\');
if ($stripeSecretKey) {
    \Stripe\Stripe::setApiKey($stripeSecretKey);
} else {
    throw new Exception(\'STRIPE_SECRET_KEY not found in configuration\');
}

// Stripe is now ready to use!
?>';
    
    file_put_contents(__DIR__ . '/includes/stripe-sdk.php', $includeContent);
    
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; margin: 10px 0;'>";
    echo "<strong>✅ Include file updated!</strong><br>";
    echo "Path corrected to: <code>{$correctPath}</code><br>";
    echo "You can now test the stripe-scan.php page.";
    echo "</div>";
}

echo "<div style='margin: 20px 0;'>";
echo "<a href='bank/stripe-scan.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test Stripe Scan</a>";
echo "<a href='stripe-migration-plan.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Back to Migration Plan</a>";
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
button {
    cursor: pointer;
    transition: opacity 0.2s;
}
button:hover {
    opacity: 0.9;
}
</style>
