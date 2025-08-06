<?php
/**
 * Cron job to send email reminders
 * Run this daily via cron: 0 9 * * * /usr/bin/php /path/to/send_reminders.php
 */

require_once '../includes/email_notifications.php';

try {
    $emailNotifications = new EmailNotifications();
    $sentCount = $emailNotifications->sendRenewalReminders();
    
    echo "Email reminders sent: $sentCount\n";
    
    // Log the cron job execution
    error_log("CashControl: Sent $sentCount renewal reminder emails at " . date('Y-m-d H:i:s'));
    
} catch (Exception $e) {
    error_log("CashControl cron error: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
}
?>
