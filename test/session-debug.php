<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Debug - CashControl</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-2xl mx-auto py-12 px-4">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">🔍 Session Debug</h1>
            
            <div class="space-y-6">
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Session Status</h2>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
                            ✅ User is logged in
                        </div>
                        
                        <div class="space-y-2 text-sm">
                            <p><strong>User ID:</strong> <?= htmlspecialchars($_SESSION['user_id']) ?></p>
                            <p><strong>User Name:</strong> <?= htmlspecialchars($_SESSION['user_name'] ?? 'Not set') ?></p>
                            <p><strong>User Email:</strong> <?= htmlspecialchars($_SESSION['user_email'] ?? 'Not set') ?></p>
                            <p><strong>Is Premium:</strong> <?= isset($_SESSION['is_premium']) && $_SESSION['is_premium'] ? 'Yes' : 'No' ?></p>
                            <p><strong>Is Paid:</strong> <?= isset($_SESSION['is_paid']) && $_SESSION['is_paid'] ? 'Yes' : 'No' ?></p>
                        </div>
                        
                        <div class="mt-6">
                            <a href="../payment/checkout.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 inline-block">
                                Try Stripe Payment
                            </a>
                        </div>
                        
                    <?php else: ?>
                        <div class="bg-red-100 text-red-800 p-3 rounded mb-4">
                            ❌ User is NOT logged in
                        </div>
                        
                        <p class="text-gray-600 mb-4">You need to log in first to test payments.</p>
                        
                        <div class="space-y-4">
                            <a href="../auth/google-callback.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 inline-block">
                                Sign in with Google
                            </a>
                            
                            <p class="text-sm text-gray-500">Or go to the test connections page and use Google OAuth sign-in</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="border rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">All Session Data</h2>
                    <pre class="bg-gray-100 p-4 rounded text-sm overflow-auto"><?= htmlspecialchars(print_r($_SESSION, true)) ?></pre>
                </div>
            </div>
            
            <div class="mt-8 text-center">
                <a href="test-connections.php" class="bg-indigo-500 text-white px-6 py-3 rounded-lg hover:bg-indigo-600 inline-block">
                    Back to Connection Tests
                </a>
            </div>
        </div>
    </div>
</body>
</html>
