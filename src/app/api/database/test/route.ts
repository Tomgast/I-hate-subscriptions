// API endpoint to test Plesk MySQL database connection
import { NextRequest, NextResponse } from 'next/server'
import { databaseService } from '@/lib/database/config'

export async function GET(request: NextRequest) {
  try {
    console.log('🔍 Testing database connection...')
    
    // Test basic connection
    const connectionTest = await databaseService.testConnection()
    if (!connectionTest) {
      return NextResponse.json({
        success: false,
        error: 'Database connection failed'
      }, { status: 500 })
    }

    // Test a simple query
    const testQuery = await databaseService.query('SELECT 1 as test_value, NOW() as time_now')
    
    // Get database info
    const dbInfo = await databaseService.query('SELECT DATABASE() as db_name, VERSION() as db_version')

    // Check if tables exist
    const tables = await databaseService.query(`
      SELECT TABLE_NAME 
      FROM INFORMATION_SCHEMA.TABLES 
      WHERE TABLE_SCHEMA = DATABASE()
    `)

    return NextResponse.json({
      success: true,
      message: 'Database connection successful',
      data: {
        connectionTest: true,
        testQuery: testQuery[0],
        databaseInfo: dbInfo[0],
        existingTables: tables.map((table: any) => table.TABLE_NAME)
      }
    })

  } catch (error: any) {
    console.error('❌ Database test failed:', error)
    
    return NextResponse.json({
      success: false,
      error: 'Database test failed',
      details: error.message
    }, { status: 500 })
  }
}

export async function POST(request: NextRequest) {
  try {
    console.log('🔧 Initializing database tables...')
    
    await databaseService.initializeTables()
    
    // Verify tables were created
    const tables = await databaseService.query(`
      SELECT TABLE_NAME, TABLE_ROWS, CREATE_TIME
      FROM INFORMATION_SCHEMA.TABLES 
      WHERE TABLE_SCHEMA = DATABASE()
      ORDER BY TABLE_NAME
    `)

    return NextResponse.json({
      success: true,
      message: 'Database tables initialized successfully',
      tables: tables
    })

  } catch (error: any) {
    console.error('❌ Database initialization failed:', error)
    
    return NextResponse.json({
      success: false,
      error: 'Database initialization failed',
      details: error.message
    }, { status: 500 })
  }
}
