<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$message_id = $_POST['message_id'];

if (empty($message_id)) {
    echo json_encode(['error' => 'Message ID is required']);
    exit();
}

// Logic: 
// If I am sender -> set deleted_for_sender = 1
// If I am receiver -> set deleted_for_receiver = 1

$stmt = $conn->prepare("UPDATE messages m SET 
    deleted_for_sender = CASE WHEN m.sender_id = ? THEN 1 ELSE deleted_for_sender END,
    deleted_for_receiver = CASE WHEN m.receiver_id = ? THEN 1 ELSE deleted_for_receiver END
    WHERE id = ? AND (sender_id = ? OR receiver_id = ?)");
    
$stmt->bind_param("iiiii", $user_id, $user_id, $message_id, $user_id, $user_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        // Technically we could cleanup if both are deleted, but let's keep it simple for now
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Message not found or permission denied']);
    }
} else {
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
}
?>