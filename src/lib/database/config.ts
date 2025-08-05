// Database Configuration for Plesk MariaDB
// Handles connection to Plesk hosting MariaDB database

import mysql from 'mysql2/promise'

export interface DatabaseConfig {
  host: string
  port: number
  database: string
  user: string
  password: string
  connectionLimit?: number
}

export class DatabaseService {
  private static instance: DatabaseService
  private pool: mysql.Pool | null = null
  private config: DatabaseConfig

  private constructor() {
    this.config = this.getDatabaseConfig()
    this.initializePool()
  }

  public static getInstance(): DatabaseService {
    if (!DatabaseService.instance) {
      DatabaseService.instance = new DatabaseService()
    }
    return DatabaseService.instance
  }

  private getDatabaseConfig(): DatabaseConfig {
    return {
      host: process.env.DB_HOST || 'localhost',
      port: parseInt(process.env.DB_PORT || '3306'),
      database: process.env.DB_NAME || '',
      user: process.env.DB_USER || '',
      password: process.env.DB_PASSWORD || '',
      connectionLimit: 10
    }
  }

  private initializePool() {
    try {
      this.pool = mysql.createPool({
        host: this.config.host,
        port: this.config.port,
        database: this.config.database,
        user: this.config.user,
        password: this.config.password,
        connectionLimit: this.config.connectionLimit,
        charset: 'utf8mb4',
        // MariaDB specific optimizations
        supportBigNumbers: true,
        bigNumberStrings: true,
        dateStrings: false
      })

      console.log('✅ MariaDB connection pool initialized for Plesk database')
    } catch (error) {
      console.error('❌ Failed to initialize MariaDB connection pool:', error)
      throw error
    }
  }

  public async testConnection(): Promise<boolean> {
    if (!this.pool) {
      console.error('❌ Database pool not initialized')
      return false
    }

    try {
      const connection = await this.pool.getConnection()
      await connection.ping()
      connection.release()
      console.log('✅ Database connection test successful')
      return true
    } catch (error) {
      console.error('❌ Database connection test failed:', error)
      return false
    }
  }

  public async query<T = any>(sql: string, params?: any[]): Promise<T[]> {
    if (!this.pool) {
      throw new Error('Database pool not initialized')
    }

    try {
      const [rows] = await this.pool.execute(sql, params)
      return rows as T[]
    } catch (error) {
      console.error('❌ Database query failed:', error)
      console.error('SQL:', sql)
      console.error('Params:', params)
      throw error
    }
  }

  public async queryOne<T = any>(sql: string, params?: any[]): Promise<T | null> {
    const results = await this.query<T>(sql, params)
    return results.length > 0 ? results[0] : null
  }

  public async transaction<T>(callback: (connection: mysql.PoolConnection) => Promise<T>): Promise<T> {
    if (!this.pool) {
      throw new Error('Database pool not initialized')
    }

    const connection = await this.pool.getConnection()
    
    try {
      await connection.beginTransaction()
      const result = await callback(connection)
      await connection.commit()
      return result
    } catch (error) {
      await connection.rollback()
      throw error
    } finally {
      connection.release()
    }
  }

  public async closePool(): Promise<void> {
    if (this.pool) {
      await this.pool.end()
      this.pool = null
      console.log('✅ Database connection pool closed')
    }
  }

  // Helper method to create tables if they don't exist
  public async initializeTables(): Promise<void> {
    const createUsersTable = `
      CREATE TABLE IF NOT EXISTS users (
        id VARCHAR(255) PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        name VARCHAR(255),
        image VARCHAR(500),
        is_paid BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_is_paid (is_paid)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `

    const createSubscriptionsTable = `
      CREATE TABLE IF NOT EXISTS subscriptions (
        id VARCHAR(255) PRIMARY KEY,
        user_id VARCHAR(255) NOT NULL,
        name VARCHAR(255) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        currency VARCHAR(3) DEFAULT 'EUR',
        billing_cycle ENUM('monthly', 'yearly', 'weekly', 'daily') DEFAULT 'monthly',
        next_billing_date DATE,
        status ENUM('active', 'cancelled', 'paused') DEFAULT 'active',
        category VARCHAR(100),
        description TEXT,
        website_url VARCHAR(500),
        cancel_url VARCHAR(500),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_status (status),
        INDEX idx_next_billing_date (next_billing_date)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `

    const createUserPreferencesTable = `
      CREATE TABLE IF NOT EXISTS user_preferences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(255) NOT NULL,
        reminder_days JSON DEFAULT '[]',
        reminder_frequency ENUM('once', 'daily', 'weekly') DEFAULT 'once',
        preferred_time TIME DEFAULT '09:00:00',
        email_welcome BOOLEAN DEFAULT TRUE,
        email_upgrade BOOLEAN DEFAULT TRUE,
        email_bank_scan BOOLEAN DEFAULT TRUE,
        email_reminders BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_prefs (user_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `

    const createReminderLogsTable = `
      CREATE TABLE IF NOT EXISTS reminder_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(255) NOT NULL,
        subscription_id VARCHAR(255) NOT NULL,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        days_before_renewal INT NOT NULL,
        email_sent BOOLEAN DEFAULT FALSE,
        error_message TEXT,
        INDEX idx_user_id (user_id),
        INDEX idx_subscription_id (subscription_id),
        INDEX idx_sent_at (sent_at),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `

    try {
      await this.query(createUsersTable)
      await this.query(createSubscriptionsTable)
      await this.query(createUserPreferencesTable)
      await this.query(createReminderLogsTable)
      console.log('✅ Database tables initialized successfully')
    } catch (error) {
      console.error('❌ Failed to initialize database tables:', error)
      throw error
    }
  }
}

// Singleton instance
export const databaseService = DatabaseService.getInstance()
