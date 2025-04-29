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

// Check if user owns the post
$check_query = $conn->prepare("SELECT user_id FROM posts WHERE id = ?");
$check_query->bind_param("i", $post_id);
$check_query->execute();
$result = $check_query->get_result();
$post = $result->fetch_assoc();

if (!$post || $post['user_id'] != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Not authorized to edit this post']);
    exit;
}

// Update the post
$update_query = $conn->prepare("UPDATE posts SET content = ? WHERE id = ? AND user_id = ?");
$update_query->bind_param("sii", $content, $post_id, $_SESSION['user_id']);

if ($update_query->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update post']);
} 