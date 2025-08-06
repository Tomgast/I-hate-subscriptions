<?php
session_start();
require_once '../config/db_config.php';

// Handle form submission
if ($_POST) {
    $email = trim($_POST['email'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($email) || empty($name) || empty($password) || empty($confirmPassword)) {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        try {
            $pdo = getDBConnection();
            
            // Check if user already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = "An account with this email already exists.";
            } else {
                // Create new user
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO users (email, name, password, is_paid) VALUES (?, ?, ?, ?)");
                $stmt->execute([$email, $name, $hashedPassword, false]);
                
                $userId = $pdo->lastInsertId();
                
                // Create session
                $sessionToken = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                $stmt = $pdo->prepare("INSERT INTO user_sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$userId, $sessionToken, $expiresAt]);
                
                // Set session variables
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = $name;
                $_SESSION['is_paid'] = false;
                $_SESSION['session_token'] = $sessionToken;
                
                // Redirect to dashboard
                header('Location: ../dashboard.php');
                exit;
            }
        } catch (Exception $e) {
            error_log("Signup error: " . $e->getMessage());
            $error = "Account creation failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - CashControl</title>
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
                <h1 class="text-3xl font-bold text-gray-900">Create Account</h1>
                <p class="text-gray-600 mt-2">Start tracking your subscriptions today</p>
            </div>

            <!-- Sign Up Form -->
            <div class="auth-card">
                <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <span class="icon-alert mr-2"></span>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Full Name
                        </label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                            class="form-input"
                            placeholder="Enter your full name"
                            required
                        >
                    </div>

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

                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Password
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input"
                            placeholder="Create a password (min. 6 characters)"
                            required
                            minlength="6"
                        >
                    </div>

                    <div class="mb-6">
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                            Confirm Password
                        </label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="form-input"
                            placeholder="Confirm your password"
                            required
                        >
                    </div>

                    <button 
                        type="submit" 
                        class="btn btn-primary w-full"
                    >
                        Create Account
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-gray-600">
                        Already have an account? 
                        <a href="signin.php" class="text-blue-600 hover:text-blue-700 font-medium">Sign in</a>
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
