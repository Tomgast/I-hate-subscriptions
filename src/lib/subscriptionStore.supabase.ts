'use client'

import { Subscription, SubscriptionStats, SubscriptionCategory } from '@/types/subscription'
import { createClient } from '@/lib/supabase'
import { useSession } from 'next-auth/react'

class SupabaseSubscriptionStore {
  private listeners: Set<() => void> = new Set()

  // Get current user ID from session
  private getCurrentUserId(): string | null {
    if (typeof window === 'undefined') return null
    
    // Get session from localStorage (NextAuth stores it there)
    try {
      const sessionToken = localStorage.getItem('next-auth.session-token') || localStorage.getItem('__Secure-next-auth.session-token')
      if (!sessionToken) return null
      
      // For now, we'll need to pass the user ID from the hook
      // This is a temporary solution - in production you'd decode the JWT
      const sessionData = localStorage.getItem('next-auth.session')
      if (sessionData) {
        const session = JSON.parse(sessionData)
        return session?.user?.id || null
      }
      return null
    } catch {
      return null
    }
  }

  // Get all subscriptions for the current user
  async getSubscriptions(): Promise<Subscription[]> {
    const userId = this.getCurrentUserId()
    if (!userId) {
      console.warn('No user session found')
      return []
    }

    try {
      const supabase = createClient()
      const { data, error } = await supabase
        .from('subscriptions')
        .select('*')
        .eq('user_id', userId)
        .order('created_at', { ascending: false })

      if (error) {
        console.error('Error loading subscriptions:', error)
        return []
      }

      // Transform database format to app format
      return data.map(this.transformFromDatabase)
    } catch (error) {
      console.error('Error loading subscriptions:', error)
      return []
    }
  }

  // Add a new subscription
  async addSubscription(subscription: Omit<Subscription, 'id' | 'createdAt' | 'updatedAt'>): Promise<Subscription | null> {
    const userId = this.getCurrentUserId()
    if (!userId) {
      throw new Error('User must be logged in to add subscriptions')
    }

    try {
      const supabase = createClient()
      const dbSubscription = this.transformToDatabase(subscription, userId)

      const { data, error } = await supabase
        .from('subscriptions')
        .insert([dbSubscription])
        .select()
        .single()

      if (error) {
        console.error('Error adding subscription:', error)
        throw new Error('Failed to add subscription')
      }

      const newSubscription = this.transformFromDatabase(data)
      this.notifyListeners()
      return newSubscription
    } catch (error) {
      console.error('Error adding subscription:', error)
      throw error
    }
  }

  // Update an existing subscription
  async updateSubscription(id: string, updates: Partial<Subscription>): Promise<Subscription | null> {
    const userId = this.getCurrentUserId()
    if (!userId) {
      throw new Error('User must be logged in to update subscriptions')
    }

    try {
      const supabase = createClient()
      const dbUpdates = this.transformUpdatesToDatabase(updates)

      const { data, error } = await supabase
        .from('subscriptions')
        .update({
          ...dbUpdates,
          updated_at: new Date().toISOString()
        })
        .eq('id', id)
        .eq('user_id', userId) // Ensure user can only update their own subscriptions
        .select()
        .single()

      if (error) {
        console.error('Error updating subscription:', error)
        return null
      }

      const updatedSubscription = this.transformFromDatabase(data)
      this.notifyListeners()
      return updatedSubscription
    } catch (error) {
      console.error('Error updating subscription:', error)
      return null
    }
  }

  // Delete a subscription
  async deleteSubscription(id: string): Promise<boolean> {
    const userId = this.getCurrentUserId()
    if (!userId) {
      throw new Error('User must be logged in to delete subscriptions')
    }

    try {
      const supabase = createClient()
      const { error } = await supabase
        .from('subscriptions')
        .delete()
        .eq('id', id)
        .eq('user_id', userId) // Ensure user can only delete their own subscriptions

      if (error) {
        console.error('Error deleting subscription:', error)
        return false
      }

      this.notifyListeners()
      return true
    } catch (error) {
      console.error('Error deleting subscription:', error)
      return false
    }
  }

