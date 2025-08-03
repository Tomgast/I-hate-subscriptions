'use client'

import { useState, useRef } from 'react'
import { subscriptionStore } from '@/lib/subscriptionStore'
import { SUBSCRIPTION_CATEGORIES, SubscriptionCategory } from '@/types/subscription'
import { Upload, X, Download, AlertCircle, CheckCircle, FileText } from 'lucide-react'
import Papa from 'papaparse'

interface BulkImportProps {
  onClose: () => void
}

interface ImportRow {
  name: string
  price: string
  billingCycle: string
  nextBillingDate: string
  category: string
  description?: string
  website?: string
  tags?: string
}

export function BulkImport({ onClose }: BulkImportProps) {
  const [file, setFile] = useState<File | null>(null)
  const [importData, setImportData] = useState<ImportRow[]>([])
  const [isProcessing, setIsProcessing] = useState(false)
  const [errors, setErrors] = useState<string[]>([])
  const [step, setStep] = useState<'upload' | 'preview' | 'complete'>('upload')
  const [importedCount, setImportedCount] = useState(0)
  const fileInputRef = useRef<HTMLInputElement>(null)

  const handleFileSelect = (selectedFile: File) => {
    if (!selectedFile.name.endsWith('.csv')) {
      setErrors(['Please select a CSV file'])
      return
    }

    setFile(selectedFile)
    setErrors([])
    setIsProcessing(true)

    Papa.parse(selectedFile, {
      header: true,
      skipEmptyLines: true,
      complete: (results) => {
        const validationErrors: string[] = []
        const processedData: ImportRow[] = []

        results.data.forEach((row: any, index: number) => {
          const rowNumber = index + 2 // +2 because index is 0-based and we skip header

          // Validate required fields
          if (!row.name?.trim()) {
            validationErrors.push(`Row ${rowNumber}: Name is required`)
          }
          if (!row.price || isNaN(parseFloat(row.price))) {
            validationErrors.push(`Row ${rowNumber}: Valid price is required`)
          }
          if (!row.billingCycle?.trim()) {
            validationErrors.push(`Row ${rowNumber}: Billing cycle is required`)
          }
          if (!row.nextBillingDate?.trim()) {
            validationErrors.push(`Row ${rowNumber}: Next billing date is required`)
          }
          if (!row.category?.trim()) {
            validationErrors.push(`Row ${rowNumber}: Category is required`)
          }

          // Validate billing cycle
          const validCycles = ['monthly', 'yearly', 'weekly', 'daily', 'quarterly']
          if (row.billingCycle && !validCycles.includes(row.billingCycle.toLowerCase())) {
            validationErrors.push(`Row ${rowNumber}: Invalid billing cycle. Must be one of: ${validCycles.join(', ')}`)
          }

          // Validate category
          if (row.category && !Object.keys(SUBSCRIPTION_CATEGORIES).includes(row.category.toLowerCase())) {
            validationErrors.push(`Row ${rowNumber}: Invalid category. Must be one of: ${Object.keys(SUBSCRIPTION_CATEGORIES).join(', ')}`)
          }

          // Validate date format
          if (row.nextBillingDate && isNaN(Date.parse(row.nextBillingDate))) {
            validationErrors.push(`Row ${rowNumber}: Invalid date format. Use YYYY-MM-DD`)
          }

          if (validationErrors.length === 0 || validationErrors.filter(e => e.includes(`Row ${rowNumber}`)).length === 0) {
            processedData.push({
              name: row.name?.trim(),
              price: row.price,
              billingCycle: row.billingCycle?.toLowerCase(),
              nextBillingDate: row.nextBillingDate,
              category: row.category?.toLowerCase(),
              description: row.description?.trim() || undefined,
              website: row.website?.trim() || undefined,
              tags: row.tags?.trim() || undefined,
            })
          }
        })

        setErrors(validationErrors)
        setImportData(processedData)
        setIsProcessing(false)
        
        if (validationErrors.length === 0 && processedData.length > 0) {
          setStep('preview')
        }
      },
      error: (error) => {
        setErrors([`Failed to parse CSV: ${error.message}`])
        setIsProcessing(false)
      }
    })
  }

  const handleImport = () => {
    setIsProcessing(true)
    
    try {
      const subscriptionsToImport = importData.map(row => ({
        name: row.name,
        price: parseFloat(row.price),
        currency: 'USD',
        billingCycle: row.billingCycle as any,
        nextBillingDate: row.nextBillingDate,
        category: row.category as SubscriptionCategory,
        description: row.description,
        website: row.website,
        reminderDays: 3,
        tags: row.tags ? row.tags.split(',').map(t => t.trim()).filter(Boolean) : undefined,
        isActive: true,
      }))

      const imported = subscriptionStore.bulkImport(subscriptionsToImport)
      setImportedCount(imported.length)
      setStep('complete')
    } catch (error) {
      setErrors(['Failed to import subscriptions. Please try again.'])
    } finally {
      setIsProcessing(false)
    }
  }

  const downloadTemplate = () => {
    const template = [
      'name,price,billingCycle,nextBillingDate,category,description,website,tags',
      'Netflix,15.99,monthly,2024-03-15,streaming,Video streaming service,https://netflix.com,entertainment',
      'Spotify,9.99,monthly,2024-03-10,music,Music streaming,https://spotify.com,music;personal',
      'Adobe Creative Cloud,52.99,monthly,2024-03-20,software,Design software suite,https://adobe.com,work;design'
    ].join('\n')

    const blob = new Blob([template], { type: 'text/csv' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = 'subscription-import-template.csv'
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
  }

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div className="bg-white dark:bg-gray-800 rounded-lg max-w-4xl w-full max-h-[90vh] overflow-hidden">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
          <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
            {step === 'upload' && 'Import Subscriptions'}
            {step === 'preview' && 'Preview Import'}
            {step === 'complete' && 'Import Complete'}
          </h2>
          <button
            onClick={onClose}
            className="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-md transition-colors"
          >
            <X className="h-5 w-5" />
          </button>
        </div>

        <div className="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
          {step === 'upload' && (
            <div className="space-y-6">
              {/* Instructions */}
              <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <h3 className="font-medium text-blue-900 dark:text-blue-100 mb-2">
                  CSV Import Instructions
                </h3>
                <ul className="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                  <li>• Required columns: name, price, billingCycle, nextBillingDate, category</li>
                  <li>• Optional columns: description, website, tags</li>
                  <li>• Billing cycles: monthly, yearly, weekly, daily, quarterly</li>
                  <li>• Date format: YYYY-MM-DD (e.g., 2024-03-15)</li>
                  <li>• Categories: {Object.keys(SUBSCRIPTION_CATEGORIES).slice(0, 5).join(', ')}, etc.</li>
                </ul>
              </div>

              {/* Template Download */}
              <div className="text-center">
                <button
                  onClick={downloadTemplate}
                  className="btn-secondary flex items-center gap-2 mx-auto"
                >
                  <Download className="h-4 w-4" />
                  Download Template CSV
                </button>
              </div>

              {/* File Upload */}
              <div className="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center">
                <input
                  ref={fileInputRef}
                  type="file"
                  accept=".csv"
                  onChange={(e) => e.target.files?.[0] && handleFileSelect(e.target.files[0])}
                  className="hidden"
                />
                
                {!file ? (
                  <div>
                    <Upload className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                    <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                      Upload CSV File
                    </h3>
                    <p className="text-gray-600 dark:text-gray-400 mb-4">
                      Select a CSV file with your subscription data
                    </p>
                    <button
                      onClick={() => fileInputRef.current?.click()}
                      className="btn-primary"
                      disabled={isProcessing}
                    >
                      {isProcessing ? 'Processing...' : 'Choose File'}
                    </button>
                  </div>
                ) : (
                  <div>
                    <FileText className="h-12 w-12 text-green-500 mx-auto mb-4" />
                    <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                      {file.name}
                    </h3>
                    <p className="text-gray-600 dark:text-gray-400 mb-4">
                      {(file.size / 1024).toFixed(1)} KB
                    </p>
                    <button
                      onClick={() => fileInputRef.current?.click()}
                      className="btn-secondary mr-3"
                      disabled={isProcessing}
                    >
                      Choose Different File
                    </button>
                  </div>
                )}
              </div>

              {/* Errors */}
              {errors.length > 0 && (
                <div className="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                  <div className="flex items-center mb-2">
                    <AlertCircle className="h-5 w-5 text-red-600 dark:text-red-400 mr-2" />
                    <h3 className="font-medium text-red-900 dark:text-red-100">
                      Import Errors
                    </h3>
                  </div>
                  <ul className="text-sm text-red-700 dark:text-red-300 space-y-1 max-h-40 overflow-y-auto">
                    {errors.map((error, index) => (
                      <li key={index}>• {error}</li>
                    ))}
                  </ul>
                </div>
              )}
            </div>
          )}

          {step === 'preview' && (
            <div className="space-y-6">
              <div className="flex items-center justify-between">
                <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                  Preview Import ({importData.length} subscriptions)
                </h3>
                <div className="flex gap-3">
                  <button
                    onClick={() => setStep('upload')}
                    className="btn-secondary"
                  >
                    Back
                  </button>
                  <button
                    onClick={handleImport}
                    disabled={isProcessing}
                    className="btn-primary disabled:opacity-50"
                  >
                    {isProcessing ? 'Importing...' : 'Import All'}
                  </button>
                </div>
              </div>

              <div className="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                <div className="overflow-x-auto">
                  <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead className="bg-gray-50 dark:bg-gray-800">
                      <tr>
                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                          Name
                        </th>
                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                          Price
                        </th>
                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                          Cycle
                        </th>
                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                          Next Billing
                        </th>
                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                          Category
                        </th>
                      </tr>
                    </thead>
                    <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                      {importData.slice(0, 10).map((row, index) => (
                        <tr key={index}>
                          <td className="px-4 py-3 text-sm text-gray-900 dark:text-white">
                            {row.name}
                          </td>
                          <td className="px-4 py-3 text-sm text-gray-900 dark:text-white">
                            ${parseFloat(row.price).toFixed(2)}
                          </td>
                          <td className="px-4 py-3 text-sm text-gray-900 dark:text-white capitalize">
                            {row.billingCycle}
                          </td>
                          <td className="px-4 py-3 text-sm text-gray-900 dark:text-white">
                            {row.nextBillingDate}
                          </td>
                          <td className="px-4 py-3 text-sm text-gray-900 dark:text-white capitalize">
                            {row.category.replace('_', ' ')}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
                {importData.length > 10 && (
                  <div className="px-4 py-3 bg-gray-50 dark:bg-gray-800 text-sm text-gray-600 dark:text-gray-400">
                    Showing first 10 of {importData.length} subscriptions
                  </div>
                )}
              </div>
            </div>
          )}

          {step === 'complete' && (
            <div className="text-center space-y-6">
              <div className="mx-auto w-16 h-16 bg-green-100 dark:bg-green-900/20 rounded-full flex items-center justify-center">
                <CheckCircle className="h-8 w-8 text-green-600 dark:text-green-400" />
              </div>
              <div>
                <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                  Import Successful!
                </h3>
                <p className="text-gray-600 dark:text-gray-400">
                  Successfully imported {importedCount} subscriptions
                </p>
              </div>
              <button
                onClick={onClose}
                className="btn-primary"
              >
                Done
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
  )
}
