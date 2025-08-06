/**
 * Dashboard JavaScript Module
 * Handles dashboard functionality and data visualization
 */

class Dashboard {
    static subscriptions = [];
    static charts = {};
    
    /**
     * Initialize dashboard
     */
    static async init() {
        try {
            this.showLoading();
            await this.loadSubscriptions();
            this.updateStats();
            this.renderCharts();
            this.renderRecentSubscriptions();
            this.renderUpcomingRenewals();
            this.showContent();
        } catch (error) {
            console.error('Dashboard initialization error:', error);
            this.showError();
        }
    }
    
    /**
     * Load subscriptions from API
     */
    static async loadSubscriptions() {
        try {
            const response = await fetch('/api/subscriptions/list.php');
            const data = await response.json();
            
            if (data.success) {
                this.subscriptions = data.subscriptions;
                this.summary = data.summary;
            } else {
                throw new Error(data.error || 'Failed to load subscriptions');
            }
        } catch (error) {
            console.error('Load subscriptions error:', error);
            throw error;
        }
    }
    
    /**
     * Update dashboard statistics
     */
    static updateStats() {
        if (!this.summary) return;
        
        document.getElementById('total-count').textContent = this.summary.total_count;
        document.getElementById('monthly-total').textContent = `€${this.summary.total_monthly.toFixed(2)}`;
        document.getElementById('yearly-total').textContent = `€${this.summary.total_yearly.toFixed(2)}`;
        document.getElementById('average-monthly').textContent = `€${this.summary.average_monthly.toFixed(2)}`;
    }
    
    /**
     * Render charts
     */
    static renderCharts() {
        this.renderSpendingChart();
        this.renderCategoryChart();
    }
    
