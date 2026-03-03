<?php
require_once 'includes/db.php';

$sql = "CREATE TABLE IF NOT EXISTS blocked_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    blocked_user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (blocked_user_id) REFERENCES users(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table blocked_users created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}
?>