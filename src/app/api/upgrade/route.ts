import { NextRequest, NextResponse } from 'next/server'
import { getServerSession } from 'next-auth'
import { authOptions } from '@/lib/auth'
import { emailService } from '@/lib/email/email-service'
import { dbAdapter } from '@/lib/database/adapter'

export async function POST(request: NextRequest) {
  try {
    const session = await getServerSession(authOptions)
    
    if (!session?.user?.email) {
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })
    }

    const { userId, plan } = await request.json()
    const userEmail = session.user.email
    
    // Update user payment status in MySQL database
    let user = await dbAdapter.getUserByEmail(userEmail)
    
    if (!user) {
      // Create new user if doesn't exist
      user = await dbAdapter.createUser({
        email: userEmail,
        name: session.user.name || 'User',
        is_paid: plan === 'pro'
      })
      
      // Create default user preferences
      await dbAdapter.createUserPreferences(user.id, {})
    } else {
      // Update existing user's payment status
      user = await dbAdapter.updateUser(user.id, {
        is_paid: plan === 'pro'
      })
    }

    // Send email notification for Pro upgrade
    if (plan === 'pro') {
      try {
        const userName = session.user.name || session.user.email.split('@')[0]
        await emailService.sendUpgradeConfirmation(userEmail, userName)
        console.log('✅ Upgrade confirmation email sent to:', userEmail)
      } catch (emailError) {
        console.error('❌ Failed to send upgrade email:', emailError)
        // Don't fail the upgrade if email fails
      }
    }

    return NextResponse.json({ 
      success: true, 
      message: `${plan === 'pro' ? 'Upgrade' : 'Account setup'} successful`,
      user: {
        id: user!.id,
        email: user!.email,
        name: user!.name,
        isPaid: user!.is_paid,
        userTier: user!.is_paid ? 'pro' : 'free',
        subscriptionLimit: user!.is_paid ? -1 : 5,
        createdAt: user!.created_at
      }
    })
  } catch (error) {
    console.error('Upgrade error:', error)
    return NextResponse.json({ error: 'Internal server error' }, { status: 500 })
  }
}

export async function GET(request: NextRequest) {
  try {
    const session = await getServerSession(authOptions)
    
    if (!session?.user?.email) {
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })
    }

    const user = await dbAdapter.getUserByEmail(session.user.email)
    
    return NextResponse.json({
      isPaid: user?.is_paid || false,
      userTier: user?.is_paid ? 'pro' : 'free',
      subscriptionLimit: user?.is_paid ? -1 : 5,
      paidAt: user?.updated_at
    })
  } catch (error) {
    console.error('Get upgrade status error:', error)
    return NextResponse.json({ error: 'Internal server error' }, { status: 500 })
  }
}
