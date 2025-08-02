'use client'

import { Subscription, SubscriptionStats, SubscriptionCategory } from '@/types/subscription'

class SubscriptionStore {
  private storageKey = 'subscriptions'
  private listeners: Set<() => void> = new Set()

  // Get all subscriptions from localStorage
  getSubscriptions(): Subscription[] {
    if (typeof window === 'undefined') return []
    
    try {
      const stored = localStorage.getItem(this.storageKey)
      return stored ? JSON.parse(stored) : []
    } catch (error) {
      console.error('Error loading subscriptions:', error)
      return []
    }
  }

  // Save subscriptions to localStorage
  private saveSubscriptions(subscriptions: Subscription[]): void {
    if (typeof window === 'undefined') return
    
    try {
      localStorage.setItem(this.storageKey, JSON.stringify(subscriptions))
      this.notifyListeners()
    } catch (error) {
      console.error('Error saving subscriptions:', error)
    }
  }

  // Add a new subscription
  addSubscription(subscription: Omit<Subscription, 'id' | 'createdAt' | 'updatedAt'>): Subscription {
    const newSubscription: Subscription = {
      ...subscription,
      id: crypto.randomUUID(),
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
    }

    const subscriptions = this.getSubscriptions()
    subscriptions.push(newSubscription)
    this.saveSubscriptions(subscriptions)
    
    return newSubscription
  }

  // Update an existing subscription
  updateSubscription(id: string, updates: Partial<Subscription>): Subscription | null {
    const subscriptions = this.getSubscriptions()
    const index = subscriptions.findIndex(sub => sub.id === id)
    
    if (index === -1) return null

    subscriptions[index] = {
      ...subscriptions[index],
      ...updates,
      updatedAt: new Date().toISOString(),
    }

    this.saveSubscriptions(subscriptions)
    return subscriptions[index]
  }

  // Delete a subscription
  deleteSubscription(id: string): boolean {
    const subscriptions = this.getSubscriptions()
    const filteredSubscriptions = subscriptions.filter(sub => sub.id !== id)
    
    if (filteredSubscriptions.length === subscriptions.length) return false

    this.saveSubscriptions(filteredSubscriptions)
    return true
  }

  // Get subscription statistics
  getStats(): SubscriptionStats {
    const subscriptions = this.getSubscriptions().filter(sub => sub.isActive)
    
    let totalMonthly = 0
    let totalYearly = 0
    const categoryBreakdown: Record<SubscriptionCategory, number> = {
      streaming: 0,
      software: 0,
      gaming: 0,
      fitness: 0,
      utilities: 0,
      food: 0,
      education: 0,
      finance: 0,
      productivity: 0,
      entertainment: 0,
      news: 0,
      music: 0,
      cloud_storage: 0,
      communication: 0,
      other: 0,
    }

    subscriptions.forEach(sub => {
      const monthlyAmount = this.getMonthlyAmount(sub)
      totalMonthly += monthlyAmount
      totalYearly += monthlyAmount * 12
      categoryBreakdown[sub.category] += monthlyAmount
    })

    // Get upcoming renewals (next 30 days)
    const thirtyDaysFromNow = new Date()
    thirtyDaysFromNow.setDate(thirtyDaysFromNow.getDate() + 30)
    
    const upcomingRenewals = subscriptions.filter(sub => {
      const renewalDate = new Date(sub.nextBillingDate)
      return renewalDate <= thirtyDaysFromNow && renewalDate >= new Date()
    }).length

    // Generate monthly trend (last 12 months)
    const monthlyTrend = this.generateMonthlyTrend(subscriptions)

    return {
      totalMonthly,
      totalYearly,
      activeCount: subscriptions.length,
      upcomingRenewals,
      categoryBreakdown,
      monthlyTrend,
    }
  }

  // Convert any billing cycle to monthly amount
  private getMonthlyAmount(subscription: Subscription): number {
    const { price, billingCycle } = subscription
    
    switch (billingCycle) {
      case 'monthly': return price
      case 'yearly': return price / 12
      case 'quarterly': return price / 3
      case 'weekly': return price * 4.33
      case 'daily': return price * 30
      default: return price
    }
  }

