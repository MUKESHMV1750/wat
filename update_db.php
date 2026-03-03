<?php
require_once 'includes/db.php';

// Add role column if it doesn't exist
$columns = [
    "role" => "ALTER TABLE users ADD COLUMN role ENUM('user','admin') DEFAULT 'user'",
    "about" => "ALTER TABLE users ADD COLUMN about TEXT",
    "profile_pos_x" => "ALTER TABLE users ADD COLUMN profile_pos_x INT DEFAULT 50",
    "profile_pos_y" => "ALTER TABLE users ADD COLUMN profile_pos_y INT DEFAULT 50"
];

foreach ($columns as $col => $sql) {
    try {
        $conn->query($sql);
        echo "Column '$col' added or already exists.<br>";
    } catch (Exception $e) {
        // Check if error is 'Duplicate column name' (1060)
        if ($conn->errno == 1060) {
             echo "Column '$col' already exists.<br>";
        } else {
             echo "Error adding column '$col': " . $e->getMessage() . "<br>";
        }
    }
}

echo "You can now go back to <a href='index.php'>Login Page</a>";
?>