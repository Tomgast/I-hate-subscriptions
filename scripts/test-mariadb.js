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
  
  let connection = null
  
  try {
    // Create connection
    connection = await mysql.createConnection({
      host: process.env.DB_HOST,
      port: parseInt(process.env.DB_PORT || '3306'),
      database: process.env.DB_NAME,
      user: process.env.DB_USER,
      password: process.env.DB_PASSWORD,
      charset: 'utf8mb4',
      supportBigNumbers: true,
      bigNumberStrings: true,
      dateStrings: false
    })
    
    console.log('✅ MariaDB connection successful!')
    
    // Test database version and info
    console.log('\n🔍 Checking MariaDB version and configuration...')
    const [versionResult] = await connection.execute('SELECT VERSION() as version')
    console.log(`📊 MariaDB Version: ${versionResult[0]?.version}`)
    
    // Check current database
    const [dbResult] = await connection.execute('SELECT DATABASE() as current_db')
    console.log(`🗄️  Current Database: ${dbResult[0]?.current_db}`)
    
    // Check character set
    const [charsetResult] = await connection.execute('SHOW VARIABLES LIKE "character_set_database"')
    console.log(`🔤 Character Set: ${charsetResult[0]?.Value}`)
    
    // List existing tables
    console.log('\n📋 Checking existing tables...')
    const [tables] = await connection.execute('SHOW TABLES')
    
    if (tables.length > 0) {
      console.log('✅ Found existing tables:')
      tables.forEach(table => {
        const tableName = Object.values(table)[0]
        console.log(`   - ${tableName}`)
      })
      
      // Test a simple query on each table if they exist
      console.log('\n🧪 Testing table queries...')
      
      const tableNames = tables.map(table => Object.values(table)[0])
      
      if (tableNames.includes('users')) {
        const [userCount] = await connection.execute('SELECT COUNT(*) as count FROM users')
        console.log(`👥 Users table: ${userCount[0]?.count} records`)
      }
      
      if (tableNames.includes('subscriptions')) {
        const [subscriptionCount] = await connection.execute('SELECT COUNT(*) as count FROM subscriptions')
        console.log(`📋 Subscriptions table: ${subscriptionCount[0]?.count} records`)
      }
      
      if (tableNames.includes('user_preferences')) {
        const [prefsCount] = await connection.execute('SELECT COUNT(*) as count FROM user_preferences')
        console.log(`⚙️  User preferences table: ${prefsCount[0]?.count} records`)
      }
      
      if (tableNames.includes('reminder_logs')) {
        const [logsCount] = await connection.execute('SELECT COUNT(*) as count FROM reminder_logs')
        console.log(`📧 Reminder logs table: ${logsCount[0]?.count} records`)
      }
      
    } else {
      console.log('📝 No tables found in database.')
      console.log('💡 Tables will be created automatically when the application starts.')
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
    if (connection) {
      await connection.end()
      console.log('\n🔌 Database connection closed')
    }
  }
}

// Run the test
testMariaDBConnection()
