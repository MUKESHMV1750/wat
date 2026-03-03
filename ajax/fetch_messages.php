<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$receiver_id = $_GET['receiver_id'] ?? 0;

if (!$receiver_id) {
    echo json_encode([]);
    exit;
}

try {
    // Attempt to update status (delivered), but don't crash if column missing
    $del_query = "UPDATE messages SET status = 'delivered' WHERE receiver_id = ? AND sender_id = ? AND status = 'sent'";
    if ($stmt = $conn->prepare($del_query)) {
        $stmt->bind_param("ii", $user_id, $receiver_id);
        $stmt->execute();
        $stmt->close();
    }
} catch (Exception $e) {
    // Ignore schema errors for now to allow fetching messages
}

// Select only messages not deleted for the viewer
$sql = "SELECT * FROM messages WHERE 
    (sender_id = ? AND receiver_id = ? AND (deleted_for_sender = 0 OR deleted_for_sender IS NULL)) 
    OR (sender_id = ? AND receiver_id = ? AND (deleted_for_receiver = 0 OR deleted_for_receiver IS NULL)) 
    ORDER BY created_at ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}
$stmt->bind_param("iiii", $user_id, $receiver_id, $receiver_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}
$stmt->close();

try {
    // Attempt to update status (read)
    $read_query = "UPDATE messages SET status = 'read' WHERE receiver_id = ? AND sender_id = ? AND status != 'read'";
    if ($stmt = $conn->prepare($read_query)) {
        $stmt->bind_param("ii", $user_id, $receiver_id);
        $stmt->execute();
        $stmt->close();
    }
} catch (Exception $e) {
    // Ignore schema errors
}

echo json_encode($messages);
?>