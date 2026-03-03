<?php
require_once 'includes/db.php';

try {
    // Add 'role' column if it doesn't exist
    $conn->query("ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user' AFTER email");
    echo "Column 'role' added successfully.<br>";
} catch (Exception $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Column 'role' already exists.<br>";
    } else {
        echo "Error adding 'role' column: " . $e->getMessage() . "<br>";
    }
}

// Set admin user if needed (example)
// $conn->query("UPDATE users SET role='admin' WHERE email='admin@example.com'");

echo "Database update complete. <a href='index.php'>Go to Login</a>";
?>