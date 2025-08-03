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

    const userName = session.user.name || session.user.email.split('@')[0]

    // Send upgrade confirmation email
    const success = await emailService.sendUpgradeConfirmation(
      session.user.email,
      userName
    )

    if (success) {
      return NextResponse.json({ 
        message: 'Upgrade confirmation email sent successfully',
        recipient: session.user.email
      })
    } else {
      return NextResponse.json({ 
        error: 'Failed to send upgrade confirmation email' 
      }, { status: 500 })
    }

  } catch (error) {
    console.error('Send upgrade email error:', error)
    return NextResponse.json({ 
      error: 'Internal server error' 
    }, { status: 500 })
  }
}
