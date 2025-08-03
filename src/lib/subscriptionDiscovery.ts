'use client'

import { Subscription, SubscriptionCategory } from '@/types/subscription'

// Mock Plaid-like transaction data structure
interface Transaction {
  id: string
  amount: number
  date: string
  merchantName: string
  description: string
  category: string[]
  accountId: string
}

// Discovered subscription with confidence score
export interface DiscoveredSubscription {
  merchantName: string
  amount: number
  frequency: 'monthly' | 'yearly' | 'quarterly' | 'weekly'
  category: SubscriptionCategory
  lastCharged: string
  confidence: number
  transactions: Transaction[]
  isLikelyActive: boolean
  estimatedNextCharge?: string
}

// Known subscription patterns for better detection
const SUBSCRIPTION_PATTERNS = {
  streaming: [
    { name: 'Netflix', patterns: ['NETFLIX', 'NFLX'], category: 'streaming' as SubscriptionCategory },
    { name: 'Spotify', patterns: ['SPOTIFY', 'SPOT'], category: 'music' as SubscriptionCategory },
    { name: 'Disney+', patterns: ['DISNEY', 'DISNEYPLUS'], category: 'streaming' as SubscriptionCategory },
    { name: 'Hulu', patterns: ['HULU'], category: 'streaming' as SubscriptionCategory },
    { name: 'Amazon Prime', patterns: ['AMAZON PRIME', 'AMZN PRIME'], category: 'streaming' as SubscriptionCategory },
    { name: 'YouTube Premium', patterns: ['YOUTUBE PREMIUM', 'GOOGLE YOUTUBE'], category: 'streaming' as SubscriptionCategory },
  ],
  software: [
    { name: 'Adobe Creative Cloud', patterns: ['ADOBE', 'ADBE'], category: 'software' as SubscriptionCategory },
    { name: 'Microsoft 365', patterns: ['MICROSOFT', 'MSFT', 'OFFICE 365'], category: 'software' as SubscriptionCategory },
    { name: 'Dropbox', patterns: ['DROPBOX'], category: 'cloud_storage' as SubscriptionCategory },
    { name: 'GitHub', patterns: ['GITHUB'], category: 'software' as SubscriptionCategory },
    { name: 'Slack', patterns: ['SLACK'], category: 'communication' as SubscriptionCategory },
    { name: 'Zoom', patterns: ['ZOOM'], category: 'communication' as SubscriptionCategory },
  ],
  fitness: [
    { name: 'Planet Fitness', patterns: ['PLANET FITNESS', 'PF '], category: 'fitness' as SubscriptionCategory },
    { name: 'LA Fitness', patterns: ['LA FITNESS'], category: 'fitness' as SubscriptionCategory },
    { name: 'Peloton', patterns: ['PELOTON'], category: 'fitness' as SubscriptionCategory },
    { name: 'ClassPass', patterns: ['CLASSPASS'], category: 'fitness' as SubscriptionCategory },
  ],
  news: [
    { name: 'New York Times', patterns: ['NY TIMES', 'NYT', 'NEW YORK TIMES'], category: 'news' as SubscriptionCategory },
    { name: 'Wall Street Journal', patterns: ['WSJ', 'WALL ST'], category: 'news' as SubscriptionCategory },
    { name: 'Washington Post', patterns: ['WASH POST', 'WAPO'], category: 'news' as SubscriptionCategory },
  ]
}

