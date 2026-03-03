<?php
require_once 'includes/db.php';

try {
    // Add 'is_approved' column if it doesn't exist
    // Default 0 (Pending), but let's set existing users to 1 (Approved) so we don't break access.
    $conn->query("ALTER TABLE users ADD COLUMN is_approved TINYINT(1) DEFAULT 0");
    $conn->query("UPDATE users SET is_approved = 1"); 
    echo "Column 'is_approved' added and existing users approved.<br>";
} catch (Exception $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Column 'is_approved' already exists.<br>";
    } else {
        echo "Error adding column: " . $e->getMessage() . "<br>";
    }
}
echo "Database update complete. <a href='admin/admin_dashboard.php'>Go to Dashboard</a>";
?>