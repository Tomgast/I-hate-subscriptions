'use client'

import { useState, useEffect } from 'react'
import { subscriptionStore } from '@/lib/subscriptionStore'
import { Subscription, SubscriptionCategory, SUBSCRIPTION_CATEGORIES } from '@/types/subscription'
import { SubscriptionCard } from '@/components/SubscriptionCard'
import { BulkImport } from '@/components/BulkImport'
import { 
  Plus, 
  Search, 
  Filter, 
  Upload, 
  Download,
  SortAsc,
  SortDesc,
  Grid,
  List
} from 'lucide-react'
import Link from 'next/link'

type SortOption = 'name' | 'price' | 'nextBilling' | 'category'
type SortDirection = 'asc' | 'desc'
type ViewMode = 'grid' | 'list'

export default function SubscriptionsPage() {
  const [subscriptions, setSubscriptions] = useState<Subscription[]>([])
  const [searchQuery, setSearchQuery] = useState('')
  const [selectedCategory, setSelectedCategory] = useState<SubscriptionCategory | 'all'>('all')
  const [showActiveOnly, setShowActiveOnly] = useState(false)
  const [sortBy, setSortBy] = useState<SortOption>('name')
  const [sortDirection, setSortDirection] = useState<SortDirection>('asc')
  const [viewMode, setViewMode] = useState<ViewMode>('grid')
  const [showImportModal, setShowImportModal] = useState(false)

  useEffect(() => {
    const loadData = () => {
      setSubscriptions(subscriptionStore.getSubscriptions())
    }

    loadData()
    const unsubscribe = subscriptionStore.subscribe(loadData)
    return unsubscribe
  }, [])

  // Filter and sort subscriptions
  const filteredAndSortedSubscriptions = subscriptions
    .filter(sub => {
      // Text search
      if (searchQuery.trim()) {
        const query = searchQuery.toLowerCase()
        const matchesSearch = 
          sub.name.toLowerCase().includes(query) ||
          sub.description?.toLowerCase().includes(query) ||
          sub.tags?.some(tag => tag.toLowerCase().includes(query))
        if (!matchesSearch) return false
      }

      // Category filter
      if (selectedCategory !== 'all' && sub.category !== selectedCategory) {
        return false
      }

      // Active filter
      if (showActiveOnly && !sub.isActive) {
        return false
      }

      return true
    })
    .sort((a, b) => {
      let comparison = 0
      
      switch (sortBy) {
        case 'name':
          comparison = a.name.localeCompare(b.name)
          break
        case 'price':
          const aMonthly = getMonthlyAmount(a)
          const bMonthly = getMonthlyAmount(b)
          comparison = aMonthly - bMonthly
          break
        case 'nextBilling':
          comparison = new Date(a.nextBillingDate).getTime() - new Date(b.nextBillingDate).getTime()
          break
        case 'category':
          comparison = a.category.localeCompare(b.category)
          break
      }
      
      return sortDirection === 'asc' ? comparison : -comparison
    })

  const getMonthlyAmount = (subscription: Subscription) => {
    const multipliers = {
      monthly: 1,
      yearly: 1/12,
      quarterly: 1/3,
      weekly: 4.33,
      daily: 30
    }
    return subscription.price * (multipliers[subscription.billingCycle] || 1)
  }

  const handleSort = (option: SortOption) => {
    if (sortBy === option) {
      setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc')
    } else {
      setSortBy(option)
      setSortDirection('asc')
    }
  }

  const handleExport = () => {
    const data = subscriptionStore.exportData()
    const blob = new Blob([data], { type: 'application/json' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `subscriptions-${new Date().toISOString().split('T')[0]}.json`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
  }

  const totalMonthlySpend = filteredAndSortedSubscriptions
    .filter(sub => sub.isActive)
    .reduce((total, sub) => total + getMonthlyAmount(sub), 0)

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
            All Subscriptions
          </h1>
          <p className="text-gray-600 dark:text-gray-400 mt-1">
            Manage and track all your recurring payments
          </p>
        </div>
        <div className="flex gap-3 mt-4 sm:mt-0">
          <button
            onClick={() => setShowImportModal(true)}
            className="btn-secondary flex items-center gap-2"
          >
            <Upload className="h-4 w-4" />
            Import CSV
          </button>
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

      {/* Stats Summary */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div className="stat-card">
          <h3 className="text-sm font-medium text-gray-600 dark:text-gray-400">Total Subscriptions</h3>
          <p className="text-2xl font-bold text-gray-900 dark:text-white">
            {filteredAndSortedSubscriptions.length}
          </p>
        </div>
        <div className="stat-card">
          <h3 className="text-sm font-medium text-gray-600 dark:text-gray-400">Active Subscriptions</h3>
          <p className="text-2xl font-bold text-gray-900 dark:text-white">
            {filteredAndSortedSubscriptions.filter(sub => sub.isActive).length}
          </p>
        </div>
        <div className="stat-card">
          <h3 className="text-sm font-medium text-gray-600 dark:text-gray-400">Monthly Spend (Filtered)</h3>
          <p className="text-2xl font-bold text-gray-900 dark:text-white">
            ${totalMonthlySpend.toFixed(2)}
          </p>
        </div>
      </div>

      {/* Filters and Search */}
      <div className="card mb-6">
        <div className="flex flex-col lg:flex-row gap-4">
          {/* Search */}
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

          {/* Category Filter */}
          <select
            value={selectedCategory}
            onChange={(e) => setSelectedCategory(e.target.value as SubscriptionCategory | 'all')}
            className="input-field"
          >
            <option value="all">All Categories</option>
            {Object.entries(SUBSCRIPTION_CATEGORIES).map(([key, { label }]) => (
              <option key={key} value={key}>
                {label}
              </option>
            ))}
          </select>

          {/* Active Filter */}
          <label className="flex items-center whitespace-nowrap">
            <input
              type="checkbox"
              checked={showActiveOnly}
              onChange={(e) => setShowActiveOnly(e.target.checked)}
              className="rounded border-gray-300 text-primary-600 focus:ring-primary-500 mr-2"
            />
            <span className="text-sm text-gray-700 dark:text-gray-300">Active only</span>
          </label>
        </div>

        {/* Sort and View Options */}
        <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
          <div className="flex items-center gap-2 mb-4 sm:mb-0">
            <span className="text-sm text-gray-600 dark:text-gray-400">Sort by:</span>
            {(['name', 'price', 'nextBilling', 'category'] as const).map((option) => (
              <button
                key={option}
                onClick={() => handleSort(option)}
                className={`flex items-center gap-1 px-3 py-1 rounded-md text-sm transition-colors ${
                  sortBy === option
                    ? 'bg-primary-100 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300'
                    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'
                }`}
              >
                {option === 'nextBilling' ? 'Next Billing' : option.charAt(0).toUpperCase() + option.slice(1)}
                {sortBy === option && (
                  sortDirection === 'asc' ? <SortAsc className="h-3 w-3" /> : <SortDesc className="h-3 w-3" />
                )}
              </button>
            ))}
          </div>

          <div className="flex items-center gap-2">
            <span className="text-sm text-gray-600 dark:text-gray-400">View:</span>
            <button
              onClick={() => setViewMode('grid')}
              className={`p-2 rounded-md transition-colors ${
                viewMode === 'grid'
                  ? 'bg-primary-100 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300'
                  : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'
              }`}
            >
              <Grid className="h-4 w-4" />
            </button>
            <button
              onClick={() => setViewMode('list')}
              className={`p-2 rounded-md transition-colors ${
                viewMode === 'list'
                  ? 'bg-primary-100 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300'
                  : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'
              }`}
            >
              <List className="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>

      {/* Subscriptions List */}
      {filteredAndSortedSubscriptions.length === 0 ? (
        <div className="card text-center py-12">
          <div className="mx-auto w-12 h-12 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
            <Search className="h-6 w-6 text-gray-400" />
          </div>
          <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
            No subscriptions found
          </h3>
          <p className="text-gray-600 dark:text-gray-400 mb-4">
            {searchQuery || selectedCategory !== 'all' || showActiveOnly
              ? 'Try adjusting your search or filters'
              : 'Get started by adding your first subscription'
            }
          </p>
          {!searchQuery && selectedCategory === 'all' && !showActiveOnly && (
            <Link href="/subscriptions/new" className="btn-primary">
              Add Your First Subscription
            </Link>
          )}
        </div>
      ) : (
        <div className={viewMode === 'grid' ? 'space-y-4' : 'grid gap-4'}>
          {filteredAndSortedSubscriptions.map((subscription) => (
            <SubscriptionCard key={subscription.id} subscription={subscription} />
          ))}
        </div>
      )}

      {/* Bulk Import Modal */}
      {showImportModal && (
        <BulkImport onClose={() => setShowImportModal(false)} />
      )}
    </div>
  )
}
