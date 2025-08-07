<?php
// Determine if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$logoLink = $isLoggedIn ? 'dashboard.php' : 'index.php';
$logoOnClick = $isLoggedIn ? '' : 'onclick="scrollToTop()"';

// Get current page for navigation highlighting
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>

<!-- Navigation -->
<nav class="bg-white/95 backdrop-blur-md shadow-sm border-b border-gray-100 sticky top-0 z-50 relative overflow-hidden">
    <!-- Flowing waves background -->
    <div class="absolute inset-0 opacity-5">
        <svg class="absolute inset-0 w-full h-full" viewBox="0 0 1200 60" preserveAspectRatio="none">
            <path d="M0,30 Q300,10 600,30 T1200,30 L1200,0 L0,0 Z" fill="#10b981" class="wave-1">
                <animateTransform attributeName="transform" type="translate" values="-1200,0;0,0;1200,0" dur="20s" repeatCount="indefinite"/>
            </path>
            <path d="M0,40 Q400,20 800,40 T1600,40 L1600,0 L0,0 Z" fill="#059669" class="wave-2">
                <animateTransform attributeName="transform" type="translate" values="-1600,0;0,0;1600,0" dur="25s" repeatCount="indefinite"/>
            </path>
            <path d="M0,20 Q200,5 400,20 T800,20 L800,0 L0,0 Z" fill="#34d399" class="wave-3">
                <animateTransform attributeName="transform" type="translate" values="-800,0;0,0;800,0" dur="15s" repeatCount="indefinite"/>
            </path>
        </svg>
    </div>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center space-x-3">
                <a href="<?php echo $logoLink; ?>" class="flex items-center space-x-2 hover:scale-105 transition-transform duration-200" <?php echo $logoOnClick; ?>>
                    <img src="assets/images/logo.svg" alt="CashControl" class="h-8">
                </a>
            </div>
            
            <?php if (!$isLoggedIn): ?>
            <div class="hidden md:flex items-center space-x-8">
                <a href="index.php#features" class="text-gray-600 hover:text-green-600 font-medium transition-colors duration-200 relative group">
                    Features
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-green-500 group-hover:w-full transition-all duration-200"></span>
                </a>
                <a href="index.php#pricing" class="text-gray-600 hover:text-green-600 font-medium transition-colors duration-200 relative group">
                    Pricing
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-green-500 group-hover:w-full transition-all duration-200"></span>
                </a>
                <a href="demo.php" class="text-gray-600 hover:text-green-600 font-medium transition-colors duration-200 relative group <?php echo $currentPage === 'demo' ? 'text-green-600' : ''; ?>">
                    Demo
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-green-500 group-hover:w-full transition-all duration-200 <?php echo $currentPage === 'demo' ? 'w-full' : ''; ?>"></span>
                </a>
            </div>
            
            <div class="flex items-center space-x-4">
                <a href="auth/signin.php" class="text-gray-600 hover:text-green-600 px-3 py-2 rounded-md font-medium transition-colors duration-200">
                    Sign In
                </a>
                <a href="auth/signup.php" class="gradient-bg text-white px-6 py-2 rounded-lg font-medium shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                    Start Free
                </a>
            </div>
            <?php else: ?>
            <div class="hidden md:flex items-center space-x-8">
                <a href="index.php" class="text-gray-600 hover:text-green-600 font-medium transition-colors duration-200 relative group <?php echo $currentPage === 'index' ? 'text-green-600' : ''; ?>">
                    Home
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-green-500 group-hover:w-full transition-all duration-200 <?php echo $currentPage === 'index' ? 'w-full' : ''; ?>"></span>
                </a>
                <a href="dashboard.php" class="text-gray-600 hover:text-green-600 font-medium transition-colors duration-200 relative group <?php echo $currentPage === 'dashboard' ? 'text-green-600' : ''; ?>">
                    Dashboard
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-green-500 group-hover:w-full transition-all duration-200 <?php echo $currentPage === 'dashboard' ? 'w-full' : ''; ?>"></span>
                </a>
                <a href="settings.php" class="text-gray-600 hover:text-green-600 font-medium transition-colors duration-200 relative group <?php echo $currentPage === 'settings' ? 'text-green-600' : ''; ?>">
                    Settings
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-green-500 group-hover:w-full transition-all duration-200 <?php echo $currentPage === 'settings' ? 'w-full' : ''; ?>"></span>
                </a>
                <?php if (!($_SESSION['is_paid'] ?? false)): ?>
                <a href="upgrade.php" class="text-gray-600 hover:text-green-600 font-medium transition-colors duration-200 relative group <?php echo $currentPage === 'upgrade' ? 'text-green-600' : ''; ?>">
                    Upgrade
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-green-500 group-hover:w-full transition-all duration-200 <?php echo $currentPage === 'upgrade' ? 'w-full' : ''; ?>"></span>
                </a>
                <?php endif; ?>
            </div>
            
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-700">Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                <?php if (!($_SESSION['is_paid'] ?? false)): ?>
                    <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs font-medium">Free</span>
                <?php else: ?>
                    <span class="bg-green-100 text-green-600 px-2 py-1 rounded text-xs font-medium">Pro</span>
                <?php endif; ?>
                <a href="auth/logout.php" class="text-gray-500 hover:text-red-500 transition-colors duration-200" title="Logout">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<style>
.gradient-bg {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}
</style>

<?php if (!$isLoggedIn): ?>
<script>
function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>
<?php endif; ?>
