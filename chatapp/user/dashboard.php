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
    $insert_user = $conn->prepare("INSERT INTO users (id, name, email, password, status, profile_pic) VALUES (?, ?, ?, ?, 'online', 'default.png')"); // Updated default
    $email = strtolower(str_replace(' ', '.', $user_name)) . '@example.com';
    $password = password_hash('default_password', PASSWORD_DEFAULT);
    $insert_user->bind_param("isss", $user_id, $user_name, $email, $password);
    if ($insert_user->execute()) {
        error_log("Created new user - ID: $user_id, Name: $user_name");
        $user = [
            'id' => $user_id,
            'name' => $user_name,
            'profile_pic' => 'default.png', // Updated default
            'status' => 'online'
        ];
    } else {
        error_log("Failed to create user: " . $conn->error);
        die("Failed to create user. Please try logging in again.");
    }
}

// Fetch recent posts
$posts_query = "SELECT p.*, p.user_id as post_user_id, u.name as author_name, u.profile_pic as author_pic 
                FROM posts p 
                JOIN users u ON p.user_id = u.id 
                ORDER BY p.created_at DESC LIMIT 10";
$posts_result = $conn->query($posts_query);

// Create posts table if it doesn't exist
$create_table_query = "CREATE TABLE IF NOT EXISTS posts (
    id int(11) NOT NULL AUTO_INCREMENT,
    user_id int(11) NOT NULL,
    content text NOT NULL,
    image_path varchar(255) DEFAULT NULL,
    created_at timestamp NULL DEFAULT current_timestamp(),
    PRIMARY KEY (id),
    KEY user_id (user_id),
    CONSTRAINT posts_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$conn->query($create_table_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChatApp</title>
    <link rel="stylesheet" href="../assets/userdashboard.css">
    <style>
        /* Additional styles for profile elements */
        .user-profile {
            position: relative;
            padding: 20px;
            text-align: center;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .profile-img-container {
            position: relative;
            width: 100px;
            height: 100px;
            margin: 0 auto 15px;
        }

        .profile-img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
            transition: filter 0.3s;
        }

        .profile-img:hover {
            filter: brightness(0.9);
        }

        .profile-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .profile-link:hover .user-name {
            color: #1877f2;
        }

        .user-name {
            font-weight: 600;
            font-size: 1.1em;
            margin: 5px 0;
            transition: color 0.3s;
        }

        .user-role {
            color: #65676b;
            font-size: 0.9em;
        }

        .top-bar .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 20px;
            transition: background-color 0.3s;
        }

        .top-bar .user-info:hover {
            background-color: #f0f2f5;
        }

        .top-bar .profile-img {
            width: 36px;
            height: 36px;
        }

        /* Additional styles for search results */
        .search-container {
            position: relative;
        }

        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-height: 400px;
            overflow-y: auto;
            display: none;
            z-index: 1000;
        }

        .search-result-item {
            display: flex;
            align-items: center;
            padding: 10px;
            gap: 10px;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }

        .search-result-item:hover {
            background-color: #f0f2f5;
        }

        .search-result-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .search-result-name {
            font-weight: 500;
        }

        .no-results {
            padding: 20px;
            text-align: center;
            color: #65676b;
        }

        /* Chat styles */
        .chat-section {
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .chat-search {
            position: relative;
            margin-bottom: 15px;
        }

        .chat-search input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 20px;
            outline: none;
            font-size: 14px;
        }

        .chat-search input:focus {
            border-color: #1877f2;
        }

        .chat-search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-height: 300px;
            overflow-y: auto;
            display: none;
            z-index: 1000;
        }

        .chat-user-item {
            display: flex;
            align-items: center;
            padding: 10px;
            gap: 10px;
            cursor: pointer;
            border-bottom: 1px solid #f0f2f5;
            text-decoration: none;
            color: inherit;
        }

        .chat-user-item:hover {
            background-color: #f0f2f5;
        }

        .chat-user-item:last-child {
            border-bottom: none;
        }

        .chat-user-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .chat-user-info {
            flex-grow: 1;
        }

        .chat-user-name {
            font-weight: 500;
            margin-bottom: 2px;
        }

        .chat-user-status {
            font-size: 12px;
            color: #65676b;
        }

        .chat-user-status.online {
            color: #31a24c;
        }

        .chat-last-message {
            font-size: 13px;
            color: #65676b;
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 150px;
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <div class="app-title">ChatApp</div>
        <div class="search-container">
            <input type="text" id="search-input" placeholder="Search users...">
            <button type="button" id="search-btn">🔍</button>
            <div class="search-results" id="search-results"></div>
        </div>
        <a href="../profile.php" class="user-info">
            <span><?php echo htmlspecialchars($user['name']); ?></span>
            <img src="../uploads/profile/<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile" class="profile-img">
        </a>
    </div>

    <div class="main-container">
        <div class="left-sidebar">
            <div class="user-profile">
                <a href="../profile.php" class="profile-link">
                    <div class="profile-img-container">
                        <img src="../uploads/profile/<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile" class="profile-img">
                    </div>
                    <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                    <div class="user-role">Student</div>
                </a>
            </div>
            <nav class="main-nav">
                <a href="dashboard.php" class="active">🏠 Home</a>
                <a href="messages.php">💬 Messages</a>
                <a href="../groups/group_dashboard.php">👥 Groups</a>
                <a href="../forum/forums.php">📢 Forums</a>
                <a href="../resources/dashboard.php">📚 Resources</a>
                <a href="../logout.php" onclick="return confirm('Are you sure you want to logout?')">🚪 Logout</a>
            </nav>
        </div>

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

                            // Get comments
                            $comments_query = $conn->prepare("
                                SELECT c.*, u.name as author_name 
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
                            error_log("Error in dashboard.php: " . $e->getMessage());
                            $likes = 0;
                            $is_liked = false;
                            $comments = null;
                            $comment_count = 0;
                        }
                        ?>
                    <div class="post" data-post-id="<?php echo $post['id']; ?>">
                        <div class="post-header">
                            <img src="../uploads/profile/<?php echo htmlspecialchars($post['author_pic']); ?>" alt="Profile" class="post-profile-img">
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
                        <div class="post-actions" style="display: flex; gap: 10px; margin-top: 15px; padding-top: 10px; border-top: 1px solid #e4e6eb;">
                            <button onclick="likePost(<?php echo $post['id']; ?>)" 
                                    style="padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; background: #f0f2f5;">
                                ❤️ <span class="like-count"><?php echo $likes; ?></span>
                            </button>
                            <button onclick="toggleComments(<?php echo $post['id']; ?>)"
                                    style="padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; background: #f0f2f5;">
                                💬 <span class="comment-count"><?php echo $comment_count; ?></span>
                            </button>
                            <button onclick="sharePost(<?php echo $post['id']; ?>)"
                                    style="padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; background: #f0f2f5;">
                                🔗 Share
                            </button>
                            <?php if($post['post_user_id'] == $_SESSION['user_id']): ?>
                                <button onclick="editPost(<?php echo $post['id']; ?>)" 
                                        style="padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; background: #f0f2f5;">
                                    ✏️ Edit
                                </button>
                                <button onclick="deletePost(<?php echo $post['id']; ?>)" 
                                        style="padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; background: #f0f2f5;">
                                    🗑️ Delete
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="comments-section" id="comments-<?php echo $post['id']; ?>" style="display: none; margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                            <div class="comments-container">
                                <?php if($comments): while($comment = $comments->fetch_assoc()): ?>
                                    <div class="comment" data-comment-id="<?php echo $comment['id']; ?>" style="margin-bottom: 10px; padding: 8px; background: white; border-radius: 5px;">
                                        <div style="font-weight: bold;"><?php echo htmlspecialchars($comment['author_name']); ?></div>
                                        <div><?php echo htmlspecialchars($comment['content']); ?></div>
                                        <div style="font-size: 12px; color: #65676b;"><?php echo date('M d, Y H:i', strtotime($comment['created_at'])); ?></div>
                                        <button onclick="deleteComment(<?php echo $comment['id']; ?>, <?php echo $post['id']; ?>)" style="background: red; color: white; border: none; border-radius: 5px; cursor: pointer; padding: 5px;">Delete</button>
                                    </div>
                                <?php endwhile; endif; ?>
                            </div>
                            <form class="comment-form" onsubmit="return addComment(event, <?php echo $post['id']; ?>);" style="margin-top: 10px; display: flex; gap: 10px;">
                                <input type="text" placeholder="Write a comment..." required style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                                <button type="submit" style="padding: 8px 16px; background: #1877f2; color: white; border: none; border-radius: 5px; cursor: pointer;">
                                    Send
                                </button>
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
                    <input type="text" id="chat-search-input" placeholder="Search users to chat...">
                    <div id="chat-search-results" class="chat-search-results"></div>
                </div>
                <div class="chat-list" id="chat-list">
                    <!-- Chat list will be populated via JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/dashboard.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-input');
            const searchBtn = document.getElementById('search-btn');
            const searchResults = document.getElementById('search-results');
            let searchTimeout;

            function performSearch() {
                const query = searchInput.value.trim();
                if (query.length < 1) {
                    searchResults.style.display = 'none';
                    return;
                }

                fetch(`search_users.php?query=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.users) {
                            searchResults.innerHTML = data.users.length > 0 
                                ? data.users.map(user => `
                                    <a href="${user.profile_url}" class="search-result-item">
                                        <img src="../uploads/profile/${user.profile_pic}" alt="${user.name}" class="search-result-img">
                                        <span class="search-result-name">${user.name}</span>
                                    </a>
                                `).join('')
                                : '<div class="no-results">No users found</div>';
                            searchResults.style.display = 'block';
                        } else {
                            console.error('Search error:', data.error);
                            searchResults.innerHTML = '<div class="no-results">No users found</div>';
                            searchResults.style.display = 'block';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        searchResults.innerHTML = '<div class="no-results">No users found</div>';
                        searchResults.style.display = 'block';
                    });
            }

            // Search on input with debounce
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(performSearch, 300);
            });

            // Search on button click
            searchBtn.addEventListener('click', performSearch);

            // Search on Enter key
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });

            // Close search results when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchResults.contains(e.target) && e.target !== searchInput && e.target !== searchBtn) {
                    searchResults.style.display = 'none';
                }
            });

            const chatSearchInput = document.getElementById('chat-search-input');
            const chatSearchResults = document.getElementById('chat-search-results');
            let chatSearchTimeout;

            function performChatSearch() {
                const query = chatSearchInput.value.trim();
                if (query.length < 1) {
                    chatSearchResults.style.display = 'none';
                    return;
                }

                fetch(`search_chat_users.php?query=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        console.log('Search results:', data); // Debug log
                        if (data.success && data.users) {
                            chatSearchResults.innerHTML = data.users.length > 0 
                                ? data.users.map(user => `
                                    <a href="${user.profile_url}" class="search-result-item">
                                        <img src="../uploads/profile/${user.profile_pic}" alt="${user.name}" class="search-result-img">
                                        <span class="search-result-name">${user.name}</span>
                                    </a>
                                `).join('')
                                : '<div class="no-results">No users found</div>';
                            chatSearchResults.style.display = 'block';
                        } else {
                            console.error('Search error:', data.error);
                            chatSearchResults.innerHTML = '<div class="no-results">No users found</div>';
                            chatSearchResults.style.display = 'block';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        chatSearchResults.innerHTML = '<div class="no-results">No users found</div>';
                        chatSearchResults.style.display = 'block';
                    });
            }

            // Search on input with debounce
            chatSearchInput.addEventListener('input', function() {
                clearTimeout(chatSearchTimeout);
                chatSearchTimeout = setTimeout(performChatSearch, 300);
            });

            // Search on button click
            chatSearchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performChatSearch();
                }
            });

            // Close search results when clicking outside
            document.addEventListener('click', function(e) {
                if (!chatSearchResults.contains(e.target) && e.target !== chatSearchInput && e.target !== chatSearchInput) {
                    chatSearchResults.style.display = 'none';
                }
            });
        });

        function deletePost(postId) {
            if (!confirm('Are you sure you want to delete this post?')) return;

            console.log('Attempting to delete post ID:', postId); // Debug log
            
            fetch('delete_post.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    post_id: postId
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Delete response:', data); // Debug log
                if (data.success) {
                    const post = document.querySelector(`[data-post-id="${postId}"]`);
                    if (post) {
                        // Add fade out effect
                        post.style.transition = 'all 0.3s ease';
                        post.style.opacity = '0';
                        post.style.transform = 'translateY(-10px)';
                        
                        // Remove the element after animation
                        setTimeout(() => {
                            post.remove();
                            
                            // Slide up posts beneath
                            const posts = document.querySelectorAll('.post');
                            posts.forEach(p => {
                                if (p.dataset.postId > postId) {
                                    p.style.transition = 'all 0.3s ease';
                                    p.style.transform = 'translateY(-10px)';
                                }
                            });
                        }, 300);
                    }
                } else {
                    console.error('Delete failed:', data.message); // Debug log
                    alert('Failed to delete post: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error); // Debug log
                alert('Failed to delete post. Check console for details.');
            });
        }

        function editPost(postId) {
            console.log('Edit button clicked for post ID:', postId); // Debug log
            const postDiv = document.querySelector(`[data-post-id="${postId}"] .post-content`);
            if (!postDiv) {
                console.error('Post div not found for ID:', postId);
                return;
            }

            const postTextElement = postDiv.querySelector('.post-text');
            if (!postTextElement) {
                console.error('Post text element not found for ID:', postId);
                return;
            }

            const currentContent = postTextElement.innerText.trim();
            const originalHTML = postTextElement.innerHTML;

            // Create a textarea for editing
            const textarea = document.createElement('textarea');
            textarea.value = currentContent;
            textarea.style.width = '100%';
            textarea.style.minHeight = '100px';
            textarea.style.padding = '8px';
            textarea.style.borderRadius = '4px';
            textarea.style.border = '1px solid #ddd';

            // Replace the post text with the textarea
            postTextElement.replaceWith(textarea);
            textarea.focus();

            // Create Save and Cancel buttons
            const buttonContainer = document.createElement('div');
            buttonContainer.style.display = 'flex';
            buttonContainer.style.gap = '10px';
            buttonContainer.style.marginTop = '10px';

            const saveButton = document.createElement('button');
            saveButton.textContent = 'Save';
            saveButton.style.padding = '8px 16px';
            saveButton.style.background = '#1877f2';
            saveButton.style.color = 'white';
            saveButton.style.border = 'none';
            saveButton.style.borderRadius = '4px';
            saveButton.style.cursor = 'pointer';

            const cancelButton = document.createElement('button');
            cancelButton.textContent = 'Cancel';
            cancelButton.style.padding = '8px 16px';
            cancelButton.style.background = '#f0f2f5';
            cancelButton.style.border = 'none';
            cancelButton.style.borderRadius = '4px';
            cancelButton.style.cursor = 'pointer';

            buttonContainer.appendChild(saveButton);
            buttonContainer.appendChild(cancelButton);
            postDiv.appendChild(buttonContainer);

            // Handle Save
            saveButton.addEventListener('click', () => {
                const newContent = textarea.value.trim();
                if (!newContent || newContent === currentContent) {
                    cancelEdit();
                    return;
                }

                fetch('edit_post.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        post_id: postId,
                        content: newContent
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the post content
                        postTextElement.innerHTML = newContent.split('\n').map(line => escapeHtml(line)).join('<br>');
                        postDiv.removeChild(buttonContainer);
                        textarea.replaceWith(postTextElement);
                    } else {
                        alert(data.message || 'Failed to edit post');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to edit post');
                });
            });

            // Handle Cancel
            cancelButton.addEventListener('click', cancelEdit);

            function cancelEdit() {
                postDiv.removeChild(buttonContainer);
                textarea.replaceWith(postTextElement);
            }
        }

        async function addComment(event, postId) {
            event.preventDefault();
            const form = event.target;
            const input = form.querySelector('input');
            const content = input.value.trim();

            if (!content) return false;

            try {
                const response = await fetch('add_comment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        post_id: postId,
                        content: content
                    })
                });

                const data = await response.json();
                if (data.success) {
                    // Create and add the new comment
                    const commentsContainer = form.parentElement.querySelector('.comments-container');
                    const commentElement = document.createElement('div');
                    commentElement.className = 'comment';
                    commentElement.style.cssText = 'margin-bottom: 10px; padding: 8px; background: white; border-radius: 5px;';
                    commentElement.innerHTML = `
                        <div style="font-weight: bold;">${escapeHtml(data.author_name)}</div>
                        <div>${escapeHtml(data.content)}</div>
                        <div style="font-size: 12px; color: #65676b;">${data.created_at}</div>
                    `;
                    
                    // Add comment at the top
                    if (commentsContainer.firstChild) {
                        commentsContainer.insertBefore(commentElement, commentsContainer.firstChild);
                    } else {
                        commentsContainer.appendChild(commentElement);
                    }

                    // Update comment count
                    const commentCount = form.parentElement.parentElement.querySelector('.comment-count');
                    commentCount.textContent = parseInt(commentCount.textContent || '0') + 1;

                    // Clear input
                    input.value = '';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error adding comment. Please try again.');
            }

            return false;
        }

        async function deleteComment(commentId, postId) {
            if (!confirm('Are you sure you want to delete this comment?')) return;

            console.log('Deleting comment ID:', commentId);

            try {
                const response = await fetch('delete_comment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        comment_id: commentId,
                        post_id: postId
                    })
                });

                const data = await response.json();
                if (data.success) {
                    // Remove the comment from the DOM
                    const commentElement = document.querySelector(`.comment[data-comment-id="${commentId}"]`);
                    if (commentElement) {
                        commentElement.remove();
                    }
                } else {
                    alert('Failed to delete comment: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to delete comment. Please try again.');
            }
        }
    </script>
</body>
</html>