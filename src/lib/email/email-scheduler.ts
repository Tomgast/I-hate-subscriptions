// Email Scheduler for CashControl
// Handles automatic renewal reminders and scheduled email notifications

import { emailService } from './email-service'

export interface ScheduledReminder {
  id: string
  userId: string
  userEmail: string
  userName: string
  subscriptionId: string
  subscriptionName: string
  amount: number
  currency: string
  renewalDate: string
  reminderDays: number[] // Days before renewal to send reminders (e.g., [7, 3, 1])
  lastSent?: string // ISO date of last reminder sent
  isActive: boolean
}

export class EmailScheduler {
  private reminders: Map<string, ScheduledReminder> = new Map()

  constructor() {
    // In production, you'd load reminders from database
    this.loadReminders()
  }

  /**
   * Schedule renewal reminders for a subscription
   */
  scheduleRenewalReminders(
    userId: string,
    userEmail: string,
    userName: string,
    subscriptionId: string,
    subscriptionName: string,
    amount: number,
    currency: string,
    renewalDate: string,
    reminderDays: number[] = [7, 3, 1]
  ): void {
    const reminderId = `${userId}_${subscriptionId}`
    
    const reminder: ScheduledReminder = {
      id: reminderId,
      userId,
      userEmail,
      userName,
      subscriptionId,
      subscriptionName,
      amount,
      currency,
      renewalDate,
      reminderDays,
      isActive: true
    }

    this.reminders.set(reminderId, reminder)
    console.log(`📅 Scheduled reminders for ${subscriptionName} (${reminderDays.join(', ')} days before renewal)`)
  }

  /**
   * Cancel reminders for a subscription
   */
  cancelReminders(userId: string, subscriptionId: string): void {
    const reminderId = `${userId}_${subscriptionId}`
    const reminder = this.reminders.get(reminderId)
    
    if (reminder) {
      reminder.isActive = false
      console.log(`❌ Cancelled reminders for ${reminder.subscriptionName}`)
    }
  }

  /**
   * Check and send due reminders
   * This would typically be called by a cron job or scheduled task
   */
  async processDueReminders(): Promise<void> {
    const now = new Date()
    const today = now.toISOString().split('T')[0]

    for (const [id, reminder] of Array.from(this.reminders.entries())) {
      if (!reminder.isActive) continue

      try {
        const renewalDate = new Date(reminder.renewalDate)
        const daysUntilRenewal = Math.ceil((renewalDate.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))

        // Check if we should send a reminder today
        if (reminder.reminderDays.includes(daysUntilRenewal)) {
          // Check if we already sent a reminder today
          if (reminder.lastSent === today) {
            continue // Already sent today
          }

          // Send the reminder
          const success = await emailService.sendSubscriptionReminder(reminder.userEmail, {
            userName: reminder.userName,
            subscriptionName: reminder.subscriptionName,
            amount: reminder.amount,
            currency: reminder.currency,
            renewalDate: reminder.renewalDate,
            daysUntilRenewal,
            manageUrl: `${process.env.NEXTAUTH_URL}/subscriptions/${reminder.subscriptionId}`,
            cancelUrl: `${process.env.NEXTAUTH_URL}/subscriptions/${reminder.subscriptionId}/cancel`
          })

          if (success) {
            reminder.lastSent = today
            console.log(`✅ Sent ${daysUntilRenewal}-day reminder for ${reminder.subscriptionName} to ${reminder.userEmail}`)
          } else {
            console.error(`❌ Failed to send reminder for ${reminder.subscriptionName}`)
          }
        }

        // Clean up expired reminders (more than 1 day past renewal)
        if (daysUntilRenewal < -1) {
          this.reminders.delete(id)
          console.log(`🗑️ Cleaned up expired reminder for ${reminder.subscriptionName}`)
        }

      } catch (error) {
        console.error(`Error processing reminder for ${reminder.subscriptionName}:`, error)
      }
    }
  }

  /**
   * Get active reminders for a user
   */
  getUserReminders(userId: string): ScheduledReminder[] {
    return Array.from(this.reminders.values())
      .filter(reminder => reminder.userId === userId && reminder.isActive)
  }

  /**
   * Update reminder settings for a subscription
   */
  updateReminderSettings(
    userId: string,
    subscriptionId: string,
    reminderDays: number[]
  ): boolean {
    const reminderId = `${userId}_${subscriptionId}`
    const reminder = this.reminders.get(reminderId)
    
    if (reminder) {
      reminder.reminderDays = reminderDays
      console.log(`⚙️ Updated reminder settings for ${reminder.subscriptionName}: ${reminderDays.join(', ')} days`)
      return true
    }
    
    return false
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
    testDate.setDate(testDate.getDate() + 3) // 3 days from now

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

  private loadReminders(): void {
    // In production, this would load from database
    // For now, we start with an empty set
    console.log('📧 Email scheduler initialized')
  }

  /**
   * Save reminders to persistent storage
   * In production, this would save to database
   */
  private saveReminders(): void {
    // Implementation would save to database
    console.log(`💾 Saved ${this.reminders.size} email reminders`)
  }
}

// Singleton instance
export const emailScheduler = new EmailScheduler()

// Helper function to start the reminder checking process
export function startEmailScheduler(): void {
  // Check for due reminders every hour
  setInterval(async () => {
    try {
      await emailScheduler.processDueReminders()
    } catch (error) {
      console.error('Error in email scheduler:', error)
    }
  }, 60 * 60 * 1000) // 1 hour

  console.log('🕐 Email scheduler started - checking every hour for due reminders')
}
