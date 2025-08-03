'use client'

import { useState } from 'react'
import { Mail, Send, CheckCircle, AlertCircle, Loader2 } from 'lucide-react'

interface EmailTestResult {
  success: boolean
  message: string
  type: string
}

export function EmailTester() {
  const [isLoading, setIsLoading] = useState(false)
  const [result, setResult] = useState<EmailTestResult | null>(null)

  const sendTestEmail = async (type: 'welcome' | 'upgrade' | 'reminder' | 'bank-scan') => {
    setIsLoading(true)
    setResult(null)

    try {
      const response = await fetch('/api/email/test', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ type }),
      })

      const data = await response.json()

      if (response.ok) {
        setResult({
          success: true,
          message: data.message,
          type: type
        })
      } else {
        setResult({
          success: false,
          message: data.error || 'Failed to send email',
          type: type
        })
      }
    } catch (error) {
      setResult({
        success: false,
        message: 'Network error occurred',
        type: type
      })
    } finally {
      setIsLoading(false)
    }
  }

  const emailTypes = [
    {
      type: 'welcome' as const,
      title: 'Welcome Email',
      description: 'Test the welcome email sent to new users',
      icon: '👋',
      color: 'bg-blue-500'
    },
    {
      type: 'upgrade' as const,
      title: 'Upgrade Confirmation',
      description: 'Test the Pro upgrade confirmation email',
      icon: '⭐',
      color: 'bg-purple-500'
    },
    {
      type: 'reminder' as const,
      title: 'Renewal Reminder',
      description: 'Test a subscription renewal reminder',
      icon: '📅',
      color: 'bg-orange-500'
    },
    {
      type: 'bank-scan' as const,
      title: 'Bank Scan Complete',
      description: 'Test the bank scan completion notification',
      icon: '🏦',
      color: 'bg-green-500'
    }
  ]

  return (
    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
      <div className="flex items-center space-x-3 mb-6">
        <div className="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
          <Mail className="h-6 w-6 text-blue-600 dark:text-blue-400" />
        </div>
        <div>
          <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
            Email Testing
          </h3>
          <p className="text-sm text-gray-600 dark:text-gray-400">
            Test your Plesk SMTP email configuration
          </p>
        </div>
      </div>

      {/* Result Display */}
      {result && (
        <div className={`mb-6 p-4 rounded-lg border ${
          result.success 
            ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' 
            : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800'
        }`}>
          <div className="flex items-center space-x-2">
            {result.success ? (
              <CheckCircle className="h-5 w-5 text-green-600 dark:text-green-400" />
            ) : (
              <AlertCircle className="h-5 w-5 text-red-600 dark:text-red-400" />
            )}
            <span className={`font-medium ${
              result.success 
                ? 'text-green-800 dark:text-green-200' 
                : 'text-red-800 dark:text-red-200'
            }`}>
              {result.message}
            </span>
          </div>
        </div>
      )}

      {/* Email Test Buttons */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        {emailTypes.map((emailType) => (
          <button
            key={emailType.type}
            onClick={() => sendTestEmail(emailType.type)}
            disabled={isLoading}
            className="relative p-4 text-left border border-gray-200 dark:border-gray-700 rounded-lg hover:border-gray-300 dark:hover:border-gray-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <div className="flex items-start space-x-3">
              <div className={`p-2 ${emailType.color} rounded-lg text-white text-sm font-bold flex items-center justify-center min-w-[2.5rem] min-h-[2.5rem]`}>
                {emailType.icon}
              </div>
              <div className="flex-1 min-w-0">
                <h4 className="text-sm font-medium text-gray-900 dark:text-white">
                  {emailType.title}
                </h4>
                <p className="text-xs text-gray-600 dark:text-gray-400 mt-1">
                  {emailType.description}
                </p>
              </div>
              <div className="flex items-center">
                {isLoading ? (
                  <Loader2 className="h-4 w-4 text-gray-400 animate-spin" />
                ) : (
                  <Send className="h-4 w-4 text-gray-400" />
                )}
              </div>
            </div>
          </button>
        ))}
      </div>

      {/* Configuration Note */}
      <div className="mt-6 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
        <h4 className="text-sm font-medium text-gray-900 dark:text-white mb-2">
          📧 Email Configuration
        </h4>
        <div className="text-xs text-gray-600 dark:text-gray-400 space-y-1">
          <p>• Update your <code className="bg-gray-200 dark:bg-gray-800 px-1 rounded">.env.local</code> file with your Plesk SMTP settings</p>
          <p>• Set <code className="bg-gray-200 dark:bg-gray-800 px-1 rounded">PLESK_SMTP_HOST</code>, <code className="bg-gray-200 dark:bg-gray-800 px-1 rounded">PLESK_SMTP_USER</code>, and <code className="bg-gray-200 dark:bg-gray-800 px-1 rounded">PLESK_SMTP_PASS</code></p>
          <p>• Test emails will be sent to your logged-in email address</p>
        </div>
      </div>
    </div>
  )
}
