// Script to create user account in production MariaDB database
const { databaseService } = require('./src/lib/database/config.ts')
const { databaseAdapter } = require('./src/lib/database/index.ts')

async function createUser() {
  try {
    console.log('🔧 Creating user account in production MariaDB database...')
    
    // Initialize database tables first
    await databaseService.initializeTables()
    console.log('✅ Database tables initialized')
    
    // Check if user already exists
    const existingUser = await databaseAdapter.getUserByEmail('support@origens.nl')
    if (existingUser) {
      console.log('✅ User already exists:', existingUser.email)
      console.log('User details:', {
        id: existingUser.id,
        email: existingUser.email,
        name: existingUser.name,
        isPaid: existingUser.is_paid,
        createdAt: existingUser.created_at
      })
      return
    }
    
    // Create the user
    const newUser = await databaseAdapter.createUser({
      email: 'support@origens.nl',
      name: 'Support Origens',
      password: null, // No password - will allow login without password verification
      is_paid: false
    })
    
    // Create default user preferences
    await databaseAdapter.createUserPreferences(newUser.id, {})
    
    console.log('✅ User created successfully!')
    console.log('User details:', {
      id: newUser.id,
      email: newUser.email,
      name: newUser.name,
      isPaid: newUser.is_paid,
      createdAt: newUser.created_at
    })
    
    console.log('🎉 You can now login with email: support@origens.nl (any password will work)')
    
  } catch (error) {
    console.error('❌ Error creating user:', error)
  } finally {
    await databaseService.closePool()
    process.exit(0)
  }
}

createUser()
