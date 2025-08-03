'use client'

import { useState, useEffect } from 'react'
import { CheckCircle, XCircle, Loader2, AlertTriangle } from 'lucide-react'

export default function TestConnectionsPage() {
  const [supabaseStatus, setSupabaseStatus] = useState<'idle' | 'testing' | 'success' | 'error'>('idle')
  const [stripeStatus, setStripeStatus] = useState<'idle' | 'testing' | 'success' | 'error'>('idle')
  const [envStatus, setEnvStatus] = useState<'checking' | 'ready' | 'missing'>('checking')
  const [results, setResults] = useState<{
    supabase?: string
    stripe?: string
    env?: string
  }>({})

  // Check environment variables on load
  useEffect(() => {
    const checkEnvVars = () => {
      const requiredVars = {
        NEXT_PUBLIC_SUPABASE_URL: process.env.NEXT_PUBLIC_SUPABASE_URL,
        NEXT_PUBLIC_SUPABASE_ANON_KEY: process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY,
        NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY: process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY
      }

      const missing = Object.entries(requiredVars)
        .filter(([key, value]) => !value || value.includes('your_'))
        .map(([key]) => key)

      if (missing.length > 0) {
        setEnvStatus('missing')
        setResults(prev => ({
          ...prev,
          env: `Missing environment variables: ${missing.join(', ')}`
        }))
      } else {
        setEnvStatus('ready')
        setResults(prev => ({
          ...prev,
          env: 'All environment variables are configured'
        }))
      }
    }

    checkEnvVars()
  }, [])

  const testSupabase = async () => {
    setSupabaseStatus('testing')
    try {
      // Dynamic import to avoid build issues
      const { createClient } = await import('@/lib/supabase')
      const supabase = createClient()
      
      // Test connection by trying to get the current user
      const { data, error } = await supabase.auth.getUser()
      
      if (error && error.message !== 'Auth session missing!') {
        throw error
      }
      
      setSupabaseStatus('success')
      setResults(prev => ({ ...prev, supabase: 'Connection successful! Supabase is properly configured.' }))
    } catch (error) {
      setSupabaseStatus('error')
      setResults(prev => ({ ...prev, supabase: `Connection failed: ${error}` }))
    }
  }

  const testStripe = async () => {
    setStripeStatus('testing')
    try {
      // First check if the publishable key is available
      const publishableKey = process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY
      if (!publishableKey || publishableKey.includes('your_')) {
        throw new Error('NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY is not properly configured')
      }
      
      // Dynamic import to avoid build issues
      const { loadStripe } = await import('@stripe/stripe-js')
      const stripe = await loadStripe(publishableKey)
      
      if (!stripe) {
        throw new Error('Stripe failed to load - check your publishable key')
      }
      
      setStripeStatus('success')
      setResults(prev => ({ ...prev, stripe: 'Connection successful! Stripe is properly configured.' }))
    } catch (error) {
      setStripeStatus('error')
      setResults(prev => ({ ...prev, stripe: `Connection failed: ${error}` }))
    }
  }

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'testing':
        return <Loader2 className="h-5 w-5 animate-spin text-blue-500" />
      case 'success':
        return <CheckCircle className="h-5 w-5 text-green-500" />
      case 'error':
        return <XCircle className="h-5 w-5 text-red-500" />
      default:
        return <div className="h-5 w-5 rounded-full border-2 border-gray-300" />
    }
  }

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900 py-12">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-8">
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-4">
            Connection Test
          </h1>
          <p className="text-lg text-gray-600 dark:text-gray-400">
            Test your Supabase and Stripe integrations
          </p>
        </div>

        <div className="space-y-6">
          {/* Environment Variables Status */}
          <div className="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
            <div className="flex items-center space-x-3 mb-4">
              {envStatus === 'checking' && <Loader2 className="h-5 w-5 animate-spin text-blue-500" />}
              {envStatus === 'ready' && <CheckCircle className="h-5 w-5 text-green-500" />}
              {envStatus === 'missing' && <AlertTriangle className="h-5 w-5 text-yellow-500" />}
              <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
                Environment Variables
              </h2>
            </div>
            
            {results.env && (
              <div className={`p-4 rounded-lg ${
                envStatus === 'ready' 
                  ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200'
                  : 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-200'
              }`}>
                {results.env}
              </div>
            )}

            <div className="mt-4 text-sm text-gray-600 dark:text-gray-400">
              <p><strong>Required Variables:</strong></p>
              <ul className="list-disc list-inside mt-2 space-y-1">
                <li>NEXT_PUBLIC_SUPABASE_URL</li>
                <li>NEXT_PUBLIC_SUPABASE_ANON_KEY</li>
                <li>NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY</li>
              </ul>
            </div>
          </div>

          {/* Supabase Test */}
          <div className="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
            <div className="flex items-center justify-between mb-4">
              <div className="flex items-center space-x-3">
                {getStatusIcon(supabaseStatus)}
                <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
                  Supabase Connection
                </h2>
              </div>
              <button
                onClick={testSupabase}
                disabled={supabaseStatus === 'testing'}
                className="btn-primary disabled:opacity-50"
              >
                {supabaseStatus === 'testing' ? 'Testing...' : 'Test Connection'}
              </button>
            </div>
            
            {results.supabase && (
              <div className={`p-4 rounded-lg ${
                supabaseStatus === 'success' 
                  ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200'
                  : 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200'
              }`}>
                {results.supabase}
              </div>
            )}

            <div className="mt-4 text-sm text-gray-600 dark:text-gray-400">
              <p><strong>Environment Variables Needed:</strong></p>
              <ul className="list-disc list-inside mt-2 space-y-1">
                <li>NEXT_PUBLIC_SUPABASE_URL</li>
                <li>NEXT_PUBLIC_SUPABASE_ANON_KEY</li>
                <li>SUPABASE_SERVICE_ROLE_KEY</li>
              </ul>
            </div>
          </div>

          {/* Stripe Test */}
          <div className="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
            <div className="flex items-center justify-between mb-4">
              <div className="flex items-center space-x-3">
                {getStatusIcon(stripeStatus)}
                <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
                  Stripe Connection
                </h2>
              </div>
              <button
                onClick={testStripe}
                disabled={stripeStatus === 'testing'}
                className="btn-primary disabled:opacity-50"
              >
                {stripeStatus === 'testing' ? 'Testing...' : 'Test Connection'}
              </button>
            </div>
            
            {results.stripe && (
              <div className={`p-4 rounded-lg ${
                stripeStatus === 'success' 
                  ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200'
                  : 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200'
              }`}>
                {results.stripe}
              </div>
            )}

            <div className="mt-4 text-sm text-gray-600 dark:text-gray-400">
              <p><strong>Environment Variables Needed:</strong></p>
              <ul className="list-disc list-inside mt-2 space-y-1">
                <li>NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY</li>
                <li>STRIPE_SECRET_KEY</li>
                <li>STRIPE_WEBHOOK_SECRET</li>
              </ul>
            </div>
          </div>

          {/* Instructions */}
          <div className="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
            <h3 className="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-4">
              Next Steps
            </h3>
            <div className="space-y-4 text-blue-800 dark:text-blue-200">
              <div>
                <h4 className="font-medium">1. Configure Supabase:</h4>
                <p className="text-sm">Go to your Supabase project → Settings → API and copy your URL and keys to .env.local</p>
              </div>
              <div>
                <h4 className="font-medium">2. Configure Stripe:</h4>
                <p className="text-sm">Go to your Stripe dashboard → Developers → API keys and copy your keys to .env.local</p>
              </div>
              <div>
                <h4 className="font-medium">3. Set up Database:</h4>
                <p className="text-sm">Run the SQL schema in your Supabase SQL editor to create the necessary tables</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
