<?php
// This script will restore the previous working version of the subscription detection

// 1. First, let's restore the GoCardless financial service file
$gocardlessFile = 'includes/gocardless_financial_service.php';
$backupFile = 'includes/backups/gocardless_financial_service.backup.php';

if (file_exists($backupFile)) {
    if (copy($backupFile, $gocardlessFile)) {
        echo "✅ Successfully restored $gocardlessFile from backup\n";
    } else {
        echo "❌ Failed to restore $gocardlessFile from backup\n";
    }
} else {
    echo "⚠️ Backup file not found: $backupFile\n";
}

// 2. Restore the dashboard file
$dashboardFile = 'dashboard.php';
$dashboardBackup = 'includes/backups/dashboard.backup.php';

if (file_exists($dashboardBackup)) {
    if (copy($dashboardBackup, $dashboardFile)) {
        echo "✅ Successfully restored $dashboardFile from backup\n";
    } else {
        echo "❌ Failed to restore $dashboardFile from backup\n";
    }
} else {
    echo "⚠️ Backup file not found: $dashboardBackup\n";
}

echo "\nRestoration complete. Please check the dashboard to see if your subscriptions are visible now.\n";
echo "If you still don't see your subscriptions, you may need to run a new bank scan.\n\n";

echo "<a href='dashboard.php'>Go to Dashboard</a> | ";
echo "<a href='bank/unified-scan.php'>Run New Bank Scan</a>";

// Add some basic styling
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    .success { color: #2ecc71; }
    .error { color: #e74c3c; }
    .warning { color: #f39c12; }
</style>";
?>
