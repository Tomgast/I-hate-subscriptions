<?php
/**
 * PHASE 4: PAGE AUDIT & BROKEN LINKS DETECTION
 * Comprehensive audit of all pages to identify broken links, dead endpoints, and outdated content
 */

require_once 'config/db_config.php';

class PageAuditor {
    private $baseDir;
    private $results = [];
    private $checkedUrls = [];
    
    public function __construct($baseDir) {
        $this->baseDir = rtrim($baseDir, '/\\');
    }
    
    /**
     * Run comprehensive page audit
     */
    public function runAudit() {
        echo "<h1>🔍 PHASE 4: PAGE AUDIT REPORT</h1>\n";
        echo "<p>Generated on: " . date('Y-m-d H:i:s') . "</p>\n";
        echo "<hr>\n\n";
        
        // 1. Audit main pages
        $this->auditMainPages();
        
        // 2. Audit authentication pages
        $this->auditAuthPages();
        
        // 3. Audit API endpoints
        $this->auditApiEndpoints();
        
        // 4. Audit admin pages
        $this->auditAdminPages();
        
        // 5. Audit new Phase 3 pages
        $this->auditPhase3Pages();
        
        // 6. Check for dead/obsolete files
        $this->checkObsoleteFiles();
        
        // 7. Generate summary
        $this->generateSummary();
    }
    
    /**
     * Audit main application pages
     */
    private function auditMainPages() {
        echo "<h2>📄 MAIN PAGES AUDIT</h2>\n";
        
        $mainPages = [
            'index.php' => 'Homepage',
            'dashboard.php' => 'Main Dashboard',
            'dashboard-onetime.php' => 'One-time Plan Dashboard',
            'upgrade.php' => 'Upgrade/Pricing Page',
            'settings.php' => 'User Settings',
            'demo.php' => 'Demo Page',
            'analytics.php' => 'Analytics Page'
        ];
        
        foreach ($mainPages as $file => $description) {
            $this->auditPage($file, $description, 'main');
        }
        
        echo "\n";
    }
    
    /**
     * Audit authentication pages
     */
    private function auditAuthPages() {
        echo "<h2>🔐 AUTHENTICATION PAGES AUDIT</h2>\n";
        
        $authPages = [
            'auth/signin.php' => 'Sign In Page',
            'auth/signup.php' => 'Sign Up Page',
            'auth/google-oauth.php' => 'Google OAuth Handler',
            'auth/google-callback.php' => 'Google OAuth Callback',
            'auth/logout.php' => 'Logout Handler'
        ];
        
        foreach ($authPages as $file => $description) {
            $this->auditPage($file, $description, 'auth');
        }
        
        echo "\n";
    }
    
    /**
     * Audit API endpoints
     */
    private function auditApiEndpoints() {
        echo "<h2>🔌 API ENDPOINTS AUDIT</h2>\n";
        
        $apiEndpoints = [
            'api/subscriptions.php' => 'Subscriptions API',
            'api/subscriptions/create.php' => 'Create Subscription API',
            'api/subscriptions/list.php' => 'List Subscriptions API',
            'api/export.php' => 'Export API',
            'api/auth/session.php' => 'Session API',
            'api/auth/verify.php' => 'Verification API',
            'api/config.php' => 'Config API'
        ];
        
        foreach ($apiEndpoints as $file => $description) {
            $this->auditPage($file, $description, 'api');
        }
        
        echo "\n";
    }
    
    /**
     * Audit admin pages
     */
    private function auditAdminPages() {
        echo "<h2>⚙️ ADMIN PAGES AUDIT</h2>\n";
        
        $adminPages = [
            'admin/database-admin.php' => 'Database Admin Interface',
            'admin/migrate-pricing.php' => 'Pricing Migration Tool',
            'admin/init-unsubscribe-guides.php' => 'Unsubscribe Guides Initialization'
        ];
        
        foreach ($adminPages as $file => $description) {
            $this->auditPage($file, $description, 'admin');
        }
        
        echo "\n";
    }
    
    /**
     * Audit new Phase 3 pages
     */
    private function auditPhase3Pages() {
        echo "<h2>🆕 PHASE 3 NEW PAGES AUDIT</h2>\n";
        
        $phase3Pages = [
            'bank/scan.php' => 'Bank Scan Controller',
            'bank/connect.php' => 'Bank Connection',
            'bank/callback.php' => 'Bank OAuth Callback',
            'export/index.php' => 'Export Interface',
            'export/pdf.php' => 'PDF Export',
            'export/csv.php' => 'CSV Export',
            'guides/index.php' => 'Unsubscribe Guides',
            'guides/view.php' => 'Individual Guide Viewer',
            'payment/checkout.php' => 'Payment Checkout',
            'payment/success.php' => 'Payment Success',
            'payment/cancel.php' => 'Payment Cancel'
        ];
        
        foreach ($phase3Pages as $file => $description) {
            $this->auditPage($file, $description, 'phase3');
        }
        
        echo "\n";
    }
    
