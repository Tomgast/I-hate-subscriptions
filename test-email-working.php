<?php
// Simple working email test
echo "<!DOCTYPE html><html><head><title>Email Test</title></head><body>";
echo "<h1>Email Test - Working Version</h1>";

// Step 1: Basic check
echo "<h2>Step 1: Basic File Check</h2>";
if (file_exists('includes/email_service.php')) {
    echo "✅ EmailService file exists<br>";
} else {
    echo "❌ EmailService file missing<br>";
    exit;
}

// Step 2: Try to include
echo "<h2>Step 2: Include EmailService</h2>";
try {
    require_once 'includes/email_service.php';
    echo "✅ EmailService included successfully<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    exit;
}

// Step 3: Create instance
echo "<h2>Step 3: Create EmailService Instance</h2>";
try {
    $emailService = new EmailService();
    echo "✅ EmailService created successfully<br>";
} catch (Exception $e) {
    echo "❌ Error creating EmailService: " . $e->getMessage() . "<br>";
    exit;
}

// Step 4: Test email sending
echo "<h2>Step 4: Send Test Emails</h2>";

echo "<div style='border: 2px solid #10b981; padding: 20px; margin: 20px 0; border-radius: 10px;'>";
echo "<h3>📧 Welcome Email Test</h3>";
try {
    $result1 = $emailService->sendWelcomeEmail('support@origens.nl', 'Tom');
    if ($result1) {
        echo "<p style='color: green; font-weight: bold;'>✅ Welcome email sent successfully to support@origens.nl!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Welcome email failed to send</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error sending welcome email: " . $e->getMessage() . "</p>";
}
echo "</div>";

echo "<div style='border: 2px solid #10b981; padding: 20px; margin: 20px 0; border-radius: 10px;'>";
echo "<h3>🚀 Upgrade Confirmation Email Test</h3>";
try {
    $result2 = $emailService->sendUpgradeConfirmation('support@origens.nl', 'Tom');
    if ($result2) {
        echo "<p style='color: green; font-weight: bold;'>✅ Upgrade confirmation email sent successfully to support@origens.nl!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Upgrade confirmation email failed to send</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error sending upgrade email: " . $e->getMessage() . "</p>";
}
echo "</div>";

echo "<h2>✅ Test Complete</h2>";
echo "<p>Check your email at <strong>support@origens.nl</strong> to see the redesigned templates!</p>";

echo "<div style='background: #f0f9ff; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h3>📋 What to Look For in the Emails:</h3>";
echo "<ul>";
echo "<li><strong>Welcome Email:</strong> Large hero CTA, 6 feature cards with icons, clickable pricing cards</li>";
echo "<li><strong>Upgrade Email:</strong> Professional confirmation with dashboard CTA</li>";
echo "<li><strong>Design:</strong> Green gradient theme matching your website</li>";
echo "<li><strong>Mobile:</strong> Responsive design that works on all devices</li>";
echo "</ul>";
echo "</div>";

echo "</body></html>";
?>