    /**
     * Render spending chart
     */
    static renderSpendingChart() {
        const ctx = document.getElementById('spending-chart');
        if (!ctx) return;
        
        // Destroy existing chart
        if (this.charts.spending) {
            this.charts.spending.destroy();
        }
        
        // Prepare data for last 6 months
        const months = [];
        const data = [];
        const now = new Date();
        
        for (let i = 5; i >= 0; i--) {
            const date = new Date(now.getFullYear(), now.getMonth() - i, 1);
            months.push(date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' }));
            
            // Calculate spending for this month
            let monthlySpending = 0;
            this.subscriptions.forEach(sub => {
                const subDate = new Date(sub.next_billing_date);
                if (subDate.getMonth() === date.getMonth() && subDate.getFullYear() === date.getFullYear()) {
                    switch (sub.billing_cycle) {
                        case 'monthly':
                            monthlySpending += sub.cost;
                            break;
                        case 'yearly':
                            monthlySpending += sub.cost / 12;
                            break;
                        case 'weekly':
                            monthlySpending += sub.cost * 4.33;
                            break;
                        case 'daily':
                            monthlySpending += sub.cost * 30;
                            break;
                    }
                }
            });
            data.push(monthlySpending);
        }
        
        this.charts.spending = new Chart(ctx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Monthly Spending',
                    data: data,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '€' + value.toFixed(2);
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
    
    /**
     * Render category chart
     */
    static renderCategoryChart() {
        const ctx = document.getElementById('category-chart');
        if (!ctx) return;
        
        // Destroy existing chart
        if (this.charts.category) {
            this.charts.category.destroy();
        }
        
        // Group subscriptions by category
        const categories = {};
        this.subscriptions.forEach(sub => {
            const category = sub.category || 'Other';
            if (!categories[category]) {
                categories[category] = 0;
            }
            
            // Convert to monthly cost
            let monthlyCost = 0;
            switch (sub.billing_cycle) {
                case 'monthly':
                    monthlyCost = sub.cost;
                    break;
                case 'yearly':
                    monthlyCost = sub.cost / 12;
                    break;
                case 'weekly':
                    monthlyCost = sub.cost * 4.33;
                    break;
                case 'daily':
                    monthlyCost = sub.cost * 30;
                    break;
            }
            
            categories[category] += monthlyCost;
        });
        
        const labels = Object.keys(categories);
        const data = Object.values(categories);
        const colors = [
            '#3B82F6', '#EF4444', '#10B981', '#F59E0B',
            '#8B5CF6', '#F97316', '#06B6D4', '#84CC16'
        ];
        
        this.charts.category = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors.slice(0, labels.length),
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${context.label}: €${value.toFixed(2)} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    /**
     * Render recent subscriptions
     */
    static renderRecentSubscriptions() {
        const container = document.getElementById('recent-subscriptions');
        if (!container) return;
        
        // Sort by creation date (most recent first)
        const recentSubs = [...this.subscriptions]
            .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
            .slice(0, 5);
        
        if (recentSubs.length === 0) {
            container.innerHTML = `
                <li class="px-4 py-4 text-center text-gray-500">
                    <p>No subscriptions yet.</p>
                    <a href="/app/subscriptions.html" class="text-blue-600 hover:text-blue-500">Add your first subscription</a>
                </li>
            `;
            return;
        }
        
        container.innerHTML = recentSubs.map(sub => `
            <li class="px-4 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                <i data-lucide="credit-card" class="h-5 w-5 text-blue-600"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">${sub.name}</div>
                            <div class="text-sm text-gray-500">€${sub.cost.toFixed(2)} per ${sub.billing_cycle}</div>
                        </div>
                    </div>
                    <div class="text-sm text-gray-500">
                        ${this.formatDate(sub.created_at)}
                    </div>
                </div>
            </li>
        `).join('');
        
        // Re-initialize Lucide icons
        lucide.createIcons();
    }
    
    /**
     * Render upcoming renewals
     */
    static renderUpcomingRenewals() {
        const container = document.getElementById('upcoming-renewals');
        if (!container) return;
        
        // Filter subscriptions renewing in next 30 days
        const upcoming = this.subscriptions
            .filter(sub => sub.days_until_renewal >= 0 && sub.days_until_renewal <= 30)
            .sort((a, b) => a.days_until_renewal - b.days_until_renewal)
            .slice(0, 5);
        
        if (upcoming.length === 0) {
            container.innerHTML = `
                <li class="px-4 py-4 text-center text-gray-500">
                    <p>No renewals in the next 30 days.</p>
                </li>
            `;
            return;
        }
        
        container.innerHTML = upcoming.map(sub => {
            const urgencyClass = sub.days_until_renewal <= 3 ? 'text-red-600' : 
                               sub.days_until_renewal <= 7 ? 'text-yellow-600' : 'text-green-600';
            
            return `
                <li class="px-4 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div class="h-10 w-10 rounded-full bg-yellow-100 flex items-center justify-center">
                                    <i data-lucide="clock" class="h-5 w-5 text-yellow-600"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">${sub.name}</div>
                                <div class="text-sm text-gray-500">€${sub.cost.toFixed(2)} per ${sub.billing_cycle}</div>
                            </div>
                        </div>
                        <div class="text-sm ${urgencyClass} font-medium">
                            ${sub.days_until_renewal === 0 ? 'Today' : 
                              sub.days_until_renewal === 1 ? 'Tomorrow' : 
                              `${sub.days_until_renewal} days`}
                        </div>
                    </div>
                </li>
            `;
        }).join('');
        
        // Re-initialize Lucide icons
        lucide.createIcons();
    }
    
    /**
     * Format date for display
     */
    static formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric',
            year: 'numeric'
        });
    }
    
    /**
     * Show loading state
     */
    static showLoading() {
        document.getElementById('loading').classList.remove('hidden');
        document.getElementById('dashboard-content').classList.add('hidden');
        document.getElementById('error-state').classList.add('hidden');
    }
    
    /**
     * Show dashboard content
     */
    static showContent() {
        document.getElementById('loading').classList.add('hidden');
        document.getElementById('dashboard-content').classList.remove('hidden');
        document.getElementById('error-state').classList.add('hidden');
    }
    
    /**
     * Show error state
     */
    static showError() {
        document.getElementById('loading').classList.add('hidden');
        document.getElementById('dashboard-content').classList.add('hidden');
        document.getElementById('error-state').classList.remove('hidden');
    }
}

// Export for use in other scripts
window.Dashboard = Dashboard;
