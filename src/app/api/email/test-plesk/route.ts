// API endpoint to test Plesk email configuration
import { NextRequest, NextResponse } from 'next/server'
import { emailService } from '@/lib/email/email-service'

export async function GET(request: NextRequest) {
  try {
    const { searchParams } = new URL(request.url)
    const testEmail = searchParams.get('email') || 'test@example.com'
    
    console.log('📧 Testing Plesk email configuration...')
    
    // Test basic email sending
    const emailSent = await emailService.sendEmail(
      testEmail,
      'CashControl - Plesk Email Test',
      `
      <h2>🎉 Email Configuration Test</h2>
      <p>This is a test email from your CashControl application using Plesk SMTP.</p>
      <p><strong>Configuration Details:</strong></p>
      <ul>
        <li>SMTP Host: ${process.env.PLESK_SMTP_HOST}</li>
        <li>SMTP Port: ${process.env.PLESK_SMTP_PORT}</li>
        <li>SMTP Secure: ${process.env.PLESK_SMTP_SECURE}</li>
        <li>From Email: ${process.env.FROM_EMAIL}</li>
      </ul>
      <p>If you received this email, your Plesk email configuration is working correctly!</p>
      `,
      'CashControl Email Test - If you received this email, your Plesk email configuration is working correctly!'
    )

    if (emailSent) {
      return NextResponse.json({
        success: true,
        message: 'Test email sent successfully',
        details: {
          recipient: testEmail,
          smtpHost: process.env.PLESK_SMTP_HOST,
          smtpPort: process.env.PLESK_SMTP_PORT,
          fromEmail: process.env.FROM_EMAIL
        }
      })
    } else {
      return NextResponse.json({
        success: false,
        error: 'Failed to send test email'
      }, { status: 500 })
    }

  } catch (error: any) {
    console.error('❌ Email test failed:', error)
    
    return NextResponse.json({
      success: false,
      error: 'Email test failed',
      details: error.message
    }, { status: 500 })
  }
}

export async function POST(request: NextRequest) {
  try {
    const body = await request.json()
    const { email, type = 'welcome', userName = 'Test User' } = body

    if (!email) {
      return NextResponse.json({
        success: false,
        error: 'Email address is required'
      }, { status: 400 })
    }

    console.log(`📧 Testing ${type} email template...`)
    
    let emailSent = false
    
    switch (type) {
      case 'welcome':
        emailSent = await emailService.sendWelcomeEmail(email, userName)
        break
      case 'upgrade':
        emailSent = await emailService.sendUpgradeConfirmation(email, userName)
        break
      case 'bank-scan':
        emailSent = await emailService.sendBankScanComplete(email, userName, 3)
        break
      case 'reminder':
        emailSent = await emailService.sendSubscriptionReminder(email, {
          userName,
          subscriptionName: 'Netflix',
          amount: 12.99,
          currency: 'EUR',
          renewalDate: '2024-02-15',
          daysUntilRenewal: 3,
          cancelUrl: 'https://netflix.com/cancel',
          manageUrl: 'https://netflix.com/account'
        })
        break
      default:
        return NextResponse.json({
          success: false,
          error: 'Invalid email type. Use: welcome, upgrade, bank-scan, or reminder'
        }, { status: 400 })
    }

    if (emailSent) {
      return NextResponse.json({
        success: true,
        message: `${type} email sent successfully`,
        details: {
          recipient: email,
          emailType: type,
          userName
        }
      })
    } else {
      return NextResponse.json({
        success: false,
        error: `Failed to send ${type} email`
      }, { status: 500 })
    }

  } catch (error: any) {
    console.error('❌ Email template test failed:', error)
    
    return NextResponse.json({
      success: false,
      error: 'Email template test failed',
      details: error.message
    }, { status: 500 })
  }
}
