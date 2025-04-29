<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user data
$stmt = $conn->prepare("SELECT name, profile_pic FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();
?>

<header class="main-header">
    <div class="header-content">
        <div class="left-section">
            <a href="profile.php" class="profile-link" onclick="window.location.href='profile.php'; return false;">
                <img src="uploads/profile/<?php echo htmlspecialchars($current_user['profile_pic']); ?>" alt="Profile" class="header-profile-pic">
                <span class="profile-name"><?php echo htmlspecialchars($current_user['name']); ?></span>
            </a>
        </div>
        <nav class="main-nav">
            <a href="/chatapp/chatapp/user/dashboard.php" class="nav-link">Home</a>
            <a href="/chatapp/chatapp/messages.php" class="nav-link">Messages</a>
            <a href="/chatapp/chatapp/groups.php" class="nav-link">Groups</a>
            <a href="/chatapp/chatapp/forums.php" class="nav-link">Forums</a>
        </nav>
        <div class="right-section">
            <div class="user-menu" id="userMenu">
                <div class="user-menu-trigger">
                    <a href="/chatapp/chatapp/profile.php" class="profile-menu-link">
                        <img src="uploads/profile/<?php echo htmlspecialchars($current_user['profile_pic']); ?>" alt="Profile" class="header-profile-pic">
                        <span class="dropdown-arrow">▼</span>
                    </a>
                </div>
                <div class="dropdown-content">
                    <a href="/chatapp/chatapp/profile.php" class="dropdown-item">Profile</a>
                    <a href="/chatapp/chatapp/settings.php" class="dropdown-item">Settings</a>
                    <a href="/chatapp/chatapp/logout.php" class="dropdown-item">Logout</a>
                </div>
            </div>
        </div>
    </div>
</header>

<style>
.main-header {
    background: #fff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    padding: 10px 0;
    position: sticky;
    top: 0;
    z-index: 1000;
}

.header-content {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 20px;
}

.left-section {
    display: flex;
    align-items: center;
}

.profile-link {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: #333;
    gap: 10px;
    padding: 8px 15px;
    border-radius: 5px;
    transition: all 0.2s ease;
    cursor: pointer;
}

.profile-link:hover {
    background-color: #f0f2f5;
    text-decoration: none;
    color: #1877f2;
}

.profile-name {
    font-weight: 500;
}

.header-profile-pic {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.main-nav {
    display: flex;
    gap: 20px;
}

.nav-link {
    text-decoration: none;
    color: #333;
    padding: 8px 16px;
    border-radius: 5px;
    transition: all 0.2s ease;
    font-weight: 500;
    cursor: pointer;
}

.nav-link:hover {
    background-color: #f0f2f5;
    color: #1877f2;
    text-decoration: none;
}

.right-section {
    position: relative;
}

.user-menu {
    position: relative;
    cursor: pointer;
}

.user-menu-trigger {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 5px;
    border-radius: 5px;
    transition: background-color 0.2s;
}

.profile-menu-link {
    display: flex;
    align-items: center;
    gap: 5px;
    text-decoration: none;
    color: #333;
    padding: 5px;
    border-radius: 5px;
    transition: all 0.2s ease;
}

.user-menu-trigger:hover {
    background-color: #f0f2f5;
}

.dropdown-arrow {
    font-size: 12px;
    color: #666;
    margin-left: 5px;
}

.dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    background: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 8px;
    padding: 8px 0;
    min-width: 180px;
    margin-top: 5px;
}

.user-menu:hover .dropdown-content {
    display: block;
}

.dropdown-item {
    display: flex;
    padding: 10px 20px;
    text-decoration: none;
    color: #333;
    transition: all 0.2s ease;
    cursor: pointer;
}

.dropdown-item:hover {
    background-color: #f0f2f5;
    color: #1877f2;
    text-decoration: none;
}

/* Add active state for current page */
.nav-link.active,
.dropdown-item.active {
    color: #1877f2;
    background-color: #e7f3ff;
}

/* Make sure all clickable elements show pointer cursor */
a, button, .user-menu, .profile-link {
    cursor: pointer;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Make sure all profile links work
    const profileLinks = document.querySelectorAll('.profile-link, .profile-menu-link, .dropdown-item');
    profileLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.getAttribute('href')) {
                window.location.href = this.getAttribute('href');
            }
        });
    });

    // Add active state to current page
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';
    document.querySelectorAll('.nav-link, .dropdown-item').forEach(link => {
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });
});
</script> 