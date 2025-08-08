<?php
/**
 * PRIVACY POLICY - CashControl
 * GDPR-compliant privacy policy for Netherlands/EU
 */

session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - CashControl</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>
    
    <div class="max-w-4xl mx-auto px-4 py-12">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Privacy Policy</h1>
                <p class="text-gray-600">Last updated: <?php echo date('F j, Y'); ?></p>
                <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <p class="text-green-800 font-medium">🔒 Your Privacy is Our Priority</p>
                    <p class="text-green-700 text-sm mt-1">We are committed to protecting your personal data and respecting your privacy rights under GDPR.</p>
                </div>
            </div>

            <div class="prose prose-gray max-w-none">
                
                <!-- 1. Introduction -->
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">1. Introduction</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        CashControl ("we," "our," or "us") operates the website <strong>123cashcontrol.com</strong> (the "Service"). 
                        This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our Service.
                    </p>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        We are committed to protecting your privacy and ensuring transparent handling of your personal data in compliance with:
                    </p>
                    <ul class="list-disc pl-6 text-gray-700 mb-4">
                        <li>EU General Data Protection Regulation (GDPR)</li>
                        <li>Dutch Personal Data Protection Act (UAVG)</li>
                        <li>Other applicable privacy laws in the Netherlands and EU</li>
                    </ul>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <p class="text-blue-800 font-medium">📍 Data Controller Information:</p>
                        <p class="text-blue-700 text-sm mt-1">
                            <strong>Company:</strong> CashControl<br>
                            <strong>Location:</strong> Netherlands<br>
                            <strong>Contact:</strong> support@123cashcontrol.com
                        </p>
                    </div>
                </section>

                <!-- 2. Information We Collect -->
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">2. Information We Collect</h2>
                    
                    <h3 class="text-xl font-medium text-gray-800 mb-3">2.1 Personal Information You Provide</h3>
                    <ul class="list-disc pl-6 text-gray-700 mb-4">
                        <li><strong>Account Information:</strong> Name, email address when you create an account</li>
                        <li><strong>Authentication Data:</strong> Google OAuth credentials (handled by Google, we only receive basic profile info)</li>
                        <li><strong>Payment Information:</strong> Processed securely by Stripe (we do not store credit card details)</li>
                        <li><strong>Subscription Data:</strong> Information about your subscriptions that you manually add or that we detect through bank connections</li>
                    </ul>

                    <h3 class="text-xl font-medium text-gray-800 mb-3">2.2 Financial Data (With Your Explicit Consent)</h3>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                        <p class="text-yellow-800 font-medium">⚠️ Important: Bank data access requires your explicit consent</p>
                    </div>
                    <ul class="list-disc pl-6 text-gray-700 mb-4">
                        <li><strong>Bank Account Information:</strong> Account names, balances, transaction history (only when you explicitly connect your bank)</li>
                        <li><strong>Transaction Data:</strong> Used solely to identify recurring subscription payments</li>
                        <li><strong>Connection Tokens:</strong> Secure tokens to maintain bank connections (encrypted and stored securely)</li>
                    </ul>

                    <h3 class="text-xl font-medium text-gray-800 mb-3">2.3 Technical Information</h3>
                    <ul class="list-disc pl-6 text-gray-700 mb-4">
                        <li><strong>Usage Data:</strong> How you interact with our Service</li>
                        <li><strong>Device Information:</strong> Browser type, operating system, IP address</li>
                        <li><strong>Cookies:</strong> Essential cookies for functionality (see Cookie Policy below)</li>
                    </ul>
                </section>

                <!-- 3. How We Use Your Information -->
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">3. How We Use Your Information</h2>
                    
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                        <p class="text-green-800 font-medium">✅ We use your data ONLY for the following purposes:</p>
                    </div>

                    <h3 class="text-xl font-medium text-gray-800 mb-3">3.1 Service Provision</h3>
                    <ul class="list-disc pl-6 text-gray-700 mb-4">
                        <li>Create and manage your account</li>
                        <li>Process payments and manage subscriptions</li>
                        <li>Analyze bank transactions to identify recurring subscriptions</li>
                        <li>Send subscription reminders and notifications (if enabled)</li>
                        <li>Provide customer support</li>
                    </ul>

                    <h3 class="text-xl font-medium text-gray-800 mb-3">3.2 Legal Basis for Processing (GDPR Article 6)</h3>
                    <ul class="list-disc pl-6 text-gray-700 mb-4">
                        <li><strong>Contract Performance:</strong> Account management, service delivery</li>
                        <li><strong>Explicit Consent:</strong> Bank data access, marketing communications</li>
                        <li><strong>Legitimate Interest:</strong> Service improvement, fraud prevention</li>
                        <li><strong>Legal Obligation:</strong> Tax records, compliance requirements</li>
                    </ul>
                </section>

                <!-- 4. Data Sharing and Third Parties -->
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">4. Data Sharing and Third Parties</h2>
                    
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                        <p class="text-red-800 font-medium">🚫 We DO NOT sell, rent, or share your personal data with third parties for marketing purposes</p>
                    </div>

                    <h3 class="text-xl font-medium text-gray-800 mb-3">4.1 Limited Third-Party Services</h3>
                    <p class="text-gray-700 mb-4">We only share data with trusted service providers who help us operate our Service:</p>
                    
                    <div class="grid md:grid-cols-2 gap-4 mb-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-800 mb-2">🔐 Google OAuth</h4>
                            <p class="text-sm text-gray-600">Authentication only - we receive basic profile info (name, email)</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-800 mb-2">💳 Stripe</h4>
                            <p class="text-sm text-gray-600">Payment processing and potential bank connections - GDPR compliant</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-800 mb-2">📧 Email Service</h4>
                            <p class="text-sm text-gray-600">Transactional emails only (account, subscription notifications)</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-800 mb-2">🏦 Bank APIs</h4>
                            <p class="text-sm text-gray-600">Secure bank connections for transaction analysis (with your consent)</p>
                        </div>
                    </div>

                    <h3 class="text-xl font-medium text-gray-800 mb-3">4.2 Legal Disclosures</h3>
                    <p class="text-gray-700 mb-4">We may disclose your information only when required by law or to:</p>
                    <ul class="list-disc pl-6 text-gray-700 mb-4">
                        <li>Comply with legal obligations</li>
                        <li>Protect our rights and property</li>
                        <li>Prevent fraud or security threats</li>
                        <li>Protect user safety</li>
                    </ul>
                </section>

                <!-- 5. Data Security -->
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">5. Data Security</h2>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <p class="text-blue-800 font-medium">🔒 We implement industry-standard security measures:</p>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4 mb-4">
                        <div class="bg-white border border-gray-200 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-800 mb-2">🔐 Encryption</h4>
                            <ul class="text-sm text-gray-600 list-disc pl-4">
                                <li>HTTPS/TLS for all data transmission</li>
                                <li>Encrypted database storage</li>
                                <li>Secure token storage</li>
                            </ul>
                        </div>
                        <div class="bg-white border border-gray-200 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-800 mb-2">🛡️ Access Control</h4>
                            <ul class="text-sm text-gray-600 list-disc pl-4">
                                <li>Strict access controls</li>
                                <li>Regular security audits</li>
                                <li>Secure hosting infrastructure</li>
                            </ul>
                        </div>
                    </div>

                    <p class="text-gray-700 mb-4">
                        <strong>Data Location:</strong> Your data is stored on secure servers within the EU/EEA to ensure GDPR compliance.
                    </p>
                </section>

                <!-- 6. Your Rights Under GDPR -->
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">6. Your Rights Under GDPR</h2>
                    
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                        <p class="text-green-800 font-medium">✅ You have the following rights regarding your personal data:</p>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4 mb-4">
                        <div class="bg-white border border-gray-200 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-800 mb-2">📋 Right to Access</h4>
                            <p class="text-sm text-gray-600">Request a copy of your personal data we hold</p>
                        </div>
                        <div class="bg-white border border-gray-200 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-800 mb-2">✏️ Right to Rectification</h4>
                            <p class="text-sm text-gray-600">Correct inaccurate or incomplete data</p>
                        </div>
                        <div class="bg-white border border-gray-200 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-800 mb-2">🗑️ Right to Erasure</h4>
                            <p class="text-sm text-gray-600">Request deletion of your personal data</p>
                        </div>
                        <div class="bg-white border border-gray-200 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-800 mb-2">⏸️ Right to Restrict Processing</h4>
                            <p class="text-sm text-gray-600">Limit how we use your data</p>
                        </div>
                        <div class="bg-white border border-gray-200 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-800 mb-2">📤 Right to Data Portability</h4>
                            <p class="text-sm text-gray-600">Receive your data in a portable format</p>
                        </div>
                        <div class="bg-white border border-gray-200 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-800 mb-2">🚫 Right to Object</h4>
                            <p class="text-sm text-gray-600">Object to processing based on legitimate interests</p>
                        </div>
                    </div>

                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                        <p class="text-yellow-800 font-medium">📞 To exercise your rights, contact us at:</p>
                        <p class="text-yellow-700 text-sm mt-1">
                            <strong>Email:</strong> support@123cashcontrol.com<br>
                            <strong>Response Time:</strong> Within 30 days as required by GDPR
                        </p>
                    </div>
                </section>

                <!-- 7. Data Retention -->
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">7. Data Retention</h2>
                    
                    <p class="text-gray-700 mb-4">We retain your personal data only as long as necessary:</p>
                    
                    <div class="bg-gray-50 p-4 rounded-lg mb-4">
                        <h4 class="font-medium text-gray-800 mb-2">Retention Periods:</h4>
                        <ul class="list-disc pl-6 text-gray-700 text-sm">
                            <li><strong>Account Data:</strong> Until account deletion + 30 days</li>
                            <li><strong>Financial Data:</strong> Until bank connection is revoked + 90 days</li>
                            <li><strong>Payment Records:</strong> 7 years (tax/legal requirements)</li>
                            <li><strong>Support Communications:</strong> 2 years</li>
                            <li><strong>Marketing Consent:</strong> Until withdrawn</li>
                        </ul>
                    </div>

                    <p class="text-gray-700">
                        <strong>Account Deletion:</strong> When you delete your account, we will permanently delete your personal data within 30 days, except where we are legally required to retain certain information.
                    </p>
                </section>

                <!-- 8. Cookies and Tracking -->
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">8. Cookies and Tracking</h2>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <p class="text-blue-800 font-medium">🍪 We use minimal, essential cookies only:</p>
                    </div>

                    <div class="bg-white border border-gray-200 p-4 rounded-lg mb-4">
                        <h4 class="font-medium text-gray-800 mb-2">Essential Cookies (No Consent Required)</h4>
                        <ul class="list-disc pl-6 text-gray-700 text-sm">
                            <li><strong>Session Cookies:</strong> Keep you logged in</li>
                            <li><strong>Security Cookies:</strong> Prevent fraud and protect your account</li>
                            <li><strong>Preference Cookies:</strong> Remember your settings</li>
                        </ul>
                    </div>

                    <p class="text-gray-700 mb-4">
                        <strong>No Tracking:</strong> We do not use analytics cookies, advertising cookies, or third-party tracking technologies without your explicit consent.
                    </p>
                </section>

                <!-- 9. International Transfers -->
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">9. International Data Transfers</h2>
                    
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                        <p class="text-green-800 font-medium">🌍 EU/EEA Data Protection:</p>
                        <p class="text-green-700 text-sm mt-1">
                            Your data is primarily stored and processed within the EU/EEA. Any transfers outside the EU/EEA are protected by appropriate safeguards such as Standard Contractual Clauses or adequacy decisions.
                        </p>
                    </div>

                    <p class="text-gray-700">
                        <strong>Third-Party Services:</strong> Some of our service providers (like Google, Stripe) may process data outside the EU/EEA, but they provide adequate protection through Privacy Shield, Standard Contractual Clauses, or other approved mechanisms.
                    </p>
                </section>

                <!-- 10. Children's Privacy -->
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">10. Children's Privacy</h2>
                    
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                        <p class="text-red-800 font-medium">🔞 Age Restriction:</p>
                        <p class="text-red-700 text-sm mt-1">
                            Our Service is not intended for individuals under 16 years of age. We do not knowingly collect personal data from children under 16. If we become aware that we have collected personal data from a child under 16, we will delete such information immediately.
                        </p>
                    </div>
                </section>

                <!-- 11. Changes to Privacy Policy -->
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">11. Changes to This Privacy Policy</h2>
                    
                    <p class="text-gray-700 mb-4">
                        We may update this Privacy Policy from time to time. We will notify you of any material changes by:
                    </p>
                    <ul class="list-disc pl-6 text-gray-700 mb-4">
                        <li>Posting the updated policy on our website</li>
                        <li>Sending you an email notification</li>
                        <li>Displaying a prominent notice on our Service</li>
                    </ul>
                    <p class="text-gray-700">
                        <strong>Effective Date:</strong> Changes become effective 30 days after notification, giving you time to review and object if necessary.
                    </p>
                </section>

                <!-- 12. Contact Information -->
                <section class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">12. Contact Information</h2>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <p class="text-blue-800 font-medium">📞 Data Protection Contact:</p>
                        <div class="text-blue-700 text-sm mt-2">
                            <p><strong>Email:</strong> support@123cashcontrol.com</p>
                            <p><strong>Subject Line:</strong> "Privacy/GDPR Request"</p>
                            <p><strong>Response Time:</strong> Within 30 days</p>
                        </div>
                    </div>

                    <p class="text-gray-700 mb-4">
                        <strong>Supervisory Authority:</strong> If you have concerns about our data handling practices, you have the right to lodge a complaint with the Dutch Data Protection Authority (Autoriteit Persoonsgegevens):
                    </p>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-gray-700 text-sm">
                            <strong>Autoriteit Persoonsgegevens</strong><br>
                            Postbus 93374<br>
                            2509 AJ Den Haag<br>
                            Website: autoriteitpersoonsgegevens.nl
                        </p>
                    </div>
                </section>

                <!-- Summary Box -->
                <div class="bg-gradient-to-r from-green-50 to-blue-50 border border-green-200 rounded-lg p-6 mt-8">
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">📋 Privacy Policy Summary</h3>
                    <div class="grid md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="font-medium text-gray-800 mb-2">✅ What We Do:</p>
                            <ul class="text-gray-700 list-disc pl-4">
                                <li>Protect your data with strong security</li>
                                <li>Use data only for service provision</li>
                                <li>Respect your GDPR rights</li>
                                <li>Keep data within EU/EEA</li>
                            </ul>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800 mb-2">🚫 What We Don't Do:</p>
                            <ul class="text-gray-700 list-disc pl-4">
                                <li>Sell or share your data</li>
                                <li>Use unnecessary tracking</li>
                                <li>Keep data longer than needed</li>
                                <li>Process data without legal basis</li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <footer class="bg-gray-900 text-white py-8 mt-12">
        <div class="max-w-6xl mx-auto px-4 text-center">
            <p class="text-gray-400">© <?php echo date('Y'); ?> CashControl. All rights reserved.</p>
            <div class="mt-4 space-x-6">
                <a href="privacy-policy.php" class="text-gray-400 hover:text-white transition-colors">Privacy Policy</a>
                <a href="terms-of-service.php" class="text-gray-400 hover:text-white transition-colors">Terms of Service</a>
                <a href="mailto:support@123cashcontrol.com" class="text-gray-400 hover:text-white transition-colors">Contact</a>
            </div>
        </div>
    </footer>
</body>
</html>
