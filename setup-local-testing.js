// Setup script to enable local database testing
// Run this with: node setup-local-testing.js

const fs = require('fs');
const path = require('path');

const envPath = path.join(__dirname, '.env.local');

// Read existing .env.local or create new one
let envContent = '';
if (fs.existsSync(envPath)) {
  envContent = fs.readFileSync(envPath, 'utf8');
}

// Check if USE_LOCAL_DB is already set
if (!envContent.includes('USE_LOCAL_DB')) {
  // Add local database configuration
  const localDbConfig = `
# Local Database Testing Configuration
USE_LOCAL_DB=true

`;
  
  envContent += localDbConfig;
  
  // Write back to .env.local
  fs.writeFileSync(envPath, envContent);
  
  console.log('✅ Local database testing enabled in .env.local');
  console.log('📝 Added USE_LOCAL_DB=true to your environment');
  console.log('🔄 Please restart your development server (npm run dev)');
} else {
  console.log('✅ Local database configuration already exists in .env.local');
  console.log('🔍 Current USE_LOCAL_DB setting:', process.env.USE_LOCAL_DB);
}

console.log('\n📋 Next steps:');
console.log('1. Restart your development server: npm run dev');
console.log('2. Visit: http://localhost:3000/api/database/test-local');
console.log('3. Test Pro upgrade at: http://localhost:3000');
console.log('4. Check dashboard after upgrading to Pro');
