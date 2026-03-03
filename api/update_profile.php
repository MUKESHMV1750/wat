<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Profile Image Upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = uniqid('profile_', true) . '.' . $ext;
            $upload_path = '../uploads/profile/' . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                $stmt->bind_param("si", $new_filename, $user_id);
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['image_url'] = 'uploads/profile/' . $new_filename;
                    $response['message'] = 'Profile image updated';
                }
            } else {
                $response['message'] = 'Failed to upload file';
            }
        } else {
            $response['message'] = 'Invalid file type';
        }
    }
    // Handle Name Update
    elseif (isset($_POST['name'])) {
        $name = trim($_POST['name']);
        if (!empty($name)) {
            $stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $user_id);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Name updated';
            }
        } else {
            $response['message'] = 'Name cannot be empty';
        }
    }
    // Handle About Update
    elseif (isset($_POST['about'])) {
        // First check if 'about' column exists, if not add it
        try {
            $conn->query("SELECT about FROM users LIMIT 1");
        } catch (Exception $e) {
            $conn->query("ALTER TABLE users ADD COLUMN about VARCHAR(255) DEFAULT 'Hey there! I am using WhatsApp.'");
        }

        $about = trim($_POST['about']);
        $stmt = $conn->prepare("UPDATE users SET about = ? WHERE id = ?");
        $stmt->bind_param("si", $about, $user_id);
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'About info updated';
        }
    }
    // Handle Profile Position Update
    elseif (isset($_POST['pos_x']) && isset($_POST['pos_y'])) {
        $x = (int)$_POST['pos_x'];
        $y = (int)$_POST['pos_y'];
        
        $stmt = $conn->prepare("UPDATE users SET profile_pos_x = ?, profile_pos_y = ? WHERE id = ?");
        $stmt->bind_param("iii", $x, $y, $user_id);
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Position updated';
        } else {
             $response['message'] = $conn->error;
        }
    }
}


echo json_encode($response);
?>