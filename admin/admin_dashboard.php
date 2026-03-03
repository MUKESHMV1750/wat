<?php
session_start();
require_once '../includes/db.php';

// Check Admin Access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Handle User Deletion
if (isset($_GET['delete_user'])) {
    $delete_id = intval($_GET['delete_user']);
    if ($delete_id != $_SESSION['user_id']) {
        $conn->query("DELETE FROM users WHERE id = $delete_id");
        header("Location: admin_dashboard.php?msg=deleted");
        exit();
    }
}

// Handle Role Change
if (isset($_GET['change_role']) && isset($_GET['user_id'])) {
    $target_id = intval($_GET['user_id']);
    $new_role = $_GET['change_role'] === 'admin' ? 'admin' : 'user';
    
    if ($target_id != $_SESSION['user_id']) {
        // Ensure approval column exists
        try {
            $conn->query("UPDATE users SET role = '$new_role' WHERE id = $target_id");
        } catch (Exception $e) {
             // Handle missing column if needed
        }
        header("Location: admin_dashboard.php?msg=role_updated");
        exit();
    }
}

// Handle Approval (Approve/Reject)
if (isset($_GET['approve_user'])) {
    $target_id = intval($_GET['approve_user']);
    $status = intval($_GET['status']); // 1 = Approved, 0 = Pending/Rejected
    
    // Ensure column exists
    try {
        $conn->query("ALTER TABLE users ADD COLUMN is_approved TINYINT(1) DEFAULT 0");
    } catch (Exception $e) {}

    $conn->query("UPDATE users SET is_approved = $status WHERE id = $target_id");
    header("Location: admin_dashboard.php?msg=approval_updated");
    exit();
}

// Fetch Stats
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_messages = $conn->query("SELECT COUNT(*) as count FROM messages")->fetch_assoc()['count'];
$online_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE status='online'")->fetch_assoc()['count'];

// Fetch All Users
$users_result = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

