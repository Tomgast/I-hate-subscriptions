// Email Service for CashControl
// Supports Plesk SMTP and alternative providers

import nodemailer from 'nodemailer'

export interface EmailConfig {
  provider: 'plesk' | 'sendgrid' | 'mailgun' | 'ses'
  host?: string
  port?: number
  secure?: boolean
  auth?: {
    user: string
    pass: string
  }
  apiKey?: string
}

export interface EmailTemplate {
  subject: string
  html: string
  text: string
}

export interface SubscriptionReminderData {
  userName: string
  subscriptionName: string
  amount: number
  currency: string
  renewalDate: string
  daysUntilRenewal: number
  cancelUrl?: string
  manageUrl?: string
}

export class EmailService {
  private transporter: nodemailer.Transporter | null = null
  private config: EmailConfig
  private fromEmail: string

  constructor() {
    this.config = this.getEmailConfig()
    this.fromEmail = process.env.FROM_EMAIL || 'noreply@123cashcontrol.com'
    this.initializeTransporter()
  }

  private getEmailConfig(): EmailConfig {
    const provider = (process.env.EMAIL_PROVIDER || 'plesk') as EmailConfig['provider']

    switch (provider) {
      case 'plesk':
        return {
          provider: 'plesk',
          host: process.env.PLESK_SMTP_HOST || 'mail.yourdomain.com',
          port: parseInt(process.env.PLESK_SMTP_PORT || '587'),
          secure: process.env.PLESK_SMTP_SECURE === 'true',
          auth: {
            user: process.env.PLESK_SMTP_USER || '',
            pass: process.env.PLESK_SMTP_PASS || ''
          }
        }
      
      case 'sendgrid':
        return {
          provider: 'sendgrid',
          apiKey: process.env.SENDGRID_API_KEY || ''
        }
      
      case 'mailgun':
        return {
          provider: 'mailgun',
          apiKey: process.env.MAILGUN_API_KEY || ''
        }
      
      default:
        return {
          provider: 'plesk',
          host: 'localhost',
          port: 587,
          secure: false,
          auth: { user: '', pass: '' }
        }
    }
  }

  private async initializeTransporter() {
    try {
      if (this.config.provider === 'plesk') {
        this.transporter = nodemailer.createTransporter({
          host: this.config.host,
          port: this.config.port,
          secure: this.config.secure,
          auth: this.config.auth,
          tls: {
            rejectUnauthorized: false // For self-signed certificates
          }
        })

        // Test the connection
        await this.transporter.verify()
        console.log('✅ Plesk SMTP connection verified')
      }
    } catch (error) {
      console.error('❌ Email service initialization failed:', error)
      console.warn('📧 Email notifications will be disabled')
    }
  }

  async sendEmail(to: string, subject: string, html: string, text?: string): Promise<boolean> {
    if (!this.transporter) {
      console.warn('📧 Email service not available, skipping email to:', to)
      return false
    }

    try {
      const mailOptions = {
        from: `CashControl <${this.fromEmail}>`,
        to,
        subject,
        html,
        text: text || this.htmlToText(html)
      }

      const result = await this.transporter.sendMail(mailOptions)
      console.log('✅ Email sent successfully:', result.messageId)
      return true
    } catch (error) {
      console.error('❌ Failed to send email:', error)
      return false
    }
  }

  async sendSubscriptionReminder(to: string, data: SubscriptionReminderData): Promise<boolean> {
    const template = this.generateReminderTemplate(data)
    return this.sendEmail(to, template.subject, template.html, template.text)
  }

  async sendWelcomeEmail(to: string, userName: string): Promise<boolean> {
    const template = this.generateWelcomeTemplate(userName)
    return this.sendEmail(to, template.subject, template.html, template.text)
  }

  async sendUpgradeConfirmation(to: string, userName: string): Promise<boolean> {
    const template = this.generateUpgradeTemplate(userName)
    return this.sendEmail(to, template.subject, template.html, template.text)
  }

  async sendBankScanComplete(to: string, userName: string, subscriptionsFound: number): Promise<boolean> {
    const template = this.generateBankScanTemplate(userName, subscriptionsFound)
    return this.sendEmail(to, template.subject, template.html, template.text)
  }

