// Bank Account Scan Service for CashControl
// Analyzes bank transactions to detect recurring subscriptions

export interface Transaction {
  account_id: string
  amount: number
  date: string
  name: string
  merchant_name?: string
  category: string[]
  transaction_id: string
}

export interface DetectedSubscription {
  merchant_name: string
  amount: number
  frequency: 'monthly' | 'yearly' | 'weekly'
  category: string
  confidence: number // 0-1 confidence score
  transactions: Transaction[]
  next_billing_date: string
  logo_url?: string
  website_url?: string
}

// Known subscription merchants for better detection
const KNOWN_SUBSCRIPTIONS = {
  'NETFLIX': { category: 'Entertainment', website: 'https://netflix.com' },
  'SPOTIFY': { category: 'Entertainment', website: 'https://spotify.com' },
  'ADOBE': { category: 'Software', website: 'https://adobe.com' },
  'GITHUB': { category: 'Software', website: 'https://github.com' },
  'AMAZON PRIME': { category: 'Shopping', website: 'https://amazon.com' },
  'APPLE': { category: 'Software', website: 'https://apple.com' },
  'MICROSOFT': { category: 'Software', website: 'https://microsoft.com' },
  'DROPBOX': { category: 'Storage', website: 'https://dropbox.com' },
  'ZOOM': { category: 'Software', website: 'https://zoom.us' },
  'SLACK': { category: 'Software', website: 'https://slack.com' },
  'NOTION': { category: 'Productivity', website: 'https://notion.so' },
  'FIGMA': { category: 'Software', website: 'https://figma.com' },
  'CANVA': { category: 'Software', website: 'https://canva.com' },
  'DISNEY': { category: 'Entertainment', website: 'https://disneyplus.com' },
  'HULU': { category: 'Entertainment', website: 'https://hulu.com' },
  'HBO': { category: 'Entertainment', website: 'https://hbomax.com' },
  'PARAMOUNT': { category: 'Entertainment', website: 'https://paramountplus.com' },
  'PEACOCK': { category: 'Entertainment', website: 'https://peacocktv.com' },
  'YOUTUBE': { category: 'Entertainment', website: 'https://youtube.com' },
  'TWITCH': { category: 'Entertainment', website: 'https://twitch.tv' }
}

export class BankScanService {
  /**
   * Analyze transactions to detect recurring subscriptions
   */
  static detectSubscriptions(transactions: Transaction[]): DetectedSubscription[] {
    const merchantGroups = this.groupTransactionsByMerchant(transactions)
    const detectedSubscriptions: DetectedSubscription[] = []

    for (const [merchantName, merchantTransactions] of merchantGroups) {
      const subscription = this.analyzeRecurringPattern(merchantName, merchantTransactions)
      if (subscription && subscription.confidence > 0.7) {
        detectedSubscriptions.push(subscription)
      }
    }

    return detectedSubscriptions.sort((a, b) => b.confidence - a.confidence)
  }

  /**
   * Group transactions by merchant name (normalized)
   */
  private static groupTransactionsByMerchant(transactions: Transaction[]): Map<string, Transaction[]> {
    const groups = new Map<string, Transaction[]>()

    for (const transaction of transactions) {
      const normalizedName = this.normalizeMerchantName(transaction.name)
      
      if (!groups.has(normalizedName)) {
        groups.set(normalizedName, [])
      }
      groups.get(normalizedName)!.push(transaction)
    }

    return groups
  }

  /**
   * Normalize merchant names for better grouping
   */
  private static normalizeMerchantName(name: string): string {
    return name
      .toUpperCase()
      .replace(/[^A-Z0-9\s]/g, '') // Remove special characters
      .replace(/\s+/g, ' ') // Normalize spaces
      .replace(/\b(INC|LLC|CORP|LTD|CO|COMPANY)\b/g, '') // Remove company suffixes
      .replace(/\b(PAYMENT|PAY|BILL|RECURRING|SUBSCRIPTION|SUB)\b/g, '') // Remove payment terms
      .replace(/\b\d{4,}\b/g, '') // Remove long numbers
      .trim()
  }

  /**
   * Analyze if transactions show a recurring pattern
   */
  private static analyzeRecurringPattern(merchantName: string, transactions: Transaction[]): DetectedSubscription | null {
    if (transactions.length < 2) return null

    // Sort transactions by date
    const sortedTransactions = transactions.sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime())
    
    // Check for consistent amounts
    const amounts = sortedTransactions.map(t => Math.abs(t.amount))
    const avgAmount = amounts.reduce((sum, amt) => sum + amt, 0) / amounts.length
    const amountVariance = amounts.reduce((sum, amt) => sum + Math.pow(amt - avgAmount, 2), 0) / amounts.length
    
    // If amount variance is too high, it's probably not a subscription
    if (amountVariance > avgAmount * 0.1) return null

    // Analyze time intervals between transactions
    const intervals: number[] = []
    for (let i = 1; i < sortedTransactions.length; i++) {
      const prevDate = new Date(sortedTransactions[i - 1].date)
      const currDate = new Date(sortedTransactions[i].date)
      const daysDiff = Math.round((currDate.getTime() - prevDate.getTime()) / (1000 * 60 * 60 * 24))
      intervals.push(daysDiff)
    }

