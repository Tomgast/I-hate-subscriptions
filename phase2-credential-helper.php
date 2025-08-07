<?php
/**
 * PHASE 2.1: CREDENTIAL UPDATE HELPER
 * Safe tool to help update critical credentials in secure-config.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phase 2.1 - Credential Update Helper</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-4xl mx-auto py-8 px-4">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">🔑 Phase 2.1: Credential Update Helper</h1>
            <p class="text-gray-600 mb-8">Safe tool to help update critical credentials in secure-config.php</p>
            
            <div class="space-y-8">
                
                <!-- Current Status Check -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">📊 Current Credential Status</h2>
                    <?php
                    // Load current config to show status
                    try {
                        require_once __DIR__ . '/config/secure_loader.php';
                        
                        $credentials = [
                            'Stripe Secret Key' => getSecureConfig('STRIPE_SECRET_KEY'),
                            'Stripe Publishable Key' => getSecureConfig('STRIPE_PUBLISHABLE_KEY'),
                            'SMTP Username' => getSecureConfig('SMTP_USER'),
                            'SMTP Password' => getSecureConfig('SMTP_PASS'),
                            'Database User' => getSecureConfig('DB_USER'),
                        ];
                        
                        foreach ($credentials as $name => $value) {
                            if ($value && !empty(trim($value)) && !str_contains($value, 'REPLACE')) {
                                $masked = substr($value, 0, 4) . '***' . substr($value, -4);
                                echo "<div class='text-green-600'>✅ $name: $masked</div>";
                            } else {
                                echo "<div class='text-red-600'>❌ $name: Missing or placeholder</div>";
                            }
                        }
                        
                    } catch (Exception $e) {
                        echo "<div class='text-red-600'>❌ Error loading config: " . htmlspecialchars($e->getMessage()) . "</div>";
                    }
                    ?>
                </div>

                <!-- Stripe Keys Section -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">💳 Stripe API Keys</h2>
                    
                    <div class="bg-blue-50 p-4 rounded mb-4">
                        <h3 class="font-bold text-blue-800 mb-2">How to Get Your Stripe Keys:</h3>
                        <ol class="list-decimal list-inside text-blue-700 space-y-1">
                            <li>Go to <a href="https://dashboard.stripe.com/apikeys" target="_blank" class="underline">Stripe Dashboard → API Keys</a></li>
                            <li>Choose <strong>Test keys</strong> (for testing) or <strong>Live keys</strong> (for production)</li>
                            <li>Copy the <strong>Secret key</strong> (starts with sk_test_ or sk_live_)</li>
                            <li>Copy the <strong>Publishable key</strong> (starts with pk_test_ or pk_live_)</li>
                        </ol>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Secret Key</label>
                            <input type="text" id="stripe_secret" placeholder="sk_test_... or sk_live_..." 
                                   class="w-full p-3 border border-gray-300 rounded-lg font-mono text-sm">
                            <p class="text-xs text-gray-500 mt-1">Starts with sk_test_ or sk_live_</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Publishable Key</label>
                            <input type="text" id="stripe_publishable" placeholder="pk_test_... or pk_live_..." 
                                   class="w-full p-3 border border-gray-300 rounded-lg font-mono text-sm">
                            <p class="text-xs text-gray-500 mt-1">Starts with pk_test_ or pk_live_</p>
                        </div>
                    </div>
                </div>

                <!-- SMTP Credentials Section -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">📧 SMTP Email Credentials</h2>
                    
                    <div class="bg-green-50 p-4 rounded mb-4">
                        <h3 class="font-bold text-green-800 mb-2">How to Find Your SMTP Credentials:</h3>
                        <ol class="list-decimal list-inside text-green-700 space-y-1">
                            <li>Log into your Plesk control panel</li>
                            <li>Go to <strong>Mail</strong> → <strong>Email Addresses</strong></li>
                            <li>Find <strong>info@123cashcontrol.com</strong> (or your main email)</li>
                            <li>Check the password or reset it if needed</li>
                        </ol>
                        <p class="text-green-600 mt-2"><strong>Host:</strong> shared58.cloud86-host.nl (already configured)</p>
                        <p class="text-green-600"><strong>Port:</strong> 587 (already configured)</p>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Username</label>
                            <input type="text" id="smtp_user" placeholder="info@123cashcontrol.com" 
                                   class="w-full p-3 border border-gray-300 rounded-lg">
                            <p class="text-xs text-gray-500 mt-1">Usually your full email address</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Password</label>
                            <input type="password" id="smtp_pass" placeholder="Your email password" 
                                   class="w-full p-3 border border-gray-300 rounded-lg">
                            <p class="text-xs text-gray-500 mt-1">The password for your email account</p>
                        </div>
                    </div>
                </div>

                <!-- Database User Section -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">🗄️ Database User</h2>
                    
                    <div class="bg-yellow-50 p-4 rounded mb-4">
                        <h3 class="font-bold text-yellow-800 mb-2">Database User Credential:</h3>
                        <p class="text-yellow-700">Based on our audit, your database user is <strong>123cashcontrol</strong></p>
                        <p class="text-yellow-700">This should be added to your secure config for consistency.</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Database User</label>
                        <input type="text" id="db_user" value="123cashcontrol" 
                               class="w-full p-3 border border-gray-300 rounded-lg bg-gray-50" readonly>
                        <p class="text-xs text-gray-500 mt-1">This value is confirmed from your database connection</p>
                    </div>
                </div>

                <!-- Generate Config Section -->
                <div class="border rounded-lg p-6 bg-blue-50">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">🔧 Generate Updated Config</h2>
                    
                    <button onclick="generateConfig()" 
                            class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 mb-4">
                        🔄 Generate Updated secure-config.php
                    </button>
                    
                    <div id="config_output" class="hidden">
                        <h3 class="font-bold mb-2">Updated Configuration:</h3>
                        <div class="bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-sm overflow-x-auto">
                            <pre id="config_content"></pre>
                        </div>
                        
                        <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded">
                            <h4 class="font-bold text-yellow-800 mb-2">⚠️ Important Instructions:</h4>
                            <ol class="list-decimal list-inside text-yellow-700 space-y-1">
                                <li>Copy the generated configuration above</li>
                                <li>Access your server via FTP, SSH, or Plesk File Manager</li>
                                <li>Navigate to: <code>/var/www/vhosts/123cashcontrol.com/secure-config.php</code></li>
                                <li>Update ONLY the credential values, keep the rest of the file intact</li>
                                <li>Save the file and test the credentials</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- Test Section -->
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">🧪 Test Updated Credentials</h2>
                    <p class="text-gray-600 mb-4">After updating your secure-config.php file, use these tools to test:</p>
                    
                    <div class="grid md:grid-cols-3 gap-4">
                        <a href="phase1-config-audit.php" 
                           class="bg-green-500 text-white px-4 py-3 rounded-lg hover:bg-green-600 text-center">
                            🔧 Re-run Config Audit
                        </a>
                        <a href="test/test-connections.php" 
                           class="bg-blue-500 text-white px-4 py-3 rounded-lg hover:bg-blue-600 text-center">
                            🔗 Test Service Connections
                        </a>
                        <a href="test-debug.php" 
                           class="bg-purple-500 text-white px-4 py-3 rounded-lg hover:bg-purple-600 text-center">
                            🐛 Debug Services
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        function generateConfig() {
            const stripeSecret = document.getElementById('stripe_secret').value.trim();
            const stripePublishable = document.getElementById('stripe_publishable').value.trim();
            const smtpUser = document.getElementById('smtp_user').value.trim();
            const smtpPass = document.getElementById('smtp_pass').value.trim();
            const dbUser = document.getElementById('db_user').value.trim();
            
            // Validation
            const errors = [];
            
            if (!stripeSecret || !stripeSecret.startsWith('sk_')) {
                errors.push('Stripe Secret Key must start with sk_test_ or sk_live_');
            }
            
            if (!stripePublishable || !stripePublishable.startsWith('pk_')) {
                errors.push('Stripe Publishable Key must start with pk_test_ or pk_live_');
            }
            
            if (!smtpUser || !smtpUser.includes('@')) {
                errors.push('SMTP Username should be a valid email address');
            }
            
            if (!smtpPass || smtpPass.length < 6) {
                errors.push('SMTP Password is required and should be at least 6 characters');
            }
            
            if (errors.length > 0) {
                alert('Please fix these issues:\n\n' + errors.join('\n'));
                return;
            }
            
            // Generate config content
            const configContent = `// Updated credentials for Phase 2.1
// Add these to your secure-config.php file:

'STRIPE_SECRET_KEY' => '${stripeSecret}',
'STRIPE_PUBLISHABLE_KEY' => '${stripePublishable}',
'SMTP_USER' => '${smtpUser}',
'SMTP_PASS' => '${smtpPass}',
'DB_USER' => '${dbUser}',

// Note: Keep all your existing credentials intact!
// Only update the values above in your existing secure-config.php file.`;
            
            document.getElementById('config_content').textContent = configContent;
            document.getElementById('config_output').classList.remove('hidden');
            
            // Scroll to output
            document.getElementById('config_output').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>
