<?php
require_once 'includes/db.php';

$queries = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_online TINYINT(1) DEFAULT 0",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL"
];

foreach ($queries as $query) {
    if ($conn->query($query) === TRUE) {
        echo "Successfully executed: $query\n";
    } else {
        echo "Error executing $query: " . $conn->error . "\n";
    }
}

echo "Database schema updated successfully.\n";
?>