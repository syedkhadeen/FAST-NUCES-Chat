<?php
session_start();
require_once 'includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if (!isset($_GET['user'])) {
    echo json_encode(['success' => false, 'error' => 'No user specified']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$other_user_id = (int)$_GET['user'];
$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

try {
    $stmt = $conn->prepare("
        SELECT m.*, u.name as sender_name 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        WHERE m.id > ? 
        AND ((m.sender_id = ? AND m.receiver_id = ?) 
        OR (m.sender_id = ? AND m.receiver_id = ?)) 
        ORDER BY m.created_at ASC
    ");
    
    $stmt->bind_param("iiiii", $last_id, $current_user_id, $other_user_id, $other_user_id, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'id' => $row['id'],
            'sender_id' => $row['sender_id'],
            'content' => htmlspecialchars($row['content']),
            'created_at' => date('M d, Y H:i', strtotime($row['created_at'])),
            'sender_name' => $row['sender_name']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);

} catch (Exception $e) {
    error_log("Error in get_messages.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error fetching messages'
    ]);
}

$conn->close();
?> 