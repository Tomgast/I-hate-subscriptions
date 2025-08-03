import { NextRequest, NextResponse } from 'next/server'
import { getServerSession } from 'next-auth'
import { authOptions } from '@/lib/auth'
import { renewalReminderService } from '@/lib/email/renewal-reminder-service'

export async function POST(request: NextRequest) {
  try {
    const session = await getServerSession(authOptions)
    
    if (!session?.user?.email) {
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })
    }

    const body = await request.json()
    const { subscriptions, userSettings } = body

    // Validate required fields
    if (!subscriptions || !Array.isArray(subscriptions)) {
      return NextResponse.json({ 
        error: 'Missing or invalid subscriptions array' 
      }, { status: 400 })
    }

    // Set user email preferences if provided
    if (userSettings) {
      renewalReminderService.setUserSettings(session.user.email, userSettings)
    }

    // Schedule reminders for each subscription
    let scheduledCount = 0
    for (const subscription of subscriptions) {
      if (subscription.isActive !== false) { // Default to active if not specified
        const subscriptionData = {
          ...subscription,
          userId: session.user.email,
          userEmail: session.user.email,
          isActive: true
        }
        
        renewalReminderService.scheduleSubscriptionReminders(subscriptionData)
        scheduledCount++
      }
    }

    return NextResponse.json({ 
      message: `Scheduled reminders for ${scheduledCount} subscriptions`,
      scheduledCount,
      activeCount: subscriptions.length
    })

  } catch (error) {
    console.error('Schedule reminders error:', error)
    return NextResponse.json({ 
      error: 'Internal server error' 
    }, { status: 500 })
  }
}

export async function GET(request: NextRequest) {
  try {
    const session = await getServerSession(authOptions)
    
    if (!session?.user?.email) {
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })
    }

    // Get reminder statistics for the user
    const stats = renewalReminderService.getReminderStats(session.user.email)
    const settings = renewalReminderService.getUserSettings(session.user.email)

    return NextResponse.json({
      stats,
      settings,
      userId: session.user.email
    })

  } catch (error) {
    console.error('Get reminder stats error:', error)
    return NextResponse.json({ 
      error: 'Internal server error' 
    }, { status: 500 })
  }
}
