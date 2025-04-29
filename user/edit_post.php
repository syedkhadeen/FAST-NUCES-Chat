<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['post_id']) || !isset($input['content'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$post_id = (int)$input['post_id'];
$content = trim($input['content']);

try {
    // Check if user owns the post
    $check_ownership = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
    $check_ownership->bind_param("i", $post_id);
    $check_ownership->execute();
    $post = $check_ownership->get_result()->fetch_assoc();

    if (!$post || $post['user_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    // Update the post
    $update_post = $conn->prepare("UPDATE posts SET content = ? WHERE id = ?");
    $update_post->bind_param("si", $content, $post_id);
    
    if ($update_post->execute()) {
        echo json_encode(['success' => true, 'message' => 'Post updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update post']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 