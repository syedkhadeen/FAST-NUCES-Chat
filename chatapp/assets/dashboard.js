// Chat functionality
document.addEventListener('DOMContentLoaded', function() {
    initializeImageUpload();
    initializePostForm();
    initializeInfiniteScroll();

    // Chat functionality
    const userSearchInput = document.querySelector('.chat-search input');
    const chatList = document.querySelector('.chat-list');

    if (userSearchInput && chatList) {
        userSearchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.trim();
            if (searchTerm.length > 2) {
                searchUsers(searchTerm);
            } else {
                chatList.innerHTML = '';
            }
        });
    }

    function searchUsers(searchTerm) {
        fetch(`search_users.php?q=${encodeURIComponent(searchTerm)}`)
            .then(response => response.json())
            .then(users => {
                chatList.innerHTML = '';
                users.forEach(user => {
                    const userElement = createUserElement(user);
                    chatList.appendChild(userElement);
                });
            })
            .catch(error => console.error('Error searching users:', error));
    }

    function createUserElement(user) {
        const div = document.createElement('div');
        div.className = 'chat-user';
        div.innerHTML = `
            <img src="../uploads/profile/${user.profile_pic || 'default.png'}" alt="${escapeHtml(user.name)}" class="profile-img">
            <div class="user-info">
                <h4>${escapeHtml(user.name)}</h4>
                <small>${user.status || 'offline'}</small>
            </div>
        `;
        div.addEventListener('click', () => startChat(user.id));
        return div;
    }

    function startChat(userId) {
        window.location.href = `messages.php?user_id=${userId}`;
    }

    // Like functionality
    document.querySelectorAll('.post-actions button').forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.closest('.post-card').dataset.postId;
            const action = this.dataset.action;
            
            if (action === 'like') {
                toggleLike(postId);
            } else if (action === 'comment') {
                showCommentBox(postId);
            }
        });
    });

    function toggleLike(postId) {
        fetch('toggle_like.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ post_id: postId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const likeButton = document.querySelector(`[data-post-id="${postId}"] [data-action="like"]`);
                likeButton.classList.toggle('liked', data.liked);
                likeButton.innerHTML = `<i class="far fa-heart"></i> ${data.likeCount} Likes`;
            }
        });
    }

    function showCommentBox(postId) {
        // Implement comment box display logic
    }

    // Initialize infinite scroll
    function initializeInfiniteScroll() {
        let page = 1;
        let loading = false;
        const postsContainer = document.querySelector('.posts-container');

        function loadMorePosts() {
            if (loading) return;
            loading = true;

            fetch(`load_posts.php?page=${page}`)
                .then(response => response.json())
                .then(data => {
                    if (data.posts && data.posts.length > 0) {
                        data.posts.forEach(post => {
                            const postElement = createPostElement(post);
                            postsContainer.appendChild(postElement);
                        });
                        page++;
                        loading = false;
                    }
                })
                .catch(error => {
                    console.error('Error loading posts:', error);
                    loading = false;
                });
        }

        // Detect when user scrolls near bottom
        window.addEventListener('scroll', () => {
            if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 1000) {
                loadMorePosts();
            }
        });

        // Load initial posts
        loadMorePosts();
    }
});

// Initialize image upload functionality
function initializeImageUpload() {
    const fileInput = document.getElementById('post-image');
    const previewContainer = document.getElementById('image-preview-container');
    const previewImage = document.getElementById('preview-image');

    if (fileInput && previewContainer && previewImage) {
        // Create remove button if it doesn't exist
        let removeButton = previewContainer.querySelector('.remove-image');
        if (!removeButton) {
            removeButton = document.createElement('button');
            removeButton.className = 'remove-image';
            removeButton.innerHTML = '×';
            removeButton.style.cssText = `
                position: absolute;
                top: 5px;
                right: 5px;
                background: rgba(0, 0, 0, 0.5);
                color: white;
                border: none;
                border-radius: 50%;
                width: 25px;
                height: 25px;
                cursor: pointer;
                display: none;
            `;
            previewContainer.appendChild(removeButton);
        }

        fileInput.onchange = function() {
            const file = this.files[0];
            if (file) {
                if (!file.type.startsWith('image/')) {
                    alert('Please select an image file');
                    this.value = '';
                    return;
                }

                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewContainer.style.display = 'block';
                    removeButton.style.display = 'block';
                    
                    // Set preview container size
                    previewContainer.style.cssText = `
                        position: relative;
                        width: 150px;
                        height: 150px;
                        margin: 10px 0;
                        overflow: hidden;
                    `;
                    
                    // Style preview image
                    previewImage.style.cssText = `
                        width: 100%;
                        height: 100%;
                        object-fit: cover;
                    `;
                };
                reader.readAsDataURL(file);
            }
        };

        // Add remove button functionality
        removeButton.onclick = function(e) {
            e.preventDefault();
            fileInput.value = '';
            previewContainer.style.display = 'none';
            previewImage.src = '';
            removeButton.style.display = 'none';
        };
    }
}

