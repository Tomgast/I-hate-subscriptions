'use client'

import Link from 'next/link'
import { useState } from 'react'
import { 
  CreditCard, 
  Shield, 
  Clock, 
  TrendingDown, 
  CheckCircle, 
  Star,
  ArrowRight,
  DollarSign,
  Calendar,
  FileText
} from 'lucide-react'

export default function HomePage() {
  const [isPaymentModalOpen, setIsPaymentModalOpen] = useState(false)

  const features = [
    {
      icon: <CreditCard className="h-6 w-6" />,
      title: "Track All Subscriptions",
      description: "Manually add or bulk import all your recurring payments in one place"
    },
    {
      icon: <Clock className="h-6 w-6" />,
      title: "Smart Reminders",
      description: "Get notified before renewals and trial expirations via email"
    },
    {
      icon: <TrendingDown className="h-6 w-6" />,
      title: "Spending Insights",
      description: "See your monthly and yearly spending with detailed analytics"
    },
    {
      icon: <FileText className="h-6 w-6" />,
      title: "Export & Backup",
      description: "Download your data as CSV or PDF anytime you want"
    },
    {
      icon: <Shield className="h-6 w-6" />,
      title: "Privacy First",
      description: "Your data stays local by default, with optional cloud sync"
    },
    {
      icon: <DollarSign className="h-6 w-6" />,
      title: "One-Time Payment",
      description: "Pay once, use forever. No recurring fees or hidden costs"
    }
  ]

  const testimonials = [
    {
      name: "Sarah M.",
      text: "Finally found all my forgotten subscriptions! Saved me $180/month.",
      rating: 5
    },
    {
      name: "Mike R.",
      text: "Clean interface, works perfectly. Worth every penny for the lifetime access.",
      rating: 5
    },
    {
      name: "Jessica L.",
      text: "The privacy-first approach sold me. My data stays mine.",
      rating: 5
    }
  ]

  return (
    <div className="min-h-screen">
      {/* Hero Section */}
      <section className="relative bg-gradient-to-br from-primary-50 to-primary-100 dark:from-gray-900 dark:to-gray-800 py-20 px-4 sm:px-6 lg:px-8">
        <div className="max-w-7xl mx-auto">
          <div className="text-center">
            <h1 className="text-4xl md:text-6xl font-bold text-gray-900 dark:text-white mb-6">
              Stop Overpaying for
              <span className="text-primary-600 dark:text-primary-400"> Subscriptions</span>
            </h1>
            <p className="text-xl md:text-2xl text-gray-600 dark:text-gray-300 mb-8 max-w-3xl mx-auto">
              Take control of your recurring payments. Track, manage, and cancel subscriptions 
              with our privacy-first platform. Pay once, use forever.
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center items-center">
              <button
                onClick={() => setIsPaymentModalOpen(true)}
                className="btn-primary text-lg px-8 py-4 flex items-center gap-2"
              >
                Get Lifetime Access - $29
                <ArrowRight className="h-5 w-5" />
              </button>
              <Link
                href="/demo"
                className="btn-secondary text-lg px-8 py-4"
              >
                Try Demo
              </Link>
            </div>
            <p className="text-sm text-gray-500 dark:text-gray-400 mt-4">
              ✓ No monthly fees ✓ Privacy-first ✓ Cancel anytime
            </p>
          </div>
        </div>
      </section>

      {/* Problem Statement */}
      <section className="py-16 px-4 sm:px-6 lg:px-8 bg-white dark:bg-gray-900">
        <div className="max-w-4xl mx-auto text-center">
          <h2 className="text-3xl font-bold text-gray-900 dark:text-white mb-8">
            The Average Person Wastes $273/Month on Forgotten Subscriptions
          </h2>
          <div className="grid md:grid-cols-3 gap-8">
            <div className="stat-card text-center">
              <div className="text-3xl font-bold text-primary-600 dark:text-primary-400 mb-2">84%</div>
              <p className="text-gray-600 dark:text-gray-300">Underestimate their monthly spend</p>
            </div>
            <div className="stat-card text-center">
              <div className="text-3xl font-bold text-primary-600 dark:text-primary-400 mb-2">$1,800</div>
              <p className="text-gray-600 dark:text-gray-300">Average yearly subscription waste</p>
            </div>
            <div className="stat-card text-center">
              <div className="text-3xl font-bold text-primary-600 dark:text-primary-400 mb-2">5 min</div>
              <p className="text-gray-600 dark:text-gray-300">To get complete clarity</p>
            </div>
          </div>
        </div>
      </section>

      {/* Features */}
      <section className="py-16 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-800">
        <div className="max-w-7xl mx-auto">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold text-gray-900 dark:text-white mb-4">
              Everything You Need to Manage Subscriptions
            </h2>
            <p className="text-xl text-gray-600 dark:text-gray-300">
              Powerful features that match or exceed leading subscription trackers
            </p>
          </div>
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            {features.map((feature, index) => (
              <div key={index} className="card hover:shadow-md transition-shadow">
                <div className="text-primary-600 dark:text-primary-400 mb-4">
                  {feature.icon}
                </div>
                <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                  {feature.title}
                </h3>
                <p className="text-gray-600 dark:text-gray-300">
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
              Join Thousands Who've Taken Control
            </h2>
          </div>
          <div className="grid md:grid-cols-3 gap-8">
            {testimonials.map((testimonial, index) => (
              <div key={index} className="card">
                <div className="flex mb-4">
                  {[...Array(testimonial.rating)].map((_, i) => (
                    <Star key={i} className="h-5 w-5 text-yellow-400 fill-current" />
                  ))}
                </div>
                <p className="text-gray-600 dark:text-gray-300 mb-4">
                  "{testimonial.text}"
                </p>
                <p className="font-semibold text-gray-900 dark:text-white">
                  {testimonial.name}
                </p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-16 px-4 sm:px-6 lg:px-8 bg-primary-600 dark:bg-primary-700">
        <div className="max-w-4xl mx-auto text-center">
          <h2 className="text-3xl font-bold text-white mb-4">
            Ready to Stop Subscription Fatigue?
          </h2>
          <p className="text-xl text-primary-100 mb-8">
            Get instant access with our one-time payment. No recurring fees, ever.
          </p>
          <button
            onClick={() => setIsPaymentModalOpen(true)}
            className="bg-white text-primary-600 hover:bg-gray-100 font-bold py-4 px-8 rounded-lg text-lg transition-colors"
          >
            Get Lifetime Access - $29
          </button>
          <div className="mt-6 flex justify-center items-center gap-6 text-primary-100">
            <div className="flex items-center gap-2">
              <CheckCircle className="h-5 w-5" />
              <span>30-day money back guarantee</span>
            </div>
            <div className="flex items-center gap-2">
              <Shield className="h-5 w-5" />
              <span>Secure payment</span>
            </div>
          </div>
        </div>
      </section>

      {/* Payment Modal */}
      {isPaymentModalOpen && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full">
            <h3 className="text-2xl font-bold text-gray-900 dark:text-white mb-4">
              Get Lifetime Access
            </h3>
            <p className="text-gray-600 dark:text-gray-300 mb-6">
              Pay once, use forever. No recurring fees or hidden costs.
            </p>
            <div className="space-y-4">
              <button className="w-full bg-[#635bff] hover:bg-[#5a52e8] text-white py-3 px-4 rounded-lg font-medium transition-colors">
                Pay with Stripe - $29
              </button>
              <button className="w-full bg-[#0070ba] hover:bg-[#005ea6] text-white py-3 px-4 rounded-lg font-medium transition-colors">
                Pay with PayPal - $29
              </button>
            </div>
            <button
              onClick={() => setIsPaymentModalOpen(false)}
              className="w-full mt-4 btn-secondary"
            >
              Cancel
            </button>
          </div>
        </div>
      )}
    </div>
  )
}
