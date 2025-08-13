<?php
/**
 * Test PHP MySQL capabilities
 */

echo "=== PHP MySQL Driver Test ===\n";

// Check if PDO MySQL driver is available
if (extension_loaded('pdo_mysql')) {
    echo "✓ PDO MySQL driver is available\n";
} else {
    echo "✗ PDO MySQL driver is NOT available\n";
    echo "Available PDO drivers: " . implode(', ', PDO::getAvailableDrivers()) . "\n";
}

// Check if MySQLi is available
if (extension_loaded('mysqli')) {
    echo "✓ MySQLi extension is available\n";
} else {
    echo "✗ MySQLi extension is NOT available\n";
}

// Check if MySQL Native Driver is available
if (extension_loaded('mysqlnd')) {
    echo "✓ MySQL Native Driver (mysqlnd) is available\n";
} else {
    echo "✗ MySQL Native Driver (mysqlnd) is NOT available\n";
}

echo "\n=== PHP Configuration ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "PHP SAPI: " . PHP_SAPI . "\n";

// Show loaded extensions related to MySQL
echo "\n=== MySQL-related Extensions ===\n";
$extensions = get_loaded_extensions();
foreach ($extensions as $ext) {
    if (stripos($ext, 'mysql') !== false || stripos($ext, 'pdo') !== false) {
        echo "- $ext\n";
    }
}

echo "\n=== Test Complete ===\n";
?>
