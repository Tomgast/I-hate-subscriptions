<?php
/**
 * UNIFIED BANK SCAN PAGE
 * Allows users to choose between Stripe (US) and GoCardless (EU) providers
 */

session_start();
require_once '../config/db_config.php';
require_once '../includes/plan_manager.php';
require_once '../includes/bank_provider_router.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/signin.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'User';

// Get user's plan information
$planManager = getPlanManager();
$userPlan = $planManager->getUserPlan($userId);

// Enhanced plan verification
$hasValidPlan = false;
$planType = null;

if ($userPlan && $userPlan['is_active'] && in_array($userPlan['plan_type'], ['monthly', 'yearly', 'onetime'])) {
    $hasValidPlan = true;
    $planType = $userPlan['plan_type'];
}

if (!$hasValidPlan) {
    header('Location: ../dashboard.php?error=no_plan');
    exit;
}

// Check scan limitations
$canScan = true;
$scansRemaining = 'unlimited';

if ($planType === 'onetime') {
    $canScan = $planManager->hasScansRemaining($userId);
    $scansRemaining = $userPlan['scans_remaining'] ?? 0;
}

// Initialize provider router
$pdo = getDBConnection();
$providerRouter = new BankProviderRouter($pdo);
$availableProviders = $providerRouter->getAvailableProviders();
$suggestedProvider = $providerRouter->suggestProvider($userId);

// Handle form submissions
$error = null;
$success = null;

