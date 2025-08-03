'use client'

import { useState } from 'react'
import Link from 'next/link'
import { 
  DollarSign, 
  Calendar, 
  TrendingUp, 
  AlertCircle, 
  Search, 
  Filter, 
  Download, 
  Plus,
  BarChart3,
  PieChart,
  Edit,
  Trash2,
  ArrowRight
} from 'lucide-react'
import { Subscription, SubscriptionStats } from '@/types/subscription'

export default function DemoPage() {
  const [searchTerm, setSearchTerm] = useState('')
  const [selectedCategory, setSelectedCategory] = useState('all')
  const [sortBy, setSortBy] = useState('nextBilling')
  const [viewMode, setViewMode] = useState<'list' | 'chart'>('list')

  // Expanded demo subscriptions data
  const demoSubscriptions: Subscription[] = [
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
      name: 'Adobe Creative Cloud',
      price: 52.99,
      currency: 'USD',
      billingCycle: 'monthly',
      nextBillingDate: '2025-02-01',
      category: 'software',
      description: 'Creative software suite',
      website: 'https://adobe.com',
      reminderDays: 7,
      isActive: true,
      createdAt: '2024-01-01T00:00:00Z',
      updatedAt: '2024-01-01T00:00:00Z'
    },
    {
      id: '4',
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
      id: '5',
      name: 'Gym Membership',
      price: 29.99,
      currency: 'USD',
      billingCycle: 'monthly',
      nextBillingDate: '2025-01-30',
      category: 'fitness',
      description: 'Local gym membership',
      reminderDays: 5,
      isActive: true,
      createdAt: '2024-01-10T00:00:00Z',
      updatedAt: '2024-01-10T00:00:00Z'
    },
    {
      id: '6',
      name: 'Disney+',
      price: 7.99,
      currency: 'USD',
      billingCycle: 'monthly',
      nextBillingDate: '2025-02-05',
      category: 'streaming',
      description: 'Disney streaming service',
      website: 'https://disneyplus.com',
      reminderDays: 3,
      isActive: true,
      createdAt: '2024-02-15T00:00:00Z',
      updatedAt: '2024-02-15T00:00:00Z'
    },
    {
      id: '7',
      name: 'Notion Pro',
      price: 8.00,
      currency: 'USD',
      billingCycle: 'monthly',
      nextBillingDate: '2025-01-22',
      category: 'productivity',
      description: 'Note-taking and organization',
      website: 'https://notion.so',
      reminderDays: 3,
      isActive: true,
      createdAt: '2024-04-01T00:00:00Z',
      updatedAt: '2024-04-01T00:00:00Z'
    },
    {
      id: '8',
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
    },
    {
      id: '9',
      name: 'VPN Service',
      price: 12.99,
      currency: 'USD',
      billingCycle: 'monthly',
      nextBillingDate: '2025-01-26',
      category: 'software',
      description: 'Privacy and security VPN',
      reminderDays: 3,
      isActive: false,
      createdAt: '2024-05-01T00:00:00Z',
      updatedAt: '2024-05-01T00:00:00Z'
    },
    {
      id: '10',
      name: 'Canva Pro',
      price: 14.99,
      currency: 'USD',
      billingCycle: 'monthly',
      nextBillingDate: '2025-02-15',
      category: 'software',
      description: 'Design and graphics tool',
      website: 'https://canva.com',
      reminderDays: 3,
      isActive: true,
      createdAt: '2024-03-15T00:00:00Z',
      updatedAt: '2024-03-15T00:00:00Z'
    }
  ]

  const calculateStats = (): SubscriptionStats => {
    const activeSubscriptions = demoSubscriptions.filter(sub => sub.isActive)
    const totalMonthly = activeSubscriptions.reduce((sum, sub) => {
      const monthlyAmount = sub.billingCycle === 'monthly' ? sub.price :
                           sub.billingCycle === 'yearly' ? sub.price / 12 :
                           sub.billingCycle === 'quarterly' ? sub.price / 3 :
                           sub.price * 4.33 // weekly
      return sum + monthlyAmount
    }, 0)

    const now = new Date()
    const dueSoon = activeSubscriptions.filter(sub => {
      const nextBilling = new Date(sub.nextBillingDate)
      const diffTime = nextBilling.getTime() - now.getTime()
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
      return diffDays <= 7 && diffDays >= 0
    }).length

    return {
      totalMonthly,
      totalYearly: totalMonthly * 12,
      dueSoon,
      activeSubscriptions: activeSubscriptions.length
    }
  }

  const stats = calculateStats()

  const filteredSubscriptions = demoSubscriptions.filter(subscription => {
    const matchesSearch = subscription.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         subscription.description?.toLowerCase().includes(searchTerm.toLowerCase())
    const matchesCategory = selectedCategory === 'all' || subscription.category === selectedCategory
    return matchesSearch && matchesCategory
  })

  const sortedSubscriptions = [...filteredSubscriptions].sort((a, b) => {
    switch (sortBy) {
      case 'name':
        return a.name.localeCompare(b.name)
      case 'price':
        return b.price - a.price
      case 'nextBilling':
        return new Date(a.nextBillingDate).getTime() - new Date(b.nextBillingDate).getTime()
      default:
        return 0
    }
  })

  const categories = ['all', 'streaming', 'music', 'software', 'fitness', 'productivity', 'cloud_storage']

  // Mock chart data for spending over time
  const chartData = [
    { month: 'Jul', amount: 142.50 },
    { month: 'Aug', amount: 158.90 },
    { month: 'Sep', amount: 165.45 },
    { month: 'Oct', amount: 171.94 },
    { month: 'Nov', amount: 168.92 },
    { month: 'Dec', amount: 171.94 },
  ]

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
      {/* Header */}
      <div className="bg-white dark:bg-gray-800 shadow-sm">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-4">
              <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Demo Dashboard</h1>
              <div className="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-3 py-1 rounded-full text-sm font-medium">
                Preview Mode
              </div>
            </div>
            <div className="flex items-center space-x-4">
              <Link href="/auth/signup" className="btn-primary">
                Get Started
              </Link>
            </div>
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <div className="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <DollarSign className="h-8 w-8 text-green-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Monthly Total</p>
                <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                  ${stats.totalMonthly.toFixed(2)}
                </p>
              </div>
            </div>
          </div>

          <div className="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <TrendingUp className="h-8 w-8 text-blue-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Yearly Total</p>
                <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                  ${stats.totalYearly.toFixed(2)}
                </p>
              </div>
            </div>
          </div>

          <div className="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <AlertCircle className="h-8 w-8 text-orange-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Due Soon</p>
                <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                  {stats.dueSoon}
                </p>
              </div>
            </div>
          </div>

          <div className="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <Calendar className="h-8 w-8 text-purple-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Active</p>
                <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                  {stats.activeSubscriptions}
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Spending Chart */}
        <div className="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm mb-8">
          <div className="flex items-center justify-between mb-6">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Spending Over Time</h2>
            <div className="flex space-x-2">
              <button
                onClick={() => setViewMode('chart')}
                className={`p-2 rounded-lg ${viewMode === 'chart' ? 'bg-primary-100 text-primary-600' : 'text-gray-400 hover:text-gray-600'}`}
              >
                <BarChart3 className="h-5 w-5" />
              </button>
              <button
                onClick={() => setViewMode('list')}
                className={`p-2 rounded-lg ${viewMode === 'list' ? 'bg-primary-100 text-primary-600' : 'text-gray-400 hover:text-gray-600'}`}
              >
                <PieChart className="h-5 w-5" />
              </button>
            </div>
          </div>
          
          {/* Simple Chart */}
          <div className="h-64 flex items-end space-x-4">
            {chartData.map((data, index) => (
              <div key={index} className="flex-1 flex flex-col items-center">
                <div 
                  className="w-full bg-primary-600 rounded-t-lg mb-2"
                  style={{ height: `${(data.amount / 200) * 100}%` }}
                ></div>
                <div className="text-sm text-gray-600 dark:text-gray-400">{data.month}</div>
                <div className="text-xs text-gray-500 dark:text-gray-500">${data.amount}</div>
              </div>
            ))}
          </div>
        </div>

        {/* Controls */}
        <div className="flex flex-col sm:flex-row gap-4 mb-6">
          <div className="flex-1">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
              <input
                type="text"
                placeholder="Search subscriptions..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="input-field pl-10"
              />
            </div>
          </div>
          
          <div className="flex gap-4">
            <select
              value={selectedCategory}
              onChange={(e) => setSelectedCategory(e.target.value)}
              className="input-field"
            >
              {categories.map(category => (
                <option key={category} value={category}>
                  {category === 'all' ? 'All Categories' : category.charAt(0).toUpperCase() + category.slice(1)}
                </option>
              ))}
            </select>
            
            <select
              value={sortBy}
              onChange={(e) => setSortBy(e.target.value)}
              className="input-field"
            >
              <option value="nextBilling">Next Billing</option>
              <option value="name">Name</option>
              <option value="price">Price</option>
            </select>
            
            <button className="btn-secondary flex items-center gap-2">
              <Plus className="h-4 w-4" />
              Add Subscription
            </button>
            
            <button className="btn-secondary flex items-center gap-2">
              <Download className="h-4 w-4" />
              Export
            </button>
          </div>
        </div>

        {/* Subscriptions List */}
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
          <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
              Your Subscriptions ({sortedSubscriptions.length})
            </h2>
          </div>
          
          <div className="divide-y divide-gray-200 dark:divide-gray-700">
            {sortedSubscriptions.map((subscription) => (
              <div key={subscription.id} className="p-6 hover:bg-gray-50 dark:hover:bg-gray-700">
                <div className="flex items-center justify-between">
                  <div className="flex items-center space-x-4">
                    <div className={`w-12 h-12 rounded-lg flex items-center justify-center text-white font-bold text-lg ${
                      subscription.category === 'streaming' ? 'bg-red-500' :
                      subscription.category === 'music' ? 'bg-green-500' :
                      subscription.category === 'software' ? 'bg-blue-500' :
                      subscription.category === 'fitness' ? 'bg-orange-500' :
                      subscription.category === 'productivity' ? 'bg-purple-500' :
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
                      <div className="text-lg font-semibold text-gray-900 dark:text-white">
                        ${subscription.price.toFixed(2)}
                      </div>
                      <div className="text-sm text-gray-500 dark:text-gray-400">
                        {subscription.billingCycle}
                      </div>
                    </div>
                    
                    <div className="flex space-x-2">
                      <button className="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <Edit className="h-4 w-4" />
                      </button>
                      <button className="p-2 text-gray-400 hover:text-red-600">
                        <Trash2 className="h-4 w-4" />
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* CTA */}
        <div className="mt-12 text-center">
          <div className="bg-gradient-to-r from-primary-600 to-primary-700 rounded-lg p-8 text-white">
            <h2 className="text-2xl font-bold mb-4">Ready to Take Control?</h2>
            <p className="text-lg mb-6 opacity-90">
              This is just a preview. Create your account to start managing your real subscriptions and save money!
            </p>
            <Link href="/auth/signup" className="bg-white text-primary-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors inline-flex items-center gap-2">
              Get Started Now
              <ArrowRight className="h-5 w-5" />
            </Link>
          </div>
        </div>
      </div>
    </div>
  )
}
