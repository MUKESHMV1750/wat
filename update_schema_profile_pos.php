<?php
require_once 'includes/db.php';

$sql = "ALTER TABLE users ADD COLUMN profile_pos_x INT DEFAULT 50, ADD COLUMN profile_pos_y INT DEFAULT 50";

if ($conn->query($sql) === TRUE) {
    echo "Columns added successfully";
} else {
    echo "Error adding columns: " . $conn->error;
}
?>