<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Add columns for soft delete
$sql1 = "ALTER TABLE messages ADD COLUMN deleted_for_sender TINYINT(1) DEFAULT 0";
$sql2 = "ALTER TABLE messages ADD COLUMN deleted_for_receiver TINYINT(1) DEFAULT 0";

if ($conn->query($sql1) === TRUE) {
    echo "Column deleted_for_sender added successfully<br>";
} else {
    echo "Error adding column deleted_for_sender: " . $conn->error . "<br>";
}

if ($conn->query($sql2) === TRUE) {
    echo "Column deleted_for_receiver added successfully<br>";
} else {
    echo "Error adding column deleted_for_receiver: " . $conn->error . "<br>";
}
?>
