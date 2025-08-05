// Local SQLite Database Configuration for Development/Testing
// This provides a local database for testing Pro upgrade functionality

import Database from 'better-sqlite3'
import { join } from 'path'
import { v4 as uuidv4 } from 'uuid'

export interface LocalUser {
  id: string
  email: string
  name?: string
  image?: string
  is_paid: boolean
  created_at: string
  updated_at: string
}

export interface LocalSubscription {
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

export class LocalDatabaseService {
  private static instance: LocalDatabaseService
  private db: Database.Database

  private constructor() {
    // Create database file in project root
    const dbPath = join(process.cwd(), 'local-test.db')
    this.db = new Database(dbPath)
    this.initializeTables()
    console.log('✅ Local SQLite database initialized at:', dbPath)
  }

  public static getInstance(): LocalDatabaseService {
    if (!LocalDatabaseService.instance) {
      LocalDatabaseService.instance = new LocalDatabaseService()
    }
    return LocalDatabaseService.instance
  }

  private initializeTables() {
    // Create users table
    this.db.exec(`
      CREATE TABLE IF NOT EXISTS users (
        id TEXT PRIMARY KEY,
        email TEXT UNIQUE NOT NULL,
        name TEXT,
        image TEXT,
        is_paid BOOLEAN DEFAULT FALSE,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
      )
    `)

    // Create subscriptions table
    this.db.exec(`
      CREATE TABLE IF NOT EXISTS subscriptions (
        id TEXT PRIMARY KEY,
        user_id TEXT NOT NULL,
        name TEXT NOT NULL,
        amount REAL NOT NULL,
        currency TEXT DEFAULT 'EUR',
        billing_cycle TEXT DEFAULT 'monthly',
        next_billing_date TEXT,
        status TEXT DEFAULT 'active',
        category TEXT,
        description TEXT,
        website_url TEXT,
        cancel_url TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
      )
    `)

    // Create user_preferences table
    this.db.exec(`
      CREATE TABLE IF NOT EXISTS user_preferences (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id TEXT NOT NULL,
        reminder_days TEXT DEFAULT '[]',
        reminder_frequency TEXT DEFAULT 'once',
        preferred_time TEXT DEFAULT '09:00:00',
        email_welcome BOOLEAN DEFAULT TRUE,
        email_upgrade BOOLEAN DEFAULT TRUE,
        email_bank_scan BOOLEAN DEFAULT TRUE,
        email_reminders BOOLEAN DEFAULT TRUE,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
      )
    `)

    console.log('✅ Local database tables initialized')
  }

  // User Management
  async createUser(userData: Omit<LocalUser, 'id' | 'created_at' | 'updated_at'>): Promise<LocalUser> {
    const id = uuidv4()
    const now = new Date().toISOString()
    
    const stmt = this.db.prepare(`
      INSERT INTO users (id, email, name, image, is_paid, created_at, updated_at) 
      VALUES (?, ?, ?, ?, ?, ?, ?)
    `)
    
    stmt.run(id, userData.email, userData.name, userData.image, userData.is_paid ? 1 : 0, now, now)

    return {
      id,
      ...userData,
      created_at: now,
      updated_at: now
    }
  }

  async getUserById(id: string): Promise<LocalUser | null> {
    const stmt = this.db.prepare('SELECT * FROM users WHERE id = ?')
    const result = stmt.get(id) as any
    if (!result) return null
    
    // Convert SQLite integer back to boolean
    return {
      ...result,
      is_paid: Boolean(result.is_paid)
    } as LocalUser
  }

  async getUserByEmail(email: string): Promise<LocalUser | null> {
    const stmt = this.db.prepare('SELECT * FROM users WHERE email = ?')
    const result = stmt.get(email) as any
    if (!result) return null
    
    // Convert SQLite integer back to boolean
    return {
      ...result,
      is_paid: Boolean(result.is_paid)
    } as LocalUser
  }

  async updateUser(id: string, updates: Partial<Omit<LocalUser, 'id' | 'created_at'>>): Promise<LocalUser | null> {
    const now = new Date().toISOString()
    const setClause = Object.keys(updates).map(key => `${key} = ?`).join(', ')
    
    // Convert boolean values to integers for SQLite
    const convertedValues = Object.values(updates).map(value => 
      typeof value === 'boolean' ? (value ? 1 : 0) : value
    )
    const values = [...convertedValues, now, id]
    
    const stmt = this.db.prepare(`UPDATE users SET ${setClause}, updated_at = ? WHERE id = ?`)
    stmt.run(...values)

    return this.getUserById(id)
  }