  private generateReminderTemplate(data: SubscriptionReminderData): EmailTemplate {
    const urgencyColor = data.daysUntilRenewal <= 3 ? '#ef4444' : '#f59e0b'
    const urgencyText = data.daysUntilRenewal <= 3 ? 'URGENT' : 'REMINDER'

    const html = `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Subscription Renewal Reminder</title>
    </head>
    <body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
      
      <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: #1f2937; margin: 0;">💳 CashControl</h1>
        <p style="color: #6b7280; margin: 5px 0 0 0;">Subscription Management</p>
      </div>

      <div style="background: ${urgencyColor}; color: white; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 25px;">
        <h2 style="margin: 0; font-size: 18px;">${urgencyText}: Subscription Renewal</h2>
      </div>

      <div style="background: #f9fafb; padding: 25px; border-radius: 8px; margin-bottom: 25px;">
        <h3 style="margin: 0 0 15px 0; color: #1f2937;">Hi ${data.userName},</h3>
        <p style="margin: 0 0 15px 0;">Your subscription to <strong>${data.subscriptionName}</strong> will renew in <strong>${data.daysUntilRenewal} day${data.daysUntilRenewal !== 1 ? 's' : ''}</strong>.</p>
        
        <div style="background: white; padding: 20px; border-radius: 6px; border-left: 4px solid #3b82f6;">
          <p style="margin: 0 0 10px 0;"><strong>Amount:</strong> ${data.currency}${data.amount.toFixed(2)}</p>
          <p style="margin: 0 0 10px 0;"><strong>Renewal Date:</strong> ${data.renewalDate}</p>
          <p style="margin: 0;"><strong>Service:</strong> ${data.subscriptionName}</p>
        </div>
      </div>

      <div style="text-align: center; margin-bottom: 25px;">
        <a href="${data.manageUrl || 'https://123cashcontrol.com/dashboard'}" 
           style="display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 500;">
          Manage Subscription
        </a>
      </div>

      ${data.cancelUrl ? `
      <div style="text-align: center; margin-bottom: 25px;">
        <a href="${data.cancelUrl}" 
           style="color: #6b7280; text-decoration: underline; font-size: 14px;">
          Cancel this subscription
        </a>
      </div>
      ` : ''}

      <div style="border-top: 1px solid #e5e7eb; padding-top: 20px; text-align: center; color: #6b7280; font-size: 14px;">
        <p>This email was sent by CashControl to help you manage your subscriptions.</p>
        <p>Visit <a href="https://123cashcontrol.com" style="color: #3b82f6;">123cashcontrol.com</a> to update your preferences.</p>
      </div>

    </body>
    </html>
    `

    const text = `
CashControl - Subscription Renewal Reminder

Hi ${data.userName},

Your subscription to ${data.subscriptionName} will renew in ${data.daysUntilRenewal} day${data.daysUntilRenewal !== 1 ? 's' : ''}.

Details:
- Amount: ${data.currency}${data.amount.toFixed(2)}
- Renewal Date: ${data.renewalDate}
- Service: ${data.subscriptionName}

Manage your subscription: ${data.manageUrl || 'https://123cashcontrol.com/dashboard'}
${data.cancelUrl ? `Cancel: ${data.cancelUrl}` : ''}

---
CashControl - Take control of your subscriptions
https://123cashcontrol.com
    `

    return {
      subject: `${urgencyText}: ${data.subscriptionName} renews in ${data.daysUntilRenewal} day${data.daysUntilRenewal !== 1 ? 's' : ''}`,
      html,
      text
    }
  }

