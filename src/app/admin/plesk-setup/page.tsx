'use client'

import { useState } from 'react'
import { CheckCircle, XCircle, Database, Mail, Settings, Play } from 'lucide-react'

interface TestResults {
  database: {
    connection: boolean
    tables: boolean
    queries: boolean
    error: string | null
  }
  email: {
    configuration: boolean
    connection: boolean
    error: string | null
  }
  overall: {
    success: boolean
    message: string
    timestamp: string
  }
}

export default function PleskSetupPage() {
  const [testResults, setTestResults] = useState<TestResults | null>(null)
  const [isLoading, setIsLoading] = useState(false)
  const [testEmail, setTestEmail] = useState('')
  const [setupResults, setSetupResults] = useState<any>(null)

  const runConfigTest = async () => {
    setIsLoading(true)
    try {
      const response = await fetch('/api/config/test-plesk')
      const data = await response.json()
      setTestResults(data.results)
    } catch (error) {
      console.error('Failed to run config test:', error)
    } finally {
      setIsLoading(false)
    }
  }

  const runMigrationTest = async () => {
    setIsLoading(true)
    try {
      const response = await fetch('/api/migration/test')
      const data = await response.json()
      alert(data.success ? 'Migration test successful!' : 'Migration test failed')
    } catch (error) {
      alert('Migration test failed')
    } finally {
      setIsLoading(false)
    }
  }

  const runFullSetup = async () => {
    setIsLoading(true)
    try {
      const response = await fetch('/api/config/test-plesk', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          initializeDatabase: true,
          testEmail: testEmail || null,
          createTestUser: true
        })
      })
      const data = await response.json()
      setSetupResults(data)
    } catch (error) {
      console.error('Failed to run setup:', error)
    } finally {
      setIsLoading(false)
    }
  }

  const sendTestEmail = async () => {
    if (!testEmail) return
    
    setIsLoading(true)
    try {
      const response = await fetch(`/api/email/test-plesk?email=${encodeURIComponent(testEmail)}`)
      const data = await response.json()
      alert(data.success ? 'Test email sent successfully!' : 'Failed to send test email')
    } catch (error) {
      alert('Failed to send test email')
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <div className="container mx-auto p-6 space-y-6 max-w-4xl">
      <div className="flex items-center space-x-2 mb-8">
        <Settings className="h-8 w-8 text-blue-600" />
        <h1 className="text-3xl font-bold">Plesk Configuration Setup</h1>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* Configuration Test */}
        <div className="bg-white p-6 rounded-lg shadow-md border">
          <div className="flex items-center space-x-2 mb-4">
            <Database className="h-5 w-5" />
            <h2 className="text-xl font-semibold">Configuration Test</h2>
          </div>
          <p className="text-gray-600 mb-4">Test database and email configuration</p>
          
          <button 
            onClick={runConfigTest} 
            disabled={isLoading}
            className="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50 flex items-center justify-center space-x-2"
          >
            <Play className="h-4 w-4" />
            <span>{isLoading ? 'Testing...' : 'Run Configuration Test'}</span>
          </button>

          {testResults && (
            <div className="mt-4 space-y-3">
              <div className="flex items-center space-x-2">
                {testResults.database.connection ? (
                  <CheckCircle className="h-5 w-5 text-green-500" />
                ) : (
                  <XCircle className="h-5 w-5 text-red-500" />
                )}
                <span>Database Connection</span>
              </div>

              <div className="flex items-center space-x-2">
                {testResults.database.tables ? (
                  <CheckCircle className="h-5 w-5 text-green-500" />
                ) : (
                  <XCircle className="h-5 w-5 text-red-500" />
                )}
                <span>Database Tables</span>
              </div>

              <div className="flex items-center space-x-2">
                {testResults.email.configuration ? (
                  <CheckCircle className="h-5 w-5 text-green-500" />
                ) : (
                  <XCircle className="h-5 w-5 text-red-500" />
                )}
                <span>Email Configuration</span>
              </div>

              <div className="bg-gray-100 p-3 rounded-md">
                <p className="text-sm">{testResults.overall.message}</p>
              </div>
            </div>
          )}
        </div>

        {/* Email Test */}
        <div className="bg-white p-6 rounded-lg shadow-md border">
          <div className="flex items-center space-x-2 mb-4">
            <Mail className="h-5 w-5" />
            <h2 className="text-xl font-semibold">Email Test</h2>
          </div>
          <p className="text-gray-600 mb-4">Send a test email to verify SMTP configuration</p>
          
          <div className="mb-4">
            <label className="block text-sm font-medium mb-2">Test Email Address</label>
            <input
              type="email"
              placeholder="your-email@example.com"
              value={testEmail}
              onChange={(e: React.ChangeEvent<HTMLInputElement>) => setTestEmail(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          
          <button 
            onClick={sendTestEmail} 
            disabled={isLoading || !testEmail}
            className="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 disabled:opacity-50 flex items-center justify-center space-x-2"
          >
            <Mail className="h-4 w-4" />
            <span>{isLoading ? 'Sending...' : 'Send Test Email'}</span>
          </button>
        </div>
      </div>

      {/* Migration Test */}
      <div className="bg-white p-6 rounded-lg shadow-md border">
        <h2 className="text-xl font-semibold mb-4">Migration Test</h2>
        <p className="text-gray-600 mb-4">Test complete migration from Supabase to Plesk MySQL</p>
        
        <button 
          onClick={runMigrationTest} 
          disabled={isLoading}
          className="bg-purple-600 text-white px-6 py-2 rounded-md hover:bg-purple-700 disabled:opacity-50 mr-4"
        >
          {isLoading ? 'Testing...' : 'Run Migration Test'}
        </button>
      </div>

      {/* Full Setup */}
      <div className="bg-white p-6 rounded-lg shadow-md border">
        <h2 className="text-xl font-semibold mb-4">Complete Plesk Setup</h2>
        <p className="text-gray-600 mb-4">Initialize database tables and run comprehensive tests</p>
        
        <button 
          onClick={runFullSetup} 
          disabled={isLoading}
          className="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50"
        >
          {isLoading ? 'Setting up...' : 'Run Complete Setup'}
        </button>

        {setupResults && (
          <div className="mt-4 bg-gray-100 p-4 rounded-md">
            <div className="space-y-2">
              <p><strong>Setup Results:</strong></p>
              <ul className="list-disc list-inside space-y-1">
                <li>Database Initialized: {setupResults.results?.databaseInitialized ? '✅' : '❌'}</li>
                <li>Test Email Sent: {setupResults.results?.testEmailSent ? '✅' : '❌'}</li>
                <li>Test User Created: {setupResults.results?.testUserCreated ? '✅' : '❌'}</li>
              </ul>
              {setupResults.results?.errors?.length > 0 && (
                <div className="mt-2">
                  <p><strong>Errors:</strong></p>
                  <ul className="list-disc list-inside">
                    {setupResults.results.errors.map((error: string, index: number) => (
                      <li key={index} className="text-red-600">{error}</li>
                    ))}
                  </ul>
                </div>
              )}
            </div>
          </div>
        )}
      </div>

      {/* Configuration Info */}
      <div className="bg-white p-6 rounded-lg shadow-md border">
        <h2 className="text-xl font-semibold mb-4">Current Configuration</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div>
            <h4 className="font-semibold mb-2">Database (MySQL)</h4>
            <ul className="space-y-1">
              <li>Host: localhost:3306</li>
              <li>Database: vxmjmwlj_</li>
              <li>User: 123cashcontrol</li>
            </ul>
          </div>
          <div>
            <h4 className="font-semibold mb-2">Email (SMTP)</h4>
            <ul className="space-y-1">
              <li>Host: 123cashcontrol.com</li>
              <li>Port: 587 (TLS)</li>
              <li>User: info@123cashcontrol.com</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  )
}
