'use client'

import { useState, useEffect } from 'react'
import { useSession } from 'next-auth/react'
import { useRouter } from 'next/navigation'
import { 
  CreditCard, 
  Search, 
  TrendingDown, 
  CheckCircle, 
  ArrowRight,
  Zap,
  Shield,
  Clock,
  DollarSign,
  Mail,
  Smartphone,
  AlertTriangle,
  Star
} from 'lucide-react'
import Link from 'next/link'

interface OnboardingStep {
  id: string
  title: string
  description: string
  icon: React.ReactNode
  completed: boolean
}

export default function OnboardingPage() {
  const { data: session, status } = useSession()
  const router = useRouter()
  const [currentStep, setCurrentStep] = useState(0)
  const [discoveredSubscriptions, setDiscoveredSubscriptions] = useState<any[]>([])
  const [isScanning, setIsScanning] = useState(false)
  const [totalSavings, setTotalSavings] = useState(0)

  // Mock discovered subscriptions for demo
  const mockDiscoveredSubs = [
    { name: 'Netflix', amount: 15.99, category: 'streaming', lastCharged: '2025-01-15', confidence: 95 },
    { name: 'Spotify Premium', amount: 9.99, category: 'music', lastCharged: '2025-01-10', confidence: 98 },
    { name: 'Adobe Creative Cloud', amount: 52.99, category: 'software', lastCharged: '2025-01-01', confidence: 92 },
    { name: 'Gym Membership (Unused)', amount: 29.99, category: 'fitness', lastCharged: '2024-12-15', confidence: 88 },
    { name: 'Old VPN Service', amount: 12.99, category: 'software', lastCharged: '2024-11-20', confidence: 85 },
    { name: 'Forgotten Cloud Storage', amount: 9.99, category: 'cloud_storage', lastCharged: '2024-10-05', confidence: 90 }
  ]

  const steps: OnboardingStep[] = [
    {
      id: 'welcome',
      title: 'Welcome to I Hate Subscriptions!',
      description: 'Let\'s find your hidden subscriptions and show you how much you can save.',
      icon: <CheckCircle className="h-8 w-8" />,
      completed: true
    },
    {
      id: 'scan',
      title: 'Scan for Hidden Subscriptions',
      description: 'We\'ll analyze your spending patterns to find forgotten subscriptions.',
      icon: <Search className="h-8 w-8" />,
      completed: discoveredSubscriptions.length > 0
    },
    {
      id: 'results',
      title: 'Your Savings Potential',
      description: 'See exactly how much you could save by canceling unused subscriptions.',
      icon: <TrendingDown className="h-8 w-8" />,
      completed: totalSavings > 0
    },
    {
      id: 'upgrade',
      title: 'Unlock Full Access',
      description: 'Get lifetime access to manage all your subscriptions and save money.',
      icon: <Star className="h-8 w-8" />,
      completed: false
    }
  ]

  useEffect(() => {
    if (status === 'unauthenticated') {
      router.push('/auth/signin')
    }
  }, [status, router])

  const handleScanSubscriptions = async () => {
    setIsScanning(true)
    
    // Simulate scanning process
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    setDiscoveredSubscriptions(mockDiscoveredSubs)
    const savings = mockDiscoveredSubs
      .filter(sub => sub.name.includes('Unused') || sub.name.includes('Old') || sub.name.includes('Forgotten'))
      .reduce((total, sub) => total + sub.amount, 0)
    setTotalSavings(savings * 12) // Annual savings
    
    setIsScanning(false)
    setCurrentStep(2)
  }

  const handleUpgrade = () => {
    router.push('/pricing')
  }

  if (status === 'loading') {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    )
  }

  if (!session) {
    return null
  }

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
      {/* Header */}
      <div className="bg-white dark:bg-gray-800 shadow-sm">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-4">
              <div className="text-2xl font-bold text-primary-600 dark:text-primary-400">
                I Hate Subscriptions
              </div>
              <div className="text-sm text-gray-500 dark:text-gray-400">
                Welcome, {session.user?.name}!
              </div>
            </div>
            <div className="text-sm text-gray-500 dark:text-gray-400">
              7-day free trial
            </div>
          </div>
        </div>
      </div>

      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Progress Steps */}
        <div className="mb-12">
          <div className="flex items-center justify-between">
            {steps.map((step, index) => (
              <div key={step.id} className="flex items-center">
                <div className={`flex items-center justify-center w-12 h-12 rounded-full border-2 ${
                  step.completed || index <= currentStep
                    ? 'bg-primary-600 border-primary-600 text-white'
                    : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-400'
                }`}>
                  {step.completed ? (
                    <CheckCircle className="h-6 w-6" />
                  ) : (
                    <span className="text-sm font-medium">{index + 1}</span>
                  )}
                </div>
                {index < steps.length - 1 && (
                  <div className={`w-16 h-1 mx-4 ${
                    index < currentStep ? 'bg-primary-600' : 'bg-gray-300 dark:bg-gray-600'
                  }`} />
                )}
              </div>
            ))}
          </div>
        </div>

        {/* Step Content */}
        {currentStep === 0 && (
          <div className="text-center space-y-8">
            <div className="space-y-4">
              <h1 className="text-4xl font-bold text-gray-900 dark:text-white">
                Let's Find Your Hidden Subscriptions
              </h1>
              <p className="text-xl text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                The average person has 12 subscriptions but only remembers 9. Let's find the ones you forgot about and show you how much you can save.
              </p>
            </div>

            <div className="grid md:grid-cols-3 gap-6 max-w-3xl mx-auto">
              <div className="text-center space-y-3">
                <div className="w-16 h-16 bg-primary-100 dark:bg-primary-900/20 rounded-full flex items-center justify-center mx-auto">
                  <Search className="h-8 w-8 text-primary-600 dark:text-primary-400" />
                </div>
                <h3 className="font-semibold text-gray-900 dark:text-white">Smart Detection</h3>
                <p className="text-sm text-gray-600 dark:text-gray-400">
                  We analyze spending patterns to find recurring charges
                </p>
              </div>
              
              <div className="text-center space-y-3">
                <div className="w-16 h-16 bg-green-100 dark:bg-green-900/20 rounded-full flex items-center justify-center mx-auto">
                  <TrendingDown className="h-8 w-8 text-green-600 dark:text-green-400" />
                </div>
                <h3 className="font-semibold text-gray-900 dark:text-white">Instant Savings</h3>
                <p className="text-sm text-gray-600 dark:text-gray-400">
                  See exactly how much you can save immediately
                </p>
              </div>
              
              <div className="text-center space-y-3">
                <div className="w-16 h-16 bg-blue-100 dark:bg-blue-900/20 rounded-full flex items-center justify-center mx-auto">
                  <Shield className="h-8 w-8 text-blue-600 dark:text-blue-400" />
                </div>
                <h3 className="font-semibold text-gray-900 dark:text-white">Privacy First</h3>
                <p className="text-sm text-gray-600 dark:text-gray-400">
                  Your data stays secure and private
                </p>
              </div>
            </div>

            <button
              onClick={() => setCurrentStep(1)}
              className="btn-primary text-lg px-8 py-4 flex items-center gap-2 mx-auto"
            >
              Start Scanning
              <ArrowRight className="h-5 w-5" />
            </button>
          </div>
        )}

        {currentStep === 1 && (
          <div className="text-center space-y-8">
            <div className="space-y-4">
              <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                Scanning Your Subscriptions
              </h1>
              <p className="text-lg text-gray-600 dark:text-gray-400">
                We're analyzing your spending patterns to find hidden subscriptions...
              </p>
            </div>

            <div className="max-w-md mx-auto">
              <div className="bg-white dark:bg-gray-800 rounded-lg p-8 shadow-lg">
                <div className="space-y-6">
                  <div className="w-16 h-16 bg-primary-100 dark:bg-primary-900/20 rounded-full flex items-center justify-center mx-auto">
                    {isScanning ? (
                      <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
                    ) : (
                      <Search className="h-8 w-8 text-primary-600 dark:text-primary-400" />
                    )}
                  </div>

                  <div className="space-y-4">
                    <div className="text-left space-y-2">
                      <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <CheckCircle className="h-4 w-4 text-green-500 mr-2" />
                        Analyzing transaction patterns...
                      </div>
                      <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <CheckCircle className="h-4 w-4 text-green-500 mr-2" />
                        Identifying recurring charges...
                      </div>
                      <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        {isScanning ? (
                          <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-primary-600 mr-2"></div>
                        ) : (
                          <CheckCircle className="h-4 w-4 text-green-500 mr-2" />
                        )}
                        Finding forgotten subscriptions...
                      </div>
                    </div>
                  </div>

                  <button
                    onClick={handleScanSubscriptions}
                    disabled={isScanning}
                    className="btn-primary w-full disabled:opacity-50"
                  >
                    {isScanning ? 'Scanning...' : 'Start Scan'}
                  </button>
                </div>
              </div>
            </div>

            <div className="text-xs text-gray-500 dark:text-gray-400 max-w-md mx-auto">
              <Shield className="h-4 w-4 inline mr-1" />
              We use bank-level encryption and never store your credentials
            </div>
          </div>
        )}

        {currentStep === 2 && (
          <div className="space-y-8">
            <div className="text-center space-y-4">
              <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                🎉 We Found Hidden Subscriptions!
              </h1>
              <p className="text-lg text-gray-600 dark:text-gray-400">
                You could save <span className="font-bold text-green-600">${totalSavings}/year</span> by canceling unused subscriptions
              </p>
            </div>

            {/* Savings Summary */}
            <div className="bg-gradient-to-r from-green-50 to-blue-50 dark:from-green-900/20 dark:to-blue-900/20 rounded-lg p-6 border border-green-200 dark:border-green-800">
              <div className="text-center space-y-2">
                <div className="text-3xl font-bold text-green-600 dark:text-green-400">
                  ${totalSavings}/year
                </div>
                <div className="text-sm text-green-700 dark:text-green-300">
                  Potential annual savings from canceling unused subscriptions
                </div>
              </div>
            </div>

            {/* Discovered Subscriptions */}
            <div className="space-y-4">
              <h3 className="text-xl font-semibold text-gray-900 dark:text-white">
                Discovered Subscriptions ({discoveredSubscriptions.length})
              </h3>
              
              <div className="grid gap-4">
                {discoveredSubscriptions.map((sub, index) => (
                  <div key={index} className="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                      <div className={`w-3 h-3 rounded-full ${
                        sub.confidence >= 90 ? 'bg-green-500' : 
                        sub.confidence >= 80 ? 'bg-yellow-500' : 'bg-red-500'
                      }`} />
                      <div>
                        <div className="font-medium text-gray-900 dark:text-white">
                          {sub.name}
                          {(sub.name.includes('Unused') || sub.name.includes('Old') || sub.name.includes('Forgotten')) && (
                            <span className="ml-2 px-2 py-1 text-xs bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400 rounded">
                              Potential Savings
                            </span>
                          )}
                        </div>
                        <div className="text-sm text-gray-500 dark:text-gray-400">
                          Last charged: {sub.lastCharged} • {sub.confidence}% confidence
                        </div>
                      </div>
                    </div>
                    <div className="text-right">
                      <div className="font-medium text-gray-900 dark:text-white">
                        ${sub.amount}/month
                      </div>
                      <div className="text-sm text-gray-500 dark:text-gray-400 capitalize">
                        {sub.category}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            <div className="text-center">
              <button
                onClick={() => setCurrentStep(3)}
                className="btn-primary text-lg px-8 py-4 flex items-center gap-2 mx-auto"
              >
                See How to Save This Money
                <ArrowRight className="h-5 w-5" />
              </button>
            </div>
          </div>
        )}

        {currentStep === 3 && (
          <div className="space-y-8">
            <div className="text-center space-y-4">
              <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                Ready to Save ${totalSavings}/year?
              </h1>
              <p className="text-lg text-gray-600 dark:text-gray-400">
                Upgrade to unlock full access and start managing all your subscriptions
              </p>
            </div>

            {/* Pricing Card */}
            <div className="max-w-md mx-auto">
              <div className="bg-white dark:bg-gray-800 rounded-lg p-8 shadow-lg border-2 border-primary-500">
                <div className="text-center space-y-6">
                  <div>
                    <div className="text-4xl font-bold text-gray-900 dark:text-white">$29</div>
                    <div className="text-sm text-gray-500 dark:text-gray-400">One-time payment</div>
                    <div className="text-xs text-green-600 dark:text-green-400 font-medium">
                      Pays for itself in {Math.ceil(29 / (totalSavings / 12))} months!
                    </div>
                  </div>

                  <div className="space-y-3 text-left">
                    <div className="flex items-center text-sm">
                      <CheckCircle className="h-4 w-4 text-green-500 mr-3" />
                      Cancel unused subscriptions easily
                    </div>
                    <div className="flex items-center text-sm">
                      <CheckCircle className="h-4 w-4 text-green-500 mr-3" />
                      Track all your recurring payments
                    </div>
                    <div className="flex items-center text-sm">
                      <CheckCircle className="h-4 w-4 text-green-500 mr-3" />
                      Get renewal reminders
                    </div>
                    <div className="flex items-center text-sm">
                      <CheckCircle className="h-4 w-4 text-green-500 mr-3" />
                      Export your data anytime
                    </div>
                    <div className="flex items-center text-sm">
                      <CheckCircle className="h-4 w-4 text-green-500 mr-3" />
                      No monthly fees ever
                    </div>
                  </div>

                  <button
                    onClick={handleUpgrade}
                    className="btn-primary w-full text-lg py-3"
                  >
                    Get Lifetime Access - $29
                  </button>

                  <div className="text-xs text-gray-500 dark:text-gray-400">
                    30-day money-back guarantee
                  </div>
                </div>
              </div>
            </div>

            {/* Alternative Actions */}
            <div className="text-center space-y-4">
              <div className="text-sm text-gray-500 dark:text-gray-400">
                Want to explore more first?
              </div>
              <div className="flex justify-center space-x-4">
                <Link href="/demo" className="btn-secondary">
                  Try Interactive Demo
                </Link>
                <Link href="/dashboard" className="text-primary-600 hover:text-primary-500 dark:text-primary-400 text-sm font-medium">
                  Continue with Free Trial
                </Link>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  )
}
