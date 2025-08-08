<?php
/**
 * STRIPE SDK INCLUDE
 * Include this file to use Stripe functionality
 */

// Include Stripe SDK (corrected path for Plesk hosting)
require_once __DIR__ . '/../stripe-php/init.php';

// Load Stripe configuration
require_once __DIR__ . '/../config/secure_loader.php';

// Set Stripe API key
$stripeSecretKey = getSecureConfig('STRIPE_SECRET_KEY');
if ($stripeSecretKey) {
    \Stripe\Stripe::setApiKey($stripeSecretKey);
} else {
    throw new Exception('STRIPE_SECRET_KEY not found in configuration');
}

// Stripe is now ready to use!
?>