// Generate mock transaction data for demonstration
function generateMockTransactions(): Transaction[] {
  const transactions: Transaction[] = []
  const now = new Date()
  
  // Netflix - monthly subscription
  for (let i = 0; i < 6; i++) {
    const date = new Date(now.getFullYear(), now.getMonth() - i, 15)
    transactions.push({
      id: `netflix_${i}`,
      amount: -15.99,
      date: date.toISOString(),
      merchantName: 'Netflix',
      description: 'NETFLIX.COM',
      category: ['Entertainment', 'Streaming'],
      accountId: 'acc_1'
    })
  }

  // Spotify - monthly subscription
  for (let i = 0; i < 8; i++) {
    const date = new Date(now.getFullYear(), now.getMonth() - i, 10)
    transactions.push({
      id: `spotify_${i}`,
      amount: -9.99,
      date: date.toISOString(),
      merchantName: 'Spotify',
      description: 'SPOTIFY PREMIUM',
      category: ['Entertainment', 'Music'],
      accountId: 'acc_1'
    })
  }

  // Adobe - monthly subscription
  for (let i = 0; i < 4; i++) {
    const date = new Date(now.getFullYear(), now.getMonth() - i, 1)
    transactions.push({
      id: `adobe_${i}`,
      amount: -52.99,
      date: date.toISOString(),
      merchantName: 'Adobe',
      description: 'ADOBE CREATIVE CLOUD',
      category: ['Software', 'Professional'],
      accountId: 'acc_1'
    })
  }

  // Old gym membership (inactive for 2 months)
  for (let i = 2; i < 8; i++) {
    const date = new Date(now.getFullYear(), now.getMonth() - i, 25)
    transactions.push({
      id: `gym_${i}`,
      amount: -29.99,
      date: date.toISOString(),
      merchantName: 'Planet Fitness',
      description: 'PLANET FITNESS',
      category: ['Health', 'Fitness'],
      accountId: 'acc_1'
    })
  }

  // Forgotten cloud storage
  for (let i = 3; i < 12; i++) {
    const date = new Date(now.getFullYear(), now.getMonth() - i, 5)
    transactions.push({
      id: `cloud_${i}`,
      amount: -9.99,
      date: date.toISOString(),
      merchantName: 'pCloud',
      description: 'PCLOUD STORAGE',
      category: ['Technology', 'Cloud'],
      accountId: 'acc_1'
    })
  }

  return transactions.sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime())
}

// Analyze transaction patterns to detect subscriptions
function analyzeTransactionPatterns(transactions: Transaction[]): DiscoveredSubscription[] {
  const merchantGroups = new Map<string, Transaction[]>()
  
  // Group transactions by merchant
  transactions.forEach(transaction => {
    const key = transaction.merchantName.toLowerCase()
    if (!merchantGroups.has(key)) {
      merchantGroups.set(key, [])
    }
    merchantGroups.get(key)!.push(transaction)
  })

  const discoveries: DiscoveredSubscription[] = []

  merchantGroups.forEach((merchantTransactions, merchantKey) => {
    if (merchantTransactions.length < 2) return // Need at least 2 transactions to detect pattern

    // Sort by date
    merchantTransactions.sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime())

    // Analyze frequency and amount consistency
    const amounts = merchantTransactions.map(t => Math.abs(t.amount))
    const dates = merchantTransactions.map(t => new Date(t.date))
    
    // Check if amounts are consistent (within 10% variance)
    const avgAmount = amounts.reduce((sum, amt) => sum + amt, 0) / amounts.length
    const isAmountConsistent = amounts.every(amt => Math.abs(amt - avgAmount) / avgAmount < 0.1)

    if (!isAmountConsistent) return

    // Calculate frequency
    const intervals: number[] = []
    for (let i = 1; i < dates.length; i++) {
      const daysDiff = (dates[i].getTime() - dates[i-1].getTime()) / (1000 * 60 * 60 * 24)
      intervals.push(daysDiff)
    }

    const avgInterval = intervals.reduce((sum, interval) => sum + interval, 0) / intervals.length
    let frequency: 'monthly' | 'yearly' | 'quarterly' | 'weekly'
    let confidence = 70

    // Determine frequency based on average interval
    if (avgInterval >= 25 && avgInterval <= 35) {
      frequency = 'monthly'
      confidence += 20
    } else if (avgInterval >= 85 && avgInterval <= 95) {
      frequency = 'quarterly'
      confidence += 15
    } else if (avgInterval >= 360 && avgInterval <= 370) {
      frequency = 'yearly'
      confidence += 10
    } else if (avgInterval >= 6 && avgInterval <= 8) {
      frequency = 'weekly'
      confidence += 15
    } else {
      return // Not a clear subscription pattern
    }

    // Determine category and boost confidence for known services
    let category: SubscriptionCategory = 'other'
    const merchantName = merchantTransactions[0].merchantName

    Object.values(SUBSCRIPTION_PATTERNS).flat().forEach(pattern => {
      if (pattern.patterns.some(p => merchantName.toUpperCase().includes(p))) {
        category = pattern.category
        confidence += 15
      }
    })

    // Boost confidence based on transaction consistency
    const intervalVariance = intervals.reduce((sum, interval) => sum + Math.abs(interval - avgInterval), 0) / intervals.length
    if (intervalVariance < 3) confidence += 10 // Very consistent timing

    // Check if subscription is likely still active
    const lastTransaction = dates[dates.length - 1]
    const daysSinceLastCharge = (Date.now() - lastTransaction.getTime()) / (1000 * 60 * 60 * 24)
    const isLikelyActive = daysSinceLastCharge < (avgInterval * 1.5)

    // Estimate next charge date
    let estimatedNextCharge: string | undefined
    if (isLikelyActive) {
      const nextChargeDate = new Date(lastTransaction.getTime() + (avgInterval * 24 * 60 * 60 * 1000))
      estimatedNextCharge = nextChargeDate.toISOString()
    }

    discoveries.push({
      merchantName,
      amount: avgAmount,
      frequency,
      category,
      lastCharged: lastTransaction.toISOString(),
      confidence: Math.min(confidence, 98), // Cap at 98%
      transactions: merchantTransactions,
      isLikelyActive,
      estimatedNextCharge
    })
  })

  return discoveries.sort((a, b) => b.confidence - a.confidence)
}