  // Generate monthly trend data
  private generateMonthlyTrend(subscriptions: Subscription[]): Array<{ month: string; amount: number }> {
    const months = []
    const now = new Date()
    
    for (let i = 11; i >= 0; i--) {
      const date = new Date(now.getFullYear(), now.getMonth() - i, 1)
      const monthName = date.toLocaleDateString('en-US', { month: 'short', year: '2-digit' })
      
      // Calculate total for subscriptions active during this month
      const monthTotal = subscriptions
        .filter(sub => {
          const createdDate = new Date(sub.createdAt)
          return createdDate <= date
        })
        .reduce((total, sub) => total + this.getMonthlyAmount(sub), 0)
      
      months.push({ month: monthName, amount: monthTotal })
    }
    
    return months
  }

  // Get subscriptions due for renewal soon
  getUpcomingRenewals(days: number = 7): Subscription[] {
    const subscriptions = this.getSubscriptions().filter(sub => sub.isActive)
    const targetDate = new Date()
    targetDate.setDate(targetDate.getDate() + days)
    
    return subscriptions.filter(sub => {
      const renewalDate = new Date(sub.nextBillingDate)
      return renewalDate <= targetDate && renewalDate >= new Date()
    }).sort((a, b) => new Date(a.nextBillingDate).getTime() - new Date(b.nextBillingDate).getTime())
  }

  // Search and filter subscriptions
  searchSubscriptions(query: string, filters?: {
    category?: SubscriptionCategory
    minPrice?: number
    maxPrice?: number
    isActive?: boolean
  }): Subscription[] {
    let subscriptions = this.getSubscriptions()
    
    // Apply text search
    if (query.trim()) {
      const searchTerm = query.toLowerCase()
      subscriptions = subscriptions.filter(sub =>
        sub.name.toLowerCase().includes(searchTerm) ||
        sub.description?.toLowerCase().includes(searchTerm) ||
        sub.tags?.some(tag => tag.toLowerCase().includes(searchTerm))
      )
    }
    
    // Apply filters
    if (filters) {
      if (filters.category) {
        subscriptions = subscriptions.filter(sub => sub.category === filters.category)
      }
      if (filters.minPrice !== undefined) {
        subscriptions = subscriptions.filter(sub => this.getMonthlyAmount(sub) >= filters.minPrice!)
      }
      if (filters.maxPrice !== undefined) {
        subscriptions = subscriptions.filter(sub => this.getMonthlyAmount(sub) <= filters.maxPrice!)
      }
      if (filters.isActive !== undefined) {
        subscriptions = subscriptions.filter(sub => sub.isActive === filters.isActive)
      }
    }
    
    return subscriptions
  }

  // Bulk import subscriptions
  bulkImport(subscriptions: Omit<Subscription, 'id' | 'createdAt' | 'updatedAt'>[]): Subscription[] {
    const newSubscriptions = subscriptions.map(sub => ({
      ...sub,
      id: crypto.randomUUID(),
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
    }))

    const existingSubscriptions = this.getSubscriptions()
    const allSubscriptions = [...existingSubscriptions, ...newSubscriptions]
    this.saveSubscriptions(allSubscriptions)
    
    return newSubscriptions
  }

  // Export subscriptions as JSON
  exportData(): string {
    return JSON.stringify(this.getSubscriptions(), null, 2)
  }

  // Clear all data
  clearAllData(): void {
    if (typeof window === 'undefined') return
    localStorage.removeItem(this.storageKey)
    this.notifyListeners()
  }

  // Subscribe to changes
  subscribe(listener: () => void): () => void {
    this.listeners.add(listener)
    return () => this.listeners.delete(listener)
  }

  // Notify all listeners
  private notifyListeners(): void {
    this.listeners.forEach(listener => listener())
  }
}

export const subscriptionStore = new SubscriptionStore()
