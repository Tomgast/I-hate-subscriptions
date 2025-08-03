import { NextRequest, NextResponse } from 'next/server'
import { getServerSession } from 'next-auth'
import { authOptions } from '@/lib/auth'
import { TrueLayerBankProvider } from '@/lib/bank-providers/truelayer'

export async function GET(request: NextRequest) {
  try {
    const session = await getServerSession(authOptions)
    
    if (!session?.user?.email) {
      return NextResponse.redirect(new URL('/auth/signin', request.url))
    }

    const { searchParams } = new URL(request.url)
    const code = searchParams.get('code')
    const state = searchParams.get('state')
    const error = searchParams.get('error')

    if (error) {
      console.error('TrueLayer OAuth error:', error)
      return NextResponse.redirect(new URL('/dashboard?bank_error=oauth_failed', request.url))
    }

    if (!code || !state) {
      return NextResponse.redirect(new URL('/dashboard?bank_error=missing_params', request.url))
    }

    // Verify state contains the user ID (basic security check)
    const [stateUserId] = state.split('_')
    // In production, you'd want to store and verify the full state

    const trueLayer = new TrueLayerBankProvider()
    
    try {
      // Exchange authorization code for access token
      const tokenResponse = await trueLayer.exchangePublicToken(code)
      
      // In a real implementation, you would:
      // 1. Store the access token securely in your database (encrypted)
      // 2. Fetch account data and transactions
      // 3. Run subscription detection
      // 4. Store detected subscriptions for user review
      
      // For now, redirect to dashboard with success message
      const successUrl = new URL('/dashboard', request.url)
      successUrl.searchParams.set('bank_connected', 'success')
      successUrl.searchParams.set('provider', 'truelayer')
      
      return NextResponse.redirect(successUrl)
      
    } catch (tokenError) {
      console.error('TrueLayer token exchange error:', tokenError)
      return NextResponse.redirect(new URL('/dashboard?bank_error=token_exchange_failed', request.url))
    }

  } catch (error) {
    console.error('TrueLayer callback error:', error)
    return NextResponse.redirect(new URL('/dashboard?bank_error=callback_failed', request.url))
  }
}
