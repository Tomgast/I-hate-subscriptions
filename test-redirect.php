<?php
// Test Redirect Issue
echo "<h2>🔧 Redirect Test</h2>";

session_start();

echo "<h3>Current Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Test Links:</h3>";
echo "<p><a href='dashboard.php'>Direct Dashboard Link</a></p>";
echo "<p><a href='dashboard-minimal.php'>Minimal Dashboard Test</a></p>";

// Simulate the exact redirect from signin.php
if (isset($_GET['test_redirect'])) {
    echo "<p>Testing redirect to dashboard...</p>";
    header('Location: dashboard.php');
    exit;
}

echo "<p><a href='test-redirect.php?test_redirect=1'>Test Signin Redirect</a></p>";

echo "<h3>Debug Info:</h3>";
echo "<p><strong>Current URL:</strong> " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p><strong>Script Name:</strong> " . $_SERVER['SCRIPT_NAME'] . "</p>";
?>
