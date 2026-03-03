<?php
require_once 'includes/db.php';

$sql = "CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'admins' created successfully.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Check if admin exists
$result = $conn->query("SELECT * FROM admins WHERE email = 'admin@example.com'");
if ($result->num_rows == 0) {
    // Password is 'password'
    $password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    $sql_insert = "INSERT INTO admins (name, email, password) VALUES ('Super Admin', 'admin@example.com', '$password')";
    
    if ($conn->query($sql_insert) === TRUE) {
        echo "Default admin user inserted successfully.<br>";
        echo "Email: admin@example.com<br>";
        echo "Password: password<br>";
    } else {
        echo "Error inserting admin: " . $conn->error . "<br>";
    }
} else {
    echo "Admin user already exists.<br>";
}

echo "<br><a href='index.php'>Go to Login</a>";
?>