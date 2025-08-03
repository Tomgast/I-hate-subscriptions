export interface Subscription {
  id: string
  name: string
  price: number
  currency: string
  billingCycle: 'monthly' | 'yearly' | 'weekly' | 'daily' | 'quarterly'
  nextBillingDate: string
  category: SubscriptionCategory
  description?: string
  website?: string
  isActive: boolean
  reminderDays: number
  createdAt: string
  updatedAt: string
  tags?: string[]
  color?: string
}

export type SubscriptionCategory = 
  | 'streaming'
  | 'software'
  | 'gaming'
  | 'fitness'
  | 'utilities'
  | 'food'
  | 'education'
  | 'finance'
  | 'productivity'
  | 'entertainment'
  | 'news'
  | 'music'
  | 'cloud_storage'
  | 'communication'
  | 'other'

export interface SubscriptionStats {
  totalMonthly: number
  totalYearly: number
  activeCount: number
  dueSoon: number
  categoryBreakdown: Record<SubscriptionCategory, number>
  monthlyTrend: { month: string; amount: number }[]
}

export interface ImportedSubscription {
  name: string
  price: number
  billingCycle: string
  nextBillingDate: string
  category: string
  description?: string
  website?: string
}

export const SUBSCRIPTION_CATEGORIES: Record<SubscriptionCategory, { label: string; color: string }> = {
  streaming: { label: 'Streaming & Media', color: '#ef4444' },
  software: { label: 'Software & Tools', color: '#3b82f6' },
  gaming: { label: 'Gaming', color: '#8b5cf6' },
  fitness: { label: 'Health & Fitness', color: '#10b981' },
  utilities: { label: 'Utilities', color: '#f59e0b' },
  food: { label: 'Food & Delivery', color: '#f97316' },
  education: { label: 'Education', color: '#06b6d4' },
  finance: { label: 'Finance & Banking', color: '#84cc16' },
  productivity: { label: 'Productivity', color: '#6366f1' },
  entertainment: { label: 'Entertainment', color: '#ec4899' },
  news: { label: 'News & Media', color: '#64748b' },
  music: { label: 'Music & Audio', color: '#14b8a6' },
  cloud_storage: { label: 'Cloud Storage', color: '#a855f7' },
  communication: { label: 'Communication', color: '#22c55e' },
  other: { label: 'Other', color: '#6b7280' },
}

export const BILLING_CYCLES = [
  { value: 'monthly', label: 'Monthly', multiplier: 1 },
  { value: 'yearly', label: 'Yearly', multiplier: 1/12 },
  { value: 'quarterly', label: 'Quarterly', multiplier: 1/3 },
  { value: 'weekly', label: 'Weekly', multiplier: 4.33 },
  { value: 'daily', label: 'Daily', multiplier: 30 },
] as const
