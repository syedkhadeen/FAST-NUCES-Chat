<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['post_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'];

try {
    // Check if user already liked the post
    $check_like = $conn->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
    $check_like->bind_param("ii", $post_id, $user_id);
    $check_like->execute();
    $result = $check_like->get_result();

    if ($result->num_rows > 0) {
        // Unlike the post
        $delete_like = $conn->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
        $delete_like->bind_param("ii", $post_id, $user_id);
        $delete_like->execute();
        $action = 'unliked';
    } else {
        // Like the post
        $add_like = $conn->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
        $add_like->bind_param("ii", $post_id, $user_id);
        $add_like->execute();
        $action = 'liked';
    }

    // Get updated like count
    $count_query = $conn->prepare("SELECT COUNT(*) as count FROM likes WHERE post_id = ?");
    $count_query->bind_param("i", $post_id);
    $count_query->execute();
    $count_result = $count_query->get_result()->fetch_assoc();

    echo json_encode([
        'success' => true,
        'action' => $action,
        'likes' => $count_result['count']
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 