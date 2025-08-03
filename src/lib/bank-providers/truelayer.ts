// TrueLayer Provider Implementation for CashControl
// Handles European banking integration via TrueLayer API

import { 
  BankProvider, 
  BankProviderType, 
  StandardTransaction, 
  BankAccount, 
  BankInstitution,
  DateRange,
  LinkTokenResponse,
  AccessTokenResponse 
} from './types'

// TrueLayer API Types
interface TrueLayerTransaction {
  transaction_id: string
  account_id: string
  amount: number
  currency: string
  transaction_type: 'DEBIT' | 'CREDIT'
  transaction_category: string
  transaction_classification: string[]
  merchant_name?: string
  description: string
  timestamp: string
  running_balance?: {
    amount: number
    currency: string
  }
}

interface TrueLayerAccount {
  account_id: string
  account_type: string
  display_name: string
  currency: string
  account_number?: {
    iban?: string
    number?: string
    sort_code?: string
  }
  provider?: {
    display_name: string
    logo_uri?: string
  }
}

interface TrueLayerProvider {
  provider_id: string
  display_name: string
  logo_uri?: string
  country_code: string
}

export class TrueLayerBankProvider extends BankProvider {
  name: BankProviderType = 'truelayer'
  supportedCountries = ['GB', 'IE', 'FR', 'DE', 'ES', 'IT', 'NL', 'BE', 'PT', 'PL']
  
  private clientId: string
  private clientSecret: string
  private baseUrl = 'https://api.truelayer-sandbox.com'
  private authUrl = 'https://auth.truelayer-sandbox.com'

  constructor() {
    super()
    this.clientId = process.env.TRUELAYER_CLIENT_ID || ''
    this.clientSecret = process.env.TRUELAYER_CLIENT_SECRET || ''
    
    if (!this.clientId || !this.clientSecret) {
      console.warn('TrueLayer credentials not configured')
    }
  }

  isAvailable(countryCode: string): boolean {
    return this.supportedCountries.includes(countryCode.toUpperCase())
  }

  async createLinkToken(userId: string, countryCode = 'GB'): Promise<LinkTokenResponse> {
    try {
      // TrueLayer uses OAuth flow, so we create an authorization URL instead of a link token
      const redirectUri = `${process.env.NEXTAUTH_URL}/api/bank-providers/truelayer/callback`
      const scopes = 'info accounts balance cards transactions direct_debits standing_orders offline_access'
      const providers = 'uk-cs-mock uk-ob-all uk-oauth-all'
      const state = `${userId}_${Date.now()}` // Include user ID in state for callback
      
      const authUrl = new URL(`${this.authUrl}/`)
      authUrl.searchParams.set('response_type', 'code')
      authUrl.searchParams.set('client_id', this.clientId)
      authUrl.searchParams.set('scope', scopes)
      authUrl.searchParams.set('redirect_uri', redirectUri)
      authUrl.searchParams.set('providers', providers)
      authUrl.searchParams.set('state', state)

      return {
        link_token: authUrl.toString(),
        expiration: new Date(Date.now() + 3600000).toISOString(), // 1 hour
        request_id: state
      }
    } catch (error) {
      console.error('TrueLayer createLinkToken error:', error)
      throw new Error('Failed to create TrueLayer link token')
    }
  }

