<?php
/**
 * PHASE 4: PAGE CLEANUP AUDIT
 * Identifies obsolete, duplicate, and unused pages for cleanup
 */

// Define core active pages that should be kept
$corePages = [
    // Main application pages
    'index.php',
    'dashboard.php',
    'dashboard-onetime.php',
    'upgrade.php',
    'settings.php',
    'demo.php',
    
    // Authentication
    'auth/signin.php',
    'auth/signup.php',
    'auth/logout.php',
    'auth/google-callback.php',
    'auth/google-oauth.php',
    
    // Payment system
    'payment/success.php',
    'payment/cancel.php',
    
    // Bank integration
    'bank/scan.php',
    'bank/connect.php',
    'bank/callback.php',
    
    // Export system
    'export/index.php',
    'export/pdf.php',
    'export/csv.php',
    
    // Guides system
    'guides/index.php',
    'guides/view.php',
    
    // Admin tools
    'admin/init-unsubscribe-guides.php',
    
    // Configuration and includes
    'config/db_config.php',
    'config/secure_loader.php',
    'includes/header.php',
    'includes/plan_manager.php',
    'includes/stripe_service.php',
    'includes/bank_service.php',
    'includes/unsubscribe_service.php',
    
    // Testing (can be removed in production)
    'test/test-connections.php',
    'phase4-page-audit.php',
    'phase4-cleanup-audit.php'
];

// Define patterns for obsolete/test files
$obsoletePatterns = [
    '*-old.*',
    '*-backup.*',
    '*-test.*',
    '*-debug.*',
    '*-temp.*',
    'simple_*',
    'demo-modern.php',
    'dashboard_*',
    'settings-*',
    'index-*',
    'test-*',
    'debug-*',
    'cleanup_*',
    'fix_*',
    'check_*',
    'email_audit.php',
    'analytics.php'
];

echo "<!DOCTYPE html>\n<html>\n<head>\n<title>Phase 4: Page Cleanup Audit</title>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .obsolete{color:#dc2626;} .keep{color:#059669;} .review{color:#d97706;}</style>\n</head>\n<body>\n";
echo "<h1>🧹 Phase 4: Page Cleanup Audit</h1>\n";
echo "<p>Analysis of pages to keep, review, or remove from the CashControl codebase.</p>\n";

// Scan for all PHP and HTML files
function getAllFiles($dir, $extensions = ['php', 'html']) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $extension = strtolower($file->getExtension());
            if (in_array($extension, $extensions)) {
                $relativePath = str_replace($dir . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $relativePath = str_replace('\\', '/', $relativePath);
                $files[] = $relativePath;
            }
        }
    }
    
    return $files;
}

