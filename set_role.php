<?php
require_once 'includes/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $role = $_POST['role'];
    
    // Check if user exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE email = ?");
        $stmt->bind_param("ss", $role, $email);
        
        if ($stmt->execute()) {
            $message = "Success! User <b>$email</b> is now a <b>$role</b>.";
        } else {
            $message = "Error updating database: " . $conn->error;
        }
    } else {
        $message = "User with email '$email' not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set User Role</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f0f2f5; }
        .container { max-width: 400px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #128C7E; }
        input, select, button { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background: #128C7E; color: white; border: none; cursor: pointer; font-size: 16px; font-weight: bold; }
        button:hover { background: #075E54; }
        .message { padding: 10px; margin-bottom: 20px; border-radius: 4px; background: #d1e7dd; color: #0f5132; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Change User Role</h2>
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        <form method="POST">
            <label>User Email:</label>
            <input type="email" name="email" placeholder="Enter user's email" required>
            
            <label>Select Role:</label>
            <select name="role">
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>
            
            <button type="submit">Update Role</button>
        </form>
        <p style="text-align:center;"><a href="index.php">Go to Login</a></p>
    </div>
</body>
</html>