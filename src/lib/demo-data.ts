// Demo data for CashControl - Complete dummy subscription data
export const demoSubscriptions = [
  {
    id: 'demo-1',
    name: 'Netflix',
    description: 'Streaming service for movies and TV shows',
    cost: 15.99,
    currency: 'USD',
    billing_cycle: 'monthly' as const,
    next_billing_date: '2024-02-15',
    status: 'active' as const,
    category: 'Entertainment',
    website_url: 'https://netflix.com',
    logo_url: 'https://logo.clearbit.com/netflix.com',
    notes: 'Family plan with 4K streaming',
    reminder_days: 3,
    created_at: '2024-01-15T10:00:00Z',
    updated_at: '2024-01-15T10:00:00Z'
  },
  {
    id: 'demo-2',
    name: 'Spotify Premium',
    description: 'Music streaming service',
    cost: 9.99,
    currency: 'USD',
    billing_cycle: 'monthly' as const,
    next_billing_date: '2024-02-10',
    status: 'active' as const,
    category: 'Entertainment',
    website_url: 'https://spotify.com',
    logo_url: 'https://logo.clearbit.com/spotify.com',
    notes: 'Individual premium plan',
    reminder_days: 5,
    created_at: '2024-01-10T14:30:00Z',
    updated_at: '2024-01-10T14:30:00Z'
  },
  {
    id: 'demo-3',
    name: 'Adobe Creative Cloud',
    description: 'Design and creative software suite',
    cost: 52.99,
    currency: 'USD',
    billing_cycle: 'monthly' as const,
    next_billing_date: '2024-02-20',
    status: 'active' as const,
    category: 'Software',
    website_url: 'https://adobe.com',
    logo_url: 'https://logo.clearbit.com/adobe.com',
    notes: 'All apps plan for professional use',
    reminder_days: 7,
    created_at: '2024-01-20T09:15:00Z',
    updated_at: '2024-01-20T09:15:00Z'
  },
  {
    id: 'demo-4',
    name: 'GitHub Pro',
    description: 'Code hosting and collaboration platform',
    cost: 4.00,
    currency: 'USD',
    billing_cycle: 'monthly' as const,
    next_billing_date: '2024-02-05',
    status: 'active' as const,
    category: 'Software',
    website_url: 'https://github.com',
    logo_url: 'https://logo.clearbit.com/github.com',
    notes: 'Pro plan for private repositories',
    reminder_days: 3,
    created_at: '2024-01-05T16:45:00Z',
    updated_at: '2024-01-05T16:45:00Z'
  },
  {
    id: 'demo-5',
    name: 'Gym Membership',
    description: 'Local fitness center membership',
    cost: 29.99,
    currency: 'USD',
    billing_cycle: 'monthly' as const,
    next_billing_date: '2024-02-01',
    status: 'active' as const,
    category: 'Health & Fitness',
    website_url: '',
    logo_url: '',
    notes: 'Basic membership with access to all equipment',
    reminder_days: 5,
    created_at: '2024-01-01T08:00:00Z',
    updated_at: '2024-01-01T08:00:00Z'
  },
  {
    id: 'demo-6',
    name: 'Amazon Prime',
    description: 'Shopping and streaming benefits',
    cost: 139.00,
    currency: 'USD',
    billing_cycle: 'yearly' as const,
    next_billing_date: '2024-12-15',
    status: 'active' as const,
    category: 'Shopping',
    website_url: 'https://amazon.com',
    logo_url: 'https://logo.clearbit.com/amazon.com',
    notes: 'Annual subscription with free shipping',
    reminder_days: 14,
    created_at: '2023-12-15T12:00:00Z',
    updated_at: '2023-12-15T12:00:00Z'
  },
  {
    id: 'demo-7',
    name: 'Notion Pro',
    description: 'Productivity and note-taking app',
    cost: 8.00,
    currency: 'USD',
    billing_cycle: 'monthly' as const,
    next_billing_date: '2024-02-12',
    status: 'active' as const,
    category: 'Productivity',
    website_url: 'https://notion.so',
    logo_url: 'https://logo.clearbit.com/notion.so',
    notes: 'Pro plan for unlimited blocks',
    reminder_days: 3,
    created_at: '2024-01-12T11:20:00Z',
    updated_at: '2024-01-12T11:20:00Z'
  },
  {
    id: 'demo-8',
    name: 'Dropbox Plus',
    description: 'Cloud storage service',
    cost: 9.99,
    currency: 'USD',
    billing_cycle: 'monthly' as const,
    next_billing_date: '2024-02-08',
    status: 'paused' as const,
    category: 'Storage',
    website_url: 'https://dropbox.com',
    logo_url: 'https://logo.clearbit.com/dropbox.com',
    notes: 'Paused due to low usage',
    reminder_days: 3,
    created_at: '2024-01-08T13:45:00Z',
    updated_at: '2024-01-25T10:30:00Z'
  },
  {
    id: 'demo-9',
    name: 'Disney+',
    description: 'Disney streaming service',
    cost: 7.99,
    currency: 'USD',
    billing_cycle: 'monthly' as const,
    next_billing_date: '2024-02-18',
    status: 'cancelled' as const,
    category: 'Entertainment',
    website_url: 'https://disneyplus.com',
    logo_url: 'https://logo.clearbit.com/disneyplus.com',
    notes: 'Cancelled after watching The Mandalorian',
    reminder_days: 3,
    created_at: '2024-01-18T15:00:00Z',
    updated_at: '2024-01-28T09:15:00Z'
  },
  {
    id: 'demo-10',
    name: 'Figma Professional',
    description: 'Design collaboration platform',
    cost: 12.00,
    currency: 'USD',
    billing_cycle: 'monthly' as const,
    next_billing_date: '2024-02-22',
    status: 'active' as const,
    category: 'Software',
    website_url: 'https://figma.com',
    logo_url: 'https://logo.clearbit.com/figma.com',
    notes: 'Professional plan for team collaboration',
    reminder_days: 5,
    created_at: '2024-01-22T14:10:00Z',
    updated_at: '2024-01-22T14:10:00Z'
  }
]

