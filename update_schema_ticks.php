<?php
require_once 'includes/db.php';

function columnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

try {
    // 1. Add 'status' column if missing
    if (!columnExists($conn, 'messages', 'status')) {
        $conn->query("ALTER TABLE messages ADD COLUMN status ENUM('sent','delivered','read') DEFAULT 'sent'");
        echo "Added 'status' column.<br>";
    }

    // 2. Add 'file_original_name' column if missing
    if (!columnExists($conn, 'messages', 'file_original_name')) {
        $conn->query("ALTER TABLE messages ADD COLUMN file_original_name VARCHAR(255) DEFAULT NULL");
        echo "Added 'file_original_name' column.<br>";
    }

    // 3. Add 'file_type' column if missing
    if (!columnExists($conn, 'messages', 'file_type')) {
        $conn->query("ALTER TABLE messages ADD COLUMN file_type VARCHAR(100) DEFAULT NULL");
        echo "Added 'file_type' column.<br>";
    }

    // 4. Add 'file_size' column if missing
    if (!columnExists($conn, 'messages', 'file_size')) {
        $conn->query("ALTER TABLE messages ADD COLUMN file_size INT DEFAULT NULL");
        echo "Added 'file_size' column.<br>";
    }

    echo "Schema check completed.<br>";

} catch (Exception $e) {
    echo "Schema Update Error: " . $e->getMessage() . "<br>";
}

// 5. Create directories
$dirs = [
    'uploads/chat_files', // ensure this exists
    'uploads/profile'
];

foreach ($dirs as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0777, true)) {
            echo "Created directory: $dir<br>";
        } else {
            echo "Failed to create directory: $dir<br>";
        }
    }
}

echo "Setup Complete.";
?>
        if (mkdir($dir, 0777, true)) {
            echo "Created directory: $dir<br>";
        } else {
            echo "Failed to create directory: $dir<br>";
        }
    }
}

echo "Database and Folder structure setup complete. <a href='index.php'>Go Home</a>";
?>