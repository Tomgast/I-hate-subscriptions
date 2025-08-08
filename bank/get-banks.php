<?php
/**
 * Get Banks for Country - GoCardless Integration
 * Fetches available banks for a specific country via GoCardless API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response

try {
    // Get country parameter
    $country = $_GET['country'] ?? '';
    
    if (empty($country)) {
        throw new Exception('Country parameter is required');
    }
    
    // Validate country code (2-letter ISO code)
    if (strlen($country) !== 2) {
        throw new Exception('Invalid country code format');
    }
    
    // Include database config and GoCardless service
    require_once '../config/db_config.php';
    require_once '../includes/gocardless_financial_service.php';
    
    // Get database connection
    $pdo = getDBConnection();
    
    // Initialize GoCardless service
    $goCardlessService = new GoCardlessFinancialService($pdo);
    
    // Get institutions for the country
    $institutions = $goCardlessService->getInstitutions($country);
    
    // Format response for frontend
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
    
    // Sort banks alphabetically by name
    usort($banks, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    // Return JSON response
    echo json_encode($banks, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // Log detailed error for debugging
    error_log("Get Banks Error for country '$country': " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Return detailed error response for debugging
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'country' => $country,
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ],
        'banks' => []
    ], JSON_PRETTY_PRINT);
}
?>