  private generateWelcomeTemplate(userName: string): EmailTemplate {
    const html = `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Welcome to CashControl</title>
    </head>
    <body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
      
      <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: #1f2937; margin: 0;">🎉 Welcome to CashControl!</h1>
        <p style="color: #6b7280; margin: 5px 0 0 0;">Take control of your subscriptions</p>
      </div>

      <div style="background: #f9fafb; padding: 25px; border-radius: 8px; margin-bottom: 25px;">
        <h3 style="margin: 0 0 15px 0; color: #1f2937;">Hi ${userName},</h3>
        <p style="margin: 0 0 15px 0;">Welcome to CashControl! We're excited to help you take control of your subscription spending.</p>
        
        <h4 style="color: #1f2937; margin: 20px 0 10px 0;">What you can do:</h4>
        <ul style="margin: 0; padding-left: 20px;">
          <li>Track all your subscriptions in one place</li>
          <li>Get renewal reminders before you're charged</li>
          <li>Scan your European bank accounts for automatic detection</li>
          <li>Export your data anytime</li>
          <li>Enjoy privacy-first design</li>
        </ul>
      </div>

      <div style="text-align: center; margin-bottom: 25px;">
        <a href="https://123cashcontrol.com/dashboard" 
           style="display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 500;">
          Get Started
        </a>
      </div>

      <div style="border-top: 1px solid #e5e7eb; padding-top: 20px; text-align: center; color: #6b7280; font-size: 14px;">
        <p>Need help? Visit our <a href="https://123cashcontrol.com/help" style="color: #3b82f6;">help center</a> or reply to this email.</p>
      </div>

    </body>
    </html>
    `

    const text = `
Welcome to CashControl!

Hi ${userName},

Welcome to CashControl! We're excited to help you take control of your subscription spending.

What you can do:
- Track all your subscriptions in one place
- Get renewal reminders before you're charged
- Scan your European bank accounts for automatic detection
- Export your data anytime
- Enjoy privacy-first design

Get started: https://123cashcontrol.com/dashboard

Need help? Visit https://123cashcontrol.com/help

---
CashControl Team
https://123cashcontrol.com
    `

    return {
      subject: 'Welcome to CashControl! 🎉',
      html,
      text
    }
  }

  private generateUpgradeTemplate(userName: string): EmailTemplate {
    const html = `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Welcome to CashControl Pro</title>
    </head>
    <body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
      
      <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: #1f2937; margin: 0;">⭐ Welcome to CashControl Pro!</h1>
        <p style="color: #6b7280; margin: 5px 0 0 0;">Unlock the full power of subscription management</p>
      </div>

      <div style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; padding: 25px; border-radius: 8px; text-align: center; margin-bottom: 25px;">
        <h2 style="margin: 0 0 10px 0;">🚀 You're now Pro!</h2>
        <p style="margin: 0; opacity: 0.9;">Thank you for upgrading to CashControl Pro</p>
      </div>

      <div style="background: #f9fafb; padding: 25px; border-radius: 8px; margin-bottom: 25px;">
        <h3 style="margin: 0 0 15px 0; color: #1f2937;">Hi ${userName},</h3>
        <p style="margin: 0 0 15px 0;">Your upgrade to CashControl Pro is now active! Here's what you can now do:</p>
        
        <h4 style="color: #1f2937; margin: 20px 0 10px 0;">Pro Features Unlocked:</h4>
        <ul style="margin: 0; padding-left: 20px;">
          <li><strong>🏦 European Bank Scanning</strong> - Connect 100+ European banks</li>
          <li><strong>📧 Email Renewal Alerts</strong> - Never miss a renewal again</li>
          <li><strong>📊 Advanced Analytics</strong> - Deep insights into your spending</li>
          <li><strong>📤 Enhanced Export</strong> - PDF reports and advanced formats</li>
          <li><strong>🔄 Bulk Management</strong> - Manage multiple subscriptions at once</li>
          <li><strong>⚡ Priority Support</strong> - Get help when you need it</li>
        </ul>
      </div>

      <div style="text-align: center; margin-bottom: 25px;">
        <a href="https://123cashcontrol.com/dashboard" 
           style="display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 500;">
          Explore Pro Features
        </a>
      </div>

      <div style="border-top: 1px solid #e5e7eb; padding-top: 20px; text-align: center; color: #6b7280; font-size: 14px;">
        <p>Questions about Pro features? We're here to help!</p>
        <p>Email us at <a href="mailto:support@123cashcontrol.com" style="color: #3b82f6;">support@123cashcontrol.com</a></p>
      </div>

    </body>
    </html>
    `

    const text = `
Welcome to CashControl Pro!

Hi ${userName},

Your upgrade to CashControl Pro is now active! Here's what you can now do:

Pro Features Unlocked:
- 🏦 European Bank Scanning - Connect 100+ European banks
- 📧 Email Renewal Alerts - Never miss a renewal again
- 📊 Advanced Analytics - Deep insights into your spending
- 📤 Enhanced Export - PDF reports and advanced formats
- 🔄 Bulk Management - Manage multiple subscriptions at once
- ⚡ Priority Support - Get help when you need it

Explore Pro features: https://123cashcontrol.com/dashboard

Questions? Email us at support@123cashcontrol.com

---
CashControl Team
https://123cashcontrol.com
    `

    return {
      subject: '⭐ Welcome to CashControl Pro!',
      html,
      text
    }
  }

