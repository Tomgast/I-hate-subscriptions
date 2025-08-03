// Bank Provider Types for CashControl
// Unified interface for multiple banking providers (Plaid, TrueLayer, Nordigen, etc.)

export interface StandardTransaction {
  id: string
  account_id: string
  amount: number
  date: string
  description: string
  merchant_name?: string
  category: string[]
  provider: BankProviderType
  currency?: string
  balance?: number
}

export interface BankAccount {
  id: string
  name: string
  type: 'checking' | 'savings' | 'credit' | 'investment' | 'other'
  balance?: number
  currency: string
  provider: BankProviderType
}

export interface BankInstitution {
  id: string
  name: string
  country: string
  logo_url?: string
  provider: BankProviderType
}

export type BankProviderType = 'plaid' | 'truelayer' | 'nordigen' | 'yapily'

export interface DateRange {
  startDate: string // YYYY-MM-DD format
  endDate: string   // YYYY-MM-DD format
}

export interface LinkTokenResponse {
  link_token: string
  expiration: string
  request_id?: string
}

export interface AccessTokenResponse {
  access_token: string
  item_id?: string
  request_id?: string
}

export abstract class BankProvider {
  abstract name: BankProviderType
  abstract supportedCountries: string[]
  
  abstract isAvailable(countryCode: string): boolean
  abstract createLinkToken(userId: string, countryCode?: string): Promise<LinkTokenResponse>
  abstract exchangePublicToken(publicToken: string): Promise<AccessTokenResponse>
  abstract getAccounts(accessToken: string): Promise<BankAccount[]>
  abstract getTransactions(
    accessToken: string, 
    accountIds: string[], 
    dateRange: DateRange
  ): Promise<StandardTransaction[]>
  abstract getInstitutions(countryCode: string): Promise<BankInstitution[]>
}

export interface BankConnectionConfig {
  userId: string
  countryCode: string
  preferredProvider?: BankProviderType
}

export interface BankScanResult {
  provider: BankProviderType
  accounts: BankAccount[]
  transactions: StandardTransaction[]
  detectedSubscriptions: any[] // Will use DetectedSubscription from bank-scan.ts
  analysisDateRange: DateRange
  totalTransactionsAnalyzed: number
}
