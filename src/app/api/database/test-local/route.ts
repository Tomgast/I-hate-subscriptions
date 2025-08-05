// API endpoint to test local database functionality and Pro upgrade flow
import { NextRequest, NextResponse } from 'next/server'
import { databaseAdapter, getDatabaseType, getDatabaseStatus } from '@/lib/database'

export async function GET(request: NextRequest) {
  try {
    console.log('🧪 Testing local database functionality...')
    
    // Get database status
    const dbStatus = await getDatabaseStatus()
    
    // Test creating a user
    const testEmail = 'test-pro@example.com'
    
    // Check if test user already exists
    let testUser = await databaseAdapter.getUserByEmail(testEmail)
    
    if (!testUser) {
      // Create test user
      testUser = await databaseAdapter.createUser({
        email: testEmail,
        name: 'Test Pro User',
        is_paid: false
      })
      console.log('✅ Test user created:', testUser.id)
    }

    // Test upgrading user to Pro
    const upgradedUser = await databaseAdapter.updateUser(testUser.id, {
      is_paid: true
    })

    // Test retrieving user by email to verify Pro status
    const retrievedUser = await databaseAdapter.getUserByEmail(testEmail)

    // Test creating a subscription for the Pro user
    const testSubscription = await databaseAdapter.createSubscription({
      user_id: testUser.id,
      name: 'Netflix',
      amount: 15.99,
      currency: 'EUR',
      billing_cycle: 'monthly',
      status: 'active',
      category: 'Entertainment',
      next_billing_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
    })

    // Get user subscriptions
    const userSubscriptions = await databaseAdapter.getSubscriptionsByUserId(testUser.id)

    // Get user stats
    const userStats = await databaseAdapter.getUserStats(testUser.id)

    return NextResponse.json({
      success: true,
      message: 'Local database test completed successfully',
      databaseType: getDatabaseType(),
      databaseStatus: dbStatus,
      testResults: {
        userCreated: !!testUser,
        userUpgraded: upgradedUser?.is_paid === true,
        userRetrieved: retrievedUser?.is_paid === true,
        subscriptionCreated: !!testSubscription,
        subscriptionsCount: userSubscriptions.length,
        userStats
      },
      testData: {
        user: retrievedUser,
        subscriptions: userSubscriptions,
        stats: userStats
      }
    })

  } catch (error: any) {
    console.error('❌ Local database test failed:', error)
    
    return NextResponse.json({
      success: false,
      error: 'Local database test failed',
      databaseType: getDatabaseType(),
      details: error.message,
      stack: error.stack
    }, { status: 500 })
  }
}

export async function POST(request: NextRequest) {
  try {
    const { action, email } = await request.json()
    
    if (action === 'cleanup') {
      // Clean up test data
      const testEmail = email || 'test-pro@example.com'
      const testUser = await databaseAdapter.getUserByEmail(testEmail)
      
      if (testUser) {
        // Delete user subscriptions first
        const subscriptions = await databaseAdapter.getSubscriptionsByUserId(testUser.id)
        for (const subscription of subscriptions) {
          await databaseAdapter.deleteSubscription(subscription.id)
        }
        
        // Delete user
        await databaseAdapter.deleteUser(testUser.id)
        
        return NextResponse.json({
          success: true,
          message: 'Test data cleaned up successfully',
          deletedUser: testUser.email,
          deletedSubscriptions: subscriptions.length
        })
      } else {
        return NextResponse.json({
          success: true,
          message: 'No test data found to clean up'
        })
      }
    }

    if (action === 'reset-pro-status') {
      // Reset Pro status for testing
      const testEmail = email || 'test-pro@example.com'
      const testUser = await databaseAdapter.getUserByEmail(testEmail)
      
      if (testUser) {
        const updatedUser = await databaseAdapter.updateUser(testUser.id, {
          is_paid: false
        })
        
        return NextResponse.json({
          success: true,
          message: 'Pro status reset to free',
          user: updatedUser
        })
      } else {
        return NextResponse.json({
          success: false,
          message: 'Test user not found'
        }, { status: 404 })
      }
    }

    return NextResponse.json({
      success: false,
      error: 'Invalid action'
    }, { status: 400 })

  } catch (error: any) {
    console.error('❌ Local database POST operation failed:', error)
    
    return NextResponse.json({
      success: false,
      error: 'Database operation failed',
      details: error.message
    }, { status: 500 })
  }
}
