'use client'

import { SubscriptionForm } from '@/components/SubscriptionForm'
import { ArrowLeft } from 'lucide-react'
import Link from 'next/link'

export default function NewSubscriptionPage() {
  return (
    <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      {/* Header */}
      <div className="mb-8">
        <Link 
          href="/dashboard" 
          className="inline-flex items-center text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 mb-4"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Back to Dashboard
        </Link>
        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
          Add New Subscription
        </h1>
        <p className="text-gray-600 dark:text-gray-400 mt-2">
          Track a new recurring payment to stay on top of your spending
        </p>
      </div>

      {/* Form */}
      <SubscriptionForm />
    </div>
  )
}
