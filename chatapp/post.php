<?php
session_start();
require_once 'includes/db.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$post_id = $_GET['id'];
$post_query = $conn->prepare("
    SELECT p.*, u.name as author_name 
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.id = ?
");
$post_query->bind_param("i", $post_id);
$post_query->execute();
$post = $post_query->get_result()->fetch_assoc();

if (!$post) {
    header('Location: index.php');
    exit;
}

// Get like count
$like_count = $conn->prepare("SELECT COUNT(*) as count FROM likes WHERE post_id = ?");
$like_count->bind_param("i", $post_id);
$like_count->execute();
$likes = $like_count->get_result()->fetch_assoc()['count'];

// Get comments
$comments_query = $conn->prepare("
    SELECT c.*, u.name as author_name 
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.post_id = ? 
    ORDER BY c.created_at DESC
");
$comments_query->bind_param("i", $post_id);
$comments_query->execute();
$comments = $comments_query->get_result();
$comment_count = $comments->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Post - ChatApp</title>
    <link rel="stylesheet" href="assets/userdashboard.css">
    <style>
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: #1877f2;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← Back to Home</a>
        
        <div class="post" data-post-id="<?php echo $post['id']; ?>">
            <div class="post-header">
                <img src="uploads/profile/default.jpg" alt="Profile" class="post-profile-img">
                <div>
                    <div class="post-author"><?php echo htmlspecialchars($post['author_name']); ?></div>
                    <div class="post-time"><?php echo date('M d, Y', strtotime($post['created_at'])); ?></div>
                </div>
            </div>
            <div class="post-content">
                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                <?php if(!empty($post['image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Post image" class="post-image">
                <?php endif; ?>
            </div>
            <div class="post-actions">
                <button class="like-btn" disabled>
                    <span class="like-icon">❤️</span>
                    <span class="like-count"><?php echo $likes; ?></span>
                </button>
                <button class="comment-btn" onclick="toggleComments(<?php echo $post['id']; ?>)">
                    💬 <span class="comment-count"><?php echo $comment_count; ?></span>
                </button>
                <button class="share-btn" onclick="sharePost(<?php echo $post['id']; ?>)">
                    🔗 Share
                </button>
            </div>
            <div class="comments-section">
                <div class="comments-container">
                    <?php while($comment = $comments->fetch_assoc()): ?>
                        <div class="comment">
                            <img src="uploads/profile/default.jpg" alt="Profile" class="comment-profile-img">
                            <div class="comment-content">
                                <div class="comment-author"><?php echo htmlspecialchars($comment['author_name']); ?></div>
                                <div class="comment-text"><?php echo htmlspecialchars($comment['content']); ?></div>
                                <div class="comment-time"><?php echo date('M d, Y H:i', strtotime($comment['created_at'])); ?></div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <form class="comment-form" onsubmit="return addComment(event, <?php echo $post['id']; ?>)">
                        <input type="text" placeholder="Write a comment..." required>
                        <button type="submit">Send</button>
                    </form>
                <?php else: ?>
                    <p class="login-prompt">Please <a href="login.php">log in</a> to comment on this post.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="assets/dashboard.js"></script>
</body>
</html> 