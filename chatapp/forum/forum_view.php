<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$current_user = $_SESSION['user_id'];
$current_user_name = htmlspecialchars($_SESSION['user_name']);

// Get forum ID
$forum_id = isset($_GET['forum_id']) ? intval($_GET['forum_id']) : 0;

// Fetch the forum details
$stmt = $conn->prepare("SELECT * FROM forums WHERE id = ?");
$stmt->bind_param("i", $forum_id);
$stmt->execute();
$forum = $stmt->get_result()->fetch_assoc();

if (!$forum) {
    header("Location: forums.php");
    exit;
}

// Fetch all posts in the forum
$stmt = $conn->prepare("SELECT fp.*, u.name AS user_name FROM forum_posts fp JOIN users u ON fp.user_id = u.id WHERE fp.forum_id = ? ORDER BY fp.created_at ASC");
$stmt->bind_param("i", $forum_id);
$stmt->execute();
$posts = $stmt->get_result();

// Handle new post creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post'])) {
    $post_content = sanitize($_POST['post_content']);
    if (!empty($post_content)) {
        $stmt = $conn->prepare("INSERT INTO forum_posts (forum_id, user_id, post_content) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $forum_id, $current_user, $post_content);
        if ($stmt->execute()) {
            header("Location: forum_view.php?forum_id=" . $forum_id);
            exit;
        } else {
            $error = "Failed to create the post.";
        }
    } else {
        $error = "Post content cannot be empty.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($forum['title']); ?> - Forum</title>
    <link rel="stylesheet" href="../assets/view_forum.css">
</head>
<body>
    <div class="container">
        <h2><?php echo htmlspecialchars($forum['title']); ?></h2>

        <!-- Display all posts in the forum -->
        <div class="post-list">
            <?php if ($posts->num_rows > 0): ?>
                <ul>
                    <?php while ($post = $posts->fetch_assoc()): ?>
                        <li class="<?php echo ($post['user_id'] == $current_user) ? 'own-message' : 'other-message'; ?>">
                            <?php if ($post['user_id'] != $current_user): ?>
                                <strong><?php echo htmlspecialchars($post['user_name']); ?>:</strong>
                            <?php endif; ?>
                            <p><?php echo nl2br(htmlspecialchars($post['post_content'])); ?></p>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No posts yet.</p>
            <?php endif; ?>
        </div>

        <!-- Create New Post Form -->
        <div class="create-post-form">
            <h3>Create New Post</h3>
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <textarea name="post_content" rows="4" required placeholder="Write your post here..."></textarea>
                <button type="submit" name="create_post" class="btn-send">Post</button>
            </form>
        </div>
    </div>

    <script>
        // Auto-scroll to the bottom of the post list on page load
        document.addEventListener('DOMContentLoaded', () => {
            const postList = document.querySelector('.post-list');
            postList.scrollTop = postList.scrollHeight;
        });
    </script>
</body>
</html>