'use client'

import { useState, useEffect } from 'react'
import { subscriptionStore } from '@/lib/subscriptionStore'
import { Subscription, SubscriptionStats } from '@/types/subscription'
import { 
  DollarSign, 
  TrendingUp, 
  Calendar, 
  AlertCircle,
  Plus,
  Filter,
  Search,
  Download
} from 'lucide-react'
import Link from 'next/link'
import { SubscriptionCard } from '@/components/SubscriptionCard'
import { StatsChart } from '@/components/StatsChart'

export default function DashboardPage() {
  const [subscriptions, setSubscriptions] = useState<Subscription[]>([])
  const [stats, setStats] = useState<SubscriptionStats | null>(null)
  const [searchQuery, setSearchQuery] = useState('')
  const [showUpcoming, setShowUpcoming] = useState(false)

  useEffect(() => {
    const loadData = () => {
      setSubscriptions(subscriptionStore.getSubscriptions())
      setStats(subscriptionStore.getStats())
    }

    loadData()
    const unsubscribe = subscriptionStore.subscribe(loadData)
    return unsubscribe
  }, [])

  const upcomingRenewals = subscriptionStore.getUpcomingRenewals(30)
  const filteredSubscriptions = subscriptionStore.searchSubscriptions(searchQuery)
  const displaySubscriptions = showUpcoming ? upcomingRenewals : filteredSubscriptions

  const handleExport = () => {
    const data = subscriptionStore.exportData()
    const blob = new Blob([data], { type: 'application/json' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = 'subscriptions.json'
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
  }

  if (!stats) {
    return (
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="animate-pulse">
          <div className="h-8 bg-gray-200 dark:bg-gray-700 rounded w-1/4 mb-8"></div>
          <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            {[...Array(4)].map((_, i) => (
              <div key={i} className="h-32 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
            ))}
          </div>
        </div>
      </div>
    )
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
          <p className="text-gray-600 dark:text-gray-400 mt-1">
            Track and manage all your subscriptions
          </p>
        </div>
        <div className="flex gap-3 mt-4 sm:mt-0">
          <button
            onClick={handleExport}
            className="btn-secondary flex items-center gap-2"
          >
            <Download className="h-4 w-4" />
            Export
          </button>
          <Link href="/subscriptions/new" className="btn-primary flex items-center gap-2">
            <Plus className="h-4 w-4" />
            Add Subscription
          </Link>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div className="stat-card">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Monthly Spend</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-white">
                ${stats.totalMonthly.toFixed(2)}
              </p>
            </div>
            <DollarSign className="h-8 w-8 text-primary-600 dark:text-primary-400" />
          </div>
        </div>

        <div className="stat-card">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Yearly Spend</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-white">
                ${stats.totalYearly.toFixed(2)}
              </p>
            </div>
            <TrendingUp className="h-8 w-8 text-primary-600 dark:text-primary-400" />
          </div>
        </div>

        <div className="stat-card">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Active Subscriptions</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-white">
                {stats.activeCount}
              </p>
            </div>
            <Calendar className="h-8 w-8 text-primary-600 dark:text-primary-400" />
          </div>
        </div>

        <div className="stat-card">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Upcoming Renewals</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-white">
                {stats.upcomingRenewals}
              </p>
            </div>
            <AlertCircle className="h-8 w-8 text-primary-600 dark:text-primary-400" />
          </div>
        </div>
      </div>

      {/* Charts */}
      {stats.monthlyTrend.length > 0 && (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
          <div className="card">
            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
              Monthly Spending Trend
            </h3>
            <StatsChart data={stats.monthlyTrend} />
          </div>
          
          <div className="card">
            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
              Spending by Category
            </h3>
            <div className="space-y-3">
              {Object.entries(stats.categoryBreakdown)
                .filter(([_, amount]) => amount > 0)
                .sort(([_, a], [__, b]) => b - a)
                .slice(0, 5)
                .map(([category, amount]) => (
                  <div key={category} className="flex justify-between items-center">
                    <span className="text-sm text-gray-600 dark:text-gray-400 capitalize">
                      {category.replace('_', ' ')}
                    </span>
                    <span className="font-medium text-gray-900 dark:text-white">
                      ${amount.toFixed(2)}
                    </span>
                  </div>
                ))}
            </div>
          </div>
        </div>
      )}

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
            {!showUpcoming && !searchQuery && (
              <Link href="/subscriptions/new" className="btn-primary">
                Add Your First Subscription
              </Link>
            )}
          </div>
        ) : (
          <div className="grid gap-4">
            {displaySubscriptions.map((subscription) => (
              <SubscriptionCard key={subscription.id} subscription={subscription} />
            ))}
          </div>
        )}
      </div>
    </div>
  )
}
