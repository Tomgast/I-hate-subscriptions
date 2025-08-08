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
                
                if (!$provider || !in_array($provider, ['stripe', 'gocardless'])) {
                    throw new Exception("Please select a valid bank provider.");
                }
                
                if ($provider === 'gocardless' && !$country) {
                    throw new Exception("Please select your country for EU bank connections.");
                }
                
                $options = [];
                if ($provider === 'gocardless') {
                    $options['country'] = $country;
                    if ($institutionId) {
                        $options['institution_id'] = $institutionId;
                    }
                }
                
                $result = $providerRouter->createBankConnectionSession($userId, $provider, $options);
                
                if ($result['success']) {
                    header('Location: ' . $result['auth_url']);
                    exit;
                } else {
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
                        
                        <form method="POST" id="scanForm">
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
                                    <p class="text-center text-muted mb-4">Select your country to see available banks</p>
                                    
                                    <div class="country-grid">
                                        <div class="country-btn" data-country="NL">
                                            <div class="fw-bold">🇳🇱 Netherlands</div>
                                            <small class="text-muted">ING, ABN AMRO, Rabobank</small>
                                        </div>
                                        <div class="country-btn" data-country="DE">
                                            <div class="fw-bold">🇩🇪 Germany</div>
                                            <small class="text-muted">Deutsche Bank, Commerzbank</small>
                                        </div>
                                        <div class="country-btn" data-country="FR">
                                            <div class="fw-bold">🇫🇷 France</div>
                                            <small class="text-muted">BNP Paribas, Crédit Agricole</small>
                                        </div>
                                        <div class="country-btn" data-country="ES">
                                            <div class="fw-bold">🇪🇸 Spain</div>
                                            <small class="text-muted">Santander, BBVA</small>
                                        </div>
                                        <div class="country-btn" data-country="IT">
                                            <div class="fw-bold">🇮🇹 Italy</div>
                                            <small class="text-muted">UniCredit, Intesa Sanpaolo</small>
                                        </div>
                                        <div class="country-btn" data-country="GB">
                                            <div class="fw-bold">🇬🇧 United Kingdom</div>
                                            <small class="text-muted">Barclays, HSBC, Lloyds</small>
                                        </div>
                                    </div>
                                    
                                    <!-- Bank Selection (shown after country selection) -->
                                    <div id="bankSelection" style="display: none; margin-top: 30px;">
                                        <h5 class="text-center mb-3">Choose Your Bank</h5>
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
                            
                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg" id="connectBtn" disabled>
                                    <i class="fas fa-link me-2"></i>
                                    Connect Bank Account
                                </button>
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
            const connectBtn = document.getElementById('connectBtn');
            const selectedProviderInput = document.getElementById('selectedProvider');
            const selectedCountryInput = document.getElementById('selectedCountry');
            
            // Auto-select suggested provider
            const suggestedCard = document.querySelector('[data-suggested="true"]');
            if (suggestedCard) {
                const suggestedProvider = suggestedCard.dataset.provider;
                const suggestedRadio = document.getElementById(suggestedProvider + '_radio');
                if (suggestedRadio) {
                    suggestedRadio.checked = true;
                    selectProvider(suggestedProvider);
                }
            }
            
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
                
                // Show/hide country selection
                if (provider === 'gocardless') {
                    document.getElementById('euBanksSection').style.display = 'block';
                    updateConnectButton();
                } else {
                    document.getElementById('euBanksSection').style.display = 'none';
                    selectedCountryInput.value = '';
                    connectBtn.disabled = false;
                }
            }
            
            // Country selection for EU banks
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
            
            // Function to load banks for a country
            async function loadBanksForCountry(country) {
                const bankGrid = document.getElementById('bankGrid');
                const bankSelection = document.getElementById('bankSelection');
                
                // Show loading
                bankGrid.innerHTML = '<div class="col-12 text-center"><i class="fas fa-spinner fa-spin"></i> Loading banks...</div>';
                bankSelection.style.display = 'block';
                
                try {
                    const response = await fetch('get-banks.php?country=' + country);
                    const banks = await response.json();
                    
                    if (banks.length === 0) {
                        bankGrid.innerHTML = '<div class="col-12 text-center text-muted">No banks available for this country</div>';
                        return;
                    }
                    
                    // Display banks
                    bankGrid.innerHTML = '';
                    banks.forEach(bank => {
                        const bankDiv = document.createElement('div');
                        bankDiv.className = 'col-md-4 col-sm-6';
                        bankDiv.innerHTML = `
                            <div class="bank-btn" data-institution-id="${bank.id}">
                                <img src="${bank.logo}" alt="${bank.name}" class="bank-logo" onerror="this.style.display='none'">
                                <div class="fw-bold">${bank.name}</div>
                                <small class="text-muted">${bank.bic || ''}</small>
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
                            
                            // Enable the connect button
                            document.getElementById('connectEuBtn').disabled = false;
                        });
                    });
                    
                } catch (error) {
                    console.error('Error loading banks:', error);
                    bankGrid.innerHTML = '<div class="col-12 text-center text-danger">Error loading banks. Please try again.</div>';
                }
            }
            
            function updateConnectButton() {
                const provider = selectedProviderInput.value;
                const country = selectedCountryInput.value;
                
                if (provider === 'stripe') {
                    connectBtn.disabled = false;
                } else if (provider === 'gocardless') {
                    connectBtn.disabled = !country;
                } else {
                    connectBtn.disabled = true;
                }
            }
        });
    </script>
</body>
</html>
