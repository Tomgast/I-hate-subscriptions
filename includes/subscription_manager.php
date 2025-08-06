<?php
// Subscription Management Class
require_once '../config/db_config.php';

class SubscriptionManager {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    // Get all subscriptions for a user
    public function getUserSubscriptions($userId, $activeOnly = true) {
        $sql = "SELECT s.*, c.icon as category_icon, c.color as category_color 
                FROM subscriptions s 
                LEFT JOIN categories c ON s.category = c.name 
                WHERE s.user_id = ?";
        
        if ($activeOnly) {
            $sql .= " AND s.is_active = 1";
        }
        
        $sql .= " ORDER BY s.next_payment_date ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    // Add new subscription
    public function addSubscription($userId, $data) {
        $sql = "INSERT INTO subscriptions (user_id, name, description, cost, currency, billing_cycle, 
                next_payment_date, category, website_url, logo_url) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $userId,
            $data['name'],
            $data['description'] ?? '',
            $data['cost'],
            $data['currency'] ?? 'EUR',
            $data['billing_cycle'] ?? 'monthly',
            $data['next_payment_date'],
            $data['category'] ?? 'Other',
            $data['website_url'] ?? '',
            $data['logo_url'] ?? ''
        ]);
    }
    
    // Update subscription
    public function updateSubscription($subscriptionId, $userId, $data) {
        $sql = "UPDATE subscriptions SET 
                name = ?, description = ?, cost = ?, currency = ?, billing_cycle = ?, 
                next_payment_date = ?, category = ?, website_url = ?, logo_url = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['description'] ?? '',
            $data['cost'],
            $data['currency'] ?? 'EUR',
            $data['billing_cycle'] ?? 'monthly',
            $data['next_payment_date'],
            $data['category'] ?? 'Other',
            $data['website_url'] ?? '',
            $data['logo_url'] ?? '',
            $subscriptionId,
            $userId
        ]);
    }
    
    // Delete subscription
    public function deleteSubscription($subscriptionId, $userId) {
        $sql = "DELETE FROM subscriptions WHERE id = ? AND user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$subscriptionId, $userId]);
    }
    
    // Toggle subscription active status
    public function toggleSubscription($subscriptionId, $userId) {
        $sql = "UPDATE subscriptions SET is_active = NOT is_active WHERE id = ? AND user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$subscriptionId, $userId]);
    }
    
    // Get subscription statistics
    public function getSubscriptionStats($userId) {
        // Total active subscriptions
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM subscriptions WHERE user_id = ? AND is_active = 1");
        $stmt->execute([$userId]);
        $totalActive = $stmt->fetch()['total'];
        
        // Monthly cost
        $stmt = $this->pdo->prepare("
            SELECT SUM(
                CASE 
                    WHEN billing_cycle = 'monthly' THEN cost
                    WHEN billing_cycle = 'yearly' THEN cost / 12
                    WHEN billing_cycle = 'weekly' THEN cost * 4.33
                    WHEN billing_cycle = 'daily' THEN cost * 30
                    ELSE cost
                END
            ) as monthly_total 
            FROM subscriptions 
            WHERE user_id = ? AND is_active = 1"
        );
        $stmt->execute([$userId]);
        $monthlyTotal = $stmt->fetch()['monthly_total'] ?? 0;
        
        // Yearly cost
        $yearlyTotal = $monthlyTotal * 12;
        
        // Next payment
        $stmt = $this->pdo->prepare("
            SELECT name, next_payment_date, cost, currency 
            FROM subscriptions 
            WHERE user_id = ? AND is_active = 1 
            ORDER BY next_payment_date ASC 
            LIMIT 1"
        );
        $stmt->execute([$userId]);
        $nextPayment = $stmt->fetch();
        
        // Category breakdown
        $stmt = $this->pdo->prepare("
            SELECT category, COUNT(*) as count, SUM(cost) as total_cost,
                   c.icon, c.color
            FROM subscriptions s
            LEFT JOIN categories c ON s.category = c.name
            WHERE s.user_id = ? AND s.is_active = 1
            GROUP BY category, c.icon, c.color
            ORDER BY total_cost DESC"
        );
        $stmt->execute([$userId]);
        $categoryBreakdown = $stmt->fetchAll();
        
        return [
            'total_active' => $totalActive,
            'monthly_total' => round($monthlyTotal, 2),
            'yearly_total' => round($yearlyTotal, 2),
            'next_payment' => $nextPayment,
            'category_breakdown' => $categoryBreakdown
        ];
    }
    
    // Get upcoming payments (next 30 days)
    public function getUpcomingPayments($userId, $days = 30) {
        $sql = "SELECT * FROM subscriptions 
                WHERE user_id = ? AND is_active = 1 
                AND next_payment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                ORDER BY next_payment_date ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $days]);
        return $stmt->fetchAll();
    }
    
    // Get all categories
    public function getCategories() {
        $stmt = $this->pdo->prepare("SELECT * FROM categories ORDER BY name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Calculate next payment date based on billing cycle
    public function calculateNextPaymentDate($currentDate, $billingCycle) {
        $date = new DateTime($currentDate);
        
        switch ($billingCycle) {
            case 'weekly':
                $date->add(new DateInterval('P7D'));
                break;
            case 'monthly':
                $date->add(new DateInterval('P1M'));
                break;
            case 'yearly':
                $date->add(new DateInterval('P1Y'));
                break;
            case 'daily':
                $date->add(new DateInterval('P1D'));
                break;
        }
        
        return $date->format('Y-m-d');
    }
    
    // Record payment
    public function recordPayment($subscriptionId, $amount, $paymentDate = null, $method = 'manual') {
        if (!$paymentDate) {
            $paymentDate = date('Y-m-d');
        }
        
        // Add payment to history
        $sql = "INSERT INTO payment_history (subscription_id, amount, payment_date, payment_method) 
                VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$subscriptionId, $amount, $paymentDate, $method]);
        
        // Update next payment date
        $stmt = $this->pdo->prepare("SELECT billing_cycle, next_payment_date FROM subscriptions WHERE id = ?");
        $stmt->execute([$subscriptionId]);
        $subscription = $stmt->fetch();
        
        if ($subscription) {
            $nextPaymentDate = $this->calculateNextPaymentDate($subscription['next_payment_date'], $subscription['billing_cycle']);
            
            $sql = "UPDATE subscriptions SET next_payment_date = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$nextPaymentDate, $subscriptionId]);
        }
        
        return true;
    }
}
?>
