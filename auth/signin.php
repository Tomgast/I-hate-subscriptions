<?php
session_start();
require_once '../config/db_config.php';

// Handle form submission
if ($_POST) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        try {
            $pdo = getDBConnection();
            
            // Get user by email
            $stmt = $pdo->prepare("SELECT id, email, name, password, is_paid FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Create session
                $sessionToken = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                $stmt = $pdo->prepare("INSERT INTO user_sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$user['id'], $sessionToken, $expiresAt]);
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['is_paid'] = $user['is_paid'];
                $_SESSION['session_token'] = $sessionToken;
                
                // Redirect to dashboard
                header('Location: ../dashboard.php');
                exit;
            } else {
                $error = "Invalid email or password.";
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "Login failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - CashControl</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <div class="auth-container">
        <div class="w-full" style="max-width: 28rem;">
            <!-- Logo and Header -->
            <div class="text-center mb-8">
                <div class="logo-icon">
                    <span class="icon-credit-card" style="font-size: 2rem;"></span>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">Welcome back</h1>
                <p class="text-gray-600 mt-2">Sign in to your CashControl account</p>
            </div>

            <!-- Sign In Form -->
            <div class="auth-card">
                <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <span class="icon-alert mr-2"></span>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email Address
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            class="form-input"
                            placeholder="Enter your email"
                            required
                        >
                    </div>

                    <div class="mb-6">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Password
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input"
                            placeholder="Enter your password"
                            required
                        >
                    </div>

                    <button 
                        type="submit" 
                        class="btn btn-primary w-full"
                    >
                        Sign In
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-gray-600">
                        Don't have an account? 
                        <a href="signup.php" class="text-blue-600 hover:text-blue-700 font-medium">Sign up</a>
                    </p>
                </div>
            </div>

            <!-- Back to Home -->
            <div class="text-center mt-6">
                <a href="../index.html" class="text-gray-600" style="text-decoration: none;">
                    <span class="icon-arrow-left mr-2"></span>
                    Back to Home
                </a>
            </div>
        </div>
    </div>


</body>
</html>
