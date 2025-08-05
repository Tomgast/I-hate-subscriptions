// MariaDB Connection Test Script
// Tests the connection to Plesk MariaDB database and creates tables if needed

import mysql from 'mysql2/promise'
import dotenv from 'dotenv'
import path from 'path'
import { fileURLToPath } from 'url'

// Load environment variables from .env.local
const __dirname = path.dirname(fileURLToPath(import.meta.url))
dotenv.config({ path: path.resolve(__dirname, '../.env.local') })

async function testMariaDBConnection() {
  console.log('🔄 Testing MariaDB connection for CashControl...')
  console.log(`📍 Connecting to: ${process.env.DB_HOST}:${process.env.DB_PORT}`)
  console.log(`🗄️  Database: ${process.env.DB_NAME}`)
  console.log(`👤 User: ${process.env.DB_USER}`)
  
  try {
    // Test basic connection
    const isConnected = await databaseService.testConnection()
    
    if (!isConnected) {
      throw new Error('Failed to connect to MariaDB database')
    }
    
    console.log('✅ MariaDB connection successful!')
    
    // Test database version and info
    console.log('\n🔍 Checking MariaDB version and configuration...')
    const versionResult = await databaseService.query('SELECT VERSION() as version')
    console.log(`📊 MariaDB Version: ${versionResult[0]?.version}`)
    
    // Check current database
    const dbResult = await databaseService.query('SELECT DATABASE() as current_db')
    console.log(`🗄️  Current Database: ${dbResult[0]?.current_db}`)
    
    // Check character set
    const charsetResult = await databaseService.query('SHOW VARIABLES LIKE "character_set_database"')
    console.log(`🔤 Character Set: ${charsetResult[0]?.Value}`)
    
    // List existing tables
    console.log('\n📋 Checking existing tables...')
    const tables = await databaseService.query('SHOW TABLES')
    
    if (tables.length > 0) {
      console.log('✅ Found existing tables:')
      tables.forEach(table => {
        const tableName = Object.values(table)[0]
        console.log(`   - ${tableName}`)
      })
    } else {
      console.log('📝 No tables found. Will create them...')
      
      // Initialize tables
      await databaseService.initializeTables()
      console.log('✅ Database tables created successfully!')
      
      // Verify tables were created
      const newTables = await databaseService.query('SHOW TABLES')
      console.log('\n✅ Created tables:')
      newTables.forEach(table => {
        const tableName = Object.values(table)[0]
        console.log(`   - ${tableName}`)
      })
    }
    
    // Test a simple query on each table
    console.log('\n🧪 Testing table queries...')
    
    try {
      const userCount = await databaseService.query('SELECT COUNT(*) as count FROM users')
      console.log(`👥 Users table: ${userCount[0]?.count} records`)
      
      const subscriptionCount = await databaseService.query('SELECT COUNT(*) as count FROM subscriptions')
      console.log(`📋 Subscriptions table: ${subscriptionCount[0]?.count} records`)
      
      const prefsCount = await databaseService.query('SELECT COUNT(*) as count FROM user_preferences')
      console.log(`⚙️  User preferences table: ${prefsCount[0]?.count} records`)
      
      const logsCount = await databaseService.query('SELECT COUNT(*) as count FROM reminder_logs')
      console.log(`📧 Reminder logs table: ${logsCount[0]?.count} records`)
      
    } catch (queryError) {
      console.warn('⚠️  Some tables may not exist yet:', queryError.message)
    }
    
    console.log('\n🎉 MariaDB database test completed successfully!')
    console.log('✅ Your database is ready for CashControl!')
    
  } catch (error) {
    console.error('\n❌ MariaDB connection test failed:')
    console.error('Error:', error.message)
    
    if (error.code) {
      console.error('Error Code:', error.code)
    }
    
    if (error.errno) {
      console.error('Error Number:', error.errno)
    }
    
    console.error('\n🔧 Troubleshooting tips:')
    console.error('1. Check that your database credentials in .env.local are correct')
    console.error('2. Verify that the MariaDB server is running')
    console.error('3. Ensure your IP is allowed to connect to the Plesk database')
    console.error('4. Check firewall settings for port 3306')
    
    process.exit(1)
  } finally {
    // Clean up connection
    await databaseService.closePool()
    console.log('\n🔌 Database connection closed')
  }
}

// Run the test
testMariaDBConnection()
