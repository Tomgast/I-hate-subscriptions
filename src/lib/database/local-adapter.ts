// Local Database Adapter for Development/Testing
// Provides the same interface as the MariaDB adapter but uses SQLite locally

import { localDatabaseService } from './local-config'
import type { User, Subscription, UserPreferences, ReminderLog } from './adapter'

export class LocalDatabaseAdapter {
  private static instance: LocalDatabaseAdapter

  public static getInstance(): LocalDatabaseAdapter {
    if (!LocalDatabaseAdapter.instance) {
      LocalDatabaseAdapter.instance = new LocalDatabaseAdapter()
    }
    return LocalDatabaseAdapter.instance
  }

  // User Management
  async createUser(userData: Omit<User, 'id' | 'created_at' | 'updated_at'>): Promise<User> {
    return await localDatabaseService.createUser(userData)
  }

  async getUserById(id: string): Promise<User | null> {
    return await localDatabaseService.getUserById(id)
  }

  async getUserByEmail(email: string): Promise<User | null> {
    return await localDatabaseService.getUserByEmail(email)
  }

  async updateUser(id: string, updates: Partial<Omit<User, 'id' | 'created_at'>>): Promise<User | null> {
    return await localDatabaseService.updateUser(id, updates)
  }

  async deleteUser(id: string): Promise<boolean> {
    return await localDatabaseService.deleteUser(id)
  }

  // Subscription Management
  async createSubscription(subscriptionData: Omit<Subscription, 'id' | 'created_at' | 'updated_at'>): Promise<Subscription> {
    return await localDatabaseService.createSubscription(subscriptionData)
  }

  async getSubscriptionsByUserId(userId: string): Promise<Subscription[]> {
    return await localDatabaseService.getSubscriptionsByUserId(userId)
  }

  async getSubscriptionById(id: string): Promise<Subscription | null> {
    return await localDatabaseService.getSubscriptionById(id)
  }

  async updateSubscription(id: string, updates: Partial<Omit<Subscription, 'id' | 'created_at'>>): Promise<Subscription | null> {
    return await localDatabaseService.updateSubscription(id, updates)
  }

  async deleteSubscription(id: string): Promise<boolean> {
    return await localDatabaseService.deleteSubscription(id)
  }

  async getActiveSubscriptions(): Promise<Subscription[]> {
    // For local testing, we'll implement a simple version
    // In a real implementation, this would query all active subscriptions across all users
    throw new Error('getActiveSubscriptions not implemented for local adapter - use getSubscriptionsByUserId instead')
  }

  async getSubscriptionsDueForReminder(days: number): Promise<Subscription[]> {
    // For local testing, we'll implement a simple version
    throw new Error('getSubscriptionsDueForReminder not implemented for local adapter')
  }

  // User Preferences Management (simplified for local testing)
  async createUserPreferences(userId: string, preferences: Partial<Omit<UserPreferences, 'id' | 'user_id' | 'created_at' | 'updated_at'>>): Promise<UserPreferences> {
    // For local testing, we'll create a mock implementation
    const defaultPrefs = {
      reminder_days: [7, 3, 1],
      reminder_frequency: 'once' as const,
      preferred_time: '09:00:00',
      email_welcome: true,
      email_upgrade: true,
      email_bank_scan: true,
      email_reminders: true,
      ...preferences
    }

    const now = new Date().toISOString()
    return {
      id: Date.now(), // Simple ID for local testing
      user_id: userId,
      ...defaultPrefs,
      created_at: now,
      updated_at: now
    }
  }

  async getUserPreferences(userId: string): Promise<UserPreferences | null> {
    // For local testing, return default preferences
    return {
      id: Date.now(),
      user_id: userId,
      reminder_days: [7, 3, 1],
      reminder_frequency: 'once',
      preferred_time: '09:00:00',
      email_welcome: true,
      email_upgrade: true,
      email_bank_scan: true,
      email_reminders: true,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString()
    }
  }

  async updateUserPreferences(userId: string, updates: Partial<Omit<UserPreferences, 'id' | 'user_id' | 'created_at'>>): Promise<UserPreferences | null> {
    // For local testing, return updated preferences
    const current = await this.getUserPreferences(userId)
    if (!current) return null

    return {
      ...current,
      ...updates,
      updated_at: new Date().toISOString()
    }
  }

  // Reminder Logs Management (simplified for local testing)
  async createReminderLog(logData: Omit<ReminderLog, 'id' | 'sent_at'>): Promise<ReminderLog> {
    return {
      id: Date.now(),
      ...logData,
      sent_at: new Date().toISOString()
    }
  }

  async getReminderLogs(userId: string, limit = 50): Promise<ReminderLog[]> {
    // For local testing, return empty array
    return []
  }

  async cleanupOldReminderLogs(daysOld = 90): Promise<number> {
    // For local testing, return 0
    return 0
  }

  // Analytics and Statistics
  async getUserStats(userId: string): Promise<{
    totalSubscriptions: number
    activeSubscriptions: number
    monthlyTotal: number
    yearlyTotal: number
    avgMonthlySpend: number
  }> {
    return await localDatabaseService.getUserStats(userId)
  }
}

// Singleton instance
export const localDbAdapter = LocalDatabaseAdapter.getInstance()