try {
    $rootDir = __DIR__;
    $allFiles = getAllFiles($rootDir);
    
    // Categorize files
    $keepFiles = [];
    $obsoleteFiles = [];
    $reviewFiles = [];
    
    foreach ($allFiles as $file) {
        $isCore = in_array($file, $corePages);
        $isObsolete = false;
        
        // Check against obsolete patterns
        foreach ($obsoletePatterns as $pattern) {
            if (fnmatch($pattern, basename($file))) {
                $isObsolete = true;
                break;
            }
        }
        
        // Skip certain directories
        if (strpos($file, 'vendor/') === 0 || 
            strpos($file, 'node_modules/') === 0 || 
            strpos($file, '.git/') === 0) {
            continue;
        }
        
        if ($isCore) {
            $keepFiles[] = $file;
        } elseif ($isObsolete) {
            $obsoleteFiles[] = $file;
        } else {
            $reviewFiles[] = $file;
        }
    }
    
    // Display results
    echo "<h2>📊 Summary</h2>\n";
    echo "<ul>\n";
    echo "<li><strong class='keep'>" . count($keepFiles) . " files</strong> to keep (core functionality)</li>\n";
    echo "<li><strong class='obsolete'>" . count($obsoleteFiles) . " files</strong> identified as obsolete</li>\n";
    echo "<li><strong class='review'>" . count($reviewFiles) . " files</strong> need manual review</li>\n";
    echo "</ul>\n";
    
    // Core files to keep
    echo "<h2>✅ Core Files to Keep (" . count($keepFiles) . ")</h2>\n";
    echo "<div style='background:#f0fdf4;padding:15px;border-left:4px solid #059669;margin:10px 0;'>\n";
    echo "<p>These files are essential for the application and should be kept:</p>\n";
    echo "<ul>\n";
    foreach ($keepFiles as $file) {
        echo "<li class='keep'>$file</li>\n";
    }
    echo "</ul>\n</div>\n";
    
    // Obsolete files
    echo "<h2>🗑️ Obsolete Files to Remove (" . count($obsoleteFiles) . ")</h2>\n";
    echo "<div style='background:#fef2f2;padding:15px;border-left:4px solid #dc2626;margin:10px 0;'>\n";
    echo "<p>These files appear to be obsolete and can likely be removed:</p>\n";
    echo "<ul>\n";
    foreach ($obsoleteFiles as $file) {
        echo "<li class='obsolete'>$file</li>\n";
    }
    echo "</ul>\n</div>\n";
    
    // Files needing review
    echo "<h2>🔍 Files Needing Review (" . count($reviewFiles) . ")</h2>\n";
    echo "<div style='background:#fffbeb;padding:15px;border-left:4px solid #d97706;margin:10px 0;'>\n";
    echo "<p>These files need manual review to determine if they should be kept or removed:</p>\n";
    echo "<ul>\n";
    foreach ($reviewFiles as $file) {
        echo "<li class='review'>$file</li>\n";
    }
    echo "</ul>\n</div>\n";
    
    // Recommendations
    echo "<h2>💡 Cleanup Recommendations</h2>\n";
    echo "<div style='background:#f8fafc;padding:15px;border:1px solid #e2e8f0;margin:10px 0;'>\n";
    echo "<h3>Immediate Actions:</h3>\n";
    echo "<ol>\n";
    echo "<li><strong>Archive obsolete files:</strong> Move " . count($obsoleteFiles) . " obsolete files to an 'archive' folder</li>\n";
    echo "<li><strong>Review API files:</strong> Many API files may be from old architecture</li>\n";
    echo "<li><strong>Consolidate dashboards:</strong> Multiple dashboard variants should be unified</li>\n";
    echo "<li><strong>Remove test files:</strong> Clean up development/testing files</li>\n";
    echo "<li><strong>Update navigation:</strong> Ensure no links point to removed files</li>\n";
    echo "</ol>\n";
    
    echo "<h3>Priority Cleanup Areas:</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>Dashboard files:</strong> dashboard_*.php, simple_dashboard.php, etc.</li>\n";
    echo "<li><strong>Old index files:</strong> index-*.html files</li>\n";
    echo "<li><strong>Debug/test files:</strong> debug-*.php, test-*.php files</li>\n";
    echo "<li><strong>Settings variants:</strong> settings-*.php files</li>\n";
    echo "<li><strong>Demo variants:</strong> demo-*.php files</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    
    // Generate cleanup script
    echo "<h2>🛠️ Automated Cleanup Script</h2>\n";
    echo "<div style='background:#f1f5f9;padding:15px;border:1px solid #cbd5e1;margin:10px 0;'>\n";
    echo "<p>The following files can be safely archived:</p>\n";
    echo "<textarea style='width:100%;height:200px;font-family:monospace;'>\n";
    echo "# Create archive directory\n";
    echo "mkdir -p archive/obsolete-pages\n\n";
    echo "# Move obsolete files to archive\n";
    foreach ($obsoleteFiles as $file) {
        echo "mv \"$file\" \"archive/obsolete-pages/\"\n";
    }
    echo "</textarea>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div style='background:#fef2f2;padding:15px;border-left:4px solid #dc2626;margin:10px 0;'>\n";
    echo "<h3>❌ Error during audit:</h3>\n";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "</div>\n";
}

echo "<hr><p><em>Audit completed at " . date('Y-m-d H:i:s') . "</em></p>\n";
echo "</body></html>";
?>
