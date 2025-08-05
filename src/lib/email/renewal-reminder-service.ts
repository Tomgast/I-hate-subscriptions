// Automated Renewal Reminder Service for CashControl
// Integrates with user settings and subscription data to send timely reminders

import { emailService } from './email-service'
import { emailScheduler } from './email-scheduler'

export interface UserSettings {
  emailNotifications: boolean
  emailReminderDays: number[]
  reminderFrequency: 'once' | 'daily' | 'weekly'
  emailTime: string
  emailWelcome: boolean
  emailUpgrade: boolean
  emailBankScan: boolean
}

export interface Subscription {
  id: string
  name: string
  amount: number
  currency: string
  renewalDate: string
  userId: string
  userEmail: string
  isActive: boolean
  category?: string
  description?: string
}

export interface ReminderLog {
  subscriptionId: string
  userId: string
  reminderDay: number
  sentAt: string
  type: 'email'
  status: 'sent' | 'failed'
}

export class RenewalReminderService {
  private reminderLogs: Map<string, ReminderLog[]> = new Map()
  private userSettings: Map<string, UserSettings> = new Map()

  constructor() {
    this.loadUserSettings()
    this.loadReminderLogs()
  }

  /**
   * Set user email preferences
   */
  setUserSettings(userId: string, settings: UserSettings): void {
    this.userSettings.set(userId, settings)
    this.saveUserSettings()
    console.log(`⚙️ Updated email settings for user ${userId}`)
  }

  /**
   * Get user email preferences
   */
  getUserSettings(userId: string): UserSettings | null {
    return this.userSettings.get(userId) || null
  }

  /**
   * Schedule reminders for a subscription
   */
  scheduleSubscriptionReminders(subscription: Subscription): void {
    const settings = this.getUserSettings(subscription.userId)
    
    if (!settings || !settings.emailNotifications) {
      console.log(`📧 Email notifications disabled for user ${subscription.userId}`)
      return
    }

    // Schedule reminders using the email scheduler
    emailScheduler.scheduleRenewalReminders(
      subscription.userId,
      subscription.userEmail,
      subscription.userEmail.split('@')[0], // userName
      subscription.id,
      subscription.name,
      subscription.amount,
      subscription.currency,
      subscription.renewalDate,
      settings.emailReminderDays
    )

    console.log(`📅 Scheduled reminders for ${subscription.name} (${settings.emailReminderDays.join(', ')} days before)`)
  }

  /**
   * Process all subscriptions and send due reminders
   */
  async processRenewalReminders(subscriptions: Subscription[]): Promise<void> {
    const now = new Date()
    const today = now.toISOString().split('T')[0]

    console.log(`🔄 Processing renewal reminders for ${subscriptions.length} subscriptions...`)

    for (const subscription of subscriptions) {
      if (!subscription.isActive) continue

      const settings = this.getUserSettings(subscription.userId)
      if (!settings || !settings.emailNotifications) continue

      try {
        const renewalDate = new Date(subscription.renewalDate)
        const daysUntilRenewal = Math.ceil((renewalDate.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))

        // Check if we should send a reminder today
        if (settings.emailReminderDays.includes(daysUntilRenewal)) {
          const shouldSend = this.shouldSendReminder(
            subscription.id,
            subscription.userId,
            daysUntilRenewal,
            settings.reminderFrequency,
            today
          )

          if (shouldSend) {
            await this.sendRenewalReminder(subscription, daysUntilRenewal, settings)
          }
        }

        // Clean up old logs (older than 60 days)
        this.cleanupOldLogs(subscription.id, 60)

      } catch (error) {
        console.error(`❌ Error processing reminder for ${subscription.name}:`, error)
      }
    }
  }

  /**
   * Send a renewal reminder for a specific subscription
   */
  private async sendRenewalReminder(
    subscription: Subscription,
    daysUntilRenewal: number,
    settings: UserSettings
  ): Promise<void> {
    try {
      const success = await emailService.sendSubscriptionReminder(subscription.userEmail, {
        userName: subscription.userEmail.split('@')[0],
        subscriptionName: subscription.name,
        amount: subscription.amount,
        currency: subscription.currency,
        renewalDate: subscription.renewalDate,
        daysUntilRenewal,
        manageUrl: `${process.env.NEXTAUTH_URL}/subscriptions/${subscription.id}`,
        cancelUrl: `${process.env.NEXTAUTH_URL}/subscriptions/${subscription.id}/cancel`
      })

      // Log the reminder attempt
      this.logReminder(
        subscription.id,
        subscription.userId,
        daysUntilRenewal,
        success ? 'sent' : 'failed'
      )

      if (success) {
        console.log(`✅ Sent ${daysUntilRenewal}-day reminder for ${subscription.name} to ${subscription.userEmail}`)
      } else {
        console.error(`❌ Failed to send reminder for ${subscription.name}`)
      }

    } catch (error) {
      console.error(`❌ Error sending reminder for ${subscription.name}:`, error)
      this.logReminder(subscription.id, subscription.userId, daysUntilRenewal, 'failed')
    }
  }

