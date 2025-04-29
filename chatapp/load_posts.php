<?php
session_start();
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$posts_per_page = 8;
$offset = ($page - 1) * $posts_per_page;

try {
    // Get posts with user info and like counts
    $query = "SELECT p.*, u.name as author_name, u.profile_pic,
              (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
              (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
              EXISTS(SELECT 1 FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked
              FROM posts p
              JOIN users u ON p.user_id = u.id
              ORDER BY p.created_at DESC
              LIMIT ? OFFSET ?";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $_SESSION['user_id'], $posts_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = [
            'id' => $row['id'],
            'content' => $row['content'],
            'image_path' => $row['image_path'],
            'created_at' => $row['created_at'],
            'author_name' => $row['author_name'],
            'profile_pic' => $row['profile_pic'] ?? 'default.jpg',
            'like_count' => $row['like_count'],
            'comment_count' => $row['comment_count'],
            'user_liked' => (bool)$row['user_liked']
        ];
    }
    
    echo json_encode(['success' => true, 'posts' => $posts]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?> 