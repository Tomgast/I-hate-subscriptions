// Debug endpoint to check specific user status in database
import { NextRequest, NextResponse } from 'next/server'
import { databaseAdapter, getDatabaseType } from '@/lib/database'

export async function GET(request: NextRequest) {
  try {
    const { searchParams } = new URL(request.url)
    const email = searchParams.get('email') || 'hyrenamw3sc2@gmail.com'
    
    console.log(`🔍 Debug: Checking user status for ${email} using ${getDatabaseType()} database`)
    
    // Check if user exists in database
    const user = await databaseAdapter.getUserByEmail(email)
    
    if (!user) {
      return NextResponse.json({
        success: false,
        message: 'User not found in database',
        email,
        databaseType: getDatabaseType(),
        user: null
      })
    }
    
    // Get user subscriptions
    const subscriptions = await databaseAdapter.getSubscriptionsByUserId(user.id)
    
    // Get user stats
    const stats = await databaseAdapter.getUserStats(user.id)
    
    return NextResponse.json({
      success: true,
      message: 'User found in database',
      email,
      databaseType: getDatabaseType(),
      user: {
        id: user.id,
        email: user.email,
        name: user.name,
        image: user.image,
        is_paid: user.is_paid,
        created_at: user.created_at,
        updated_at: user.updated_at
      },
      subscriptions: subscriptions.length,
      stats,
      rawUser: user
    })
    
  } catch (error: any) {
    console.error('❌ Debug user check failed:', error)
    
    return NextResponse.json({
      success: false,
      error: 'Debug check failed',
      details: error.message,
      stack: error.stack
    }, { status: 500 })
  }
}

export async function POST(request: NextRequest) {
  try {
    const { email, action } = await request.json()
    const targetEmail = email || 'hyrenamw3sc2@gmail.com'
    
    if (action === 'upgrade-to-pro') {
      // Manually upgrade user to Pro for testing
      const user = await databaseAdapter.getUserByEmail(targetEmail)
      
      if (!user) {
        return NextResponse.json({
          success: false,
          message: 'User not found - cannot upgrade'
        }, { status: 404 })
      }
      
      const updatedUser = await databaseAdapter.updateUser(user.id, {
        is_paid: true
      })
      
      return NextResponse.json({
        success: true,
        message: 'User manually upgraded to Pro',
        user: updatedUser
      })
    }
    
    if (action === 'reset-to-free') {
      // Reset user to free for testing
      const user = await databaseAdapter.getUserByEmail(targetEmail)
      
      if (!user) {
        return NextResponse.json({
          success: false,
          message: 'User not found - cannot reset'
        }, { status: 404 })
      }
      
      const updatedUser = await databaseAdapter.updateUser(user.id, {
        is_paid: false
      })
      
      return NextResponse.json({
        success: true,
        message: 'User reset to free account',
        user: updatedUser
      })
    }
    
    return NextResponse.json({
      success: false,
      error: 'Invalid action. Use: upgrade-to-pro or reset-to-free'
    }, { status: 400 })
    
  } catch (error: any) {
    console.error('❌ Debug user action failed:', error)
    
    return NextResponse.json({
      success: false,
      error: 'Debug action failed',
      details: error.message
    }, { status: 500 })
  }
}
