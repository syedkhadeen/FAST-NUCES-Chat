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
                <?php
                try {
                    // Get like count
                    $like_count = $conn->prepare("SELECT COUNT(*) as count FROM likes WHERE post_id = ?");
                    if ($like_count) {
                        $like_count->bind_param("i", $post['id']);
                        $like_count->execute();
                        $likes = $like_count->get_result()->fetch_assoc()['count'];
                    } else {
                        $likes = 0;
                    }

                    // Check if user liked this post
                    $user_liked = $conn->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
                    if ($user_liked) {
                        $user_liked->bind_param("ii", $post['id'], $user_id);
                        $user_liked->execute();
                        $is_liked = $user_liked->get_result()->num_rows > 0;
                    } else {
                        $is_liked = false;
                    }

                    // Get comments with author info
                    $comments_query = $conn->prepare("
                        SELECT c.*, u.name as author_name, u.profile_pic as author_pic
                        FROM comments c 
                        JOIN users u ON c.user_id = u.id 
                        WHERE c.post_id = ? 
                        ORDER BY c.created_at DESC
                    ");
                    if ($comments_query) {
                        $comments_query->bind_param("i", $post['id']);
                        $comments_query->execute();
                        $comments = $comments_query->get_result();
                        $comment_count = $comments->num_rows;
                    } else {
                        $comments = null;
                        $comment_count = 0;
                    }
                } catch (Exception $e) {
                    error_log("Error in dashboard_content.php: " . $e->getMessage());
                    $likes = 0;
                    $is_liked = false;
                    $comments = null;
                    $comment_count = 0;
                }
                ?>
                <div class="post" data-post-id="<?php echo $post['id']; ?>">
                    <div class="post-header">
                        <a href="../profile.php?id=<?php echo $post['user_id']; ?>" class="post-author-link">
                            <img src="../uploads/profile/<?php echo htmlspecialchars($post['author_pic']); ?>" alt="Profile" class="post-profile-img">
                            <div>
                                <div class="post-author"><?php echo htmlspecialchars($post['author_name']); ?></div>
                                <div class="post-time"><?php echo date('M d, Y', strtotime($post['created_at'])); ?></div>
                            </div>
                        </a>
                    </div>
                    <div class="post-content">
                        <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                        <?php if(!empty($post['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Post image" class="post-image">
                        <?php endif; ?>
                    </div>
                    <div class="post-actions">
                        <button class="like-btn <?php echo $is_liked ? 'liked' : ''; ?>" onclick="likePost(<?php echo $post['id']; ?>)">
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
                    <div class="comments-section" style="display: none;">
                        <div class="comments-container">
                            <?php if($comments): while($comment = $comments->fetch_assoc()): ?>
                                <div class="comment">
                                    <a href="../profile.php?id=<?php echo $comment['user_id']; ?>" class="comment-author-link">
                                        <img src="../uploads/profile/<?php echo htmlspecialchars($comment['author_pic']); ?>" alt="Profile" class="comment-profile-img">
                                        <div class="comment-content">
                                            <div class="comment-author"><?php echo htmlspecialchars($comment['author_name']); ?></div>
                                            <div class="comment-text"><?php echo htmlspecialchars($comment['content']); ?></div>
                                            <div class="comment-time"><?php echo date('M d, Y H:i', strtotime($comment['created_at'])); ?></div>
                                        </div>
                                    </a>
                                </div>
                            <?php endwhile; endif; ?>
                        </div>
                        <form class="comment-form" onsubmit="return addComment(event, <?php echo $post['id']; ?>)">
                            <input type="text" placeholder="Write a comment..." required>
                            <button type="submit">Send</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-posts">No posts yet. Be the first to post!</div>
        <?php endif; ?>
    </div>
</div>

<div class="right-sidebar">
    <div class="chat-section">
        <h3>Chat</h3>
        <div class="chat-search">
            <input type="text" placeholder="Search users to chat...">
        </div>
        <div class="chat-list">
            <!-- Chat list will be populated via JavaScript -->
        </div>
    </div>
</div> 