// Admin API endpoint to create user account in production MariaDB database
import { NextRequest, NextResponse } from 'next/server'
import { databaseAdapter, getDatabaseType } from '@/lib/database'
import { databaseService } from '@/lib/database/config'

export async function POST(request: NextRequest) {
  try {
    const { email, name, password } = await request.json()
    
    if (!email) {
      return NextResponse.json({
        success: false,
        error: 'Email is required'
      }, { status: 400 })
    }
    
    console.log(`🔧 Creating user: ${email} in ${getDatabaseType()} database`)
    
    // First, ensure database tables exist
    await databaseService.initializeTables()
    
    // Check if user already exists
    const existingUser = await databaseAdapter.getUserByEmail(email)
    if (existingUser) {
      return NextResponse.json({
        success: false,
        error: 'User already exists',
        user: {
          id: existingUser.id,
          email: existingUser.email,
          name: existingUser.name,
          isPaid: existingUser.is_paid,
          createdAt: existingUser.created_at
        }
      }, { status: 409 })
    }
    
    // Create the user in production database
    const newUser = await databaseAdapter.createUser({
      email,
      name: name || email.split('@')[0],
      password: password || null, // Allow users without passwords (OAuth users)
      is_paid: false
    })
    
    // Create default user preferences
    await databaseAdapter.createUserPreferences(newUser.id, {})
    
    console.log(`✅ User created successfully:`, newUser.email)
    
    return NextResponse.json({
      success: true,
      message: 'User created successfully in production database',
      user: {
        id: newUser.id,
        email: newUser.email,
        name: newUser.name,
        isPaid: newUser.is_paid,
        createdAt: newUser.created_at
      },
      databaseType: getDatabaseType()
    })
    
  } catch (error: any) {
    console.error('❌ Error creating user:', error)
    
    return NextResponse.json({
      success: false,
      error: 'Failed to create user',
      details: error.message,
      databaseType: getDatabaseType()
    }, { status: 500 })
  }
}

export async function GET(request: NextRequest) {
  try {
    const { searchParams } = new URL(request.url)
    const email = searchParams.get('email')
    
    if (!email) {
      return NextResponse.json({
        success: false,
        error: 'Email parameter is required'
      }, { status: 400 })
    }
    
    console.log(`🔍 Checking user: ${email} in ${getDatabaseType()} database`)
    
    // Check if user exists
    const user = await databaseAdapter.getUserByEmail(email)
    
    if (user) {
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
      return NextResponse.json({
        success: true,
        userExists: false,
        message: `User ${email} not found in ${getDatabaseType()} database`,
        databaseType: getDatabaseType()
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
