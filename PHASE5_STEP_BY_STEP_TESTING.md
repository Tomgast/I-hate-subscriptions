# Phase 5: Step-by-Step Testing Guide

## 🎯 Testing Strategy
We'll test the CashControl application systematically using direct file access and manual browser testing. This ensures we don't miss anything critical.

---

## 📋 STEP 1: Infrastructure Verification

### A. File Existence Check
**What to do:** Check that all critical files exist and are accessible.

**Files to verify exist:**
```
✅ Core Pages:
- index.php (Homepage)
- dashboard.php (Main Dashboard)
- dashboard-onetime.php (One-time Dashboard)
- upgrade.php (Pricing/Plans)
- settings.php (User Settings)

✅ Authentication:
- auth/signin.php
- auth/signup.php
- auth/logout.php
- auth/google-callback.php

✅ Payment System:
- payment/success.php
- payment/cancel.php

✅ Features:
- export/index.php, export/pdf.php, export/csv.php
- guides/index.php, guides/view.php
- bank/scan.php, bank/connect.php

✅ Core Services:
- includes/plan_manager.php
- includes/stripe_service.php
- includes/bank_service.php
- includes/unsubscribe_service.php
```

**How to check:** Navigate to your project folder and verify these files exist.

### B. Configuration Test
**What to do:** Verify your secure configuration is working.

**Steps:**
1. Open: `test/test-connections.php` in browser
2. Check all services show "✅ Connected" or "✅ Working"
3. If any show errors, fix the credentials in your secure-config.php

**Expected Results:**
- Database: Connected
- Stripe: API keys valid
- SMTP: Email configured
- Google OAuth: Client configured

---

## 📋 STEP 2: Core Page Testing

### A. Homepage Test
**URL:** `https://123cashcontrol.com/index.php`

**What to check:**
- [ ] Page loads without errors
- [ ] Shows three pricing plans: €3/month, €25/year, €25 one-time
- [ ] No references to "free plan" or old €29 pricing
- [ ] Sign up/login buttons work
- [ ] Header navigation works

### B. Authentication Pages
**URLs to test:**
- `https://123cashcontrol.com/auth/signin.php`
- `https://123cashcontrol.com/auth/signup.php`

**What to check:**
- [ ] Sign-in page loads and shows email + Google options
- [ ] Sign-up page loads and shows email + Google options
- [ ] Forms are properly styled and functional
- [ ] No broken links or missing images

### C. Upgrade/Pricing Page
**URL:** `https://123cashcontrol.com/upgrade.php`

**What to check:**
- [ ] Shows exactly three plans (no free plan)
- [ ] Pricing: €3/month, €25/year, €25 one-time
- [ ] Plan features clearly described
- [ ] "Choose Plan" buttons work (lead to Stripe)
- [ ] Plan Manager integration working (shows current plan if logged in)

---

## 📋 STEP 3: User Registration & Login Testing

### A. Create Test User (Email Registration)
**Steps:**
1. Go to `/auth/signup.php`
2. Register with test email: `test1@example.com`
3. Use password: `TestPassword123!`
4. Complete registration process

**What to verify:**
- [ ] Registration completes successfully
- [ ] User is logged in after registration
- [ ] Redirected to appropriate page (dashboard or upgrade)
- [ ] Session variables set correctly

### B. Test Login Process
**Steps:**
1. Logout (if logged in)
2. Go to `/auth/signin.php`
3. Login with test credentials
4. Verify login works

**What to verify:**
- [ ] Login successful
- [ ] Session persists across page navigation
- [ ] User redirected to correct dashboard

### C. Test Google OAuth (Optional)
**Steps:**
1. Try Google sign-in button
2. Complete OAuth flow

**What to verify:**
- [ ] Google OAuth redirects work
- [ ] User account created/linked
- [ ] Login successful

---

## 📋 STEP 4: Payment System Testing

### A. Test Stripe Checkout
**Important:** Use Stripe test cards only!
- **Success Card:** 4242 4242 4242 4242
- **Decline Card:** 4000 0000 0000 0002
- **Expiry:** Any future date
- **CVC:** Any 3 digits

**Steps for each plan:**
1. Login as test user
2. Go to `/upgrade.php`
3. Click "Choose Plan" for Monthly (€3)
4. Complete Stripe checkout with test card
5. Verify success page
6. Repeat for Yearly (€25) and One-time (€25)

**What to verify:**
- [ ] Stripe checkout opens correctly
- [ ] Correct amount shown (€3.00, €25.00, €25.00)
- [ ] Payment success redirects to `/payment/success.php`
- [ ] Success page shows correct plan type
- [ ] User plan updated in database
- [ ] Payment cancel works (redirects to `/payment/cancel.php`)

### B. Test Payment Success/Cancel Pages
**What to check:**
- [ ] Success page shows plan-specific messaging
- [ ] Cancel page provides helpful next steps
- [ ] Both pages have proper navigation
- [ ] No old pricing references (€29)

---

## 📋 STEP 5: Dashboard & Plan-Based Access

### A. Test Dashboard Routing
**Create test users with different plans:**
1. User A: Monthly plan
2. User B: Yearly plan  
3. User C: One-time plan
4. User D: No plan

**For each user, test:**
- [ ] Correct dashboard loads (full vs limited)
- [ ] Plan status shows correctly
- [ ] Features available match plan type
- [ ] Navigation works properly