  private generateBankScanTemplate(userName: string, subscriptionsFound: number): EmailTemplate {
    const html = `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Bank Scan Complete</title>
    </head>
    <body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
      
      <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: #1f2937; margin: 0;">🏦 Bank Scan Complete</h1>
        <p style="color: #6b7280; margin: 5px 0 0 0;">CashControl found your subscriptions</p>
      </div>

      <div style="background: #10b981; color: white; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 25px;">
        <h2 style="margin: 0 0 10px 0;">✅ Scan Successful!</h2>
        <p style="margin: 0; font-size: 18px;">Found ${subscriptionsFound} subscription${subscriptionsFound !== 1 ? 's' : ''}</p>
      </div>

      <div style="background: #f9fafb; padding: 25px; border-radius: 8px; margin-bottom: 25px;">
        <h3 style="margin: 0 0 15px 0; color: #1f2937;">Hi ${userName},</h3>
        <p style="margin: 0 0 15px 0;">Great news! We've completed scanning your bank account and found ${subscriptionsFound} subscription${subscriptionsFound !== 1 ? 's' : ''} that ${subscriptionsFound !== 1 ? 'were' : 'was'} automatically detected.</p>
        
        ${subscriptionsFound > 0 ? `
        <p style="margin: 0 0 15px 0;">These subscriptions have been added to your CashControl dashboard where you can:</p>
        <ul style="margin: 0; padding-left: 20px;">
          <li>Review and edit subscription details</li>
          <li>Set up renewal reminders</li>
          <li>Track your spending patterns</li>
          <li>Cancel unwanted subscriptions</li>
        </ul>
        ` : `
        <p style="margin: 0;">No subscriptions were detected in your recent transactions. You can still manually add any subscriptions you want to track.</p>
        `}
      </div>

      <div style="text-align: center; margin-bottom: 25px;">
        <a href="https://123cashcontrol.com/dashboard" 
           style="display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 500;">
          View Your Subscriptions
        </a>
      </div>

      <div style="border-top: 1px solid #e5e7eb; padding-top: 20px; text-align: center; color: #6b7280; font-size: 14px;">
        <p>Your bank data is processed securely and never stored permanently.</p>
        <p>Questions? Contact us at <a href="mailto:support@123cashcontrol.com" style="color: #3b82f6;">support@123cashcontrol.com</a></p>
      </div>

    </body>
    </html>
    `

    const text = `
Bank Scan Complete - CashControl

Hi ${userName},

Great news! We've completed scanning your bank account and found ${subscriptionsFound} subscription${subscriptionsFound !== 1 ? 's' : ''} that ${subscriptionsFound !== 1 ? 'were' : 'was'} automatically detected.

${subscriptionsFound > 0 ? `
These subscriptions have been added to your CashControl dashboard where you can:
- Review and edit subscription details
- Set up renewal reminders
- Track your spending patterns
- Cancel unwanted subscriptions
` : `
No subscriptions were detected in your recent transactions. You can still manually add any subscriptions you want to track.
`}

View your subscriptions: https://123cashcontrol.com/dashboard

Your bank data is processed securely and never stored permanently.

Questions? Contact us at support@123cashcontrol.com

---
CashControl Team
https://123cashcontrol.com
    `

    return {
      subject: `🏦 Bank scan complete - Found ${subscriptionsFound} subscription${subscriptionsFound !== 1 ? 's' : ''}`,
      html,
      text
    }
  }

  private htmlToText(html: string): string {
    return html
      .replace(/<[^>]*>/g, '')
      .replace(/&nbsp;/g, ' ')
      .replace(/&amp;/g, '&')
      .replace(/&lt;/g, '<')
      .replace(/&gt;/g, '>')
      .replace(/\s+/g, ' ')
      .trim()
  }
}

// Singleton instance
export const emailService = new EmailService()
