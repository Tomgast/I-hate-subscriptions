<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
    header('Location: auth/signin.php');
    exit;
}

$userName = $_SESSION['user_name'] ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';
$isPaid = $_SESSION['is_paid'] ?? false;
$userId = $_SESSION['user_id'];

// Initialize variables with safe defaults
$subscriptions = [];
$stats = [
    'total_active' => 0,
    'monthly_total' => 0,
    'yearly_total' => 0,
    'next_payment' => null,
    'category_breakdown' => []
];
$upcomingPayments = [];
$categories = [];
$error = null;

// Try to load data safely
try {
    require_once 'config/db_config.php';
    $pdo = getDBConnection();
    
    // Verify session token
    $stmt = $pdo->prepare("SELECT user_id FROM user_sessions WHERE session_token = ? AND expires_at > NOW()");
    $stmt->execute([$_SESSION['session_token']]);
    
    if (!$stmt->fetch()) {
        // Invalid or expired session
        session_destroy();
        header('Location: auth/signin.php');
        exit;
    }
    
    // Try to load subscription data
    try {
        require_once 'includes/subscription_manager.php';
        $subscriptionManager = new SubscriptionManager();
        
        $subscriptions = $subscriptionManager->getUserSubscriptions($userId);
        $stats = $subscriptionManager->getSubscriptionStats($userId);
        $upcomingPayments = $subscriptionManager->getUpcomingPayments($userId, 7);
        $categories = $subscriptionManager->getCategories();
    } catch (Exception $e) {
        $error = "Data loading error: " . $e->getMessage();
        // Continue with empty data
    }
    
} catch (Exception $e) {
    $error = "Database connection error: " . $e->getMessage();
    // Continue with empty data
}
$stats = $subscriptionManager->getSubscriptionStats($userId);
$upcomingPayments = $subscriptionManager->getUpcomingPayments($userId, 7);
$categories = $subscriptionManager->getCategories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CashControl</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <div class="container py-6">
        <!-- Welcome Section -->
        <div class="card mb-6">
            <div class="p-5">
                <div class="flex items-center">
                    <div style="flex-shrink: 0;">
                        <span class="icon-user" style="font-size: 3rem; color: #2563eb;"></span>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-2xl font-bold text-gray-900">Welcome back, <?php echo htmlspecialchars($userName); ?>!</h1>
                        <p class="text-gray-600">Manage your subscriptions and track your spending</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-3 gap-6 mb-6">
            <div class="card">
                <div class="stat-card">
                    <div class="flex items-center">
                        <div style="flex-shrink: 0;">
                            <span class="icon-credit-card stat-icon" style="color: #2563eb;"></span>
                        </div>
                        <div class="ml-5" style="flex: 1;">
                            <div class="text-sm font-medium text-gray-500">Active Subscriptions</div>
                            <div class="text-lg font-medium text-gray-900"><?php echo $stats['total_active']; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="stat-card">
                    <div class="flex items-center">
                        <div style="flex-shrink: 0;">
                            <span class="icon-euro stat-icon" style="color: #059669;"></span>
                        </div>
                        <div class="ml-5" style="flex: 1;">
                            <div class="text-sm font-medium text-gray-500">Monthly Total</div>
                            <div class="text-lg font-medium text-gray-900">€<?php echo number_format($stats['monthly_total'], 2); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="stat-card">
                    <div class="flex items-center">
                        <div style="flex-shrink: 0;">
                            <span class="icon-calendar stat-icon" style="color: #ea580c;"></span>
                        </div>
                        <div class="ml-5" style="flex: 1;">
                            <div class="text-sm font-medium text-gray-500">Next Payment</div>
                            <div class="text-lg font-medium text-gray-900">
                                <?php if ($stats['next_payment']): ?>
                                    <?php echo date('M j', strtotime($stats['next_payment']['next_payment_date'])); ?>
                                <?php else: ?>
                                    None
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mb-6">
            <div class="p-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Quick Actions</h3>
                    <button onclick="openAddSubscriptionModal()" class="btn btn-primary">
                        <span class="icon-plus mr-2"></span>
                        Add Subscription
                    </button>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <button onclick="openAddSubscriptionModal()" class="flex items-center p-4 border border-gray-300 rounded-lg" style="background: white; cursor: pointer;">
                        <span class="icon-plus mr-3" style="color: #2563eb;"></span>
                        <span class="text-sm font-medium text-gray-900">Add Subscription</span>
                    </button>
                    
                    <?php if (!$isPaid): ?>
                    <a href="upgrade.php" class="flex items-center p-4 border border-blue-300 bg-blue-50 rounded-lg" style="cursor: pointer; text-decoration: none;">
                        <span class="icon-star mr-3" style="color: #2563eb;"></span>
                        <span class="text-sm font-medium text-blue-900">Upgrade to Pro</span>
                    </a>
                    <?php endif; ?>
                    
                    <a href="settings.php" class="flex items-center p-4 border border-gray-300 rounded-lg" style="background: white; cursor: pointer; text-decoration: none;">
                        <span class="icon-settings mr-3" style="color: #4b5563;"></span>
                        <span class="text-sm font-medium text-gray-900">Settings</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Upcoming Payments -->
        <?php if (!empty($upcomingPayments)): ?>
        <div class="card mb-6">
            <div class="p-5">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Upcoming Payments (Next 7 Days)</h3>
                <div class="space-y-3">
                    <?php foreach ($upcomingPayments as $payment): ?>
                    <div class="flex items-center justify-between p-3 bg-orange-50 rounded-lg border border-orange-200">
                        <div class="flex items-center">
                            <span class="icon-calendar mr-3" style="color: #ea580c;"></span>
                            <div>
                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($payment['name']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo date('M j, Y', strtotime($payment['next_payment_date'])); ?></div>
                            </div>
                        </div>
                        <div class="text-lg font-semibold text-orange-600">
                            €<?php echo number_format($payment['cost'], 2); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Subscriptions List -->
        <div class="card">
            <div class="p-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Your Subscriptions</h3>
                    <div class="flex items-center space-x-2">
                        <select id="categoryFilter" class="form-input" style="width: auto; padding: 0.5rem;">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['name']); ?>">
                                <?php echo $category['icon']; ?> <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <?php if (empty($subscriptions)): ?>
                <div class="text-center py-12">
                    <span class="icon-inbox" style="font-size: 3rem; color: #9ca3af; display: block; margin: 0 auto 1rem;"></span>
                    <h3 class="text-sm font-medium text-gray-900 mb-2">No subscriptions yet</h3>
                    <p class="text-sm text-gray-500 mb-4">Get started by adding your first subscription</p>
                    <button onclick="openAddSubscriptionModal()" class="btn btn-primary">
                        <span class="icon-plus mr-2"></span>
                        Add Your First Subscription
                    </button>
                </div>
                <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="subscriptionsList">
                    <?php foreach ($subscriptions as $subscription): ?>
                    <div class="subscription-card border border-gray-200 rounded-lg p-4" data-category="<?php echo htmlspecialchars($subscription['category']); ?>">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center">
                                <?php if ($subscription['category_icon']): ?>
                                <span style="font-size: 1.5rem; margin-right: 0.5rem;"><?php echo $subscription['category_icon']; ?></span>
                                <?php endif; ?>
                                <div>
                                    <h4 class="font-semibold text-gray-900"><?php echo htmlspecialchars($subscription['name']); ?></h4>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($subscription['category']); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-1">
                                <button onclick="editSubscription(<?php echo $subscription['id']; ?>)" class="text-gray-400 hover:text-blue-600">
                                    <span class="icon-settings"></span>
                                </button>
                                <button onclick="deleteSubscription(<?php echo $subscription['id']; ?>)" class="text-gray-400 hover:text-red-600">
                                    ❌
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="text-2xl font-bold text-gray-900">
                                €<?php echo number_format($subscription['cost'], 2); ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                per <?php echo $subscription['billing_cycle']; ?>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Next payment:</span>
                            <span class="font-medium">
                                <?php echo date('M j, Y', strtotime($subscription['next_payment_date'])); ?>
                            </span>
                        </div>
                        
                        <?php if ($subscription['description']): ?>
                        <div class="mt-2 text-sm text-gray-600">
                            <?php echo htmlspecialchars($subscription['description']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <button onclick="recordPayment(<?php echo $subscription['id']; ?>)" class="btn btn-secondary" style="width: 100%; font-size: 0.875rem; padding: 0.5rem;">
                                Mark as Paid
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add/Edit Subscription Modal -->
    <div id="subscriptionModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div class="modal-content" style="background: white; margin: 5% auto; padding: 2rem; width: 90%; max-width: 500px; border-radius: 0.5rem; position: relative;">
            <div class="flex justify-between items-center mb-4">
                <h2 id="modalTitle" class="text-xl font-bold text-gray-900">Add Subscription</h2>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600" style="font-size: 1.5rem;">&times;</button>
            </div>
            
            <form id="subscriptionForm">
                <input type="hidden" id="subscriptionId" name="id">
                
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Service Name</label>
                    <input type="text" id="name" name="name" class="form-input" placeholder="Netflix, Spotify, etc." required>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="cost" class="block text-sm font-medium text-gray-700 mb-2">Cost</label>
                        <input type="number" id="cost" name="cost" class="form-input" step="0.01" placeholder="9.99" required>
                    </div>
                    <div>
                        <label for="billing_cycle" class="block text-sm font-medium text-gray-700 mb-2">Billing Cycle</label>
                        <select id="billing_cycle" name="billing_cycle" class="form-input">
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                            <option value="weekly">Weekly</option>
                            <option value="daily">Daily</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select id="category" name="category" class="form-input">
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['name']); ?>">
                                <?php echo $category['icon']; ?> <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="next_payment_date" class="block text-sm font-medium text-gray-700 mb-2">Next Payment</label>
                        <input type="date" id="next_payment_date" name="next_payment_date" class="form-input" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description (Optional)</label>
                    <textarea id="description" name="description" class="form-input" rows="3" placeholder="Additional notes..."></textarea>
                </div>
                
                <div class="mb-6">
                    <label for="website_url" class="block text-sm font-medium text-gray-700 mb-2">Website (Optional)</label>
                    <input type="url" id="website_url" name="website_url" class="form-input" placeholder="https://netflix.com">
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Subscription</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function openAddSubscriptionModal() {
            document.getElementById('modalTitle').textContent = 'Add Subscription';
            document.getElementById('subscriptionForm').reset();
            document.getElementById('subscriptionId').value = '';
            
            // Set default next payment date to next month
            const nextMonth = new Date();
            nextMonth.setMonth(nextMonth.getMonth() + 1);
            document.getElementById('next_payment_date').value = nextMonth.toISOString().split('T')[0];
            
            document.getElementById('subscriptionModal').style.display = 'block';
        }
        
        function editSubscription(id) {
            // This would fetch subscription data and populate the form
            document.getElementById('modalTitle').textContent = 'Edit Subscription';
            document.getElementById('subscriptionId').value = id;
            document.getElementById('subscriptionModal').style.display = 'block';
            
            // TODO: Fetch and populate subscription data
        }
        
        function closeModal() {
            document.getElementById('subscriptionModal').style.display = 'none';
        }
        
        function deleteSubscription(id) {
            if (confirm('Are you sure you want to delete this subscription?')) {
                window.location.href = 'api/subscriptions.php?action=delete&id=' + id;
            }
        }
        
        function recordPayment(id) {
            if (confirm('Mark this subscription as paid and update the next payment date?')) {
                window.location.href = 'api/subscriptions.php?action=record_payment&id=' + id;
            }
        }
        
        // Category filter
        document.getElementById('categoryFilter').addEventListener('change', function() {
            const selectedCategory = this.value;
            const subscriptionCards = document.querySelectorAll('.subscription-card');
            
            subscriptionCards.forEach(card => {
                if (selectedCategory === '' || card.dataset.category === selectedCategory) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
        
        // Form submission
        document.getElementById('subscriptionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const action = document.getElementById('subscriptionId').value ? 'update' : 'add';
            
            fetch('api/subscriptions.php?action=' + action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error saving subscription');
                console.error('Error:', error);
            });
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('subscriptionModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>

</body>
</html>
