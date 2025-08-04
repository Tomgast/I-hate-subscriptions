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
  ExternalLink,
  Info,
  Lock,
  HelpCircle
} from 'lucide-react'
import Link from 'next/link'
import { DetectedSubscription } from '@/lib/bank-scan'
import { useRouter } from 'next/navigation'

interface BankScanProps {
  userTier: 'free' | 'pro'
  onSubscriptionsAdded?: (count: number) => void
}

type BankScanStep = 'intro' | 'provider-selection' | 'connecting' | 'scanning' | 'review' | 'complete' | 'error'

export function BankScan({ userTier, onSubscriptionsAdded }: BankScanProps) {
  const router = useRouter()
  const { data: session } = useSession()
  
  // Flow control states
  const [currentStep, setCurrentStep] = useState<BankScanStep>('intro')
  const [isScanning, setIsScanning] = useState(false)
  const [isConfirming, setIsConfirming] = useState(false)
  
  // Data states
  const [detectedSubscriptions, setDetectedSubscriptions] = useState<DetectedSubscription[]>([])
  const [selectedSubscriptions, setSelectedSubscriptions] = useState<Set<number>>(new Set())
  const [scanResults, setScanResults] = useState<{
    totalTransactionsAnalyzed: number
    analysisDateRange: { startDate: string; endDate: string }
  } | null>(null)
  
  // Error handling
  const [error, setError] = useState<string | null>(null)
  const [bankProviders, setBankProviders] = useState<{id: string, name: string, logo: string}[]>([
    { id: 'truelayer', name: 'TrueLayer (Open Banking)', logo: '/images/truelayer-logo.png' },
    { id: 'plaid', name: 'Plaid', logo: '/images/plaid-logo.png' },
    { id: 'demo', name: 'Demo Bank (Test Data)', logo: '/images/demo-bank-logo.png' }
  ])
  const [selectedProvider, setSelectedProvider] = useState<string | null>(null)

  // Start bank connection flow
  const startBankConnection = async () => {
    if (userTier !== 'pro') {
      setError('Bank account scanning is a Pro feature. Please upgrade to access this functionality.')
      return
    }

    setCurrentStep('provider-selection')
    setError(null)
  }

  // Handle provider selection
  const handleProviderSelect = async (providerId: string) => {
    setSelectedProvider(providerId)
    setCurrentStep('connecting')
    setError(null)

    try {
      if (providerId === 'demo') {
        // Use demo data
        await new Promise(resolve => setTimeout(resolve, 2000))
        await handleDemoScan()
      } else {
        // Real provider integration
        const response = await fetch('/api/bank-providers/link-token', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            provider: providerId,
            countryCode: 'GB' // Default to UK for now, could be made configurable
          })
        })

        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.error || 'Failed to connect to bank provider')
        }

        const data = await response.json()
        
        // In a real implementation, we would open the bank's authentication page
        // For demo purposes, we'll simulate this process
        console.log('Auth URL:', data.authUrl)
        
        // Simulate successful authentication
        await new Promise(resolve => setTimeout(resolve, 3000))
        await handleBankScan(providerId)
      }
    } catch (error) {
      console.error('Bank connection error:', error)
      setError(error instanceof Error ? error.message : 'Failed to connect to bank provider')
      setCurrentStep('error')
    }
  }

  // Handle demo scan
  const handleDemoScan = async () => {
    setCurrentStep('scanning')
    
    try {
      // Simulate scanning process
      await new Promise(resolve => setTimeout(resolve, 3000))
      
      // Mock data for demo
      const mockData: {
        detectedSubscriptions: DetectedSubscription[],
        totalTransactionsAnalyzed: number,
        analysisDateRange: { startDate: string, endDate: string }
      } = {
        detectedSubscriptions: [
          {
            merchant_name: 'Netflix',
            amount: 14.99,
            frequency: 'monthly',
            category: 'Entertainment',
            confidence: 0.95,
            transactions: [{ account_id: 'demo', amount: 14.99, date: '2023-05-15', name: 'NETFLIX', category: ['Entertainment'], transaction_id: 'tx1' }, 
                          { account_id: 'demo', amount: 14.99, date: '2023-04-15', name: 'NETFLIX', category: ['Entertainment'], transaction_id: 'tx2' }],
            next_billing_date: '2023-06-15',
            logo_url: 'https://logo.clearbit.com/netflix.com',
            website_url: 'https://netflix.com'
          },
          {
            merchant_name: 'Spotify',
            amount: 9.99,
            frequency: 'monthly',
            category: 'Entertainment',
            confidence: 0.92,
            transactions: [{ account_id: 'demo', amount: 9.99, date: '2023-05-10', name: 'SPOTIFY', category: ['Entertainment'], transaction_id: 'tx3' }, 
                          { account_id: 'demo', amount: 9.99, date: '2023-04-10', name: 'SPOTIFY', category: ['Entertainment'], transaction_id: 'tx4' }],
            next_billing_date: '2023-06-10',
            logo_url: 'https://logo.clearbit.com/spotify.com',
            website_url: 'https://spotify.com'
          },
          {
            merchant_name: 'Adobe Creative Cloud',
            amount: 52.99,
            frequency: 'monthly',
            category: 'Software',
            confidence: 0.88,
            transactions: [{ account_id: 'demo', amount: 52.99, date: '2023-05-05', name: 'ADOBE', category: ['Software'], transaction_id: 'tx5' }, 
                          { account_id: 'demo', amount: 52.99, date: '2023-04-05', name: 'ADOBE', category: ['Software'], transaction_id: 'tx6' }],
            next_billing_date: '2023-06-05',
            logo_url: 'https://logo.clearbit.com/adobe.com',
            website_url: 'https://adobe.com'
          }
        ],
        totalTransactionsAnalyzed: 156,
        analysisDateRange: { startDate: '2023-03-01', endDate: '2023-05-31' }
      }
      
      setDetectedSubscriptions(mockData.detectedSubscriptions)
      setScanResults({
        totalTransactionsAnalyzed: mockData.totalTransactionsAnalyzed,
        analysisDateRange: mockData.analysisDateRange
      })
      
      // Pre-select all high-confidence subscriptions
      const highConfidenceIndices = new Set<number>(
        mockData.detectedSubscriptions
          .map((sub: DetectedSubscription, index: number) => sub.confidence > 0.8 ? index : -1)
          .filter((index: number) => index !== -1)
      )
      setSelectedSubscriptions(highConfidenceIndices as Set<number>)
      
      setCurrentStep('review')
    } catch (error) {
      console.error('Demo scan error:', error)
      setError('Failed to process demo data. Please try again.')
      setCurrentStep('error')
    }
  }

  // Handle bank scan with real provider
  const handleBankScan = async (providerId: string) => {
    setCurrentStep('scanning')
    
    try {
      const response = await fetch('/api/bank-scan', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ providerId })
      })
      
      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.error || 'Failed to scan bank account')
      }
      
      const data = await response.json()
      
      // Process detected subscriptions
      setDetectedSubscriptions(data.detectedSubscriptions)
      setScanResults({
        totalTransactionsAnalyzed: data.totalTransactionsAnalyzed,
        analysisDateRange: data.analysisDateRange
      })
      
      // Select all subscriptions by default
      setSelectedSubscriptions(new Set<number>(data.detectedSubscriptions.map((_: DetectedSubscription, index: number) => index)))
      
      setCurrentStep('review')
    } catch (error) {
      console.error('Bank scan error:', error)
      setError(error instanceof Error ? error.message : 'Failed to scan bank account')
      setCurrentStep('error')
    }
  }

  // Handle confirm subscriptions
  const handleConfirmSubscriptions = async () => {
    if (selectedSubscriptions.size === 0) return
    
    setIsConfirming(true)
    
    try {
      // Get selected subscriptions
      const subscriptionsToAdd = Array.from(selectedSubscriptions).map(index => detectedSubscriptions[index])
      
      // In a real implementation, we would send these to the API
      const response = await fetch('/api/bank-scan/confirm', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ subscriptions: subscriptionsToAdd })
      })
      
      if (!response.ok) {
        throw new Error('Failed to add subscriptions')
      }
      
      // Notify parent component if callback provided
      if (onSubscriptionsAdded) {
        onSubscriptionsAdded(selectedSubscriptions.size)
      }
      
      // Show completion state briefly before resetting
      setCurrentStep('complete')
      
      // Reset after a delay
      setTimeout(() => {
        setDetectedSubscriptions([])
        setSelectedSubscriptions(new Set<number>())
        setIsScanning(false)
        setIsConfirming(false)
      }, 5000)
      
    } catch (error) {
      console.error('Error confirming subscriptions:', error)
      setError(error instanceof Error ? error.message : 'Failed to add subscriptions')
      setCurrentStep('error')
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
    setSelectedSubscriptions(newSelection as Set<number>)
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

  if (currentStep === 'review' && detectedSubscriptions.length > 0) {
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
                setCurrentStep('intro')
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
  // Provider selection step
  if (currentStep === 'provider-selection') {
    return (
      <div className="bg-white dark:bg-gray-800 rounded-lg p-6">
        <div className="text-center mb-6">
          <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-2">
            Select Your Bank Provider
          </h3>
          <p className="text-gray-600 dark:text-gray-400 max-w-md mx-auto">
            Choose a provider to securely connect your bank account.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
          {bankProviders.map(provider => (
            <button
              key={provider.id}
              onClick={() => handleProviderSelect(provider.id)}
              className="flex items-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              <div className="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center mr-4">
                <Building2 className="h-6 w-6 text-primary-600 dark:text-primary-400" />
              </div>
              <div className="text-left">
                <h4 className="font-medium text-gray-900 dark:text-white">{provider.name}</h4>
                <p className="text-sm text-gray-500 dark:text-gray-400">
                  {provider.id === 'demo' ? 'Test with sample data' : 'Connect securely via Open Banking'}
                </p>
              </div>
            </button>
          ))}
        </div>

        <div className="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
          <div className="flex items-start">
            <Info className="h-5 w-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-3 flex-shrink-0" />
            <div className="text-sm text-blue-700 dark:text-blue-300">
              <p className="font-medium mb-1">Your data is secure</p>
              <p>We use bank-level encryption and security. Your credentials are never stored on our servers, and we only access the transaction data needed to identify subscriptions.</p>
            </div>
          </div>
        </div>

        <button
          onClick={() => setCurrentStep('intro')}
          className="mt-6 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 mx-auto block"
        >
          Cancel
        </button>
      </div>
    )
  }

  // Connecting step
  if (currentStep === 'connecting') {
    return (
      <div className="bg-white dark:bg-gray-800 rounded-lg p-6">
        <div className="text-center">
          <div className="mx-auto w-16 h-16 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center mb-4">
            <Lock className="h-8 w-8 text-primary-600 dark:text-primary-400" />
          </div>
          
          <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-2">
            Connecting to {selectedProvider === 'demo' ? 'Demo Bank' : 'Your Bank'}
          </h3>
          
          <div className="flex justify-center items-center space-x-2 mb-6">
            <Loader2 className="h-5 w-5 animate-spin text-primary-600 dark:text-primary-400" />
            <p className="text-gray-600 dark:text-gray-400">
              Establishing secure connection...
            </p>
          </div>
          
          <div className="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6 max-w-md mx-auto">
            <div className="flex items-center justify-center text-sm text-gray-600 dark:text-gray-400 mb-2">
              <Shield className="h-4 w-4 mr-2" />
              <span>Secure Connection</span>
            </div>
            <p className="text-xs text-gray-500 dark:text-gray-400">
              You'll be redirected to your bank's secure login page. Your credentials are never shared with us.
            </p>
          </div>
        </div>
      </div>
    )
  }

  // Scanning step
  if (currentStep === 'scanning') {
    return (
      <div className="bg-white dark:bg-gray-800 rounded-lg p-6">
        <div className="text-center">
          <div className="mx-auto w-16 h-16 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center mb-4">
            <Loader2 className="h-8 w-8 text-primary-600 dark:text-primary-400 animate-spin" />
          </div>
          
          <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-2">
            Scanning Your Transactions
          </h3>
          
          <p className="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
            We're analyzing your transaction history to identify recurring subscription payments.
            This may take a few moments.
          </p>
          
          <div className="w-full max-w-md mx-auto bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 mb-6">
            <div className="bg-primary-600 h-2.5 rounded-full animate-pulse w-2/3"></div>
          </div>
          
          <p className="text-sm text-gray-500 dark:text-gray-400">
            Looking for patterns in your transaction history...
          </p>
        </div>
      </div>
    )
  }

  // Complete step
  if (currentStep === 'complete') {
    return (
      <div className="bg-white dark:bg-gray-800 rounded-lg p-6">
        <div className="text-center">
          <div className="mx-auto w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-4">
            <CheckCircle className="h-8 w-8 text-green-600 dark:text-green-400" />
          </div>
          
          <h3 className="text-xl font-semibold text-green-900 dark:text-green-100 mb-2">
            Subscriptions Added Successfully!
          </h3>
          
          <p className="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
            Your selected subscriptions have been added to your account.
          </p>
          
          <button
            onClick={() => {
              setCurrentStep('intro')
              router.push('/dashboard')
            }}
            className="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg font-medium"
          >
            View My Dashboard
          </button>
        </div>
      </div>
    )
  }

  // Error step
  if (currentStep === 'error') {
    return (
      <div className="bg-white dark:bg-gray-800 rounded-lg p-6">
        <div className="text-center">
          <div className="mx-auto w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mb-4">
            <AlertCircle className="h-8 w-8 text-red-600 dark:text-red-400" />
          </div>
          
          <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-2">
            Something Went Wrong
          </h3>
          
          <p className="text-red-600 dark:text-red-400 mb-4">
            {error || 'We encountered an issue while connecting to your bank.'}
          </p>
          
          <p className="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
            This could be due to a temporary connection issue or because the bank's services are currently unavailable.
          </p>
          
          <div className="flex flex-col sm:flex-row justify-center space-y-3 sm:space-y-0 sm:space-x-3">
            <button
              onClick={() => {
                if (selectedProvider) {
                  handleProviderSelect(selectedProvider)
                } else {
                  setCurrentStep('provider-selection')
                }
              }}
              className="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg font-medium"
            >
              Try Again
            </button>
            
            <button
              onClick={() => setCurrentStep('intro')}
              className="border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 px-6 py-2 rounded-lg font-medium"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    )
  }

  // Default intro step
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
          onClick={startBankConnection}
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
