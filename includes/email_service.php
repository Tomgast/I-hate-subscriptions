<?php
// Email Service for CashControl - Plesk SMTP Integration
require_once __DIR__ . '/../config/db_config.php';

class EmailService {
    private $smtpHost;
    private $smtpPort;
    private $smtpUsername;
    private $smtpPassword;
    private $fromEmail;
    private $fromName;
    
    public function __construct() {
        // Load email configuration securely
        $this->smtpHost = $this->getSecureConfig('SMTP_HOST', 'shared58.cloud86-host.nl');
        $this->smtpPort = $this->getSecureConfig('SMTP_PORT', 587);
        $this->smtpUsername = $this->getSecureConfig('SMTP_USERNAME', 'info@123cashcontrol.com');
        $this->smtpPassword = $this->getSecureConfig('SMTP_PASSWORD');
        $this->fromEmail = $this->getSecureConfig('FROM_EMAIL', 'info@123cashcontrol.com');
        $this->fromName = $this->getSecureConfig('FROM_NAME', 'CashControl');
    }
    
    /**
     * Securely load configuration values from multiple sources
     * Priority: Plesk Environment Variables > Secure Config File > Default
     */
    private function getSecureConfig($key, $default = null) {
        // Try Plesk environment variables first
        $value = getenv($key) ?: $_SERVER[$key] ?? null;
        
        if ($value) {
            return $value;
        }
        
        // Try secure config file (outside web root)
        static $secureConfig = null;
        if ($secureConfig === null) {
            $configPath = dirname(__DIR__) . '/../secure-config.php';
            if (file_exists($configPath)) {
                $secureConfig = include $configPath;
            } else {
                $secureConfig = [];
            }
        }
        
        return $secureConfig[$key] ?? $default;
    }
    
