'use client'

import { useState } from 'react'
import Link from 'next/link'
import { 
  DollarSign, 
  TrendingUp, 
  Calendar, 
  AlertCircle,
  Plus,
  Filter,
  Search,
  Download,
  Upload,
  CreditCard,
  CheckCircle,
  ArrowRight,
  BarChart3,
  PieChart,
  Edit,
  Trash2,
  MoreVertical
} from 'lucide-react'

// Sample data that mirrors real dashboard functionality
const demoSubscriptions = [
  {
    id: '1',
    name: 'Netflix',
    price: 15.99,
    currency: 'USD',
    billingCycle: 'monthly',
    nextBillingDate: '2025-01-25',
    category: 'streaming',
    description: 'Video streaming service',
    website: 'https://netflix.com',
    reminderDays: 3,
    isActive: true,
    createdAt: '2024-01-15T00:00:00Z',
    updatedAt: '2024-01-15T00:00:00Z'
  },
  {
    id: '2',
    name: 'Spotify Premium',
    price: 9.99,
    currency: 'USD',
    billingCycle: 'monthly',
    nextBillingDate: '2025-01-20',
    category: 'music',
    description: 'Music streaming service',
    website: 'https://spotify.com',
    reminderDays: 3,
    isActive: true,
    createdAt: '2024-02-01T00:00:00Z',
    updatedAt: '2024-02-01T00:00:00Z'
  },
  {
    id: '3',
    name: 'GitHub Pro',
    price: 4.00,
    currency: 'USD',
    billingCycle: 'monthly',
    nextBillingDate: '2025-01-28',
    category: 'software',
    description: 'Code repository hosting',
    website: 'https://github.com',
    reminderDays: 3,
    isActive: true,
    createdAt: '2024-03-01T00:00:00Z',
    updatedAt: '2024-03-01T00:00:00Z'
  },
  {
    id: '4',
    name: 'Adobe Creative Cloud',
    price: 52.99,
    currency: 'USD',
    billingCycle: 'monthly',
    nextBillingDate: '2025-02-15',
    category: 'software',
    description: 'Creative design tools',
    website: 'https://adobe.com',
    reminderDays: 7,
    isActive: true,
    createdAt: '2024-01-10T00:00:00Z',
    updatedAt: '2024-01-10T00:00:00Z'
  },
  {
    id: '5',
    name: 'Dropbox Plus',
    price: 9.99,
    currency: 'USD',
    billingCycle: 'monthly',
    nextBillingDate: '2025-02-10',
    category: 'cloud_storage',
    description: 'Cloud storage service',
    website: 'https://dropbox.com',
    reminderDays: 3,
    isActive: true,
    createdAt: '2024-01-20T00:00:00Z',
    updatedAt: '2024-01-20T00:00:00Z'
  }
]

