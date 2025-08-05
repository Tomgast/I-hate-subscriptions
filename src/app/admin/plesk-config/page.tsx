'use client'

import { useState } from 'react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Alert, AlertDescription } from '@/components/ui/alert'
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

export default function PleskConfigPage() {
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
    <div className="container mx-auto p-6 space-y-6">
      <div className="flex items-center space-x-2">
        <Settings className="h-8 w-8 text-blue-600" />
        <h1 className="text-3xl font-bold">Plesk Configuration Manager</h1>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* Configuration Test */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center space-x-2">
              <Database className="h-5 w-5" />
              <span>Configuration Test</span>
            </CardTitle>
            <CardDescription>
              Test database and email configuration
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <Button 
              onClick={runConfigTest} 
              disabled={isLoading}
              className="w-full"
            >
              <Play className="h-4 w-4 mr-2" />
              {isLoading ? 'Testing...' : 'Run Configuration Test'}
            </Button>

            {testResults && (
              <div className="space-y-3">
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

                <Alert>
                  <AlertDescription>
                    {testResults.overall.message}
                  </AlertDescription>
                </Alert>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Email Test */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center space-x-2">
              <Mail className="h-5 w-5" />
              <span>Email Test</span>
            </CardTitle>
            <CardDescription>
              Send a test email to verify SMTP configuration
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div>
              <Label htmlFor="testEmail">Test Email Address</Label>
              <Input
                id="testEmail"
                type="email"
                placeholder="your-email@example.com"
                value={testEmail}
                onChange={(e) => setTestEmail(e.target.value)}
              />
            </div>
            
            <Button 
              onClick={sendTestEmail} 
              disabled={isLoading || !testEmail}
              className="w-full"
            >
              <Mail className="h-4 w-4 mr-2" />
              {isLoading ? 'Sending...' : 'Send Test Email'}
            </Button>
          </CardContent>
        </Card>
      </div>

      {/* Full Setup */}
      <Card>
        <CardHeader>
          <CardTitle>Complete Plesk Setup</CardTitle>
          <CardDescription>
            Initialize database tables and run comprehensive tests
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <Button 
            onClick={runFullSetup} 
            disabled={isLoading}
            className="w-full"
            variant="default"
          >
            <Settings className="h-4 w-4 mr-2" />
            {isLoading ? 'Setting up...' : 'Run Complete Setup'}
          </Button>

          {setupResults && (
            <Alert>
              <AlertDescription>
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
              </AlertDescription>
            </Alert>
          )}
        </CardContent>
      </Card>

      {/* Configuration Info */}
      <Card>
        <CardHeader>
          <CardTitle>Current Configuration</CardTitle>
        </CardHeader>
        <CardContent>
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
        </CardContent>
      </Card>
    </div>
  )
}
