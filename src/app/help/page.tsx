'use client'

import { useState } from 'react'
import { 
  ChevronDown, 
  ChevronUp, 
  HelpCircle, 
  Mail, 
  MessageCircle,
  Book,
  Shield,
  CreditCard,
  Download,
  Upload
} from 'lucide-react'

interface FAQItem {
  id: string
  question: string
  answer: string
  category: 'general' | 'privacy' | 'billing' | 'features'
}

const faqData: FAQItem[] = [
  {
    id: '1',
    category: 'general',
    question: 'What is I Hate Subscriptions?',
    answer: 'I Hate Subscriptions is a privacy-first subscription tracking app that helps you manage all your recurring payments in one place. Unlike other subscription trackers, we charge a one-time fee instead of monthly subscriptions, and your data stays private by default.'
  },
  {
    id: '2',
    category: 'billing',
    question: 'How much does it cost?',
    answer: 'We charge a one-time payment of $29 for lifetime access. No monthly fees, no recurring charges, no hidden costs. Pay once, use forever.'
  },
  {
    id: '3',
    category: 'privacy',
    question: 'Where is my data stored?',
    answer: 'Your subscription data is stored locally in your browser by default. We don\'t track you, sell your data, or share it with third parties. Optional cloud sync will be available for users who want to access their data across devices.'
  },
  {
    id: '4',
    category: 'features',
    question: 'Can I import my existing subscriptions?',
    answer: 'Yes! You can bulk import subscriptions using a CSV file. We provide a template to make this easy. You can also manually add subscriptions one by one.'
  },
  {
    id: '5',
    category: 'features',
    question: 'Do you send renewal reminders?',
    answer: 'Email reminders will be available after purchase for users who opt-in. You can set custom reminder periods (1-30 days before renewal) and manage all notification preferences in settings.'
  },
  {
    id: '6',
    category: 'general',
    question: 'How is this different from other subscription trackers?',
    answer: 'Most subscription trackers charge monthly fees (ironic, right?). We charge once. Most track and sell your data. We don\'t. Most require accounts. We work locally first. We\'re built for people who actually hate subscriptions.'
  },
  {
    id: '7',
    category: 'features',
    question: 'Can I export my data?',
    answer: 'Absolutely! You can export all your subscription data as JSON or CSV at any time. Your data belongs to you, and you should always be able to take it with you.'
  },
  {
    id: '8',
    category: 'billing',
    question: 'Is there a money-back guarantee?',
    answer: 'Yes, we offer a 30-day money-back guarantee. If you\'re not satisfied with the app, contact us within 30 days of purchase for a full refund.'
  },
  {
    id: '9',
    category: 'features',
    question: 'Does it work on mobile?',
    answer: 'Yes! The app is fully responsive and works great on phones, tablets, and desktops. You can manage your subscriptions from any device with a web browser.'
  },
  {
    id: '10',
    category: 'privacy',
    question: 'Do you integrate with my bank account?',
    answer: 'Bank integration is optional and will be clearly marked as opt-in for privacy. We believe in giving you choice - you can manually track subscriptions or optionally connect accounts for automatic detection.'
  }
]

const categories = [
  { id: 'all', label: 'All Questions', icon: HelpCircle },
  { id: 'general', label: 'General', icon: Book },
  { id: 'privacy', label: 'Privacy & Security', icon: Shield },
  { id: 'billing', label: 'Billing & Pricing', icon: CreditCard },
  { id: 'features', label: 'Features', icon: Download }
]

