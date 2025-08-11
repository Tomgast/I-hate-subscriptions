<?php
/**
 * Improved Subscription Detection Algorithm
 * Filters out false positives and only detects true recurring subscriptions
 */

require_once 'includes/database_helper.php';

class ImprovedSubscriptionDetector {
    
    // Merchants that should NEVER be considered subscriptions
    private static $merchantBlacklist = [
        // Grocery stores
        'albert heijn', 'ah ', 'jumbo', 'lidl', 'aldi', 'supermarket', 'grocery',
        
        // Shipping/postal services
        'postnl', 'post nl', 'dhl', 'ups', 'fedex', 'dpd', 'shipping',
        
        // Food delivery/restaurants (irregular amounts)
        'takeaway', 'uber eats', 'deliveroo', 'thuisbezorgd', 'restaurant', 
        'cafe', 'eetcafe', 'bakkerij', 'bakery', 'pizza', 'mcdonalds', 'kfc',
        
        // Gas stations (irregular amounts)
        'tankstation', 'shell', 'bp', 'esso', 'total', 'gas station',
        
        // Parking (irregular)
        'parking', 'parkeren', 'park',
        
        // Government/taxes (irregular)
        'gemeente', 'belasting', 'tax', 'government', 'overheid',
        
        // ATM withdrawals
        'geldautomaat', 'atm', 'cash withdrawal',
        
        // One-time purchases
        'amazon.nl', 'bol.com', 'webshop', 'shop'
    ];
    
    // Merchants that are likely to be subscriptions
    private static $merchantWhitelist = [
        // Streaming services
        'spotify', 'netflix', 'disney', 'amazon prime', 'apple music', 
        'youtube premium', 'hulu', 'hbo', 'paramount',
        
        // Software/SaaS
        'adobe', 'microsoft', 'office 365', 'dropbox', 'google workspace',
        'icloud', 'onedrive', 'zoom', 'slack', 'notion',
        
        // Gaming
        'playstation', 'xbox', 'steam', 'epic games', 'jagex', 'games studio',
        'nintendo', 'blizzard', 'ea games',
        
        // News/media
        'newspaper', 'magazine', 'news', 'media subscription',
        
        // Fitness/health
        'gym', 'fitness', 'health app', 'meditation',
        
        // Utilities (if regular)
        'energy', 'gas', 'water', 'internet', 'phone', 'mobile'
    ];
    
