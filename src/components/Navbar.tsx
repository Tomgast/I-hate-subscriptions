'use client'

import { useState } from 'react'
import Link from 'next/link'
import { useSession, signOut } from 'next-auth/react'
import { useTheme } from './ThemeProvider'
import { Moon, Sun, Menu, X, Settings, HelpCircle, User, LogOut, LogIn } from 'lucide-react'
import { Logo } from './Logo'

export function Navbar() {
  const [isOpen, setIsOpen] = useState(false)
  const [isUserMenuOpen, setIsUserMenuOpen] = useState(false)
  const [userMenuPosition, setUserMenuPosition] = useState({ top: 0, right: 0 })
  const { data: session, status } = useSession()
  const { theme, setTheme } = useTheme()

  const toggleTheme = () => {
    setTheme(theme === 'dark' ? 'light' : 'dark')
  }

  return (
    <nav className="airbreeze-header bg-white/80 dark:bg-gray-900/80 shadow-sm border-b border-gray-200/50 dark:border-gray-700/50 backdrop-blur-sm">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between h-16">
          <div className="flex items-center">
            <Link href="/" className="flex items-center">
              <Logo size="md" showText={true} />
            </Link>
          </div>

          {/* Desktop Navigation */}
          <div className="hidden md:flex items-center space-x-8">
            {session ? (
              <>
                <Link
                  href="/dashboard"
                  className="text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 px-3 py-2 rounded-md text-sm font-medium transition-colors"
                >
                  Dashboard
                </Link>
                <Link
                  href="/settings"
                  className="text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 px-3 py-2 rounded-md text-sm font-medium transition-colors"
                >
                  <Settings className="h-5 w-5" />
                </Link>
                <Link
                  href="/help"
                  className="text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 px-3 py-2 rounded-md text-sm font-medium transition-colors"
                >
                  <HelpCircle className="h-5 w-5" />
                </Link>
                
                {/* User Profile */}
                <div className="relative flex items-center space-x-2">
                  <div className="flex items-center space-x-2 text-gray-700 dark:text-gray-300 px-3 py-2 rounded-md text-sm font-medium">
                    <User className="h-5 w-5" />
                    <span className="truncate max-w-[120px]">{session.user?.name || session.user?.email}</span>
                  </div>
                  
                  {/* Direct Logout Button */}
                  <button
                    onClick={() => signOut()}
                    className="flex items-center space-x-1 text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 rounded-md text-sm font-medium transition-colors"
                    title="Sign Out"
                  >
                    <LogOut className="h-5 w-5" />
                    <span className="sr-only">Sign Out</span>
                  </button>
                </div>
              </>
            ) : (
              <>
                <Link
                  href="/demo"
                  className="text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 px-3 py-2 rounded-md text-sm font-medium transition-colors"
                >
                  Demo
                </Link>
                <Link
                  href="/help"
                  className="text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 px-3 py-2 rounded-md text-sm font-medium transition-colors"
                >
                  <HelpCircle className="h-5 w-5" />
                </Link>
                <Link
                  href="/auth/signin"
                  className="flex items-center space-x-1 text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 px-3 py-2 rounded-md text-sm font-medium transition-colors"
                >
                  <LogIn className="h-4 w-4" />
                  <span>Sign In</span>
                </Link>
                <Link
                  href="/pricing"
                  className="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                >
                  Get Started
                </Link>
              </>
            )}
            
            <button
              onClick={toggleTheme}
              className="p-2 rounded-md text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            >
              {theme === 'dark' ? <Sun className="h-5 w-5" /> : <Moon className="h-5 w-5" />}
            </button>
          </div>

          {/* Mobile menu button */}
          <div className="md:hidden flex items-center space-x-2">
            <button
              onClick={toggleTheme}
              className="p-2 rounded-md text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            >
              {theme === 'dark' ? <Sun className="h-5 w-5" /> : <Moon className="h-5 w-5" />}
            </button>
            <button
              onClick={() => setIsOpen(!isOpen)}
              className="p-2 rounded-md text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            >
              {isOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
            </button>
          </div>
        </div>
      </div>

      {/* Mobile Navigation */}
      {isOpen && (
        <div className="md:hidden">
          <div className="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
            {session ? (
              <>
                <div className="px-3 py-2 text-sm text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 mb-2">
                  {session.user?.name || session.user?.email}
                </div>
                <Link
                  href="/dashboard"
                  className="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                  onClick={() => setIsOpen(false)}
                >
                  Dashboard
                </Link>
                <Link
                  href="/settings"
                  className="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                  onClick={() => setIsOpen(false)}
                >
                  Settings
                </Link>
                <Link
                  href="/help"
                  className="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                  onClick={() => setIsOpen(false)}
                >
                  Help & FAQ
                </Link>
                <button
                  onClick={() => {
                    setIsOpen(false)
                    signOut()
                  }}
                  className="block w-full text-left px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                >
                  Sign Out
                </button>
              </>
            ) : (
              <>
                <Link
                  href="/demo"
                  className="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                  onClick={() => setIsOpen(false)}
                >
                  Demo
                </Link>
                <Link
                  href="/help"
                  className="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                  onClick={() => setIsOpen(false)}
                >
                  Help & FAQ
                </Link>
                <Link
                  href="/auth/signin"
                  className="block px-3 py-2 rounded-md text-base font-medium text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                  onClick={() => setIsOpen(false)}
                >
                  Sign In
                </Link>
                <Link
                  href="/pricing"
                  className="block px-3 py-2 rounded-md text-base font-medium bg-primary-600 text-white hover:bg-primary-700 transition-colors"
                  onClick={() => setIsOpen(false)}
                >
                  Get Started
                </Link>
              </>
            )}
          </div>
        </div>
      )}
    </nav>
  )
}
