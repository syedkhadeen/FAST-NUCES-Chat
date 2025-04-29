<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Error executing query: " . $conn->error);
}

$user = $result->fetch_assoc();

if (!$user) {
    die("User not found");
}

// Get user's post count
$stmt = $conn->prepare("SELECT COUNT(*) as post_count FROM posts WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$post_count = $stmt->get_result()->fetch_assoc()['post_count'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo htmlspecialchars($user['name']); ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .profile-pic-container {
            position: relative;
            width: 150px;
            height: 150px;
        }

        .profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
        }

        .profile-actions {
            position: absolute;
            bottom: 0;
            right: 0;
            display: flex;
            gap: 5px;
        }

        .change-photo-btn, .save-photo-btn {
            background: #007bff;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .save-photo-btn {
            display: none;
            background: #28a745;
        }

        #photo-input {
            display: none;
        }

        .alert {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            display: none;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .profile-info {
            flex-grow: 1;
        }

        .profile-stats {
            display: flex;
            gap: 20px;
            margin: 10px 0;
        }

        .stat {
            text-align: center;
        }

        .bio-container {
            margin: 20px 0;
        }

        .bio-text {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            min-height: 100px;
            margin-bottom: 10px;
        }

        .edit-bio-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="profile-container">
        <div id="alert" class="alert"></div>
        <div class="profile-header">
            <div class="profile-pic-container">
                <img src="uploads/profile/<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile Picture" class="profile-pic" id="profile-pic">
                <div class="profile-actions">
                    <label for="photo-input" class="change-photo-btn" title="Change Photo">
                        <span>📷</span>
                    </label>
                    <button id="save-photo-btn" class="save-photo-btn" title="Save Photo">
                        <span>✓</span>
                    </button>
                </div>
                <input type="file" id="photo-input" accept="image/*">
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($user['name']); ?></h1>
                <div class="profile-stats">
                    <div class="stat">
                        <div class="stat-value"><?php echo $post_count; ?></div>
                        <div class="stat-label">Posts</div>
                    </div>
                    <!-- Add more stats here if needed -->
                </div>
            </div>
        </div>

        <div class="bio-container">
            <h3>Bio</h3>
            <div class="bio-text" id="bio-text"><?php echo nl2br(htmlspecialchars($user['bio'] ?? '')); ?></div>
            <button class="edit-bio-btn" id="edit-bio-btn">Edit Bio</button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const photoInput = document.getElementById('photo-input');
            const profilePic = document.getElementById('profile-pic');
            const savePhotoBtn = document.getElementById('save-photo-btn');
            const bioText = document.getElementById('bio-text');
            const editBioBtn = document.getElementById('edit-bio-btn');
            const alertDiv = document.getElementById('alert');
            let isEditingBio = false;
            let selectedFile = null;

            function showAlert(message, type) {
                alertDiv.textContent = message;
                alertDiv.className = `alert alert-${type}`;
                alertDiv.style.display = 'block';
                setTimeout(() => {
                    alertDiv.style.display = 'none';
                }, 3000);
            }

            function updateHeaderImages(newImageUrl) {
                // Update all profile pictures in the header
                const headerProfilePics = document.querySelectorAll('.header-profile-pic');
                headerProfilePics.forEach(img => {
                    img.src = newImageUrl;
                });
            }

            // Profile photo upload
            photoInput.addEventListener('change', function(e) {
                const file = this.files[0];
                if (file) {
                    if (!file.type.startsWith('image/')) {
                        showAlert('Please select an image file', 'error');
                        return;
                    }

                    if (file.size > 5 * 1024 * 1024) {
                        showAlert('File size must be less than 5MB', 'error');
                        return;
                    }

                    selectedFile = file;
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        profilePic.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                    savePhotoBtn.style.display = 'flex';
                }
            });

            // Save photo button click
            savePhotoBtn.addEventListener('click', function() {
                if (!selectedFile) return;

                const formData = new FormData();
                formData.append('profile_pic', selectedFile);

                fetch('update_profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Profile picture updated successfully', 'success');
                        updateHeaderImages(data.profile_pic_url);
                        savePhotoBtn.style.display = 'none';
                        selectedFile = null;
                    } else {
                        showAlert(data.error || 'Error updating profile picture', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Error updating profile picture', 'error');
                });
            });

            // Bio editing
            editBioBtn.addEventListener('click', function() {
                if (!isEditingBio) {
                    const currentBio = bioText.innerText;
                    bioText.innerHTML = `<textarea style="width: 100%; min-height: 100px; padding: 8px;">${currentBio}</textarea>`;
                    editBioBtn.textContent = 'Save Bio';
                    isEditingBio = true;
                } else {
                    const newBio = bioText.querySelector('textarea').value;
                    
                    fetch('update_profile.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ bio: newBio })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            bioText.innerHTML = newBio.replace(/\n/g, '<br>');
                            editBioBtn.textContent = 'Edit Bio';
                            isEditingBio = false;
                        } else {
                            alert(data.message || 'Error updating bio');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error updating bio');
                    });
                }
            });
        });
    </script>
</body>
</html> 