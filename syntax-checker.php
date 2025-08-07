<?php
/**
 * SYNTAX CHECKER - Find PHP syntax errors in key files
 */

echo "<h2>🔧 PHP SYNTAX CHECKER</h2>\n";
echo "<p>Checking key files for syntax errors...</p>\n";

$filesToCheck = [
    'index.php',
    'dashboard.php', 
    'upgrade.php',
    'auth/signin.php',
    'settings.php',
    'payment/success.php',
    'payment/cancel.php'
];

foreach ($filesToCheck as $file) {
    if (file_exists($file)) {
        echo "<h3>Checking: $file</h3>\n";
        
        // Check for basic syntax using php -l equivalent
        $content = file_get_contents($file);
        
        // Check for common issues
        $issues = [];
        
        // Check for mismatched PHP tags
        $phpOpenCount = substr_count($content, '<?php');
        $phpCloseCount = substr_count($content, '?>');
        
        if ($phpOpenCount != $phpCloseCount) {
            $issues[] = "Mismatched PHP tags: $phpOpenCount opening tags, $phpCloseCount closing tags";
        }
        
        // Check for unclosed strings
        $lines = explode("\n", $content);
        foreach ($lines as $lineNum => $line) {
            $realLineNum = $lineNum + 1;
            
            // Skip comments and HTML
            if (strpos(trim($line), '//') === 0 || strpos(trim($line), '#') === 0 || strpos(trim($line), '<!--') === 0) {
                continue;
            }
            
            // Check for unclosed quotes in PHP sections
            if (strpos($line, '<?php') !== false || (strpos($content, '<?php') !== false && strpos($line, '?>') === false)) {
                // Count quotes
                $singleQuotes = substr_count($line, "'");
                $doubleQuotes = substr_count($line, '"');
                
                // Skip escaped quotes (basic check)
                $singleQuotes -= substr_count($line, "\\'");
                $doubleQuotes -= substr_count($line, '\\"');
                
                if ($singleQuotes % 2 != 0) {
                    $issues[] = "Line $realLineNum: Possible unclosed single quote";
                }
                if ($doubleQuotes % 2 != 0) {
                    $issues[] = "Line $realLineNum: Possible unclosed double quote";
                }
            }
        }
        
        // Check for missing semicolons (basic check)
        $phpSections = [];
        preg_match_all('/\<\?php(.*?)\?\>/s', $content, $phpSections);
        foreach ($phpSections[1] as $phpCode) {
            $lines = explode("\n", trim($phpCode));
            foreach ($lines as $lineNum => $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '//') === 0 || strpos($line, '#') === 0) continue;
                
                // Check if line should end with semicolon
                if (!empty($line) && 
                    !in_array(substr($line, -1), ['{', '}', ':', ';']) &&
                    !preg_match('/^\s*(if|else|elseif|while|for|foreach|function|class|switch|case|default|try|catch|finally)\s*\(/i', $line) &&
                    !preg_match('/^\s*(if|else|elseif|while|for|foreach|switch|case|default|try|catch|finally)\s*$/i', $line)) {
                    $issues[] = "Possible missing semicolon: " . substr($line, 0, 50) . "...";
                }
            }
        }
        
        if (empty($issues)) {
            echo "✅ No obvious syntax issues found<br>\n";
        } else {
            echo "❌ Issues found:<br>\n";
            foreach ($issues as $issue) {
                echo "   - $issue<br>\n";
            }
        }
        echo "<br>\n";
    } else {
        echo "❌ File not found: $file<br><br>\n";
    }
}

echo "<h3>🎯 NEXT STEPS</h3>\n";
echo "<p>If issues are found above, they need to be fixed to resolve the 500 errors.</p>\n";
echo "<p>The emergency diagnostic showed that infrastructure is working - it's just syntax errors causing page failures.</p>\n";
?>
