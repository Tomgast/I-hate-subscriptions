<?php
/**
 * Analyze Current Subscription Detection Results
 * Show what's currently detected and classify true vs false positives
 */

require_once 'includes/database_helper.php';

$userId = 2; // Your user ID

try {
    $pdo = DatabaseHelper::getConnection();
    
    echo "=== CURRENT SUBSCRIPTION ANALYSIS ===\n\n";
    
    // Get all current subscriptions
    $stmt = $pdo->prepare("
        SELECT merchant_name, amount, currency, billing_cycle, 
               last_charge_date, confidence, created_at 
        FROM subscriptions 
        WHERE user_id = ? 
        ORDER BY amount DESC
    ");
    $stmt->execute([$userId]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total detected subscriptions: " . count($subscriptions) . "\n\n";
    
    // Define merchant categories for analysis
    $knownSubscriptionKeywords = [
        'spotify', 'netflix', 'disney', 'amazon prime', 'adobe', 'microsoft', 
        'apple music', 'youtube', 'hulu', 'twitch', 'playstation', 'xbox',
        'office 365', 'dropbox', 'google', 'icloud', 'onedrive', 'steam',
        'jagex', 'games studio', 'subscription', 'premium'
    ];
    
    $nonSubscriptionKeywords = [
        'albert heijn', 'jumbo', 'lidl', 'aldi', 'supermarket', 'ah ',
        'postnl', 'dhl', 'ups', 'fedex', 'post nl',
        'takeaway', 'uber eats', 'deliveroo', 'thuisbezorgd',
        'restaurant', 'cafe', 'bakkerij', 'bakery', 'eetcafe',
        'tankstation', 'shell', 'bp', 'esso', 'gas station',
        'parking', 'parkeren', 'gemeente', 'belasting'
    ];
    
    $trueSubscriptions = [];
    $falsePositives = [];
    $uncertain = [];
    
    foreach ($subscriptions as $sub) {
        $merchant = strtolower($sub['merchant_name']);
        $amount = $sub['amount'];
        $cycle = $sub['billing_cycle'];
        $confidence = $sub['confidence'];
        
        // Check if it's a known subscription service
        $isKnownSubscription = false;
        foreach ($knownSubscriptionKeywords as $keyword) {
            if (strpos($merchant, $keyword) !== false) {
                $isKnownSubscription = true;
                break;
            }
        }
        
        // Check if it's a known non-subscription service
        $isKnownNonSubscription = false;
        foreach ($nonSubscriptionKeywords as $keyword) {
            if (strpos($merchant, $keyword) !== false) {
                $isKnownNonSubscription = true;
                break;
            }
        }
        
        // Classify the subscription
        if ($isKnownSubscription && $cycle && $cycle !== 'unknown' && $amount >= 2) {
            $trueSubscriptions[] = $sub;
            $category = "TRUE SUBSCRIPTION";
        } elseif ($isKnownNonSubscription) {
            $falsePositives[] = $sub;
            $category = "FALSE POSITIVE";
        } elseif (!$cycle || $cycle === 'unknown') {
            $falsePositives[] = $sub;
            $category = "FALSE POSITIVE (No Cycle)";
        } elseif ($amount < 2) {
            $falsePositives[] = $sub;
            $category = "FALSE POSITIVE (Too Small)";
        } else {
            $uncertain[] = $sub;
            $category = "UNCERTAIN";
        }
        
        echo sprintf("%-40s | €%8.2f | %-12s | %s\n", 
            substr($sub['merchant_name'], 0, 39),
            $amount,
            $cycle ?: 'None',
            $category
        );
    }
    
    echo "\n=== SUMMARY ===\n";
    echo "True Subscriptions: " . count($trueSubscriptions) . "\n";
    echo "False Positives: " . count($falsePositives) . "\n";
    echo "Uncertain: " . count($uncertain) . "\n";
    echo "Accuracy: " . round((count($trueSubscriptions) / count($subscriptions)) * 100, 1) . "%\n\n";
    
    echo "=== FALSE POSITIVES (Should be removed) ===\n";
    foreach ($falsePositives as $fp) {
        $reason = '';
        $merchant = strtolower($fp['merchant_name']);
        
        if (strpos($merchant, 'albert heijn') !== false || strpos($merchant, 'ah ') !== false) {
            $reason = 'Grocery store';
        } elseif (strpos($merchant, 'postnl') !== false) {
            $reason = 'Shipping service';
        } elseif (strpos($merchant, 'bakkerij') !== false) {
            $reason = 'Bakery';
        } elseif (empty($fp['billing_cycle']) || $fp['billing_cycle'] === 'unknown') {
            $reason = 'No billing cycle detected';
        } elseif ($fp['amount'] < 2) {
            $reason = 'Amount too small';
        } else {
            $reason = 'Non-subscription merchant';
        }
        
        echo sprintf("- %-35s | €%6.2f | %s\n", 
            $fp['merchant_name'], 
            $fp['amount'], 
            $reason
        );
    }
    
    echo "\n=== TRUE SUBSCRIPTIONS (Keep these) ===\n";
    foreach ($trueSubscriptions as $ts) {
        echo sprintf("- %-35s | €%6.2f | %s\n", 
            $ts['merchant_name'], 
            $ts['amount'], 
            $ts['billing_cycle']
        );
    }
    
    echo "\n=== UNCERTAIN (Need manual review) ===\n";
    foreach ($uncertain as $uc) {
        echo sprintf("- %-35s | €%6.2f | %s\n", 
            $uc['merchant_name'], 
            $uc['amount'], 
            $uc['billing_cycle'] ?: 'None'
        );
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
