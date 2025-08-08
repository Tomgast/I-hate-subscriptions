# Google OAuth Setup Guide

## Current Status
✅ NextAuth.js configured with Google provider  
✅ Sign-in page with Google OAuth button  
✅ User management integrated with Supabase  
✅ Environment variables template ready  

## Setup Steps

### 1. Create Google OAuth Credentials

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable the **Google+ API** (or Google Identity API)
4. Navigate to **Credentials** → **Create Credentials** → **OAuth 2.0 Client IDs**
5. Choose **Web application** as application type
6. Add authorized redirect URIs:
   - Development: `http://localhost:3000/api/auth/callback/google`
   - Production: `https://yourdomain.com/api/auth/callback/google`

### 2. Update Environment Variables

Replace the placeholder values in `.env.local`:

```env
GOOGLE_CLIENT_ID=your_actual_google_client_id_here
GOOGLE_CLIENT_SECRET=your_actual_google_client_secret_here
```

### 3. Test the Integration

1. Start your development server: `npm run dev`
2. Navigate to `/auth/signin`
3. Click "Continue with Google"
4. Complete the OAuth flow
5. Verify user is created in Supabase

## How It Works

### Authentication Flow
1. User clicks "Continue with Google" button
2. NextAuth redirects to Google OAuth
3. User authorizes your app
4. Google redirects back with authorization code
5. NextAuth exchanges code for user info
6. System checks if user exists in Supabase
7. If new user, creates profile automatically
8. User is signed in and redirected to onboarding/dashboard

### User Management
- New Google users are automatically created in Supabase
- User profiles include Google account info
- Trial period is automatically set (7 days)
- Payment status is tracked for premium features

### Security Features
- JWT-based sessions
- Secure cookie handling
- CSRF protection via NextAuth
- Environment variable protection

## Troubleshooting

### Common Issues
1. **"Invalid redirect URI"** - Check your Google Console redirect URIs match exactly
2. **"Client ID not found"** - Verify environment variables are loaded correctly
3. **"User creation failed"** - Check Supabase permissions and database triggers

### Debug Steps
1. Check browser console for errors
2. Verify environment variables: `console.log(process.env.GOOGLE_CLIENT_ID)`
3. Check NextAuth debug logs by adding `debug: true` to authOptions
4. Verify Supabase connection and permissions

## Production Deployment

Before going live:
1. Update redirect URIs in Google Console with production domain
2. Set secure NEXTAUTH_SECRET (generate with: `openssl rand -base64 32`)
3. Update NEXTAUTH_URL to production domain
4. Test OAuth flow on production environment