  async deleteUser(id: string): Promise<boolean> {
    const stmt = this.db.prepare('DELETE FROM users WHERE id = ?')
    const result = stmt.run(id)
    return result.changes > 0
  }

  // Subscription Management
  async createSubscription(subscriptionData: Omit<LocalSubscription, 'id' | 'created_at' | 'updated_at'>): Promise<LocalSubscription> {
    const id = uuidv4()
    const now = new Date().toISOString()
    
    const stmt = this.db.prepare(`
      INSERT INTO subscriptions (id, user_id, name, amount, currency, billing_cycle, 
       next_billing_date, status, category, description, website_url, cancel_url, created_at, updated_at) 
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    `)
    
    stmt.run(
      id, subscriptionData.user_id, subscriptionData.name, subscriptionData.amount,
      subscriptionData.currency, subscriptionData.billing_cycle, subscriptionData.next_billing_date,
      subscriptionData.status, subscriptionData.category, subscriptionData.description,
      subscriptionData.website_url, subscriptionData.cancel_url, now, now
    )

    return {
      id,
      ...subscriptionData,
      created_at: now,
      updated_at: now
    }
  }

  async getSubscriptionsByUserId(userId: string): Promise<LocalSubscription[]> {
    const stmt = this.db.prepare('SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC')
    return stmt.all(userId) as LocalSubscription[]
  }

  async getSubscriptionById(id: string): Promise<LocalSubscription | null> {
    const stmt = this.db.prepare('SELECT * FROM subscriptions WHERE id = ?')
    const result = stmt.get(id) as LocalSubscription | undefined
    return result || null
  }

  async updateSubscription(id: string, updates: Partial<Omit<LocalSubscription, 'id' | 'created_at'>>): Promise<LocalSubscription | null> {
    const now = new Date().toISOString()
    const setClause = Object.keys(updates).map(key => `${key} = ?`).join(', ')
    const values = [...Object.values(updates), now, id]
    
    const stmt = this.db.prepare(`UPDATE subscriptions SET ${setClause}, updated_at = ? WHERE id = ?`)
    stmt.run(...values)

    return this.getSubscriptionById(id)
  }

  async deleteSubscription(id: string): Promise<boolean> {
    const stmt = this.db.prepare('DELETE FROM subscriptions WHERE id = ?')
    const result = stmt.run(id)
    return result.changes > 0
  }

  // Get user statistics
  async getUserStats(userId: string): Promise<{
    totalSubscriptions: number
    activeSubscriptions: number
    monthlyTotal: number
    yearlyTotal: number
    avgMonthlySpend: number
  }> {
    const stmt = this.db.prepare(`
      SELECT 
        COUNT(*) as totalSubscriptions,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as activeSubscriptions,
        SUM(CASE WHEN status = 'active' AND billing_cycle = 'monthly' THEN amount ELSE 0 END) as monthlyTotal,
        SUM(CASE WHEN status = 'active' AND billing_cycle = 'yearly' THEN amount ELSE 0 END) as yearlyTotal,
        AVG(CASE WHEN status = 'active' AND billing_cycle = 'monthly' THEN amount ELSE NULL END) as avgMonthlySpend
       FROM subscriptions 
       WHERE user_id = ?
    `)
    
    const stats = stmt.get(userId) as any

    return {
      totalSubscriptions: stats?.totalSubscriptions || 0,
      activeSubscriptions: stats?.activeSubscriptions || 0,
      monthlyTotal: stats?.monthlyTotal || 0,
      yearlyTotal: stats?.yearlyTotal || 0,
      avgMonthlySpend: stats?.avgMonthlySpend || 0
    }
  }

  // Test connection
  async testConnection(): Promise<boolean> {
    try {
      const stmt = this.db.prepare('SELECT 1 as test')
      const result = stmt.get()
      return !!result
    } catch (error) {
      console.error('❌ Local database connection test failed:', error)
      return false
    }
  }

  // Close database
  close() {
    this.db.close()
    console.log('✅ Local database connection closed')
  }
}

// Singleton instance
export const localDatabaseService = LocalDatabaseService.getInstance()
