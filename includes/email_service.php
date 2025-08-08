<?php
// Email Service for CashControl - Plesk SMTP Integration
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../config/secure_loader.php';

class EmailService {
    private $smtpHost;
    private $smtpPort;
    private $smtpUsername;
    private $smtpPassword;
    private $fromEmail;
    private $fromName;
    
    public function __construct() {
        // Load email configuration securely using global getSecureConfig function
        $this->smtpHost = getSecureConfig('SMTP_HOST') ?: 'shared58.cloud86-host.nl';
        $this->smtpPort = getSecureConfig('SMTP_PORT') ?: 587;
        $this->smtpUsername = getSecureConfig('SMTP_USERNAME') ?: 'info@123cashcontrol.com';
        $this->smtpPassword = getSecureConfig('SMTP_PASSWORD');
        $this->fromEmail = getSecureConfig('FROM_EMAIL') ?: 'info@123cashcontrol.com';
        $this->fromName = getSecureConfig('FROM_NAME') ?: 'CashControl';
    }
    
    // Removed private getSecureConfig method - now using global function from secure_loader.php
    
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
            <title>Welcome to CashControl - Start Saving Today!</title>
            <style>
                body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; line-height: 1.6; color: #374151; background: #f9fafb; margin: 0; padding: 20px; }
                .container { max-width: 650px; margin: 0 auto; background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 50px 40px; text-align: center; position: relative; }
                .header::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.1); }
                .header h1 { margin: 0 0 15px 0; font-size: 36px; font-weight: 800; position: relative; z-index: 1; text-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .header p { margin: 0; font-size: 18px; opacity: 0.95; position: relative; z-index: 1; font-weight: 500; }
                .content { padding: 50px 40px; }
                .hero-cta { text-align: center; margin: 40px 0; }
                .hero-button { display: inline-block; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 20px 40px; text-decoration: none; border-radius: 16px; font-weight: 700; font-size: 18px; box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3); transition: all 0.3s ease; text-transform: uppercase; letter-spacing: 0.5px; }
                .hero-button:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(16, 185, 129, 0.4); }
                .features-section { margin: 50px 0; }
                .features-title { color: #1f2937; font-size: 32px; margin: 0 0 30px 0; font-weight: 700; text-align: center; }
                .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin: 30px 0; }
                .feature-card { background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%); border: 2px solid #d1fae5; border-radius: 16px; padding: 30px; text-align: center; transition: all 0.3s ease; }
                .feature-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(16, 185, 129, 0.15); }
                .feature-icon { font-size: 48px; margin-bottom: 15px; display: block; }
                .feature-title { font-size: 20px; font-weight: 700; color: #059669; margin: 0 0 10px 0; }
                .feature-desc { font-size: 16px; color: #4b5563; line-height: 1.5; }
                .pricing-section { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 20px; padding: 40px; margin: 40px 0; }
                .pricing-title { color: #1f2937; font-size: 28px; margin: 0 0 30px 0; font-weight: 700; text-align: center; }
                .pricing-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin: 30px 0; }
                .pricing-card { background: white; border: 3px solid #e2e8f0; border-radius: 16px; padding: 30px 20px; text-align: center; transition: all 0.3s ease; cursor: pointer; text-decoration: none; color: inherit; display: block; }
                .pricing-card:hover { transform: translateY(-8px); box-shadow: 0 15px 35px rgba(0,0,0,0.1); border-color: #10b981; }
                .pricing-card.featured { border-color: #10b981; background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); position: relative; }
                .pricing-card.featured::before { content: 'BEST VALUE'; position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: #059669; color: white; padding: 6px 20px; border-radius: 20px; font-size: 12px; font-weight: 700; }
                .plan-icon { font-size: 32px; margin-bottom: 15px; }
                .plan-price { font-size: 36px; font-weight: 800; color: #10b981; margin: 15px 0 5px 0; }
                .plan-period { font-size: 16px; color: #6b7280; margin-bottom: 15px; }
                .plan-savings { font-size: 14px; color: #059669; font-weight: 700; background: #d1fae5; padding: 4px 12px; border-radius: 20px; display: inline-block; margin-top: 8px; }
                .plan-cta { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 12px 24px; border-radius: 10px; font-weight: 600; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 15px; display: inline-block; text-decoration: none; }
                .final-cta { background: linear-gradient(135deg, #1f2937 0%, #374151 100%); color: white; padding: 40px; text-align: center; border-radius: 20px; margin: 40px 0; }
                .final-cta h3 { color: white; font-size: 24px; margin: 0 0 15px 0; font-weight: 700; }
                .final-cta p { color: #d1d5db; margin: 0 0 25px 0; font-size: 16px; }
                .final-button { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 18px 36px; text-decoration: none; border-radius: 12px; font-weight: 700; font-size: 16px; display: inline-block; text-transform: uppercase; letter-spacing: 0.5px; }
                .footer { background: #f8fafc; padding: 30px; text-align: center; color: #6b7280; font-size: 14px; }
                @media (max-width: 600px) {
                    .content { padding: 30px 20px; }
                    .features-grid { grid-template-columns: 1fr; }
                    .pricing-grid { grid-template-columns: 1fr; }
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='https://123cashcontrol.com/assets/images/logo.svg' alt='CashControl' style='height: 40px; margin-bottom: 20px;'>
                    <h1>Welcome to CashControl, {$userName}!</h1>
                    <p>Join thousands saving €100s per year on subscriptions</p>
                </div>
                <div class='content'>
                    <div class='hero-cta'>
                        <a href='https://123cashcontrol.com/upgrade.php' class='hero-button'>Start Saving Money Now</a>
                    </div>
                    
                    <div class='features-section'>
                        <h2 class='features-title'>What CashControl Delivers</h2>
                        <div class='features-grid'>
                            <div class='feature-card'>
                                <span class='feature-icon'>🏦</span>
                                <h3 class='feature-title'>Bank Integration</h3>
                                <p class='feature-desc'>Connect 3000+ European banks instantly and discover all subscriptions</p>
                            </div>
                            <div class='feature-card'>
                                <span class='feature-icon'>🤖</span>
                                <h3 class='feature-title'>AI Detection</h3>
                                <p class='feature-desc'>Automatically find hidden subscriptions you forgot about</p>
                            </div>
                            <div class='feature-card'>
                                <span class='feature-icon'>💰</span>
                                <h3 class='feature-title'>Money Savings</h3>
                                <p class='feature-desc'>Track spending and identify subscriptions to cancel</p>
                            </div>
                            <div class='feature-card'>
                                <span class='feature-icon'>📧</span>
                                <h3 class='feature-title'>Smart Alerts</h3>
                                <p class='feature-desc'>Get renewal reminders before you're charged</p>
                            </div>
                            <div class='feature-card'>
                                <span class='feature-icon'>📊</span>
                                <h3 class='feature-title'>Analytics</h3>
                                <p class='feature-desc'>Beautiful reports and spending insights</p>
                            </div>
                            <div class='feature-card'>
                                <span class='feature-icon'>🔒</span>
                                <h3 class='feature-title'>100% Secure</h3>
                                <p class='feature-desc'>Bank-grade security, your data stays private</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class='pricing-section'>
                        <h2 class='pricing-title'>Choose Your Plan</h2>
                        <div class='pricing-grid'>
                            <a href='https://123cashcontrol.com/upgrade.php?plan=monthly' class='pricing-card'>
                                <div class='plan-icon'>📅</div>
                                <div class='plan-price'>€3</div>
                                <div class='plan-period'>per month</div>
                                <div class='plan-cta'>Choose Monthly</div>
                            </a>
                            <a href='https://123cashcontrol.com/upgrade.php?plan=yearly' class='pricing-card featured'>
                                <div class='plan-icon'>💎</div>
                                <div class='plan-price'>€25</div>
                                <div class='plan-period'>per year</div>
                                <div class='plan-savings'>Save 31%!</div>
                                <div class='plan-cta'>Choose Yearly</div>
                            </a>
                            <a href='https://123cashcontrol.com/upgrade.php?plan=onetime' class='pricing-card'>
                                <div class='plan-icon'>🔍</div>
                                <div class='plan-price'>€25</div>
                                <div class='plan-period'>one-time scan</div>
                                <div class='plan-cta'>Choose One-Time</div>
                            </a>
                        </div>
                    </div>
                    
                    <div class='final-cta'>
                        <h3>Ready to Save Money?</h3>
                        <p>Join thousands of users already saving hundreds of euros per year</p>
                        <a href='https://123cashcontrol.com/upgrade.php' class='final-button'>Start Your Journey</a>
                    </div>
                </div>
                <div class='footer'>
                    <p><strong>CashControl</strong> - Professional Subscription Management</p>
                    <p>You received this email because you created an account at 123cashcontrol.com</p>
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
            <title>Welcome to CashControl Pro!</title>
            <style>
                body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; line-height: 1.6; color: #374151; background: #f9fafb; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 40px 30px; text-align: center; position: relative; }
                .header::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.1); }
                .header h1 { margin: 0 0 10px 0; font-size: 28px; font-weight: 700; position: relative; z-index: 1; }
                .header p { margin: 0; font-size: 16px; opacity: 0.9; position: relative; z-index: 1; }
                .content { padding: 40px 30px; }
                .content h2 { color: #1f2937; font-size: 24px; margin: 0 0 20px 0; font-weight: 600; }
                .button { display: inline-block; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 16px 32px; text-decoration: none; border-radius: 12px; font-weight: 600; margin: 20px 0; font-size: 16px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); transition: all 0.3s ease; }
                .button:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4); }
                .success-badge { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border: 2px solid #10b981; border-radius: 12px; padding: 25px; text-align: center; margin: 25px 0; }
                .success-badge h3 { color: #059669; margin: 0 0 10px 0; font-size: 20px; }
                .feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 30px 0; }
                .feature-card { background: #f8fafc; border-radius: 12px; padding: 20px; border-left: 4px solid #10b981; }
                .feature-card h4 { color: #1f2937; margin: 0 0 10px 0; font-size: 16px; font-weight: 600; }
                .feature-card p { color: #6b7280; margin: 0; font-size: 14px; }
                .cta-section { text-align: center; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-radius: 12px; padding: 30px; margin: 30px 0; }
                .footer { text-align: center; padding: 30px; background: #f9fafb; color: #6b7280; font-size: 14px; }
                .footer p { margin: 5px 0; }
                @media (max-width: 600px) { .feature-grid { grid-template-columns: 1fr; } .container { margin: 10px; } .content, .header { padding: 30px 20px; } }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🚀 Welcome to CashControl Pro!</h1>
                    <p>Your subscription management upgrade is complete</p>
                </div>
                <div class='content'>
                    <h2>Hi {$userName}!</h2>
                    <p>Congratulations! Your payment has been processed successfully and you now have full access to all CashControl premium features.</p>
                    
                    <div class='success-badge'>
                        <h3>✅ Payment Confirmed</h3>
                        <p style='color: #374151; margin: 0;'>Your CashControl Pro subscription is now active</p>
                    </div>
                    
                    <div class='feature-grid'>
                        <div class='feature-card'>
                            <h4>🏦 Bank Integration</h4>
                            <p>Connect with 3000+ European banks for automatic subscription discovery</p>
                        </div>
                        <div class='feature-card'>
                            <h4>📊 Advanced Analytics</h4>
                            <p>Get detailed insights into your spending patterns and subscription trends</p>
                        </div>
                        <div class='feature-card'>
                            <h4>📧 Smart Reminders</h4>
                            <p>Never miss a renewal with intelligent email notifications</p>
                        </div>
                        <div class='feature-card'>
                            <h4>📄 Export & Reports</h4>
                            <p>Generate PDF reports and CSV exports of your subscription data</p>
                        </div>
                    </div>
                    
                    <div class='cta-section'>
                        <h3 style='color: #059669; margin: 0 0 15px 0;'>Ready to Get Started?</h3>
                        <p style='margin: 0 0 20px 0; color: #374151;'>Access your Pro dashboard and start connecting your bank accounts to discover all your subscriptions.</p>
                        <a href='https://123cashcontrol.com/dashboard.php' class='button'>Access Pro Dashboard →</a>
                    </div>
                    
                    <p>Need help getting started? Just reply to this email and we'll guide you through setting up your first bank connection and discovering your subscriptions.</p>
                    
                    <p style='margin-top: 30px;'>Thank you for choosing CashControl Pro!<br><strong>The CashControl Team</strong></p>
                </div>
                <div class='footer'>
                    <p><strong>CashControl Pro</strong> - Professional Subscription Management</p>
                    <p>Questions? Reply to this email or visit our support center</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Send a test email to verify configuration
     */
    public function sendTestEmail($toEmail, $userName = 'User') {
        $subject = "CashControl Email Test - Configuration Working!";
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Email Test</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 20px; text-align: center; border-radius: 8px; }
                .content { background: white; padding: 20px; border: 1px solid #ddd; border-radius: 8px; margin-top: 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✅ Email Test Successful!</h1>
                </div>
                <div class='content'>
                    <h2>Hi {$userName}!</h2>
                    <p>This is a test email from CashControl to verify that email delivery is working correctly.</p>
                    <p><strong>Email Configuration Details:</strong></p>
                    <ul>
                        <li>SMTP Host: {$this->smtpHost}</li>
                        <li>SMTP Port: {$this->smtpPort}</li>
                        <li>From Email: {$this->fromEmail}</li>
                        <li>From Name: {$this->fromName}</li>
                    </ul>
                    <p>If you received this email, your email configuration is working properly!</p>
                    <p>Best regards,<br>CashControl Email Service</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->sendEmail($toEmail, $subject, $body);
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