// Main discovery function
export async function discoverSubscriptions(userId?: string): Promise<DiscoveredSubscription[]> {
  // In a real implementation, this would:
  // 1. Connect to Plaid API with user's bank account
  // 2. Fetch transaction data from the last 12-24 months
  // 3. Analyze patterns to identify subscriptions
  
  // For demo purposes, we'll use mock data
  await new Promise(resolve => setTimeout(resolve, 2000)) // Simulate API delay
  
  const mockTransactions = generateMockTransactions()
  const discoveries = analyzeTransactionPatterns(mockTransactions)
  
  return discoveries
}

// Convert discovered subscription to app subscription format
export function convertToSubscription(discovered: DiscoveredSubscription): Omit<Subscription, 'id' | 'createdAt' | 'updatedAt'> {
  return {
    name: discovered.merchantName,
    price: discovered.amount,
    currency: 'USD',
    billingCycle: discovered.frequency,
    nextBillingDate: discovered.estimatedNextCharge || new Date().toISOString(),
    category: discovered.category,
    description: `Automatically discovered subscription (${discovered.confidence}% confidence)`,
    isActive: discovered.isLikelyActive,
    reminderDays: 3,
    tags: ['auto-discovered']
  }
}

// Email scanning functionality (mock implementation)
export async function scanEmailForSubscriptions(emailProvider: 'gmail' | 'outlook'): Promise<DiscoveredSubscription[]> {
  // In a real implementation, this would:
  // 1. Connect to Gmail/Outlook API
  // 2. Search for subscription-related emails (receipts, confirmations)
  // 3. Extract subscription details using NLP/regex patterns
  
  await new Promise(resolve => setTimeout(resolve, 1500))
  
  // Mock email discoveries
  return [
    {
      merchantName: 'Medium',
      amount: 5.00,
      frequency: 'monthly',
      category: 'news',
      lastCharged: new Date(Date.now() - 15 * 24 * 60 * 60 * 1000).toISOString(),
      confidence: 85,
      transactions: [],
      isLikelyActive: true,
      estimatedNextCharge: new Date(Date.now() + 15 * 24 * 60 * 60 * 1000).toISOString()
    },
    {
      merchantName: 'Canva Pro',
      amount: 12.99,
      frequency: 'monthly',
      category: 'software',
      lastCharged: new Date(Date.now() - 8 * 24 * 60 * 60 * 1000).toISOString(),
      confidence: 92,
      transactions: [],
      isLikelyActive: true,
      estimatedNextCharge: new Date(Date.now() + 22 * 24 * 60 * 60 * 1000).toISOString()
    }
  ]
}

// Calculate potential savings from discovered subscriptions
export function calculatePotentialSavings(discoveries: DiscoveredSubscription[]): {
  monthlySavings: number
  yearlySavings: number
  inactiveSubscriptions: DiscoveredSubscription[]
} {
  const inactiveSubscriptions = discoveries.filter(d => !d.isLikelyActive)
  
  const monthlySavings = inactiveSubscriptions.reduce((total, sub) => {
    const monthlyAmount = sub.frequency === 'monthly' ? sub.amount :
                         sub.frequency === 'yearly' ? sub.amount / 12 :
                         sub.frequency === 'quarterly' ? sub.amount / 3 :
                         sub.amount * 4.33 // weekly
    return total + monthlyAmount
  }, 0)

  return {
    monthlySavings,
    yearlySavings: monthlySavings * 12,
    inactiveSubscriptions
  }
}
