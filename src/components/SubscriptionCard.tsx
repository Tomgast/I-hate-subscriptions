'use client'

import { useState } from 'react'
import { Subscription, SUBSCRIPTION_CATEGORIES, BILLING_CYCLES } from '@/types/subscription'
import { subscriptionStore } from '@/lib/subscriptionStore'
import { 
  Calendar, 
  DollarSign, 
  Edit3, 
  Trash2, 
  ExternalLink,
  AlertTriangle,
  CheckCircle
} from 'lucide-react'
import { format, isAfter, isBefore, addDays } from 'date-fns'
import Link from 'next/link'

interface SubscriptionCardProps {
  subscription: Subscription
}

export function SubscriptionCard({ subscription }: SubscriptionCardProps) {
  const [isDeleting, setIsDeleting] = useState(false)
  
  const nextBillingDate = new Date(subscription.nextBillingDate)
  const now = new Date()
  const isOverdue = isBefore(nextBillingDate, now)
  const isDueSoon = isAfter(nextBillingDate, now) && isBefore(nextBillingDate, addDays(now, 7))
  
  const category = SUBSCRIPTION_CATEGORIES[subscription.category]
  const billingCycle = BILLING_CYCLES.find(cycle => cycle.value === subscription.billingCycle)
  
  const getMonthlyAmount = () => {
    const multiplier = billingCycle?.multiplier || 1
    return subscription.price * multiplier
  }

  const handleDelete = async () => {
    if (!confirm('Are you sure you want to delete this subscription?')) return
    
    setIsDeleting(true)
    try {
      subscriptionStore.deleteSubscription(subscription.id)
    } catch (error) {
      console.error('Error deleting subscription:', error)
      setIsDeleting(false)
    }
  }

  const toggleActive = () => {
    subscriptionStore.updateSubscription(subscription.id, {
      isActive: !subscription.isActive
    })
  }

  return (
    <div className={`card hover:shadow-md transition-all duration-200 ${
      !subscription.isActive ? 'opacity-60' : ''
    } ${isDeleting ? 'opacity-50 pointer-events-none' : ''}`}>
      <div className="flex items-start justify-between">
        <div className="flex items-start space-x-4 flex-1">
          {/* Category Color Indicator */}
          <div 
            className="w-1 h-16 rounded-full flex-shrink-0"
            style={{ backgroundColor: category.color }}
          />
          
          <div className="flex-1 min-w-0">
            {/* Header */}
            <div className="flex items-start justify-between mb-2">
              <div>
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white truncate">
                  {subscription.name}
                </h3>
                <p className="text-sm text-gray-500 dark:text-gray-400">
                  {category.label}
                </p>
              </div>
              
              {/* Status Badge */}
              <div className="flex items-center space-x-2 ml-4">
                {isOverdue && (
                  <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400">
                    <AlertTriangle className="h-3 w-3 mr-1" />
                    Overdue
                  </span>
                )}
                {isDueSoon && !isOverdue && (
                  <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">
                    <Calendar className="h-3 w-3 mr-1" />
                    Due Soon
                  </span>
                )}
                {subscription.isActive ? (
                  <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                    <CheckCircle className="h-3 w-3 mr-1" />
                    Active
                  </span>
                ) : (
                  <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400">
                    Inactive
                  </span>
                )}
              </div>
            </div>

            {/* Description */}
            {subscription.description && (
              <p className="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
                {subscription.description}
              </p>
            )}

            {/* Pricing and Billing Info */}
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
              <div className="flex items-center space-x-2">
                <DollarSign className="h-4 w-4 text-gray-400" />
                <div>
                  <p className="text-sm font-medium text-gray-900 dark:text-white">
                    ${subscription.price.toFixed(2)}
                  </p>
                  <p className="text-xs text-gray-500 dark:text-gray-400">
                    {billingCycle?.label}
                  </p>
                </div>
              </div>
              
              <div className="flex items-center space-x-2">
                <Calendar className="h-4 w-4 text-gray-400" />
                <div>
                  <p className="text-sm font-medium text-gray-900 dark:text-white">
                    {format(nextBillingDate, 'MMM dd, yyyy')}
                  </p>
                  <p className="text-xs text-gray-500 dark:text-gray-400">
                    Next billing
                  </p>
                </div>
              </div>
              
              <div className="flex items-center space-x-2">
                <DollarSign className="h-4 w-4 text-gray-400" />
                <div>
                  <p className="text-sm font-medium text-gray-900 dark:text-white">
                    ${getMonthlyAmount().toFixed(2)}
                  </p>
                  <p className="text-xs text-gray-500 dark:text-gray-400">
                    Monthly equivalent
                  </p>
                </div>
              </div>
            </div>

            {/* Tags */}
            {subscription.tags && subscription.tags.length > 0 && (
              <div className="flex flex-wrap gap-1 mb-4">
                {subscription.tags.map((tag, index) => (
                  <span
                    key={index}
                    className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300"
                  >
                    {tag}
                  </span>
                ))}
              </div>
            )}
          </div>
        </div>

        {/* Actions */}
        <div className="flex items-center space-x-2 ml-4">
          {subscription.website && (
            <a
              href={subscription.website}
              target="_blank"
              rel="noopener noreferrer"
              className="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
              title="Visit website"
            >
              <ExternalLink className="h-4 w-4" />
            </a>
          )}
          
          <button
            onClick={toggleActive}
            className={`p-2 rounded-md transition-colors ${
              subscription.isActive
                ? 'text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20'
                : 'text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800'
            }`}
            title={subscription.isActive ? 'Mark as inactive' : 'Mark as active'}
          >
            <CheckCircle className="h-4 w-4" />
          </button>
          
          <Link
            href={`/subscriptions/${subscription.id}`}
            className="p-2 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-md transition-colors"
            title="Edit subscription"
          >
            <Edit3 className="h-4 w-4" />
          </Link>
          
          <button
            onClick={handleDelete}
            disabled={isDeleting}
            className="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors disabled:opacity-50"
            title="Delete subscription"
          >
            <Trash2 className="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>
  )
}
