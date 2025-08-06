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
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <div class="container py-6">
        <div class="card">
            <div class="p-5">
                <h1 class="text-2xl font-bold text-gray-900 mb-6">Account Settings</h1>
                
                <?php if (isset($success)): ?>
                <div class="alert" style="background-color: #d1fae5; border: 1px solid #a7f3d0; color: #065f46; margin-bottom: 1rem;">
                    ✅ <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <span class="icon-alert mr-2"></span>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <!-- Profile Section -->
                    <div class="mb-8">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Profile Information</h2>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                                <input type="text" id="name" name="name" class="form-input" 
                                       value="<?php echo htmlspecialchars($userName); ?>">
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                <input type="email" id="email" name="email" class="form-input" 
                                       value="<?php echo htmlspecialchars($userEmail); ?>" disabled>
                                <p class="text-xs text-gray-500 mt-1">Email cannot be changed</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Preferences Section -->
                    <div class="mb-8">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Preferences</h2>
                        
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">Default Currency</label>
                                <select id="currency" name="currency" class="form-input">
                                    <option value="EUR" <?php echo ($preferences['currency'] ?? 'EUR') === 'EUR' ? 'selected' : ''; ?>>€ Euro (EUR)</option>
                                    <option value="USD" <?php echo ($preferences['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>$ US Dollar (USD)</option>
                                    <option value="GBP" <?php echo ($preferences['currency'] ?? '') === 'GBP' ? 'selected' : ''; ?>>£ British Pound (GBP)</option>
                                    <option value="CAD" <?php echo ($preferences['currency'] ?? '') === 'CAD' ? 'selected' : ''; ?>>$ Canadian Dollar (CAD)</option>
                                </select>
                            </div>
                            <div>
                                <label for="timezone" class="block text-sm font-medium text-gray-700 mb-2">Timezone</label>
                                <select id="timezone" name="timezone" class="form-input">
                                    <option value="Europe/Amsterdam" <?php echo ($preferences['timezone'] ?? 'Europe/Amsterdam') === 'Europe/Amsterdam' ? 'selected' : ''; ?>>Amsterdam (CET)</option>
                                    <option value="Europe/London" <?php echo ($preferences['timezone'] ?? '') === 'Europe/London' ? 'selected' : ''; ?>>London (GMT)</option>
                                    <option value="America/New_York" <?php echo ($preferences['timezone'] ?? '') === 'America/New_York' ? 'selected' : ''; ?>>New York (EST)</option>
                                    <option value="America/Los_Angeles" <?php echo ($preferences['timezone'] ?? '') === 'America/Los_Angeles' ? 'selected' : ''; ?>>Los Angeles (PST)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="language" class="block text-sm font-medium text-gray-700 mb-2">Language</label>
                                <select id="language" name="language" class="form-input">
                                    <option value="en" <?php echo ($preferences['language'] ?? 'en') === 'en' ? 'selected' : ''; ?>>English</option>
                                    <option value="nl" <?php echo ($preferences['language'] ?? '') === 'nl' ? 'selected' : ''; ?>>Nederlands</option>
                                    <option value="de" <?php echo ($preferences['language'] ?? '') === 'de' ? 'selected' : ''; ?>>Deutsch</option>
                                    <option value="fr" <?php echo ($preferences['language'] ?? '') === 'fr' ? 'selected' : ''; ?>>Français</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notification Settings -->
                    <div class="mb-8">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Notifications</h2>
                        
                        <div class="space-y-3">
                            <label class="flex items-center">
                                <input type="checkbox" name="email_notifications" 
                                       <?php echo ($preferences['email_notifications'] ?? 1) ? 'checked' : ''; ?>
                                       class="mr-3">
                                <span class="text-sm text-gray-700">Email notifications for upcoming payments</span>
                            </label>
                            
                            <label class="flex items-center">
                                <input type="checkbox" name="dark_mode" 
                                       <?php echo ($preferences['dark_mode'] ?? 0) ? 'checked' : ''; ?>
                                       class="mr-3">
                                <span class="text-sm text-gray-700">Dark mode (coming soon)</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Account Plan -->
                    <div class="mb-8">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Account Plan</h2>
                        
                        <div class="p-4 border border-gray-200 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-medium text-gray-900">
                                        <?php echo $isPaid ? 'Pro Plan' : 'Free Plan'; ?>
                                    </h3>
                                    <p class="text-sm text-gray-500">
                                        <?php if ($isPaid): ?>
                                            Access to all premium features including bank integration
                                        <?php else: ?>
                                            Basic subscription tracking with manual entry
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <?php if (!$isPaid): ?>
                                <a href="upgrade.php" class="btn btn-primary">
                                    Upgrade to Pro
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Save Button -->
                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary">
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
