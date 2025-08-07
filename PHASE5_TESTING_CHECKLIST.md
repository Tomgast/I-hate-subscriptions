# Phase 5: CashControl Testing Checklist

## 🎯 Testing Overview
Systematic verification of all CashControl functionality across the three-plan business model:
- **Monthly Plan**: €3/month - Full ongoing access
- **Yearly Plan**: €25/year - Full ongoing access (31% savings)
- **One-time Plan**: €25 - Single bank scan + export + guides

---

## 📋 Core Infrastructure Tests

### ✅ Database & Configuration
- [ ] **Database Connection**: Verify MariaDB connection works
- [ ] **Required Tables**: Check users, user_preferences, bank_scans, unsubscribe_guides exist
- [ ] **Plan Manager**: Test plan detection and access control logic
- [ ] **Stripe Integration**: Verify API keys and service initialization
- [ ] **Email Configuration**: Check SMTP settings and credentials
- [ ] **TrueLayer Setup**: Verify bank integration credentials

**Testing Method**: Run `test/test-connections.php` and verify all services connect successfully.

---

## 🔐 Authentication & User Management

### ✅ User Registration
- [ ] **Email Signup**: Test registration with email/password
- [ ] **Google OAuth**: Test Google sign-in integration
- [ ] **Email Verification**: Check if verification emails are sent
- [ ] **User Creation**: Verify user records are created in database
- [ ] **Session Handling**: Test session creation and persistence

**Manual Steps**:
1. Visit `/auth/signup.php`
2. Try both email and Google registration
3. Check database for new user records
4. Verify session variables are set correctly

### ✅ Login & Logout
- [ ] **Email Login**: Test signin with email/password
- [ ] **Google Login**: Test Google OAuth signin
- [ ] **Session Management**: Verify sessions persist across pages
- [ ] **Logout Function**: Test logout clears sessions properly
- [ ] **Redirect Logic**: Check post-login redirects work

**Manual Steps**:
1. Visit `/auth/signin.php`
2. Test both login methods
3. Navigate between pages to verify session
4. Test logout functionality

---

## 💳 Payment Processing & Plans

### ✅ Plan Selection & Checkout
- [ ] **Upgrade Page**: All three plans display correctly
- [ ] **Pricing Display**: €3/month, €25/year, €25 one-time shown
- [ ] **Stripe Checkout**: Test checkout session creation for each plan
- [ ] **Payment Success**: Test successful payment handling
- [ ] **Payment Cancel**: Test payment cancellation flow
- [ ] **Plan Activation**: Verify user plan is updated after payment

**Manual Steps**:
1. Visit `/upgrade.php` - verify all plans show
2. Click each plan - test Stripe checkout (use test cards)
3. Complete payment - verify success page
4. Cancel payment - verify cancel page
5. Check database for plan updates

### ✅ Plan-Based Access Control
- [ ] **Monthly Users**: Full dashboard, unlimited scans, all features
- [ ] **Yearly Users**: Full dashboard, unlimited scans, all features  
- [ ] **One-time Users**: Limited dashboard, single scan, export + guides
- [ ] **No Plan Users**: Redirect to upgrade page
- [ ] **Expired Plans**: Handle expired subscriptions properly

**Testing Method**: Create test users with different plans and verify feature access.

---

## 📊 Dashboard & Core Features

### ✅ Dashboard Routing
- [ ] **Plan Detection**: Users routed to correct dashboard version
- [ ] **Full Dashboard**: Monthly/yearly users see `dashboard.php`
- [ ] **Limited Dashboard**: One-time users see `dashboard-onetime.php`
- [ ] **Plan Status**: Current plan displayed correctly
- [ ] **Feature Access**: Features enabled/disabled by plan type

**Manual Steps**:
1. Login as different plan types
2. Verify correct dashboard loads
3. Check plan status component shows correct info
4. Test feature availability matches plan type

### ✅ Subscription Management
- [ ] **Add Subscriptions**: Test manual subscription entry
- [ ] **Edit Subscriptions**: Test subscription modification
- [ ] **Delete Subscriptions**: Test subscription removal
- [ ] **Categories**: Test subscription categorization
- [ ] **Data Persistence**: Verify data saves correctly

---

## 🏦 Bank Integration & Scanning

### ✅ TrueLayer Integration
- [ ] **Bank Connection**: Test TrueLayer authorization flow
- [ ] **Scan Initiation**: Test bank scan startup
- [ ] **Scan Limits**: Verify one-time users limited to 1 scan
- [ ] **Scan Results**: Test scan data retrieval and display
- [ ] **Error Handling**: Test failed scan scenarios

**Manual Steps**:
1. Visit `/bank/scan.php`
2. Test connection with different plan types
3. Verify scan limits enforced
4. Check scan results display properly

### ✅ Scan Usage Tracking
- [ ] **Usage Counting**: Verify scans are counted correctly
- [ ] **Limit Enforcement**: One-time users blocked after 1 scan
- [ ] **Unlimited Access**: Monthly/yearly users can scan repeatedly
- [ ] **Database Updates**: Scan records saved properly