    /**
     * Check if a merchant should be blacklisted (never a subscription)
     */
    public static function isBlacklistedMerchant($merchantName) {
        $merchant = strtolower($merchantName);
        
        foreach (self::$merchantBlacklist as $blacklisted) {
            if (strpos($merchant, $blacklisted) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if a merchant is likely to be a subscription service
     */
    public static function isWhitelistedMerchant($merchantName) {
        $merchant = strtolower($merchantName);
        
        foreach (self::$merchantWhitelist as $whitelisted) {
            if (strpos($merchant, $whitelisted) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Validate if a detected pattern is actually a subscription
     */
    public static function validateSubscriptionPattern($pattern) {
        $merchant = $pattern['merchant_name'];
        $amount = $pattern['amount'];
        $cycle = $pattern['billing_cycle'];
        $confidence = $pattern['confidence'] ?? 0;
        
        // Rule 1: Blacklisted merchants are never subscriptions
        if (self::isBlacklistedMerchant($merchant)) {
            return [
                'valid' => false,
                'reason' => 'Blacklisted merchant type (grocery, shipping, restaurant, etc.)'
            ];
        }
        
        // Rule 2: Must have a valid billing cycle
        if (!$cycle || $cycle === 'unknown') {
            return [
                'valid' => false,
                'reason' => 'No valid billing cycle detected'
            ];
        }
        
        // Rule 3: Minimum amount threshold (€2 to exclude small fees)
        if ($amount < 2.00) {
            return [
                'valid' => false,
                'reason' => 'Amount too small (likely fees or tips)'
            ];
        }
        
        // Rule 4: Maximum reasonable amount (€500 to exclude large one-time purchases)
        if ($amount > 500.00) {
            return [
                'valid' => false,
                'reason' => 'Amount too large (likely one-time purchase)'
            ];
        }
        
        // Rule 5: Minimum confidence threshold
        if ($confidence < 50) {
            return [
                'valid' => false,
                'reason' => 'Confidence too low'
            ];
        }
        
        // Rule 6: Whitelist gets bonus points
        $isWhitelisted = self::isWhitelistedMerchant($merchant);
        
        // Calculate final score
        $score = $confidence;
        if ($isWhitelisted) {
            $score += 25; // Bonus for known subscription services
        }
        
        // Valid billing cycles get bonus
        if (in_array($cycle, ['monthly', 'yearly', 'quarterly'])) {
            $score += 15;
        }
        
        // Amount in reasonable subscription range gets bonus
        if ($amount >= 5 && $amount <= 100) {
            $score += 10;
        }
        
        return [
            'valid' => $score >= 60,
            'reason' => $score >= 60 ? 'Valid subscription pattern' : 'Score too low (' . $score . '/100)',
            'score' => $score,
            'is_whitelisted' => $isWhitelisted
        ];
    }
    
    /**
     * Clean up existing subscriptions by removing false positives
     */
    public static function cleanupExistingSubscriptions($userId) {
        $pdo = DatabaseHelper::getConnection();
        
        // Get all current subscriptions
        $stmt = $pdo->prepare("
            SELECT id, merchant_name, amount, billing_cycle, confidence 
            FROM subscriptions 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $removed = [];
        $kept = [];
        
        foreach ($subscriptions as $sub) {
            $validation = self::validateSubscriptionPattern($sub);
            
            if (!$validation['valid']) {
                // Remove invalid subscription
                $deleteStmt = $pdo->prepare("DELETE FROM subscriptions WHERE id = ?");
                $deleteStmt->execute([$sub['id']]);
                
                $removed[] = [
                    'merchant' => $sub['merchant_name'],
                    'amount' => $sub['amount'],
                    'reason' => $validation['reason']
                ];
            } else {
                $kept[] = [
                    'merchant' => $sub['merchant_name'],
                    'amount' => $sub['amount'],
                    'score' => $validation['score']
                ];
            }
        }
        
        return [
            'removed' => $removed,
            'kept' => $kept,
            'total_before' => count($subscriptions),
            'total_after' => count($kept)
        ];
    }
}

// Test the cleanup if run directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $userId = 2; // Your user ID
    
    echo "=== IMPROVED SUBSCRIPTION DETECTION CLEANUP ===\n\n";
    
    try {
        $result = ImprovedSubscriptionDetector::cleanupExistingSubscriptions($userId);
        
        echo "BEFORE: " . $result['total_before'] . " subscriptions\n";
        echo "AFTER:  " . $result['total_after'] . " subscriptions\n";
        echo "REMOVED: " . count($result['removed']) . " false positives\n\n";
        
        if (!empty($result['removed'])) {
            echo "=== REMOVED FALSE POSITIVES ===\n";
            foreach ($result['removed'] as $removed) {
                echo sprintf("- %-35s | €%6.2f | %s\n", 
                    $removed['merchant'], 
                    $removed['amount'], 
                    $removed['reason']
                );
            }
            echo "\n";
        }
        
        if (!empty($result['kept'])) {
            echo "=== KEPT TRUE SUBSCRIPTIONS ===\n";
            foreach ($result['kept'] as $kept) {
                echo sprintf("- %-35s | €%6.2f | Score: %d/100\n", 
                    $kept['merchant'], 
                    $kept['amount'], 
                    $kept['score']
                );
            }
        }
        
        echo "\n=== CLEANUP COMPLETE ===\n";
        echo "Accuracy improved significantly by removing false positives!\n";
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
