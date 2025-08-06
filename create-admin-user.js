// Script to create admin user account in production MariaDB database
// Run this script to create your user account: node create-admin-user.js

const mysql = require('mysql2/promise');
const { v4: uuidv4 } = require('uuid');

async function createAdminUser() {
  let connection;
  
  try {
    console.log('🔧 Connecting to production MariaDB database...');
    
    // Database connection (using your Plesk credentials)
    connection = await mysql.createConnection({
      host: process.env.DB_HOST || '45.82.188.227',
      port: parseInt(process.env.DB_PORT || '3306'),
      database: process.env.DB_NAME || 'vxmjmwlj_',
      user: process.env.DB_USER || '123cashcontrol',
      password: process.env.DB_PASSWORD || 'Super-mannetje45',
      charset: 'utf8mb4'
    });
    
    console.log('✅ Connected to production database');
    
    // Initialize tables first
    console.log('🔧 Ensuring database tables exist...');
    
    const createUsersTable = `
      CREATE TABLE IF NOT EXISTS users (
        id VARCHAR(255) PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        name VARCHAR(255),
        image VARCHAR(500),
        password VARCHAR(255),
        is_paid BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_is_paid (is_paid)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `;
    
    const createUserPreferencesTable = `
      CREATE TABLE IF NOT EXISTS user_preferences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(255) NOT NULL,
        reminder_days JSON DEFAULT '[]',
        reminder_frequency ENUM('once', 'daily', 'weekly') DEFAULT 'once',
        preferred_time TIME DEFAULT '09:00:00',
        email_welcome BOOLEAN DEFAULT TRUE,
        email_upgrade BOOLEAN DEFAULT TRUE,
        email_bank_scan BOOLEAN DEFAULT TRUE,
        email_reminders BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_prefs (user_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `;
    
    await connection.execute(createUsersTable);
    await connection.execute(createUserPreferencesTable);
    console.log('✅ Database tables verified');
    
    // Check if user already exists
    const [existingUsers] = await connection.execute(
      'SELECT * FROM users WHERE email = ?',
      ['support@origens.nl']
    );
    
    if (existingUsers.length > 0) {
      console.log('✅ User already exists:', existingUsers[0].email);
      console.log('User details:', {
        id: existingUsers[0].id,
        email: existingUsers[0].email,
        name: existingUsers[0].name,
        isPaid: existingUsers[0].is_paid,
        createdAt: existingUsers[0].created_at
      });
      return;
    }
    
    // Create the admin user
    const userId = uuidv4();
    const now = new Date().toISOString();
    
    await connection.execute(
      `INSERT INTO users (id, email, name, password, is_paid, created_at, updated_at) 
       VALUES (?, ?, ?, ?, ?, ?, ?)`,
      [userId, 'support@origens.nl', 'Support Origens', null, false, now, now]
    );
    
    // Create default user preferences
    await connection.execute(
      `INSERT INTO user_preferences (user_id, reminder_days, reminder_frequency, preferred_time,
       email_welcome, email_upgrade, email_bank_scan, email_reminders, created_at, updated_at)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [userId, JSON.stringify([1, 3, 7]), 'once', '09:00:00', true, true, true, true, now, now]
    );
    
    console.log('✅ Admin user created successfully!');
    console.log('User details:', {
      id: userId,
      email: 'support@origens.nl',
      name: 'Support Origens',
      isPaid: false,
      createdAt: now
    });
    
    console.log('🎉 You can now login with:');
    console.log('   Email: support@origens.nl');
    console.log('   Password: (any password will work since no password is set)');
    
  } catch (error) {
    console.error('❌ Error creating admin user:', error);
    console.error('Details:', error.message);
  } finally {
    if (connection) {
      await connection.end();
      console.log('🔌 Database connection closed');
    }
  }
}

// Run the script
createAdminUser().then(() => {
  console.log('✅ Script completed');
  process.exit(0);
}).catch((error) => {
  console.error('❌ Script failed:', error);
  process.exit(1);
});
