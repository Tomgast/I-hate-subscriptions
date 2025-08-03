'use client'

import { useState, useEffect } from 'react'
import { useSubscriptions } from '@/hooks/useSubscriptions'
import { Subscription, SubscriptionStats } from '@/types/subscription'
import { useSession } from 'next-auth/react'
import { useRouter } from 'next/navigation'
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
} from 'lucide-react'
import Link from 'next/link'
import { SubscriptionCard } from '@/components/SubscriptionCard'
import { StatsChart } from '@/components/StatsChart'
import { BulkImport } from '@/components/BulkImport'
import { exportToCSV, exportToPDF, exportToJSON } from '@/lib/exportUtils'

export default function DashboardPage() {
  const { data: session, status } = useSession()
  const router = useRouter()
  const {
    subscriptions,
    stats,
    loading,
    error,
    isAuthenticated,
    getUpcomingRenewals,
    searchSubscriptions,
    exportData
  } = useSubscriptions()
  
  // All useState hooks must be at the top before any conditional logic
  const [searchQuery, setSearchQuery] = useState('')
  const [showUpcoming, setShowUpcoming] = useState(false)
  const [upcomingRenewals, setUpcomingRenewals] = useState<Subscription[]>([])
  const [filteredSubscriptions, setFilteredSubscriptions] = useState<Subscription[]>([])
  const [showExportMenu, setShowExportMenu] = useState(false)
  const [showBulkImport, setShowBulkImport] = useState(false)
  const [bankScanLoading, setBankScanLoading] = useState(false)
  const [successMessage, setSuccessMessage] = useState('')

  // Redirect to login if not authenticated
  useEffect(() => {
    if (status === 'loading') return
    if (!isAuthenticated) {
      router.push('/auth/signin')
      return
    }
  }, [status, isAuthenticated, router])

  // Check for bank connection success message
  useEffect(() => {
    const urlParams = new URLSearchParams(window.location.search)
    const bankConnected = urlParams.get('bank_connected')
    const provider = urlParams.get('provider')
    
    if (bankConnected === 'success' && provider) {
      setSuccessMessage(`Successfully connected to ${provider.toUpperCase()}! European bank scanning is now ready.`)
      // Clean up URL params
      window.history.replaceState({}, '', window.location.pathname)
      
      // Clear message after 5 seconds
      setTimeout(() => setSuccessMessage(''), 5000)
    }
  }, [])

  // Load upcoming renewals and filtered subscriptions
  useEffect(() => {
    const loadData = async () => {
      if (!isAuthenticated) return
      
      try {
        const [upcoming, filtered] = await Promise.all([
          getUpcomingRenewals(30),
          searchSubscriptions(searchQuery)
        ])
        setUpcomingRenewals(upcoming)
        setFilteredSubscriptions(filtered)
      } catch (err) {
        console.error('Error loading dashboard data:', err)
      }
    }

    loadData()
  }, [isAuthenticated, searchQuery, subscriptions, getUpcomingRenewals, searchSubscriptions])

  const displaySubscriptions = showUpcoming ? upcomingRenewals : filteredSubscriptions

  // Show loading state while checking authentication
  if (status === 'loading' || loading) {
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

  // Don't render anything if not authenticated (will redirect)
  if (!isAuthenticated) {
    return null
  }

  // Show error state
  if (error) {
    return (
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="card text-center py-12">
          <AlertCircle className="h-12 w-12 text-red-400 mx-auto mb-4" />
          <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
            Error Loading Dashboard
          </h3>
          <p className="text-gray-600 dark:text-gray-400 mb-4">
            {error}
          </p>
          <button 
            onClick={() => window.location.reload()} 
            className="btn-primary"
          >
            Try Again
          </button>
        </div>
      </div>
    )
  }

  const handleExportCSV = () => {
    exportToCSV(subscriptions)
    setShowExportMenu(false)
  }

  const handleExportPDF = () => {
    exportToPDF(subscriptions)
    setShowExportMenu(false)
  }

  const handleExportJSON = async () => {
    try {
      const jsonData = await exportData()
      const blob = new Blob([jsonData], { type: 'application/json' })
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `subscriptions-${new Date().toISOString().split('T')[0]}.json`
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
      URL.revokeObjectURL(url)
    } catch (error) {
      console.error('Export error:', error)
      alert('Failed to export data. Please try again.')
    } finally {
      setShowExportMenu(false)
    }
  }

  const handleBankScan = async () => {
    setBankScanLoading(true)
    try {
      // Create TrueLayer link token for European banks
      const response = await fetch('/api/bank-providers/link-token', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          provider: 'truelayer',
          countryCode: 'GB' // Default to UK, could be detected from user location
        })
      })

      if (!response.ok) {
        throw new Error('Failed to create bank connection')
      }

      const { authUrl } = await response.json()
      
      // Redirect to TrueLayer for bank authentication
      window.location.href = authUrl
    } catch (error) {
      console.error('Bank scan error:', error)
      alert('Failed to connect to bank. Please try again.')
    } finally {
      setBankScanLoading(false)
    }
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
          <div className="relative">
            <button
              onClick={() => setShowExportMenu(!showExportMenu)}
              className="btn-secondary flex items-center gap-2"
            >
              <Download className="h-4 w-4" />
              Export
            </button>
            
            {showExportMenu && (
              <div className="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 z-10">
                <div className="py-1">
                  <button
                    onClick={handleExportCSV}
                    className="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                  >
                    Export as CSV
                  </button>
                  <button
                    onClick={handleExportPDF}
                    className="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                  >
                    Export as PDF
                  </button>
                  <button
                    onClick={handleExportJSON}
                    className="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                  >
                    Export as JSON
                  </button>
                </div>
              </div>
            )}
          </div>
          <button
            onClick={() => setShowBulkImport(true)}
            className="btn-secondary flex items-center gap-2"
          >
            <Upload className="h-4 w-4" />
            Bulk Import
          </button>
          <button
            onClick={handleBankScan}
            disabled={bankScanLoading}
            className="btn-secondary flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <CreditCard className="h-4 w-4" />
            {bankScanLoading ? 'Connecting...' : 'Scan Bank'}
          </button>
          <Link href="/subscriptions/new" className="btn-primary flex items-center gap-2">
            <Plus className="h-4 w-4" />
            Add Subscription
          </Link>
        </div>
      </div>

      {/* Success Message */}
      {successMessage && (
        <div className="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
          <div className="flex items-center">
            <CheckCircle className="h-5 w-5 text-green-400 mr-3" />
            <p className="text-green-800 dark:text-green-200">{successMessage}</p>
            <button 
              onClick={() => setSuccessMessage('')}
              className="ml-auto text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-200"
            >
              ×
            </button>
          </div>
        </div>
      )}

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div className="stat-card">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Monthly Spend</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-white">
                ${stats?.totalMonthly?.toFixed(2) || '0.00'}
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
                ${stats?.totalYearly?.toFixed(2) || '0.00'}
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
                {stats?.activeCount || 0}
              </p>
            </div>
            <Calendar className="h-8 w-8 text-primary-600 dark:text-primary-400" />
          </div>
        </div>

        <div className="stat-card">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Due Soon</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-white">
                {stats?.dueSoon || 0}
              </p>
            </div>
            <AlertCircle className="h-8 w-8 text-primary-600 dark:text-primary-400" />
          </div>
        </div>
      </div>

      {/* Charts */}
      {stats?.monthlyTrend && stats.monthlyTrend.length > 0 && (
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
              {stats?.categoryBreakdown && Object.entries(stats.categoryBreakdown)
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

      {/* Bulk Import Modal */}
      {showBulkImport && (
        <BulkImport onClose={() => setShowBulkImport(false)} />
      )}
    </div>
  )
}
