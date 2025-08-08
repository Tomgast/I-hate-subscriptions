<?php
echo "<h1>🔧 Minimal Email Test</h1>";

// Test 1: Check if secure loader exists
echo "<h2>Step 1: Secure Loader Check</h2>";
if (file_exists('config/secure_loader.php')) {
    echo "✅ secure_loader.php exists<br>";
    try {
        require_once 'config/secure_loader.php';
        echo "✅ secure_loader.php loaded successfully<br>";
    } catch (Exception $e) {
        echo "❌ Error loading secure_loader.php: " . $e->getMessage() . "<br>";
        exit;
    }
} else {
    echo "❌ secure_loader.php NOT found<br>";
    exit;
}

// Test 2: Check if getSecureConfig function exists
echo "<h2>Step 2: Function Check</h2>";
if (function_exists('getSecureConfig')) {
    echo "✅ getSecureConfig function exists<br>";
} else {
    echo "❌ getSecureConfig function NOT found<br>";
    exit;
}

// Test 3: Try to get a config value
echo "<h2>Step 3: Config Test</h2>";
try {
    $smtpHost = getSecureConfig('SMTP_HOST');
    if ($smtpHost) {
        echo "✅ SMTP_HOST found: " . $smtpHost . "<br>";
    } else {
        echo "⚠️ SMTP_HOST not found or empty<br>";
    }
} catch (Exception $e) {
    echo "❌ Error getting config: " . $e->getMessage() . "<br>";
}

// Test 4: Try to include EmailService without creating instance
echo "<h2>Step 4: EmailService Include Test</h2>";
try {
    require_once 'includes/email_service.php';
    echo "✅ EmailService included successfully<br>";
} catch (ParseError $e) {
    echo "❌ PHP Parse Error in EmailService: " . $e->getMessage() . "<br>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    exit;
} catch (Exception $e) {
    echo "❌ Error including EmailService: " . $e->getMessage() . "<br>";
    exit;
}

// Test 5: Check if EmailService class exists
echo "<h2>Step 5: Class Check</h2>";
if (class_exists('EmailService')) {
    echo "✅ EmailService class exists<br>";
} else {
    echo "❌ EmailService class NOT found<br>";
    exit;
}

// Test 6: Try to create EmailService instance
echo "<h2>Step 6: Instance Creation Test</h2>";
try {
    $emailService = new EmailService();
    echo "✅ EmailService instance created successfully<br>";
    
    // Test if methods exist
    if (method_exists($emailService, 'sendWelcomeEmail')) {
        echo "✅ sendWelcomeEmail method exists<br>";
    }
    if (method_exists($emailService, 'sendUpgradeConfirmation')) {
        echo "✅ sendUpgradeConfirmation method exists<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error creating EmailService instance: " . $e->getMessage() . "<br>";
    echo "<p><strong>This usually means:</strong></p>";
    echo "<ul>";
    echo "<li>Missing SMTP credentials in secure-config.php</li>";
    echo "<li>secure-config.php file doesn't exist</li>";
    echo "<li>Permission issues</li>";
    echo "</ul>";
}

echo "<h2>✅ All Tests Complete</h2>";
echo "<p>If all tests pass, try running the original email test again.</p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 40px; }
h1, h2 { color: #333; }
ul { line-height: 1.6; }
</style>
