<?php
session_start();
require_once '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$post_id = $data['post_id'] ?? null;
$content = $data['content'] ?? null;

if (!$post_id || !$content) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

// Create comments table if it doesn't exist
$create_table_query = "CREATE TABLE IF NOT EXISTS comments (
    id int(11) NOT NULL AUTO_INCREMENT,
    post_id int(11) NOT NULL,
    user_id int(11) NOT NULL,
    content text NOT NULL,
    created_at timestamp NULL DEFAULT current_timestamp(),
    PRIMARY KEY (id),
    KEY post_id (post_id),
    KEY user_id (user_id),
    CONSTRAINT comments_ibfk_1 FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE,
    CONSTRAINT comments_ibfk_2 FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$conn->query($create_table_query);

// Add the comment
$insert_query = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
$insert_query->bind_param("iis", $post_id, $_SESSION['user_id'], $content);

if ($insert_query->execute()) {
    // Get the comment details including author name
    $comment_query = $conn->prepare("
        SELECT c.*, u.name as author_name 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.id = ?
    ");
    $comment_id = $insert_query->insert_id;
    $comment_query->bind_param("i", $comment_id);
    $comment_query->execute();
    $comment = $comment_query->get_result()->fetch_assoc();

    echo json_encode([
        'success' => true,
        'author_name' => $comment['author_name'],
        'content' => $comment['content'],
        'created_at' => date('M d, Y H:i', strtotime($comment['created_at']))
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add comment']);
}
?> 