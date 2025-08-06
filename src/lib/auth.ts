import { NextAuthOptions } from 'next-auth'
import CredentialsProvider from 'next-auth/providers/credentials'
import GoogleProvider from 'next-auth/providers/google'
import { compare, hash } from 'bcryptjs'
import { databaseAdapter, getDatabaseType } from '@/lib/database'

// Database user interface
interface User {
  id: string
  email: string
  password?: string
  name: string
  isPaid: boolean
  createdAt: string
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
          // Sign up logic with MySQL database
          
          // Check if user already exists
          console.log(`🔐 Auth: Using ${getDatabaseType()} database for signup`)
          const existingUser = await databaseAdapter.getUserByEmail(credentials.email)
          if (existingUser) {
            throw new Error('User already exists')
          }

          // Hash password
          const hashedPassword = await hash(credentials.password, 12)
          
          // Create user in database (local or production)
          const newUser = await databaseAdapter.createUser({
            email: credentials.email,
            name: credentials.name || credentials.email.split('@')[0],
            is_paid: false
          })

          // Create default user preferences
          await databaseAdapter.createUserPreferences(newUser.id, {})

          return {
            id: newUser.id,
            email: newUser.email,
            name: newUser.name || credentials.email.split('@')[0],
            isPaid: false,
          }
        } else {
          // Sign in logic with database (local or production)
          
          console.log(`🔐 Auth: Attempting login for ${credentials.email} using ${getDatabaseType()} database`)
          
          // Get user profile
          const userProfile = await databaseAdapter.getUserByEmail(credentials.email)
          if (!userProfile) {
            console.log(`❌ Auth: User not found: ${credentials.email}`)
            throw new Error('Invalid email or password')
          }

          console.log(`✅ Auth: User found: ${userProfile.email}`)

          // For users migrated from OAuth or created without password, allow login without password verification
          // This is a temporary solution for the migration period
          if (!userProfile.password) {
            console.log(`🔓 Auth: User ${credentials.email} has no password set (OAuth user), allowing login`)
            
            return {
              id: userProfile.id,
              email: userProfile.email,
              name: userProfile.name || userProfile.email.split('@')[0],
              isPaid: userProfile.is_paid || false,
            }
          }

          // Verify password for users with stored passwords
          const isPasswordValid = await compare(credentials.password, userProfile.password)
          if (!isPasswordValid) {
            console.log(`❌ Auth: Invalid password for ${credentials.email}`)
            throw new Error('Invalid email or password')
          }

          console.log(`✅ Auth: Password verified for ${credentials.email}`)

          return {
            id: userProfile.id,
            email: userProfile.email,
            name: userProfile.name || userProfile.email.split('@')[0],
            isPaid: userProfile.is_paid || false,
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
      
      // Handle Google OAuth with database (local or production)
      if (account?.provider === 'google' && user) {
        console.log(`🔐 Google OAuth: Using ${getDatabaseType()} database for:`, user.email)
        // Check if user exists, if not create them
        const existingUser = await databaseAdapter.getUserByEmail(user.email!)

        if (!existingUser) {
          // Create user in database for OAuth
          const newUser = await databaseAdapter.createUser({
            email: user.email!,
            name: user.name || user.email!.split('@')[0],
            image: user.image || undefined,
            is_paid: false
          })

          // Create default user preferences
          await databaseAdapter.createUserPreferences(newUser.id, {})
          
          token.isPaid = false
        } else {
          token.isPaid = existingUser.is_paid || false
        }
      }

      return token
    },
    async session({ session, token }) {
      if (session.user) {
        // Add custom properties to session
        ;(session.user as any).id = token.sub!
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

// Helper functions for user management with database (local or production)
export async function createUser(email: string, password: string, name: string): Promise<User> {
  // Hash password
  const hashedPassword = await hash(password, 12)
  
  // Create user in database
  const newUser = await databaseAdapter.createUser({
    email,
    name,
    is_paid: false
  })

  // Create default user preferences
  await databaseAdapter.createUserPreferences(newUser.id, {})

  return {
    id: newUser.id,
    email: newUser.email,
    name: newUser.name || name,
    isPaid: false,
    createdAt: newUser.created_at,
  }
}

export async function getUserByEmail(email: string): Promise<User | null> {
  const userProfile = await databaseAdapter.getUserByEmail(email)
  
  if (!userProfile) return null

  return {
    id: userProfile.id,
    email: userProfile.email,
    name: userProfile.name || userProfile.email.split('@')[0],
    isPaid: userProfile.is_paid || false,
    createdAt: userProfile.created_at,
  }
}

export async function updateUserPaymentStatus(userId: string, isPaid: boolean): Promise<void> {
  await databaseAdapter.updateUser(userId, {
    is_paid: isPaid
  })
}

export function isPremiumUser(user: User): boolean {
  return user.isPaid
}

export function canAccessPremiumFeatures(user: User): boolean {
  return user.isPaid
}
