import { NextAuthOptions } from 'next-auth'
import CredentialsProvider from 'next-auth/providers/credentials'
import GoogleProvider from 'next-auth/providers/google'
import { compare, hash } from 'bcryptjs'
import { createServerClient } from '@/lib/supabase'

// Database user interface
interface User {
  id: string
  email: string
  password?: string
  name: string
  isPaid: boolean
  createdAt: string
  trialEndsAt: string
}

export const authOptions: NextAuthOptions = {
  providers: [
    GoogleProvider({
      clientId: process.env.GOOGLE_CLIENT_ID || '',
      clientSecret: process.env.GOOGLE_CLIENT_SECRET || '',
    }),
    CredentialsProvider({
      name: 'credentials',
      credentials: {
        email: { label: 'Email', type: 'email' },
        password: { label: 'Password', type: 'password' },
        name: { label: 'Name', type: 'text', optional: true },
        isSignUp: { label: 'Is Sign Up', type: 'text', optional: true },
      },
      async authorize(credentials) {
        if (!credentials?.email || !credentials?.password) {
          return null
        }

        const isSignUp = credentials.isSignUp === 'true'

        if (isSignUp) {
          // Sign up logic
          const supabase = createServerClient()
          
          // Check if user already exists
          const { data: existingUser } = await supabase
            .from('user_profiles')
            .select('*')
            .eq('email', credentials.email)
            .single()

          if (existingUser) {
            throw new Error('User already exists')
          }

          // Create user in Supabase Auth
          const { data: authData, error: authError } = await supabase.auth.admin.createUser({
            email: credentials.email,
            password: credentials.password,
            email_confirm: true,
            user_metadata: {
              full_name: credentials.name || credentials.email.split('@')[0]
            }
          })

          if (authError || !authData.user) {
            throw new Error(authError?.message || 'Failed to create user')
          }

          // User profile will be created automatically by the database trigger
          return {
            id: authData.user.id,
            email: authData.user.email!,
            name: credentials.name || credentials.email.split('@')[0],
            isPaid: false,
          }
        } else {
          // Sign in logic
          const supabase = createServerClient()
          
          // Get user profile
          const { data: userProfile } = await supabase
            .from('user_profiles')
            .select('*')
            .eq('email', credentials.email)
            .single()

          if (!userProfile) {
            throw new Error('No user found')
          }

          // Verify password with Supabase Auth
          const { data: authData, error: authError } = await supabase.auth.signInWithPassword({
            email: credentials.email,
            password: credentials.password
          })

          if (authError || !authData.user) {
            throw new Error('Invalid credentials')
          }

          return {
            id: userProfile.id,
            email: userProfile.email,
            name: userProfile.full_name || userProfile.email.split('@')[0],
            isPaid: userProfile.has_paid || false,
          }
        }
      },
    }),
  ],
  callbacks: {
    async jwt({ token, user, account }) {
      if (user && 'isPaid' in user) {
        token.isPaid = user.isPaid
      }
      
      // Handle Google OAuth
      if (account?.provider === 'google' && user) {
        const supabase = createServerClient()
        
        // Check if user exists, if not create them
        const { data: existingUser } = await supabase
          .from('user_profiles')
          .select('*')
          .eq('email', user.email!)
          .single()

        if (!existingUser) {
          // Create user in Supabase Auth for OAuth
          const { data: authData, error: authError } = await supabase.auth.admin.createUser({
            email: user.email!,
            email_confirm: true,
            user_metadata: {
              full_name: user.name || user.email!.split('@')[0]
            }
          })

          if (!authError && authData.user) {
            token.isPaid = false
          }
        } else {
          token.isPaid = existingUser.has_paid || false
        }
      }

      return token
    },
    async session({ session, token }) {
      if (session.user) {
        session.user.id = token.sub!
        // Add custom properties to session
        ;(session.user as any).isPaid = token.isPaid as boolean
      }
      return session
    },
  },
  pages: {
    signIn: '/auth/signin',
  },
  session: {
    strategy: 'jwt',
  },
}

// Helper functions for user management with Supabase
export async function createUser(email: string, password: string, name: string): Promise<User> {
  const supabase = createServerClient()
  
  // Create user in Supabase Auth
  const { data: authData, error: authError } = await supabase.auth.admin.createUser({
    email,
    password,
    email_confirm: true,
    user_metadata: {
      full_name: name
    }
  })

  if (authError || !authData.user) {
    throw new Error(authError?.message || 'Failed to create user')
  }

  // User profile will be created automatically by the database trigger
  return {
    id: authData.user.id,
    email: authData.user.email!,
    name,
    isPaid: false,
    createdAt: new Date().toISOString(),
    trialEndsAt: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString(),
  }
}

export async function getUserByEmail(email: string): Promise<User | null> {
  const supabase = createServerClient()
  
  const { data: userProfile } = await supabase
    .from('user_profiles')
    .select('*')
    .eq('email', email)
    .single()

  if (!userProfile) return null

  return {
    id: userProfile.id,
    email: userProfile.email,
    name: userProfile.full_name || userProfile.email.split('@')[0],
    isPaid: userProfile.has_paid || false,
    createdAt: userProfile.created_at,
    trialEndsAt: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString(), // Default 7-day trial
  }
}

export async function updateUserPaymentStatus(userId: string, isPaid: boolean): Promise<void> {
  const supabase = createServerClient()
  
  await supabase
    .from('user_profiles')
    .update({ 
      has_paid: isPaid,
      payment_date: isPaid ? new Date().toISOString() : null
    })
    .eq('id', userId)
}

export function isTrialExpired(user: User): boolean {
  return new Date() > new Date(user.trialEndsAt)
}
