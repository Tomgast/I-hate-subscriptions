// Database Adapter Service - Replaces Supabase with Plesk MySQL
// Provides the same interface as Supabase for seamless migration

import { databaseService } from './config'
import { v4 as uuidv4 } from 'uuid'

export interface User {
  id: string
  email: string
  name?: string
  image?: string
  password?: string
  is_paid: boolean
  created_at: string
  updated_at: string
}

export interface Subscription {
  id: string
  user_id: string
  name: string
  amount: number
  currency: string
  billing_cycle: 'monthly' | 'yearly' | 'weekly' | 'daily'
  next_billing_date?: string
  status: 'active' | 'cancelled' | 'paused'
  category?: string
  description?: string
  website_url?: string
  cancel_url?: string
  created_at: string
  updated_at: string
}

export interface UserPreferences {
  id: number
  user_id: string
  reminder_days: number[]
  reminder_frequency: 'once' | 'daily' | 'weekly'
  preferred_time: string
  email_welcome: boolean
  email_upgrade: boolean
  email_bank_scan: boolean
  email_reminders: boolean
  created_at: string
  updated_at: string
}

export interface ReminderLog {
  id: number
  user_id: string
  subscription_id: string
  sent_at: string
  days_before_renewal: number
  email_sent: boolean
  error_message?: string
}

export class DatabaseAdapter {
  private static instance: DatabaseAdapter

  public static getInstance(): DatabaseAdapter {
    if (!DatabaseAdapter.instance) {
      DatabaseAdapter.instance = new DatabaseAdapter()
    }
    return DatabaseAdapter.instance
  }

  // User Management
  async createUser(userData: Omit<User, 'id' | 'created_at' | 'updated_at'>): Promise<User> {
    const id = uuidv4()
    const now = new Date().toISOString()
    
    await databaseService.query(
      `INSERT INTO users (id, email, name, image, password, is_paid, created_at, updated_at) 
       VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
      [id, userData.email, userData.name, userData.image, userData.password, userData.is_paid, now, now]
    )

    return {
      id,
      ...userData,
      created_at: now,
      updated_at: now
    }
  }

  async getUserById(id: string): Promise<User | null> {
    return await databaseService.queryOne<User>(
      'SELECT * FROM users WHERE id = ?',
      [id]
    )
  }

  async getUserByEmail(email: string): Promise<User | null> {
    return await databaseService.queryOne<User>(
      'SELECT * FROM users WHERE email = ?',
      [email]
    )
  }

  async updateUser(id: string, updates: Partial<Omit<User, 'id' | 'created_at'>>): Promise<User | null> {
    const setClause = Object.keys(updates).map(key => `${key} = ?`).join(', ')
    const values = [...Object.values(updates), new Date().toISOString(), id]
    
    await databaseService.query(
      `UPDATE users SET ${setClause}, updated_at = ? WHERE id = ?`,
      values
    )

    return await this.getUserById(id)
  }

  async deleteUser(id: string): Promise<boolean> {
    const result = await databaseService.query(
      'DELETE FROM users WHERE id = ?',
      [id]
    )
    return (result as any).affectedRows > 0
  }

  // Subscription Management
  async createSubscription(subscriptionData: Omit<Subscription, 'id' | 'created_at' | 'updated_at'>): Promise<Subscription> {
    const id = uuidv4()
    const now = new Date().toISOString()
    
    await databaseService.query(
      `INSERT INTO subscriptions (id, user_id, name, amount, currency, billing_cycle, 
       next_billing_date, status, category, description, website_url, cancel_url, created_at, updated_at) 
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [
        id, subscriptionData.user_id, subscriptionData.name, subscriptionData.amount,
        subscriptionData.currency, subscriptionData.billing_cycle, subscriptionData.next_billing_date,
        subscriptionData.status, subscriptionData.category, subscriptionData.description,
        subscriptionData.website_url, subscriptionData.cancel_url, now, now
      ]
    )

    return {
      id,
      ...subscriptionData,
      created_at: now,
      updated_at: now
    }
  }

  async getSubscriptionsByUserId(userId: string): Promise<Subscription[]> {
    return await databaseService.query<Subscription>(
      'SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC',
      [userId]
    )
  }

  async getSubscriptionById(id: string): Promise<Subscription | null> {
    return await databaseService.queryOne<Subscription>(
      'SELECT * FROM subscriptions WHERE id = ?',
      [id]
    )
  }

  async updateSubscription(id: string, updates: Partial<Omit<Subscription, 'id' | 'created_at'>>): Promise<Subscription | null> {
    const setClause = Object.keys(updates).map(key => `${key} = ?`).join(', ')
    const values = [...Object.values(updates), new Date().toISOString(), id]
    
    await databaseService.query(
      `UPDATE subscriptions SET ${setClause}, updated_at = ? WHERE id = ?`,
      values
    )

    return await this.getSubscriptionById(id)
  }

  async deleteSubscription(id: string): Promise<boolean> {
    const result = await databaseService.query(
      'DELETE FROM subscriptions WHERE id = ?',
      [id]
    )
    return (result as any).affectedRows > 0
  }

  async getActiveSubscriptions(): Promise<Subscription[]> {
    return await databaseService.query<Subscription>(
      "SELECT * FROM subscriptions WHERE status = 'active' ORDER BY next_billing_date ASC"
    )
  }

