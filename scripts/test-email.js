// Email Service Test Script
// Tests Plesk SMTP configuration and email sending

import nodemailer from 'nodemailer'
import dotenv from 'dotenv'
import path from 'path'
import { fileURLToPath } from 'url'

// Load environment variables from .env.local
const __dirname = path.dirname(fileURLToPath(import.meta.url))
dotenv.config({ path: path.resolve(__dirname, '../.env.local') })

async function testEmailConfiguration() {
  console.log('📧 Testing Plesk SMTP email configuration for CashControl...')
  console.log(`📍 SMTP Host: ${process.env.PLESK_SMTP_HOST}:${process.env.PLESK_SMTP_PORT}`)
  console.log(`👤 SMTP User: ${process.env.PLESK_SMTP_USER}`)
  console.log(`🔒 SMTP Secure: ${process.env.PLESK_SMTP_SECURE}`)
  console.log(`📨 From Email: ${process.env.FROM_EMAIL}`)
  
  let transporter = null
  
  try {
    // Create SMTP transporter with Plesk configuration
    transporter = nodemailer.createTransport({
      host: process.env.PLESK_SMTP_HOST,
      port: parseInt(process.env.PLESK_SMTP_PORT || '587'),
      secure: process.env.PLESK_SMTP_SECURE === 'true', // true for 465, false for other ports
      auth: {
        user: process.env.PLESK_SMTP_USER,
        pass: process.env.PLESK_SMTP_PASS
      },
      tls: {
        rejectUnauthorized: false // Accept self-signed certificates
      },
      debug: true, // Enable debug output
      logger: true // Log to console
    })
    
    console.log('\n🔍 Testing SMTP connection...')
    
    // Verify SMTP connection
    const isConnected = await transporter.verify()
    
    if (isConnected) {
      console.log('✅ SMTP connection successful!')
      
      // Test sending a simple email
      console.log('\n📤 Sending test email...')
      
      const testEmail = {
        from: `CashControl Test <${process.env.FROM_EMAIL}>`,
        to: process.env.PLESK_SMTP_USER, // Send to same email for testing
        subject: '🧪 CashControl Email Test - ' + new Date().toISOString(),
        html: `
          <!DOCTYPE html>
          <html>
          <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Email Test</title>
          </head>
          <body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
            
            <div style="text-align: center; margin-bottom: 30px;">
              <h1 style="color: #1f2937; margin: 0;">🧪 Email Test Successful</h1>
              <p style="color: #6b7280; margin: 5px 0 0 0;">CashControl SMTP Configuration</p>
            </div>

            <div style="background: #10b981; color: white; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 25px;">
              <h2 style="margin: 0 0 10px 0;">✅ SMTP Working!</h2>
              <p style="margin: 0; font-size: 16px;">Your Plesk email configuration is working correctly</p>
            </div>

            <div style="background: #f9fafb; padding: 25px; border-radius: 8px; margin-bottom: 25px;">
              <h3 style="margin: 0 0 15px 0; color: #1f2937;">Configuration Details:</h3>
              <ul style="margin: 0; padding-left: 20px;">
                <li><strong>Host:</strong> ${process.env.PLESK_SMTP_HOST}</li>
                <li><strong>Port:</strong> ${process.env.PLESK_SMTP_PORT}</li>
                <li><strong>Secure:</strong> ${process.env.PLESK_SMTP_SECURE}</li>
                <li><strong>User:</strong> ${process.env.PLESK_SMTP_USER}</li>
                <li><strong>From:</strong> ${process.env.FROM_EMAIL}</li>
              </ul>
            </div>

            <div style="background: #eff6ff; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
              <h3 style="margin: 0 0 15px 0; color: #1e40af;">📧 Email Types Ready:</h3>
              <ul style="margin: 0; padding-left: 20px; color: #1e40af;">
                <li>Welcome emails for new users</li>
                <li>Upgrade confirmation emails</li>
                <li>Subscription renewal reminders</li>
                <li>Bank scan completion notifications</li>
              </ul>
            </div>

            <div style="text-align: center; margin-bottom: 25px;">
              <p style="color: #10b981; font-weight: 600; font-size: 18px;">🎉 Email service is ready for production!</p>
            </div>

            <div style="border-top: 1px solid #e5e7eb; padding-top: 20px; text-align: center; color: #6b7280; font-size: 14px;">
              <p>Test sent at: ${new Date().toLocaleString()}</p>
              <p>CashControl Email Service Test</p>
            </div>

          </body>
          </html>
        `,
        text: `
CashControl Email Test

✅ SMTP Working!
Your Plesk email configuration is working correctly.

Configuration Details:
- Host: ${process.env.PLESK_SMTP_HOST}
- Port: ${process.env.PLESK_SMTP_PORT}
- Secure: ${process.env.PLESK_SMTP_SECURE}
- User: ${process.env.PLESK_SMTP_USER}
- From: ${process.env.FROM_EMAIL}

📧 Email Types Ready:
- Welcome emails for new users
- Upgrade confirmation emails  
- Subscription renewal reminders
- Bank scan completion notifications

🎉 Email service is ready for production!

Test sent at: ${new Date().toLocaleString()}
CashControl Email Service Test
        `
      }
      
      const result = await transporter.sendMail(testEmail)
      console.log('✅ Test email sent successfully!')
      console.log('📧 Message ID:', result.messageId)
      console.log('📬 Check your inbox:', process.env.PLESK_SMTP_USER)
      
    } else {
      throw new Error('SMTP connection verification failed')
    }
    
    console.log('\n🎉 Email configuration test completed successfully!')
    console.log('✅ Your Plesk SMTP is ready for CashControl!')
    
  } catch (error) {
    console.error('\n❌ Email configuration test failed:')
    console.error('Error:', error.message)
    
    if (error.code) {
      console.error('Error Code:', error.code)
    }
    
    if (error.response) {
      console.error('SMTP Response:', error.response)
    }
    
    console.error('\n🔧 Troubleshooting tips:')
    console.error('1. Check that your SMTP credentials in .env.local are correct')
    console.error('2. Verify that the SMTP server (123cashcontrol.com) is accessible')
    console.error('3. Ensure SMTP is enabled in your Plesk control panel')
    console.error('4. Check if port 587 is open and not blocked by firewall')
    console.error('5. Try using port 465 with secure=true if 587 fails')
    console.error('6. Verify the email password is correct')
    
    process.exit(1)
  }
}

// Run the test
testEmailConfiguration()
