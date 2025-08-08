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
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <meta name='x-apple-disable-message-reformatting'>
            <title>Welcome to CashControl</title>
            <!--[if mso]>
            <noscript>
                <xml>
                    <o:OfficeDocumentSettings>
                        <o:PixelsPerInch>96</o:PixelsPerInch>
                    </o:OfficeDocumentSettings>
                </xml>
            </noscript>
            <![endif]-->
            <style>
                /* Reset and base styles */
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #374151; background-color: #f9fafb; margin: 0; padding: 0; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
                table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
                img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
                
                /* Container and layout */
                .email-container { max-width: 600px; margin: 0 auto; background: #ffffff; }
                .content-wrapper { padding: 0; }
                
                /* Header */
                .header { background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #d1fae5 100%); padding: 40px 30px; text-align: center; }
                .logo { display: inline-block; margin-bottom: 20px; }
                .logo svg { width: 120px; height: auto; }
                .header h1 { color: #1f2937; font-size: 28px; font-weight: 700; margin: 0 0 8px 0; }
                .header p { color: #6b7280; font-size: 16px; margin: 0; }
                
                /* Content sections */
                .content { padding: 40px 30px; }
                .section { margin-bottom: 40px; }
                .section:last-child { margin-bottom: 0; }
                
                /* Typography */
                .section-title { color: #1f2937; font-size: 24px; font-weight: 600; margin: 0 0 20px 0; text-align: center; }
                .text { color: #4b5563; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0; }
                .text:last-child { margin-bottom: 0; }
                
                /* Buttons */
                .button-container { text-align: center; margin: 30px 0; }
                .button { display: inline-block; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff; text-decoration: none; padding: 16px 32px; border-radius: 8px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25); }
                .button:hover { box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35); }
                
                /* Feature list */
                .feature-list { margin: 30px 0; }
                .feature-item { display: flex; align-items: flex-start; margin-bottom: 16px; padding: 16px; background: #f8fafc; border-radius: 8px; border-left: 4px solid #10b981; }
                .feature-item:last-child { margin-bottom: 0; }
                .feature-icon { width: 24px; height: 24px; margin-right: 12px; margin-top: 2px; flex-shrink: 0; }
                .feature-content h4 { color: #1f2937; font-size: 16px; font-weight: 600; margin: 0 0 4px 0; }
                .feature-content p { color: #6b7280; font-size: 14px; margin: 0; }
                
                /* Pricing cards */
                .pricing-container { margin: 30px 0; }
                .pricing-grid { display: table; width: 100%; }
                .pricing-row { display: table-row; }
                .pricing-cell { display: table-cell; width: 33.333%; padding: 0 8px; vertical-align: top; }
                .pricing-card { background: #ffffff; border: 2px solid #e5e7eb; border-radius: 12px; padding: 24px 16px; text-align: center; margin-bottom: 16px; }
                .pricing-card.featured { border-color: #10b981; background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); position: relative; }
                .pricing-card.featured::before { content: 'Most Popular'; position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: #059669; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
                .pricing-card h3 { color: #1f2937; font-size: 18px; font-weight: 600; margin: 0 0 8px 0; }
                .pricing-card .price { color: #10b981; font-size: 32px; font-weight: 700; margin: 8px 0; }
                .pricing-card .period { color: #6b7280; font-size: 14px; margin: 0 0 16px 0; }
                .pricing-card .savings { background: #d1fae5; color: #059669; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 600; margin: 8px 0; display: inline-block; }
                .pricing-card .card-button { display: inline-block; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; text-decoration: none; padding: 12px 20px; border-radius: 6px; font-weight: 600; font-size: 14px; }
                
                /* Footer */
                .footer { background: #f8fafc; padding: 30px; text-align: center; border-top: 1px solid #e5e7eb; }
                .footer p { color: #6b7280; font-size: 14px; margin: 0 0 8px 0; }
                .footer p:last-child { margin-bottom: 0; }
                .footer a { color: #10b981; text-decoration: none; }
                
                /* Mobile responsive */
                @media only screen and (max-width: 600px) {
                    .email-container { width: 100% !important; }
                    .content { padding: 30px 20px !important; }
                    .header { padding: 30px 20px !important; }
                    .pricing-grid { display: block !important; }
                    .pricing-cell { display: block !important; width: 100% !important; padding: 0 !important; }
                    .pricing-card { margin-bottom: 16px !important; }
                    .feature-item { flex-direction: column !important; text-align: center !important; }
                    .feature-icon { margin: 0 0 8px 0 !important; }
                }
            </style>
        </head>
        <body>
            <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%'>
                <tr>
                    <td style='padding: 20px 0;'>
                        <div class='email-container'>
                            <!-- Header -->
                            <div class='header'>
                                <div class='logo'>
                                    <svg width='120' height='32' viewBox='0 0 120 32' fill='none' xmlns='http://www.w3.org/2000/svg'>
                                        <rect width='120' height='32' rx='8' fill='#10b981'/>
                                        <text x='60' y='20' text-anchor='middle' fill='white' font-family='Inter, sans-serif' font-weight='700' font-size='14'>CashControl</text>
                                    </svg>
                                </div>
                                <h1>Welcome to CashControl, {$userName}!</h1>
                                <p>Your subscription management journey starts here</p>
                            </div>
                            
                            <!-- Content -->
                            <div class='content'>
                                <!-- Welcome message -->
                                <div class='section'>
                                    <p class='text'>Thank you for creating your CashControl account! We're excited to help you take control of your subscriptions and save money.</p>
                                    <p class='text'>CashControl makes it easy to discover, track, and manage all your subscriptions in one place. Connect your bank account once and let us do the heavy lifting.</p>
                                </div>
                                
                                <!-- Features -->
                                <div class='section'>
                                    <h2 class='section-title'>What you get with CashControl</h2>
                                    <div class='feature-list'>
                                        <div class='feature-item'>
                                            <div class='feature-icon'>
                                                <svg width='24' height='24' fill='#10b981' viewBox='0 0 24 24'>
                                                    <path d='M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2 2z'/>
                                                    <path d='M8 1v6m8-6v6M1 9h22'/>
                                                </svg>
                                            </div>
                                            <div class='feature-content'>
                                                <h4>Bank Integration</h4>
                                                <p>Connect 3000+ European banks to automatically discover all your subscriptions</p>
                                            </div>
                                        </div>
                                        <div class='feature-item'>
                                            <div class='feature-icon'>
                                                <svg width='24' height='24' fill='#10b981' viewBox='0 0 24 24'>
                                                    <path d='M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z'/>
                                                </svg>
                                            </div>
                                            <div class='feature-content'>
                                                <h4>Smart Detection</h4>
                                                <p>AI-powered system finds subscriptions you might have forgotten about</p>
                                            </div>
                                        </div>
                                        <div class='feature-item'>
                                            <div class='feature-icon'>
                                                <svg width='24' height='24' fill='#10b981' viewBox='0 0 24 24'>
                                                    <path d='M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6'/>
                                                </svg>
                                            </div>
                                            <div class='feature-content'>
                                                <h4>Money Savings</h4>
                                                <p>Track spending patterns and identify subscriptions to cancel or optimize</p>
                                            </div>
                                        </div>
                                        <div class='feature-item'>
                                            <div class='feature-icon'>
                                                <svg width='24' height='24' fill='#10b981' viewBox='0 0 24 24'>
                                                    <path d='M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z'/>
                                                    <polyline points='22,6 12,13 2,6'/>
                                                </svg>
                                            </div>
                                            <div class='feature-content'>
                                                <h4>Smart Reminders</h4>
                                                <p>Get notified before renewals so you never pay for unwanted subscriptions</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pricing -->
                                <div class='section'>
                                    <h2 class='section-title'>Choose your plan</h2>
                                    <div class='pricing-container'>
                                        <div class='pricing-grid'>
                                            <div class='pricing-row'>
                                                <div class='pricing-cell'>
                                                    <div class='pricing-card'>
                                                        <h3>Monthly</h3>
                                                        <div class='price'>€3</div>
                                                        <div class='period'>per month</div>
                                                        <a href='https://123cashcontrol.com/upgrade.php?plan=monthly' class='card-button'>Choose Monthly</a>
                                                    </div>
                                                </div>
                                                <div class='pricing-cell'>
                                                    <div class='pricing-card featured'>
                                                        <h3>Yearly</h3>
                                                        <div class='price'>€25</div>
                                                        <div class='period'>per year</div>
                                                        <div class='savings'>Save 31%</div>
                                                        <a href='https://123cashcontrol.com/upgrade.php?plan=yearly' class='card-button'>Choose Yearly</a>
                                                    </div>
                                                </div>
                                                <div class='pricing-cell'>
                                                    <div class='pricing-card'>
                                                        <h3>One-Time</h3>
                                                        <div class='price'>€25</div>
                                                        <div class='period'>single scan</div>
                                                        <a href='https://123cashcontrol.com/upgrade.php?plan=onetime' class='card-button'>Choose One-Time</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- CTA -->
                                <div class='button-container'>
                                    <a href='https://123cashcontrol.com/upgrade.php' class='button'>Get Started Now</a>
                                </div>
                                
                                <div class='section'>
                                    <p class='text'>Questions? Just reply to this email - we're here to help you save money and take control of your subscriptions.</p>
                                </div>
                            </div>
                            
                            <!-- Footer -->
                            <div class='footer'>
                                <p><strong>CashControl</strong> - Subscription Management Made Simple</p>
                                <p>You received this email because you created an account at <a href='https://123cashcontrol.com'>123cashcontrol.com</a></p>
                                <p>Questions? Contact us at <a href='mailto:support@123cashcontrol.com'>support@123cashcontrol.com</a></p>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
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
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <meta name='x-apple-disable-message-reformatting'>
            <title>Welcome to CashControl Pro!</title>
            <!--[if mso]>
            <noscript>
                <xml>
                    <o:OfficeDocumentSettings>
                        <o:PixelsPerInch>96</o:PixelsPerInch>
                    </o:OfficeDocumentSettings>
                </xml>
            </noscript>
            <![endif]-->
            <style>
                /* Reset and base styles */
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #374151; background-color: #f9fafb; margin: 0; padding: 0; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
                table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
                img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
                
                /* Container and layout */
                .email-container { max-width: 600px; margin: 0 auto; background: #ffffff; }
                .content-wrapper { padding: 0; }
                
                /* Header */
                .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 40px 30px; text-align: center; color: white; }
                .logo { display: inline-block; margin-bottom: 20px; }
                .logo svg { width: 120px; height: auto; }
                .header h1 { color: white; font-size: 28px; font-weight: 700; margin: 0 0 8px 0; }
                .header p { color: rgba(255, 255, 255, 0.9); font-size: 16px; margin: 0; }
                
                /* Content sections */
                .content { padding: 40px 30px; }
                .section { margin-bottom: 40px; }
                .section:last-child { margin-bottom: 0; }
                
                /* Success badge */
                .success-badge { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border: 2px solid #10b981; border-radius: 12px; padding: 30px; text-align: center; margin: 30px 0; }
                .success-badge .icon { font-size: 48px; margin-bottom: 16px; }
                .success-badge h2 { color: #059669; font-size: 24px; font-weight: 700; margin: 0 0 8px 0; }
                .success-badge p { color: #047857; font-size: 16px; margin: 0; }
                
                /* Typography */
                .section-title { color: #1f2937; font-size: 24px; font-weight: 600; margin: 0 0 20px 0; text-align: center; }
                .text { color: #4b5563; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0; }
                .text:last-child { margin-bottom: 0; }
                
                /* Buttons */
                .button-container { text-align: center; margin: 30px 0; }
                .button { display: inline-block; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff; text-decoration: none; padding: 16px 32px; border-radius: 8px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25); }
                .button:hover { box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35); }
                
                /* Feature list */
                .feature-list { margin: 30px 0; }
                .feature-item { display: flex; align-items: flex-start; margin-bottom: 16px; padding: 16px; background: #f8fafc; border-radius: 8px; border-left: 4px solid #10b981; }
                .feature-item:last-child { margin-bottom: 0; }
                .feature-icon { width: 24px; height: 24px; margin-right: 12px; margin-top: 2px; flex-shrink: 0; }
                .feature-content h4 { color: #1f2937; font-size: 16px; font-weight: 600; margin: 0 0 4px 0; }
                .feature-content p { color: #6b7280; font-size: 14px; margin: 0; }
                
                /* Next steps */
                .next-steps { background: #f8fafc; border-radius: 12px; padding: 30px; margin: 30px 0; border: 1px solid #e5e7eb; }
                .next-steps h3 { color: #1f2937; font-size: 20px; font-weight: 600; margin: 0 0 16px 0; text-align: center; }
                .step-list { list-style: none; padding: 0; margin: 0; }
                .step-item { display: flex; align-items: flex-start; margin-bottom: 16px; }
                .step-item:last-child { margin-bottom: 0; }
                .step-number { background: #10b981; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px; margin-right: 12px; flex-shrink: 0; }
                .step-content { flex: 1; }
                .step-content h4 { color: #1f2937; font-size: 16px; font-weight: 600; margin: 0 0 4px 0; }
                .step-content p { color: #6b7280; font-size: 14px; margin: 0; }
                
                /* Footer */
                .footer { background: #f8fafc; padding: 30px; text-align: center; border-top: 1px solid #e5e7eb; }
                .footer p { color: #6b7280; font-size: 14px; margin: 0 0 8px 0; }
                .footer p:last-child { margin-bottom: 0; }
                .footer a { color: #10b981; text-decoration: none; }
                
                /* Mobile responsive */
                @media only screen and (max-width: 600px) {
                    .email-container { width: 100% !important; }
                    .content { padding: 30px 20px !important; }
                    .header { padding: 30px 20px !important; }
                    .feature-item { flex-direction: column !important; text-align: center !important; }
                    .feature-icon { margin: 0 0 8px 0 !important; }
                    .step-item { flex-direction: column !important; text-align: center !important; }
                    .step-number { margin: 0 0 8px 0 !important; }
                }
            </style>
        </head>
        <body>
            <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%'>
                <tr>
                    <td style='padding: 20px 0;'>
                        <div class='email-container'>
                            <!-- Header -->
                            <div class='header'>
                                <div class='logo'>
                                    <svg width='120' height='32' viewBox='0 0 120 32' fill='none' xmlns='http://www.w3.org/2000/svg'>
                                        <rect width='120' height='32' rx='8' fill='white'/>
                                        <text x='60' y='20' text-anchor='middle' fill='#10b981' font-family='Inter, sans-serif' font-weight='700' font-size='14'>CashControl</text>
                                    </svg>
                                </div>
                                <h1>Welcome to CashControl Pro, {$userName}!</h1>
                                <p>Your payment was successful and your account is now active</p>
                            </div>
                            
                            <!-- Content -->
                            <div class='content'>
                                <!-- Success confirmation -->
                                <div class='success-badge'>
                                    <div class='icon'>
                                        <svg width='48' height='48' fill='#10b981' viewBox='0 0 24 24'>
                                            <path d='M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'/>
                                        </svg>
                                    </div>
                                    <h2>Payment Confirmed!</h2>
                                    <p>Your CashControl Pro subscription is now active and ready to use</p>
                                </div>
                                
                                <!-- Welcome message -->
                                <div class='section'>
                                    <p class='text'>Thank you for upgrading to CashControl Pro! You now have access to all our premium features to help you take complete control of your subscriptions and save money.</p>
                                </div>
                                
                                <!-- Features -->
                                <div class='section'>
                                    <h2 class='section-title'>What's now available to you</h2>
                                    <div class='feature-list'>
                                        <div class='feature-item'>
                                            <div class='feature-icon'>
                                                <svg width='24' height='24' fill='#10b981' viewBox='0 0 24 24'>
                                                    <path d='M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2 2z'/>
                                                    <path d='M8 1v6m8-6v6M1 9h22'/>
                                                </svg>
                                            </div>
                                            <div class='feature-content'>
                                                <h4>Unlimited Bank Scans</h4>
                                                <p>Connect multiple bank accounts and scan as often as you need</p>
                                            </div>
                                        </div>
                                        <div class='feature-item'>
                                            <div class='feature-icon'>
                                                <svg width='24' height='24' fill='#10b981' viewBox='0 0 24 24'>
                                                    <path d='M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'/>
                                                </svg>
                                            </div>
                                            <div class='feature-content'>
                                                <h4>Export Reports</h4>
                                                <p>Download your subscription data as PDF or CSV files</p>
                                            </div>
                                        </div>
                                        <div class='feature-item'>
                                            <div class='feature-icon'>
                                                <svg width='24' height='24' fill='#10b981' viewBox='0 0 24 24'>
                                                    <path d='M15 17h5l-5 5v-5zM4 1h5v5H4V1zm0 7h5v5H4V8zm0 7h5v5H4v-5zM16 1h5v5h-5V1zm0 7h5v5h-5V8z'/>
                                                </svg>
                                            </div>
                                            <div class='feature-content'>
                                                <h4>Advanced Analytics</h4>
                                                <p>Detailed insights into your spending patterns and trends</p>
                                            </div>
                                        </div>
                                        <div class='feature-item'>
                                            <div class='feature-icon'>
                                                <svg width='24' height='24' fill='#10b981' viewBox='0 0 24 24'>
                                                    <path d='M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z'/>
                                                    <polyline points='22,6 12,13 2,6'/>
                                                </svg>
                                            </div>
                                            <div class='feature-content'>
                                                <h4>Smart Notifications</h4>
                                                <p>Get alerts before renewals and when new subscriptions are detected</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Next steps -->
                                <div class='next-steps'>
                                    <h3>Get started in 3 easy steps</h3>
                                    <ol class='step-list'>
                                        <li class='step-item'>
                                            <div class='step-number'>1</div>
                                            <div class='step-content'>
                                                <h4>Access your dashboard</h4>
                                                <p>Click the button below to go to your CashControl dashboard</p>
                                            </div>
                                        </li>
                                        <li class='step-item'>
                                            <div class='step-number'>2</div>
                                            <div class='step-content'>
                                                <h4>Connect your bank</h4>
                                                <p>Securely link your bank account to start discovering subscriptions</p>
                                            </div>
                                        </li>
                                        <li class='step-item'>
                                            <div class='step-number'>3</div>
                                            <div class='step-content'>
                                                <h4>Start saving money</h4>
                                                <p>Review your subscriptions and cancel the ones you don't need</p>
                                            </div>
                                        </li>
                                    </ol>
                                </div>
                                
                                <!-- CTA -->
                                <div class='button-container'>
                                    <a href='https://123cashcontrol.com/dashboard.php' class='button'>Go to Dashboard</a>
                                </div>
                                
                                <div class='section'>
                                    <p class='text'>Need help getting started? Just reply to this email and our support team will be happy to assist you.</p>
                                </div>
                            </div>
                            
                            <!-- Footer -->
                            <div class='footer'>
                                <p><strong>CashControl</strong> - Subscription Management Made Simple</p>
                                <p>You received this email because you upgraded your account at <a href='https://123cashcontrol.com'>123cashcontrol.com</a></p>
                                <p>Questions? Contact us at <a href='mailto:support@123cashcontrol.com'>support@123cashcontrol.com</a></p>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
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
