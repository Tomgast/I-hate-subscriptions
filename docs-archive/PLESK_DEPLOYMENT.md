# Plesk Deployment Guide for CashControl

## Current Status
✅ GitHub integration working  
✅ Domain 123cashcontrol.com active  
✅ Next.js app builds successfully  
❌ Plesk needs Node.js configuration  

## Plesk Configuration Steps

### 1. Enable Node.js in Plesk
1. Log into your Plesk control panel
2. Go to **Websites & Domains** → **123cashcontrol.com**
3. Click **Node.js** (if available) or **Additional Services**
4. Enable Node.js support
5. Set Node.js version to **18.x** or **20.x** (latest LTS)

### 2. Configure Application Settings
In the Node.js section:
- **Application Root**: `/` (repository root)
- **Application Startup File**: `server.js` (we'll create this)
- **Application Mode**: `production`
- **Environment Variables**: Add from `.env.local`

### 3. Required Environment Variables
Add these in Plesk Node.js environment variables:
```
NODE_ENV=production
NEXTAUTH_URL=https://123cashcontrol.com
NEXTAUTH_SECRET=your-secret-key
GOOGLE_CLIENT_ID=267507492904-hr7q0qi2655ne01tv2si5ienpi6el4cm.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-google-secret
DB_HOST=45.82.188.227
DB_PORT=3306
DB_NAME=vxmjmwlj_
DB_USER=123cashcontrol
DB_PASSWORD=Super-mannetje45
SMTP_HOST=shared58.cloud86-host.nl
SMTP_PORT=587
SMTP_USER=info@123cashcontrol.com
SMTP_PASSWORD=your-smtp-password
```

### 4. Package.json Scripts
Ensure these scripts exist in package.json:
```json
{
  "scripts": {
    "dev": "next dev",
    "build": "next build",
    "start": "next start",
    "lint": "next lint"
  }
}
```

### 5. Create Server.js (if needed)
Some Plesk setups require a custom server file:
```javascript
const { createServer } = require('http')
const { parse } = require('url')
const next = require('next')

const dev = process.env.NODE_ENV !== 'production'
const hostname = 'localhost'
const port = process.env.PORT || 3000

const app = next({ dev, hostname, port })
const handle = app.getRequestHandler()

app.prepare().then(() => {
  createServer(async (req, res) => {
    try {
      const parsedUrl = parse(req.url, true)
      await handle(req, res, parsedUrl)
    } catch (err) {
      console.error('Error occurred handling', req.url, err)
      res.statusCode = 500
      res.end('internal server error')
    }
  }).listen(port, (err) => {
    if (err) throw err
    console.log(`> Ready on http://${hostname}:${port}`)
  })
})
```

## Alternative: Static Export Method

If Node.js configuration is complex, we can use static export:

### 1. Update next.config.js
```javascript
/** @type {import('next').NextConfig} */
const nextConfig = {
  output: 'export',
  trailingSlash: true,
  images: {
    unoptimized: true,
  },
}
module.exports = nextConfig
```

### 2. Build and Deploy
```bash
npm run build
# This creates an 'out' folder
# Point Plesk document root to the 'out' folder
```

### 3. Limitations of Static Export
- No API routes (authentication, payments, email)
- No server-side features
- Limited to client-side functionality only

## Recommended Approach

**Use Node.js deployment** to keep all features:
1. Configure Plesk for Node.js
2. Set startup command: `npm start`
3. Ensure all environment variables are set
4. Test the deployment

## Troubleshooting

### If you see the placeholder index.html:
- Plesk document root is pointing to repository root
- Node.js application is not running
- Check Plesk logs for Node.js errors

### If build fails:
- Check Node.js version compatibility
- Verify all dependencies are installed
- Review environment variables

### If API routes don't work:
- Confirm Node.js is properly configured
- Check that `npm start` works locally
- Verify environment variables in Plesk

## Next Steps
1. Apply the updated index.html (commit and push)
2. Configure Node.js in Plesk control panel
3. Set environment variables
4. Test the deployment
5. Monitor Plesk logs for any issues