---

## 📄 Export System

### ✅ PDF Export
- [ ] **PDF Generation**: Test PDF creation from scan data
- [ ] **Content Quality**: Verify PDF contains all required data
- [ ] **Plan Access**: All plan types can export
- [ ] **File Download**: Test PDF download functionality
- [ ] **Error Handling**: Test export with no data

**Manual Steps**:
1. Visit `/export/index.php`
2. Select PDF export
3. Verify PDF generates and downloads
4. Check PDF content quality and completeness

### ✅ CSV Export
- [ ] **CSV Generation**: Test CSV creation from scan data
- [ ] **Format Options**: Test detailed vs summary formats
- [ ] **Excel Compatibility**: Verify CSV opens in Excel/Sheets
- [ ] **Data Accuracy**: Check all subscription data included
- [ ] **File Download**: Test CSV download functionality

**Manual Steps**:
1. Visit `/export/index.php`
2. Select CSV export (both formats)
3. Download and open in Excel/Google Sheets
4. Verify data accuracy and completeness

---

## 📚 Unsubscribe Guides

### ✅ Guide System
- [ ] **Guide Database**: Verify guides table populated
- [ ] **Guide Browser**: Test `/guides/index.php` functionality
- [ ] **Search Function**: Test guide search by service name
- [ ] **Category Filter**: Test filtering by category
- [ ] **Guide Viewing**: Test individual guide display
- [ ] **Plan Access**: Verify access control by plan type

**Manual Steps**:
1. Visit `/guides/index.php`
2. Test search functionality
3. Test category filters
4. Click individual guides to view details
5. Verify plan-based access works

### ✅ Guide Content
- [ ] **Guide Quality**: Check guide instructions are clear
- [ ] **Step-by-step**: Verify guides have proper steps
- [ ] **Contact Info**: Check service contact details included
- [ ] **Usage Tracking**: Test guide view counting

---

## 📧 Email & Notifications

### ✅ SMTP Integration
- [ ] **Email Sending**: Test email functionality works
- [ ] **Welcome Emails**: Test new user welcome messages
- [ ] **Payment Confirmations**: Test payment success emails
- [ ] **Plan Notifications**: Test plan-related emails
- [ ] **Error Handling**: Test email failure scenarios

**Testing Method**: Check email logs and test email sending manually.

---

## 🔒 Security & Session Management

### ✅ Authentication Security
- [ ] **Session Expiration**: Test session timeout handling
- [ ] **Re-authentication**: Test login after session expires
- [ ] **Protected Pages**: Verify auth required for sensitive pages
- [ ] **Plan Enforcement**: Test unauthorized plan access blocked
- [ ] **CSRF Protection**: Check form security measures

### ✅ Data Security
- [ ] **SQL Injection**: Test form inputs for SQL injection
- [ ] **XSS Protection**: Test cross-site scripting prevention
- [ ] **Sensitive Data**: Verify no credentials exposed in code
- [ ] **Error Messages**: Check error messages don't leak info

---

## 🧪 Error Handling & Edge Cases

### ✅ Payment Errors
- [ ] **Failed Payments**: Test declined card scenarios
- [ ] **Network Errors**: Test Stripe connection failures
- [ ] **Invalid Plans**: Test invalid plan selection
- [ ] **Duplicate Payments**: Test double-payment prevention

### ✅ System Errors
- [ ] **Database Errors**: Test database connection failures
- [ ] **File Errors**: Test missing file scenarios
- [ ] **API Errors**: Test third-party service failures
- [ ] **Memory/Timeout**: Test resource limit scenarios

---

## 📝 Test Results Documentation

### ✅ Results Tracking
- [ ] **Pass/Fail Status**: Record result for each test
- [ ] **Issue Documentation**: Document any bugs found
- [ ] **Performance Notes**: Record any performance issues
- [ ] **User Experience**: Note UX improvements needed

### ✅ Final Verification
- [ ] **Complete User Flows**: Test end-to-end user journeys
- [ ] **Cross-browser**: Test in different browsers
- [ ] **Mobile Responsive**: Test mobile functionality
- [ ] **Production Readiness**: Confirm ready for live deployment

---

## 🚀 Go-Live Checklist

### ✅ Pre-Launch
- [ ] **All Tests Pass**: Verify 95%+ test pass rate
- [ ] **Critical Bugs Fixed**: No blocking issues remain
- [ ] **Performance Acceptable**: Page load times reasonable
- [ ] **Security Verified**: No major security vulnerabilities
- [ ] **Backup Plan**: Rollback procedure documented

### ✅ Launch Preparation
- [ ] **DNS Configuration**: Domain pointing correctly
- [ ] **SSL Certificate**: HTTPS working properly
- [ ] **Monitoring Setup**: Error tracking configured
- [ ] **Support Documentation**: User guides available

---

*Testing started: [DATE]*
*Testing completed: [DATE]*
*Overall pass rate: [PERCENTAGE]*
*Ready for production: [YES/NO]*
