<?php
echo "<h1>Recent Error Logs</h1>";

// Common error log locations
$possibleLogPaths = [
    '/var/log/apache2/error.log',
    '/var/log/httpd/error_log',
    '/var/log/php_errors.log',
    '/var/log/nginx/error.log',
    ini_get('error_log'),
    '/tmp/php_errors.log',
    '../logs/error.log',
    '../../logs/error.log'
];

echo "<h2>Checking PHP Error Log Configuration:</h2>";
echo "<p>PHP Error Log Setting: " . (ini_get('error_log') ?: 'Not set') . "</p>";
echo "<p>Log Errors Enabled: " . (ini_get('log_errors') ? 'Yes' : 'No') . "</p>";
echo "<p>Display Errors: " . (ini_get('display_errors') ? 'Yes' : 'No') . "</p>";

echo "<h2>Searching for Error Logs:</h2>";

$foundLogs = [];

foreach ($possibleLogPaths as $path) {
    if ($path && file_exists($path) && is_readable($path)) {
        $foundLogs[] = $path;
        echo "<p style='color: green;'>✅ Found: $path</p>";
    } else {
        echo "<p style='color: gray;'>❌ Not found: $path</p>";
    }
}

if (empty($foundLogs)) {
    echo "<h2>No Error Logs Found</h2>";
    echo "<p>Try enabling error logging by adding this to a .htaccess file:</p>";
    echo "<pre>";
    echo "php_flag log_errors on\n";
    echo "php_value error_log /path/to/your/error.log\n";
    echo "</pre>";
} else {
    echo "<h2>Recent Error Log Entries (Last 50 lines):</h2>";
    
    foreach ($foundLogs as $logPath) {
        echo "<h3>Log: $logPath</h3>";
        
        try {
            // Get last 50 lines of the log file
            $lines = file($logPath);
            if ($lines) {
                $recentLines = array_slice($lines, -50);
                echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 400px; overflow-y: scroll;'>";
                foreach ($recentLines as $line) {
                    // Highlight lines containing our session ID or payment-related keywords
                    if (strpos($line, 'payment') !== false || 
                        strpos($line, 'stripe') !== false || 
                        strpos($line, 'session') !== false ||
                        strpos($line, 'handleSuccessfulPayment') !== false) {
                        echo "<strong style='color: red;'>" . htmlspecialchars($line) . "</strong>";
                    } else {
                        echo htmlspecialchars($line);
                    }
                }
                echo "</pre>";
            } else {
                echo "<p>Log file is empty or unreadable</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>Error reading log: " . $e->getMessage() . "</p>";
        }
    }
}

// Also check if we can trigger a test error to see where it goes
echo "<h2>Test Error Logging:</h2>";
error_log("TEST: CashControl error log test at " . date('Y-m-d H:i:s'));
echo "<p>Test error logged. Check the logs above for this message.</p>";

echo "<h2>Alternative: Enable Error Display</h2>";
echo "<p>If no error logs are found, you can temporarily enable error display by adding this to the top of success.php:</p>";
echo "<pre>";
echo "error_reporting(E_ALL);\n";
echo "ini_set('display_errors', 1);\n";
echo "</pre>";
?>
