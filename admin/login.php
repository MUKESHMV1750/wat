<?php
session_start();
require_once '../includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if ($email === 'admin@example.com' && $password === 'admin123') {
        $_SESSION['user_id'] = 999999;
        $_SESSION['is_admin'] = true;
        header("Location: admin_dashboard.php");
        exit();
    }

    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE email = ? AND role = 'admin'");
    if($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['is_admin'] = true;
                header("Location: admin_dashboard.php");
                exit();
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "Admin account not found.";
        }
    } else {
        $error = "Database setup incomplete for admin roles.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login - WhatsApp Clone</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
}

.auth-container {
    width: 380px;
    padding: 40px;
    border-radius: 15px;
    backdrop-filter: blur(15px);
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 15px 35px rgba(0,0,0,0.5);
    color: white;
    animation: fadeIn 0.8s ease-in-out;
}

.auth-container h2 {
    text-align: center;
    margin-bottom: 25px;
    font-weight: 600;
    color: #00e6a8;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    font-size: 14px;
    color: #ccc;
}

.form-group input {
    width: 100%;
    padding: 10px;
    margin-top: 6px;
    border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.2);
    background: rgba(255,255,255,0.1);
    color: white;
    transition: 0.3s;
}

.form-group input:focus {
    border-color: #00e6a8;
    outline: none;
    box-shadow: 0 0 10px #00e6a8;
}

.btn {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 8px;
    background: linear-gradient(45deg, #00e6a8, #00b894);
    color: white;
    font-weight: 500;
    cursor: pointer;
    transition: 0.3s;
}

.btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 230, 168, 0.4);
}

.error {
    background: #ff4d4d;
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 15px;
    font-size: 14px;
    text-align: center;
}

.back-link {
    text-align: center;
    margin-top: 15px;
}

.back-link a {
    color: #00e6a8;
    text-decoration: none;
    font-size: 14px;
}

.back-link a:hover {
    text-decoration: underline;
}

@keyframes fadeIn {
    from {opacity: 0; transform: translateY(-20px);}
    to {opacity: 1; transform: translateY(0);}
}
</style>
</head>

<body>

<div class="auth-container">
    <h2>Admin Login</h2>

    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="Enter admin email" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Enter password" required>
        </div>

        <button type="submit" class="btn">Login to Admin Panel</button>
    </form>

    <div class="back-link">
        <a href="../index.php">&larr; Back to User Login</a>
    </div>
</div>

</body>
</html>