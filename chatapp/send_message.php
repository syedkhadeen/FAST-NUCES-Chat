<?php
session_start();
require_once 'includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['receiver_id']) || !isset($input['content']) || trim($input['content']) === '') {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$sender_id = $_SESSION['user_id'];
$receiver_id = (int)$input['receiver_id'];
$content = trim($input['content']);

try {
    // Create messages table if it doesn't exist
    $conn->query("
        CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            receiver_id INT NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (sender_id) REFERENCES users(id),
            FOREIGN KEY (receiver_id) REFERENCES users(id)
        )
    ");

    // Insert the message
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $sender_id, $receiver_id, $content);
    
    if ($stmt->execute()) {
        $message_id = $stmt->insert_id;
        
        // Fetch the sender's name
        $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->bind_param("i", $sender_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $sender = $result->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'message' => [
                'id' => $message_id,
                'sender_id' => $sender_id,
                'content' => htmlspecialchars($content),
                'created_at' => date('M d, Y H:i'),
                'sender_name' => $sender['name']
            ]
        ]);
    } else {
        throw new Exception("Failed to send message");
    }

} catch (Exception $e) {
    error_log("Error in send_message.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error sending message'
    ]);
}

$conn->close();
?> 