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

if (!isset($input['post_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing post ID']);
    exit;
}

$post_id = (int)$input['post_id'];

try {
    // Check if user owns the post
    $check_ownership = $conn->prepare("SELECT user_id, image_path FROM posts WHERE id = ?");
    $check_ownership->bind_param("i", $post_id);
    $check_ownership->execute();
    $post = $check_ownership->get_result()->fetch_assoc();

    if (!$post || $post['user_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    // Begin transaction
    $conn->begin_transaction();

    // Delete associated likes
    $delete_likes = $conn->prepare("DELETE FROM likes WHERE post_id = ?");
    $delete_likes->bind_param("i", $post_id);
    $delete_likes->execute();

    // Delete associated comments
    $delete_comments = $conn->prepare("DELETE FROM comments WHERE post_id = ?");
    $delete_comments->bind_param("i", $post_id);
    $delete_comments->execute();

    // Delete the post
    $delete_post = $conn->prepare("DELETE FROM posts WHERE id = ?");
    $delete_post->bind_param("i", $post_id);
    
    if ($delete_post->execute()) {
        // Delete the post image if it exists
        if (!empty($post['image_path']) && file_exists('../' . $post['image_path'])) {
            unlink('../' . $post['image_path']);
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Post deleted successfully']);
    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to delete post']);
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 