<?php
/**
 * Email Notifications System for CashControl
 * Handles automated renewal reminders and notifications
 */

class EmailNotifications {
    private $emailService;
    private $db;
    
    public function __construct() {
        require_once 'includes/email_service.php';
        require_once 'config/db_config.php';
        $this->emailService = new EmailService();
        $this->db = getDBConnection();
    }
    
    /**
     * Send renewal reminders for upcoming subscriptions
     */
    public function sendRenewalReminders() {
        $pdo = $this->db->connect();
        
        // Get subscriptions due in the next 7 days (only for paid users)
        $stmt = $pdo->prepare("
            SELECT s.*, u.email, u.name, up.email_notifications, up.reminder_days
            FROM subscriptions s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN user_preferences up ON u.id = up.user_id
            WHERE s.is_active = 1 
            AND s.next_payment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            AND u.is_paid = 1
            AND (up.email_notifications IS NULL OR up.email_notifications = 1)
        ");
        $stmt->execute();
        $subscriptions = $stmt->fetchAll();
        
        $sentCount = 0;
        
        foreach ($subscriptions as $subscription) {
            $daysUntilRenewal = $this->getDaysUntilRenewal($subscription['next_payment_date']);
            $reminderDays = $subscription['reminder_days'] ? 
                json_decode($subscription['reminder_days']) : [1, 3, 7];
            
            // Check if we should send reminder for this number of days
            if (in_array($daysUntilRenewal, $reminderDays)) {
                // Check if we haven't already sent this reminder
                if (!$this->hasReminderBeenSent($subscription['id'], $daysUntilRenewal)) {
                    $sent = $this->emailService->sendRenewalReminder(
                        $subscription['email'],
                        $subscription['name'],
                        $subscription,
                        $daysUntilRenewal
                    );
                    
                    if ($sent) {
                        $this->logReminderSent($subscription['user_id'], $subscription['id'], $daysUntilRenewal);
                        $sentCount++;
                    }
                }
            }
        }
        
        return $sentCount;
    }
    
    /**
     * Send welcome email to new users
     */
    public function sendWelcomeEmail($userEmail, $userName) {
        return $this->emailService->sendWelcomeEmail($userEmail, $userName);
    }
    
    /**
     * Send upgrade confirmation email
     */
    public function sendUpgradeEmail($userEmail, $userName) {
        return $this->emailService->sendUpgradeEmail($userEmail, $userName);
    }
    
    /**
     * Send bank scan completion email
     */
    public function sendBankScanEmail($userEmail, $userName, $accountsConnected, $paymentsDetected) {
        return $this->emailService->sendBankScanEmail($userEmail, $userName, $accountsConnected, $paymentsDetected);
    }
    
    /**
     * Get days until renewal
     */
    private function getDaysUntilRenewal($nextPaymentDate) {
        $today = new DateTime();
        $renewalDate = new DateTime($nextPaymentDate);
        $diff = $today->diff($renewalDate);
        return $diff->days;
    }
    
    /**
     * Check if reminder has already been sent
     */
    private function hasReminderBeenSent($subscriptionId, $daysUntilRenewal) {
        $pdo = $this->db->connect();
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM notifications 
            WHERE subscription_id = ? 
            AND reminder_type = 'renewal' 
            AND days_before = ?
            AND sent_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
        ");
        $stmt->execute([$subscriptionId, $daysUntilRenewal]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Log that a reminder was sent
     */
    private function logReminderSent($userId, $subscriptionId, $daysUntilRenewal) {
        $pdo = $this->db->connect();
        
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, subscription_id, reminder_type, days_before, sent_at, status)
            VALUES (?, ?, 'renewal', ?, NOW(), 'sent')
        ");
        
        return $stmt->execute([$userId, $subscriptionId, $daysUntilRenewal]);
    }
    
    /**
     * Get notification statistics for a user
     */
    public function getNotificationStats($userId) {
        $pdo = $this->db->connect();
        
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_sent,
                COUNT(CASE WHEN sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as sent_last_30_days,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_count
            FROM notifications 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        
        return $stmt->fetch();
    }
    
    /**
     * Update user notification preferences
     */
    public function updateNotificationPreferences($userId, $preferences) {
        $pdo = $this->db->connect();
        
        $stmt = $pdo->prepare("
            INSERT INTO user_preferences (user_id, email_notifications, reminder_days)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
            email_notifications = VALUES(email_notifications),
            reminder_days = VALUES(reminder_days)
        ");
        
        return $stmt->execute([
            $userId,
            $preferences['email_notifications'] ? 1 : 0,
            json_encode($preferences['reminder_days'] ?? [1, 3, 7])
        ]);
    }
}
?>
