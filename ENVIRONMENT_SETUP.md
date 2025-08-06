# Environment Variables Setup for CashControl

## Required Environment Variables

Set these environment variables in your Plesk hosting control panel:

### Google OAuth
```
GOOGLE_CLIENT_SECRET=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
```

### Database (Already configured in code)
```
DB_HOST=45.82.188.227
DB_PORT=3306
DB_NAME=vxmjmwlj_
DB_USER=123cashcontrol
DB_PASSWORD=Super-mannetje45
```

### Email (Already configured in code)
```
SMTP_HOST=shared58.cloud86-host.nl
SMTP_PORT=587
SMTP_USER=info@123cashcontrol.com
SMTP_PASSWORD=Super-mannetje45
```

## How to Set Environment Variables in Plesk

1. Log into your Plesk control panel
2. Go to **Websites & Domains**
3. Select your domain (123cashcontrol.com)
4. Click on **PHP Settings**
5. Scroll down to **Environment Variables**
6. Add each variable with its value
7. Save changes

## Testing

After setting the environment variables, test:
- Google OAuth: https://123cashcontrol.com/api/auth/google.php
- Database: https://123cashcontrol.com/api/init-db.php
- Signup: https://123cashcontrol.com/app/auth/signup.html

## Security Notes

- Never commit secrets to Git
- Environment variables are the secure way to handle credentials
- The Google Client Secret is now properly secured