if ($_POST && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'start_scan':
                if (!$canScan) {
                    throw new Exception("You have reached your scan limit for this plan.");
                }
                
                $provider = $_POST['provider'] ?? null;
                $country = $_POST['country'] ?? null;
                $institutionId = $_POST['institution_id'] ?? null;
                
                // Debug logging
                error_log("Bank connection debug - Provider: $provider, Country: $country, Institution ID: $institutionId");
                error_log("POST data: " . print_r($_POST, true));
                
                if (!$provider || !in_array($provider, ['stripe', 'gocardless'])) {
                    throw new Exception("Please select a valid bank provider.");
                }
                
                if ($provider === 'gocardless' && !$country) {
                    throw new Exception("Please select your country for EU bank connections.");
                }
                
                if ($provider === 'gocardless' && !$institutionId) {
                    throw new Exception("Please select a bank before connecting. Institution ID is missing.");
                }
                
                $options = [];
                if ($provider === 'gocardless') {
                    $options['country'] = $country;
                    if ($institutionId) {
                        $options['institution_id'] = $institutionId;
                    }
                }
                
                error_log("About to call createBankConnectionSession with provider: $provider, options: " . print_r($options, true));
                
                try {
                    $result = $providerRouter->createBankConnectionSession($userId, $provider, $options);
                    error_log("createBankConnectionSession result: " . print_r($result, true));
                    
                    // DETAILED DEBUG - Check what we got back
                    error_log("DEBUG: Result success: " . ($result['success'] ? 'true' : 'false'));
                    error_log("DEBUG: Result auth_url: " . ($result['auth_url'] ?? 'NULL'));
                    error_log("DEBUG: Result error: " . ($result['error'] ?? 'NULL'));
                    
                } catch (Exception $e) {
                    error_log("Error in createBankConnectionSession: " . $e->getMessage());
                    error_log("Stack trace: " . $e->getTraceAsString());
                    throw $e;
                }
                
                if ($result['success']) {
                    error_log("DEBUG: About to redirect to: " . $result['auth_url']);
                    
                    // Check if auth_url is actually valid
                    if (empty($result['auth_url'])) {
                        error_log("ERROR: auth_url is empty!");
                        throw new Exception("Bank connection session created but no authorization URL provided.");
                    }
                    
                    header('Location: ' . $result['auth_url']);
                    exit;
                } else {
                    error_log("DEBUG: createBankConnectionSession failed with error: " . ($result['error'] ?? 'Unknown error'));
                    throw new Exception($result['error'] ?? "Failed to initiate bank connection. Please try again.");
                }
                break;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get current connection status
$connectionStatus = $providerRouter->getUnifiedConnectionStatus($userId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Account Scan - CashControl</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .main-container {
            padding: 40px 0;
        }
        .scan-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .provider-card {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        .provider-card:hover {
            border-color: #007bff;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,123,255,0.1);
        }
        .provider-card.selected {
            border-color: #007bff;
            background: #f8f9ff;
        }
        .provider-flag {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .country-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        .country-btn {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s ease;
        }
        .country-btn:hover {
            border-color: #007bff;
            background: #f8f9ff;
        }
        .country-btn.selected {
            border-color: #007bff;
            background: #007bff;
            color: white;
        }
        .bank-btn {
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s ease;
            height: 100%;
        }
        .bank-btn:hover {
            border-color: #007bff;
            background: #f8f9ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,123,255,0.1);
        }
        .bank-btn.selected {
            border-color: #007bff;
            background: #007bff;
            color: white;
        }
        .bank-logo {
            width: 40px;
            height: 40px;
            object-fit: contain;
            margin-bottom: 8px;
        }
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .status-connected {
            background: #d4edda;
            color: #155724;
        }
        .status-not-connected {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container main-container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="scan-card">
                    <div class="card-header bg-primary text-white text-center py-4">
                        <h2 class="mb-0">
                            <i class="fas fa-university me-2"></i>
                            Connect Your Bank Account
                        </h2>
                        <p class="mb-0 mt-2">Choose your region to connect your bank and scan for subscriptions</p>
                    </div>
                    
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Current Status -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user-circle fa-2x text-primary me-3"></i>
                                    <div>
                                        <h5 class="mb-0"><?php echo htmlspecialchars($userName); ?></h5>
                                        <small class="text-muted"><?php echo ucfirst($planType); ?> Plan</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <div class="mb-2">
                                    <span class="status-badge <?php echo $connectionStatus['has_connections'] ? 'status-connected' : 'status-not-connected'; ?>">
                                        <?php if ($connectionStatus['has_connections']): ?>
                                            <i class="fas fa-check-circle me-1"></i>
                                            <?php echo $connectionStatus['total_connections']; ?> Bank(s) Connected
                                        <?php else: ?>
                                            <i class="fas fa-times-circle me-1"></i>
                                            No Banks Connected
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <?php if ($planType === 'onetime'): ?>
                                    <small class="text-muted">
                                        Scans remaining: <?php echo $scansRemaining; ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <form method="POST" id="scanForm" onsubmit="debugFormSubmission(event)">
                            <input type="hidden" name="action" value="start_scan">
                            <input type="hidden" name="institution_id" id="institutionId" value="">
                            <input type="hidden" name="provider" id="selectedProvider">
                            <input type="hidden" name="country" id="selectedCountry">
                            
                            <h4 class="mb-3">Select Your Bank Region</h4>
                            
                            <!-- Stripe Provider -->
                            <div class="provider-card" data-provider="stripe" <?php echo $suggestedProvider === 'stripe' ? 'data-suggested="true"' : ''; ?>>
                                <div class="row align-items-center">
                                    <div class="col-md-2 text-center">
                                        <div class="provider-flag">🇺🇸</div>
                                    </div>
                                    <div class="col-md-7">
                                        <h5 class="mb-1">
                                            United States Banks
                                            <?php if ($suggestedProvider === 'stripe'): ?>
                                                <span class="badge bg-success ms-2">Suggested</span>
                                            <?php endif; ?>
                                        </h5>
                                        <p class="text-muted mb-1">Stripe Financial Connections</p>
                                        <small class="text-muted">Connect US bank accounts securely</small>
                                        
                                        <?php if ($connectionStatus['providers']['stripe']['has_connections']): ?>
                                            <div class="mt-2">
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>
                                                    <?php echo $connectionStatus['providers']['stripe']['connection_count']; ?> Connected
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="provider_radio" value="stripe" id="stripe_radio">
                                            <label class="form-check-label" for="stripe_radio">
                                                Select
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- GoCardless Provider -->
                            <div class="provider-card" data-provider="gocardless" <?php echo $suggestedProvider === 'gocardless' ? 'data-suggested="true"' : ''; ?>>
                                <div class="row align-items-center">
                                    <div class="col-md-2 text-center">
                                        <div class="provider-flag">🇪🇺</div>
                                    </div>
                                    <div class="col-md-7">
                                        <h5 class="mb-1">
                                            European Banks
                                            <?php if ($suggestedProvider === 'gocardless'): ?>
                                                <span class="badge bg-success ms-2">Suggested</span>
                                            <?php endif; ?>
                                        </h5>
                                        <p class="text-muted mb-1">GoCardless Bank Account Data</p>
                                        <small class="text-muted">Connect EU bank accounts securely</small>
                                        
                                        <?php if ($connectionStatus['providers']['gocardless']['has_connections']): ?>
                                            <div class="mt-2">
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>
                                                    <?php echo $connectionStatus['providers']['gocardless']['connection_count']; ?> Connected
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="provider_radio" value="gocardless" id="gocardless_radio">
                                            <label class="form-check-label" for="gocardless_radio">
                                                Select
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- EU Banks Section -->
                                <div id="euBanksSection" style="display: none;">
                                    <h4 class="text-center mb-4">
                                        <i class="fas fa-university text-primary me-2"></i>
                                        European Banks
                                    </h4>
                                    <p class="text-center text-muted mb-4">Select your country to see available banks (31+ countries supported)</p>
                                    
                                    <!-- Country Loading -->
                                    <div id="countryLoading" class="text-center py-4" style="display: none;">
                                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                        <p class="mt-2 text-muted">Loading countries...</p>
                                    </div>
                                    
                                    <!-- Countries by Region -->
                                    <div id="countriesByRegion" style="display: none;">
                                        <!-- Dynamically populated -->
                                    </div>
                                    
                                    <!-- Bank Selection (shown after country selection) -->
                                    <div id="bankSelection" style="display: none; margin-top: 30px;">
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <h5 class="mb-0">Choose Your Bank</h5>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="goBackToCountries()">
                                                <i class="fas fa-arrow-left me-1"></i> Back to Countries
                                            </button>
                                        </div>
                                        <div id="selectedCountryInfo" class="alert alert-info mb-3"></div>
                                        <div id="bankGrid" class="row g-3"></div>
                                    </div>
                                    
                                    <div class="text-center mt-4">
                                        <button type="submit" class="btn btn-primary btn-lg px-5" id="connectEuBtn" disabled>
                                            <i class="fas fa-link me-2"></i>
                                            Connect European Bank
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- US Banks Section (shown when Stripe is selected) -->
                            <div id="usBanksSection" style="display: none; margin-top: 30px;">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <div class="provider-flag" style="font-size: 3rem;">🇺🇸</div>
                                        </div>
                                        <h5 class="card-title">Connect US Bank Account</h5>
                                        <p class="card-text text-muted">
                                            You'll be redirected to Stripe Financial Connections to securely link your US bank account.
                                            This service supports thousands of US banks and credit unions.
                                        </p>
                                        <div class="text-center mt-4">
                                            <button type="submit" class="btn btn-primary btn-lg px-5" id="connectUsBtn">
                                                <i class="fas fa-link me-2"></i>
                                                Connect American Bank
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 mt-4">
                                <a href="../dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Back to Dashboard
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const providerCards = document.querySelectorAll('.provider-card');
            const providerRadios = document.querySelectorAll('input[name="provider_radio"]');
            const countrySelection = document.getElementById('countrySelection');
            const countryButtons = document.querySelectorAll('.country-btn');
            const connectUsBtn = document.getElementById('connectUsBtn');
            const connectEuBtn = document.getElementById('connectEuBtn');
            const selectedProviderInput = document.getElementById('selectedProvider');
            const selectedCountryInput = document.getElementById('selectedCountry');
            
            // Don't auto-select any provider - let user choose manually
            // The suggested provider will still show the "Suggested" badge in the UI
            
            // Provider selection
            providerCards.forEach(card => {
                card.addEventListener('click', function() {
                    const provider = this.dataset.provider;
                    const radio = document.getElementById(provider + '_radio');
                    radio.checked = true;
                    selectProvider(provider);
                });
            });
            
            providerRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.checked) {
                        selectProvider(this.value);
                    }
                });
            });
            
            function selectProvider(provider) {
                // Update UI
                providerCards.forEach(card => {
                    card.classList.remove('selected');
                });
                document.querySelector(`[data-provider="${provider}"]`).classList.add('selected');
                
                // Update form
                selectedProviderInput.value = provider;
                
                // Handle provider-specific logic
                if (provider === 'gocardless') {
                    // Show EU banks section for country/bank selection
                    document.getElementById('euBanksSection').style.display = 'block';
                    document.getElementById('usBanksSection').style.display = 'none';
                    // Load countries immediately when EU section is shown
                    setTimeout(() => loadAllCountries(), 100); // Small delay to ensure DOM is ready
                    updateConnectButton();
                } else if (provider === 'stripe') {
                    // Show US banks section and hide EU banks section
                    document.getElementById('euBanksSection').style.display = 'none';
                    document.getElementById('usBanksSection').style.display = 'block';
                    selectedCountryInput.value = '';
                    updateConnectButton();
                } else {
                    // Hide both sections
                    document.getElementById('euBanksSection').style.display = 'none';
                    document.getElementById('usBanksSection').style.display = 'none';
                    selectedCountryInput.value = '';
                }
            }
            
            // Load countries when EU banks section is shown
            let countriesLoaded = false;
            
            // Function to load all countries
            async function loadAllCountries() {
                if (countriesLoaded) return;
                
                const countryLoading = document.getElementById('countryLoading');
                const countriesByRegion = document.getElementById('countriesByRegion');
                
                // Show loading state
                countryLoading.style.display = 'block';
                countriesByRegion.style.display = 'none';
                
                try {
                    const response = await fetch('get-all-countries-banks.php?action=countries');
                    const data = await response.json();
                    
                    if (!data.success) {
                        throw new Error(data.message || 'Failed to load countries');
                    }
                    
                    // Hide loading, show countries
                    countryLoading.style.display = 'none';
                    countriesByRegion.style.display = 'block';
                    
                    // Build countries by region
                    let html = '';
                    Object.keys(data.regions).forEach(region => {
                        html += `
                            <div class="mb-4">
                                <h6 class="text-muted mb-3">${region}</h6>
                                <div class="country-grid">
                        `;
                        
                        data.regions[region].forEach(country => {
                            html += `
                                <div class="country-btn" data-country="${country.code}">
                                    <div class="fw-bold">${country.flag} ${country.name}</div>
                                    <small class="text-muted">${country.code}</small>
                                </div>
                            `;
                        });
                        
                        html += `
                                </div>
                            </div>
                        `;
                    });
                    
                    countriesByRegion.innerHTML = html;
                    
                    // Add click handlers for country selection
                    document.querySelectorAll('.country-btn').forEach(btn => {
                        btn.addEventListener('click', function() {
                            // Remove selected class from all country buttons
                            document.querySelectorAll('.country-btn').forEach(b => b.classList.remove('selected'));
                            
                            // Add selected class to clicked button
                            this.classList.add('selected');
                            
                            // Set the selected country
                            const country = this.dataset.country;
                            document.getElementById('selectedCountry').value = country;
                            
                            // Load banks for selected country
                            loadBanksForCountry(country);
                        });
                    });
                    
                    countriesLoaded = true;
                    
                } catch (error) {
                    console.error('Error loading countries:', error);
                    countryLoading.innerHTML = `
                        <div class="text-center text-danger">
                            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                            <p>Error loading countries: ${error.message}</p>
                            <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                                <i class="fas fa-refresh me-1"></i> Retry
                            </button>
                        </div>
                    `;
                }
            }
            
            // Function to load banks for a country
            async function loadBanksForCountry(country) {
                const bankGrid = document.getElementById('bankGrid');
                const bankSelection = document.getElementById('bankSelection');
                const selectedCountryInfo = document.getElementById('selectedCountryInfo');
                const countriesByRegion = document.getElementById('countriesByRegion');
                
                // Hide countries, show bank selection
                countriesByRegion.style.display = 'none';
                bankSelection.style.display = 'block';
                
                // Show loading
                bankGrid.innerHTML = '<div class="col-12 text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2">Loading banks...</p></div>';
                
                try {
                    const response = await fetch(`get-all-countries-banks.php?action=banks&country=${country}`);
                    const data = await response.json();
                    
                    if (!data.success) {
                        throw new Error(data.message || 'Failed to load banks');
                    }
                    
                    // Update country info
                    selectedCountryInfo.innerHTML = `
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>${data.country.flag} ${data.country.name}</strong> - ${data.total_banks} banks available
                    `;
                    
                    if (data.total_banks === 0) {
                        bankGrid.innerHTML = '<div class="col-12 text-center text-muted py-4">No banks available for this country</div>';
                        return;
                    }
                    
                    // Display banks
                    bankGrid.innerHTML = '';
                    data.banks.forEach(bank => {
                        const bankDiv = document.createElement('div');
                        bankDiv.className = 'col-lg-3 col-md-4 col-sm-6';
                        bankDiv.innerHTML = `
                            <div class="bank-btn" data-institution-id="${bank.id}">
                                <img src="${bank.logo}" alt="${bank.name}" class="bank-logo" onerror="this.style.display='none'">
                                <div class="fw-bold">${bank.name}</div>
                                <small class="text-muted">${bank.bic || bank.id.split('_')[0]}</small>
                            </div>
                        `;
                        bankGrid.appendChild(bankDiv);
                    });
                    
                    // Add click handlers for bank selection
                    document.querySelectorAll('.bank-btn').forEach(btn => {
                        btn.addEventListener('click', function() {
                            // Remove selected class from all bank buttons
                            document.querySelectorAll('.bank-btn').forEach(b => b.classList.remove('selected'));
                            
                            // Add selected class to clicked button
                            this.classList.add('selected');
                            
                            // Set the selected institution ID
                            const institutionId = this.dataset.institutionId;
                            document.getElementById('institutionId').value = institutionId;
                            
                            // Debug logging
                            console.log('Bank selected:', {
                                institutionId: institutionId,
                                bankName: this.querySelector('.fw-bold').textContent,
                                formValue: document.getElementById('institutionId').value
                            });
                            
                            // Enable the connect button
                            document.getElementById('connectEuBtn').disabled = false;
                        });
                    });
                    
                } catch (error) {
                    console.error('Error loading banks:', error);
                    bankGrid.innerHTML = `
                        <div class="col-12 text-center text-danger py-4">
                            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                            <p>Error loading banks: ${error.message}</p>
                            <button class="btn btn-outline-primary btn-sm" onclick="loadBanksForCountry('${country}')">
                                <i class="fas fa-refresh me-1"></i> Retry
                            </button>
                        </div>
                    `;
                }
            }
            
            // Function to go back to countries
            window.goBackToCountries = function() {
                document.getElementById('bankSelection').style.display = 'none';
                document.getElementById('countriesByRegion').style.display = 'block';
                document.getElementById('connectEuBtn').disabled = true;
                document.getElementById('institutionId').value = '';
                
                // Remove bank selection
                document.querySelectorAll('.bank-btn').forEach(b => b.classList.remove('selected'));
            }
            
            // Debug form submission
            window.debugFormSubmission = function(event) {
                const formData = new FormData(event.target);
                const data = {};
                for (let [key, value] of formData.entries()) {
                    data[key] = value;
                }
                
                console.log('Form submission debug:', data);
                
                // Check if institution_id is missing for GoCardless
                if (data.provider === 'gocardless' && !data.institution_id) {
                    console.error('ERROR: Institution ID is missing for GoCardless provider!');
                    console.log('Current form values:', {
                        provider: document.getElementById('selectedProvider').value,
                        country: document.getElementById('selectedCountry').value,
                        institution_id: document.getElementById('institutionId').value
                    });
                    
                    alert('Debug: Institution ID is missing! Please select a bank first.');
                    event.preventDefault();
                    return false;
                }
                
                return true;
            }
            
            function updateConnectButton() {
                const provider = selectedProviderInput.value;
                const country = selectedCountryInput.value;
                
                if (provider === 'stripe') {
                    // Enable US connect button (always ready for Stripe)
                    if (connectUsBtn) connectUsBtn.disabled = false;
                    if (connectEuBtn) connectEuBtn.disabled = true;
                } else if (provider === 'gocardless') {
                    // Enable EU connect button only when country is selected
                    if (connectUsBtn) connectUsBtn.disabled = true;
                    if (connectEuBtn) connectEuBtn.disabled = !country;
                } else {
                    // Disable both buttons when no provider selected
                    if (connectUsBtn) connectUsBtn.disabled = true;
                    if (connectEuBtn) connectEuBtn.disabled = true;
                }
            }
        });
    </script>
</body>
</html>
