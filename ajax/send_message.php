<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$sender_id = $_SESSION['user_id'];
$receiver_id = $_POST['receiver_id'];
$message = $_POST['message'];

if (!empty($_POST['message']) || (isset($_FILES['file']) && !empty($_FILES['file']['name']))) {
    
    $file_path = null;
    $message = $_POST['message'] ?? '';

    // Handle File Upload
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $upload_dir = '../uploads/chat_files/';
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                error_log("Failed to create directory: " . $upload_dir);
            }
        }
        
        $file_name = time() . '_' . basename($_FILES['file']['name']);
        $target_file = $upload_dir . $file_name;
        
        // Validate file type
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        // Allow all common file types as requested
        $allowed = [
            'jpg', 'jpeg', 'png', 'gif', 'webp', // Images
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf', // Documents
            'zip', 'rar', '7z', // Archives
            'mp3', 'wav', 'ogg', 'weba', // Audio
            'mp4', 'avi', 'mov', 'mkv', 'webm' // Video
        ];
        
        if (in_array($file_type, $allowed)) {
            if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
                $file_path = $file_name;
            } else {
                 // Upload failed
                 error_log("Failed to move uploaded file: " . $_FILES['file']['name']);
            }
        } else {
            error_log("File type not allowed: " . $file_type);
        }
    }

    // If file upload failed but message is empty, don't insert empty row
    if (empty($message) && empty($file_path)) {
        echo json_encode(['error' => 'File upload failed or type not allowed, and message is empty.']);
        exit;
    }

    // Unified Insert Logic
    try {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, file) VALUES (?, ?, ?, ?)");
        if ($stmt) {
             $stmt->bind_param("iiss", $sender_id, $receiver_id, $message, $file_path);
             
             if ($stmt->execute()) {
                 echo json_encode(['success' => true]);
                 exit;
             } else {
                 echo json_encode(['error' => 'Execute failed: ' . $stmt->error]);
                 exit;
             }
        } else {
            echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
            exit;
        }
    } catch (Throwable $e) {
        echo json_encode(['error' => 'Critical Error: ' . $e->getMessage()]);
        exit;
    }
} else {
    // Check if post_max_size exceeded
    if (empty($_POST) && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0) {
        echo json_encode(['error' => 'File too large (exceeds post_max_size).']);
    } else {
        echo json_encode(['error' => 'Empty message and no file']);
    }
}
?>