import { NextRequest, NextResponse } from 'next/server'
import { headers } from 'next/headers'
import Stripe from 'stripe'
import { updateUserPaymentStatus } from '@/lib/auth'
import { createServerClient } from '@/lib/supabase'

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

      console.log(`Processing payment for user: ${userEmail}`)
      
      // Update user payment status in Supabase
      const supabase = createServerClient()
      const { data, error } = await supabase
        .from('user_profiles')
        .update({ 
          has_paid: true,
          payment_date: new Date().toISOString()
        })
        .eq('email', userEmail)
        .select()
      
      if (error) {
        console.error('Error updating user payment status:', error)
        return NextResponse.json({ error: 'Failed to update user status' }, { status: 500 })
      }
      
      console.log(`Successfully updated payment status for user: ${userEmail}`)
      
      // Send confirmation email (optional)
      try {
        // Import email service dynamically to avoid circular dependencies
        const { emailService } = await import('@/lib/email/email-service')
        await emailService.sendUpgradeConfirmation(
          userEmail, 
          data?.[0]?.full_name || userEmail.split('@')[0]
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
