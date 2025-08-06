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
    
    // Stripe Payment Configuration (Test Environment)
    'STRIPE_PUBLISHABLE_KEY' => 'pk_test_51RrlZALvnN2VglsGvTVQQGbxuxQ1lqY0mm1S3nhvUyRKDMIy03t4A6D9yYOaiLzPOnnU53JnMy3Gnr9mOtRzdKJs00KfGWumn8',
    'STRIPE_SECRET_KEY' => 'sk_test_51RrlZALvnN2VglsGjadB3fyDsuYobiL4E5v0LHLHWskuQi5j4Paiglxb2w9IjlqLMTcjRWmxXwm28chUoPzV4CT400EJOx6Vtk',
    'STRIPE_WEBHOOK_SECRET' => 'we_1RrlzeLsoDV5QV3U99d8AZNt', // Webhook endpoint ID
    
    // Stripe Live Keys (for production deployment)
    // 'STRIPE_PUBLISHABLE_KEY' => 'pk_live_51RrlYaLsoDV5QV3UM9pWVwJMNPuMNk60ICLX9XG72U7dRpz24ZUYT1R6UnckRG3MTHrqNj3PBOxgsErNxmQM9l4s00MCYbV6Rj',
    // 'STRIPE_SECRET_KEY' => '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',
    
    // Email Configuration (if needed)
    'SMTP_HOST' => 'your-smtp-host',
    'SMTP_USERNAME' => 'your-smtp-username',
    'SMTP_PASSWORD' => 'your-smtp-password',
];
