<?php
// Debug Secure Config Loading
echo "<h2>🔍 Secure Config Debug</h2>";

$configPath = '/var/www/vhosts/123cashcontrol.com/secure-config.php';

echo "<h3>1. File Information</h3>";
echo "<p><strong>Config Path:</strong> " . htmlspecialchars($configPath) . "</p>";
echo "<p><strong>File Exists:</strong> " . (file_exists($configPath) ? '✅ Yes' : '❌ No') . "</p>";
echo "<p><strong>File Readable:</strong> " . (is_readable($configPath) ? '✅ Yes' : '❌ No') . "</p>";

if (file_exists($configPath)) {
    echo "<p><strong>File Size:</strong> " . filesize($configPath) . " bytes</p>";
    echo "<p><strong>File Permissions:</strong> " . substr(sprintf('%o', fileperms($configPath)), -4) . "</p>";
}

echo "<h3>2. Before Loading Config</h3>";
echo "<p><strong>getSecureConfig function exists:</strong> " . (function_exists('getSecureConfig') ? '✅ Yes' : '❌ No') . "</p>";

echo "<h3>3. Loading Config File</h3>";
try {
    if (file_exists($configPath)) {
        echo "<p>Attempting to load: " . htmlspecialchars($configPath) . "</p>";
        
        // Try to get first few lines to see the structure
        $firstLines = file_get_contents($configPath, false, null, 0, 500);
        echo "<h4>First 500 characters of config file:</h4>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
        echo htmlspecialchars($firstLines);
        echo "</pre>";
        
        // Now try to include it
        require_once $configPath;
        echo "<p>✅ Config file loaded successfully</p>";
    } else {
        echo "<p>❌ Config file not found</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error loading config: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
} catch (ParseError $e) {
    echo "<p>❌ Parse error in config file: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
}

echo "<h3>4. After Loading Config</h3>";
echo "<p><strong>getSecureConfig function exists:</strong> " . (function_exists('getSecureConfig') ? '✅ Yes' : '❌ No') . "</p>";

if (function_exists('getSecureConfig')) {
    echo "<h3>5. Testing getSecureConfig Function</h3>";
    
    $testKeys = ['DB_PASSWORD', 'GOOGLE_CLIENT_SECRET', 'SMTP_PASSWORD'];
    
    foreach ($testKeys as $key) {
        try {
            $value = getSecureConfig($key);
            echo "<p><strong>$key:</strong> " . (empty($value) ? '❌ Empty/Not Set' : '✅ Set (length: ' . strlen($value) . ')') . "</p>";
        } catch (Exception $e) {
            echo "<p><strong>$key:</strong> ❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
} else {
    echo "<p>❌ Cannot test getSecureConfig - function not available</p>";
}

echo "<h3>6. Defined Functions</h3>";
$definedFunctions = get_defined_functions()['user'];
$configFunctions = array_filter($definedFunctions, function($func) {
    return strpos(strtolower($func), 'config') !== false || strpos(strtolower($func), 'secure') !== false;
});

if (!empty($configFunctions)) {
    echo "<p><strong>Config-related functions found:</strong></p>";
    echo "<ul>";
    foreach ($configFunctions as $func) {
        echo "<li>" . htmlspecialchars($func) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>❌ No config-related functions found</p>";
}
?>
