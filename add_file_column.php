<?php
require_once '../includes/db.php';

try {
    $conn->query("ALTER TABLE messages ADD COLUMN file_path VARCHAR(255) DEFAULT NULL");
    echo "Column 'file_path' added successfully.";
} catch (Exception $e) {
    echo "Column already exists or error: " . $e->getMessage();
}
?>