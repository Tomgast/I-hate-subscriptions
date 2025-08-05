// Static Export Script for Plesk Deployment
// This script temporarily renames API routes to allow static export

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const apiDir = path.join(__dirname, 'src', 'app', 'api');
const tempApiDir = path.join(__dirname, 'src', 'app', 'api-temp');

console.log('🚀 Starting static export for Plesk...');

try {
  // Step 1: Temporarily move API routes
  if (fs.existsSync(apiDir)) {
    console.log('📁 Moving API routes temporarily...');
    fs.renameSync(apiDir, tempApiDir);
  }

  // Step 2: Run Next.js build with static export
  console.log('🔨 Building static export...');
  execSync('npm run build', { stdio: 'inherit' });

  // Step 3: Restore API routes
  if (fs.existsSync(tempApiDir)) {
    console.log('📁 Restoring API routes...');
    fs.renameSync(tempApiDir, apiDir);
  }

  console.log('✅ Static export completed successfully!');
  console.log('📦 Files are ready in the "out" folder for Plesk deployment');
  
} catch (error) {
  // Restore API routes if something went wrong
  if (fs.existsSync(tempApiDir)) {
    fs.renameSync(tempApiDir, apiDir);
  }
  
  console.error('❌ Static export failed:', error.message);
  process.exit(1);
}
