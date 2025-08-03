import { NextRequest, NextResponse } from 'next/server'
import { getServerSession } from 'next-auth'
import { authOptions } from '@/lib/auth'
import { DetectedSubscription } from '@/lib/bank-scan'

// Mock database for demo - replace with actual database integration
const mockSubscriptions = new Map<string, any[]>()

export async function POST(request: NextRequest) {
  try {
    const session = await getServerSession(authOptions)
    
    if (!session?.user?.email) {
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })
    }

    const { confirmedSubscriptions }: { confirmedSubscriptions: DetectedSubscription[] } = await request.json()

    if (!confirmedSubscriptions || !Array.isArray(confirmedSubscriptions)) {
      return NextResponse.json({ error: 'Invalid subscription data' }, { status: 400 })
    }

    const userEmail = session.user.email
    const addedSubscriptions = []

    // Get existing subscriptions for this user
    const existingSubscriptions = mockSubscriptions.get(userEmail) || []

    for (const detectedSub of confirmedSubscriptions) {
      // Convert detected subscription to our subscription format
      const newSubscription = {
        id: `scan-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
        user_id: userEmail,
        name: detectedSub.merchant_name,
        description: `Automatically detected from bank transactions`,
        cost: detectedSub.amount,
        currency: 'USD',
        billing_cycle: detectedSub.frequency === 'yearly' ? 'yearly' : 'monthly',
        next_billing_date: detectedSub.next_billing_date,
        status: 'active',
        category: detectedSub.category,
        website_url: detectedSub.website_url || '',
        logo_url: detectedSub.logo_url || '',
        notes: `Detected with ${Math.round(detectedSub.confidence * 100)}% confidence from ${detectedSub.transactions.length} transactions`,
        reminder_days: 3,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString(),
        source: 'bank_scan' // Mark as bank-detected
      }

      // Check if subscription already exists (avoid duplicates)
      const isDuplicate = existingSubscriptions.some(existing => 
        existing.name.toLowerCase() === newSubscription.name.toLowerCase() &&
        Math.abs(existing.cost - newSubscription.cost) < 0.01
      )

      if (!isDuplicate) {
        existingSubscriptions.push(newSubscription)
        addedSubscriptions.push(newSubscription)
      }
    }

    // Update mock database
    mockSubscriptions.set(userEmail, existingSubscriptions)

    return NextResponse.json({
      success: true,
      message: `Successfully added ${addedSubscriptions.length} subscriptions`,
      addedSubscriptions,
      totalSubscriptions: existingSubscriptions.length
    })

  } catch (error) {
    console.error('Confirm subscriptions error:', error)
    return NextResponse.json({ 
      error: 'Failed to add subscriptions',
      details: error instanceof Error ? error.message : 'Unknown error'
    }, { status: 500 })
  }
}

// GET endpoint to retrieve user's subscriptions (including bank-scanned ones)
export async function GET(request: NextRequest) {
  try {
    const session = await getServerSession(authOptions)
    
    if (!session?.user?.email) {
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })
    }

    const userEmail = session.user.email
    const subscriptions = mockSubscriptions.get(userEmail) || []

    return NextResponse.json({
      subscriptions,
      total: subscriptions.length,
      bankScanned: subscriptions.filter(sub => sub.source === 'bank_scan').length
    })

  } catch (error) {
    console.error('Get subscriptions error:', error)
    return NextResponse.json({ error: 'Internal server error' }, { status: 500 })
  }
}
