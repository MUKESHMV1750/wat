<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    
    // Update status to offline
    $update_stmt = $conn->prepare("UPDATE users SET status = 'offline', last_seen = NOW() WHERE id = ?");
    $update_stmt->bind_param("i", $user_id);
    $update_stmt->execute();

    session_destroy();
}

header("Location: index.php");
exit();
?>