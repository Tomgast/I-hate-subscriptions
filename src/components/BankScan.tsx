'use client'

import { useState, useEffect } from 'react'
import { useSession } from 'next-auth/react'
import { 
  CreditCard, 
  Loader2, 
  CheckCircle, 
  AlertCircle, 
  DollarSign,
  Calendar,
  Building2,
  Shield,
  Eye,
  EyeOff,
  ExternalLink
} from 'lucide-react'
import Link from 'next/link'
import { DetectedSubscription } from '@/lib/bank-scan'

interface BankScanProps {
  userTier: 'free' | 'pro'
  onSubscriptionsAdded?: (count: number) => void
}

export function BankScan({ userTier, onSubscriptionsAdded }: BankScanProps) {
  const [isScanning, setIsScanning] = useState(false)
  const [scanComplete, setScanComplete] = useState(false)
  const [detectedSubscriptions, setDetectedSubscriptions] = useState<DetectedSubscription[]>([])
  const [selectedSubscriptions, setSelectedSubscriptions] = useState<Set<number>>(new Set())
  const [isConfirming, setIsConfirming] = useState(false)
  const [scanResults, setScanResults] = useState<{
    totalTransactionsAnalyzed: number
    analysisDateRange: { startDate: string; endDate: string }
  } | null>(null)

  // Mock bank connection for demo - in production this would use Plaid Link
  const handleBankScan = async () => {
    if (userTier !== 'pro') {
      alert('Bank account scanning is a Pro feature. Please upgrade to access this functionality.')
      return
    }

    setIsScanning(true)
    
    try {
      // Simulate bank connection and scanning process
      await new Promise(resolve => setTimeout(resolve, 3000))
      
      const response = await fetch('/api/bank-scan', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          accessToken: 'demo_access_token', // In production, this comes from Plaid Link
          accountIds: ['demo_account_1']
        })
      })

      if (!response.ok) {
        throw new Error('Failed to scan bank account')
      }

      const data = await response.json()
      setDetectedSubscriptions(data.detectedSubscriptions)
      setScanResults({
        totalTransactionsAnalyzed: data.totalTransactionsAnalyzed,
        analysisDateRange: data.analysisDateRange
      })
      
      // Pre-select all high-confidence subscriptions
      const highConfidenceIndices = new Set(
        data.detectedSubscriptions
          .map((sub: DetectedSubscription, index: number) => sub.confidence > 0.8 ? index : -1)
          .filter((index: number) => index !== -1)
      )
      setSelectedSubscriptions(highConfidenceIndices)
      
      setScanComplete(true)
    } catch (error) {
      console.error('Bank scan error:', error)
      alert('Failed to scan bank account. Please try again.')
    } finally {
      setIsScanning(false)
    }
  }

  const handleConfirmSubscriptions = async () => {
    setIsConfirming(true)
    
    try {
      const confirmedSubs = Array.from(selectedSubscriptions).map(index => detectedSubscriptions[index])
      
      const response = await fetch('/api/bank-scan/confirm', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ confirmedSubscriptions: confirmedSubs })
      })

      if (!response.ok) {
        throw new Error('Failed to add subscriptions')
      }

      const data = await response.json()
      
      // Notify parent component
      onSubscriptionsAdded?.(data.addedSubscriptions.length)
      
      // Reset state
      setScanComplete(false)
      setDetectedSubscriptions([])
      setSelectedSubscriptions(new Set())
      setScanResults(null)
      
      alert(`Successfully added ${data.addedSubscriptions.length} subscriptions to your account!`)
      
    } catch (error) {
      console.error('Confirm subscriptions error:', error)
      alert('Failed to add subscriptions. Please try again.')
    } finally {
      setIsConfirming(false)
    }
  }

  const toggleSubscriptionSelection = (index: number) => {
    const newSelection = new Set(selectedSubscriptions)
    if (newSelection.has(index)) {
      newSelection.delete(index)
    } else {
      newSelection.add(index)
    }
    setSelectedSubscriptions(newSelection)
  }

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD'
    }).format(amount)
  }

  const getConfidenceColor = (confidence: number) => {
    if (confidence >= 0.9) return 'text-green-600 dark:text-green-400'
    if (confidence >= 0.8) return 'text-yellow-600 dark:text-yellow-400'
    return 'text-red-600 dark:text-red-400'
  }

  const getConfidenceText = (confidence: number) => {
    if (confidence >= 0.9) return 'High'
    if (confidence >= 0.8) return 'Medium'
    return 'Low'
  }

  if (userTier !== 'pro') {
    return (
      <div className="bg-gradient-to-r from-primary-50 to-primary-100 dark:from-primary-900/20 dark:to-primary-800/20 rounded-lg p-6 border border-primary-200 dark:border-primary-800">
        <div className="flex items-center mb-4">
          <Building2 className="h-8 w-8 text-primary-600 dark:text-primary-400 mr-3" />
          <div>
            <h3 className="text-lg font-semibold text-primary-900 dark:text-primary-100">
              Bank Account Scan
            </h3>
            <p className="text-primary-700 dark:text-primary-300 text-sm">
              Automatically discover subscriptions from your bank transactions
            </p>
          </div>
        </div>
        
        <div className="bg-white dark:bg-gray-800 rounded-lg p-4 mb-4">
          <div className="flex items-center text-sm text-gray-600 dark:text-gray-400 mb-2">
            <Shield className="h-4 w-4 mr-2" />
            <span>Secure & Private</span>
          </div>
          <ul className="text-sm text-gray-700 dark:text-gray-300 space-y-1">
            <li>• Scan 6 months of transaction history</li>
            <li>• Automatically detect recurring payments</li>
            <li>• Add discovered subscriptions with one click</li>
            <li>• Bank credentials never stored</li>
          </ul>
        </div>
        
        <div className="bg-primary-600 text-white rounded-lg p-4 text-center">
          <p className="font-medium mb-2">Pro Feature</p>
          <p className="text-sm opacity-90">
            Upgrade to Pro to scan your bank account and automatically discover all your subscriptions
          </p>
        </div>
      </div>
    )
  }

  if (scanComplete && detectedSubscriptions.length > 0) {
    return (
      <div className="space-y-6">
        {/* Scan Results Header */}
        <div className="bg-green-50 dark:bg-green-900/20 rounded-lg p-6 border border-green-200 dark:border-green-800">
          <div className="flex items-center mb-4">
            <CheckCircle className="h-8 w-8 text-green-600 dark:text-green-400 mr-3" />
            <div>
              <h3 className="text-lg font-semibold text-green-900 dark:text-green-100">
                Bank Scan Complete!
              </h3>
              <p className="text-green-700 dark:text-green-300 text-sm">
                Found {detectedSubscriptions.length} potential subscriptions
              </p>
            </div>
          </div>
          
          {scanResults && (
            <div className="text-sm text-green-700 dark:text-green-300">
              <p>Analyzed {scanResults.totalTransactionsAnalyzed} transactions from {scanResults.analysisDateRange.startDate} to {scanResults.analysisDateRange.endDate}</p>
            </div>
          )}
        </div>

        {/* Detected Subscriptions */}
        <div className="bg-white dark:bg-gray-800 rounded-lg p-6">
          <h4 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Select subscriptions to add to your account:
          </h4>
          
          <div className="space-y-4 mb-6">
            {detectedSubscriptions.map((subscription, index) => (
              <div 
                key={index}
                className={`border rounded-lg p-4 cursor-pointer transition-colors ${
                  selectedSubscriptions.has(index)
                    ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                }`}
                onClick={() => toggleSubscriptionSelection(index)}
              >
                <div className="flex items-start justify-between">
                  <div className="flex items-start space-x-4 flex-1">
                    {/* Subscription Logo */}
                    <div className="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center flex-shrink-0">
                      {subscription.logo_url ? (
                        <img 
                          src={subscription.logo_url} 
                          alt={subscription.merchant_name}
                          className="w-8 h-8 rounded"
                          onError={(e) => {
                            const target = e.target as HTMLImageElement
                            target.style.display = 'none'
                            target.nextElementSibling?.classList.remove('hidden')
                          }}
                        />
                      ) : null}
                      <Building2 className={`h-6 w-6 text-gray-400 ${subscription.logo_url ? 'hidden' : ''}`} />
                    </div>
                    
                    {/* Subscription Details */}
                    <div className="flex-1">
                      <div className="flex items-center space-x-2 mb-1">
                        <h5 className="font-medium text-gray-900 dark:text-white">
                          {subscription.merchant_name}
                        </h5>
                        <span className={`text-xs px-2 py-1 rounded-full ${getConfidenceColor(subscription.confidence)} bg-current bg-opacity-10`}>
                          {getConfidenceText(subscription.confidence)} confidence
                        </span>
                      </div>
                      
                      <div className="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                        <div className="flex items-center">
                          <DollarSign className="h-4 w-4 mr-1" />
                          <span>{formatCurrency(subscription.amount)}</span>
                        </div>
                        <div className="flex items-center">
                          <Calendar className="h-4 w-4 mr-1" />
                          <span className="capitalize">{subscription.frequency}</span>
                        </div>
                        <div>
                          <span className="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                            {subscription.category}
                          </span>
                        </div>
                      </div>
                      
                      <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Next billing: {subscription.next_billing_date} • Based on {subscription.transactions.length} transactions
                      </p>
                    </div>
                  </div>
                  
                  {/* Selection Indicator */}
                  <div className="flex-shrink-0 ml-4">
                    {selectedSubscriptions.has(index) ? (
                      <CheckCircle className="h-6 w-6 text-primary-600 dark:text-primary-400" />
                    ) : (
                      <div className="h-6 w-6 border-2 border-gray-300 dark:border-gray-600 rounded-full" />
                    )}
                  </div>
                </div>
              </div>
            ))}
          </div>
          
          {/* Action Buttons */}
          <div className="flex justify-between items-center">
            <button
              onClick={() => {
                setScanComplete(false)
                setDetectedSubscriptions([])
                setSelectedSubscriptions(new Set())
              }}
              className="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
            >
              Cancel
            </button>
            
            <div className="flex space-x-3">
              <button
                onClick={() => {
                  if (selectedSubscriptions.size === detectedSubscriptions.length) {
                    setSelectedSubscriptions(new Set())
                  } else {
                    setSelectedSubscriptions(new Set(detectedSubscriptions.map((_, index) => index)))
                  }
                }}
                className="text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300"
              >
                {selectedSubscriptions.size === detectedSubscriptions.length ? 'Deselect All' : 'Select All'}
              </button>
              
              <button
                onClick={handleConfirmSubscriptions}
                disabled={selectedSubscriptions.size === 0 || isConfirming}
                className="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
              >
                {isConfirming ? (
                  <>
                    <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                    Adding...
                  </>
                ) : (
                  `Add ${selectedSubscriptions.size} Subscription${selectedSubscriptions.size !== 1 ? 's' : ''}`
                )}
              </button>
            </div>
          </div>
        </div>
      </div>
    )
  }

  return (
    <div className="bg-white dark:bg-gray-800 rounded-lg p-6">
      <div className="text-center">
        <div className="mx-auto w-16 h-16 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center mb-4">
          <Building2 className="h-8 w-8 text-primary-600 dark:text-primary-400" />
        </div>
        
        <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-2">
          Scan Your Bank Account
        </h3>
        
        <p className="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
          Connect your bank account to automatically discover all your subscriptions. 
          We'll analyze your transaction history to find recurring payments.
        </p>
        
        <div className="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6">
          <div className="flex items-center justify-center text-sm text-gray-600 dark:text-gray-400 mb-2">
            <Shield className="h-4 w-4 mr-2" />
            <span>Bank-level security & privacy</span>
          </div>
          <ul className="text-xs text-gray-500 dark:text-gray-400 space-y-1">
            <li>• Your banking credentials are never stored</li>
            <li>• We only read transaction data, never account balances</li>
            <li>• All data is encrypted and secure</li>
          </ul>
        </div>
        
        <button
          onClick={handleBankScan}
          disabled={isScanning}
          className="bg-primary-600 hover:bg-primary-700 text-white px-8 py-3 rounded-lg font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center mx-auto"
        >
          {isScanning ? (
            <>
              <Loader2 className="h-5 w-5 mr-2 animate-spin" />
              Scanning Bank Account...
            </>
          ) : (
            <>
              <CreditCard className="h-5 w-5 mr-2" />
              Connect & Scan Bank Account
            </>
          )}
        </button>
      </div>
    </div>
  )
}
