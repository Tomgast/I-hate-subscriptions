<?php
// Basic PHP test
echo "<h1>PHP Test</h1>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";

if ($_POST['test'] ?? false) {
    echo "<div style='background: green; color: white; padding: 10px; margin: 10px 0;'>";
    echo "✅ Form submission works! Button was clicked at " . date('Y-m-d H:i:s');
    echo "</div>";
}

phpinfo();
?>
<!DOCTYPE html>
<html>
<head>
    <title>PHP Test</title>
</head>
<body>
    <h2>Form Test</h2>
    <form method="post">
        <button type="submit" name="test" value="1">Test Form Submission</button>
    </form>
</body>
</html>
