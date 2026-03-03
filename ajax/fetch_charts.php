<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$data = [];

// Messages per user
$stmt1 = $conn->query("SELECT u.name, u.email, COUNT(m.id) as total FROM users u LEFT JOIN messages m ON u.id = m.sender_id GROUP BY u.id");
$messagesPerUser = [];
while ($row = $stmt1->fetch_assoc()) {
    $messagesPerUser['labels'][] = $row['name'] . ' (' . $row['email'] . ')';
    $messagesPerUser['data'][] = $row['total'];
}
$data['messagesPerUser'] = $messagesPerUser;

// Daily messages
$stmt2 = $conn->query("SELECT DATE(created_at) as date, COUNT(id) as total FROM messages GROUP BY DATE(created_at) ORDER BY date ASC LIMIT 7");
$dailyMessages = [];
while ($row = $stmt2->fetch_assoc()) {
    $dailyMessages['labels'][] = $row['date'];
    $dailyMessages['data'][] = $row['total'];
}
$data['dailyMessages'] = $dailyMessages;

echo json_encode($data);
?>