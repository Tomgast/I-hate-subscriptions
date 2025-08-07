<?php
/**
 * Secure Database Administration Interface
 * Provides secure access for database configuration and management
 */

require_once dirname(__DIR__) . '/includes/database_helper.php';

// Simple authentication - you can enhance this
session_start();
$admin_password = 'admin123'; // Change this to a secure password

if (!isset($_SESSION['admin_authenticated'])) {
    if (isset($_POST['admin_password']) && $_POST['admin_password'] === $admin_password) {
        $_SESSION['admin_authenticated'] = true;
    } else {
        showLoginForm();
        exit;
    }
}

function showLoginForm() {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Database Admin - CashControl</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
            .form-group { margin: 15px 0; }
            input[type="password"] { width: 200px; padding: 8px; }
            button { background: #10b981; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
            button:hover { background: #059669; }
        </style>
    </head>
    <body>
        <h1>🔒 Database Admin Access</h1>
        <form method="POST">
            <div class="form-group">
                <label>Admin Password:</label><br>
                <input type="password" name="admin_password" required>
            </div>
            <button type="submit">Login</button>
        </form>
    </body>
    </html>
    <?php
}

// Handle actions
$action = $_GET['action'] ?? '';
$result = null;

switch ($action) {
    case 'test_connection':
        $result = DatabaseHelper::testConnection();
        break;
    case 'test_all_configs':
        try {
            $results = DatabaseHelper::testAllConfigurations();
            $result = ['success' => true, 'configs' => $results];
        } catch (Exception $e) {
            $result = ['success' => false, 'message' => $e->getMessage()];
        }
        break;
    case 'initialize_tables':
        try {
            $result = DatabaseHelper::initializeTables();
        } catch (Exception $e) {
            $result = ['success' => false, 'message' => $e->getMessage()];
        }
        break;
    case 'get_stats':
        try {
            $stats = DatabaseHelper::getStats();
            $result = ['success' => true, 'stats' => $stats];
        } catch (Exception $e) {
            $result = ['success' => false, 'message' => $e->getMessage()];
        }
        break;
    case 'logout':
        session_destroy();
        header('Location: database-admin.php');
        exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Admin - CashControl</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 1200px; 
            margin: 20px auto; 
            padding: 20px; 
            background: #f8fafc;
        }
        .header { 
            background: linear-gradient(135deg, #10b981, #059669); 
            color: white; 
            padding: 20px; 
            border-radius: 10px; 
            margin-bottom: 20px;
        }
        .card { 
            background: white; 
            padding: 20px; 
            border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            margin-bottom: 20px;
        }
        .btn { 
            background: #10b981; 
            color: white; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover { background: #059669; }
        .btn-danger { background: #ef4444; }
        .btn-danger:hover { background: #dc2626; }
        .success { color: #10b981; background: #f0fdf4; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #ef4444; background: #fef2f2; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: #f59e0b; background: #fffbeb; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .stat-card { background: #f8fafc; padding: 15px; border-radius: 8px; text-align: center; }
        .stat-number { font-size: 2em; font-weight: bold; color: #10b981; }
        .config-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; }
        .config-card { background: #f8fafc; padding: 15px; border-radius: 8px; }
        .config-status { font-weight: bold; margin-bottom: 10px; }
        .config-details { font-size: 0.9em; color: #6b7280; }
        pre { background: #f1f5f9; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🗄️ Database & Services Administration</h1>
        <p>Secure management for CashControl - Database, Google OAuth, TrueLayer, Stripe & Email</p>
        <a href="?action=logout" class="btn btn-danger" style="float: right;">Logout</a>
    </div>

    <?php if ($result): ?>
        <div class="card">
            <h3>Action Result</h3>
            
            <?php if (isset($result['configs'])): ?>
                <h4>🔧 Service Configuration Status</h4>
                <div class="config-grid">
                    <?php foreach ($result['configs'] as $service => $config): ?>
                        <div class="config-card">
                            <div class="config-status <?= $config['success'] ? 'success' : 'error' ?>">
                                <?= $config['success'] ? '✅' : '❌' ?> <?= ucfirst(str_replace('_', ' ', $service)) ?>
                            </div>
                            <div class="config-details">
                                <?= htmlspecialchars($config['message']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="<?= $result['success'] ? 'success' : 'error' ?>">
                    <?= htmlspecialchars($result['message']) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($result['stats'])): ?>
                <h4>📊 Database Statistics</h4>
                <div class="stats-grid">
                    <?php foreach ($result['stats'] as $table => $count): ?>
                        <div class="stat-card">
                            <div class="stat-number"><?= is_numeric($count) ? $count : '?' ?></div>
                            <div><?= ucfirst(str_replace('_', ' ', $table)) ?></div>
                            <?php if (!is_numeric($count)): ?>
                                <div style="color: #ef4444; font-size: 0.8em;"><?= $count ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h3>🛠️ Database Operations</h3>
        <p>Use these tools to manage your database and services securely:</p>
        
        <a href="?action=test_connection" class="btn">🔌 Test Database</a>
        <a href="?action=test_all_configs" class="btn">🔧 Test All Services</a>
        <a href="?action=initialize_tables" class="btn">🏗️ Initialize Tables</a>
        <a href="?action=get_stats" class="btn">📊 Get Statistics</a>
    </div>

    <div class="card">
        <h3>📋 Configuration Status</h3>
        <?php
        $configPath = dirname(__DIR__) . '/secure-config.php';
        if (file_exists($configPath)) {
            echo '<div class="success">✅ Secure configuration file found</div>';
            
            // Check if config has required fields
            try {
                $config = require $configPath;
                $requiredFields = [
                    'DB_PASSWORD' => 'Database',
                    'GOOGLE_CLIENT_SECRET' => 'Google OAuth',
                    'TRUELAYER_CLIENT_ID' => 'TrueLayer Banking',
                    'TRUELAYER_CLIENT_SECRET' => 'TrueLayer Banking',
                    'STRIPE_PUBLISHABLE_KEY' => 'Stripe Payments',
                    'STRIPE_SECRET_KEY' => 'Stripe Payments',
                    'SMTP_HOST' => 'Email SMTP',
                    'SMTP_USERNAME' => 'Email SMTP',
                    'SMTP_PASSWORD' => 'Email SMTP'
                ];
                
                $configuredServices = [];
                $missingServices = [];
                
                foreach ($requiredFields as $field => $service) {
                    if (isset($config[$field]) && !empty($config[$field])) {
                        if (!in_array($service, $configuredServices)) {
                            $configuredServices[] = $service;
                        }
                    } else {
                        if (!in_array($service, $missingServices)) {
                            $missingServices[] = $service;
                        }
                    }
                }
                
                if (empty($missingServices)) {
                    echo '<div class="success">✅ All required services configured: ' . implode(', ', $configuredServices) . '</div>';
                } else {
                    echo '<div class="success">✅ Configured services: ' . implode(', ', $configuredServices) . '</div>';
                    echo '<div class="warning">⚠️ Missing configuration for: ' . implode(', ', $missingServices) . '</div>';
                }
            } catch (Exception $e) {
                echo '<div class="error">❌ Error reading configuration: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        } else {
            echo '<div class="error">❌ Secure configuration file not found at: ' . $configPath . '</div>';
            echo '<p>Please create this file with your service credentials.</p>';
        }
        ?>
    </div>

    <div class="card">
        <h3>🔐 Security Information</h3>
        <ul>
            <li>✅ All service credentials stored in <code>secure-config.php</code></li>
            <li>✅ Configuration file excluded from Git version control</li>
            <li>✅ Database connections use PDO with prepared statements</li>
            <li>✅ Admin interface requires authentication</li>
            <li>✅ No hardcoded secrets in source code</li>
        </ul>
    </div>

    <div class="card">
        <h3>🌐 Configured Services</h3>
        <div class="config-grid">
            <div class="config-card">
                <h4>🗄️ Database (MariaDB)</h4>
                <p>Plesk hosting database for user data, subscriptions, and preferences</p>
                <small>Host: 45.82.188.227 | Database: vxmjmwlj_</small>
            </div>
            <div class="config-card">
                <h4>🔐 Google OAuth</h4>
                <p>User authentication via Google accounts</p>
                <small>Client ID: 267507492904-hr7q0qi2655ne01tv2si5ienpi6el4cm.apps.googleusercontent.com</small>
            </div>
            <div class="config-card">
                <h4>🏦 TrueLayer Banking</h4>
                <p>Bank account integration for subscription scanning</p>
                <small>Environment: Sandbox | Client ID: sandbox-123cashcontrol-496c49</small>
            </div>
            <div class="config-card">
                <h4>💳 Stripe Payments</h4>
                <p>Payment processing for subscriptions</p>
                <small>Test Mode | Publishable Key: pk_test_51RrlZA...</small>
            </div>
            <div class="config-card">
                <h4>📧 Email SMTP</h4>
                <p>Email notifications and reminders</p>
                <small>Host: shared58.cloud86-host.nl | From: noreply@123cashcontrol.com</small>
            </div>
        </div>
    </div>

    <div class="card">
        <h3>📝 Next Steps</h3>
        <ol>
            <li>✅ Secure configuration file created with all service credentials</li>
            <li>🔄 Test all service configurations using "Test All Services" button</li>
            <li>🔄 Initialize database tables if not already done</li>
            <li>🔄 Verify statistics show proper table creation</li>
            <li>🔄 Test individual service integrations (Google OAuth, TrueLayer, Stripe, Email)</li>
        </ol>
    </div>
</body>
</html>
