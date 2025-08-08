<?php
/**
 * Server Configuration Diagnostic
 * Checks where secure-config.php is located on the server
 */

echo "<h1>🔍 Server Configuration Diagnostic</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 3px; }
</style>";

echo "<h2>📁 Server Environment</h2>";
echo "<div class='info'>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</div>";
echo "<div class='info'>Current User: " . get_current_user() . "</div>";
echo "<div class='info'>HTTP Host: " . ($_SERVER['HTTP_HOST'] ?? 'unknown') . "</div>";
echo "<div class='info'>Script Path: " . __FILE__ . "</div>";
echo "<div class='info'>Script Dir: " . __DIR__ . "</div>";

echo "<h2>🔍 Checking Possible Config Locations</h2>";

$possiblePaths = [
    // Outside document root (most likely location)
    dirname($_SERVER['DOCUMENT_ROOT']) . '/secure-config.php',
    $_SERVER['DOCUMENT_ROOT'] . '/../secure-config.php',
    
    // Project root variations
    __DIR__ . '/../secure-config.php',
    __DIR__ . '/../../secure-config.php',
    
    // Common Plesk hosting paths
    dirname(dirname($_SERVER['DOCUMENT_ROOT'])) . '/secure-config.php',
    '/var/www/vhosts/' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/secure-config.php',
    '/home/' . get_current_user() . '/secure-config.php',
];

$foundPath = null;

foreach ($possiblePaths as $path) {
    $exists = file_exists($path);
    $readable = $exists ? is_readable($path) : false;
    $size = $exists ? filesize($path) : 0;
    
    echo "<div>";
    echo "<strong>Path:</strong> $path<br>";
    echo "<strong>Exists:</strong> " . ($exists ? "<span class='success'>YES</span>" : "<span class='error'>NO</span>") . "<br>";
    if ($exists) {
        echo "<strong>Readable:</strong> " . ($readable ? "<span class='success'>YES</span>" : "<span class='error'>NO</span>") . "<br>";
        echo "<strong>Size:</strong> " . number_format($size) . " bytes<br>";
        if ($readable && !$foundPath) {
            $foundPath = $path;
        }
    }
    echo "</div><br>";
}

if ($foundPath) {
    echo "<h2>✅ Found Config File</h2>";
    echo "<div class='success'>Located at: $foundPath</div>";
    
    // Try to load it
    try {
        $config = include $foundPath;
        if (is_array($config)) {
            echo "<div class='success'>✅ Config loaded successfully</div>";
            echo "<div class='info'>Contains " . count($config) . " configuration keys</div>";
            
            // Check for essential keys
            $essentialKeys = ['STRIPE_SECRET_KEY', 'STRIPE_PUBLISHABLE_KEY', 'STRIPE_WEBHOOK_SECRET'];
            foreach ($essentialKeys as $key) {
                if (isset($config[$key]) && !empty($config[$key])) {
                    echo "<div class='success'>✅ $key: configured</div>";
                } else {
                    echo "<div class='error'>❌ $key: missing or empty</div>";
                }
            }
        } else {
            echo "<div class='error'>❌ Config file doesn't return an array</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error loading config: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<h2>❌ Config File Not Found</h2>";
    echo "<div class='error'>secure-config.php not found in any expected location</div>";
    
    echo "<h3>💡 Suggestions:</h3>";
    echo "<ul>";
    echo "<li>Check if secure-config.php is uploaded to the server</li>";
    echo "<li>Verify it's placed outside the web root directory</li>";
    echo "<li>Check file permissions (should be readable by web server)</li>";
    echo "<li>Most likely location: " . dirname($_SERVER['DOCUMENT_ROOT']) . "/secure-config.php</li>";
    echo "</ul>";
}

echo "<h2>🔧 Test Secure Loader</h2>";
require_once __DIR__ . '/config/secure_loader.php';

if (function_exists('getSecureConfig')) {
    echo "<div class='success'>✅ getSecureConfig function available</div>";
    
    // Test getting a config value
    $testKey = getSecureConfig('STRIPE_SECRET_KEY');
    if (!empty($testKey)) {
        echo "<div class='success'>✅ Can retrieve STRIPE_SECRET_KEY</div>";
        echo "<div class='info'>Key length: " . strlen($testKey) . " characters</div>";
        if (strpos($testKey, 'sk_test_') === 0) {
            echo "<div class='success'>✅ Using TEST key (safe)</div>";
        } elseif (strpos($testKey, 'sk_live_') === 0) {
            echo "<div class='error'>⚠️ Using LIVE key (be careful!)</div>";
        }
    } else {
        echo "<div class='error'>❌ STRIPE_SECRET_KEY is empty or not found</div>";
    }
} else {
    echo "<div class='error'>❌ getSecureConfig function not available</div>";
}
?>
