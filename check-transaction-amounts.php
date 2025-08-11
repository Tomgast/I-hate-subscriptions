<?php
/**
 * Check Raw Transaction Data Structure
 * Understand how amounts are stored (positive vs negative)
 */

require_once 'includes/database_helper.php';

$userId = 2;

try {
    $pdo = DatabaseHelper::getConnection();
    
    echo "=== CHECKING RAW TRANSACTION DATA STRUCTURE ===\n\n";
    
    // Get sample transactions to understand the data structure
    $stmt = $pdo->prepare("
        SELECT merchant_name, amount, booking_date, description
        FROM raw_transactions 
        WHERE user_id = ? 
        ORDER BY booking_date DESC
        LIMIT 20
    ");
    $stmt->execute([$userId]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Sample raw transactions:\n";
    echo "Merchant Name                    | Amount     | Date       | Type\n";
    echo "--------------------------------|------------|------------|----------\n";
    
    foreach ($transactions as $trans) {
        $amount = $trans['amount'];
        $type = $amount > 0 ? "INCOME" : "EXPENSE";
        
        echo sprintf("%-30s | %10.2f | %s | %s\n", 
            substr($trans['merchant_name'], 0, 29),
            $amount,
            $trans['booking_date'],
            $type
        );
    }
    
    // Check specifically for the merchants you mentioned
    echo "\n=== CHECKING SPECIFIC MERCHANTS ===\n";
    $checkMerchants = ['doedens', 'wiebe', 'gruijter', 'prim'];
    
    foreach ($checkMerchants as $searchTerm) {
        $stmt = $pdo->prepare("
            SELECT merchant_name, amount, booking_date
            FROM raw_transactions 
            WHERE user_id = ? AND LOWER(merchant_name) LIKE ?
            ORDER BY booking_date DESC
            LIMIT 5
        ");
        $stmt->execute([$userId, "%$searchTerm%"]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($results)) {
            echo "\nMerchants containing '$searchTerm':\n";
            foreach ($results as $result) {
                $type = $result['amount'] > 0 ? "INCOME (+)" : "EXPENSE (-)";
                echo sprintf("- %-30s | %8.2f | %s\n", 
                    $result['merchant_name'],
                    $result['amount'],
                    $type
                );
            }
        }
    }
    
    // Show current subscription detection logic issue
    echo "\n=== CURRENT ISSUE ANALYSIS ===\n";
    echo "The subscription detection should:\n";
    echo "✅ INCLUDE: Negative amounts (money going OUT = expenses/subscriptions)\n";
    echo "❌ EXCLUDE: Positive amounts (money coming IN = income/deposits)\n\n";
    
    echo "Current filter in code: if (\$recurringAmount <= 0) return null;\n";
    echo "This is WRONG - it excludes negative amounts (which are the subscriptions!)\n";
    echo "Should be: if (\$recurringAmount > 0) return null;\n\n";
    
    echo "🔧 NEED TO FIX: Change the filter to exclude positive amounts, not negative ones!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
