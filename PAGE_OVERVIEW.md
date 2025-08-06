# CashControl - Page Structure & Navigation Overview

## 🏠 **MAIN ENTRY POINTS**

### **index.php** *(Primary Landing Page)*
- **Status**: ✅ Modernized
- **Purpose**: Main homepage with marketing content
- **Links to**:
  - `auth/signup.php` (Start Free button)
  - `auth/signin.php` (Sign In link)
  - `dashboard.php` (Go to Dashboard - if logged in)
  - `upgrade.php` (View Pro Plans)
  - `demo.php` (Try Demo)
- **Used by**: Direct visitors, logo clicks when logged out

### **index-old.html** *(Backup)*
- **Status**: 🗄️ Archived (renamed from index.html)
- **Purpose**: Original static homepage with extensive animations
- **Note**: Kept as backup, not actively used

---

## 🔐 **AUTHENTICATION PAGES**

### **auth/signin.php**
- **Status**: ⚠️ Needs modernization
- **Purpose**: User login page
- **Links to**:
  - `auth/signup.php` (Create account)
  - `auth/google-oauth.php` (Google login)
  - `dashboard.php` (after successful login)
- **Linked from**: Header, index.php

### **auth/signup.php**
- **Status**: ⚠️ Needs modernization
- **Purpose**: User registration page
- **Links to**:
  - `auth/signin.php` (Already have account)
  - `auth/google-oauth.php` (Google signup)
  - `dashboard.php` (after successful signup)
- **Linked from**: Header, index.php

### **auth/google-oauth.php**
- **Status**: ⚠️ Needs review
- **Purpose**: Google OAuth authentication handler
- **Links to**: `dashboard.php` (after OAuth success)

### **auth/google-callback.php**
- **Status**: ⚠️ Backend handler
- **Purpose**: Google OAuth callback processing

### **auth/logout.php**
- **Status**: ✅ Working
- **Purpose**: User logout handler
- **Redirects to**: `index.php`

---

## 📊 **USER DASHBOARD PAGES**

### **dashboard.php** *(Primary Dashboard)*
- **Status**: ✅ Modernized
- **Purpose**: Main user dashboard with subscriptions
- **Links to**:
  - `upgrade.php` (Upgrade to Pro buttons)
  - `bank/connect.php` (Connect Bank - Pro only)
  - `settings.php` (Settings)
  - `analytics.php` (View Analytics - if available)
- **Linked from**: Header, post-login redirects, success pages

### **dashboard-minimal.php**
- **Status**: 🤔 Legacy/Testing
- **Purpose**: Simplified dashboard version
- **Note**: May be used for testing or fallback

### **dashboard_complete.php**
- **Status**: 🤔 Legacy/Testing
- **Purpose**: Full-featured dashboard version

### **dashboard_safe.php**
- **Status**: 🤔 Legacy/Testing
- **Purpose**: Safe mode dashboard

### **simple_dashboard.php**
- **Status**: 🤔 Legacy
- **Purpose**: Basic dashboard implementation
- **Links to**: `upgrade.php`, `analytics.php`, `settings.php`, `bank/connect.php`

---

## ⚙️ **USER MANAGEMENT PAGES**

### **settings.php**
- **Status**: ✅ Modernized
- **Purpose**: User account and preferences settings
- **Links to**:
  - `upgrade.php` (Upgrade to Pro)
- **Linked from**: Header (when logged in), dashboard

### **upgrade.php**
- **Status**: ✅ Modernized (just completed!)
- **Purpose**: Pro plan upgrade page with pricing
- **Links to**:
  - `payment/checkout.php` (Upgrade Now button)
- **Linked from**: Dashboard, settings, header (free users)

---

## 💳 **PAYMENT FLOW PAGES**

### **payment/checkout.php**
- **Status**: ✅ Modernized & Fixed
- **Purpose**: Stripe checkout page for Pro upgrade
- **Links to**:
  - `upgrade.php` (Back button)
  - Stripe Checkout (external)
- **Linked from**: `upgrade.php`

### **payment/success.php**
- **Status**: ✅ Modernized
- **Purpose**: Payment success/failure confirmation
- **Links to**:
  - `dashboard.php` (Go to Dashboard)
  - `bank/connect.php` (Connect Bank)
  - `upgrade.php` (Try Again - if failed)
- **Linked from**: Stripe redirect after payment

### **payment/create-payment-intent.php**
- **Status**: 🔧 Backend API
- **Purpose**: Stripe payment intent creation

---

