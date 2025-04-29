// Function to create a new post element
function createPostElement(post) {
    const div = document.createElement('div');
    div.className = 'post';
    div.setAttribute('data-post-id', post.id);
    
    const isOwner = post.user_id === currentUserId;
    
    div.innerHTML = `
        <div class="post-header">
            <div class="post-author-info">
                <img src="../uploads/profile/${escapeHtml(post.author_pic)}" alt="Profile" class="post-profile-img">
                <div>
                    <div class="post-author">${escapeHtml(post.author_name)}</div>
                    <div class="post-time">${formatDate(post.created_at)}</div>
                </div>
            </div>
            ${isOwner ? `
                <div class="post-actions-menu">
                    <button type="button" class="menu-btn">Options ▼</button>
                    <div class="post-menu">
                        <button type="button" onclick="editPost(${post.id})">Edit Post</button>
                        <button type="button" onclick="deletePost(${post.id})">Delete Post</button>
                    </div>
                </div>
            ` : ''}
        </div>
        <div class="post-content">
            <div class="post-text" id="post-text-${post.id}">${post.content ? post.content.split('\n').map(line => escapeHtml(line)).join('<br>') : ''}</div>
            ${post.image_path ? `
                <div class="post-image-container">
                    <img src="${escapeHtml(post.image_path)}" alt="Post image" class="post-image" onclick="showImagePreview('${escapeHtml(post.image_path)}')">
                </div>
            ` : ''}
        </div>
        <div class="post-actions">
            <button class="like-btn ${post.is_liked ? 'liked' : ''}" onclick="likePost(${post.id})">
                <span class="like-icon">❤️</span>
                <span class="like-count">${post.likes || 0}</span>
            </button>
            <button class="comment-btn" onclick="toggleComments(${post.id})">
                💬 <span class="comment-count">${post.comments || 0}</span>
            </button>
            <button class="share-btn" onclick="sharePost(${post.id})">
                🔗 Share
            </button>
        </div>
        <div class="comments-section" style="display: none;">
            <div class="comments-container"></div>
            <form class="comment-form" onsubmit="return addComment(event, ${post.id})">
                <input type="text" placeholder="Write a comment..." required>
                <button type="submit">Send</button>
            </form>
        </div>
    `;
    
    return div;
}

// Function to show image preview
function showImagePreview(imagePath) {
    const modal = document.createElement('div');
    modal.className = 'image-preview-modal';
    modal.innerHTML = `
        <div class="modal-content">
            <span class="close-modal" onclick="this.parentElement.parentElement.remove()">&times;</span>
            <img src="${imagePath}" alt="Full size image">
        </div>
    `;
    document.body.appendChild(modal);
}

// Function to toggle post menu
function togglePostMenu(postId) {
    const menu = document.getElementById(`post-menu-${postId}`);
    if (!menu) return;
    
    // Close all other menus first
    document.querySelectorAll('.post-menu').forEach(m => {
        if (m !== menu) m.style.display = 'none';
    });
    
    menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    
    // Add click outside listener to close menu
    const closeMenu = (e) => {
        if (!menu.contains(e.target) && !e.target.matches('.menu-btn')) {
            menu.style.display = 'none';
            document.removeEventListener('click', closeMenu);
        }
    };
    document.addEventListener('click', closeMenu);
}

// Initialize post menus
document.addEventListener('DOMContentLoaded', function() {
    // Add click handlers for menu buttons
    document.querySelectorAll('.menu-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const menu = this.nextElementSibling;
            
            // Close all other menus
            document.querySelectorAll('.post-menu').forEach(m => {
                if (m !== menu) m.style.display = 'none';
            });
            
            // Toggle this menu
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        });
    });
    
    // Close menus when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.post-actions-menu')) {
            document.querySelectorAll('.post-menu').forEach(menu => {
                menu.style.display = 'none';
            });
        }
    });
});

// Function to show comments
function showComments(postId) {
    const commentsSection = document.getElementById(`comments-${postId}`);
    if (commentsSection) {
        commentsSection.style.display = commentsSection.style.display === 'none' ? 'block' : 'none';
    }
}

// Function to add a comment
function addComment(postId) {
    const input = document.getElementById(`comment-input-${postId}`);
    const content = input.value.trim();
    
    if (!content) return;

    fetch('add_comment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            post_id: postId,
            content: content
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear input
            input.value = '';
            
            // Add new comment to the list
            const commentsContainer = document.querySelector(`#comments-${postId} .comments-container`);
            const newComment = document.createElement('div');
            newComment.style = 'margin-bottom: 10px; padding: 8px; background: white; border-radius: 5px;';
            newComment.innerHTML = `
                <div style="font-weight: bold;">${data.author_name}</div>
                <div>${data.content}</div>
                <div style="font-size: 12px; color: #65676b;">${data.created_at}</div>
            `;
            commentsContainer.insertBefore(newComment, commentsContainer.firstChild);

            // Update comment count
            const countSpan = document.querySelector(`button[onclick="showComments(${postId})"] .comment-count`);
            if (countSpan) {
                countSpan.textContent = parseInt(countSpan.textContent || 0) + 1;
            }
        } else {
            alert(data.message || 'Failed to add comment');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to add comment');
    });
}

// Function to edit post
function editPost(postId) {
    const postDiv = document.querySelector(`[data-post-id="${postId}"] .post-content`);
    if (!postDiv) return;

    const currentContent = postDiv.innerText.trim();
    const newContent = prompt('Edit your post:', currentContent);
    
    if (!newContent || newContent === currentContent) return;

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
            postDiv.innerHTML = newContent.split('\n').map(line => line).join('<br>');
        } else {
            alert(data.message || 'Failed to edit post');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to edit post');
    });
}

// Function to delete post
function deletePost(postId) {
    if (!confirm('Are you sure you want to delete this post?')) return;

    fetch('delete_post.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            post_id: postId
        })
    })
    .then(response => response.json())
    .then(data => {
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
            alert(data.message || 'Failed to delete post');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to delete post');
    });
}

// Function to report post
function reportPost(postId) {
    alert('Post has been reported. Our team will review it shortly.');
    // Close the menu after reporting
    document.querySelectorAll('.post-menu').forEach(menu => {
        menu.style.display = 'none';
    });
}

// Helper function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Function to format date
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    
    if (diff < 60000) { // Less than 1 minute
        return 'Just now';
    } else if (diff < 3600000) { // Less than 1 hour
        const minutes = Math.floor(diff / 60000);
        return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
    } else if (diff < 86400000) { // Less than 1 day
        const hours = Math.floor(diff / 3600000);
        return `${hours} hour${hours > 1 ? 's' : ''} ago`;
    } else {
        return date.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric', 
            year: 'numeric' 
        });
    }
} 