    /**
     * Send email using PHP mail() function with SMTP headers
     */
    public function sendEmail($to, $subject, $htmlBody, $textBody = null) {
        try {
            // Prepare headers
            $headers = [
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
                'Reply-To: ' . $this->fromEmail,
                'X-Mailer: CashControl Email Service',
                'X-Priority: 3'
            ];
            
            $headerString = implode("\r\n", $headers);
            
            // Send email
            $success = mail($to, $subject, $htmlBody, $headerString);
            
            if ($success) {
                error_log("Email sent successfully to: $to");
                return true;
            } else {
                error_log("Failed to send email to: $to");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send welcome email to new users
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
        
        return $this->sendEmail($userEmail, $subject, $htmlBody);
    }
    
    /**
     * Send upgrade confirmation email
     */
    public function sendUpgradeConfirmation($userEmail, $userName) {
        $subject = "Welcome to CashControl Pro! 🚀";
        
        $htmlBody = $this->getUpgradeConfirmationTemplate($userName);
        
        return $this->sendEmail($userEmail, $subject, $htmlBody);
    }
    
    /**
     * Get welcome email HTML template
     */
    private function getWelcomeEmailTemplate($userName) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Welcome to CashControl</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; margin: 20px 0; }
                .feature { margin: 15px 0; padding: 15px; background: #f8f9fa; border-radius: 8px; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Welcome to CashControl!</h1>
                    <p>Take control of your subscription chaos</p>
                </div>
                <div class='content'>
                    <h2>Hi {$userName}!</h2>
                    <p>Welcome to CashControl! We're excited to help you take control of your subscriptions and save money.</p>
                    
                    <div class='feature'>
                        <h3>🎯 What you can do now:</h3>
                        <ul>
                            <li>Add your subscriptions manually</li>
                            <li>Set up renewal reminders</li>
                            <li>View your spending analytics</li>
                            <li>Export your data anytime</li>
                        </ul>
                    </div>
                    
                    <div class='feature'>
                        <h3>🚀 Upgrade to Pro for:</h3>
                        <ul>
                            <li>🏦 Automatic bank integration</li>
                            <li>📧 Smart email reminders</li>
                            <li>📊 Advanced analytics</li>
                            <li>🔍 Auto-detect subscriptions</li>
                        </ul>
                    </div>
                    
                    <div style='text-align: center;'>
                        <a href='https://123cashcontrol.com/dashboard.php' class='button'>Get Started Now</a>
                    </div>
                    
                    <p>If you have any questions, just reply to this email. We're here to help!</p>
                    
                    <p>Best regards,<br>The CashControl Team</p>
                </div>
                <div class='footer'>
                    <p>CashControl - Stop Subscription Chaos Forever</p>
                    <p>You received this email because you signed up for CashControl.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Get renewal reminder email HTML template
     */
    private function getRenewalReminderTemplate($userName, $subscription, $daysUntilRenewal) {
        $amount = number_format($subscription['cost'], 2);
        $currency = $subscription['currency'] ?? 'EUR';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Subscription Renewal Reminder</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                .subscription-card { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #f59e0b; }
                .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>⏰ Renewal Reminder</h1>
                    <p>Your subscription is renewing soon</p>
                </div>
                <div class='content'>
                    <h2>Hi {$userName}!</h2>
                    <p>This is a friendly reminder that your subscription is renewing soon.</p>
                    
                    <div class='subscription-card'>
                        <h3>{$subscription['name']}</h3>
                        <p><strong>Amount:</strong> {$currency}{$amount}</p>
                        <p><strong>Renewal Date:</strong> {$subscription['next_payment_date']}</p>
                        <p><strong>Days Until Renewal:</strong> {$daysUntilRenewal} days</p>
                    </div>
                    
                    <p>If you want to cancel this subscription, make sure to do it before the renewal date to avoid being charged.</p>
                    
                    <div style='text-align: center;'>
                        <a href='https://123cashcontrol.com/dashboard.php' class='button'>Manage Subscriptions</a>
                    </div>
                    
                    <p>Stay in control of your subscriptions!</p>
                    
                    <p>Best regards,<br>The CashControl Team</p>
                </div>
                <div class='footer'>
                    <p>CashControl - Stop Subscription Chaos Forever</p>
                    <p>You can adjust your notification preferences in your dashboard.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Get upgrade confirmation email HTML template
     */
    private function getUpgradeConfirmationTemplate($userName) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Welcome to CashControl Pro</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                .feature { margin: 15px 0; padding: 15px; background: #f0fdf4; border-radius: 8px; border-left: 4px solid #10b981; }
                .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🚀 Welcome to CashControl Pro!</h1>
                    <p>You now have lifetime access to all premium features</p>
                </div>
                <div class='content'>
                    <h2>Hi {$userName}!</h2>
                    <p>Congratulations! You've successfully upgraded to CashControl Pro. You now have lifetime access to all our premium features.</p>
                    
                    <div class='feature'>
                        <h3>🎉 Your Pro features are now active:</h3>
                        <ul>
                            <li>🏦 Bank integration with 3000+ European banks</li>
                            <li>📧 Smart email reminders and notifications</li>
                            <li>📊 Advanced analytics and insights</li>
                            <li>🔍 Automatic subscription detection</li>
                            <li>📄 PDF reports and exports</li>
                            <li>🔒 100% private and secure</li>
                        </ul>
                    </div>
                    
                    <p>Start by connecting your bank account to automatically discover all your subscriptions, including the ones you might have forgotten about!</p>
                    
                    <div style='text-align: center;'>
                        <a href='https://123cashcontrol.com/dashboard.php' class='button'>Access Your Pro Dashboard</a>
                    </div>
                    
                    <p>Thank you for choosing CashControl Pro. We're excited to help you save money and take control of your subscriptions!</p>
                    
                    <p>Best regards,<br>The CashControl Team</p>
                </div>
                <div class='footer'>
                    <p>CashControl Pro - Lifetime Access</p>
                    <p>You have lifetime access to all current and future Pro features.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Test email configuration
     */
    public function testEmailConfiguration() {
        $testEmail = $this->fromEmail;
        $subject = "CashControl Email Test";
        $body = "<h2>Email Configuration Test</h2><p>If you receive this email, your email configuration is working correctly!</p>";
        
        return $this->sendEmail($testEmail, $subject, $body);
    }
}
?>
