import { NextRequest, NextResponse } from 'next/server'
import { getServerSession } from 'next-auth'
import { authOptions } from '@/lib/auth'
import { emailService } from '@/lib/email/email-service'
import { emailScheduler } from '@/lib/email/email-scheduler'

export async function POST(request: NextRequest) {
  try {
    const session = await getServerSession(authOptions)
    
    if (!session?.user?.email) {
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })
    }

    const body = await request.json()
    const { type } = body

    const userName = session.user.name || session.user.email.split('@')[0]
    let success = false
    let message = ''

    switch (type) {
      case 'welcome':
        success = await emailService.sendWelcomeEmail(session.user.email, userName)
        message = 'Welcome email sent'
        break

      case 'upgrade':
        success = await emailService.sendUpgradeConfirmation(session.user.email, userName)
        message = 'Upgrade confirmation email sent'
        break

      case 'reminder':
        success = await emailScheduler.sendTestReminder(
          session.user.email,
          userName,
          'Netflix',
          15.99,
          'EUR'
        )
        message = 'Test renewal reminder sent'
        break

      case 'bank-scan':
        success = await emailService.sendBankScanComplete(session.user.email, userName, 3)
        message = 'Bank scan completion email sent'
        break

      default:
        return NextResponse.json({ 
          error: 'Invalid email type. Use: welcome, upgrade, reminder, or bank-scan' 
        }, { status: 400 })
    }

    if (success) {
      return NextResponse.json({ 
        message,
        recipient: session.user.email,
        type
      })
    } else {
      return NextResponse.json({ 
        error: `Failed to send ${type} email` 
      }, { status: 500 })
    }

  } catch (error) {
    console.error('Test email error:', error)
    return NextResponse.json({ 
      error: 'Internal server error' 
    }, { status: 500 })
  }
}
