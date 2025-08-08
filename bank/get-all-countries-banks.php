<?php
/**
 * Get All Countries and Banks - GoCardless Integration
 * Fetches all available countries and their banks dynamically from GoCardless API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response

try {
    // Include database config and GoCardless service
    require_once '../config/db_config.php';
    require_once '../includes/gocardless_financial_service.php';
    
    // Get database connection
    $pdo = getDBConnection();
    
    // Initialize GoCardless service
    $goCardlessService = new GoCardlessFinancialService($pdo);
    
    // All known GoCardless supported countries (based on research)
    $supportedCountries = [
        // Western Europe
        'AT' => ['name' => 'Austria', 'flag' => '🇦🇹', 'region' => 'Western Europe'],
        'BE' => ['name' => 'Belgium', 'flag' => '🇧🇪', 'region' => 'Western Europe'],
        'CH' => ['name' => 'Switzerland', 'flag' => '🇨🇭', 'region' => 'Western Europe'],
        'DE' => ['name' => 'Germany', 'flag' => '🇩🇪', 'region' => 'Western Europe'],
        'FR' => ['name' => 'France', 'flag' => '🇫🇷', 'region' => 'Western Europe'],
        'LI' => ['name' => 'Liechtenstein', 'flag' => '🇱🇮', 'region' => 'Western Europe'],
        'LU' => ['name' => 'Luxembourg', 'flag' => '🇱🇺', 'region' => 'Western Europe'],
        'NL' => ['name' => 'Netherlands', 'flag' => '🇳🇱', 'region' => 'Western Europe'],
        
        // Northern Europe
        'DK' => ['name' => 'Denmark', 'flag' => '🇩🇰', 'region' => 'Northern Europe'],
        'FI' => ['name' => 'Finland', 'flag' => '🇫🇮', 'region' => 'Northern Europe'],
        'GB' => ['name' => 'United Kingdom', 'flag' => '🇬🇧', 'region' => 'Northern Europe'],
        'IE' => ['name' => 'Ireland', 'flag' => '🇮🇪', 'region' => 'Northern Europe'],
        'IS' => ['name' => 'Iceland', 'flag' => '🇮🇸', 'region' => 'Northern Europe'],
        'NO' => ['name' => 'Norway', 'flag' => '🇳🇴', 'region' => 'Northern Europe'],
        'SE' => ['name' => 'Sweden', 'flag' => '🇸🇪', 'region' => 'Northern Europe'],
        
        // Southern Europe
        'ES' => ['name' => 'Spain', 'flag' => '🇪🇸', 'region' => 'Southern Europe'],
        'IT' => ['name' => 'Italy', 'flag' => '🇮🇹', 'region' => 'Southern Europe'],
        'PT' => ['name' => 'Portugal', 'flag' => '🇵🇹', 'region' => 'Southern Europe'],
        'MT' => ['name' => 'Malta', 'flag' => '🇲🇹', 'region' => 'Southern Europe'],
        'CY' => ['name' => 'Cyprus', 'flag' => '🇨🇾', 'region' => 'Southern Europe'],
        
        // Eastern Europe
        'CZ' => ['name' => 'Czech Republic', 'flag' => '🇨🇿', 'region' => 'Eastern Europe'],
        'EE' => ['name' => 'Estonia', 'flag' => '🇪🇪', 'region' => 'Eastern Europe'],
        'LT' => ['name' => 'Lithuania', 'flag' => '🇱🇹', 'region' => 'Eastern Europe'],
        'LV' => ['name' => 'Latvia', 'flag' => '🇱🇻', 'region' => 'Eastern Europe'],
        'PL' => ['name' => 'Poland', 'flag' => '🇵🇱', 'region' => 'Eastern Europe'],
        'SK' => ['name' => 'Slovakia', 'flag' => '🇸🇰', 'region' => 'Eastern Europe'],
        'SI' => ['name' => 'Slovenia', 'flag' => '🇸🇮', 'region' => 'Eastern Europe'],
        'HU' => ['name' => 'Hungary', 'flag' => '🇭🇺', 'region' => 'Eastern Europe'],
        'RO' => ['name' => 'Romania', 'flag' => '🇷🇴', 'region' => 'Eastern Europe'],
        'BG' => ['name' => 'Bulgaria', 'flag' => '🇧🇬', 'region' => 'Eastern Europe'],
        'HR' => ['name' => 'Croatia', 'flag' => '🇭🇷', 'region' => 'Eastern Europe']
    ];
    
    $action = $_GET['action'] ?? 'countries';
    
    if ($action === 'countries') {
        // Return all supported countries grouped by region
        $countriesByRegion = [];
        foreach ($supportedCountries as $code => $info) {
            $region = $info['region'];
            if (!isset($countriesByRegion[$region])) {
                $countriesByRegion[$region] = [];
            }
            $countriesByRegion[$region][] = [
                'code' => $code,
                'name' => $info['name'],
                'flag' => $info['flag']
            ];
        }
        
        // Sort countries within each region
        foreach ($countriesByRegion as $region => $countries) {
            usort($countriesByRegion[$region], function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
        }
        
        echo json_encode([
            'success' => true,
            'total_countries' => count($supportedCountries),
            'regions' => $countriesByRegion,
            'all_countries' => array_map(function($code, $info) {
                return [
                    'code' => $code,
                    'name' => $info['name'],
                    'flag' => $info['flag'],
                    'region' => $info['region']
                ];
            }, array_keys($supportedCountries), $supportedCountries)
        ], JSON_PRETTY_PRINT);
        
    } elseif ($action === 'banks') {
        $country = $_GET['country'] ?? '';
        
        if (empty($country)) {
            throw new Exception('Country parameter is required for banks action');
        }
        
        if (!isset($supportedCountries[$country])) {
            throw new Exception("Country '$country' is not supported by GoCardless");
        }
        
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
                'transaction_total_days' => $institution['transaction_total_days'] ?? 90,
                'max_access_valid_for_days' => $institution['max_access_valid_for_days'] ?? 90
            ];
        }
        
        // Sort banks alphabetically by name
        usort($banks, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        echo json_encode([
            'success' => true,
            'country' => [
                'code' => $country,
                'name' => $supportedCountries[$country]['name'],
                'flag' => $supportedCountries[$country]['flag']
            ],
            'total_banks' => count($banks),
            'banks' => $banks
        ], JSON_PRETTY_PRINT);
        
    } else {
        throw new Exception("Invalid action. Use 'countries' or 'banks'");
    }
    
} catch (Exception $e) {
    // Log detailed error for debugging
    error_log("Get All Countries/Banks Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Return detailed error response for debugging
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => true,
        'message' => $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'action' => $_GET['action'] ?? 'none',
            'country' => $_GET['country'] ?? 'none'
        ]
    ], JSON_PRETTY_PRINT);
}
?>
