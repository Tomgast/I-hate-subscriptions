'use client'

import { useState, useEffect } from 'react'
import { subscriptionStore } from '@/lib/subscriptionStore'
import { useTheme } from '@/components/ThemeProvider'
import { 
  Bell, 
  Globe, 
  DollarSign, 
  Shield, 
  Trash2, 
  Download,
  Moon,
  Sun,
  Monitor,
  AlertTriangle,
  CheckCircle
} from 'lucide-react'

interface Settings {
  defaultReminderDays: number
  currency: string
  language: string
  emailNotifications: boolean
  theme: 'light' | 'dark' | 'system'
  emailReminderDays: number[]
  emailWelcome: boolean
  emailUpgrade: boolean
  emailBankScan: boolean
  reminderFrequency: 'once' | 'daily' | 'weekly'
  emailTime: string
}

export default function SettingsPage() {
  const { theme, setTheme } = useTheme()
  const [settings, setSettings] = useState<Settings>({
    defaultReminderDays: 3,
    currency: 'USD',
    language: 'en',
    emailNotifications: true,
    theme: 'system',
    emailReminderDays: [7, 3, 1],
    emailWelcome: true,
    emailUpgrade: true,
    emailBankScan: true,
    reminderFrequency: 'once',
    emailTime: '09:00'
  })
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false)
  const [isExporting, setIsExporting] = useState(false)
  const [exportSuccess, setExportSuccess] = useState(false)

  useEffect(() => {
    const savedSettings = localStorage.getItem('app-settings')
    if (savedSettings) {
      try {
        const parsed = JSON.parse(savedSettings)
        setSettings({ ...settings, ...parsed })
      } catch (error) {
        console.error('Error loading settings:', error)
      }
    }
  }, [])

  const saveSettings = (newSettings: Partial<Settings>) => {
    const updated = { ...settings, ...newSettings }
    setSettings(updated)
    localStorage.setItem('app-settings', JSON.stringify(updated))
  }

  const handleThemeChange = (newTheme: 'light' | 'dark' | 'system') => {
    setTheme(newTheme)
    saveSettings({ theme: newTheme })
  }

  const handleExportData = async () => {
    setIsExporting(true)
    try {
      const data = subscriptionStore.exportData()
      const blob = new Blob([data], { type: 'application/json' })
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `subscriptions-backup-${new Date().toISOString().split('T')[0]}.json`
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
      URL.revokeObjectURL(url)
      
      setExportSuccess(true)
      setTimeout(() => setExportSuccess(false), 3000)
    } catch (error) {
      console.error('Export failed:', error)
    } finally {
      setIsExporting(false)
    }
  }

  const handleDeleteAllData = () => {
    subscriptionStore.clearAllData()
    setShowDeleteConfirm(false)
  }

  const subscriptionCount = subscriptionStore.getSubscriptions().length

  return (
    <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Settings</h1>
        <p className="text-gray-600 dark:text-gray-400 mt-2">
          Manage your preferences and account settings
        </p>
      </div>

      <div className="space-y-6">
        <div className="card">
          <div className="flex items-center mb-4">
            <Bell className="h-5 w-5 text-primary-600 dark:text-primary-400 mr-3" />
            <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
              Notifications
            </h2>
          </div>
          
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Default reminder days before renewal
              </label>
              <select
                value={settings.defaultReminderDays}
                onChange={(e) => saveSettings({ defaultReminderDays: parseInt(e.target.value) })}
                className="input-field"
              >
                <option value={1}>1 day</option>
                <option value={3}>3 days</option>
                <option value={7}>7 days</option>
                <option value={14}>14 days</option>
                <option value={30}>30 days</option>
              </select>
            </div>

            <div className="flex items-center">
              <input
                type="checkbox"
                id="emailNotifications"
                checked={settings.emailNotifications}
                onChange={(e) => saveSettings({ emailNotifications: e.target.checked })}
                className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
              />
              <label htmlFor="emailNotifications" className="ml-2 text-sm text-gray-700 dark:text-gray-300">
                Enable email notifications
              </label>
            </div>
          </div>
        </div>

        <div className="card">
          <div className="flex items-center mb-4">
            <Globe className="h-5 w-5 text-primary-600 dark:text-primary-400 mr-3" />
            <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
              Preferences
            </h2>
          </div>
          
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Currency
              </label>
              <select
                value={settings.currency}
                onChange={(e) => saveSettings({ currency: e.target.value })}
                className="input-field"
              >
                <option value="USD">USD ($)</option>
                <option value="EUR">EUR (€)</option>
                <option value="GBP">GBP (£)</option>
                <option value="CAD">CAD ($)</option>
                <option value="AUD">AUD ($)</option>
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Language
              </label>
              <select
                value={settings.language}
                onChange={(e) => saveSettings({ language: e.target.value })}
                className="input-field"
              >
                <option value="en">English</option>
                <option value="es">Español</option>
                <option value="fr">Français</option>
                <option value="de">Deutsch</option>
                <option value="it">Italiano</option>
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Theme
              </label>
              <div className="flex space-x-3">
                <button
                  onClick={() => handleThemeChange('light')}
                  className={`flex items-center px-3 py-2 rounded-lg border ${
                    theme === 'light'
                      ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300'
                      : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
                  }`}
                >
                  <Sun className="h-4 w-4 mr-2" />
                  Light
                </button>
                <button
                  onClick={() => handleThemeChange('dark')}
                  className={`flex items-center px-3 py-2 rounded-lg border ${
                    theme === 'dark'
                      ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300'
                      : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
                  }`}
                >
                  <Moon className="h-4 w-4 mr-2" />
                  Dark
                </button>
                <button
                  onClick={() => handleThemeChange('system')}
                  className={`flex items-center px-3 py-2 rounded-lg border ${
                    theme === 'system'
                      ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300'
                      : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
                  }`}
                >
                  <Monitor className="h-4 w-4 mr-2" />
                  System
                </button>
              </div>
            </div>
          </div>
        </div>

        <div className="card">
          <div className="flex items-center mb-4">
            <Shield className="h-5 w-5 text-primary-600 dark:text-primary-400 mr-3" />
            <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
              Privacy & Data
            </h2>
          </div>
          
          <div className="space-y-4">
            <div className="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
              <div className="flex items-center">
                <CheckCircle className="h-5 w-5 text-green-600 dark:text-green-400 mr-2" />
                <span className="text-sm font-medium text-green-800 dark:text-green-200">
                  Privacy-First Design
                </span>
              </div>
              <p className="text-sm text-green-700 dark:text-green-300 mt-1">
                Your data is stored locally in your browser by default. No tracking, no data sharing.
              </p>
            </div>

            <div>
              <h3 className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Data Management
              </h3>
              <div className="flex flex-col sm:flex-row gap-3">
                <button
                  onClick={handleExportData}
                  disabled={isExporting}
                  className="btn-secondary flex items-center gap-2 disabled:opacity-50"
                >
                  <Download className="h-4 w-4" />
                  {isExporting ? 'Exporting...' : 'Export All Data'}
                </button>
                
                {exportSuccess && (
                  <div className="flex items-center text-green-600 dark:text-green-400 text-sm">
                    <CheckCircle className="h-4 w-4 mr-1" />
                    Export successful!
                  </div>
                )}
              </div>
              <p className="text-xs text-gray-500 dark:text-gray-400 mt-2">
                Download a backup of all your subscription data as JSON
              </p>
            </div>

            <div className="pt-4 border-t border-gray-200 dark:border-gray-700">
              <h3 className="text-sm font-medium text-red-700 dark:text-red-400 mb-2">
                Danger Zone
              </h3>
              <button
                onClick={() => setShowDeleteConfirm(true)}
                className="btn-danger flex items-center gap-2"
              >
                <Trash2 className="h-4 w-4" />
                Delete All Data
              </button>
              <p className="text-xs text-gray-500 dark:text-gray-400 mt-2">
                Permanently delete all {subscriptionCount} subscriptions. This cannot be undone.
              </p>
            </div>
          </div>
        </div>
      </div>

      {showDeleteConfirm && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full">
            <div className="flex items-center mb-4">
              <AlertTriangle className="h-6 w-6 text-red-600 dark:text-red-400 mr-3" />
              <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                Delete All Data
              </h3>
            </div>
            <p className="text-gray-600 dark:text-gray-400 mb-6">
              Are you sure you want to delete all {subscriptionCount} subscriptions? 
              This action cannot be undone.
            </p>
            <div className="flex justify-end space-x-3">
              <button
                onClick={() => setShowDeleteConfirm(false)}
                className="btn-secondary"
              >
                Cancel
              </button>
              <button
                onClick={handleDeleteAllData}
                className="btn-danger"
              >
                Delete All Data
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
