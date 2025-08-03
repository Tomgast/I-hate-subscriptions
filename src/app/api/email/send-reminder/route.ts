import { NextRequest, NextResponse } from 'next/server'
import { getServerSession } from 'next-auth'
import { authOptions } from '@/lib/auth'
import { emailService } from '@/lib/email/email-service'

export async function POST(request: NextRequest) {
  try {
    const session = await getServerSession(authOptions)
    
    if (!session?.user?.email) {
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })
    }

    const body = await request.json()
    const { subscriptionId, subscriptionName, amount, currency, renewalDate, daysUntilRenewal } = body

    // Validate required fields
    if (!subscriptionName || !amount || !renewalDate || daysUntilRenewal === undefined) {
      return NextResponse.json({ 
        error: 'Missing required fields: subscriptionName, amount, renewalDate, daysUntilRenewal' 
      }, { status: 400 })
    }

    // Prepare reminder data
    const reminderData = {
      userName: session.user.name || session.user.email.split('@')[0],
      subscriptionName,
      amount: parseFloat(amount),
      currency: currency || 'EUR',
      renewalDate,
      daysUntilRenewal: parseInt(daysUntilRenewal),
      manageUrl: `${process.env.NEXTAUTH_URL}/subscriptions/${subscriptionId || ''}`,
      cancelUrl: subscriptionId ? `${process.env.NEXTAUTH_URL}/subscriptions/${subscriptionId}/cancel` : undefined
    }

    // Send the reminder email
    const success = await emailService.sendSubscriptionReminder(
      session.user.email,
      reminderData
    )

    if (success) {
      return NextResponse.json({ 
        message: 'Reminder email sent successfully',
        recipient: session.user.email
      })
    } else {
      return NextResponse.json({ 
        error: 'Failed to send reminder email' 
      }, { status: 500 })
    }

  } catch (error) {
    console.error('Send reminder email error:', error)
    return NextResponse.json({ 
      error: 'Internal server error' 
    }, { status: 500 })
  }
}
