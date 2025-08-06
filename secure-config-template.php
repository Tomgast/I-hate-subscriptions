<?php
/**
 * SECURE CONFIGURATION FILE
 * 
 * IMPORTANT: This file should be placed OUTSIDE your web root directory
 * (one level up from httpdocs/public_html) so it's not accessible via web browser.
 * 
 * Example placement:
 * /your-domain/secure-config.php  (NOT in httpdocs!)
 * /your-domain/httpdocs/          (your web files are here)
 * 
 * This file contains sensitive API credentials and should never be committed to Git.
 */

return [
    // Google OAuth Configuration
    // Get these from Google Cloud Console -> Credentials
    'GOOGLE_CLIENT_SECRET' => 'REPLACE_WITH_YOUR_GOOGLE_CLIENT_SECRET',
    
    // TrueLayer Bank Integration Configuration  
    // Get these from TrueLayer Console -> Data API
    'TRUELAYER_CLIENT_ID' => 'REPLACE_WITH_YOUR_TRUELAYER_CLIENT_ID',
    'TRUELAYER_CLIENT_SECRET' => 'REPLACE_WITH_YOUR_TRUELAYER_CLIENT_SECRET',
    'TRUELAYER_ENVIRONMENT' => 'sandbox', // or 'live' for production
    
    // Stripe Payment Configuration
    // Get these from Stripe Dashboard -> Developers -> API Keys
    'STRIPE_PUBLISHABLE_KEY' => 'REPLACE_WITH_YOUR_STRIPE_PUBLISHABLE_KEY',
    'STRIPE_SECRET_KEY' => 'REPLACE_WITH_YOUR_STRIPE_SECRET_KEY',
    'STRIPE_WEBHOOK_SECRET' => 'REPLACE_WITH_YOUR_STRIPE_WEBHOOK_SECRET',
    
    // Email Configuration (Plesk SMTP)
    // Configure these with your Plesk email settings
    'SMTP_HOST' => 'shared58.cloud86-host.nl',
    'SMTP_PORT' => '587',
    'SMTP_USERNAME' => 'info@123cashcontrol.com',
    'SMTP_PASSWORD' => 'REPLACE_WITH_YOUR_EMAIL_PASSWORD',
    'FROM_EMAIL' => 'info@123cashcontrol.com',
    'FROM_NAME' => 'CashControl',
];