    /**
     * Audit individual page
     */
    private function auditPage($file, $description, $category) {
        $fullPath = $this->baseDir . '/' . $file;
        $status = 'UNKNOWN';
        $issues = [];
        $recommendations = [];
        
        // Check if file exists
        if (!file_exists($fullPath)) {
            $status = 'MISSING';
            $issues[] = 'File does not exist';
        } else {
            $content = file_get_contents($fullPath);
            
            // Basic syntax check
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $syntaxCheck = $this->checkPhpSyntax($fullPath);
                if ($syntaxCheck !== true) {
                    $status = 'SYNTAX_ERROR';
                    $issues[] = 'PHP syntax error: ' . $syntaxCheck;
                } else {
                    $status = 'EXISTS';
                }
                
                // Check for common issues
                $this->checkCommonIssues($content, $file, $issues, $recommendations);
            } else {
                $status = 'EXISTS';
            }
        }
        
        // Store result
        $this->results[$category][$file] = [
            'description' => $description,
            'status' => $status,
            'issues' => $issues,
            'recommendations' => $recommendations
        ];
        
        // Display result
        $statusIcon = $this->getStatusIcon($status);
        echo "  {$statusIcon} <strong>{$file}</strong> - {$description}\n";
        
        if (!empty($issues)) {
            foreach ($issues as $issue) {
                echo "    ❌ {$issue}\n";
            }
        }
        
        if (!empty($recommendations)) {
            foreach ($recommendations as $rec) {
                echo "    💡 {$rec}\n";
            }
        }
        
