<?php
/**
 * Dashboard Enrichment Service
 * Automatically enriches dashboard data during bank scans
 */

class DashboardEnrichmentService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Main enrichment method called after subscription scan
     */
    public function enrichDashboardData($userId, $scanId) {
        try {
            error_log("DashboardEnrichment: Starting enrichment for user $userId, scan $scanId");
            
            // 1. Auto-categorize subscriptions
            $this->autoCategorizeSubscriptions($userId);
            
            // 2. Detect price changes and create history
            $this->detectPriceChanges($userId);
            
            // 3. Detect anomalies in subscription charges
            $this->detectSubscriptionAnomalies($userId);
            
            // 4. Update subscription metadata
            $this->updateSubscriptionMetadata($userId);
            
            error_log("DashboardEnrichment: Completed enrichment for user $userId");
            
            return ['success' => true, 'message' => 'Dashboard data enriched successfully'];
            
        } catch (Exception $e) {
            error_log("DashboardEnrichment: Error enriching data for user $userId: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Auto-categorize subscriptions based on merchant names
     */
    private function autoCategorizeSubscriptions($userId) {
        $categories = [
            'Streaming' => ['netflix', 'disney', 'hbo', 'prime video', 'hulu', 'paramount', 'peacock', 'apple tv'],
            'Music' => ['spotify', 'apple music', 'youtube music', 'amazon music', 'tidal', 'deezer'],
            'Software' => ['adobe', 'microsoft', 'google workspace', 'dropbox', 'zoom', 'slack', 'notion'],
            'Fitness' => ['gym', 'fitness', 'sport', 'peloton', 'nike', 'strava', 'myfitnesspal'],
            'News' => ['news', 'times', 'guardian', 'post', 'journal', 'magazine', 'economist'],
            'Gaming' => ['steam', 'xbox', 'playstation', 'nintendo', 'epic games', 'ubisoft'],
            'Shopping' => ['amazon', 'ebay', 'walmart', 'target', 'costco'],
            'Telecom' => ['verizon', 'att', 't-mobile', 'kpn', 'vodafone', 'telekom'],
            'Insurance' => ['insurance', 'verzekering', 'allstate', 'geico', 'progressive', 'centraal beheer', 'zilveren kruis'],
            'Education' => ['coursera', 'udemy', 'skillshare', 'masterclass', 'duo', 'university'],
            'Finance' => ['bank', 'credit', 'loan', 'investment', 'trading'],
            'Utilities' => ['electric', 'gas', 'water', 'internet', 'phone', 'energy']
        ];
        
        foreach ($categories as $category => $keywords) {
            $conditions = [];
            $params = [$category, $userId];
            
            foreach ($keywords as $keyword) {
                $conditions[] = "LOWER(name) LIKE ?";
                $params[] = "%$keyword%";
            }
            
            $sql = "UPDATE subscriptions SET category = ? 
                    WHERE user_id = ? AND (category IS NULL OR category = 'Other' OR category = '') 
                    AND (" . implode(' OR ', $conditions) . ")";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $updated = $stmt->rowCount();
            if ($updated > 0) {
                error_log("DashboardEnrichment: Categorized $updated subscriptions as '$category'");
            }
        }
    }
    
    /**
     * Detect price changes by comparing current subscriptions with raw transactions
     */
    private function detectPriceChanges($userId) {
        // Get current subscriptions
        $stmt = $this->pdo->prepare("
            SELECT id, name, cost, billing_cycle, last_charge_date
            FROM subscriptions 
            WHERE user_id = ? AND is_active = 1
        ");
        $stmt->execute([$userId]);
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($subscriptions as $subscription) {
            // Find recent transactions for this subscription
            $stmt = $this->pdo->prepare("
                SELECT amount, date
                FROM raw_transactions 
                WHERE user_id = ? 
                AND merchant_name LIKE ? 
                AND amount < 0 
                AND date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                ORDER BY date DESC
                LIMIT 5
            ");
            $stmt->execute([$userId, '%' . $subscription['name'] . '%']);
            $recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($recentTransactions) >= 2) {
                $currentCost = $subscription['cost'];
                $latestTransactionAmount = abs($recentTransactions[0]['amount']);
                
                // Check if there's a significant price difference (more than €0.50)
                if (abs($currentCost - $latestTransactionAmount) > 0.50) {
                    // Check if this price change is already recorded
                    $stmt = $this->pdo->prepare("
                        SELECT COUNT(*) FROM price_history 
                        WHERE subscription_id = ? AND new_cost = ? AND old_cost = ?
                    ");
                    $stmt->execute([$subscription['id'], $latestTransactionAmount, $currentCost]);
                    
                    if ($stmt->fetchColumn() == 0) {
                        // Record the price change
                        $stmt = $this->pdo->prepare("
                            INSERT INTO price_history (subscription_id, user_id, old_cost, new_cost, change_reason)
                            VALUES (?, ?, ?, ?, 'Detected from transaction data')
                        ");
                        $stmt->execute([
                            $subscription['id'], 
                            $userId, 
                            $currentCost, 
                            $latestTransactionAmount
                        ]);
                        
                        // Update the subscription cost
                        $stmt = $this->pdo->prepare("
                            UPDATE subscriptions 
                            SET cost = ?, price_change_count = price_change_count + 1, last_price_check = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([$latestTransactionAmount, $subscription['id']]);
                        
                        error_log("DashboardEnrichment: Detected price change for '{$subscription['name']}': €$currentCost → €$latestTransactionAmount");
                    }
                }
            }
        }
    }
    
    /**
     * Detect anomalies in subscription charges
     */
    private function detectSubscriptionAnomalies($userId) {
        // Get subscriptions with their expected costs
        $stmt = $this->pdo->prepare("
            SELECT id, name, cost, billing_cycle
            FROM subscriptions 
            WHERE user_id = ? AND is_active = 1
        ");
        $stmt->execute([$userId]);
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($subscriptions as $subscription) {
            // Find recent transactions that might be anomalies
            $stmt = $this->pdo->prepare("
                SELECT amount, date, merchant_name
                FROM raw_transactions 
                WHERE user_id = ? 
                AND merchant_name LIKE ? 
                AND amount < 0 
                AND date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY date DESC
            ");
            $stmt->execute([$userId, '%' . $subscription['name'] . '%']);
            $recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($recentTransactions as $transaction) {
                $actualAmount = abs($transaction['amount']);
                $expectedAmount = $subscription['cost'];
                $difference = abs($actualAmount - $expectedAmount);
                
                // Flag as anomaly if difference is more than 10% or €2
                if ($difference > max($expectedAmount * 0.1, 2.0)) {
                    // Check if this anomaly is already recorded
                    $stmt = $this->pdo->prepare("
                        SELECT COUNT(*) FROM subscription_anomalies 
                        WHERE user_id = ? AND merchant_name = ? AND transaction_date = ? AND actual_amount = ?
                    ");
                    $stmt->execute([$userId, $transaction['merchant_name'], $transaction['date'], $actualAmount]);
                    
                    if ($stmt->fetchColumn() == 0) {
                        // Determine severity
                        $severity = 'low';
                        if ($difference > $expectedAmount * 0.5) {
                            $severity = 'high';
                        } elseif ($difference > $expectedAmount * 0.2) {
                            $severity = 'medium';
                        }
                        
                        // Record the anomaly
                        $stmt = $this->pdo->prepare("
                            INSERT INTO subscription_anomalies 
                            (user_id, subscription_id, merchant_name, expected_amount, actual_amount, transaction_date, anomaly_type, severity)
                            VALUES (?, ?, ?, ?, ?, ?, 'unexpected_charge', ?)
                        ");
                        $stmt->execute([
                            $userId,
                            $subscription['id'],
                            $transaction['merchant_name'],
                            $expectedAmount,
                            $actualAmount,
                            $transaction['date'],
                            $severity
                        ]);
                        
                        error_log("DashboardEnrichment: Detected anomaly for '{$subscription['name']}': Expected €$expectedAmount, Actual €$actualAmount ($severity severity)");
                    }
                }
            }
        }
    }
    
    /**
     * Update subscription metadata for enhanced features
     */
    private function updateSubscriptionMetadata($userId) {
        // Update last_price_check for all active subscriptions
        $stmt = $this->pdo->prepare("
            UPDATE subscriptions 
            SET last_price_check = NOW() 
            WHERE user_id = ? AND is_active = 1
        ");
        $stmt->execute([$userId]);
        
        error_log("DashboardEnrichment: Updated metadata for user $userId subscriptions");
    }
    
    /**
     * Get enrichment statistics for logging
     */
    public function getEnrichmentStats($userId) {
        $stats = [];
        
        // Price changes in last 30 days
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM price_history 
            WHERE user_id = ? AND change_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$userId]);
        $stats['recent_price_changes'] = $stmt->fetchColumn();
        
        // Active anomalies
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM subscription_anomalies 
            WHERE user_id = ? AND status = 'new'
        ");
        $stmt->execute([$userId]);
        $stats['active_anomalies'] = $stmt->fetchColumn();
        
        // Categorized subscriptions
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM subscriptions 
            WHERE user_id = ? AND is_active = 1 AND category IS NOT NULL AND category != 'Other'
        ");
        $stmt->execute([$userId]);
        $stats['categorized_subscriptions'] = $stmt->fetchColumn();
        
        return $stats;
    }
}
?>
