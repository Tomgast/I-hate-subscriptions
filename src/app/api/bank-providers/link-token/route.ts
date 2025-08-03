import { NextRequest, NextResponse } from 'next/server'
import { getServerSession } from 'next-auth'
import { authOptions } from '@/lib/auth'

export async function POST(request: NextRequest) {
  try {
    const session = await getServerSession(authOptions)
    
    if (!session?.user?.email) {
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })
    }

    const { provider, countryCode } = await request.json()

    // For now, we'll focus on TrueLayer
    if (provider === 'truelayer') {
      const clientId = process.env.TRUELAYER_CLIENT_ID
      const redirectUri = process.env.TRUELAYER_REDIRECT_URI || 
        `${request.nextUrl.origin}/api/bank-providers/truelayer/callback`

      if (!clientId) {
        return NextResponse.json({ 
          error: 'TrueLayer not configured. Please add TRUELAYER_CLIENT_ID to environment variables.' 
        }, { status: 500 })
      }

      // Debug logging
      console.log('TrueLayer Configuration:')
      console.log('- Client ID:', clientId)
      console.log('- Redirect URI:', redirectUri)
      console.log('- Environment:', process.env.TRUELAYER_ENVIRONMENT)

      // Create TrueLayer authorization URL
      const state = `${session.user.email}_${Date.now()}`
      const scopes = 'info accounts balance cards transactions direct_debits standing_orders offline_access'
      const providers = 'uk-cs-mock uk-ob-all uk-oauth-all'
      
      const authUrl = new URL('https://auth.truelayer-sandbox.com')
      authUrl.searchParams.set('response_type', 'code')
      authUrl.searchParams.set('client_id', clientId)
      authUrl.searchParams.set('scope', scopes)
      authUrl.searchParams.set('redirect_uri', redirectUri)
      authUrl.searchParams.set('providers', providers)
      authUrl.searchParams.set('state', state)

      return NextResponse.json({
        authUrl: authUrl.toString(),
        provider: 'truelayer',
        state
      })
    }

    // For other providers, return placeholder
    return NextResponse.json({
      error: `Provider ${provider} not yet implemented`,
      supportedProviders: ['truelayer']
    }, { status: 400 })

  } catch (error) {
    console.error('Link token creation error:', error)
    return NextResponse.json({ 
      error: 'Failed to create link token' 
    }, { status: 500 })
  }
}
