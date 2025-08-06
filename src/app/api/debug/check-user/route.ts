// Debug API endpoint to check if a specific user exists and troubleshoot login issues
import { NextRequest, NextResponse } from 'next/server'
import { databaseAdapter, getDatabaseType } from '@/lib/database'
import { databaseService } from '@/lib/database/config'

export async function GET(request: NextRequest) {
  try {
    const { searchParams } = new URL(request.url)
    const email = searchParams.get('email') || 'support@origens.nl'
    
    console.log(`🔍 Checking user: ${email} in ${getDatabaseType()} database`)
    
    // Check if user exists
    const user = await databaseAdapter.getUserByEmail(email)
    
    if (user) {
      console.log(`✅ User found:`, user)
      return NextResponse.json({
        success: true,
        userExists: true,
        user: {
          id: user.id,
          email: user.email,
          name: user.name,
          isPaid: user.is_paid,
          createdAt: user.created_at
        },
        databaseType: getDatabaseType()
      })
    } else {
      console.log(`❌ User not found: ${email}`)
      
      // Get all users to see what's in the database
      const allUsers = await databaseService.query('SELECT id, email, name, is_paid, created_at FROM users LIMIT 10')
      
      return NextResponse.json({
        success: true,
        userExists: false,
        message: `User ${email} not found`,
        databaseType: getDatabaseType(),
        sampleUsers: allUsers
      })
    }
    
  } catch (error: any) {
    console.error('❌ Error checking user:', error)
    
    return NextResponse.json({
      success: false,
      error: 'Failed to check user',
      details: error.message,
      databaseType: getDatabaseType()
    }, { status: 500 })
  }
}

export async function POST(request: NextRequest) {
  try {
    const { email, name } = await request.json()
    
    if (!email) {
      return NextResponse.json({
        success: false,
        error: 'Email is required'
      }, { status: 400 })
    }
    
    console.log(`🔧 Creating user: ${email}`)
    
    // Check if user already exists
    const existingUser = await databaseAdapter.getUserByEmail(email)
    if (existingUser) {
      return NextResponse.json({
        success: false,
        error: 'User already exists',
        user: existingUser
      }, { status: 409 })
    }
    
    // Create the user
    const newUser = await databaseAdapter.createUser({
      email,
      name: name || email.split('@')[0],
      is_paid: false
    })
    
    // Create default user preferences
    await databaseAdapter.createUserPreferences(newUser.id, {})
    
    console.log(`✅ User created:`, newUser)
    
    return NextResponse.json({
      success: true,
      message: 'User created successfully',
      user: newUser,
      databaseType: getDatabaseType()
    })
    
  } catch (error: any) {
    console.error('❌ Error creating user:', error)
    
    return NextResponse.json({
      success: false,
      error: 'Failed to create user',
      details: error.message
    }, { status: 500 })
  }
}
