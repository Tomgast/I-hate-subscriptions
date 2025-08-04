// Database setup script for CashControl
// This script checks and creates the necessary database structure in Supabase

import { createClient } from '@supabase/supabase-js';
import dotenv from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

// Load environment variables from .env.local
const __dirname = path.dirname(fileURLToPath(import.meta.url));
dotenv.config({ path: path.resolve(__dirname, '../.env.local') });

// Initialize Supabase client with admin privileges
const supabaseUrl = process.env.NEXT_PUBLIC_SUPABASE_URL;
const supabaseKey = process.env.SUPABASE_SERVICE_ROLE_KEY;

if (!supabaseUrl || !supabaseKey) {
  console.error('❌ Missing Supabase credentials in .env.local file');
  process.exit(1);
}

const supabase = createClient(supabaseUrl, supabaseKey);

async function setupDatabase() {
  console.log('🔄 Setting up database for CashControl...');
  
  try {
    // Check if user_profiles table exists
    const { data: tables, error: tablesError } = await supabase
      .from('information_schema.tables')
      .select('table_name')
      .eq('table_schema', 'public');
    
    if (tablesError) {
      throw new Error(`Failed to check tables: ${tablesError.message}`);
    }
    
    const userProfilesExists = tables.some(table => table.table_name === 'user_profiles');
    
    if (!userProfilesExists) {
      console.log('📝 Creating user_profiles table...');
      
      // Create user_profiles table
      const { error: createTableError } = await supabase.rpc('create_user_profiles_table');
      
      if (createTableError) {
        // Try direct SQL if RPC fails
        const { error: sqlError } = await supabase.sql(`
          CREATE TABLE public.user_profiles (
            id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
            email TEXT UNIQUE NOT NULL,
            full_name TEXT,
            avatar_url TEXT,
            has_paid BOOLEAN DEFAULT FALSE,
            payment_date TIMESTAMPTZ,
            created_at TIMESTAMPTZ DEFAULT NOW(),
            updated_at TIMESTAMPTZ DEFAULT NOW()
          );
        `);
        
        if (sqlError) {
          throw new Error(`Failed to create user_profiles table: ${sqlError.message}`);
        }
      }
      
      console.log('✅ user_profiles table created successfully');
    } else {
      console.log('✓ user_profiles table already exists');
      
      // Check if required columns exist
      const { data: columns, error: columnsError } = await supabase
        .from('information_schema.columns')
        .select('column_name')
        .eq('table_schema', 'public')
        .eq('table_name', 'user_profiles');
      
      if (columnsError) {
        throw new Error(`Failed to check columns: ${columnsError.message}`);
      }
      
      const columnNames = columns.map(col => col.column_name);
      
      // Check and add has_paid column if it doesn't exist
      if (!columnNames.includes('has_paid')) {
        console.log('📝 Adding has_paid column...');
        const { error: addColumnError } = await supabase.sql(`
          ALTER TABLE public.user_profiles ADD COLUMN has_paid BOOLEAN DEFAULT FALSE;
        `);
        
        if (addColumnError) {
          throw new Error(`Failed to add has_paid column: ${addColumnError.message}`);
        }
        console.log('✅ has_paid column added successfully');
      }
      
      // Check and add payment_date column if it doesn't exist
      if (!columnNames.includes('payment_date')) {
        console.log('📝 Adding payment_date column...');
        const { error: addColumnError } = await supabase.sql(`
          ALTER TABLE public.user_profiles ADD COLUMN payment_date TIMESTAMPTZ;
        `);
        
        if (addColumnError) {
          throw new Error(`Failed to add payment_date column: ${addColumnError.message}`);
        }
        console.log('✅ payment_date column added successfully');
      }
    }
    
    // Check for existing users and update their profiles if needed
    console.log('🔄 Checking for existing users without profiles...');
    const { data: authUsers, error: authError } = await supabase.auth.admin.listUsers();
    
    if (authError) {
      throw new Error(`Failed to list auth users: ${authError.message}`);
    }
    
    if (authUsers && authUsers.users && authUsers.users.length > 0) {
      for (const user of authUsers.users) {
        if (!user.email) continue;
        
        // Check if user has a profile
        const { data: profile, error: profileError } = await supabase
          .from('user_profiles')
          .select('*')
          .eq('email', user.email)
          .single();
        
        if (profileError && profileError.code !== 'PGRST116') { // Not found error is expected
          console.warn(`⚠️ Error checking profile for ${user.email}: ${profileError.message}`);
          continue;
        }
        
        // Create profile if it doesn't exist
        if (!profile) {
          console.log(`📝 Creating profile for existing user: ${user.email}`);
          const { error: insertError } = await supabase
            .from('user_profiles')
            .insert({
              email: user.email,
              full_name: user.user_metadata?.full_name || '',
              avatar_url: user.user_metadata?.avatar_url || '',
              has_paid: false
            });
          
          if (insertError) {
            console.error(`❌ Failed to create profile for ${user.email}: ${insertError.message}`);
          } else {
            console.log(`✅ Profile created for ${user.email}`);
          }
        }
      }
    }
    
    console.log('✅ Database setup completed successfully');
    
  } catch (error) {
    console.error('❌ Database setup failed:', error.message);
    process.exit(1);
  }
}

// Run the setup
setupDatabase();