body{
    background:linear-gradient(135deg,#0f2027,#203a43,#2c5364);
    color:#fff;
}

.admin-container{
    max-width:1200px;
    margin:30px auto;
    padding:20px;
}

.header-actions{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:30px;
}

.header-actions h1{
    font-weight:600;
    color:#25d366;
}

.btn{
    padding:10px 18px;
    border-radius:8px;
    text-decoration:none;
    background:linear-gradient(45deg,#25d366,#128c7e);
    color:#fff;
    font-size:14px;
    transition:0.3s;
}

.btn:hover{
    transform:translateY(-2px);
    box-shadow:0 8px 20px rgba(37,211,102,0.4);
}

.stats-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:20px;
    margin-bottom:40px;
}

.stat-card{
    background:rgba(255,255,255,0.08);
    backdrop-filter:blur(10px);
    padding:25px;
    border-radius:15px;
    text-align:center;
    box-shadow:0 10px 30px rgba(0,0,0,0.3);
    transition:0.3s;
}

.stat-card:hover{
    transform:translateY(-5px);
}

.stat-card h3{
    font-size:34px;
    color:#25d366;
}

.stat-card p{
    margin-top:8px;
    color:#ccc;
}

h2{
    margin-bottom:15px;
    font-weight:500;
}

.user-table{
    width:100%;
    border-collapse:collapse;
    background:rgba(255,255,255,0.05);
    backdrop-filter:blur(10px);
    border-radius:12px;
    overflow:hidden;
}

.user-table th,
.user-table td{
    padding:14px;
    text-align:left;
}

.user-table th{
    background:rgba(0,0,0,0.4);
    font-weight:500;
    font-size:14px;
    color:#25d366;
}

.user-table tr{
    border-bottom:1px solid rgba(255,255,255,0.1);
    transition:0.2s;
}

.user-table tr:hover{
    background:rgba(255,255,255,0.05);
}

.role-badge{
    padding:4px 10px;
    border-radius:20px;
    font-size:12px;
    font-weight:500;
}

.admin-role{
    background:#1b5e20;
    color:#b9f6ca;
}

.user-role{
    background:#4a148c;
    color:#e1bee7;
}

.status-online{
    color:#25d366;
    font-weight:500;
}

.status-offline{
    color:#aaa;
}

.btn-role{
    background:#007bff;
    padding:6px 10px;
    border-radius:6px;
    font-size:12px;
    text-decoration:none;
    color:#fff;
    margin-right:5px;
    transition:0.2s;
}

.btn-role:hover{
    background:#0056b3;
}

.btn-delete{
    background:#dc3545;
    padding:6px 10px;
    border-radius:6px;
    font-size:12px;
    text-decoration:none;
    color:#fff;
    transition:0.2s;
}

.btn-delete:hover{
    background:#b02a37;
}
</style>
</head>

<body>

<div class="admin-container">

    <div class="header-actions">
        <h1><i class="fas fa-user-shield"></i> Admin Panel</h1>
        <a href="../dashboard.php" class="btn"><i class="fas fa-comments"></i> Back to Chat</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3><?php echo $total_users; ?></h3>
            <p>Total Users</p>
        </div>
        <div class="stat-card">
            <h3><?php echo $total_messages; ?></h3>
            <p>Total Messages</p>
        </div>
        <div class="stat-card">
            <h3><?php echo $online_users; ?></h3>
            <p>Online Users</p>
        </div>
    </div>

    <h2>User Management</h2>

    <table class="user-table">
        <thead>
        <tr>
            <th>ID</th>
            <th>User</th>
            <th>Role</th>
            <th>Approved</th>
            <th>Status</th>
            <th>Email</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>

        <?php while($user = $users_result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $user['id']; ?></td>

            <td>
                <div style="display:flex;align-items:center;gap:10px;">
                    <img src="<?php echo $user['profile_image'] ? '../uploads/profile/'.$user['profile_image'] : 'https://ui-avatars.com/api/?name='.urlencode($user['name']).'&background=random'; ?>" 
                    style="width:35px;height:35px;border-radius:50%;">
                    <?php echo htmlspecialchars($user['name']); ?>
                </div>
            </td>

            <td>
                <?php $role = $user['role'] ?? 'user'; ?>
                <span class="role-badge <?php echo $role=='admin'?'admin-role':'user-role'; ?>">
                    <?php echo ucfirst($role); ?>
                </span>
            </td>

            <td>
                <?php 
                    $is_approved = isset($user['is_approved']) ? $user['is_approved'] : 0;
                    $status_text = ($is_approved == 1) ? 'Approved' : 'Pending';
                    $status_color = ($is_approved == 1) ? '#25d366' : '#ff9f43';
                    $btn_action = ($is_approved == 1) ? '0' : '1';
                    $btn_text = ($is_approved == 1) ? 'Revoke' : 'Approve';
                    $btn_style = ($is_approved == 1) ? 'background:#dc3545;' : 'background:#25d366;';
                ?>
                <span style="color:<?php echo $status_color; ?>; font-weight:bold;">
                    <?php echo $status_text; ?>
                </span>
                <?php if($user['id'] != $_SESSION['user_id']): ?>
                    <a href="?approve_user=<?php echo $user['id']; ?>&status=<?php echo $btn_action; ?>"
                       class="btn-role" style="<?php echo $btn_style; ?> margin-left:5px; padding:2px 8px;">
                       <?php echo $btn_text; ?>
                    </a>
                <?php endif; ?>
            </td>

            <td>
                <span class="<?php echo $user['status']=='online'?'status-online':'status-offline'; ?>">
                    ● <?php echo ucfirst($user['status']); ?>
                </span>
            </td>

            <td><?php echo htmlspecialchars($user['email']); ?></td>

            <td>
                <?php if($user['id'] != $_SESSION['user_id']): ?>
                    <form action="" method="GET" style="display:inline;">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <select name="change_role" onchange="this.form.submit()" style="padding:5px;border-radius:4px;border:1px solid #ddd;">
                            <option value="user" <?php echo ($role == 'user') ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo ($role == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </form>

                    <a href="?delete_user=<?php echo $user['id']; ?>" 
                       class="btn-delete"
                       onclick="return confirm('Delete this user?');">
                       Delete
                    </a>
                <?php else: ?>
                    <span style="color:#999;">(You)</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>

        </tbody>
    </table>

</div>

</body>
</html>