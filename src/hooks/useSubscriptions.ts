'use client'

import { useState, useEffect, useCallback } from 'react'
import { useSession } from 'next-auth/react'
import { Subscription, SubscriptionStats, SubscriptionCategory } from '@/types/subscription'
import { supabaseSubscriptionStore } from '@/lib/subscriptionStore.supabase'

export function useSubscriptions() {
  const { data: session, status } = useSession()
  const [subscriptions, setSubscriptions] = useState<Subscription[]>([])
  const [stats, setStats] = useState<SubscriptionStats | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  // Load subscriptions when user is authenticated
  const loadSubscriptions = useCallback(async () => {
    if (status === 'loading') return
    if (!session?.user) {
      setSubscriptions([])
      setStats(null)
      setLoading(false)
      return
    }

    try {
      setLoading(true)
      setError(null)
      
      const [subs, statsData] = await Promise.all([
        supabaseSubscriptionStore.getSubscriptions(),
        supabaseSubscriptionStore.getStats()
      ])
      
      setSubscriptions(subs)
      setStats(statsData)
    } catch (err) {
      console.error('Error loading subscriptions:', err)
      setError(err instanceof Error ? err.message : 'Failed to load subscriptions')
    } finally {
      setLoading(false)
    }
  }, [session, status])

  // Load data on mount and when session changes
  useEffect(() => {
    loadSubscriptions()
  }, [loadSubscriptions])

  // Subscribe to store changes
  useEffect(() => {
    const unsubscribe = supabaseSubscriptionStore.subscribe(() => {
      loadSubscriptions()
    })
    return unsubscribe
  }, [loadSubscriptions])

  // Add subscription
  const addSubscription = useCallback(async (subscription: Omit<Subscription, 'id' | 'createdAt' | 'updatedAt'>) => {
    if (!session?.user) {
      throw new Error('Must be logged in to add subscriptions')
    }

    try {
      setError(null)
      const newSubscription = await supabaseSubscriptionStore.addSubscription(subscription)
      return newSubscription
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to add subscription'
      setError(errorMessage)
      throw new Error(errorMessage)
    }
  }, [session])

  // Update subscription
  const updateSubscription = useCallback(async (id: string, updates: Partial<Subscription>) => {
    if (!session?.user) {
      throw new Error('Must be logged in to update subscriptions')
    }

    try {
      setError(null)
      const updatedSubscription = await supabaseSubscriptionStore.updateSubscription(id, updates)
      return updatedSubscription
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to update subscription'
      setError(errorMessage)
      throw new Error(errorMessage)
    }
  }, [session])

  // Delete subscription
  const deleteSubscription = useCallback(async (id: string) => {
    if (!session?.user) {
      throw new Error('Must be logged in to delete subscriptions')
    }

    try {
      setError(null)
      const success = await supabaseSubscriptionStore.deleteSubscription(id)
      return success
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to delete subscription'
      setError(errorMessage)
      throw new Error(errorMessage)
    }
  }, [session])

  // Search subscriptions
  const searchSubscriptions = useCallback(async (query: string, filters?: {
    category?: SubscriptionCategory
    minPrice?: number
    maxPrice?: number
    isActive?: boolean
  }) => {
    if (!session?.user) return []

    try {
      setError(null)
      return await supabaseSubscriptionStore.searchSubscriptions(query, filters)
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to search subscriptions'
      setError(errorMessage)
      return []
    }
  }, [session])

  // Get upcoming renewals
  const getUpcomingRenewals = useCallback(async (days: number = 7) => {
    if (!session?.user) return []

    try {
      setError(null)
      return await supabaseSubscriptionStore.getUpcomingRenewals(days)
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to get upcoming renewals'
      setError(errorMessage)
      return []
    }
  }, [session])

  // Bulk import
  const bulkImport = useCallback(async (subscriptions: Omit<Subscription, 'id' | 'createdAt' | 'updatedAt'>[]) => {
    if (!session?.user) {
      throw new Error('Must be logged in to import subscriptions')
    }

    try {
      setError(null)
      const importedSubscriptions = await supabaseSubscriptionStore.bulkImport(subscriptions)
      return importedSubscriptions
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to import subscriptions'
      setError(errorMessage)
      throw new Error(errorMessage)
    }
  }, [session])

  // Export data
  const exportData = useCallback(async () => {
    if (!session?.user) return ''

    try {
      setError(null)
      return await supabaseSubscriptionStore.exportData()
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to export data'
      setError(errorMessage)
      return ''
    }
  }, [session])

  // Clear all data
  const clearAllData = useCallback(async () => {
    if (!session?.user) {
      throw new Error('Must be logged in to clear data')
    }

    try {
      setError(null)
      await supabaseSubscriptionStore.clearAllData()
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to clear data'
      setError(errorMessage)
      throw new Error(errorMessage)
    }
  }, [session])

  return {
    // Data
    subscriptions,
    stats,
    loading,
    error,
    isAuthenticated: !!session?.user,
    
    // Actions
    addSubscription,
    updateSubscription,
    deleteSubscription,
    searchSubscriptions,
    getUpcomingRenewals,
    bulkImport,
    exportData,
    clearAllData,
    refresh: loadSubscriptions,
  }
}
