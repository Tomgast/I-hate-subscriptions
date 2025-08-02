import type { Metadata } from 'next'
import { Inter } from 'next/font/google'
import './globals.css'
import { ThemeProvider } from '@/components/ThemeProvider'
import { Navbar } from '@/components/Navbar'

const inter = Inter({ subsets: ['latin'] })

export const metadata: Metadata = {
  title: 'I Hate Subscriptions - Take Control of Your Recurring Payments',
  description: 'Track, manage, and cancel your subscriptions with ease. One-time payment for lifetime access. Privacy-first subscription management.',
  keywords: 'subscription tracker, recurring payments, subscription management, cancel subscriptions, budget tracker',
  authors: [{ name: 'I Hate Subscriptions' }],
  viewport: 'width=device-width, initial-scale=1',
  themeColor: [
    { media: '(prefers-color-scheme: light)', color: '#ffffff' },
    { media: '(prefers-color-scheme: dark)', color: '#111827' }
  ],
}

export default function RootLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <html lang="en" suppressHydrationWarning>
      <body className={inter.className}>
        <ThemeProvider>
          <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
            <Navbar />
            <main className="pb-16">
              {children}
            </main>
          </div>
        </ThemeProvider>
      </body>
    </html>
  )
}