## 🏦 **BANK INTEGRATION PAGES**

### **bank/connect.php**
- **Status**: ⚠️ Needs modernization
- **Purpose**: Bank account connection interface
- **Note**: Pro feature only
- **Linked from**: Dashboard (Pro users), success page

### **bank/callback.php**
- **Status**: ⚠️ Backend handler
- **Purpose**: Bank connection callback processing

---

## 🎯 **FEATURE PAGES**

### **demo.php**
- **Status**: ⚠️ Needs modernization
- **Purpose**: Product demonstration/preview
- **Linked from**: Header, index.php

### **demo-modern.php**
- **Status**: 🤔 Alternative version
- **Purpose**: Modern demo implementation

### **analytics.php**
- **Status**: 🚫 Skipped (not in active use)
- **Purpose**: User analytics and insights
- **Note**: May be linked from dashboard but not prioritized

---

## 🔧 **API & BACKEND PAGES**

### **API Endpoints** (`api/` folder)
- `api/auth/` - Authentication handlers
- `api/subscriptions/` - Subscription management
- `api/config.php` - Configuration
- `api/export.php` - Data export functionality

### **Configuration Files** (`config/` folder)
- Database, email, and authentication configuration

### **Includes** (`includes/` folder)
- **header.php** ✅ - Modern header component used across all pages
- **stripe_service.php** ✅ - Payment processing
- **email_service.php** ✅ - Email notifications
- **bank_integration.php** - Bank connection services

---

## 🗺️ **NAVIGATION FLOW MAP**

```
🏠 index.php (Landing)
├── 🔐 auth/signin.php → dashboard.php
├── 🔐 auth/signup.php → dashboard.php
├── 🎯 demo.php
└── 💰 upgrade.php → payment/checkout.php → payment/success.php

📊 dashboard.php (Main Hub)
├── ⚙️ settings.php
├── 💰 upgrade.php (if Free user)
├── 🏦 bank/connect.php (if Pro user)
└── 📈 analytics.php (if available)

💳 Payment Flow
upgrade.php → payment/checkout.php → Stripe → payment/success.php → dashboard.php
```

---

## 📋 **MODERNIZATION STATUS**

### ✅ **Completed (Modern & Working)**
- `index.php` - Landing page
- `dashboard.php` - Main dashboard
- `settings.php` - User settings
- `upgrade.php` - Pro upgrade page
- `payment/checkout.php` - Checkout page
- `payment/success.php` - Success page
- `includes/header.php` - Universal header

### ⚠️ **Needs Modernization**
- `auth/signin.php` - Sign in page
- `auth/signup.php` - Sign up page
- `demo.php` - Demo page
- `bank/connect.php` - Bank connection
- `bank/callback.php` - Bank callback

### 🤔 **Review/Cleanup Needed**
- `dashboard-minimal.php` - Legacy dashboard
- `dashboard_complete.php` - Legacy dashboard
- `dashboard_safe.php` - Legacy dashboard
- `simple_dashboard.php` - Legacy dashboard
- `demo-modern.php` - Alternative demo

### 🚫 **Skipped/Not in Use**
- `analytics.php` - Not prioritized
- `index-old.html` - Archived backup

---

## 🔗 **KEY NAVIGATION PATTERNS**

### **Header Navigation (Context-Aware)**
- **Logged Out**: Home, Features, Pricing, Demo, Sign In, Start Free
- **Logged In**: Home, Dashboard, Settings, Upgrade (if Free), User Menu, Logout

### **Logo Behavior**
- **Logged Out**: Links to `index.php`
- **Logged In**: Links to `dashboard.php`

### **Common User Journeys**
1. **New User**: `index.php` → `auth/signup.php` → `dashboard.php`
2. **Returning User**: `index.php` → `auth/signin.php` → `dashboard.php`
3. **Upgrade Flow**: `dashboard.php` → `upgrade.php` → `payment/checkout.php` → `payment/success.php` → `dashboard.php`
4. **Bank Connection**: `dashboard.php` → `bank/connect.php` → `bank/callback.php` → `dashboard.php`

---

## 🎯 **NEXT PRIORITIES**

Based on active usage and user flow importance:

1. **High Priority**: `auth/signin.php`, `auth/signup.php` (critical user entry points)
2. **Medium Priority**: `demo.php`, `bank/connect.php` (feature pages)
3. **Low Priority**: Legacy dashboard variants (cleanup/removal)

This overview shows that the core payment and dashboard functionality is modernized and working, with authentication pages being the next critical modernization target.
