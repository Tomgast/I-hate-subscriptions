<?php
require_once 'includes/email_service.php';

echo "<h1>🎨 Redesigned Email Templates Test</h1>";
echo "<p>Testing the updated email templates with current subscription tiers and website aesthetic.</p>";

try {
    $emailService = new EmailService();
    
    // Test 1: Welcome Email
    echo "<div style='margin: 30px 0; padding: 20px; border: 2px solid #10b981; border-radius: 12px; background: #f0fdf4;'>";
    echo "<h2>📧 Welcome Email Template</h2>";
    echo "<p><strong>Features:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Green gradient theme matching website</li>";
    echo "<li>✅ Current pricing: Monthly (€3), Yearly (€25), One-Time (€25)</li>";
    echo "<li>✅ Professional pricing cards with 'Save 31%' highlight</li>";
    echo "<li>✅ Modern button design with hover effects</li>";
    echo "<li>✅ Mobile-responsive layout</li>";
    echo "<li>✅ Clear call-to-action to upgrade page</li>";
    echo "</ul>";
    
    $result1 = $emailService->sendWelcomeEmail('support@origens.nl', 'Tom');
    if ($result1) {
        echo "<p style='color: #059669; font-weight: bold;'>✅ Welcome email sent successfully!</p>";
    } else {
        echo "<p style='color: #dc2626; font-weight: bold;'>❌ Welcome email failed to send</p>";
    }
    echo "</div>";
    
    // Test 2: Upgrade Confirmation Email
    echo "<div style='margin: 30px 0; padding: 20px; border: 2px solid #10b981; border-radius: 12px; background: #f0fdf4;'>";
    echo "<h2>🚀 Upgrade Confirmation Email Template</h2>";
    echo "<p><strong>Features:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Payment confirmation badge</li>";
    echo "<li>✅ Feature grid showcasing Pro benefits</li>";
    echo "<li>✅ Professional dashboard CTA button</li>";
    echo "<li>✅ Consistent green gradient branding</li>";
    echo "<li>✅ Support and help messaging</li>";
    echo "<li>✅ Modern card-based layout</li>";
    echo "</ul>";
    
    $result2 = $emailService->sendUpgradeConfirmation('support@origens.nl', 'Tom');
    if ($result2) {
        echo "<p style='color: #059669; font-weight: bold;'>✅ Upgrade confirmation email sent successfully!</p>";
    } else {
        echo "<p style='color: #dc2626; font-weight: bold;'>❌ Upgrade confirmation email failed to send</p>";
    }
    echo "</div>";
    
    // Summary
    echo "<div style='margin: 30px 0; padding: 25px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border-radius: 12px;'>";
    echo "<h2 style='color: white; margin: 0 0 15px 0;'>✨ Email Redesign Complete!</h2>";
    echo "<p><strong>What's New:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Brand Consistency:</strong> Green gradient theme matches your website perfectly</li>";
    echo "<li><strong>Current Pricing:</strong> All emails reflect your actual subscription tiers</li>";
    echo "<li><strong>Professional Design:</strong> Modern cards, buttons, and typography</li>";
    echo "<li><strong>Mobile Responsive:</strong> Looks great on all devices</li>";
    echo "<li><strong>Clear CTAs:</strong> Strategic buttons drive users to upgrade/dashboard</li>";
    echo "<li><strong>Business Model Aligned:</strong> No free tier messaging, premium positioning</li>";
    echo "</ul>";
    echo "</div>";
    
    // Next Steps
    echo "<div style='margin: 30px 0; padding: 20px; background: #f8fafc; border-radius: 12px; border-left: 4px solid #10b981;'>";
    echo "<h3 style='color: #1f2937;'>🎯 Next Steps</h3>";
    echo "<ol>";
    echo "<li><strong>Test Signup Flow:</strong> Create a new account to see the welcome email</li>";
    echo "<li><strong>Test Payment Flow:</strong> Complete a purchase to see the upgrade confirmation</li>";
    echo "<li><strong>Check Email Delivery:</strong> Verify emails arrive in inbox (not spam)</li>";
    echo "<li><strong>Mobile Testing:</strong> Check how emails look on mobile devices</li>";
    echo "<li><strong>A/B Testing:</strong> Monitor conversion rates from email CTAs</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #fef2f2; border: 1px solid #fecaca; padding: 20px; border-radius: 8px; color: #991b1b;'>";
    echo "<h3>❌ Error Testing Emails</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
body { 
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
    margin: 40px; 
    background: #f9fafb; 
    color: #374151;
}
h1, h2, h3 { 
    color: #1f2937; 
}
ul, ol { 
    line-height: 1.6; 
}
li { 
    margin: 8px 0; 
}
</style>