// Function to create a new post element
function createPostElement(post) {
    const div = document.createElement('div');
    div.className = 'post';
    div.setAttribute('data-post-id', post.id);
    
    div.innerHTML = `
        <div class="post-header">
            <img src="../uploads/profile/default.png" alt="Profile" class="post-profile-img">
            <div>
                <div class="post-author">${escapeHtml(post.author_name)}</div>
                <div class="post-time">Just now</div>
            </div>
        </div>
        <div class="post-content">
            ${post.content ? post.content.split('\n').map(line => escapeHtml(line)).join('<br>') : ''}
            ${post.image_path ? `<img src="${escapeHtml(post.image_path)}" alt="Post image" class="post-image">` : ''}
        </div>
        <div class="post-actions">
            <button class="like-btn" onclick="likePost(${post.id})">
                <span class="like-icon">❤️</span>
                <span class="like-count">0</span>
            </button>
            <button class="comment-btn" onclick="toggleComments(${post.id})">
                💬 <span class="comment-count">0</span>
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

// Helper function to escape HTML
function escapeHtml(unsafe) {
    if (!unsafe) return '';
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Function to handle likes
async function likePost(postId) {
    try {
        const response = await fetch('like_post.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `post_id=${postId}`
        });

        const data = await response.json();
        if (data.success) {
            const post = document.querySelector(`[data-post-id="${postId}"]`);
            const likeBtn = post.querySelector('.like-btn');
            const likeCount = post.querySelector('.like-count');

            likeCount.textContent = data.likes;
            if (data.action === 'liked') {
                likeBtn.classList.add('liked');
            } else {
                likeBtn.classList.remove('liked');
            }
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Function to toggle comments
function toggleComments(postId) {
    const post = document.querySelector(`[data-post-id="${postId}"]`);
    const commentsSection = post.querySelector('.comments-section');
    commentsSection.style.display = commentsSection.style.display === 'none' ? 'block' : 'none';
}

// Function to add comment
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

// Function to share post
function sharePost(postId) {
    const url = `${window.location.origin}/chatapp/post.php?id=${postId}`;
    navigator.clipboard.writeText(url).then(() => {
        alert('Link copied to clipboard!');
    }).catch(() => {
        prompt('Copy this link:', url);
    });
}

function initializePostForm() {
    const postForm = document.getElementById('post-form');
    if (postForm) {
        postForm.onsubmit = function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Posting...';

            fetch('create_post.php', {
                method: 'POST',
                body: new FormData(this)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Create and add the new post
                    const postsContainer = document.querySelector('.posts-container');
                    const newPost = createPostElement(data.post);
                    
                    // Add new post at the top
                    if (postsContainer.firstChild) {
                        postsContainer.insertBefore(newPost, postsContainer.firstChild);
                    } else {
                        postsContainer.appendChild(newPost);
                    }

                    // Clear the form and preview
                    this.reset();
                    const previewContainer = document.getElementById('image-preview-container');
                    const previewImage = document.getElementById('preview-image');
                    const removeButton = previewContainer.querySelector('.remove-image');
                    
                    if (previewContainer && previewImage) {
                        previewContainer.style.display = 'none';
                        previewImage.src = '';
                        if (removeButton) {
                            removeButton.style.display = 'none';
                        }
                    }

                    // Remove "No posts" message if it exists
                    const noPostsMessage = document.querySelector('.no-posts');
                    if (noPostsMessage) {
                        noPostsMessage.remove();
                    }
                } else {
                    alert(data.message || 'Error creating post');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error creating post. Please try again.');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Post';
            });
        };
    }
}