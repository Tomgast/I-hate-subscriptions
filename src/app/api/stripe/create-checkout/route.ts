import { NextRequest, NextResponse } from 'next/server'
import { getServerSession } from 'next-auth'
import { authOptions } from '@/lib/auth'
import Stripe from 'stripe'

// Initialize Stripe with the secret key
const stripe = new Stripe(process.env.STRIPE_SECRET_KEY || '', {
  apiVersion: '2025-07-30.basil', // Use the latest stable API version
})

export async function POST(request: NextRequest) {
  try {
    const session = await getServerSession(authOptions)
    
    if (!session?.user?.email) {
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })
    }

    const { userId, plan, successUrl, cancelUrl } = await request.json()
    
    // Verify the user is the same as the logged-in user
    if (userId !== session.user.email) {
      return NextResponse.json({ error: 'User mismatch' }, { status: 403 })
    }

    // Create a Stripe checkout session
    const checkoutSession = await stripe.checkout.sessions.create({
      payment_method_types: ['card'],
      line_items: [
        {
          price_data: {
            currency: 'usd',
            product_data: {
              name: 'CashControl Pro',
              description: 'One-time payment for lifetime access to CashControl Pro',
              images: ['https://cashcontrol.app/images/logo.png'], // Replace with your actual logo URL
            },
            unit_amount: 2999, // $29.99 in cents
          },
          quantity: 1,
        },
      ],
      mode: 'payment',
      success_url: successUrl,
      cancel_url: cancelUrl,
      customer_email: session.user.email,
      client_reference_id: session.user.email,
      metadata: {
        userId: session.user.email,
        plan: 'pro',
      },
    })

    return NextResponse.json({ 
      checkoutUrl: checkoutSession.url,
      sessionId: checkoutSession.id
    })
  } catch (error) {
    console.error('Stripe checkout error:', error)
    return NextResponse.json({ 
      error: 'Failed to create checkout session' 
    }, { status: 500 })
  }
}