  // Get subscription statistics
  async getStats(): Promise<SubscriptionStats> {
    const subscriptions = await this.getSubscriptions()
    const activeSubscriptions = subscriptions.filter(sub => sub.isActive)
    
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

    activeSubscriptions.forEach(sub => {
      const monthlyAmount = this.getMonthlyAmount(sub)
      totalMonthly += monthlyAmount
      totalYearly += monthlyAmount * 12
      categoryBreakdown[sub.category] += monthlyAmount
    })

    // Get subscriptions due soon (next 7 days)
    const sevenDaysFromNow = new Date()
    sevenDaysFromNow.setDate(sevenDaysFromNow.getDate() + 7)
  
    const dueSoon = activeSubscriptions.filter(sub => {
      const renewalDate = new Date(sub.nextBillingDate)
      return renewalDate <= sevenDaysFromNow && renewalDate >= new Date()
    }).length

    // Generate monthly trend (last 12 months)
    const monthlyTrend = this.generateMonthlyTrend(activeSubscriptions)

    return {
      totalMonthly,
      totalYearly,
      activeCount: activeSubscriptions.length,
      dueSoon,
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
  async getUpcomingRenewals(days: number = 7): Promise<Subscription[]> {
    const subscriptions = await this.getSubscriptions()
    const activeSubscriptions = subscriptions.filter(sub => sub.isActive)
    const targetDate = new Date()
    targetDate.setDate(targetDate.getDate() + days)
    
    return activeSubscriptions.filter(sub => {
      const renewalDate = new Date(sub.nextBillingDate)
      return renewalDate <= targetDate && renewalDate >= new Date()
    }).sort((a, b) => new Date(a.nextBillingDate).getTime() - new Date(b.nextBillingDate).getTime())
  }

  // Search and filter subscriptions
  async searchSubscriptions(query: string, filters?: {
    category?: SubscriptionCategory
    minPrice?: number
    maxPrice?: number
    isActive?: boolean
  }): Promise<Subscription[]> {
    const userId = this.getCurrentUserId()
    if (!userId) return []

    try {
      const supabase = createClient()
      let queryBuilder = supabase
        .from('subscriptions')
        .select('*')
        .eq('user_id', userId)

      // Apply text search
      if (query.trim()) {
        queryBuilder = queryBuilder.or(`name.ilike.%${query}%,description.ilike.%${query}%`)
      }

      // Apply filters
      if (filters?.category) {
        queryBuilder = queryBuilder.eq('category', filters.category)
      }
      if (filters?.isActive !== undefined) {
        queryBuilder = queryBuilder.eq('status', filters.isActive ? 'active' : 'cancelled')
      }

      const { data, error } = await queryBuilder.order('created_at', { ascending: false })

      if (error) {
        console.error('Error searching subscriptions:', error)
        return []
      }

      let subscriptions = data.map(this.transformFromDatabase)

      // Apply price filters (need to be done client-side due to billing cycle conversion)
      if (filters?.minPrice !== undefined || filters?.maxPrice !== undefined) {
        subscriptions = subscriptions.filter(sub => {
          const monthlyAmount = this.getMonthlyAmount(sub)
          if (filters.minPrice !== undefined && monthlyAmount < filters.minPrice) return false
          if (filters.maxPrice !== undefined && monthlyAmount > filters.maxPrice) return false
          return true
        })
      }

      return subscriptions
    } catch (error) {
      console.error('Error searching subscriptions:', error)
      return []
    }
  }

  // Bulk import subscriptions
  async bulkImport(subscriptions: Omit<Subscription, 'id' | 'createdAt' | 'updatedAt'>[]): Promise<Subscription[]> {
    const userId = this.getCurrentUserId()
    if (!userId) {
      throw new Error('User must be logged in to import subscriptions')
    }

    try {
      const supabase = createClient()
      const dbSubscriptions = subscriptions.map(sub => this.transformToDatabase(sub, userId))

      const { data, error } = await supabase
        .from('subscriptions')
        .insert(dbSubscriptions)
        .select()

      if (error) {
        console.error('Error importing subscriptions:', error)
        throw new Error('Failed to import subscriptions')
      }

      const newSubscriptions = data.map(this.transformFromDatabase)
      this.notifyListeners()
      return newSubscriptions
    } catch (error) {
      console.error('Error importing subscriptions:', error)
      throw error
    }
  }

  // Export subscriptions as JSON
  async exportData(): Promise<string> {
    const subscriptions = await this.getSubscriptions()
    return JSON.stringify(subscriptions, null, 2)
  }

  // Clear all data for current user
  async clearAllData(): Promise<void> {
    const userId = this.getCurrentUserId()
    if (!userId) {
      throw new Error('User must be logged in to clear data')
    }

    try {
      const supabase = createClient()
      const { error } = await supabase
        .from('subscriptions')
        .delete()
        .eq('user_id', userId)

      if (error) {
        console.error('Error clearing data:', error)
        throw new Error('Failed to clear data')
      }

      this.notifyListeners()
    } catch (error) {
      console.error('Error clearing data:', error)
      throw error
    }
  }

  // Transform database format to app format
  private transformFromDatabase(dbSub: any): Subscription {
    return {
      id: dbSub.id,
      name: dbSub.name,
      description: dbSub.description,
      price: dbSub.cost,
      currency: dbSub.currency || 'USD',
      billingCycle: this.mapBillingCycleFromDb(dbSub.billing_cycle),
      nextBillingDate: dbSub.next_billing_date,
      isActive: dbSub.status === 'active',
      category: dbSub.category || 'other',
      website: dbSub.website_url,
      tags: [], // We can add tags later if needed
      reminderDays: dbSub.reminder_days || 3,
      createdAt: dbSub.created_at,
      updatedAt: dbSub.updated_at,
    }
  }

  // Transform app format to database format
  private transformToDatabase(subscription: Omit<Subscription, 'id' | 'createdAt' | 'updatedAt'>, userId: string): any {
    return {
      user_id: userId,
      name: subscription.name,
      description: subscription.description,
      cost: subscription.price,
      currency: subscription.currency || 'USD',
      billing_cycle: this.mapBillingCycleToDb(subscription.billingCycle),
      next_billing_date: subscription.nextBillingDate,
      status: subscription.isActive ? 'active' : 'cancelled',
      category: subscription.category,
      website_url: subscription.website,
      reminder_days: subscription.reminderDays || 3,
    }
  }

  // Transform partial updates to database format
  private transformUpdatesToDatabase(updates: Partial<Subscription>): any {
    const dbUpdates: any = {}
    
    if (updates.name !== undefined) dbUpdates.name = updates.name
    if (updates.description !== undefined) dbUpdates.description = updates.description
    if (updates.price !== undefined) dbUpdates.cost = updates.price
    if (updates.currency !== undefined) dbUpdates.currency = updates.currency
    if (updates.billingCycle !== undefined) dbUpdates.billing_cycle = this.mapBillingCycleToDb(updates.billingCycle)
    if (updates.nextBillingDate !== undefined) dbUpdates.next_billing_date = updates.nextBillingDate
    if (updates.isActive !== undefined) dbUpdates.status = updates.isActive ? 'active' : 'cancelled'
    if (updates.category !== undefined) dbUpdates.category = updates.category
    if (updates.website !== undefined) dbUpdates.website_url = updates.website
    if (updates.reminderDays !== undefined) dbUpdates.reminder_days = updates.reminderDays

    return dbUpdates
  }

  // Map billing cycle from database to app format
  private mapBillingCycleFromDb(dbCycle: string): Subscription['billingCycle'] {
    switch (dbCycle) {
      case 'monthly': return 'monthly'
      case 'yearly': return 'yearly'
      case 'weekly': return 'weekly'
      case 'daily': return 'daily'
      case 'one-time': return 'one-time'
      default: return 'monthly'
    }
  }

  // Map billing cycle from app to database format
  private mapBillingCycleToDb(appCycle: Subscription['billingCycle']): string {
    switch (appCycle) {
      case 'monthly': return 'monthly'
      case 'yearly': return 'yearly'
      case 'quarterly': return 'monthly' // Map quarterly to monthly for now
      case 'weekly': return 'weekly'
      case 'daily': return 'daily'
      case 'one-time': return 'one-time'
      default: return 'monthly'
    }
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

export const supabaseSubscriptionStore = new SupabaseSubscriptionStore()
