# TrueLayer Integration Setup for CashControl

This guide will help you set up TrueLayer for European banking integration in CashControl.

## Prerequisites

1. **TrueLayer Account**: Sign up at [TrueLayer Console](https://console.truelayer.com/)
2. **API Credentials**: Get your Client ID and Client Secret
3. **Environment Variables**: Add to your `.env.local`

## Environment Variables

Add these to your `.env.local` file:

```bash
# TrueLayer Configuration
TRUELAYER_CLIENT_ID=your_client_id_here
TRUELAYER_CLIENT_SECRET=your_client_secret_here
TRUELAYER_ENVIRONMENT=sandbox  # or 'production'
TRUELAYER_REDIRECT_URI=http://localhost:3000/api/bank-providers/truelayer/callback

# For production
# TRUELAYER_REDIRECT_URI=https://123cashcontrol.com/api/bank-providers/truelayer/callback
```

## TrueLayer Console Setup

1. **Create Application**:
   - Go to TrueLayer Console
   - Create a new application
   - Set application type to "Web Application"

2. **Configure Redirect URIs**:
   - Development: `http://localhost:3000/api/bank-providers/truelayer/callback`
   - Production: `https://123cashcontrol.com/api/bank-providers/truelayer/callback`

3. **Set Permissions**:
   - `accounts` - Read account information
   - `transactions` - Read transaction history
   - `offline_access` - Maintain connection without user present

4. **Enable Countries**:
   - United Kingdom
   - Ireland
   - France
   - Germany
   - Spain
   - Italy
   - Netherlands
   - Other EU countries as needed

## Integration Flow

### 1. User Initiates Bank Connection
```typescript
// Frontend: User clicks "Connect Bank Account"
const connectBank = async () => {
  const response = await fetch('/api/bank-providers/link-token', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ 
      provider: 'truelayer',
      countryCode: 'GB' // User's country
    })
  })
  
  const { authUrl } = await response.json()
  window.location.href = authUrl // Redirect to TrueLayer
}
```

### 2. TrueLayer OAuth Flow
- User is redirected to TrueLayer
- User selects their bank and logs in
- User grants permissions
- TrueLayer redirects back to your callback

### 3. Callback Processing
- Your callback endpoint receives the authorization code
- Exchange code for access token
- Store token securely (encrypted)
- Redirect user back to dashboard

### 4. Fetch Bank Data
```typescript
// Fetch accounts
const accounts = await trueLayer.getAccounts(accessToken)

// Fetch transactions (last 90 days)
const transactions = await trueLayer.getTransactions(
  accessToken, 
  accountIds, 
  { startDate: '2024-01-01', endDate: '2024-03-31' }
)

// Analyze for subscriptions
const detectedSubs = bankScanService.detectSubscriptions(transactions)
```

## API Endpoints Created

1. **`/api/bank-providers/link-token`** - Create TrueLayer auth URL
2. **`/api/bank-providers/truelayer/callback`** - Handle OAuth callback
3. **`/api/bank-scan`** - Perform subscription detection
4. **`/api/bank-scan/confirm`** - Confirm detected subscriptions

## Security Considerations

### Token Storage
- **Never store in localStorage/sessionStorage**
- Use secure HTTP-only cookies or server-side sessions
- Encrypt tokens before database storage
- Implement token refresh logic

### Data Privacy
- Only request necessary permissions
- Delete transaction data after analysis
- Comply with GDPR requirements
- Provide clear privacy policy

### Error Handling
- Handle expired tokens gracefully
- Provide user-friendly error messages
- Log errors for debugging (without sensitive data)
- Implement retry logic for transient failures

## Testing

### Sandbox Mode
TrueLayer provides sandbox banks for testing:
- **Mock Bank** - Always succeeds
- **Error Bank** - Simulates various error conditions
- **Slow Bank** - Tests timeout handling

### Test Credentials
In sandbox mode, use these test credentials:
- Username: `john`
- Password: `doe`

## Production Checklist

- [ ] Environment variables configured
- [ ] Redirect URIs updated for production domain
- [ ] SSL certificate installed
- [ ] Error monitoring setup (Sentry, etc.)
- [ ] Token encryption implemented
- [ ] GDPR compliance verified
- [ ] Rate limiting implemented
- [ ] Webhook endpoints secured

## Supported Banks

TrueLayer supports 100+ European banks including:

### United Kingdom
- Barclays, HSBC, Lloyds, NatWest, Santander, TSB, etc.

### Ireland
- AIB, Bank of Ireland, Ulster Bank, Permanent TSB

### France
- BNP Paribas, Crédit Agricole, Société Générale, LCL

### Germany
- Deutsche Bank, Commerzbank, ING, DKB

### Spain
- Santander, BBVA, CaixaBank, Bankia

### Italy
- UniCredit, Intesa Sanpaolo, BNL, Monte dei Paschi

### Netherlands
- ING, ABN AMRO, Rabobank, SNS Bank

## Troubleshooting

### Common Issues

1. **"Invalid redirect URI"**
   - Check TrueLayer Console configuration
   - Ensure exact match including protocol and port

2. **"Insufficient permissions"**
   - Verify scopes in TrueLayer Console
   - Check application permissions

3. **"Token expired"**
   - Implement refresh token logic
   - Handle 401 responses gracefully

4. **"Bank not supported"**
   - Check TrueLayer's supported institutions
   - Provide fallback options

### Debug Mode
Enable debug logging:
```bash
DEBUG=truelayer:* npm run dev
```

## Next Steps

1. **Set up environment variables**
2. **Test with sandbox credentials**
3. **Implement token refresh logic**
4. **Add error handling and user feedback**
5. **Test with real bank accounts**
6. **Deploy to production**

## Support

- **TrueLayer Docs**: https://docs.truelayer.com/
- **TrueLayer Support**: support@truelayer.com
- **Community**: TrueLayer Slack/Discord

---

This integration provides secure, compliant access to European bank data for subscription detection in CashControl.