  async getSubscriptionsDueForReminder(days: number): Promise<Subscription[]> {
    return await databaseService.query<Subscription>(
      `SELECT s.*, u.email, u.name as user_name 
       FROM subscriptions s 
       JOIN users u ON s.user_id = u.id 
       WHERE s.status = 'active' 
       AND s.next_billing_date = DATE_ADD(CURDATE(), INTERVAL ? DAY)`,
      [days]
    )
  }

  // User Preferences Management
  async createUserPreferences(userId: string, preferences: Partial<Omit<UserPreferences, 'id' | 'user_id' | 'created_at' | 'updated_at'>>): Promise<UserPreferences> {
    const now = new Date().toISOString()
    const defaultPrefs = {
      reminder_days: [1, 3, 7],
      reminder_frequency: 'once' as const,
      preferred_time: '09:00:00',
      email_welcome: true,
      email_upgrade: true,
      email_bank_scan: true,
      email_reminders: true,
      ...preferences
    }

    await databaseService.query(
      `INSERT INTO user_preferences (user_id, reminder_days, reminder_frequency, preferred_time,
       email_welcome, email_upgrade, email_bank_scan, email_reminders, created_at, updated_at)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [
        userId, JSON.stringify(defaultPrefs.reminder_days), defaultPrefs.reminder_frequency,
        defaultPrefs.preferred_time, defaultPrefs.email_welcome, defaultPrefs.email_upgrade,
        defaultPrefs.email_bank_scan, defaultPrefs.email_reminders, now, now
      ]
    )

    const result = await databaseService.queryOne<UserPreferences>(
      'SELECT * FROM user_preferences WHERE user_id = ?',
      [userId]
    )

    if (result) {
      result.reminder_days = JSON.parse(result.reminder_days as any)
    }

    return result!
  }

  async getUserPreferences(userId: string): Promise<UserPreferences | null> {
    const result = await databaseService.queryOne<UserPreferences>(
      'SELECT * FROM user_preferences WHERE user_id = ?',
      [userId]
    )

    if (result) {
      result.reminder_days = JSON.parse(result.reminder_days as any)
    }

    return result
  }

  async updateUserPreferences(userId: string, updates: Partial<Omit<UserPreferences, 'id' | 'user_id' | 'created_at'>>): Promise<UserPreferences | null> {
    const updateData = { ...updates }
    if (updateData.reminder_days) {
      (updateData as any).reminder_days = JSON.stringify(updateData.reminder_days)
    }

    const setClause = Object.keys(updateData).map(key => `${key} = ?`).join(', ')
    const values = [...Object.values(updateData), new Date().toISOString(), userId]
    
    await databaseService.query(
      `UPDATE user_preferences SET ${setClause}, updated_at = ? WHERE user_id = ?`,
      values
    )

    return await this.getUserPreferences(userId)
  }

  // Reminder Logs Management
  async createReminderLog(logData: Omit<ReminderLog, 'id' | 'sent_at'>): Promise<ReminderLog> {
    const now = new Date().toISOString()
    
    const result = await databaseService.query(
      `INSERT INTO reminder_logs (user_id, subscription_id, days_before_renewal, email_sent, error_message, sent_at)
       VALUES (?, ?, ?, ?, ?, ?)`,
      [logData.user_id, logData.subscription_id, logData.days_before_renewal, logData.email_sent, logData.error_message, now]
    )

    return {
      id: (result as any).insertId,
      ...logData,
      sent_at: now
    }
  }

  async getReminderLogs(userId: string, limit = 50): Promise<ReminderLog[]> {
    return await databaseService.query<ReminderLog>(
      `SELECT rl.*, s.name as subscription_name 
       FROM reminder_logs rl 
       JOIN subscriptions s ON rl.subscription_id = s.id 
       WHERE rl.user_id = ? 
       ORDER BY rl.sent_at DESC 
       LIMIT ?`,
      [userId, limit]
    )
  }

  async cleanupOldReminderLogs(daysOld = 90): Promise<number> {
    const result = await databaseService.query(
      'DELETE FROM reminder_logs WHERE sent_at < DATE_SUB(NOW(), INTERVAL ? DAY)',
      [daysOld]
    )
    return (result as any).affectedRows
  }

  // Analytics and Statistics
  async getUserStats(userId: string): Promise<{
    totalSubscriptions: number
    activeSubscriptions: number
    monthlyTotal: number
    yearlyTotal: number
    avgMonthlySpend: number
  }> {
    const stats = await databaseService.queryOne<any>(
      `SELECT 
        COUNT(*) as totalSubscriptions,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as activeSubscriptions,
        SUM(CASE WHEN status = 'active' AND billing_cycle = 'monthly' THEN amount ELSE 0 END) as monthlyTotal,
        SUM(CASE WHEN status = 'active' AND billing_cycle = 'yearly' THEN amount ELSE 0 END) as yearlyTotal,
        AVG(CASE WHEN status = 'active' AND billing_cycle = 'monthly' THEN amount ELSE NULL END) as avgMonthlySpend
       FROM subscriptions 
       WHERE user_id = ?`,
      [userId]
    )

    return {
      totalSubscriptions: stats?.totalSubscriptions || 0,
      activeSubscriptions: stats?.activeSubscriptions || 0,
      monthlyTotal: stats?.monthlyTotal || 0,
      yearlyTotal: stats?.yearlyTotal || 0,
      avgMonthlySpend: stats?.avgMonthlySpend || 0
    }
  }
}

// Singleton instance
export const dbAdapter = DatabaseAdapter.getInstance()
