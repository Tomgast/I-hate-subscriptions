<?php
/**
 * Debug Get Banks for Country - GoCardless Integration
 * Debug version to identify issues with bank fetching
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Debug: Get Banks for Country</h1>";

try {
    // Get country parameter
    $country = $_GET['country'] ?? 'NL';
    
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>🎯 Testing Country:</strong> $country<br>";
    echo "<strong>📅 Time:</strong> " . date('Y-m-d H:i:s') . "<br>";
    echo "</div>";
    
    // Include database config and GoCardless service
    require_once '../config/db_config.php';
    require_once '../includes/gocardless_financial_service.php';
    echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 5px 0;'>✅ GoCardless service included</div>";
    
    // Get database connection
    $pdo = getDBConnection();
    echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 5px 0;'>✅ Database connection established</div>";
    
    // Initialize GoCardless service
    $goCardlessService = new GoCardlessFinancialService($pdo);
    echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 5px 0;'>✅ GoCardless service initialized</div>";
    
    // Check if access token is available
    $reflection = new ReflectionClass($goCardlessService);
    $tokenProperty = $reflection->getProperty('accessToken');
    $tokenProperty->setAccessible(true);
    $token = $tokenProperty->getValue($goCardlessService);
    
    if ($token) {
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 5px 0;'>✅ Access token available: " . substr($token, 0, 20) . "...</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; margin: 5px 0;'>❌ No access token available</div>";
    }
    
    // Test API base URL
    $baseUrlProperty = $reflection->getProperty('apiBaseUrl');
    $baseUrlProperty->setAccessible(true);
    $baseUrl = $baseUrlProperty->getValue($goCardlessService);
    echo "<div style='background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 5px 0;'>🌐 API Base URL: $baseUrl</div>";
    
    // Get institutions for the country
    echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px; margin: 5px 0;'>🔄 Fetching institutions for country: $country</div>";
    
    $institutions = $goCardlessService->getInstitutions($country);
    
    echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 5px 0;'>✅ Successfully fetched " . count($institutions) . " institutions</div>";
    
    // Display institutions
    echo "<h2>📋 Available Banks for $country:</h2>";
    
    if (empty($institutions)) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>❌ No banks found for country: $country</strong><br>";
        echo "This could mean:<br>";
        echo "• Country code is not supported by GoCardless<br>";
        echo "• API authentication failed<br>";
        echo "• Network connectivity issues<br>";
        echo "</div>";
    } else {
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        foreach ($institutions as $index => $institution) {
            echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 5px 0; border-radius: 5px; background: white;'>";
            echo "<strong>" . ($index + 1) . ". " . htmlspecialchars($institution['name']) . "</strong><br>";
            echo "<small>ID: " . htmlspecialchars($institution['id']) . "</small><br>";
            if (!empty($institution['bic'])) {
                echo "<small>BIC: " . htmlspecialchars($institution['bic']) . "</small><br>";
            }
            if (!empty($institution['logo'])) {
                echo "<img src='" . htmlspecialchars($institution['logo']) . "' alt='Logo' style='width: 30px; height: 30px; object-fit: contain; margin: 5px 0;' onerror='this.style.display=\"none\"'><br>";
            }
            echo "<small>Countries: " . implode(', ', $institution['countries'] ?? []) . "</small><br>";
            echo "<small>Transaction Days: " . ($institution['transaction_total_days'] ?? 'N/A') . "</small>";
            echo "</div>";
        }
        echo "</div>";
    }
    
    // Test JSON output
    echo "<h2>📤 JSON Output:</h2>";
    echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
    
    $banks = [];
    foreach ($institutions as $institution) {
        $banks[] = [
            'id' => $institution['id'],
            'name' => $institution['name'],
            'bic' => $institution['bic'] ?? '',
            'logo' => $institution['logo'] ?? '',
            'countries' => $institution['countries'] ?? [$country],
            'transaction_total_days' => $institution['transaction_total_days'] ?? 90
        ];
    }
    
    echo htmlspecialchars(json_encode($banks, JSON_PRETTY_PRINT));
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>❌ Error occurred:</strong><br>";
    echo "<strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "<strong>Stack Trace:</strong><br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>🧪 Test Different Countries:</h3>";
$testCountries = ['NL', 'DE', 'FR', 'GB', 'ES', 'IT', 'BE', 'AT', 'PT', 'IE', 'FI', 'DK', 'SE', 'NO'];
foreach ($testCountries as $testCountry) {
    echo "<a href='?country=$testCountry' style='display: inline-block; margin: 5px; padding: 8px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>$testCountry</a> ";
}
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
</style>
