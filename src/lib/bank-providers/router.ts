// Bank Provider Router for CashControl
// Automatically selects the best banking provider based on user's country

import { BankProvider, BankProviderType, BankConnectionConfig } from './types'
import { TrueLayerBankProvider } from './truelayer'

// Mock Plaid provider for existing US/Canada support
class PlaidBankProvider extends BankProvider {
  name: BankProviderType = 'plaid'
  supportedCountries = ['US', 'CA']
  
  isAvailable(countryCode: string): boolean {
    return this.supportedCountries.includes(countryCode.toUpperCase())
  }

  async createLinkToken(userId: string, countryCode?: string): Promise<any> {
    // This would use your existing Plaid implementation
    throw new Error('Plaid implementation should use existing code')
  }

  async exchangePublicToken(publicToken: string): Promise<any> {
    throw new Error('Plaid implementation should use existing code')
  }

  async getAccounts(accessToken: string): Promise<any> {
    throw new Error('Plaid implementation should use existing code')
  }

  async getTransactions(accessToken: string, accountIds: string[], dateRange: any): Promise<any> {
    throw new Error('Plaid implementation should use existing code')
  }

  async getInstitutions(countryCode: string): Promise<any> {
    throw new Error('Plaid implementation should use existing code')
  }
}

// Future providers (stubs for now)
class NordigenBankProvider extends BankProvider {
  name: BankProviderType = 'nordigen'
  supportedCountries = ['SE', 'DK', 'NO', 'FI', 'IS', 'EE', 'LV', 'LT']
  
  isAvailable(countryCode: string): boolean {
    return this.supportedCountries.includes(countryCode.toUpperCase())
  }

  async createLinkToken(userId: string, countryCode?: string): Promise<any> {
    throw new Error('Nordigen implementation coming soon')
  }

  async exchangePublicToken(publicToken: string): Promise<any> {
    throw new Error('Nordigen implementation coming soon')
  }

  async getAccounts(accessToken: string): Promise<any> {
    throw new Error('Nordigen implementation coming soon')
  }

  async getTransactions(accessToken: string, accountIds: string[], dateRange: any): Promise<any> {
    throw new Error('Nordigen implementation coming soon')
  }

  async getInstitutions(countryCode: string): Promise<any> {
    throw new Error('Nordigen implementation coming soon')
  }
}

class YapilyBankProvider extends BankProvider {
  name: BankProviderType = 'yapily'
  supportedCountries = ['PL', 'CZ', 'HU', 'RO', 'BG', 'SK', 'SI', 'HR', 'EE', 'LV', 'LT']
  
  isAvailable(countryCode: string): boolean {
    return this.supportedCountries.includes(countryCode.toUpperCase())
  }

  async createLinkToken(userId: string, countryCode?: string): Promise<any> {
    throw new Error('Yapily implementation coming soon')
  }

  async exchangePublicToken(publicToken: string): Promise<any> {
    throw new Error('Yapily implementation coming soon')
  }

  async getAccounts(accessToken: string): Promise<any> {
    throw new Error('Yapily implementation coming soon')
  }

  async getTransactions(accessToken: string, accountIds: string[], dateRange: any): Promise<any> {
    throw new Error('Yapily implementation coming soon')
  }

  async getInstitutions(countryCode: string): Promise<any> {
    throw new Error('Yapily implementation coming soon')
  }
}

export class BankProviderRouter {
  private providers: BankProvider[]

  constructor() {
    this.providers = [
      new PlaidBankProvider(),
      new TrueLayerBankProvider(),
      new NordigenBankProvider(),
      new YapilyBankProvider()
    ]
  }

  /**
   * Get the best provider for a specific country
   */
  getProvider(countryCode: string, preferredProvider?: BankProviderType): BankProvider {
    const upperCountryCode = countryCode.toUpperCase()

    // If user has a preferred provider and it supports the country, use it
    if (preferredProvider) {
      const preferred = this.providers.find(p => 
        p.name === preferredProvider && p.isAvailable(upperCountryCode)
      )
      if (preferred) return preferred
    }

    // Find the best provider for this country based on priority
    const availableProviders = this.providers.filter(p => p.isAvailable(upperCountryCode))
    
    if (availableProviders.length === 0) {
      throw new Error(`No banking provider available for country: ${countryCode}`)
    }

    // Return the first available provider (they're ordered by priority)
    return availableProviders[0]
  }

  /**
   * Get all providers that support a specific country
   */
  getAvailableProviders(countryCode: string): BankProvider[] {
    return this.providers.filter(p => p.isAvailable(countryCode.toUpperCase()))
  }

  /**
   * Check if banking is supported in a country
   */
  isCountrySupported(countryCode: string): boolean {
    return this.providers.some(p => p.isAvailable(countryCode.toUpperCase()))
  }

  /**
   * Get all supported countries
   */
  getSupportedCountries(): string[] {
    const countries = new Set<string>()
    this.providers.forEach(provider => {
      provider.supportedCountries.forEach(country => countries.add(country))
    })
    return Array.from(countries).sort()
  }

  /**
   * Get provider by name
   */
  getProviderByName(name: BankProviderType): BankProvider | undefined {
    return this.providers.find(p => p.name === name)
  }

  /**
   * Get country-specific information for UI
   */
  getCountryInfo(countryCode: string) {
    const upperCountryCode = countryCode.toUpperCase()
    const availableProviders = this.getAvailableProviders(upperCountryCode)
    const primaryProvider = availableProviders[0]

    return {
      countryCode: upperCountryCode,
      isSupported: availableProviders.length > 0,
      primaryProvider: primaryProvider?.name,
      availableProviders: availableProviders.map(p => p.name),
      providerCount: availableProviders.length
    }
  }
}

// Singleton instance
export const bankProviderRouter = new BankProviderRouter()

// Helper function to detect user's country from IP (mock implementation)
export async function detectUserCountry(request?: Request): Promise<string> {
  // In production, you'd use a service like CloudFlare, MaxMind, or IP geolocation
  // For now, return a default based on common usage
  
  // You could also check the user's browser language/locale
  if (typeof window !== 'undefined') {
    const locale = navigator.language || 'en-US'
    const countryFromLocale = locale.split('-')[1]
    if (countryFromLocale && bankProviderRouter.isCountrySupported(countryFromLocale)) {
      return countryFromLocale.toUpperCase()
    }
  }

  // Default fallback - could be based on your primary market
  return 'GB' // Default to UK for European users
}

// Country name mapping for UI display
export const COUNTRY_NAMES: Record<string, string> = {
  'US': 'United States',
  'CA': 'Canada',
  'GB': 'United Kingdom',
  'IE': 'Ireland',
  'FR': 'France',
  'DE': 'Germany',
  'ES': 'Spain',
  'IT': 'Italy',
  'NL': 'Netherlands',
  'BE': 'Belgium',
  'PT': 'Portugal',
  'PL': 'Poland',
  'SE': 'Sweden',
  'DK': 'Denmark',
  'NO': 'Norway',
  'FI': 'Finland',
  'IS': 'Iceland',
  'EE': 'Estonia',
  'LV': 'Latvia',
  'LT': 'Lithuania',
  'CZ': 'Czech Republic',
  'HU': 'Hungary',
  'RO': 'Romania',
  'BG': 'Bulgaria',
  'SK': 'Slovakia',
  'SI': 'Slovenia',
  'HR': 'Croatia'
}