        echo "\n";
    }
    
    /**
     * Check PHP syntax
     */
    private function checkPhpSyntax($file) {
        $output = [];
        $return_var = 0;
        exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $return_var);
        
        if ($return_var !== 0) {
            return implode("\n", $output);
        }
        
        return true;
    }
    
    /**
     * Check for common issues in file content
     */
    private function checkCommonIssues($content, $file, &$issues, &$recommendations) {
        // Check for old business model references
        if (strpos($content, 'free') !== false && strpos($content, 'trial') !== false) {
            $issues[] = 'Contains references to old free/trial model';
            $recommendations[] = 'Update to reflect premium-only business model';
        }
        
        // Check for hardcoded credentials
        if (preg_match('/password\s*=\s*["\'][^"\']+["\']/', $content)) {
            $issues[] = 'May contain hardcoded credentials';
            $recommendations[] = 'Use secure config system';
        }
        
        // Check for old Supabase references
        if (strpos($content, 'supabase') !== false) {
            $issues[] = 'Contains Supabase references (should use MariaDB)';
            $recommendations[] = 'Update to use MariaDB/PHP backend';
        }
        
        // Check for missing plan manager integration
        if (strpos($file, 'dashboard') !== false || strpos($file, 'bank') !== false || strpos($file, 'export') !== false) {
            if (strpos($content, 'plan_manager') === false && strpos($content, 'PlanManager') === false) {
                $issues[] = 'Missing plan manager integration';
                $recommendations[] = 'Add plan-based access control';
            }
        }
        
        // Check for missing session checks
        if (strpos($content, 'session_start()') === false && strpos($file, 'api') === false && strpos($file, 'auth') === false) {
            if (strpos($content, '$_SESSION') !== false) {
                $issues[] = 'Uses sessions but missing session_start()';
                $recommendations[] = 'Add session_start() at the beginning';
            }
        }
        
        // Check for broken includes
        preg_match_all('/(?:include|require)(?:_once)?\s*["\']([^"\']+)["\']/', $content, $matches);
        foreach ($matches[1] as $includePath) {
            $fullIncludePath = dirname($this->baseDir . '/' . $file) . '/' . $includePath;
            if (!file_exists($fullIncludePath)) {
                $issues[] = "Broken include: {$includePath}";
                $recommendations[] = "Fix include path or create missing file";
            }
        }
        
        // Check for old pricing references
        if (preg_match('/\$29|\$25|trial|free tier/i', $content)) {
            $issues[] = 'Contains old pricing or trial references';
            $recommendations[] = 'Update to new pricing: €3/month, €25/year, €25 one-time';
        }
    }
    
    /**
     * Check for obsolete files
     */
    private function checkObsoleteFiles() {
        echo "<h2>🗑️ OBSOLETE FILES CHECK</h2>\n";
        
        $obsoletePatterns = [
            '*-old.*',
            '*-backup.*',
            '*-test.*',
            'debug-*',
            'test-*',
            '*_old.*',
            '*_backup.*',
            '*_test.*'
        ];
        
        $obsoleteFiles = [];
        
        foreach ($obsoletePatterns as $pattern) {
            $files = glob($this->baseDir . '/' . $pattern);
            foreach ($files as $file) {
                $relativePath = str_replace($this->baseDir . '/', '', $file);
                if (!in_array($relativePath, $obsoleteFiles)) {
                    $obsoleteFiles[] = $relativePath;
                }
            }
        }
        
        if (empty($obsoleteFiles)) {
            echo "  ✅ No obvious obsolete files found\n";
        } else {
            echo "  ⚠️ Found " . count($obsoleteFiles) . " potentially obsolete files:\n";
            foreach ($obsoleteFiles as $file) {
                echo "    📄 {$file}\n";
            }
            echo "  💡 Consider reviewing and removing obsolete files\n";
        }
        
        echo "\n";
    }
    
    /**
     * Generate audit summary
     */
    private function generateSummary() {
        echo "<h2>📊 AUDIT SUMMARY</h2>\n";
        
        $totalPages = 0;
        $statusCounts = [
            'EXISTS' => 0,
            'MISSING' => 0,
            'SYNTAX_ERROR' => 0,
            'UNKNOWN' => 0
        ];
        $totalIssues = 0;
        $totalRecommendations = 0;
        
        foreach ($this->results as $category => $pages) {
            foreach ($pages as $file => $data) {
                $totalPages++;
                $statusCounts[$data['status']]++;
                $totalIssues += count($data['issues']);
                $totalRecommendations += count($data['recommendations']);
            }
        }
        
        echo "  📈 <strong>STATISTICS:</strong>\n";
        echo "    • Total pages audited: {$totalPages}\n";
        echo "    • Pages existing: {$statusCounts['EXISTS']}\n";
        echo "    • Missing pages: {$statusCounts['MISSING']}\n";
        echo "    • Syntax errors: {$statusCounts['SYNTAX_ERROR']}\n";
        echo "    • Total issues found: {$totalIssues}\n";
        echo "    • Total recommendations: {$totalRecommendations}\n\n";
        
        // Priority recommendations
        echo "  🎯 <strong>PRIORITY ACTIONS:</strong>\n";
        
        if ($statusCounts['MISSING'] > 0) {
            echo "    1. HIGH: Fix {$statusCounts['MISSING']} missing pages\n";
        }
        
        if ($statusCounts['SYNTAX_ERROR'] > 0) {
            echo "    2. HIGH: Fix {$statusCounts['SYNTAX_ERROR']} syntax errors\n";
        }
        
        if ($totalIssues > 0) {
            echo "    3. MEDIUM: Address {$totalIssues} content/integration issues\n";
        }
        
        echo "    4. LOW: Review and implement {$totalRecommendations} recommendations\n\n";
        
        // Category breakdown
        echo "  📂 <strong>BY CATEGORY:</strong>\n";
        foreach ($this->results as $category => $pages) {
            $categoryIssues = 0;
            foreach ($pages as $data) {
                $categoryIssues += count($data['issues']);
            }
            echo "    • " . ucfirst($category) . ": " . count($pages) . " pages, {$categoryIssues} issues\n";
        }
        
        echo "\n";
        echo "<hr>\n";
        echo "<p><strong>Next Steps:</strong> Review issues above and proceed with fixes in order of priority.</p>\n";
    }
    
    /**
     * Get status icon
     */
    private function getStatusIcon($status) {
        switch ($status) {
            case 'EXISTS': return '✅';
            case 'MISSING': return '❌';
            case 'SYNTAX_ERROR': return '🔥';
            default: return '❓';
        }
    }
}

// Run the audit
$auditor = new PageAuditor(__DIR__);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Phase 4 Page Audit Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1 { color: #2563eb; }
        h2 { color: #059669; border-bottom: 2px solid #e5e7eb; padding-bottom: 5px; }
        pre { background: #f9fafb; padding: 15px; border-radius: 5px; white-space: pre-wrap; }
        .issue { color: #dc2626; }
        .recommendation { color: #059669; }
        hr { margin: 30px 0; }
    </style>
</head>
<body>
    <pre>
<?php $auditor->runAudit(); ?>
    </pre>
</body>
</html>