  async exchangePublicToken(authCode: string): Promise<AccessTokenResponse> {
    try {
      const response = await fetch(`${this.authUrl}/connect/token`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          grant_type: 'authorization_code',
          client_id: this.clientId,
          client_secret: this.clientSecret,
          redirect_uri: `${process.env.NEXTAUTH_URL}/api/bank-providers/truelayer/callback`,
          code: authCode
        })
      })

      if (!response.ok) {
        throw new Error(`TrueLayer token exchange failed: ${response.statusText}`)
      }

      const data = await response.json()
      
      return {
        access_token: data.access_token,
        request_id: data.refresh_token // Store refresh token as request_id for later use
      }
    } catch (error) {
      console.error('TrueLayer exchangePublicToken error:', error)
      throw new Error('Failed to exchange TrueLayer authorization code')
    }
  }

  async getAccounts(accessToken: string): Promise<BankAccount[]> {
    try {
      const response = await fetch(`${this.baseUrl}/data/v1/accounts`, {
        headers: {
          'Authorization': `Bearer ${accessToken}`,
          'Content-Type': 'application/json'
        }
      })

      if (!response.ok) {
        throw new Error(`TrueLayer getAccounts failed: ${response.statusText}`)
      }

      const data = await response.json()
      
      return data.results.map((account: TrueLayerAccount): BankAccount => ({
        id: account.account_id,
        name: account.display_name,
        type: this.mapAccountType(account.account_type),
        currency: account.currency,
        provider: this.name
      }))
    } catch (error) {
      console.error('TrueLayer getAccounts error:', error)
      throw new Error('Failed to fetch TrueLayer accounts')
    }
  }

  async getTransactions(
    accessToken: string, 
    accountIds: string[], 
    dateRange: DateRange
  ): Promise<StandardTransaction[]> {
    try {
      const allTransactions: StandardTransaction[] = []

      for (const accountId of accountIds) {
        const url = new URL(`${this.baseUrl}/data/v1/accounts/${accountId}/transactions`)
        url.searchParams.set('from', dateRange.startDate)
        url.searchParams.set('to', dateRange.endDate)

        const response = await fetch(url.toString(), {
          headers: {
            'Authorization': `Bearer ${accessToken}`,
            'Content-Type': 'application/json'
          }
        })

        if (!response.ok) {
          console.warn(`Failed to fetch transactions for account ${accountId}: ${response.statusText}`)
          continue
        }

        const data = await response.json()
        
        const transactions = data.results.map((tx: TrueLayerTransaction): StandardTransaction => ({
          id: tx.transaction_id,
          account_id: tx.account_id,
          amount: tx.transaction_type === 'DEBIT' ? -Math.abs(tx.amount) : Math.abs(tx.amount),
          date: tx.timestamp.split('T')[0], // Convert to YYYY-MM-DD
          description: tx.description,
          merchant_name: tx.merchant_name,
          category: tx.transaction_classification || [tx.transaction_category],
          provider: this.name,
          currency: tx.currency,
          balance: tx.running_balance?.amount
        }))

        allTransactions.push(...transactions)
      }

      return allTransactions.sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime())
    } catch (error) {
      console.error('TrueLayer getTransactions error:', error)
      throw new Error('Failed to fetch TrueLayer transactions')
    }
  }

  async getInstitutions(countryCode: string): Promise<BankInstitution[]> {
    try {
      // TrueLayer doesn't have a public institutions endpoint, so we'll return common ones
      const commonInstitutions = this.getCommonInstitutions(countryCode)
      return commonInstitutions
    } catch (error) {
      console.error('TrueLayer getInstitutions error:', error)
      return []
    }
  }

  private mapAccountType(trueLayerType: string): BankAccount['type'] {
    const typeMap: Record<string, BankAccount['type']> = {
      'TRANSACTION': 'checking',
      'SAVINGS': 'savings',
      'CREDIT_CARD': 'credit',
      'INVESTMENT': 'investment'
    }
    return typeMap[trueLayerType] || 'other'
  }

  private getProvidersForCountry(countryCode: string): string {
    // TrueLayer provider IDs for different countries
    const providerMap: Record<string, string> = {
      'GB': 'uk-ob-all uk-oauth-all',
      'IE': 'ie-ob-all',
      'FR': 'fr-ob-all',
      'DE': 'de-ob-all',
      'ES': 'es-ob-all',
      'IT': 'it-ob-all',
      'NL': 'nl-ob-all',
      'BE': 'be-ob-all',
      'PT': 'pt-ob-all',
      'PL': 'pl-ob-all'
    }
    return providerMap[countryCode.toUpperCase()] || 'uk-ob-all'
  }

  private getCommonInstitutions(countryCode: string): BankInstitution[] {
    const institutionMap: Record<string, BankInstitution[]> = {
      'GB': [
        { id: 'hsbc-gb', name: 'HSBC UK', country: 'GB', provider: this.name },
        { id: 'barclays-gb', name: 'Barclays', country: 'GB', provider: this.name },
        { id: 'lloyds-gb', name: 'Lloyds Bank', country: 'GB', provider: this.name },
        { id: 'natwest-gb', name: 'NatWest', country: 'GB', provider: this.name },
        { id: 'santander-gb', name: 'Santander UK', country: 'GB', provider: this.name }
      ],
      'DE': [
        { id: 'deutsche-bank-de', name: 'Deutsche Bank', country: 'DE', provider: this.name },
        { id: 'commerzbank-de', name: 'Commerzbank', country: 'DE', provider: this.name },
        { id: 'ing-de', name: 'ING Germany', country: 'DE', provider: this.name },
        { id: 'dkb-de', name: 'DKB', country: 'DE', provider: this.name }
      ],
      'FR': [
        { id: 'bnp-paribas-fr', name: 'BNP Paribas', country: 'FR', provider: this.name },
        { id: 'credit-agricole-fr', name: 'Crédit Agricole', country: 'FR', provider: this.name },
        { id: 'societe-generale-fr', name: 'Société Générale', country: 'FR', provider: this.name }
      ],
      'NL': [
        { id: 'ing-nl', name: 'ING Netherlands', country: 'NL', provider: this.name },
        { id: 'abn-amro-nl', name: 'ABN AMRO', country: 'NL', provider: this.name },
        { id: 'rabobank-nl', name: 'Rabobank', country: 'NL', provider: this.name }
      ]
    }
    
    return institutionMap[countryCode.toUpperCase()] || []
  }
}