// Demo user profile
export const demoUserProfile = {
  id: 'demo-user-id',
  email: 'demo@cashcontrol.com',
  full_name: 'Demo User',
  user_tier: 'pro' as const,
  has_paid: true,
  payment_date: '2024-01-01T00:00:00Z',
  subscription_limit: -1, // Unlimited for pro
  created_at: '2024-01-01T00:00:00Z',
  updated_at: '2024-01-01T00:00:00Z'
}

// Calculate demo statistics
export const calculateDemoStats = () => {
  const activeSubscriptions = demoSubscriptions.filter(sub => sub.status === 'active')
  const monthlyTotal = activeSubscriptions
    .filter(sub => sub.billing_cycle === 'monthly')
    .reduce((sum, sub) => sum + sub.cost, 0)
  
  const yearlyTotal = activeSubscriptions
    .filter(sub => sub.billing_cycle === 'yearly')
    .reduce((sum, sub) => sum + sub.cost, 0)
  
  const totalMonthly = monthlyTotal + (yearlyTotal / 12)
  const totalYearly = totalMonthly * 12
  
  return {
    totalMonthly,
    totalYearly,
    activeCount: activeSubscriptions.length,
    dueSoon: activeSubscriptions.filter(sub => {
      const dueDate = new Date(sub.next_billing_date)
      const daysDiff = Math.ceil((dueDate.getTime() - Date.now()) / (1000 * 60 * 60 * 24))
      return daysDiff <= 7 && daysDiff >= 0
    }).length,
    categoryBreakdown: activeSubscriptions.reduce((acc, sub) => {
      acc[sub.category] = (acc[sub.category] || 0) + 1
      return acc
    }, {} as Record<string, number>),
    monthlyTrend: [],
    upcomingRenewals: activeSubscriptions.filter(sub => {
      const renewalDate = new Date(sub.next_billing_date)
      const today = new Date()
      const daysUntilRenewal = Math.ceil((renewalDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24))
      return daysUntilRenewal <= 7
    })
  }
}
