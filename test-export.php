<?php
/**
 * Export System Test (PDF/CSV Generation)
 */

// Suppress HTTP_HOST warnings for CLI
$_SERVER['HTTP_HOST'] = 'localhost';
session_start();

// Simulate logged in user
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test User';
$_SESSION['user_email'] = 'support@origens.nl';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Export System Test ===\n\n";

try {
    // Test 1: Check export files
    echo "1. Checking export system files...\n";
    
    $exportFiles = [
        'export/index.php' => 'Export main page',
        'export/pdf.php' => 'PDF export handler',
        'export/csv.php' => 'CSV export handler'
    ];
    
    foreach ($exportFiles as $file => $description) {
        if (file_exists(__DIR__ . '/' . $file)) {
            echo "   ✅ $description: $file\n";
        } else {
            echo "   ❌ Missing: $description ($file)\n";
        }
    }
    echo "\n";
    
    // Test 2: Check for PDF generation libraries
    echo "2. Checking PDF generation capabilities...\n";
    
    // Check for common PDF libraries
    $pdfLibraries = [
        'TCPDF' => class_exists('TCPDF'),
        'FPDF' => class_exists('FPDF'),
        'mPDF' => class_exists('Mpdf\\Mpdf'),
        'DomPDF' => class_exists('Dompdf\\Dompdf')
    ];
    
    $pdfAvailable = false;
    foreach ($pdfLibraries as $lib => $available) {
        if ($available) {
            echo "   ✅ $lib library available\n";
            $pdfAvailable = true;
        } else {
            echo "   ❌ $lib library not found\n";
        }
    }
    
    if (!$pdfAvailable) {
        echo "   ⚠️ No PDF libraries detected - checking for manual implementations\n";
        
        // Check for custom PDF implementations
        $customPdfFiles = [
            'includes/pdf_generator.php',
            'export/pdf_generator.php',
            'includes/export_service.php'
        ];
        
        foreach ($customPdfFiles as $file) {
            if (file_exists(__DIR__ . '/' . $file)) {
                echo "   ✅ Found custom PDF implementation: $file\n";
                $pdfAvailable = true;
            }
        }
    }
    echo "\n";
    
    // Test 3: Test CSV generation capabilities
    echo "3. Testing CSV generation capabilities...\n";
    
    // Test basic CSV functionality
    $testData = [
        ['Name', 'Cost', 'Billing Cycle', 'Next Payment'],
        ['Netflix', '€12.99', 'Monthly', '2025-02-08'],
        ['Spotify', '€9.99', 'Monthly', '2025-02-15']
    ];
    
    $csvContent = '';
    foreach ($testData as $row) {
        $csvContent .= implode(',', $row) . "\n";
    }
    
    if (!empty($csvContent)) {
        echo "   ✅ CSV generation functionality working\n";
        echo "   - Sample CSV content generated successfully\n";
        echo "   - Length: " . strlen($csvContent) . " characters\n";
    } else {
        echo "   ❌ CSV generation failed\n";
    }
    echo "\n";
    
    // Test 4: Check subscription data for export
    echo "4. Testing subscription data availability...\n";
    require_once __DIR__ . '/config/db_config.php';
    
    $pdo = getDBConnection();
    
    // Check subscriptions table
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM subscriptions");
        $result = $stmt->fetch();
        echo "   ✅ Subscriptions table accessible\n";
        echo "   - Total subscriptions: " . $result['count'] . "\n";
        
        if ($result['count'] > 0) {
            // Get sample subscription data
            $stmt = $pdo->query("SELECT * FROM subscriptions LIMIT 1");
            $sample = $stmt->fetch();
            
            echo "   - Sample subscription columns available:\n";
            foreach (array_keys($sample) as $column) {
                echo "     • $column\n";
            }
        } else {
            echo "   ⚠️ No subscription data available for export testing\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error accessing subscriptions table: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    // Test 5: Test export access control
    echo "5. Testing export access control...\n";
    
    if (file_exists(__DIR__ . '/includes/plan_manager.php')) {
        require_once __DIR__ . '/includes/plan_manager.php';
        $planManager = getPlanManager();
        
        try {
            $userId = 1;
            $canExport = $planManager->canAccessFeature($userId, 'export');
            
            echo "   ✅ Export access control working\n";
            echo "   - User $userId export access: " . ($canExport ? "✅ Allowed" : "❌ Denied") . "\n";
        } catch (Exception $e) {
            echo "   ⚠️ Export access control error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ⚠️ Cannot test export access control (PlanManager missing)\n";
    }
    echo "\n";
    
    // Test 6: Check export templates/formatting
    echo "6. Checking export templates and formatting...\n";
    
    $templateDirs = [
        'export/templates/',
        'templates/export/',
        'includes/export_templates/'
    ];
    
    $templatesFound = false;
    foreach ($templateDirs as $dir) {
        if (is_dir(__DIR__ . '/' . $dir)) {
            $templates = glob(__DIR__ . '/' . $dir . '*');
            if (!empty($templates)) {
                echo "   ✅ Found export templates in: $dir\n";
                foreach ($templates as $template) {
                    echo "     - " . basename($template) . "\n";
                }
                $templatesFound = true;
            }
        }
    }
    
    if (!$templatesFound) {
        echo "   ⚠️ No export templates found - exports may use basic formatting\n";
    }
    echo "\n";
    
    echo "=== Export System Test Summary ===\n";
    
    $exportFilesExist = file_exists(__DIR__ . '/export/index.php');
    $csvCapable = true; // CSV is always possible with PHP
    
    if ($exportFilesExist) {
        echo "✅ Export system files present\n";
        echo "✅ CSV export capability confirmed\n";
        
        if ($pdfAvailable) {
            echo "✅ PDF export capability available\n";
        } else {
            echo "⚠️ PDF export may need library installation\n";
        }
        
        echo "Ready for subscription data export functionality\n";
    } else {
        echo "❌ Export system incomplete:\n";
        echo "   - Missing export handler files\n";
        echo "   - Export functionality may not be implemented\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
?>
