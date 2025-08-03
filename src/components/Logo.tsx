import React from 'react'

interface LogoProps {
  size?: 'sm' | 'md' | 'lg' | 'xl'
  showText?: boolean
  variant?: 'default' | 'white' | 'dark'
  className?: string
}

export function Logo({ size = 'md', showText = true, variant = 'default', className = '' }: LogoProps) {
  const sizeClasses = {
    sm: 'logo-icon-sm',
    md: 'logo-icon-md', 
    lg: 'logo-icon-lg',
    xl: 'logo-icon-xl'
  }

  const textSizeClasses = {
    sm: 'text-lg',
    md: 'text-xl',
    lg: 'text-2xl',
    xl: 'text-3xl'
  }

  const textColorClasses = {
    default: 'text-gray-900 dark:text-white',
    white: 'text-white',
    dark: 'text-gray-900'
  }

  return (
    <div className={`flex items-center space-x-3 ${className}`}>
      {/* Logo Icon */}
      <div className={`${sizeClasses[size]} relative flex items-center justify-center`}>
        {/* Green wavy background */}
        <svg 
          viewBox="0 0 40 40" 
          className="w-full h-full"
        >
          <defs>
            <linearGradient id="waveGradient" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stopColor="#10b981" />
              <stop offset="50%" stopColor="#059669" />
              <stop offset="100%" stopColor="#0d9488" />
            </linearGradient>
          </defs>
          
          {/* Main circle */}
          <circle cx="20" cy="20" r="19" fill="url(#waveGradient)" />
          
          {/* Flowing wave patterns */}
          <path 
            d="M5 15 Q12 10 20 15 Q28 20 35 15" 
            fill="none" 
            stroke="rgba(255,255,255,0.4)" 
            strokeWidth="2"
            strokeLinecap="round"
          />
          <path 
            d="M5 20 Q12 15 20 20 Q28 25 35 20" 
            fill="none" 
            stroke="rgba(255,255,255,0.6)" 
            strokeWidth="2"
            strokeLinecap="round"
          />
          <path 
            d="M5 25 Q12 20 20 25 Q28 30 35 25" 
            fill="none" 
            stroke="rgba(255,255,255,0.4)" 
            strokeWidth="2"
            strokeLinecap="round"
          />
          

        </svg>
      </div>

      {/* Logo Text */}
      {showText && (
        <div className="flex flex-col">
          <span className={`font-bold ${textSizeClasses[size]} ${textColorClasses[variant]} leading-tight`}>
            CashControl
          </span>
          <span className="text-xs text-gray-500 dark:text-gray-400 leading-tight">
            Take Control. Save Money.
          </span>
        </div>
      )}
    </div>
  )
}

export function LogoIcon({ size = 'md', variant = 'default', className = '' }: Omit<LogoProps, 'showText'>) {
  return <Logo size={size} variant={variant} showText={false} className={className} />
}
