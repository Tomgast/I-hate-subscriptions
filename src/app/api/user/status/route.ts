import { NextRequest, NextResponse } from 'next/server'
import { getServerSession } from 'next-auth'
import { authOptions } from '@/lib/auth'
import { createServerClient } from '@/lib/supabase'

export async function GET(request: NextRequest) {
  try {
    const session = await getServerSession(authOptions)
    
    if (!session?.user?.email) {
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })
    }

    const userEmail = session.user.email
    
    // Get the latest user status from the database
    const supabase = createServerClient()
    const { data: userProfile, error } = await supabase
      .from('user_profiles')
      .select('has_paid, payment_date')
      .eq('email', userEmail)
      .single()
    
    if (error || !userProfile) {
      console.error('Error fetching user profile:', error)
      return NextResponse.json({ 
        isPaid: false,
        error: 'Failed to fetch user status'
      })
    }

    return NextResponse.json({
      isPaid: userProfile.has_paid || false,
      paymentDate: userProfile.payment_date,
      email: userEmail
    })
  } catch (error) {
    console.error('Get user status error:', error)
    return NextResponse.json({ 
      isPaid: false,
      error: 'Internal server error' 
    }, { status: 500 })
  }
}
