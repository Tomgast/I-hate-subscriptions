# CashControl Migration Plan: Next.js → Static + PHP

## 🎯 Goal
Migrate from Next.js (requires Node.js hosting €200/mo) to Static + PHP solution (works with basic Plesk €3/mo) while preserving ALL functionality.

## 📋 Current Functionality to Preserve
- ✅ Google OAuth Authentication
- ✅ Interactive Dashboard with charts
- ✅ Subscription CRUD operations
- ✅ Email reminders (SMTP configured)
- ✅ Stripe payments integration
- ✅ Bank integration (TrueLayer)
- ✅ MariaDB database (already working)
- ✅ User preferences and settings
- ✅ Export functionality

## 🏗️ New Architecture

### Frontend (Static Files)
- **Technology**: Vanilla JavaScript + HTML/CSS
- **UI Framework**: Tailwind CSS (CDN)
- **Charts**: Chart.js (CDN)
- **Icons**: Lucide icons (CDN)
- **Build**: No build process needed - direct file serving

### Backend (PHP Scripts)
- **Technology**: PHP 8.x (available on basic Plesk)
- **Database**: Existing MariaDB connection
- **Session**: PHP sessions for authentication state
- **Email**: PHP Mailer with existing SMTP
- **API**: RESTful PHP endpoints

### Authentication Flow
1. **Google OAuth**: Client-side OAuth flow
2. **Token Exchange**: PHP script validates and creates session
3. **Session Management**: PHP sessions (server-side)
4. **Security**: CSRF tokens, secure sessions

### Database Integration
- **Connection**: Use existing MariaDB credentials
- **ORM**: Simple PHP PDO wrapper
- **Migrations**: PHP scripts for schema updates
- **Queries**: Direct SQL with prepared statements

## 📁 New File Structure
```
/
├── index.html (landing page)
├── app/
│   ├── dashboard.html
│   ├── subscriptions.html
│   ├── settings.html
│   └── auth/
│       ├── login.html
│       └── callback.html
├── api/
│   ├── auth/
│   │   ├── google-callback.php
│   │   ├── logout.php
│   │   └── session.php
│   ├── subscriptions/
│   │   ├── list.php
│   │   ├── create.php
│   │   ├── update.php
│   │   └── delete.php
│   ├── payments/
│   │   ├── create-checkout.php
│   │   └── webhook.php
│   ├── email/
│   │   ├── send-reminder.php
│   │   └── schedule.php
│   └── database/
│       ├── connection.php
│       └── migrations.php
├── assets/
│   ├── css/
│   │   └── app.css
│   ├── js/
│   │   ├── app.js
│   │   ├── dashboard.js
│   │   ├── auth.js
│   │   └── subscriptions.js
│   └── images/
└── config/
    ├── database.php
    ├── auth.php
    └── email.php
```

## 🔄 Migration Steps

### Phase 1: Core Infrastructure (Day 1)
1. **Database Connection**: Port MariaDB connection to PHP
2. **Authentication System**: Implement Google OAuth + PHP sessions
3. **Basic API Endpoints**: Create essential PHP endpoints
4. **Static Frontend Shell**: Create basic HTML structure

### Phase 2: Dashboard & Subscriptions (Day 2)
1. **Dashboard Page**: Port dashboard with vanilla JS + Chart.js
2. **Subscription Management**: CRUD operations in PHP
3. **User Interface**: Recreate UI with Tailwind CSS
4. **Data Visualization**: Charts and statistics

### Phase 3: Advanced Features (Day 3)
1. **Email System**: Port email functionality to PHP
2. **Stripe Integration**: Client-side Stripe + PHP webhook
3. **Bank Integration**: TrueLayer API integration
4. **Settings & Preferences**: User configuration system

### Phase 4: Testing & Deployment (Day 4)
1. **Testing**: Comprehensive functionality testing
2. **Security Review**: Authentication, CSRF, SQL injection prevention
3. **Performance**: Optimize for basic hosting
4. **Deployment**: Push to GitHub → Plesk

## 🛠️ Technical Implementation

### Google OAuth (Client-Side + PHP)
```javascript
// Frontend: Google OAuth
function initGoogleAuth() {
    google.accounts.id.initialize({
        client_id: 'your-google-client-id',
        callback: handleGoogleCallback
    });
}

function handleGoogleCallback(response) {
    // Send token to PHP for validation
    fetch('/api/auth/google-callback.php', {
        method: 'POST',
        body: JSON.stringify({token: response.credential})
    });
}
```

```php
// Backend: PHP OAuth validation
<?php
// api/auth/google-callback.php
session_start();
$token = json_decode(file_get_contents('php://input'))->token;
// Validate with Google, create user session
$_SESSION['user_id'] = $user_id;
?>
```

### Database Operations
```php
// config/database.php
class Database {
    private $host = '45.82.188.227';
    private $db_name = 'vxmjmwlj_';
    private $username = '123cashcontrol';
    private $password = 'Super-mannetje45';
    
    public function connect() {
        return new PDO("mysql:host={$this->host};dbname={$this->db_name}", 
                      $this->username, $this->password);
    }
}
```

### Frontend Dashboard
```javascript
// assets/js/dashboard.js
class Dashboard {
    async loadSubscriptions() {
        const response = await fetch('/api/subscriptions/list.php');
        const subscriptions = await response.json();
        this.renderChart(subscriptions);
    }
    
    renderChart(data) {
        // Use Chart.js for visualization
        new Chart(ctx, {
            type: 'doughnut',
            data: data
        });
    }
}
```

## 💰 Cost Comparison
- **Current Plan**: €3/month basic Plesk hosting
- **Node.js Alternative**: €200/month (66x more expensive!)
- **Our Solution**: €3/month (same cost, full functionality)

## 🚀 Benefits of This Approach
1. **Cost Effective**: Works with existing €3/month hosting
2. **Full Functionality**: Preserves all current features
3. **Better Performance**: Static files load faster
4. **Easier Maintenance**: No complex build processes
5. **Scalable**: Can handle more users on basic hosting
6. **SEO Friendly**: Static HTML is better for search engines

## 📋 Migration Checklist
- [ ] Set up PHP database connection
- [ ] Implement Google OAuth flow
- [ ] Create subscription management APIs
- [ ] Build dashboard with vanilla JS
- [ ] Port email functionality
- [ ] Integrate Stripe payments
- [ ] Add bank integration
- [ ] Test all functionality
- [ ] Deploy to production

## 🎯 Success Metrics
- ✅ All current features working
- ✅ Same user experience
- ✅ Fast loading times
- ✅ Mobile responsive
- ✅ Secure authentication
- ✅ Cost remains €3/month

This migration will give you the same powerful CashControl app at a fraction of the hosting cost!
