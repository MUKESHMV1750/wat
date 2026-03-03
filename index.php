<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

redirectIfLoggedIn();

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Hardcoded Admin Check (For Demo/Testing)
    if ($email === 'admin@example.com' && $password === 'admin123') {
        $_SESSION['user_id'] = 999999;
        $_SESSION['role'] = 'admin';
        $_SESSION['is_admin'] = true;
        header("Location: admin/admin_dashboard.php");
        exit();
    }

    // Check in 'users' table
    try {
        $stmt = $conn->prepare("SELECT id, password, role, is_approved FROM users WHERE email = ?");
    } catch (mysqli_sql_exception $e) {
        throw $e;
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Check for admin approval if user is not admin
            if ($user['role'] !== 'admin' && $user['is_approved'] == 0) {
                $error = "Your account is pending admin approval. Please contact support.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'] ?? 'user';
                
                // Update status to online
                $conn->query("UPDATE users SET status = 'online' WHERE id = {$user['id']}");

                if ($_SESSION['role'] === 'admin') {
                     header("Location: admin/admin_dashboard.php");
                } else {
                     header("Location: dashboard.php");
                }
                exit();
            }
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - WhatsApp Web Clone</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

body{
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background:linear-gradient(135deg,#075e54,#128c7e,#25d366);
}

.auth-container{
    width:380px;
    padding:40px;
    border-radius:18px;
    background:rgba(0,0,0,0.6);
    backdrop-filter:blur(12px);
    box-shadow:0 20px 40px rgba(0,0,0,0.4);
    color:#fff;
    animation:fadeIn 0.8s ease-in-out;
}

.auth-container h2{
    text-align:center;
    margin-bottom:25px;
    font-weight:600;
    color:#25d366;
}

.form-group{
    margin-bottom:20px;
}

.form-group label{
    font-size:14px;
    color:#ccc;
}

.form-group input{
    width:100%;
    padding:12px;
    margin-top:6px;
    border-radius:10px;
    border:1px solid rgba(255,255,255,0.2);
    background:rgba(255,255,255,0.1);
    color:#fff;
    transition:0.3s;
}

.form-group input:focus{
    border-color:#25d366;
    box-shadow:0 0 12px rgba(37,211,102,0.6);
    outline:none;
}

.btn{
    width:100%;
    padding:12px;
    border:none;
    border-radius:10px;
    background:linear-gradient(45deg,#25d366,#128c7e);
    color:#fff;
    font-weight:500;
    cursor:pointer;
    transition:0.3s;
}

.btn:hover{
    transform:translateY(-3px);
    box-shadow:0 10px 20px rgba(37,211,102,0.4);
}

.error{
    background:#ff4d4d;
    padding:10px;
    border-radius:8px;
    margin-bottom:15px;
    text-align:center;
    font-size:14px;
}

.auth-container p{
    margin-top:18px;
    text-align:center;
    font-size:14px;
}

.auth-container a{
    color:#25d366;
    text-decoration:none;
    font-weight:500;
}

.auth-container a:hover{
    text-decoration:underline;
}

@keyframes fadeIn{
    from{opacity:0; transform:translateY(-20px);}
    to{opacity:1; transform:translateY(0);}
}

@media(max-width:480px){
    .auth-container{
        width:90% !important;
        padding:20px;
    }
}
</style>
</head>

<body>

<div class="auth-container">
    <h2>Welcome Back</h2>

    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="Enter your email" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Enter your password" required>
        </div>

        <button type="submit" class="btn">Login</button>
    </form>

    <p>Don't have an account? <a href="register.php">Register here</a></p>
</div>

</body>
</html>