'use client'

import { useState } from 'react'
import { useSession } from 'next-auth/react'
import { useRouter } from 'next/navigation'
import Link from 'next/link'
import { 
  CheckCircle, 
  X,
  Shield, 
  ArrowLeft,
  Zap,
  Star,
  Clock,
  DollarSign,
  Users,
  Loader2,
  Crown,
  Mail,
  BarChart3,
  Download
} from 'lucide-react'

export default function PricingPage() {
  const { data: session } = useSession()
  const router = useRouter()
  const [isProcessing, setIsProcessing] = useState(false)

  const handleGetStarted = async (plan: 'free' | 'pro') => {
    if (!session) {
      router.push('/auth/signin')
      return
    }

    if (plan === 'free') {
      router.push('/dashboard')
      return
    }

    setIsProcessing(true)
    
    try {
      const response = await fetch('/api/upgrade', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ userId: session.user?.email, plan: 'pro' })
      })
      
      if (response.ok) {
        router.push('/dashboard?upgraded=true')
      } else {
        throw new Error('Payment failed')
      }
    } catch (error) {
      alert('Payment failed. Please try again.')
    } finally {
      setIsProcessing(false)
    }
  }

  const freeFeatures = [
    'Manual subscription entry (up to 5)',
    'Basic dashboard overview',
    'Privacy-first design',
    'Dark mode support',
    'Mobile-friendly interface',
    'Data export (JSON)',
    'Local data storage'
  ]

  const proFeatures = [
    'Everything in Free',
    'Unlimited manual subscriptions',
    '🏦 European Bank Scanning (100+ banks)',
    '📧 Smart Email Renewal Alerts',
    '🔔 Customizable Reminder Settings',
    '📊 Advanced Spending Analytics',
    '📤 Enhanced Export (PDF/CSV/Excel)',
    '🔄 Bulk Subscription Management',
    '🎯 Smart Categorization & Tagging',
    '⚡ Automated Subscription Detection',
    '🌍 Multi-Currency Support',
    '📱 Cross-Device Synchronization',
    '🛡️ Bank-Grade Security (PSD2 Compliant)',
    '🎨 Advanced Dashboard Customization',
    '📈 Spending Trend Analysis',
    '⏰ Flexible Reminder Scheduling',
    '🚀 Priority Support & Updates',
    '🔗 Cancellation Assistance Tools'
  ]

  const testimonials = [
    {
      name: 'Sarah M.',
      text: 'Found $180/month in forgotten subscriptions! This app paid for itself immediately.',
      savings: '$2,160/year'
    },
    {
      name: 'Mike R.',
      text: 'Finally, a subscription tracker that doesn\'t charge monthly fees. Love the one-time payment.',
      savings: '$840/year'
    },
    {
      name: 'Jessica L.',
      text: 'The automatic discovery found subscriptions I completely forgot about. Amazing!',
      savings: '$1,320/year'
    }
  ]

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
      {/* Header */}
      <div className="bg-white dark:bg-gray-800 shadow-sm">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <div className="flex items-center justify-between">
            <Link href="/" className="text-2xl font-bold text-primary-600 dark:text-primary-400">
              💳 CashControl
            </Link>
            {session ? (
              <div className="text-sm text-gray-600 dark:text-gray-400">
                Welcome back, {session.user?.name}!
              </div>
            ) : (
              <Link href="/auth/signin" className="btn-secondary">
                Sign In
              </Link>
            )}
          </div>
        </div>
      </div>

      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        {/* Back Button */}
        <Link 
          href={session ? "/onboarding" : "/"}
          className="inline-flex items-center text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 mb-8"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Back
        </Link>

        {/* Hero Section */}
        <div className="text-center mb-12">
          <h1 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">
            Choose Your Plan
          </h1>
          <p className="text-xl text-gray-600 dark:text-gray-400 mb-4">
            Start free, upgrade when you need more power. No recurring fees.
          </p>
          <div className="flex flex-wrap justify-center gap-4 text-sm text-gray-500 dark:text-gray-400">
            <div className="flex items-center">
              <span className="text-green-500 mr-1">✓</span>
              European Banking Integration
            </div>
            <div className="flex items-center">
              <span className="text-green-500 mr-1">✓</span>
              Smart Email Alerts
            </div>
            <div className="flex items-center">
              <span className="text-green-500 mr-1">✓</span>
              Privacy-First Design
            </div>
            <div className="flex items-center">
              <span className="text-green-500 mr-1">✓</span>
              One-Time Payment
            </div>
          </div>
        </div>

        {/* Pricing Cards */}
        <div className="grid md:grid-cols-2 gap-8 mb-12">
          {/* Free Plan */}
          <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 border border-gray-200 dark:border-gray-700">
            <div className="text-center mb-6">
              <h3 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                </h3>
                <div className="flex items-center justify-center mb-4">
                  <span className="text-4xl font-bold text-gray-900 dark:text-white">${plan.price}</span>
                  <span className="text-gray-500 dark:text-gray-400 ml-2">{plan.price > 0 ? 'one-time' : 'forever'}</span>
                </div>
                <p className="mt-4 text-sm text-gray-500 dark:text-gray-400">
                  {plan.description}
                  {plan.price > 0 && (
                    <span className="block mt-2 text-xs text-gray-400">
                      No hidden fees, cancel anytime
                    </span>
                  )}
                </p>
              </div>

              <ul className="space-y-3 mb-8">
                {plan.name === 'Free' ? freeFeatures.map((feature, index) => (
                  <li key={index} className="flex items-center">
                    <CheckCircle className="h-5 w-5 text-green-500 mr-3 flex-shrink-0" />
                    <span className="text-gray-700 dark:text-gray-300">{feature}</span>
                  </li>
                )) : proFeatures.map((feature, index) => (
                  <li key={index} className="flex items-center">
                    <CheckCircle className="h-5 w-5 text-green-500 mr-3 flex-shrink-0" />
                    <span className="text-gray-700 dark:text-gray-300">{feature}</span>
                  </li>
                ))}
              </ul>

              <button
                onClick={() => handleGetStarted(plan.name.toLowerCase())}
                disabled={isProcessing}
                className="w-full bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-6 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {plan.name === 'Free' ? 'Get Started Free' : 'Upgrade to Pro'}
              </button>
            </div>
          ))}
        </div>

        {/* Comparison */}
        <div className="bg-white dark:bg-gray-800 rounded-lg p-8 mb-12">
          <h3 className="text-2xl font-bold text-gray-900 dark:text-white text-center mb-8">
            Why Choose CashControl Over Competitors?
          </h3>
          
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-gray-200 dark:border-gray-700">
                  <th className="text-left py-3 font-medium text-gray-900 dark:text-white">Feature</th>
                  <th className="text-center py-3 font-medium text-primary-600 dark:text-primary-400">CashControl</th>
                  <th className="text-center py-3 font-medium text-gray-500 dark:text-gray-400">Rocket Money</th>
                  <th className="text-center py-3 font-medium text-gray-500 dark:text-gray-400">Trim</th>
                  <th className="text-center py-3 font-medium text-gray-500 dark:text-gray-400">Bobby</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                <tr>
                  <td className="py-3 text-gray-900 dark:text-white">Pricing</td>
                  <td className="text-center py-3 text-primary-600 dark:text-primary-400 font-medium">Free + $29 Pro</td>
                  <td className="text-center py-3 text-gray-500">$6-12/month</td>
                  <td className="text-center py-3 text-gray-500">$10/month</td>
                  <td className="text-center py-3 text-gray-500">$3/month</td>
                </tr>
                <tr>
                  <td className="py-3 text-gray-900 dark:text-white">European Banking</td>
                  <td className="text-center py-3"><CheckCircle className="h-5 w-5 text-green-500 mx-auto" /></td>
                  <td className="text-center py-3 text-gray-400">US only</td>
                  <td className="text-center py-3 text-gray-400">US only</td>
                  <td className="text-center py-3 text-gray-400">Manual only</td>
                </tr>
                <tr>
                  <td className="py-3 text-gray-900 dark:text-white">Smart Email Alerts</td>
                  <td className="text-center py-3"><CheckCircle className="h-5 w-5 text-green-500 mx-auto" /></td>
                  <td className="text-center py-3"><CheckCircle className="h-5 w-5 text-green-500 mx-auto" /></td>
                  <td className="text-center py-3 text-gray-400">Basic only</td>
                  <td className="text-center py-3"><CheckCircle className="h-5 w-5 text-green-500 mx-auto" /></td>
                </tr>
                <tr>
                  <td className="py-3 text-gray-900 dark:text-white">Privacy First</td>
                  <td className="text-center py-3"><CheckCircle className="h-5 w-5 text-green-500 mx-auto" /></td>
                  <td className="text-center py-3 text-gray-400">Data shared</td>
                  <td className="text-center py-3 text-gray-400">Data shared</td>
                  <td className="text-center py-3"><CheckCircle className="h-5 w-5 text-green-500 mx-auto" /></td>
                </tr>
                <tr>
                  <td className="py-3 text-gray-900 dark:text-white">No Monthly Fees</td>
                  <td className="text-center py-3"><CheckCircle className="h-5 w-5 text-green-500 mx-auto" /></td>
                  <td className="text-center py-3 text-red-400">❌</td>
                  <td className="text-center py-3 text-red-400">❌</td>
                  <td className="text-center py-3 text-red-400">❌</td>
                </tr>
                <tr>
                  <td className="py-3 text-gray-900 dark:text-white">PSD2 Compliant</td>
                  <td className="text-center py-3"><CheckCircle className="h-5 w-5 text-green-500 mx-auto" /></td>
                  <td className="text-center py-3 text-gray-400">N/A</td>
                  <td className="text-center py-3 text-gray-400">N/A</td>
                  <td className="text-center py-3 text-gray-400">N/A</td>
                </tr>
                <tr>
                  <td className="py-3 text-gray-900 dark:text-white">Multi-Currency</td>
                  <td className="text-center py-3"><CheckCircle className="h-5 w-5 text-green-500 mx-auto" /></td>
                  <td className="text-center py-3 text-gray-400">USD only</td>
                  <td className="text-center py-3 text-gray-400">USD only</td>
                  <td className="text-center py-3"><CheckCircle className="h-5 w-5 text-green-500 mx-auto" /></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        {/* Testimonials */}
        <div className="mb-12">
          <h3 className="text-2xl font-bold text-gray-900 dark:text-white text-center mb-8">
            What Our Users Say
          </h3>
          
          <div className="grid md:grid-cols-3 gap-6">
            {testimonials.map((testimonial, index) => (
              <div key={index} className="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-lg">
                <div className="flex mb-4">
                  {[...Array(5)].map((_, i) => (
                    <Star key={i} className="h-4 w-4 text-yellow-400 fill-current" />
                  ))}
                </div>
                <p className="text-gray-600 dark:text-gray-400 text-sm mb-4">
                  "{testimonial.text}"
                </p>
                <div className="flex justify-between items-center">
                  <div className="font-medium text-gray-900 dark:text-white text-sm">
                    {testimonial.name}
                  </div>
                  <div className="text-green-600 dark:text-green-400 text-sm font-medium">
                    Saved {testimonial.savings}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* FAQ */}
        <div className="bg-white dark:bg-gray-800 rounded-lg p-8">
          <h3 className="text-2xl font-bold text-gray-900 dark:text-white text-center mb-8">
            Frequently Asked Questions
          </h3>
          
          <div className="space-y-6">
            <div>
              <h4 className="font-medium text-gray-900 dark:text-white mb-2">
                Is this really a one-time payment?
              </h4>
              <p className="text-gray-600 dark:text-gray-400 text-sm">
                Yes! Pay $29 once and get lifetime access. No monthly fees, no recurring charges, no hidden costs.
              </p>
            </div>
            
            <div>
              <h4 className="font-medium text-gray-900 dark:text-white mb-2">
                How does European bank scanning work?
              </h4>
              <p className="text-gray-600 dark:text-gray-400 text-sm">
                We connect to 100+ European banks using secure PSD2-compliant APIs (like TrueLayer) to analyze your transaction patterns and automatically detect recurring subscription charges. Your banking credentials are never stored, and all connections use bank-grade security.
              </p>
            </div>
            
            <div>
              <h4 className="font-medium text-gray-900 dark:text-white mb-2">
                What email features are included?
              </h4>
              <p className="text-gray-600 dark:text-gray-400 text-sm">
                CashControl Pro includes smart email renewal alerts with customizable timing (1-30 days before renewal), welcome emails for new users, upgrade confirmations, and bank scan completion notifications. You can configure reminder frequency and preferred email times in settings.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
