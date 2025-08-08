# GoCardless (Nordigen) Setup Guide

## 🌍 **GoCardless Bank Account Data Integration**

GoCardless Bank Account Data (formerly Nordigen) provides EU bank integration for CashControl. This guide will help you set up the credentials and test the integration.

## 📋 **Prerequisites**

- Small business or individual developer account (no enterprise requirements)
- EU bank account for testing
- Access to your secure configuration file

## 🚀 **Step 1: Create GoCardless Account**

1. **Visit**: [GoCardless Bank Account Data](https://gocardless.com/bank-account-data/)
2. **Sign up** for a developer account (free tier available)
3. **Verify** your email and complete account setup
4. **Navigate** to the API section in your dashboard

## 🔑 **Step 2: Get API Credentials**

1. **Login** to your GoCardless dashboard
2. **Go to** API > Credentials
3. **Copy** your:
   - **Secret ID** (looks like: `12345678-1234-1234-1234-123456789012`)
   - **Secret Key** (looks like: `abcdef123456789abcdef123456789abcdef123456789abcdef123456789abcdef`)

## ⚙️ **Step 3: Configure CashControl**

1. **Open** your `secure-config.php` file
2. **Replace** the placeholder values:

```php
// GoCardless (Nordigen) Bank Account Data API - EU Banks
'GOCARDLESS_SECRET_ID' => 'YOUR_ACTUAL_SECRET_ID_HERE',
'GOCARDLESS_SECRET_KEY' => 'YOUR_ACTUAL_SECRET_KEY_HERE',
'GOCARDLESS_ENVIRONMENT' => 'production', // or 'sandbox' for testing
```

3. **Save** the file

## 🧪 **Step 4: Test the Integration**

1. **Login** to your CashControl account
2. **Go to** Dashboard
3. **Click** "Bank Scan" or "Connect Bank Account"
4. **Select** "European Banks" option
5. **Choose** your country (NL, DE, FR, etc.)
6. **Click** "Connect Bank Account"
7. **Complete** the bank authorization flow

## 🌍 **Supported Countries**

GoCardless supports banks in these EU countries:

- 🇳🇱 **Netherlands** - All major banks (ING, ABN AMRO, Rabobank, etc.)
- 🇩🇪 **Germany** - Deutsche Bank, Commerzbank, Sparkasse, etc.
- 🇫🇷 **France** - BNP Paribas, Crédit Agricole, Société Générale, etc.
- 🇪🇸 **Spain** - Santander, BBVA, CaixaBank, etc.
- 🇮🇹 **Italy** - UniCredit, Intesa Sanpaolo, etc.
- 🇬🇧 **United Kingdom** - Barclays, HSBC, Lloyds, etc.
- 🇧🇪 **Belgium** - KBC, BNP Paribas Fortis, etc.
- 🇦🇹 **Austria** - Erste Bank, Bank Austria, etc.
- 🇵🇹 **Portugal** - Millennium BCP, CGD, etc.
- 🇮🇪 **Ireland** - AIB, Bank of Ireland, etc.
- 🇫🇮 **Finland** - Nordea, OP Financial, etc.
- 🇩🇰 **Denmark** - Danske Bank, Nordea, etc.
- 🇸🇪 **Sweden** - SEB, Swedbank, Nordea, etc.
- 🇳🇴 **Norway** - DNB, Nordea, etc.

## 🔧 **Troubleshooting**

### **"GoCardless credentials not configured" Error**
- Check that your `GOCARDLESS_SECRET_ID` and `GOCARDLESS_SECRET_KEY` are correctly set
- Ensure there are no extra spaces or quotes around the values
- Verify the file path to `secure-config.php` is correct

### **"Failed to get GoCardless access token" Error**
- Verify your credentials are correct
- Check if your GoCardless account is active
- Ensure you're using the right environment (sandbox vs production)

### **"No banks available for country" Error**
- The selected country might not be supported
- Try a different country from the supported list
- Check GoCardless status page for service issues

### **Bank Connection Fails**
- Some banks require additional verification steps
- Try connecting with a different bank in the same country
- Check that your bank supports Open Banking/PSD2

## 📊 **Features Supported**

✅ **Account Connection** - Connect multiple EU bank accounts  
✅ **Transaction History** - Up to 90 days of transaction data  
✅ **Subscription Detection** - Automatic recurring payment detection  
✅ **Balance Information** - Current account balances  
✅ **Export Functionality** - PDF and CSV exports  
✅ **Multi-Country Support** - 14+ EU countries  

## 💰 **Pricing**

- **Free Tier**: 100 API requests per month
- **Pay-as-you-go**: Additional requests at competitive rates
- **No setup fees** or minimum commitments
- **Perfect for small businesses** and individual developers

## 🔒 **Security**

- **Bank-grade security** with PSD2 compliance
- **No credential storage** - users authenticate directly with their bank
- **Limited access scope** - only transactions and balances
- **Automatic token expiration** - connections expire after 90 days

## 📞 **Support**

- **GoCardless Documentation**: [docs.gocardless.com](https://docs.gocardless.com)
- **CashControl Support**: Check your dashboard for support options
- **Community**: GoCardless has an active developer community

---

## 🎯 **Next Steps**

Once you've completed the setup:

1. **Test** with your own bank account
2. **Verify** subscription detection works
3. **Try** the export functionality
4. **Monitor** your API usage in the GoCardless dashboard

Your CashControl app now supports both **US banks** (via Stripe) and **EU banks** (via GoCardless)!
