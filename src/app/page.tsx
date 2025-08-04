'use client'

import { useState } from 'react'
import Link from 'next/link'
import { useSession } from 'next-auth/react'
import { 
  DollarSign, 
  Shield, 
  Zap, 
  CheckCircle, 
  Star,
  ArrowRight,
  Play,
  X,
  CreditCard,
  TrendingDown,
  Clock,
  Users
} from 'lucide-react'
import { Logo } from '@/components/Logo'

export default function HomePage() {
  const { data: session } = useSession()
  const [showPaymentModal, setShowPaymentModal] = useState(false)

  const features = [
    {
      icon: <TrendingDown className="h-6 w-6" />,
      title: "Automatic Discovery",
      description: "Find forgotten subscriptions by scanning your bank transactions"
    },
    {
      icon: <DollarSign className="h-6 w-6" />,
      title: "Smart Analytics",
      description: "See your monthly and yearly spending with detailed insights"
    },
    {
      icon: <Users className="h-6 w-6" />,
      title: "Multi-Device Sync",
      description: "Access your subscriptions from any device, anywhere"
    },
    {
      icon: <Shield className="h-6 w-6" />,
      title: "Privacy-First",
      description: "Your data stays private and secure, always"
    },
    {
      icon: <Clock className="h-6 w-6" />,
      title: "Smart Reminders",
      description: "Never get surprised by unexpected charges again"
    },
    {
      icon: <Zap className="h-6 w-6" />,
      title: "One-Time Payment",
      description: "Pay once, use forever. No monthly subscription fees"
    }
  ]

  // Real subscription waste statistics based on research
  const subscriptionStats = [
    {
      figure: "$273",
      label: "Average monthly subscription spending",
      source: "West Monroe survey"
    },
    {
      figure: "12%",
      label: "Of consumers underestimate subscription costs",
      source: "Deloitte Digital Media Trends"
    },
    {
      figure: "$348",
      label: "Annual waste on unused subscriptions",
      source: "Chase Bank study"
    },
    {
      figure: "$640M",
      label: "Lost to unwanted subscription renewals yearly",
      source: "CNBC Consumer Reports"
    }
  ]
  
  // Competitor comparison data
  const competitors = [
    {
      name: "Rocket Money",
      price: "$3-$12/mo",
      dataCollection: "Yes",
      bankAccess: "Required",
      onetime: "No",
      privacy: "Limited"
    },
    {
      name: "Bobby",
      price: "$2-$6/mo",
      dataCollection: "Yes",
      bankAccess: "Optional",
      onetime: "No",
      privacy: "Moderate"
    },
    {
      name: "Truebill",
      price: "$3-$12/mo",
      dataCollection: "Yes",
      bankAccess: "Required",
      onetime: "No",
      privacy: "Limited"
    },
    {
      name: "CashControl",
      price: "$29 once",
      dataCollection: "No",
      bankAccess: "Optional",
      onetime: "Yes",
      privacy: "Complete"
    }
  ]

  return (
    <div className="min-h-screen">
      {/* Hero Section */}
      <section className="relative py-20 px-4 sm:px-6 lg:px-8 overflow-hidden">
        {/* Background gradient */}
        <div className="absolute inset-0 bg-gradient-to-br from-primary-50 via-orange-50 to-yellow-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900"></div>
        <div className="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg%20width%3D%2260%22%20height%3D%2260%22%20viewBox%3D%220%200%2060%2060%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%3E%3Cg%20fill%3D%22none%22%20fill-rule%3D%22evenodd%22%3E%3Cg%20fill%3D%22%23f97316%22%20fill-opacity%3D%220.05%22%3E%3Ccircle%20cx%3D%2230%22%20cy%3D%2230%22%20r%3D%222%22/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-40"></div>
        
        <div className="relative max-w-4xl mx-auto text-center">
          {/* Logo showcase */}
          <div className="mb-8">
            <Logo size="lg" showText={false} className="mx-auto" />
          </div>
          
          <h1 className="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 dark:text-white mb-6">
            Take Control of Your
            <span className="bg-gradient-to-r from-red-500 via-orange-500 to-yellow-500 bg-clip-text text-transparent block">
              Subscription Chaos
            </span>
          </h1>
          <p className="text-xl text-gray-600 dark:text-gray-400 mb-8 max-w-2xl mx-auto leading-relaxed">
            Stop paying for forgotten subscriptions. Track, manage, and cancel with ease. 
            <span className="font-semibold text-primary-600 dark:text-primary-400">One-time payment, lifetime access.</span>
          </p>
          
          {/* Stats preview */}
          <div className="grid grid-cols-3 gap-4 max-w-md mx-auto mb-8 text-center">
            <div className="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-lg p-3">
              <div className="text-2xl font-bold text-green-600">$1,440</div>
              <div className="text-xs text-gray-600 dark:text-gray-400">Avg. Yearly Savings</div>
            </div>
            <div className="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-lg p-3">
              <div className="text-2xl font-bold text-blue-600">3.2</div>
              <div className="text-xs text-gray-600 dark:text-gray-400">Forgotten Subs</div>
            </div>
            <div className="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-lg p-3">
              <div className="text-2xl font-bold text-purple-600">$29</div>
              <div className="text-xs text-gray-600 dark:text-gray-400">One-Time Cost</div>
            </div>
          </div>
          
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            {session ? (
              // Logged-in users: Go to Dashboard or Demo
              <>
                <Link href="/dashboard" className="btn-primary text-lg px-8 py-4 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                  <DollarSign className="h-5 w-5 mr-2" />
                  Go to Dashboard
                </Link>
                <Link href="/demo" className="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white text-lg px-8 py-4 rounded-lg font-medium shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                  <Play className="h-5 w-5 mr-2" />
                  View Demo
                </Link>
              </>
            ) : (
              // Not logged-in users: Demo and Sign Up
              <>
                <Link href="/demo" className="btn-primary text-lg px-8 py-4 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                  <Play className="h-5 w-5 mr-2" />
                  Try Interactive Demo
                </Link>
                <Link 
                  href="/auth/signup"
                  className="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white text-lg px-8 py-4 rounded-lg font-medium shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200"
                >
                  <Zap className="h-5 w-5 mr-2 inline" />
                  Get Started
                </Link>
              </>
            )}
          </div>
          
          <div className="mt-6 text-sm text-gray-500 dark:text-gray-400">
            ✨ <span className="font-medium">No monthly fees</span> • <span className="font-medium">Pay once, use forever</span> • <span className="font-medium">Instant access</span>
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section className="py-16 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-800">
        <div className="max-w-6xl mx-auto">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold text-gray-900 dark:text-white mb-4">
              Everything You Need to <span className="text-primary-600">Take Control</span>
            </h2>
            <p className="text-xl text-gray-600 dark:text-gray-400">
              Powerful features designed to save you money and time
            </p>
          </div>
          
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            {features.map((feature, index) => (
              <div key={index} className="bg-white dark:bg-gray-900 rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow">
                <div className="text-primary-600 dark:text-primary-400 mb-4">
                  {feature.icon}
                </div>
                <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                  {feature.title}
                </h3>
                <p className="text-gray-600 dark:text-gray-400">
                  {feature.description}
                </p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Subscription Waste Data Section */}
      <section className="py-16 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-800">
        <div className="max-w-4xl mx-auto text-center">
          <h2 className="text-3xl font-bold text-gray-900 dark:text-white mb-6">
            The Real Cost of Subscription Overload
          </h2>
          <p className="text-lg text-gray-600 dark:text-gray-400 mb-12 max-w-2xl mx-auto">
            Research shows most people vastly underestimate how much they spend on subscriptions. See the data below.
          </p>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            {subscriptionStats.map((stat, i) => (
              <div 
                key={i} 
                className="bg-white dark:bg-gray-700 p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow"
              >
                <div className="text-3xl font-bold text-primary-600 dark:text-primary-400 mb-2">
                  {stat.figure}
                </div>
                <p className="text-gray-600 dark:text-gray-400 mb-1">
                  {stat.label}
                </p>
                <div className="text-xs text-gray-500 dark:text-gray-500 italic">
                  Source: {stat.source}
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>
      
      {/* Competitor Comparison Section */}
      <section className="py-16 px-4 sm:px-6 lg:px-8 bg-white dark:bg-gray-900">
        <div className="max-w-5xl mx-auto">
          <h2 className="text-3xl font-bold text-gray-900 dark:text-white mb-6 text-center">
            How We Compare
          </h2>
          <p className="text-lg text-gray-600 dark:text-gray-400 mb-12 max-w-2xl mx-auto text-center">
            Unlike subscription-based competitors, we offer a privacy-first approach with a one-time payment model.
          </p>
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead>
                <tr>
                  <th className="px-6 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">App</th>
                  <th className="px-6 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Price</th>
                  <th className="px-6 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data Collection</th>
                  <th className="px-6 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Bank Access</th>
                  <th className="px-6 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">One-Time Payment</th>
                  <th className="px-6 py-3 bg-gray-50 dark:bg-gray-800 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Privacy Focus</th>
                </tr>
              </thead>
              <tbody className="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-800">
                {competitors.map((competitor, i) => (
                  <tr key={i} className={competitor.name === "CashControl" ? "bg-green-50 dark:bg-green-900/20" : ""}>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{competitor.name}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{competitor.price}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{competitor.dataCollection}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{competitor.bankAccess}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{competitor.onetime}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{competitor.privacy}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-16 px-4 sm:px-6 lg:px-8 bg-gradient-to-r from-primary-600 to-primary-700">
        <div className="max-w-4xl mx-auto text-center">
          <h2 className="text-3xl font-bold text-white mb-4">

            Ready to Stop Wasting Money?
          </h2>
          <p className="text-xl text-primary-100 mb-8">
            Join thousands who've taken control of their subscriptions
          </p>
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link
              href="/demo"
              className="bg-white text-primary-600 px-8 py-4 rounded-lg font-semibold hover:bg-gray-100 transition-colors"
            >
              Try Interactive Demo
            </Link>
            <Link
              href="/auth/signup"
              className="bg-primary-800 text-white px-8 py-4 rounded-lg font-semibold hover:bg-primary-900 transition-colors border border-primary-500"
            >
              Start Free Account
            </Link>
          </div>
        </div>
      </section>

      {/* Payment Modal */}
      {showPaymentModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white dark:bg-gray-800 rounded-lg max-w-md w-full p-6">
            <div className="flex justify-between items-center mb-6">
              <h3 className="text-xl font-bold text-gray-900 dark:text-white">
                Get Lifetime Access
              </h3>
              <button
                onClick={() => setShowPaymentModal(false)}
                className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
              >
                <X className="h-6 w-6" />
              </button>
            </div>
            
            <div className="text-center space-y-4">
              <div className="text-3xl font-bold text-gray-900 dark:text-white">$29</div>
              <p className="text-gray-600 dark:text-gray-400">
                One-time payment for lifetime access
              </p>
              
              <div className="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                <div className="flex items-center justify-center">
                  <CheckCircle className="h-4 w-4 text-green-500 mr-2" />
                  Automatic subscription discovery
                </div>
                <div className="flex items-center justify-center">
                  <CheckCircle className="h-4 w-4 text-green-500 mr-2" />
                  Unlimited subscriptions
                </div>
                <div className="flex items-center justify-center">
                  <CheckCircle className="h-4 w-4 text-green-500 mr-2" />
                  Export & backup features
                </div>
                <div className="flex items-center justify-center">
                  <CheckCircle className="h-4 w-4 text-green-500 mr-2" />
                  No recurring payments
                </div>
                <div className="flex items-center justify-center">
                  <CheckCircle className="h-4 w-4 text-green-500 mr-2" />
                  Complete privacy protection
                </div>
              </div>
              
              <Link
                href="/pricing"
                className="block w-full bg-primary-600 hover:bg-primary-700 text-white py-3 px-6 rounded-lg font-medium transition-colors"
              >
                <CreditCard className="h-5 w-5 inline mr-2" />
                Complete Purchase
              </Link>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
