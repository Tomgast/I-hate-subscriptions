<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Header Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mx-auto mt-8 p-4">
        <h1 class="text-2xl font-bold mb-4">Header Test Page</h1>
        <p>Testing header from root directory</p>
        
        <div class="mt-4">
            <h2 class="text-lg font-semibold">Debug Info:</h2>
            <p>Current script: <?php echo $_SERVER['PHP_SELF']; ?></p>
            <p>Is in auth dir: <?php echo $isInAuthDir ? 'Yes' : 'No'; ?></p>
            <p>Path prefix: "<?php echo $pathPrefix; ?>"</p>
        </div>
    </div>
</body>
</html>