    // Determine frequency based on intervals
    const avgInterval = intervals.reduce((sum, interval) => sum + interval, 0) / intervals.length
    let frequency: 'monthly' | 'yearly' | 'weekly'
    let confidence = 0

    if (avgInterval >= 28 && avgInterval <= 32) {
      frequency = 'monthly'
      confidence = 0.9
    } else if (avgInterval >= 360 && avgInterval <= 370) {
      frequency = 'yearly'
      confidence = 0.9
    } else if (avgInterval >= 6 && avgInterval <= 8) {
      frequency = 'weekly'
      confidence = 0.8
    } else if (avgInterval >= 25 && avgInterval <= 35) {
      frequency = 'monthly'
      confidence = 0.7
    } else {
      return null // Not a clear recurring pattern
    }

    // Boost confidence for known subscription services
    const knownService = Object.keys(KNOWN_SUBSCRIPTIONS).find(key => 
      merchantName.includes(key)
    )
    if (knownService) {
      confidence = Math.min(confidence + 0.2, 1.0)
    }

    // Calculate next billing date
    const lastTransaction = sortedTransactions[sortedTransactions.length - 1]
    const nextBillingDate = this.calculateNextBillingDate(new Date(lastTransaction.date), frequency)

    // Determine category and website
    const serviceInfo = knownService ? KNOWN_SUBSCRIPTIONS[knownService as keyof typeof KNOWN_SUBSCRIPTIONS] : null
    const category = serviceInfo?.category || this.categorizeTransaction(sortedTransactions[0])

    return {
      merchant_name: this.cleanMerchantName(merchantName),
      amount: avgAmount,
      frequency,
      category,
      confidence,
      transactions: sortedTransactions,
      next_billing_date: nextBillingDate.toISOString().split('T')[0],
      website_url: serviceInfo?.website,
      logo_url: serviceInfo?.website ? `https://logo.clearbit.com/${new URL(serviceInfo.website).hostname}` : undefined
    }
  }

  /**
   * Clean merchant name for display
   */
  private static cleanMerchantName(name: string): string {
    return name
      .toLowerCase()
      .split(' ')
      .map(word => word.charAt(0).toUpperCase() + word.slice(1))
      .join(' ')
      .trim()
  }

  /**
   * Calculate next billing date based on frequency
   */
  private static calculateNextBillingDate(lastDate: Date, frequency: 'monthly' | 'yearly' | 'weekly'): Date {
    const nextDate = new Date(lastDate)
    
    switch (frequency) {
      case 'monthly':
        nextDate.setMonth(nextDate.getMonth() + 1)
        break
      case 'yearly':
        nextDate.setFullYear(nextDate.getFullYear() + 1)
        break
      case 'weekly':
        nextDate.setDate(nextDate.getDate() + 7)
        break
    }
    
    return nextDate
  }

  /**
   * Categorize transaction based on merchant name and transaction category
   */
  private static categorizeTransaction(transaction: Transaction): string {
    const categories = transaction.category || []
    const name = transaction.name.toUpperCase()

    // Check transaction categories first
    if (categories.includes('Entertainment')) return 'Entertainment'
    if (categories.includes('Software')) return 'Software'
    if (categories.includes('Subscription')) return 'Software'
    if (categories.includes('Internet')) return 'Software'
    if (categories.includes('Telecommunication Services')) return 'Utilities'

    // Check merchant name patterns
    if (name.includes('NETFLIX') || name.includes('SPOTIFY') || name.includes('DISNEY') || name.includes('HULU')) {
      return 'Entertainment'
    }
    if (name.includes('ADOBE') || name.includes('MICROSOFT') || name.includes('GITHUB') || name.includes('ZOOM')) {
      return 'Software'
    }
    if (name.includes('GYM') || name.includes('FITNESS') || name.includes('YOGA')) {
      return 'Health & Fitness'
    }
    if (name.includes('AMAZON') || name.includes('PRIME')) {
      return 'Shopping'
    }

    return 'Other'
  }

  /**
   * Filter out false positives (non-subscription recurring payments)
   */
  static filterFalsePositives(detectedSubscriptions: DetectedSubscription[]): DetectedSubscription[] {
    const falsePositivePatterns = [
      /PAYROLL/i,
      /SALARY/i,
      /WAGE/i,
      /RENT/i,
      /MORTGAGE/i,
      /LOAN/i,
      /INSURANCE/i,
      /UTILITY/i,
      /ELECTRIC/i,
      /GAS/i,
      /WATER/i,
      /INTERNET/i,
      /PHONE/i,
      /CABLE/i,
      /TRANSFER/i,
      /DEPOSIT/i,
      /WITHDRAWAL/i,
      /ATM/i,
      /CHECK/i
    ]

    return detectedSubscriptions.filter(subscription => {
      const merchantName = subscription.merchant_name
      return !falsePositivePatterns.some(pattern => pattern.test(merchantName))
    })
  }
}
