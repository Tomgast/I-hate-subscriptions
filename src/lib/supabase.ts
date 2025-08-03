import { createClient as createSupabaseClient } from '@supabase/supabase-js'

// Client-side Supabase client
export const createClient = () => {
  return createSupabaseClient(
    process.env.NEXT_PUBLIC_SUPABASE_URL!,
    process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY!
  )
}

// Server-side Supabase client (for API routes)
export const createServerClient = () => {
  return createSupabaseClient(
    process.env.NEXT_PUBLIC_SUPABASE_URL!,
    process.env.SUPABASE_SERVICE_ROLE_KEY!,
    {
      auth: {
        autoRefreshToken: false,
        persistSession: false
      }
    }
  )
}

// Database types (you'll generate these later)
export type Database = {
  public: {
    Tables: {
      subscriptions: {
        Row: {
          id: string
          user_id: string
          name: string
          price: number
          currency: string
          billing_cycle: string
          next_billing_date: string
          category: string
          description: string | null
          website: string | null
          reminder_days: number
          is_active: boolean
          created_at: string
          updated_at: string
        }
        Insert: {
          id?: string
          user_id: string
          name: string
          price: number
          currency?: string
          billing_cycle: string
          next_billing_date: string
          category: string
          description?: string | null
          website?: string | null
          reminder_days?: number
          is_active?: boolean
          created_at?: string
          updated_at?: string
        }
        Update: {
          id?: string
          user_id?: string
          name?: string
          price?: number
          currency?: string
          billing_cycle?: string
          next_billing_date?: string
          category?: string
          description?: string | null
          website?: string | null
          reminder_days?: number
          is_active?: boolean
          created_at?: string
          updated_at?: string
        }
      }
      user_profiles: {
        Row: {
          id: string
          email: string | null
          name: string | null
          is_paid: boolean
          payment_date: string | null
          created_at: string
        }
        Insert: {
          id: string
          email?: string | null
          name?: string | null
          is_paid?: boolean
          payment_date?: string | null
          created_at?: string
        }
        Update: {
          id?: string
          email?: string | null
          name?: string | null
          is_paid?: boolean
          payment_date?: string | null
          created_at?: string
        }
      }
    }
  }
}