export default function DemoPage() {
  const [searchQuery, setSearchQuery] = useState('')
  const [showUpcoming, setShowUpcoming] = useState(false)

  // Calculate stats like the real dashboard
  const stats = {
    totalMonthly: demoSubscriptions.reduce((sum, sub) => 
      sub.billingCycle === 'monthly' ? sum + sub.price : sum + (sub.price / 12), 0
    ),
    totalYearly: demoSubscriptions.reduce((sum, sub) => 
      sub.billingCycle === 'monthly' ? sum + (sub.price * 12) : sum + sub.price, 0
    ),
    activeCount: demoSubscriptions.filter(sub => sub.isActive).length,
    dueSoon: demoSubscriptions.filter(sub => {
      const daysUntilRenewal = Math.ceil((new Date(sub.nextBillingDate).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24))
      return daysUntilRenewal <= 7
    }).length
  }

  // Filter subscriptions based on search
  const filteredSubscriptions = demoSubscriptions.filter(sub =>
    sub.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
    sub.category.toLowerCase().includes(searchQuery.toLowerCase())
  )

  const displaySubscriptions = showUpcoming 
    ? demoSubscriptions.filter(sub => {
        const daysUntilRenewal = Math.ceil((new Date(sub.nextBillingDate).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24))
        return daysUntilRenewal <= 30
      })
    : filteredSubscriptions

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
      {/* Demo Banner */}
      <div className="bg-gradient-to-r from-blue-600 to-purple-600 text-white py-3 px-4">
        <div className="max-w-7xl mx-auto flex items-center justify-between">
          <div className="flex items-center space-x-3">
            <div className="bg-white bg-opacity-20 rounded-full p-1">
              <BarChart3 className="h-5 w-5" />
            </div>
            <div>
              <p className="font-medium">Interactive Demo - CashControl Dashboard</p>
              <p className="text-sm opacity-90">This is a preview of your real dashboard experience</p>
            </div>
          </div>
          <Link 
            href="/auth/signup" 
            className="bg-white text-blue-600 px-4 py-2 rounded-lg font-medium hover:bg-gray-100 transition-colors"
          >
            Start Free Account
          </Link>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
          <p className="text-gray-600 dark:text-gray-400 mt-2">
            Manage your subscriptions and track your spending
          </p>
        </div>

        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div className="card">
            <div className="flex items-center">
              <div className="p-2 bg-primary-100 dark:bg-primary-900 rounded-lg">
                <DollarSign className="h-6 w-6 text-primary-600 dark:text-primary-400" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Monthly Total</p>
                <p className="text-2xl font-bold text-gray-900 dark:text-white">
                  ${stats.totalMonthly.toFixed(2)}
                </p>
              </div>
            </div>
          </div>

          <div className="card">
            <div className="flex items-center">
              <div className="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                <TrendingUp className="h-6 w-6 text-green-600 dark:text-green-400" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Yearly Total</p>
                <p className="text-2xl font-bold text-gray-900 dark:text-white">
                  ${stats.totalYearly.toFixed(2)}
                </p>
              </div>
            </div>
          </div>

          <div className="card">
            <div className="flex items-center">
              <div className="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                <Calendar className="h-6 w-6 text-blue-600 dark:text-blue-400" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Active</p>
                <p className="text-2xl font-bold text-gray-900 dark:text-white">
                  {stats.activeCount}
                </p>
              </div>
            </div>
          </div>

          <div className="card">
            <div className="flex items-center">
              <div className="p-2 bg-orange-100 dark:bg-orange-900 rounded-lg">
                <AlertCircle className="h-6 w-6 text-orange-600 dark:text-orange-400" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Due Soon</p>
                <p className="text-2xl font-bold text-gray-900 dark:text-white">
                  {stats.dueSoon}
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Action Buttons */}
        <div className="flex flex-col sm:flex-row gap-4 mb-6">
          <button className="btn-primary disabled:opacity-50 disabled:cursor-not-allowed" disabled>
            <Plus className="h-4 w-4 mr-2" />
            Add Subscription
          </button>
          <button className="btn-outline disabled:opacity-50 disabled:cursor-not-allowed" disabled>
            <Upload className="h-4 w-4 mr-2" />
            Bulk Import
          </button>
          <button className="btn-outline disabled:opacity-50 disabled:cursor-not-allowed" disabled>
            <Download className="h-4 w-4 mr-2" />
            Export Data
          </button>
          <button className="btn-outline disabled:opacity-50 disabled:cursor-not-allowed" disabled>
            <CreditCard className="h-4 w-4 mr-2" />
            Scan Bank Account
          </button>
        </div>

        {/* Search and Filter */}
        <div className="flex flex-col sm:flex-row gap-4 mb-6">
          <div className="relative flex-1">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
            <input
              type="text"
              placeholder="Search subscriptions..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="input-field pl-10"
            />
          </div>
          <div className="flex gap-2">
            <button
              onClick={() => setShowUpcoming(!showUpcoming)}
              className={`px-4 py-2 rounded-lg font-medium transition-colors ${
                showUpcoming
                  ? 'bg-primary-600 text-white'
                  : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'
              }`}
            >
              <Filter className="h-4 w-4 inline mr-2" />
              {showUpcoming ? 'All' : 'Upcoming'}
            </button>
          </div>
        </div>

        {/* Subscriptions List */}
        <div className="space-y-4">
          <div className="flex justify-between items-center">
            <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
              {showUpcoming ? 'Upcoming Renewals' : 'Your Subscriptions'}
            </h2>
            <span className="text-sm text-gray-500 dark:text-gray-400">
              {displaySubscriptions.length} subscription{displaySubscriptions.length !== 1 ? 's' : ''}
            </span>
          </div>

          {displaySubscriptions.length === 0 ? (
            <div className="card text-center py-12">
              <Calendar className="h-12 w-12 text-gray-400 mx-auto mb-4" />
              <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                {showUpcoming ? 'No upcoming renewals' : 'No subscriptions found'}
              </h3>
              <p className="text-gray-600 dark:text-gray-400 mb-4">
                {showUpcoming 
                  ? 'All your subscriptions are up to date!'
                  : searchQuery 
                    ? 'Try adjusting your search terms'
                    : 'Get started by adding your first subscription'
                }
              </p>
            </div>
          ) : (
            <div className="grid gap-4">
              {displaySubscriptions.map((subscription) => (
                <div key={subscription.id} className="card hover:shadow-md transition-shadow">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                      <div className={`w-12 h-12 rounded-lg flex items-center justify-center text-white font-bold text-lg ${
                        subscription.category === 'streaming' ? 'bg-red-500' :
                        subscription.category === 'music' ? 'bg-green-500' :
                        subscription.category === 'software' ? 'bg-blue-500' :
                        subscription.category === 'cloud_storage' ? 'bg-purple-500' :
                        'bg-gray-500'
                      }`}>
                        {subscription.name.charAt(0)}
                      </div>
                      <div>
                        <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                          {subscription.name}
                        </h3>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                          {subscription.description}
                        </p>
                        <div className="flex items-center space-x-4 mt-1">
                          <span className="text-sm text-gray-500 dark:text-gray-400">
                            Next billing: {new Date(subscription.nextBillingDate).toLocaleDateString()}
                          </span>
                          <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                            subscription.isActive 
                              ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                              : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                          }`}>
                            {subscription.isActive ? 'Active' : 'Inactive'}
                          </span>
                        </div>
                      </div>
                    </div>
                  
                    <div className="flex items-center space-x-4">
                      <div className="text-right">
                        <p className="text-lg font-semibold text-gray-900 dark:text-white">
                          ${subscription.price.toFixed(2)}
                        </p>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                          {subscription.billingCycle}
                        </p>
                      </div>
                      <button className="btn-outline p-2 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        <MoreVertical className="h-5 w-5" />
                      </button>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* CTA Section */}
        <div className="mt-12">
          <div className="bg-gradient-to-r from-primary-600 to-primary-700 rounded-lg p-8 text-white text-center">
            <h2 className="text-2xl font-bold mb-4">Ready to Take Control of Your Subscriptions?</h2>
            <p className="text-lg mb-6 opacity-90">
              This is exactly what your dashboard will look like. Create your free account to start tracking your real subscriptions!
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <Link 
                href="/auth/signup" 
                className="bg-white text-primary-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors inline-flex items-center justify-center gap-2"
              >
                Start Free Account
                <ArrowRight className="h-5 w-5" />
              </Link>
              <Link 
                href="/pricing" 
                className="bg-primary-800 text-white px-8 py-3 rounded-lg font-semibold hover:bg-primary-900 transition-colors border border-primary-500 inline-flex items-center justify-center gap-2"
              >
                View Pricing
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
