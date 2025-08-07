<?php
/**
 * CashControl Secure Configuration
 * 
 * IMPORTANT: This file should be placed OUTSIDE your web root directory
 * (in hoofdmap, one level up from httpdocs) so it's not accessible via web browser.
 * 
 * File location: /hoofdmap/secure-config.php  (NOT in httpdocs!)
 * Web files:     /hoofdmap/httpdocs/          (your web files are here)
 * 
 * This file contains sensitive API credentials and should never be committed to Git.
 * Copy this template to secure-config.php and fill in your actual credentials.
 */

return [
    // =============================================================================
    // DATABASE CONFIGURATION (MariaDB on Plesk)
    // =============================================================================
    
    // MariaDB Database Connection
    'DB_HOST' => 'YOUR_DB_HOST',
    'DB_PORT' => '3306',
    'DB_NAME' => 'YOUR_DB_NAME',
    'DB_USERNAME' => 'YOUR_DB_USERNAME',
    'DB_PASSWORD' => 'YOUR_DB_PASSWORD',
    'DB_CHARSET' => 'utf8mb4',
    
    // =============================================================================
    // GOOGLE OAUTH CONFIGURATION
    // =============================================================================
    
    // Google OAuth Credentials (from Google Cloud Console -> Credentials)
    'GOOGLE_CLIENT_ID' => 'YOUR_GOOGLE_CLIENT_ID',
    'GOOGLE_CLIENT_SECRET' => 'YOUR_GOOGLE_CLIENT_SECRET',
    'GOOGLE_REDIRECT_URI' => 'https://yourdomain.com/auth/google-callback.php',
    
    // =============================================================================
    // STRIPE PAYMENT CONFIGURATION
    // =============================================================================
    
    // Stripe Test Keys (for development)
    'STRIPE_PUBLISHABLE_KEY' => 'REPLACE_WITH_YOUR_STRIPE_PUBLISHABLE_KEY', // Current: pk_test_...
    'STRIPE_SECRET_KEY' => 'REPLACE_WITH_YOUR_STRIPE_SECRET_KEY', // Current: sk_test_...
    'STRIPE_WEBHOOK_SECRET' => 'REPLACE_WITH_YOUR_STRIPE_WEBHOOK_SECRET', // Current: we_1RrlzeLsoDV5QV3U99d8AZNt
    
    // Stripe Live Keys (uncomment for production)
    // 'STRIPE_PUBLISHABLE_KEY' => 'YOUR_STRIPE_LIVE_PUBLISHABLE_KEY',
    // 'STRIPE_SECRET_KEY' => 'YOUR_STRIPE_LIVE_SECRET_KEY',
    
    // Stripe Configuration
    'STRIPE_CURRENCY' => 'eur',
    'STRIPE_SUCCESS_URL' => 'https://123cashcontrol.com/payment/success.php',
    'STRIPE_CANCEL_URL' => 'https://123cashcontrol.com/upgrade.php',
    
    // =============================================================================
    // EMAIL CONFIGURATION (Plesk SMTP)
    // =============================================================================
    
    // SMTP Server Settings
    'SMTP_HOST' => 'YOUR_SMTP_HOST',
    'SMTP_PORT' => 587,
    'SMTP_SECURITY' => 'tls', // or 'ssl'
    'SMTP_USERNAME' => 'YOUR_SMTP_USERNAME',
    'SMTP_PASSWORD' => 'YOUR_SMTP_PASSWORD',
    
    // Email Sender Information
    'FROM_EMAIL' => 'noreply@yourdomain.com',
    'FROM_NAME' => 'YourAppName',
    'REPLY_TO_EMAIL' => 'noreply@yourdomain.com',
    'SUPPORT_EMAIL' => 'support@yourdomain.com',
    
    // =============================================================================
    // TRUELAYER BANK INTEGRATION CONFIGURATION
    // =============================================================================
    
    // TrueLayer API Credentials (from TrueLayer Console)
    'TRUELAYER_CLIENT_ID' => 'YOUR_TRUELAYER_CLIENT_ID',
    'TRUELAYER_CLIENT_SECRET' => 'YOUR_TRUELAYER_CLIENT_SECRET',
    'TRUELAYER_ENVIRONMENT' => 'sandbox', // 'sandbox' for testing, 'live' for production
    'TRUELAYER_REDIRECT_URI' => 'https://yourdomain.com/bank/callback.php',
    
    // =============================================================================
    // APPLICATION CONFIGURATION
    // =============================================================================
    
    // Application Settings
    'APP_NAME' => 'YourAppName',
    'APP_URL' => 'https://yourdomain.com',
    'APP_ENV' => 'production', // 'development', 'staging', or 'production'
    'APP_DEBUG' => false, // Set to true for development
    
    // Security Settings
    'SESSION_LIFETIME' => 7200, // 2 hours in seconds
    'CSRF_TOKEN_NAME' => 'csrf_token',
    'ENCRYPTION_KEY' => 'REPLACE_WITH_32_CHAR_RANDOM_STRING', // Generate a random 32-character string
    
    // =============================================================================
    // OPTIONAL THIRD-PARTY SERVICES
    // =============================================================================
    
    // Analytics (if needed)
    // 'GOOGLE_ANALYTICS_ID' => 'GA-XXXXXXXXX',
    
    // Error Tracking (if needed)
    // 'SENTRY_DSN' => 'https://your-sentry-dsn@sentry.io/project-id',
    
    // File Upload Settings
    'MAX_UPLOAD_SIZE' => '10M',
    'ALLOWED_FILE_TYPES' => ['jpg', 'jpeg', 'png', 'pdf', 'csv'],
    
    // Rate Limiting
    'RATE_LIMIT_REQUESTS' => 100, // requests per hour per IP
    'RATE_LIMIT_WINDOW' => 3600, // 1 hour in seconds
];
