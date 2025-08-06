        <!-- Pro Features Section -->
        <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Available Features</h2>
                <?php if (!$isPaid): ?>
                <span class="text-sm text-gray-500">Upgrade to unlock Pro features</span>
                <?php endif; ?>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Manual Add (Free Feature) -->
                <button onclick="openAddModal()" class="flex items-center p-4 border-2 border-green-200 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                    <div class="p-2 bg-green-100 rounded-lg mr-3">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </div>
                    <div class="text-left">
                        <p class="font-medium text-green-900">Add Subscription</p>
                        <p class="text-sm text-green-600">Manual entry (Free)</p>
                    </div>
                </button>
                
                <!-- Bank Integration (Pro Feature) -->
                <?php if ($isPaid): ?>
                <button onclick="startBankScan()" class="flex items-center p-4 border-2 border-blue-200 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                    <div class="p-2 bg-blue-100 rounded-lg mr-3">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                    </div>
                    <div class="text-left">
                        <p class="font-medium text-blue-900">Bank Scan</p>
                        <p class="text-sm text-blue-600">Auto-discover subscriptions</p>
                    </div>
                </button>
                <?php else: ?>
                <a href="upgrade.php" class="flex items-center p-4 border-2 border-gray-300 bg-gray-50 rounded-lg opacity-75 cursor-pointer hover:opacity-100 transition-opacity">
                    <div class="p-2 bg-gray-100 rounded-lg mr-3">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                    </div>
                    <div class="text-left">
                        <p class="font-medium text-gray-700">Bank Scan 🔒</p>
                        <p class="text-sm text-orange-600">Pro feature - Upgrade to unlock</p>
                    </div>
                </a>
                <?php endif; ?>
                
                <!-- Email Notifications (Pro Feature) -->
                <?php if ($isPaid): ?>
                <a href="settings.php" class="flex items-center p-4 border-2 border-purple-200 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                    <div class="p-2 bg-purple-100 rounded-lg mr-3">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="text-left">
                        <p class="font-medium text-purple-900">Email Reminders</p>
                        <p class="text-sm text-purple-600">Configure notifications</p>
                    </div>
                </a>
                <?php else: ?>
                <a href="upgrade.php" class="flex items-center p-4 border-2 border-gray-300 bg-gray-50 rounded-lg opacity-75 cursor-pointer hover:opacity-100 transition-opacity">
                    <div class="p-2 bg-gray-100 rounded-lg mr-3">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="text-left">
                        <p class="font-medium text-gray-700">Email Reminders 🔒</p>
                        <p class="text-sm text-orange-600">Pro feature - Upgrade to unlock</p>
                    </div>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Subscriptions List -->
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold text-gray-900">Your Subscriptions</h2>
                <div class="flex items-center space-x-3">
                    <select id="categoryFilter" class="border border-gray-300 rounded-md px-3 py-2 text-sm" onchange="filterSubscriptions()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['name']); ?>">
                            <?php echo $category['icon']; ?> <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button onclick="openAddModal()" class="bg-green-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-green-700 transition-colors">
                        Add Subscription
                    </button>
                </div>
            </div>
            
            <?php if (empty($subscriptions)): ?>
            <div class="text-center py-12">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No subscriptions yet</h3>
                <p class="text-gray-500 mb-6">Get started by adding your first subscription manually or with bank integration</p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <button onclick="openAddModal()" class="bg-green-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-green-700 transition-colors">
                        Add Manually (Free)
                    </button>
                    <?php if (!$isPaid): ?>
                    <a href="upgrade.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                        Upgrade for Bank Scan
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="subscriptionsList">
                <?php foreach ($subscriptions as $subscription): ?>
                <div class="subscription-card border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow" data-category="<?php echo htmlspecialchars($subscription['category']); ?>">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center">
                            <span class="text-2xl mr-3">
                                <?php 
                                $categoryIcon = '📦';
                                foreach ($categories as $cat) {
                                    if ($cat['name'] === $subscription['category']) {
                                        $categoryIcon = $cat['icon'];
                                        break;
                                    }
                                }
                                echo $categoryIcon;
                                ?>
                            </span>
                            <div>
                                <h4 class="font-semibold text-gray-900"><?php echo htmlspecialchars($subscription['name']); ?></h4>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($subscription['category']); ?></p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button onclick="editSubscription(<?php echo $subscription['id']; ?>)" class="text-gray-400 hover:text-blue-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            <button onclick="deleteSubscription(<?php echo $subscription['id']; ?>)" class="text-gray-400 hover:text-red-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-500">Cost:</span>
                            <span class="font-semibold text-gray-900">€<?php echo number_format($subscription['cost'], 2); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-500">Billing:</span>
                            <span class="text-sm text-gray-700 capitalize"><?php echo htmlspecialchars($subscription['billing_cycle']); ?></span>
                        </div>
                        <?php if ($subscription['next_payment_date']): ?>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-500">Next payment:</span>
                            <span class="text-sm text-gray-700"><?php echo date('M j, Y', strtotime($subscription['next_payment_date'])); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-500">Status:</span>
                            <span class="text-sm px-2 py-1 rounded-full <?php echo $subscription['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                <?php echo ucfirst($subscription['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Subscription Modal -->
    <div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Add New Subscription</h3>
                <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_subscription">
                
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Service Name</label>
                        <input type="text" id="name" name="name" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="e.g., Netflix, Spotify">
                    </div>
                    
                    <div>
                        <label for="cost" class="block text-sm font-medium text-gray-700 mb-1">Cost (€)</label>
                        <input type="number" id="cost" name="cost" step="0.01" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="9.99">
                    </div>
                    
                    <div>
                        <label for="billing_cycle" class="block text-sm font-medium text-gray-700 mb-1">Billing Cycle</label>
                        <select id="billing_cycle" name="billing_cycle" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                            <option value="weekly">Weekly</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select id="category" name="category" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['name']); ?>">
                                <?php echo $category['icon']; ?> <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="flex space-x-3 mt-6">
                    <button type="button" onclick="closeAddModal()" class="flex-1 bg-gray-200 text-gray-800 px-4 py-2 rounded-lg font-medium hover:bg-gray-300 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-green-700 transition-colors">
                        Add Subscription
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.remove('hidden');
            document.getElementById('addModal').classList.add('flex');
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.add('hidden');
            document.getElementById('addModal').classList.remove('flex');
        }

        function filterSubscriptions() {
            const filter = document.getElementById('categoryFilter').value;
            const cards = document.querySelectorAll('.subscription-card');
            
            cards.forEach(card => {
                if (filter === '' || card.dataset.category === filter) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function deleteSubscription(id) {
            if (confirm('Are you sure you want to delete this subscription?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_subscription">
                    <input type="hidden" name="subscription_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function editSubscription(id) {
            // For now, just show an alert - you can implement edit modal later
            alert('Edit functionality coming soon! For now, you can delete and re-add the subscription.');
        }

        function startBankScan() {
            <?php if ($isPaid): ?>
            alert('Bank scan feature coming soon! This will connect to your bank to automatically discover subscriptions.');
            <?php else: ?>
            window.location.href = 'upgrade.php';
            <?php endif; ?>
        }

        // Close modal when clicking outside
        document.getElementById('addModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddModal();
            }
        });
    </script>
</body>
</html>
