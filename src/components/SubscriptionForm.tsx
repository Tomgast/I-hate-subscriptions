'use client'

import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { subscriptionStore } from '@/lib/subscriptionStore'
import { SUBSCRIPTION_CATEGORIES, BILLING_CYCLES, SubscriptionCategory } from '@/types/subscription'
import { Calendar, DollarSign, Tag, Globe, AlertCircle } from 'lucide-react'
import { useRouter } from 'next/navigation'

const subscriptionSchema = z.object({
  name: z.string().min(1, 'Name is required').max(100, 'Name must be less than 100 characters'),
  price: z.number().min(0.01, 'Price must be greater than 0'),
  currency: z.string().default('USD'),
  billingCycle: z.enum(['monthly', 'yearly', 'weekly', 'daily', 'quarterly']),
  nextBillingDate: z.string().min(1, 'Next billing date is required'),
  category: z.enum([
    'streaming', 'software', 'gaming', 'fitness', 'utilities', 'food',
    'education', 'finance', 'productivity', 'entertainment', 'news',
    'music', 'cloud_storage', 'communication', 'other'
  ] as const),
  description: z.string().optional(),
  website: z.string().url('Please enter a valid URL').optional().or(z.literal('')),
  reminderDays: z.number().min(0).max(30).default(3),
  tags: z.string().optional(),
  isActive: z.boolean().default(true),
})

type SubscriptionFormData = z.infer<typeof subscriptionSchema>

interface SubscriptionFormProps {
  onSuccess?: () => void
  onCancel?: () => void
}

export function SubscriptionForm({ onSuccess, onCancel }: SubscriptionFormProps) {
  const [isSubmitting, setIsSubmitting] = useState(false)
  const router = useRouter()

  const {
    register,
    handleSubmit,
    formState: { errors },
    watch,
    setValue,
  } = useForm<SubscriptionFormData>({
    resolver: zodResolver(subscriptionSchema),
    defaultValues: {
      currency: 'USD',
      billingCycle: 'monthly',
      category: 'other',
      reminderDays: 3,
      isActive: true,
    },
  })

  const watchedBillingCycle = watch('billingCycle')
  const watchedPrice = watch('price')

  const getMonthlyEquivalent = () => {
    if (!watchedPrice) return 0
    const cycle = BILLING_CYCLES.find(c => c.value === watchedBillingCycle)
    return watchedPrice * (cycle?.multiplier || 1)
  }

  const onSubmit = async (data: SubscriptionFormData) => {
    setIsSubmitting(true)
    try {
      const tags = data.tags 
        ? data.tags.split(',').map(tag => tag.trim()).filter(Boolean)
        : []

      subscriptionStore.addSubscription({
        name: data.name,
        price: data.price,
        currency: data.currency,
        billingCycle: data.billingCycle,
        nextBillingDate: data.nextBillingDate,
        category: data.category,
        description: data.description || undefined,
        website: data.website || undefined,
        reminderDays: data.reminderDays,
        tags: tags.length > 0 ? tags : undefined,
        isActive: data.isActive,
      })

      if (onSuccess) {
        onSuccess()
      } else {
        router.push('/dashboard')
      }
    } catch (error) {
      console.error('Error adding subscription:', error)
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
      {/* Basic Information */}
      <div className="card">
        <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
          Basic Information
        </h3>
        
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Service Name *
            </label>
            <input
              type="text"
              {...register('name')}
              placeholder="e.g., Netflix, Spotify, Adobe Creative Cloud"
              className="input-field"
            />
            {errors.name && (
              <p className="mt-1 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                <AlertCircle className="h-4 w-4" />
                {errors.name.message}
              </p>
            )}
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Category *
            </label>
            <select {...register('category')} className="input-field">
              {Object.entries(SUBSCRIPTION_CATEGORIES).map(([key, { label }]) => (
                <option key={key} value={key}>
                  {label}
                </option>
              ))}
            </select>
          </div>
        </div>

        <div className="mt-4">
          <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Description
          </label>
          <textarea
            {...register('description')}
            rows={3}
            placeholder="Optional description or notes about this subscription"
            className="input-field"
          />
        </div>

        <div className="mt-4">
          <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Website
          </label>
          <div className="relative">
            <Globe className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
            <input
              type="url"
              {...register('website')}
              placeholder="https://example.com"
              className="input-field pl-10"
            />
          </div>
          {errors.website && (
            <p className="mt-1 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
              <AlertCircle className="h-4 w-4" />
              {errors.website.message}
            </p>
          )}
        </div>
      </div>

      {/* Pricing Information */}
      <div className="card">
        <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
          Pricing & Billing
        </h3>
        
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Price *
            </label>
            <div className="relative">
              <DollarSign className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
              <input
                type="number"
                step="0.01"
                min="0"
                {...register('price', { valueAsNumber: true })}
                placeholder="9.99"
                className="input-field pl-10"
              />
            </div>
            {errors.price && (
              <p className="mt-1 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                <AlertCircle className="h-4 w-4" />
                {errors.price.message}
              </p>
            )}
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Billing Cycle *
            </label>
            <select {...register('billingCycle')} className="input-field">
              {BILLING_CYCLES.map(({ value, label }) => (
                <option key={value} value={value}>
                  {label}
                </option>
              ))}
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Currency
            </label>
            <select {...register('currency')} className="input-field">
              <option value="USD">USD ($)</option>
              <option value="EUR">EUR (€)</option>
              <option value="GBP">GBP (£)</option>
              <option value="CAD">CAD (C$)</option>
              <option value="AUD">AUD (A$)</option>
            </select>
          </div>
        </div>

        {watchedPrice && (
          <div className="mt-4 p-3 bg-primary-50 dark:bg-primary-900/20 rounded-lg">
            <p className="text-sm text-primary-700 dark:text-primary-300">
              <strong>Monthly equivalent:</strong> ${getMonthlyEquivalent().toFixed(2)} per month
            </p>
          </div>
        )}

        <div className="mt-4">
          <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Next Billing Date *
          </label>
          <div className="relative">
            <Calendar className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
            <input
              type="date"
              {...register('nextBillingDate')}
              className="input-field pl-10"
            />
          </div>
          {errors.nextBillingDate && (
            <p className="mt-1 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
              <AlertCircle className="h-4 w-4" />
              {errors.nextBillingDate.message}
            </p>
          )}
        </div>
      </div>

      {/* Additional Settings */}
      <div className="card">
        <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
          Additional Settings
        </h3>
        
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Reminder (days before renewal)
            </label>
            <input
              type="number"
              min="0"
              max="30"
              {...register('reminderDays', { valueAsNumber: true })}
              className="input-field"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Tags
            </label>
            <div className="relative">
              <Tag className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
              <input
                type="text"
                {...register('tags')}
                placeholder="work, personal, essential (comma-separated)"
                className="input-field pl-10"
              />
            </div>
          </div>
        </div>

        <div className="mt-4">
          <label className="flex items-center">
            <input
              type="checkbox"
              {...register('isActive')}
              className="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
            />
            <span className="ml-2 text-sm text-gray-700 dark:text-gray-300">
              This subscription is currently active
            </span>
          </label>
        </div>
      </div>

      {/* Form Actions */}
      <div className="flex justify-end space-x-4">
        {onCancel && (
          <button
            type="button"
            onClick={onCancel}
            className="btn-secondary"
            disabled={isSubmitting}
          >
            Cancel
          </button>
        )}
        <button
          type="submit"
          disabled={isSubmitting}
          className="btn-primary disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {isSubmitting ? 'Adding...' : 'Add Subscription'}
        </button>
      </div>
    </form>
  )
}
