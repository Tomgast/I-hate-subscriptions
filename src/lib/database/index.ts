// Database Switcher - Allows easy switching between local and production databases
// Set USE_LOCAL_DB=true in .env.local to use local SQLite database for testing

import { dbAdapter } from './adapter'
import { localDbAdapter } from './local-adapter'

// Check if we should use local database for testing
const useLocalDatabase = process.env.USE_LOCAL_DB === 'true' || 
                         process.env.NODE_ENV === 'development' && !process.env.DB_HOST

// Export the appropriate database adapter
export const databaseAdapter = useLocalDatabase ? localDbAdapter : dbAdapter

// Export types
export type { User, Subscription, UserPreferences, ReminderLog } from './adapter'

// Helper function to check which database is being used
export function getDatabaseType(): 'local' | 'production' {
  return useLocalDatabase ? 'local' : 'production'
}

// Helper function to get database status
export async function getDatabaseStatus() {
  try {
    if (useLocalDatabase) {
      const testUser = await localDbAdapter.getUserByEmail('test@example.com')
      return {
        type: 'local',
        connected: true,
        message: 'Using local SQLite database for testing',
        hasTestData: !!testUser
      }
    } else {
      // Test production database connection
      const testResult = await dbAdapter.getUserByEmail('test@example.com')
      return {
        type: 'production',
        connected: true,
        message: 'Using production MariaDB database',
        hasTestData: !!testResult
      }
    }
  } catch (error) {
    return {
      type: useLocalDatabase ? 'local' : 'production',
      connected: false,
      message: `Database connection failed: ${error}`,
      hasTestData: false
    }
  }
}

console.log(`🗄️ Database: Using ${useLocalDatabase ? 'LOCAL SQLite' : 'PRODUCTION MariaDB'} database`)