### B. Test Plan-Specific Features
**Monthly/Yearly users should have:**
- [ ] Full dashboard access
- [ ] Unlimited bank scan access
- [ ] All export features
- [ ] All unsubscribe guides

**One-time users should have:**
- [ ] Limited dashboard
- [ ] Single bank scan only
- [ ] Export features (limited)
- [ ] Unsubscribe guides access

**No-plan users should:**
- [ ] Be redirected to upgrade page
- [ ] Not access protected features

---

## 📋 STEP 6: Export System Testing

### A. Test PDF Export
**Steps:**
1. Login as user with active plan
2. Go to `/export/index.php`
3. Select PDF export
4. Download and verify PDF

**What to verify:**
- [ ] Export page loads correctly
- [ ] PDF generates without errors
- [ ] PDF contains subscription data
- [ ] PDF is well-formatted and professional
- [ ] Download works in browser

### B. Test CSV Export
**Steps:**
1. Go to `/export/index.php`
2. Select CSV export (detailed)
3. Select CSV export (summary)
4. Download both formats

**What to verify:**
- [ ] Both CSV formats generate
- [ ] Files download correctly
- [ ] CSV opens properly in Excel/Google Sheets
- [ ] Data is accurate and complete

---

## 📋 STEP 7: Bank Integration Testing

### A. Test Bank Scan Access
**Steps:**
1. Login as different plan types
2. Go to `/bank/scan.php`
3. Test scan initiation

**What to verify:**
- [ ] One-time users: Limited to 1 scan
- [ ] Monthly/yearly users: Unlimited scans
- [ ] Scan limits enforced properly
- [ ] Error messages clear and helpful

### B. Test TrueLayer Integration
**Note:** This requires actual bank credentials - test carefully!

**What to verify:**
- [ ] Bank connection flow works
- [ ] Scan results display properly
- [ ] Data saved to database correctly
- [ ] Error handling works

---

## 📋 STEP 8: Unsubscribe Guides Testing

### A. Test Guide System
**Steps:**
1. Go to `/guides/index.php`
2. Browse available guides
3. Test search functionality
4. View individual guides

**What to verify:**
- [ ] Guides page loads correctly
- [ ] Search function works
- [ ] Category filtering works
- [ ] Individual guides display properly
- [ ] Plan-based access control works

### B. Test Guide Content
**What to verify:**
- [ ] Guides have clear instructions
- [ ] Contact information included
- [ ] Steps are easy to follow
- [ ] No broken links or missing info

---

## 📋 STEP 9: Email & Notifications

### A. Test Email Functionality
**What to test:**
- [ ] Welcome emails sent after registration
- [ ] Payment confirmation emails
- [ ] Plan-related notifications
- [ ] Password reset emails (if implemented)

**How to test:**
Check your email inbox and spam folder after each action.

---

## 📋 STEP 10: Error Handling & Edge Cases

### A. Test Error Scenarios
**What to test:**
- [ ] Invalid login credentials
- [ ] Expired sessions
- [ ] Failed payments (use decline test card)
- [ ] Missing/invalid data
- [ ] Network timeouts

### B. Test Security
**What to verify:**
- [ ] Protected pages require login
- [ ] Plan restrictions enforced
- [ ] No sensitive data exposed in URLs
- [ ] Form validation works
- [ ] SQL injection protection

---

## 📋 STEP 11: Cross-Browser & Mobile Testing

### A. Browser Compatibility
**Test in:**
- [ ] Chrome
- [ ] Firefox
- [ ] Safari
- [ ] Edge

### B. Mobile Responsiveness
**What to check:**
- [ ] Pages display correctly on mobile
- [ ] Navigation works on touch devices
- [ ] Forms are usable on mobile
- [ ] Payment flow works on mobile

---

## 📋 STEP 12: Performance & Final Checks

### A. Performance Testing
**What to check:**
- [ ] Page load times reasonable (<3 seconds)
- [ ] No obvious memory leaks
- [ ] Database queries efficient
- [ ] Large exports don't timeout

### B. Final Verification
**Complete user journeys:**
1. [ ] New user → Register → Upgrade → Use features → Export
2. [ ] Existing user → Login → Change plan → Use features
3. [ ] Payment failure → Retry → Success
4. [ ] Session expiry → Re-login → Continue

---

## 📊 Test Results Template

### Test Summary
- **Tests Completed:** ___/50
- **Tests Passed:** ___/50
- **Critical Issues:** ___
- **Minor Issues:** ___
- **Overall Status:** PASS/FAIL

### Critical Issues Found
1. 
2. 
3. 

### Minor Issues Found
1. 
2. 
3. 

### Recommendations
1. 
2. 
3. 

---

## 🚀 Go-Live Decision

**Ready for Production?** YES / NO

**Criteria for YES:**
- [ ] 95%+ tests pass
- [ ] No critical issues
- [ ] Payment processing works
- [ ] Plan access control works
- [ ] Core user flows complete

**Next Steps:**
- [ ] Fix any critical issues
- [ ] Document known minor issues
- [ ] Prepare deployment
- [ ] Set up monitoring
- [ ] Create user documentation

---

*Testing started: [DATE]*
*Testing completed: [DATE]*
*Tested by: [NAME]*
