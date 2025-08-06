<?php
/**
 * Email Configuration for CashControl
 * PHP Mailer with existing Plesk SMTP setup
 */

require_once 'database.php';

class EmailService {
    private $smtp_host = 'shared58.cloud86-host.nl';
    private $smtp_port = 587;
    private $smtp_user = 'info@123cashcontrol.com';
    private $smtp_password = 'Super-mannetje45'; // Use actual password from .env
    private $from_email = 'info@123cashcontrol.com';
    private $from_name = 'CashControl';
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Send email using PHP mail with SMTP
     */
    public function sendEmail($to, $subject, $htmlBody, $textBody = null) {
        try {
            // Create email headers
            $headers = [
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $this->from_name . ' <' . $this->from_email . '>',
                'Reply-To: ' . $this->from_email,
                'X-Mailer: PHP/' . phpversion()
            ];
            
            // Use PHP mail function (Plesk handles SMTP configuration)
            $success = mail($to, $subject, $htmlBody, implode("\r\n", $headers));
            
            if ($success) {
                error_log("Email sent successfully to: " . $to);
                return true;
            } else {
                error_log("Email failed to send to: " . $to);
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Email error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send welcome email to new user
     */
    public function sendWelcomeEmail($userEmail, $userName) {
        $subject = "Welcome to CashControl! 🎉";
        
        $htmlBody = $this->getWelcomeEmailTemplate($userName);
        
        return $this->sendEmail($userEmail, $subject, $htmlBody);
    }
    
    /**
     * Send subscription renewal reminder
     */
    public function sendRenewalReminder($userEmail, $userName, $subscription, $daysUntilRenewal) {
        $subject = "Reminder: {$subscription['name']} renews in {$daysUntilRenewal} days";
        
        $htmlBody = $this->getRenewalReminderTemplate($userName, $subscription, $daysUntilRenewal);
        
        $success = $this->sendEmail($userEmail, $subject, $htmlBody);
        
        // Log the reminder
        if ($success) {
            $this->logReminder($subscription['user_id'], $subscription['id'], 'renewal', 'sent');
        } else {
            $this->logReminder($subscription['user_id'], $subscription['id'], 'renewal', 'failed');
        }
        
        return $success;
    }
    
    /**
     * Send upgrade confirmation email
     */
    public function sendUpgradeEmail($userEmail, $userName) {
        $subject = "Welcome to CashControl Premium! 🚀";
        
        $htmlBody = $this->getUpgradeEmailTemplate($userName);
        
        return $this->sendEmail($userEmail, $subject, $htmlBody);
    }
    
    /**
     * Send bank scan completion email
     */
    public function sendBankScanEmail($userEmail, $userName, $foundSubscriptions) {
        $subject = "Bank Scan Complete - Found " . count($foundSubscriptions) . " subscriptions";
        
        $htmlBody = $this->getBankScanEmailTemplate($userName, $foundSubscriptions);
        
        return $this->sendEmail($userEmail, $subject, $htmlBody);
    }
    
    /**
     * Log email reminder
     */
    private function logReminder($userId, $subscriptionId, $type, $status, $errorMessage = null) {
        $logId = $this->generateUuid();
        
        $this->db->execute(
            "INSERT INTO reminder_logs (id, user_id, subscription_id, reminder_type, sent_at, email_status, error_message) 
             VALUES (?, ?, ?, ?, NOW(), ?, ?)",
            [$logId, $userId, $subscriptionId, $type, $status, $errorMessage]
        );
    }
    
    /**
     * Welcome email template
     */
    private function getWelcomeEmailTemplate($userName) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Welcome to CashControl</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: white; padding: 30px; border: 1px solid #e1e5e9; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; font-size: 14px; color: #666; }
                .button { display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
                .feature { margin: 15px 0; padding: 15px; background: #f8f9fa; border-radius: 6px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>💰 Welcome to CashControl!</h1>
                    <p>Your personal subscription tracker is ready</p>
                </div>
                
                <div class='content'>
                    <h2>Hi {$userName}! 👋</h2>
                    
                    <p>Welcome to CashControl! We're excited to help you take control of your subscriptions and save money.</p>
                    
                    <div class='feature'>
                        <h3>🎯 What you can do now:</h3>
                        <ul>
                            <li><strong>Track Subscriptions:</strong> Add all your monthly and yearly subscriptions</li>
                            <li><strong>Get Reminders:</strong> Never miss a renewal date again</li>
                            <li><strong>View Analytics:</strong> See where your money goes each month</li>
                            <li><strong>Export Data:</strong> Download your subscription data anytime</li>
                        </ul>
                    </div>
                    
                    <div class='feature'>
                        <h3>🚀 Premium Features (€29 one-time):</h3>
                        <ul>
                            <li><strong>Bank Integration:</strong> Automatically scan for subscriptions</li>
                            <li><strong>Smart Reminders:</strong> Customizable email notifications</li>
                            <li><strong>Advanced Analytics:</strong> Detailed spending insights</li>
                            <li><strong>Priority Support:</strong> Get help when you need it</li>
                        </ul>
                    </div>
                    
                    <div style='text-align: center;'>
                        <a href='https://123cashcontrol.com/dashboard' class='button'>Start Tracking Now</a>
                    </div>
                    
                    <p>If you have any questions, just reply to this email. We're here to help!</p>
                    
                    <p>Best regards,<br>The CashControl Team</p>
                </div>
                
                <div class='footer'>
                    <p>CashControl - Your Personal Subscription Tracker</p>
                    <p>Visit us at <a href='https://123cashcontrol.com'>123cashcontrol.com</a></p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Renewal reminder email template
     */
    private function getRenewalReminderTemplate($userName, $subscription, $daysUntilRenewal) {
        $cost = number_format($subscription['cost'], 2);
        $renewalDate = date('F j, Y', strtotime($subscription['next_billing_date']));
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Subscription Renewal Reminder</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: white; padding: 30px; border: 1px solid #e1e5e9; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; font-size: 14px; color: #666; }
                .subscription-card { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #f5576c; }
                .button { display: inline-block; background: #f5576c; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
                .urgent { color: #f5576c; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>⏰ Renewal Reminder</h1>
                    <p>Your subscription renews soon</p>
                </div>
                
                <div class='content'>
                    <h2>Hi {$userName}!</h2>
                    
                    <p>This is a friendly reminder that one of your subscriptions is coming up for renewal.</p>
                    
                    <div class='subscription-card'>
                        <h3>{$subscription['name']}</h3>
                        <p><strong>Cost:</strong> €{$cost} per {$subscription['billing_cycle']}</p>
                        <p><strong>Renewal Date:</strong> <span class='urgent'>{$renewalDate}</span></p>
                        <p><strong>Days Until Renewal:</strong> <span class='urgent'>{$daysUntilRenewal} days</span></p>
                    </div>
                    
                    <p><strong>What you can do:</strong></p>
                    <ul>
                        <li>Review if you still need this subscription</li>
                        <li>Cancel if you're not using it</li>
                        <li>Look for better alternatives</li>
                        <li>Update your budget planning</li>
                    </ul>
                    
                    <div style='text-align: center;'>
                        <a href='https://123cashcontrol.com/subscriptions' class='button'>Manage Subscriptions</a>
                    </div>
                    
                    <p>Want to stop these reminders? You can adjust your notification preferences in your account settings.</p>
                    
                    <p>Best regards,<br>The CashControl Team</p>
                </div>
                
                <div class='footer'>
                    <p>CashControl - Never miss a renewal again</p>
                    <p><a href='https://123cashcontrol.com/settings'>Update Preferences</a> | <a href='https://123cashcontrol.com'>Dashboard</a></p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Upgrade confirmation email template
     */
    private function getUpgradeEmailTemplate($userName) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Welcome to Premium!</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: white; padding: 30px; border: 1px solid #e1e5e9; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; font-size: 14px; color: #666; }
                .feature { margin: 15px 0; padding: 15px; background: #e8f4fd; border-radius: 6px; border-left: 4px solid #4facfe; }
                .button { display: inline-block; background: #4facfe; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🚀 Welcome to Premium!</h1>
                    <p>You now have access to all CashControl features</p>
                </div>
                
                <div class='content'>
                    <h2>Congratulations {$userName}! 🎉</h2>
                    
                    <p>Thank you for upgrading to CashControl Premium! You now have access to all our powerful features.</p>
                    
                    <div class='feature'>
                        <h3>🏦 Bank Integration</h3>
                        <p>Automatically scan your bank account for subscriptions using secure TrueLayer integration.</p>
                    </div>
                    
                    <div class='feature'>
                        <h3>📧 Smart Reminders</h3>
                        <p>Customizable email notifications with your preferred timing and frequency.</p>
                    </div>
                    
                    <div class='feature'>
                        <h3>📊 Advanced Analytics</h3>
                        <p>Detailed insights into your spending patterns and subscription trends.</p>
                    </div>
                    
                    <div class='feature'>
                        <h3>🎯 Priority Support</h3>
                        <p>Get help faster with priority customer support via email.</p>
                    </div>
                    
                    <div style='text-align: center;'>
                        <a href='https://123cashcontrol.com/dashboard' class='button'>Explore Premium Features</a>
                    </div>
                    
                    <p>Ready to get started? Head to your dashboard and try out the bank integration feature!</p>
                    
                    <p>Best regards,<br>The CashControl Team</p>
                </div>
                
                <div class='footer'>
                    <p>CashControl Premium - Unlock your financial potential</p>
                    <p>Questions? Just reply to this email!</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Bank scan completion email template
     */
    private function getBankScanEmailTemplate($userName, $foundSubscriptions) {
        $count = count($foundSubscriptions);
        $subscriptionList = '';
        
        foreach ($foundSubscriptions as $sub) {
            $subscriptionList .= "<li>{$sub['name']} - €{$sub['cost']} per {$sub['billing_cycle']}</li>";
        }
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Bank Scan Complete</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: white; padding: 30px; border: 1px solid #e1e5e9; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; font-size: 14px; color: #666; }
                .subscription-list { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .button { display: inline-block; background: #20c997; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🏦 Bank Scan Complete!</h1>
                    <p>We found {$count} subscriptions in your account</p>
                </div>
                
                <div class='content'>
                    <h2>Hi {$userName}!</h2>
                    
                    <p>Great news! We've completed scanning your bank account and found {$count} subscription(s) that you might want to track.</p>
                    
                    " . ($count > 0 ? "
                    <div class='subscription-list'>
                        <h3>Found Subscriptions:</h3>
                        <ul>{$subscriptionList}</ul>
                    </div>
                    
                    <p>These subscriptions have been automatically added to your CashControl dashboard. You can review, edit, or remove any of them.</p>
                    " : "
                    <p>We didn't find any new subscriptions in your recent transactions. This could mean:</p>
                    <ul>
                        <li>You've already added all your subscriptions manually</li>
                        <li>Your subscriptions use different payment methods</li>
                        <li>The transactions are older than our scan period</li>
                    </ul>
                    ") . "
                    
                    <div style='text-align: center;'>
                        <a href='https://123cashcontrol.com/subscriptions' class='button'>Review Subscriptions</a>
                    </div>
                    
                    <p>Want to scan again? You can run another bank scan anytime from your dashboard.</p>
                    
                    <p>Best regards,<br>The CashControl Team</p>
                </div>
                
                <div class='footer'>
                    <p>CashControl - Automated subscription discovery</p>
                    <p>Your financial data is always secure and encrypted</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Generate UUID v4
     */
    private function generateUuid() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}

// Global email service instance
$emailService = new EmailService();
?>