export default function HelpPage() {
  const [selectedCategory, setSelectedCategory] = useState<string>('all')
  const [openItems, setOpenItems] = useState<Set<string>>(new Set())
  const [searchQuery, setSearchQuery] = useState('')

  const toggleItem = (id: string) => {
    const newOpenItems = new Set(openItems)
    if (newOpenItems.has(id)) {
      newOpenItems.delete(id)
    } else {
      newOpenItems.add(id)
    }
    setOpenItems(newOpenItems)
  }

  const filteredFAQs = faqData.filter(item => {
    const matchesCategory = selectedCategory === 'all' || item.category === selectedCategory
    const matchesSearch = searchQuery === '' || 
      item.question.toLowerCase().includes(searchQuery.toLowerCase()) ||
      item.answer.toLowerCase().includes(searchQuery.toLowerCase())
    
    return matchesCategory && matchesSearch
  })

  return (
    <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      {/* Header */}
      <div className="text-center mb-12">
        <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-4">
          Help & FAQ
        </h1>
        <p className="text-xl text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
          Find answers to common questions about I Hate Subscriptions
        </p>
      </div>

      {/* Search */}
      <div className="mb-8">
        <div className="relative">
          <HelpCircle className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
          <input
            type="text"
            placeholder="Search for help..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="input-field pl-12 text-lg py-4"
          />
        </div>
      </div>

      {/* Categories */}
      <div className="mb-8">
        <div className="flex flex-wrap gap-2">
          {categories.map((category) => {
            const Icon = category.icon
            return (
              <button
                key={category.id}
                onClick={() => setSelectedCategory(category.id)}
                className={`flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-colors ${
                  selectedCategory === category.id
                    ? 'bg-primary-600 text-white'
                    : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'
                }`}
              >
                <Icon className="h-4 w-4" />
                {category.label}
              </button>
            )
          })}
        </div>
      </div>

      {/* FAQ Items */}
      <div className="space-y-4 mb-12">
        {filteredFAQs.length === 0 ? (
          <div className="text-center py-12">
            <HelpCircle className="h-12 w-12 text-gray-400 mx-auto mb-4" />
            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
              No results found
            </h3>
            <p className="text-gray-600 dark:text-gray-400">
              Try adjusting your search terms or browse different categories
            </p>
          </div>
        ) : (
          filteredFAQs.map((item) => (
            <div key={item.id} className="card">
              <button
                onClick={() => toggleItem(item.id)}
                className="w-full flex items-center justify-between text-left"
              >
                <h3 className="text-lg font-medium text-gray-900 dark:text-white pr-4">
                  {item.question}
                </h3>
                {openItems.has(item.id) ? (
                  <ChevronUp className="h-5 w-5 text-gray-500 flex-shrink-0" />
                ) : (
                  <ChevronDown className="h-5 w-5 text-gray-500 flex-shrink-0" />
                )}
              </button>
              
              {openItems.has(item.id) && (
                <div className="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                  <p className="text-gray-600 dark:text-gray-400 leading-relaxed">
                    {item.answer}
                  </p>
                </div>
              )}
            </div>
          ))
        )}
      </div>

      {/* Quick Start Guide */}
      <div className="card mb-12">
        <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-6">
          Quick Start Guide
        </h2>
        
        <div className="grid md:grid-cols-2 gap-6">
          <div>
            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
              <Upload className="h-5 w-5 mr-2 text-primary-600 dark:text-primary-400" />
              Adding Subscriptions
            </h3>
            <ol className="space-y-2 text-gray-600 dark:text-gray-400">
              <li>1. Click "Add Subscription" from the dashboard</li>
              <li>2. Fill in the service name, price, and billing cycle</li>
              <li>3. Set the next billing date and category</li>
              <li>4. Save and start tracking!</li>
            </ol>
          </div>
          
          <div>
            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
              <Download className="h-5 w-5 mr-2 text-primary-600 dark:text-primary-400" />
              Bulk Import
            </h3>
            <ol className="space-y-2 text-gray-600 dark:text-gray-400">
              <li>1. Download the CSV template</li>
              <li>2. Fill in your subscription data</li>
              <li>3. Upload the CSV file</li>
              <li>4. Review and confirm the import</li>
            </ol>
          </div>
        </div>
      </div>

      {/* Contact Support */}
      <div className="card text-center">
        <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-4">
          Still Need Help?
        </h2>
        <p className="text-gray-600 dark:text-gray-400 mb-6">
          Can't find what you're looking for? We're here to help!
        </p>
        
        <div className="flex flex-col sm:flex-row gap-4 justify-center">
          <a
            href="mailto:support@ihatesubscriptions.com"
            className="btn-primary flex items-center gap-2"
          >
            <Mail className="h-4 w-4" />
            Email Support
          </a>
          <a
            href="#"
            className="btn-secondary flex items-center gap-2"
          >
            <MessageCircle className="h-4 w-4" />
            Live Chat (Coming Soon)
          </a>
        </div>
        
        <p className="text-sm text-gray-500 dark:text-gray-400 mt-4">
          We typically respond within 24 hours
        </p>
      </div>
    </div>
  )
}
