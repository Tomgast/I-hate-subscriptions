import { NextRequest, NextResponse } from 'next/server'
import { getServerSession } from 'next-auth'
import { authOptions } from '@/lib/auth'
import { emailService } from '@/lib/email/email-service'

// In-memory user storage for demo (replace with database in production)
const users = new Map<string, {
  id: string
  email: string
  name: string
  hashedPassword?: string
  isPaid: boolean
  userTier: 'free' | 'pro'
  subscriptionLimit: number
  createdAt: string
  paidAt?: string
}>()

export async function POST(request: NextRequest) {
  try {
    const session = await getServerSession(authOptions)
    
    if (!session?.user?.email) {
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })
    }

    const { userId, plan } = await request.json()
    const userEmail = session.user.email
    
    // In a real implementation, this would:
    // 1. Verify payment with Stripe/PayPal webhook
    // 2. Update user record in database
    // 3. Send confirmation email
    
    // For demo purposes, we'll create/update the user with pro tier
    let user = users.get(userEmail)
    if (!user) {
      user = {
        id: userEmail,
        email: userEmail,
        name: session.user.name || 'User',
        isPaid: plan === 'pro',
        userTier: plan === 'pro' ? 'pro' : 'free',
        subscriptionLimit: plan === 'pro' ? -1 : 5, // -1 means unlimited
        createdAt: new Date().toISOString(),
        paidAt: plan === 'pro' ? new Date().toISOString() : undefined
      }
    } else {
      user.isPaid = plan === 'pro'
      user.userTier = plan === 'pro' ? 'pro' : 'free'
      user.subscriptionLimit = plan === 'pro' ? -1 : 5
      if (plan === 'pro') {
        user.paidAt = new Date().toISOString()
      }
    }
    
    users.set(userEmail, user)

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
        ...user,
        hashedPassword: undefined // Don't send password hash
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

    const user = users.get(session.user.email)
    
    return NextResponse.json({
      isPaid: user?.isPaid || false,
      userTier: user?.userTier || 'free',
      subscriptionLimit: user?.subscriptionLimit || 5,
      paidAt: user?.paidAt
    })
  } catch (error) {
    console.error('Get upgrade status error:', error)
    return NextResponse.json({ error: 'Internal server error' }, { status: 500 })
  }
}
