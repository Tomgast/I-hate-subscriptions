import { NextRequest, NextResponse } from 'next/server'
import { headers } from 'next/headers'
import Stripe from 'stripe'
import { databaseAdapter, getDatabaseType } from '@/lib/database'

// Initialize Stripe with the secret key
const stripe = new Stripe(process.env.STRIPE_SECRET_KEY || '', {
  apiVersion: '2025-07-30.basil',
})

const webhookSecret = process.env.STRIPE_WEBHOOK_SECRET

export async function POST(request: NextRequest) {
  try {
    const body = await request.text()
    const signature = headers().get('stripe-signature') || ''

    if (!webhookSecret) {
      console.error('Missing Stripe webhook secret')
      return NextResponse.json({ error: 'Webhook secret missing' }, { status: 500 })
    }

    // Verify the webhook signature
    let event: Stripe.Event
    try {
      event = stripe.webhooks.constructEvent(body, signature, webhookSecret)
    } catch (err) {
      console.error(`Webhook signature verification failed: ${err}`)
      return NextResponse.json({ error: 'Invalid signature' }, { status: 400 })
    }

    // Handle the event
    if (event.type === 'checkout.session.completed') {
      const session = event.data.object as Stripe.Checkout.Session
      
      // Get the user email from the session
      const userEmail = session.customer_email || session.client_reference_id
      
      if (!userEmail) {
        console.error('No user email found in session')
        return NextResponse.json({ error: 'No user email found' }, { status: 400 })
      }

      console.log(`Processing payment for user: ${userEmail} using ${getDatabaseType()} database`)
      
      // Update user payment status in database (local or production)
      let user = await databaseAdapter.getUserByEmail(userEmail)
      
      if (!user) {
        // Create new user if doesn't exist
        user = await databaseAdapter.createUser({
          email: userEmail,
          name: userEmail.split('@')[0], // Use email prefix as name
          is_paid: true
        })
        
        // Create default user preferences
        await databaseAdapter.createUserPreferences(user.id, {})
        console.log(`Created new Pro user: ${userEmail}`)
      } else {
        // Update existing user's payment status
        user = await databaseAdapter.updateUser(user.id, {
          is_paid: true
        })
        console.log(`Updated existing user to Pro: ${userEmail}`)
      }
      
      if (!user) {
        console.error('Failed to create/update user payment status')
        return NextResponse.json({ error: 'Failed to update user status' }, { status: 500 })
      }
      
      console.log(`Successfully updated payment status for user: ${userEmail}`)
      
      // Send confirmation email (optional)
      try {
        // Import email service dynamically to avoid circular dependencies
        const { emailService } = await import('@/lib/email/email-service')
        await emailService.sendUpgradeConfirmation(
          userEmail, 
          user.name || userEmail.split('@')[0]
        )
        console.log('✅ Upgrade confirmation email sent to:', userEmail)
      } catch (emailError) {
        console.error('❌ Failed to send upgrade email:', emailError)
        // Don't fail the webhook if email fails
      }
    }

    return NextResponse.json({ received: true })
  } catch (error) {
    console.error('Stripe webhook error:', error)
    return NextResponse.json({ error: 'Webhook handler failed' }, { status: 500 })
  }
}
