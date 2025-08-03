'use client'

import { useState } from 'react'
import Link from 'next/link'
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

  const testimonials = [
    {
      name: "Sarah M.",
      text: "Found $180/month in forgotten subscriptions! This app paid for itself immediately.",
      rating: 5,
      savings: "$2,160/year"
    },
    {
      name: "Mike R.",
      text: "Finally, a subscription tracker that doesn't charge monthly fees. Love the one-time payment.",
      rating: 5,
      savings: "$840/year"
    },
    {
      name: "Jessica L.",
      text: "The privacy-first approach sold me. My data stays mine.",
      rating: 5,
      savings: "$1,320/year"
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
            <Link href="/demo" className="btn-primary text-lg px-8 py-4 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
              <Play className="h-5 w-5 mr-2" />
              Try Free Demo
            </Link>
            <Link 
              href="/auth/signup"
              className="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white text-lg px-8 py-4 rounded-lg font-medium shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200"
            >
              <Zap className="h-5 w-5 mr-2 inline" />
              Get Started
            </Link>
          </div>
          
          <div className="mt-6 text-sm text-gray-500 dark:text-gray-400">
            ✨ <span className="font-medium">No monthly fees</span> • <span className="font-medium">30-day money back</span> • <span className="font-medium">Instant access</span>
          </div>
        </div>
      </section>

      {/* Problem Statement */}
      <section className="py-16 px-4 sm:px-6 lg:px-8 bg-white dark:bg-gray-900">
        <div className="max-w-4xl mx-auto text-center">
          <h2 className="text-3xl font-bold text-gray-900 dark:text-white mb-8">
            The Average Person Wastes <span className="text-red-500">$273/Month</span> on Forgotten Subscriptions
          </h2>
          <div className="grid md:grid-cols-3 gap-8 mb-12">
            <div className="text-center">
              <div className="text-4xl font-bold text-red-500 mb-2">73%</div>
              <p className="text-gray-600 dark:text-gray-400">Have subscriptions they forgot about</p>
            </div>
            <div className="text-center">
              <div className="text-4xl font-bold text-orange-500 mb-2">$3,276</div>
              <p className="text-gray-600 dark:text-gray-400">Wasted per year on unused services</p>
            </div>
            <div className="text-center">
              <div className="text-4xl font-bold text-yellow-500 mb-2">5.2</div>
              <p className="text-gray-600 dark:text-gray-400">Average forgotten subscriptions per person</p>
            </div>
          </div>
          <p className="text-xl text-gray-600 dark:text-gray-400 mb-8">
            Don't be part of the statistic. Take control today.
          </p>
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

      {/* Testimonials */}
      <section className="py-16 px-4 sm:px-6 lg:px-8 bg-white dark:bg-gray-900">
        <div className="max-w-6xl mx-auto">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold text-gray-900 dark:text-white mb-4">
              Real Results from Real Users
            </h2>
            <p className="text-xl text-gray-600 dark:text-gray-400">
              See how much our users are saving every month
            </p>
          </div>
          
          <div className="grid md:grid-cols-3 gap-8">
            {testimonials.map((testimonial, index) => (
              <div key={index} className="bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
                <div className="flex mb-4">
                  {[...Array(testimonial.rating)].map((_, i) => (
                    <Star key={i} className="h-5 w-5 text-yellow-400 fill-current" />
                  ))}
                </div>
                <p className="text-gray-600 dark:text-gray-400 mb-4">
                  "{testimonial.text}"
                </p>
                <div className="flex justify-between items-center">
                  <div className="font-semibold text-gray-900 dark:text-white">
                    {testimonial.name}
                  </div>
                  <div className="text-green-600 dark:text-green-400 font-bold">
                    Saved {testimonial.savings}
                  </div>
                </div>
              </div>
            ))}
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
              Try Free Demo
            </Link>
            <Link
              href="/auth/signup"
              className="bg-primary-800 text-white px-8 py-4 rounded-lg font-semibold hover:bg-primary-900 transition-colors border border-primary-500"
            >
              Get Started
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
                  30-day money-back guarantee
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
