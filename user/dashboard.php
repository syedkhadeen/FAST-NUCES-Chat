<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/db.php';
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get user data including profile picture and bio
$user_query = $conn->prepare("SELECT id, name, profile_pic, bio, status FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user = $user_query->get_result()->fetch_assoc();

if (!$user) {
    // User doesn't exist, create one
    $insert_user = $conn->prepare("INSERT INTO users (id, name, email, password, status, profile_pic) VALUES (?, ?, ?, ?, 'online', 'default.jpg')");
    $email = strtolower(str_replace(' ', '.', $user_name)) . '@example.com';
    $password = password_hash('default_password', PASSWORD_DEFAULT);
    $insert_user->bind_param("isss", $user_id, $user_name, $email, $password);
    if ($insert_user->execute()) {
        error_log("Created new user - ID: $user_id, Name: $user_name");
        $user = [
            'id' => $user_id,
            'name' => $user_name,
            'profile_pic' => 'default.jpg',
            'status' => 'online'
        ];
    } else {
        error_log("Failed to create user: " . $conn->error);
        die("Failed to create user. Please try logging in again.");
    }
}

// Fetch recent posts with like and comment counts
$posts_query = "SELECT p.*, p.user_id as post_user_id, u.name as author_name, u.profile_pic as author_pic,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments,
                EXISTS(SELECT 1 FROM likes WHERE post_id = p.id AND user_id = ?) as is_liked
                FROM posts p 
                JOIN users u ON p.user_id = u.id 
                ORDER BY p.created_at DESC LIMIT 10";
$posts_stmt = $conn->prepare($posts_query);
$posts_stmt->bind_param("i", $user_id);
$posts_stmt->execute();
$posts_result = $posts_stmt->get_result();

// Debug information
echo "<!-- Current user ID: " . $_SESSION['user_id'] . " -->";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChatApp</title>
    <link rel="stylesheet" href="../assets/userdashboard.css">
</head>
<body>
    <div class="container">
        <div class="content-area">
            <div class="post-box">
                <form id="post-form" action="create_post.php" method="POST" enctype="multipart/form-data">
                    <textarea name="post_content" placeholder="What's on your mind?" required></textarea>
                    
                    <div id="image-preview-container" style="display: none; margin: 10px 0;">
                        <img id="preview-image" src="" alt="Preview" style="max-width: 200px; border-radius: 8px;">
                        <button type="button" onclick="document.getElementById('image-preview-container').style.display='none'; document.getElementById('post-image').value='';" style="position: absolute; top: 5px; right: 5px; background: rgba(0,0,0,0.5); color: white; border: none; border-radius: 50%; width: 25px; height: 25px; cursor: pointer;">×</button>
                    </div>

                    <div class="post-actions">
                        <div class="left-actions">
                            <label class="photo-upload">
                                📷 Add Photo
                                <input type="file" id="post-image" name="post_image" accept="image/*" style="display: none;">
                            </label>
                        </div>
                        <button type="submit" class="post-btn">Post</button>
                    </div>
                </form>
            </div>

            <div class="posts-container">
                <?php if($posts_result && $posts_result->num_rows > 0): ?>
                    <?php while($post = $posts_result->fetch_assoc()): ?>
                        <div class="post" style="background: white; padding: 15px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                            <!-- Simple header with all elements in one line -->
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                                <img src="../uploads/profile/<?php echo htmlspecialchars($post['author_pic']); ?>" 
                                     alt="Profile" 
                                     style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                
                                <div style="font-weight: bold;">
                                    <?php echo htmlspecialchars($post['author_name']); ?>
                                </div>

                                <div style="color: #65676b; font-size: 14px;">
                                    <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                                </div>
                            </div>

                            <!-- Post content -->
                            <div style="margin-top: 10px;">
                                <div style="margin-bottom: 10px;">
                                    <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                                </div>
                                
                                <?php if(!empty($post['image_path'])): ?>
                                    <div style="margin-top: 10px;">
                                        <img src="<?php echo htmlspecialchars($post['image_path']); ?>" 
                                             alt="Post image" 
                                             style="max-width: 100%; border-radius: 8px; cursor: pointer;"
                                             onclick="showImagePreview('<?php echo htmlspecialchars($post['image_path']); ?>')">
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Simple action buttons -->
                            <div style="display: flex; gap: 10px; margin-top: 15px; padding-top: 10px; border-top: 1px solid #e4e6eb;">
                                <button style="padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; background: #f0f2f5;">
                                    ❤️ Like
                                </button>
                                <button style="padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; background: #f0f2f5;">
                                    💬 Comment
                                </button>
                                <button style="padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; background: #f0f2f5;">
                                    🔗 Share
                                </button>
                                <?php if($post['post_user_id'] == $_SESSION['user_id']): ?>
                                    <button onclick="deletePost(<?php echo $post['id']; ?>)" 
                                            style="padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; background: #f0f2f5;">
                                        🗑️ Delete
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px; color: #65676b;">
                        No posts yet. Be the first to post!
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Store current user ID for JavaScript functions
        const currentUserId = <?php echo $_SESSION['user_id']; ?>;
        console.log('Current user ID:', currentUserId);
    </script>
    <script src="../assets/dashboard.js"></script>
    <script>
        // Add event listener for post image preview
        document.getElementById('post-image')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-image').src = e.target.result;
                    document.getElementById('image-preview-container').style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });

        // Debug post ownership
        document.querySelectorAll('.post').forEach(post => {
            const postId = post.dataset.postId;
            const userId = post.querySelector('.post-header')?.dataset.userId;
            console.log(`Post ${postId} - User ID: ${userId}, Current User: ${currentUserId}`);
        });
    </script>
</body>
</html> 