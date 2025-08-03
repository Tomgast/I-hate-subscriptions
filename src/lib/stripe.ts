import Stripe from 'stripe'
import { loadStripe } from '@stripe/stripe-js'

// Server-side Stripe client
export const stripe = new Stripe(process.env.STRIPE_SECRET_KEY!, {
  // Using default API version for compatibility
})

// Client-side Stripe client
let stripePromise: Promise<any> | null = null

export const getStripe = () => {
  if (!stripePromise) {
    const publishableKey = process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY
    if (!publishableKey) {
      throw new Error('NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY is not defined')
    }
    stripePromise = loadStripe(publishableKey)
  }
  return stripePromise
}

// Stripe configuration
export const STRIPE_CONFIG = {
  LIFETIME_ACCESS_PRICE: 2900, // $29.00 in cents
  CURRENCY: 'usd',
  PRODUCT_NAME: 'I Hate Subscriptions - Lifetime Access',
  PRODUCT_DESCRIPTION: 'One-time payment for lifetime access to subscription management tools',
}
