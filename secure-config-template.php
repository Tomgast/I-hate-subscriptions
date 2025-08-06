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
    'GOOGLE_CLIENT_SECRET' => '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',
    
    // TrueLayer Bank Integration Configuration
    'TRUELAYER_CLIENT_ID' => 'sandbox-123cashcontrol-496c49',
    'TRUELAYER_CLIENT_SECRET' => 'a7e49c68-72c7-4fda-ac30-4be41407a366',
    'TRUELAYER_ENVIRONMENT' => 'sandbox',
    
    // Email Configuration (if needed)
    'SMTP_HOST' => 'your-smtp-host',
    'SMTP_USERNAME' => 'your-smtp-username',
    'SMTP_PASSWORD' => 'your-smtp-password',
];
