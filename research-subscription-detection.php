<?php
/**
 * Subscription Detection Research & Analysis
 * Research best practices and analyze current detection results
 */

session_start();
require_once 'includes/database_helper.php';

echo "<h1>Subscription Detection Research & Analysis</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .error { color: red; }
    .success { color: green; }
    .info { color: blue; }
    .warning { color: orange; }
    .subscription { color: green; font-weight: bold; }
    .not-subscription { color: red; }
    .maybe { color: orange; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .highlight { background-color: #fff3cd; padding: 10px; border-radius: 5px; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style>";

$userId = $_GET['user_id'] ?? 2;

try {
    $pdo = DatabaseHelper::getConnection();
    
    echo "<div class='section'>";
    echo "<h2>1. Subscription Detection Best Practices Research</h2>";
    echo "<div class='highlight'>";
    echo "<h3>🔬 What Makes a True Subscription?</h3>";
    echo "<p><strong>Industry Best Practices for Subscription Detection:</strong></p>";
    echo "<ol>";
    echo "<li><strong>Regularity:</strong> Payments occur at predictable intervals (monthly, yearly, weekly)</li>";
    echo "<li><strong>Consistency:</strong> Same merchant, same amount (or very similar)</li>";
    echo "<li><strong>Frequency:</strong> At least 2-3 occurrences to establish pattern</li>";
    echo "<li><strong>Merchant Type:</strong> Known subscription-based businesses</li>";
    echo "<li><strong>Amount Stability:</strong> Amount doesn't vary wildly between payments</li>";
    echo "<li><strong>Time Intervals:</strong> Consistent gaps between payments (28-32 days for monthly)</li>";
    echo "</ol>";
    
    echo "<h3>🚫 What Should NOT Be Considered Subscriptions:</h3>";
    echo "<ul>";
    echo "<li><strong>Grocery Stores:</strong> Albert Heijn, supermarkets (irregular amounts/timing)</li>";
    echo "<li><strong>Restaurants/Food:</strong> Takeaway, individual meal purchases</li>";
    echo "<li><strong>Shipping:</strong> PostNL, DHL (one-time shipping costs)</li>";
    echo "<li><strong>Irregular Payments:</strong> Varying amounts to same merchant</li>";
    echo "<li><strong>Government/Utilities:</strong> Unless clearly monthly bills</li>";
    echo "<li><strong>One-time Purchases:</strong> Single transactions or irregular patterns</li>";
    echo "</ul>";
    
    echo "<h3>✅ True Subscription Examples:</h3>";
    echo "<ul>";
    echo "<li><strong>Streaming:</strong> Netflix, Spotify, Disney+ (monthly/yearly)</li>";
    echo "<li><strong>Software:</strong> Adobe, Microsoft Office, SaaS tools</li>";
    echo "<li><strong>Gaming:</strong> PlayStation Plus, Xbox Live (if regular)</li>";
    echo "<li><strong>News/Media:</strong> Newspaper subscriptions, magazines</li>";
    echo "<li><strong>Fitness:</strong> Gym memberships (if regular monthly)</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>2. Analysis of Current 36 Detected 'Subscriptions'</h2>";
    
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
    
    echo "<h3>Current Detection Results:</h3>";
    echo "<table>";
    echo "<tr><th>Merchant</th><th>Amount</th><th>Cycle</th><th>Last Charge</th><th>Confidence</th><th>Assessment</th></tr>";
    
    // Define subscription indicators
    $knownSubscriptionMerchants = [
        'spotify', 'netflix', 'disney', 'amazon prime', 'adobe', 'microsoft', 
        'apple music', 'youtube', 'hulu', 'twitch', 'playstation', 'xbox',
        'office 365', 'dropbox', 'google', 'icloud', 'onedrive'
    ];
    
    $nonSubscriptionMerchants = [
        'albert heijn', 'jumbo', 'lidl', 'aldi', 'supermarket',
        'postnl', 'dhl', 'ups', 'fedex',
        'takeaway', 'uber eats', 'deliveroo', 'thuisbezorgd',
        'restaurant', 'cafe', 'bakkerij', 'bakery'
    ];
    
    $trueSubscriptions = [];
    $falsePositives = [];
    $uncertain = [];
    
    foreach ($subscriptions as $sub) {
        $merchant = strtolower($sub['merchant_name']);
        $amount = $sub['amount'];
        $cycle = $sub['billing_cycle'];
        
        // Assessment logic
        $isKnownSubscription = false;
        $isKnownNonSubscription = false;
        
        foreach ($knownSubscriptionMerchants as $known) {
            if (strpos($merchant, $known) !== false) {
                $isKnownSubscription = true;
                break;
            }
        }
        
        foreach ($nonSubscriptionMerchants as $nonSub) {
            if (strpos($merchant, $nonSub) !== false) {
                $isKnownNonSubscription = true;
                break;
            }
        }
        
        // Determine assessment
        $assessment = '';
        $class = '';
        
        if ($isKnownSubscription && $cycle && $amount > 0) {
            $assessment = '✅ Likely Subscription';
            $class = 'subscription';
            $trueSubscriptions[] = $sub;
        } elseif ($isKnownNonSubscription) {
            $assessment = '❌ Not Subscription';
            $class = 'not-subscription';
            $falsePositives[] = $sub;
        } elseif (!$cycle || empty($cycle)) {
            $assessment = '❌ No Billing Cycle';
            $class = 'not-subscription';
            $falsePositives[] = $sub;
        } elseif ($amount < 2) {
            $assessment = '❌ Too Small Amount';
            $class = 'not-subscription';
            $falsePositives[] = $sub;
        } else {
            $assessment = '⚠️ Needs Review';
            $class = 'maybe';
            $uncertain[] = $sub;
        }
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($sub['merchant_name']) . "</td>";
        echo "<td>€" . number_format($amount, 2) . "</td>";
        echo "<td>" . htmlspecialchars($cycle ?: 'None') . "</td>";
        echo "<td>" . htmlspecialchars($sub['last_charge_date'] ?: 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($sub['confidence'] ?: 'N/A') . "</td>";
        echo "<td class='$class'>$assessment</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>3. Detection Results Summary</h2>";
    echo "<div class='highlight'>";
    echo "<h3>📊 Current Detection Analysis:</h3>";
    echo "<ul>";
    echo "<li><strong class='subscription'>True Subscriptions:</strong> " . count($trueSubscriptions) . "</li>";
    echo "<li><strong class='not-subscription'>False Positives:</strong> " . count($falsePositives) . "</li>";
    echo "<li><strong class='maybe'>Uncertain/Need Review:</strong> " . count($uncertain) . "</li>";
    echo "<li><strong>Total Detected:</strong> " . count($subscriptions) . "</li>";
    echo "</ul>";
    
    $accuracy = count($subscriptions) > 0 ? round((count($trueSubscriptions) / count($subscriptions)) * 100, 1) : 0;
    echo "<p><strong>Current Algorithm Accuracy:</strong> ~{$accuracy}% (only " . count($trueSubscriptions) . " out of " . count($subscriptions) . " are likely true subscriptions)</p>";
    echo "</div>";
    
    if (!empty($trueSubscriptions)) {
        echo "<h3>✅ Likely True Subscriptions:</h3>";
        echo "<table>";
        echo "<tr><th>Merchant</th><th>Amount</th><th>Cycle</th></tr>";
        foreach ($trueSubscriptions as $sub) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($sub['merchant_name']) . "</td>";
            echo "<td>€" . number_format($sub['amount'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($sub['billing_cycle']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    if (!empty($falsePositives)) {
        echo "<h3>❌ False Positives (Not Subscriptions):</h3>";
        echo "<table>";
        echo "<tr><th>Merchant</th><th>Amount</th><th>Reason</th></tr>";
        foreach ($falsePositives as $sub) {
            $reason = '';
            $merchant = strtolower($sub['merchant_name']);
            
            if (strpos($merchant, 'albert heijn') !== false) $reason = 'Grocery store';
            elseif (strpos($merchant, 'postnl') !== false) $reason = 'Shipping service';
            elseif (strpos($merchant, 'bakkerij') !== false) $reason = 'Bakery/food';
            elseif (empty($sub['billing_cycle'])) $reason = 'No billing cycle';
            elseif ($sub['amount'] < 2) $reason = 'Amount too small';
            else $reason = 'Non-subscription merchant';
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($sub['merchant_name']) . "</td>";
            echo "<td>€" . number_format($sub['amount'], 2) . "</td>";
            echo "<td>$reason</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>4. Improved Algorithm Recommendations</h2>";
    echo "<div class='highlight'>";
    echo "<h3>🎯 Proposed New Subscription Detection Rules:</h3>";
    echo "<ol>";
    echo "<li><strong>Merchant Filtering:</strong> Exclude known non-subscription merchants (grocery, shipping, restaurants)</li>";
    echo "<li><strong>Amount Threshold:</strong> Minimum €2-3 to exclude small incidental charges</li>";
    echo "<li><strong>Billing Cycle Required:</strong> Must have detected monthly/yearly pattern</li>";
    echo "<li><strong>Frequency Check:</strong> At least 2-3 transactions to establish pattern</li>";
    echo "<li><strong>Amount Consistency:</strong> Amounts should be identical or very close (±10%)</li>";
    echo "<li><strong>Time Interval Validation:</strong> Check if intervals match claimed billing cycle</li>";
    echo "<li><strong>Merchant Category:</strong> Prioritize known subscription-based industries</li>";
    echo "</ol>";
    
    echo "<h3>🔧 Implementation Strategy:</h3>";
    echo "<ol>";
    echo "<li><strong>Create Merchant Blacklist:</strong> Exclude grocery stores, shipping, restaurants</li>";
    echo "<li><strong>Create Merchant Whitelist:</strong> Prioritize streaming, software, SaaS services</li>";
    echo "<li><strong>Validate Transaction Patterns:</strong> Check raw transaction data for consistency</li>";
    echo "<li><strong>Confidence Scoring:</strong> Assign scores based on multiple criteria</li>";
    echo "<li><strong>User Verification:</strong> Allow users to confirm/deny detected subscriptions</li>";
    echo "</ol>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>5. Raw Transaction Pattern Analysis</h2>";
    echo "<p class='info'>Let's analyze some raw transaction patterns to validate our approach...</p>";
    
    // Analyze a few merchants from raw data
    $testMerchants = ['SpotifyAB', 'Albert Heijn 8760', 'JagexGamesStudio'];
    
    foreach ($testMerchants as $merchant) {
        echo "<h3>Pattern Analysis: $merchant</h3>";
        
        $stmt = $pdo->prepare("
            SELECT amount, booking_date, description 
            FROM raw_transactions 
            WHERE user_id = ? AND merchant_name LIKE ? 
            ORDER BY booking_date DESC 
            LIMIT 10
        ");
        $stmt->execute([$userId, "%$merchant%"]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($transactions)) {
            echo "<table>";
            echo "<tr><th>Date</th><th>Amount</th><th>Description</th></tr>";
            
            $amounts = [];
            $dates = [];
            
            foreach ($transactions as $trans) {
                $amount = abs($trans['amount']); // Remove negative sign
                $amounts[] = $amount;
                $dates[] = $trans['booking_date'];
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($trans['booking_date']) . "</td>";
                echo "<td>€" . number_format($amount, 2) . "</td>";
                echo "<td>" . htmlspecialchars(substr($trans['description'], 0, 50)) . "...</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Analyze pattern
            $uniqueAmounts = array_unique($amounts);
            $amountVariation = count($uniqueAmounts) == 1 ? 0 : (max($amounts) - min($amounts));
            
            echo "<p><strong>Pattern Analysis:</strong></p>";
            echo "<ul>";
            echo "<li>Unique amounts: " . count($uniqueAmounts) . "</li>";
            echo "<li>Amount variation: €" . number_format($amountVariation, 2) . "</li>";
            echo "<li>Transaction count: " . count($transactions) . "</li>";
            
            if (count($uniqueAmounts) == 1 && count($transactions) >= 2) {
                echo "<li class='subscription'>✅ Consistent amount pattern - likely subscription</li>";
            } elseif ($amountVariation > 5) {
                echo "<li class='not-subscription'>❌ High amount variation - likely not subscription</li>";
            } else {
                echo "<li class='maybe'>⚠️ Pattern unclear - needs review</li>";
            }
            echo "</ul>";
        } else {
            echo "<p class='warning'>No raw transaction data found for this merchant</p>";
        }
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<p><a href='?user_id=$userId&refresh=1'>🔄 Refresh Analysis</a></p>";
?>
