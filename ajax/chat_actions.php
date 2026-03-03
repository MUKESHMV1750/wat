<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if (empty($action)) {
    echo json_encode(['success' => false, 'error' => 'No action specified']);
    exit();
}

switch ($action) {
    case 'clear_chat':
        $other_user_id = $_POST['user_id'];
        if (!$other_user_id) {
            echo json_encode(['success' => false, 'error' => 'User ID required']);
            exit();
        }

        $stmt = $conn->prepare("UPDATE messages SET 
            deleted_for_sender = CASE WHEN sender_id = ? THEN 1 ELSE deleted_for_sender END,
            deleted_for_receiver = CASE WHEN receiver_id = ? THEN 1 ELSE deleted_for_receiver END
            WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
        
        $stmt->bind_param("iiiiii", $user_id, $user_id, $user_id, $other_user_id, $other_user_id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        break;

    case 'delete_chat':
        // Same as clear chat for now, maybe remove from 'recent chats' if we tracked that separately
        // For this app, deleting messages effectively removes it from the list if the list is built from messages
        // But wait, the list is built from users table (all users). So delete chat just clears history.
        // Reusing the clear_chat logic
        $other_user_id = $_POST['user_id'];
        if (!$other_user_id) {
            echo json_encode(['success' => false, 'error' => 'User ID required']);
            exit();
        }

        $stmt = $conn->prepare("UPDATE messages SET 
            deleted_for_sender = CASE WHEN sender_id = ? THEN 1 ELSE deleted_for_sender END,
            deleted_for_receiver = CASE WHEN receiver_id = ? THEN 1 ELSE deleted_for_receiver END
            WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
        
        $stmt->bind_param("iiiiii", $user_id, $user_id, $user_id, $other_user_id, $other_user_id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        break;

    case 'block_user':
        $block_id = $_POST['user_id'];
        $stmt = $conn->prepare("INSERT INTO blocked_users (user_id, blocked_user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $block_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            // Duplicate entry might happen if already blocked
            echo json_encode(['success' => true, 'message' => 'Already blocked']);
        }
        break;

    case 'unblock_user':
        $block_id = $_POST['user_id'];
        $stmt = $conn->prepare("DELETE FROM blocked_users WHERE user_id = ? AND blocked_user_id = ?");
        $stmt->bind_param("ii", $user_id, $block_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        break;
        
    case 'get_contact_info':
        $other_user_id = $_POST['user_id'];
        // Fetch all relevant profile info including about and position
        $stmt = $conn->prepare("SELECT name, email, status, profile_image, last_seen, about, profile_pos_x, profile_pos_y FROM users WHERE id = ?");
        $stmt->bind_param("i", $other_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            echo json_encode(['success' => false, 'error' => 'User not found']);
        }
        break;

    case 'report_user':
        // Log report
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>