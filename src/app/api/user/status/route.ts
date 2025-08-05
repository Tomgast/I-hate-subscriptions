import { NextRequest, NextResponse } from 'next/server'
import { getServerSession } from 'next-auth'
import { authOptions } from '@/lib/auth'
import { databaseAdapter, getDatabaseType } from '@/lib/database'

export async function GET(request: NextRequest) {
  try {
    const session = await getServerSession(authOptions)
    
    if (!session?.user?.email) {
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })
    }

    const userEmail = session.user.email
    
    // Get the latest user status from the database (local or production)
    console.log(`🔍 Checking user status using ${getDatabaseType()} database for:`, userEmail)
    const userProfile = await databaseAdapter.getUserByEmail(userEmail)
    
    if (!userProfile) {
      console.error('User profile not found for email:', userEmail)
      return NextResponse.json({ 
        isPaid: false,
        error: 'User profile not found'
      })
    }

    return NextResponse.json({
      isPaid: userProfile.is_paid || false,
      paymentDate: userProfile.updated_at,
      email: userEmail,
      userId: userProfile.id,
      name: userProfile.name
    })
  } catch (error) {
    console.error('Get user status error:', error)
    return NextResponse.json({ 
      isPaid: false,
      error: 'Internal server error' 
    }, { status: 500 })
  }
}