  /**
   * Determine if a reminder should be sent based on frequency settings
   */
  private shouldSendReminder(
    subscriptionId: string,
    userId: string,
    daysUntilRenewal: number,
    frequency: 'once' | 'daily' | 'weekly',
    today: string
  ): boolean {
    const logs = this.getReminderLogs(subscriptionId)
    const todayLogs = logs.filter(log => 
      log.sentAt.startsWith(today) && 
      log.reminderDay === daysUntilRenewal &&
      log.status === 'sent'
    )

    switch (frequency) {
      case 'once':
        // Send only once per reminder period
        return todayLogs.length === 0

      case 'daily':
        // Send daily until renewal (but only once per day)
        return todayLogs.length === 0

      case 'weekly':
        // Send weekly until renewal
        const weekAgo = new Date()
        weekAgo.setDate(weekAgo.getDate() - 7)
        const recentLogs = logs.filter(log => 
          new Date(log.sentAt) > weekAgo && 
          log.reminderDay === daysUntilRenewal &&
          log.status === 'sent'
        )
        return recentLogs.length === 0

      default:
        return false
    }
  }

  /**
   * Log a reminder attempt
   */
  private logReminder(
    subscriptionId: string,
    userId: string,
    reminderDay: number,
    status: 'sent' | 'failed'
  ): void {
    const logs = this.reminderLogs.get(subscriptionId) || []
    logs.push({
      subscriptionId,
      userId,
      reminderDay,
      sentAt: new Date().toISOString(),
      type: 'email',
      status
    })
    this.reminderLogs.set(subscriptionId, logs)
    this.saveReminderLogs()
  }

  /**
   * Get reminder logs for a subscription
   */
  private getReminderLogs(subscriptionId: string): ReminderLog[] {
    return this.reminderLogs.get(subscriptionId) || []
  }

  /**
   * Clean up old reminder logs
   */
  private cleanupOldLogs(subscriptionId: string, daysToKeep: number): void {
    const logs = this.reminderLogs.get(subscriptionId) || []
    const cutoffDate = new Date()
    cutoffDate.setDate(cutoffDate.getDate() - daysToKeep)

    const filteredLogs = logs.filter(log => new Date(log.sentAt) > cutoffDate)
    
    if (filteredLogs.length !== logs.length) {
      this.reminderLogs.set(subscriptionId, filteredLogs)
      console.log(`🗑️ Cleaned up ${logs.length - filteredLogs.length} old reminder logs for subscription ${subscriptionId}`)
    }
  }

  /**
   * Get reminder statistics for a user
   */
  getReminderStats(userId: string): {
    totalReminders: number
    successfulReminders: number
    failedReminders: number
    lastReminderSent?: string
  } {
    let totalReminders = 0
    let successfulReminders = 0
    let failedReminders = 0
    let lastReminderSent: string | undefined

    Array.from(this.reminderLogs.entries()).forEach(([subscriptionId, logs]) => {
      const userLogs = logs.filter(log => log.userId === userId)
      totalReminders += userLogs.length
      successfulReminders += userLogs.filter(log => log.status === 'sent').length
      failedReminders += userLogs.filter(log => log.status === 'failed').length

      const latestLog = userLogs.sort((a, b) => new Date(b.sentAt).getTime() - new Date(a.sentAt).getTime())[0]
      if (latestLog && (!lastReminderSent || latestLog.sentAt > lastReminderSent)) {
        lastReminderSent = latestLog.sentAt
      }
    })

    return {
      totalReminders,
      successfulReminders,
      failedReminders,
      lastReminderSent
    }
  }

  /**
   * Send a test reminder immediately
   */
  async sendTestReminder(
    userEmail: string,
    userName: string,
    subscriptionName: string = 'Netflix',
    amount: number = 15.99,
    currency: string = 'EUR'
  ): Promise<boolean> {
    const testDate = new Date()
    testDate.setDate(testDate.getDate() + 3)

    return await emailService.sendSubscriptionReminder(userEmail, {
      userName,
      subscriptionName,
      amount,
      currency,
      renewalDate: testDate.toISOString().split('T')[0],
      daysUntilRenewal: 3,
      manageUrl: `${process.env.NEXTAUTH_URL}/dashboard`,
      cancelUrl: `${process.env.NEXTAUTH_URL}/help`
    })
  }

  /**
   * Load user settings from storage (localStorage in browser, database in production)
   */
  private loadUserSettings(): void {
    // In production, this would load from database
    // For now, settings are managed by the settings page component
    console.log('📧 Renewal reminder service initialized')
  }

  /**
   * Save user settings to storage
   */
  private saveUserSettings(): void {
    // In production, this would save to database
    console.log(`💾 Saved settings for ${this.userSettings.size} users`)
  }

  /**
   * Load reminder logs from storage
   */
  private loadReminderLogs(): void {
    // In production, this would load from database
    console.log('📊 Loaded reminder logs')
  }

  /**
   * Save reminder logs to storage
   */
  private saveReminderLogs(): void {
    // In production, this would save to database
    console.log(`💾 Saved reminder logs for ${this.reminderLogs.size} subscriptions`)
  }
}

// Singleton instance
export const renewalReminderService = new RenewalReminderService()

// Helper function to start the renewal reminder checking process
export function startRenewalReminderService(): void {
  // Check for due reminders every hour
  setInterval(async () => {
    try {
      // In production, this would fetch subscriptions from database
      // For now, we'll integrate with the subscription store
      console.log('🔄 Checking for due renewal reminders...')
      // The actual processing will be triggered by the subscription management system
    } catch (error) {
      console.error('Error in renewal reminder service:', error)
    }
  }, 60 * 60 * 1000) // 1 hour

  console.log('🕐 Renewal reminder service started - checking every hour')
}
