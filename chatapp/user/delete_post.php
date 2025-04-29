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

if (!$post_id) {
    echo json_encode(['success' => false, 'message' => 'No post ID provided']);
    exit;
}

// Check if user owns the post and get image path
$check_query = $conn->prepare("SELECT user_id, image_path FROM posts WHERE id = ?");
$check_query->bind_param("i", $post_id);
$check_query->execute();
$result = $check_query->get_result();
$post = $result->fetch_assoc();

if (!$post || $post['user_id'] != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Not authorized to delete this post']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Delete comments first (if any)
    $delete_comments = $conn->prepare("DELETE FROM comments WHERE post_id = ?");
    $delete_comments->bind_param("i", $post_id);
    $delete_comments->execute();

    // Delete likes (if any)
    $delete_likes = $conn->prepare("DELETE FROM likes WHERE post_id = ?");
    $delete_likes->bind_param("i", $post_id);
    $delete_likes->execute();

    // Delete the post
    $delete_post = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
    $delete_post->bind_param("ii", $post_id, $_SESSION['user_id']);
    $delete_post->execute();

    // Delete the image file if it exists
    if (!empty($post['image_path'])) {
        $image_path = $_SERVER['DOCUMENT_ROOT'] . '/chatapp/chatapp/' . $post['image_path'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }

    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Failed to delete post: ' . $e->getMessage()]);
} 