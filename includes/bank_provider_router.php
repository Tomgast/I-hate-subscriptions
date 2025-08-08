<?php
/**
 * BANK PROVIDER ROUTER
 * Routes users to appropriate bank integration provider based on their region/choice
 * Supports both Stripe Financial Connections (US) and GoCardless (EU)
 */

class BankProviderRouter {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get available providers based on user preference or region
     */
    public function getAvailableProviders() {
        return [
            'stripe' => [
                'name' => 'Stripe Financial Connections',
                'description' => 'Connect US bank accounts',
                'countries' => ['US'],
                'flag' => '🇺🇸',
                'color' => '#635bff'
            ],
            'gocardless' => [
                'name' => 'GoCardless Bank Account Data',
                'description' => 'Connect EU bank accounts',
                'countries' => ['NL', 'DE', 'FR', 'ES', 'IT', 'GB', 'BE', 'AT', 'PT', 'IE', 'FI', 'DK', 'SE', 'NO'],
                'flag' => '🇪🇺',
                'color' => '#00d4aa'
            ]
        ];
    }
    
    /**
     * Get provider service instance
     */
    public function getProviderService($provider) {
        switch ($provider) {
            case 'stripe':
                require_once __DIR__ . '/stripe_financial_service.php';
                return new StripeFinancialService($this->pdo);
                
            case 'gocardless':
                require_once __DIR__ . '/gocardless_financial_service.php';
                return new GoCardlessFinancialService($this->pdo);
                
            default:
                throw new Exception('Unknown provider: ' . $provider);
        }
    }
    
    /**
     * Create bank connection session with selected provider
     */
    public function createBankConnectionSession($userId, $provider, $options = []) {
        $service = $this->getProviderService($provider);
        
        if ($provider === 'gocardless') {
            $country = $options['country'] ?? 'NL';
            $institutionId = $options['institution_id'] ?? null;
            return $service->createBankConnectionSession($userId, $country, $institutionId);
        } else {
            return $service->createBankConnectionSession($userId);
        }
    }
    
    /**
     * Get connection status across all providers
     */
    public function getUnifiedConnectionStatus($userId) {
        $status = [
            'has_connections' => false,
            'total_connections' => 0,
            'total_scans' => 0,
            'providers' => []
        ];
        
        $providers = ['stripe', 'gocardless'];
        
        foreach ($providers as $provider) {
            try {
                $service = $this->getProviderService($provider);
                $providerStatus = $service->getConnectionStatus($userId);
                
                $status['providers'][$provider] = $providerStatus;
                $status['total_connections'] += $providerStatus['connection_count'] ?? 0;
                $status['total_scans'] += $providerStatus['scan_count'] ?? 0;
                
                if ($providerStatus['has_connections']) {
                    $status['has_connections'] = true;
                }
            } catch (Exception $e) {
                $status['providers'][$provider] = [
                    'has_connections' => false,
                    'connection_count' => 0,
                    'scan_count' => 0,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $status;
    }
    
    /**
     * Get unified scan results from all providers
     */
    public function getUnifiedScanResults($userId) {
        $allResults = [];
        $providers = ['stripe', 'gocardless'];
        
        foreach ($providers as $provider) {
            try {
                $service = $this->getProviderService($provider);
                $results = $service->getLatestScanResults($userId);
                
                // Add provider info to each result
                foreach ($results as &$result) {
                    $result['provider'] = $provider;
                    $result['provider_name'] = $provider === 'stripe' ? 'Stripe (US)' : 'GoCardless (EU)';
                }
                
                $allResults = array_merge($allResults, $results);
            } catch (Exception $e) {
                error_log("Error getting scan results from $provider: " . $e->getMessage());
            }
        }
        
        // Sort by scan date descending, then by amount descending
        usort($allResults, function($a, $b) {
            $dateCompare = strtotime($b['scan_date'] ?? 0) - strtotime($a['scan_date'] ?? 0);
            if ($dateCompare === 0) {
                return $b['amount'] - $a['amount'];
            }
            return $dateCompare;
        });
        
        return $allResults;
    }
    
    /**
     * Get available scans from all providers
     */
    public function getUnifiedAvailableScans($userId) {
        $allScans = [];
        $providers = ['stripe', 'gocardless'];
        
        foreach ($providers as $provider) {
            try {
                $service = $this->getProviderService($provider);
                $scans = $service->getAvailableScans($userId);
                
                // Add provider info to each scan
                foreach ($scans as &$scan) {
                    $scan['provider'] = $provider;
                    $scan['provider_name'] = $provider === 'stripe' ? 'Stripe (US)' : 'GoCardless (EU)';
                }
                
                $allScans = array_merge($allScans, $scans);
            } catch (Exception $e) {
                error_log("Error getting available scans from $provider: " . $e->getMessage());
            }
        }
        
        // Sort by date descending
        usort($allScans, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return $allScans;
    }
    
    /**
     * Get institutions for GoCardless by country
     */
    public function getInstitutionsByCountry($country) {
        try {
            $service = $this->getProviderService('gocardless');
            return $service->getInstitutions($country);
        } catch (Exception $e) {
            error_log("Error getting institutions for $country: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Detect user's likely region based on their profile or IP (optional)
     */
    public function suggestProvider($userId) {
        // Check if user has existing connections
        $status = $this->getUnifiedConnectionStatus($userId);
        
        if ($status['providers']['stripe']['has_connections']) {
            return 'stripe';
        }
        if ($status['providers']['gocardless']['has_connections']) {
            return 'gocardless';
        }
        
        // Default suggestion based on common usage
        // You could enhance this with IP geolocation or user preference
        return 'gocardless'; // EU is more common globally
    }
}
?>
