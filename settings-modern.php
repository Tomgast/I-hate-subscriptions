<?php
session_start();
require_once 'config/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/signin.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';
$isPaid = $_SESSION['is_paid'] ?? false;

// Handle form submission
if ($_POST) {
    try {
        $pdo = getDBConnection();
        
        // Update user preferences
        $stmt = $pdo->prepare("INSERT INTO user_preferences (user_id, currency, timezone, email_notifications, dark_mode, language) 
                              VALUES (?, ?, ?, ?, ?, ?) 
                              ON DUPLICATE KEY UPDATE 
                              currency = VALUES(currency), 
                              timezone = VALUES(timezone), 
                              email_notifications = VALUES(email_notifications), 
                              dark_mode = VALUES(dark_mode), 
                              language = VALUES(language)");
        
        $stmt->execute([
            $userId,
            $_POST['currency'] ?? 'EUR',
            $_POST['timezone'] ?? 'Europe/Amsterdam',
            isset($_POST['email_notifications']) ? 1 : 0,
            isset($_POST['dark_mode']) ? 1 : 0,
            $_POST['language'] ?? 'en'
        ]);
        
        // Update user name if changed
        if (!empty($_POST['name']) && $_POST['name'] !== $userName) {
            $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
            $stmt->execute([$_POST['name'], $userId]);
            $_SESSION['user_name'] = $_POST['name'];
            $userName = $_POST['name'];
        }
        
        $success = "Settings updated successfully!";
        
    } catch (Exception $e) {
        $error = "Failed to update settings: " . $e->getMessage();
    }
}

// Get current preferences
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
    $stmt->execute([$userId]);
    $preferences = $stmt->fetch() ?: [];
} catch (Exception $e) {
    $preferences = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - CashControl</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .hero-gradient {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 50%, #bbf7d0 100%);
        }
        .wave-animation {
            background: linear-gradient(-45deg, #10b981, #059669, #047857, #065f46);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
    </style>
</head>
<body class="bg-white">
    <?php include 'includes/header.php'; ?>

    <!-- Settings Header -->
    <div class="hero-gradient py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">Account Settings</h1>
                <p class="text-xl text-gray-600 mb-8">Manage your preferences and account information</p>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if (isset($success)): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-lg mb-8 shadow-sm max-w-2xl mx-auto">
                ✅ <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-lg mb-8 shadow-sm max-w-2xl mx-auto">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Settings Content -->
    <section class="py-12 bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="">
                <!-- Profile Section -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 mb-8">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Profile Information</h2>
                            <p class="text-gray-600">Update your personal details and contact information</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                            <input type="text" id="name" name="name" 
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors" 
                                   value="<?php echo htmlspecialchars($userName); ?>">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                            <input type="email" id="email" name="email" 
                                   class="w-full border border-gray-300 rounded-lg px-4 py-3 bg-gray-50 text-gray-500" 
                                   value="<?php echo htmlspecialchars($userEmail); ?>" readonly>
                            <p class="text-xs text-gray-500 mt-1">Email cannot be changed after registration</p>
                        </div>
                    </div>
                </div>
                
                <!-- Preferences Section -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 mb-8">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Preferences</h2>
                            <p class="text-gray-600">Customize your experience and regional settings</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">Default Currency</label>
                            <select id="currency" name="currency" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                <option value="EUR" <?php echo ($preferences['currency'] ?? 'EUR') === 'EUR' ? 'selected' : ''; ?>>€ Euro (EUR)</option>
                                <option value="USD" <?php echo ($preferences['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>$ US Dollar (USD)</option>
                                <option value="GBP" <?php echo ($preferences['currency'] ?? '') === 'GBP' ? 'selected' : ''; ?>>£ British Pound (GBP)</option>
                                <option value="CAD" <?php echo ($preferences['currency'] ?? '') === 'CAD' ? 'selected' : ''; ?>>$ Canadian Dollar (CAD)</option>
                            </select>
                        </div>
                        <div>
                            <label for="timezone" class="block text-sm font-medium text-gray-700 mb-2">Timezone</label>
                            <select id="timezone" name="timezone" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                <option value="Europe/Amsterdam" <?php echo ($preferences['timezone'] ?? 'Europe/Amsterdam') === 'Europe/Amsterdam' ? 'selected' : ''; ?>>Amsterdam (CET)</option>
                                <option value="Europe/London" <?php echo ($preferences['timezone'] ?? '') === 'Europe/London' ? 'selected' : ''; ?>>London (GMT)</option>
                                <option value="America/New_York" <?php echo ($preferences['timezone'] ?? '') === 'America/New_York' ? 'selected' : ''; ?>>New York (EST)</option>
                                <option value="America/Los_Angeles" <?php echo ($preferences['timezone'] ?? '') === 'America/Los_Angeles' ? 'selected' : ''; ?>>Los Angeles (PST)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="language" class="block text-sm font-medium text-gray-700 mb-2">Language</label>
                            <select id="language" name="language" class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                <option value="en" <?php echo ($preferences['language'] ?? 'en') === 'en' ? 'selected' : ''; ?>>English</option>
                                <option value="nl" <?php echo ($preferences['language'] ?? '') === 'nl' ? 'selected' : ''; ?>>Nederlands</option>
                                <option value="de" <?php echo ($preferences['language'] ?? '') === 'de' ? 'selected' : ''; ?>>Deutsch</option>
                                <option value="fr" <?php echo ($preferences['language'] ?? '') === 'fr' ? 'selected' : ''; ?>>Français</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Notification Settings -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 mb-8">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5-5-5h5v-5a7.5 7.5 0 01-7.5-7.5H7.5"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Notifications</h2>
                            <p class="text-gray-600">Control how and when you receive notifications</p>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <label class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer">
                            <input type="checkbox" name="email_notifications" 
                                   <?php echo ($preferences['email_notifications'] ?? 1) ? 'checked' : ''; ?>
                                   class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500 mr-4">
                            <div>
                                <span class="text-sm font-medium text-gray-900">Email notifications for upcoming payments</span>
                                <p class="text-xs text-gray-500">Get reminded before your subscriptions renew</p>
                            </div>
                        </label>
                        
                        <label class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer">
                            <input type="checkbox" name="dark_mode" 
                                   <?php echo ($preferences['dark_mode'] ?? 0) ? 'checked' : ''; ?>
                                   class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500 mr-4">
                            <div>
                                <span class="text-sm font-medium text-gray-900">Dark mode</span>
                                <p class="text-xs text-gray-500">Coming soon - Enable dark theme</p>
                            </div>
                        </label>
                    </div>
                </div>
                
                <!-- Account Plan -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 mb-8">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Account Plan</h2>
                            <p class="text-gray-600">Manage your subscription and billing</p>
                        </div>
                    </div>
                    
                    <div class="p-6 border border-gray-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                    <?php echo $isPaid ? 'Pro Plan' : 'Free Plan'; ?>
                                    <?php if ($isPaid): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 ml-2">
                                        Active
                                    </span>
                                    <?php endif; ?>
                                </h3>
                                <p class="text-gray-600 mb-4">
                                    <?php if ($isPaid): ?>
                                        Access to all premium features including bank integration and advanced analytics
                                    <?php else: ?>
                                        Basic subscription tracking with manual entry - upgrade for more features
                                    <?php endif; ?>
                                </p>
                                <?php if ($isPaid): ?>
                                <div class="flex items-center text-sm text-gray-500">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Bank integration enabled
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php if (!$isPaid): ?>
                            <a href="upgrade.php" class="gradient-bg text-white px-6 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-200">
                                Upgrade to Pro
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Save Button -->
                <div class="flex justify-end">
                    <button type="submit" class="gradient-bg text-white px-8 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-200">
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </section>

</body>
</html>
