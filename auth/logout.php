<?php
session_start();
require_once '../config/db_config.php';

// Delete session from database if exists
if (isset($_SESSION['session_token'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE session_token = ?");
        $stmt->execute([$_SESSION['session_token']]);
    } catch (Exception $e) {
        error_log("Logout error: " . $e->getMessage());
    }
}

// Destroy session
session_destroy();

// Redirect to home page
header('Location: ../index.php');
exit;
?>
