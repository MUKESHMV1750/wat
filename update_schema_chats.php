<?php
require_once 'includes/db.php';

$queries = [
    "CREATE TABLE IF NOT EXISTS chats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        is_group TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS chat_participants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chat_id INT,
        user_id INT,
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS message_status (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message_id INT,
        user_id INT,
        status ENUM('sent', 'delivered', 'read') DEFAULT 'sent',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "ALTER TABLE messages ADD COLUMN IF NOT EXISTS chat_id INT",
    "ALTER TABLE messages ADD CONSTRAINT fk_chat_id FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE"
];

foreach ($queries as $query) {
    if ($conn->query($query) === TRUE) {
        echo "Successfully executed: " . substr($query, 0, 50) . "...\n";
    } else {
        echo "Error executing query: " . $conn->error . "\n";
    }
}

echo "Database schema updated successfully.\n";
?>