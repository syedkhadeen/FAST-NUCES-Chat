<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat App - Dashboard</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <!-- Post creation form -->
        <div class="post-form-container">
            <form id="post-form" enctype="multipart/form-data">
                <textarea name="content" placeholder="What's on your mind?" required></textarea>
                <div id="image-preview-container" style="display: none;">
                    <img id="preview-image" src="" alt="Preview">
                </div>
                <div class="post-actions">
                    <input type="file" id="post-image" name="image" accept="image/*">
                    <button type="submit">Post</button>
                </div>
            </form>
        </div>

        <!-- Posts container -->
        <div class="posts-container">
            <!-- Posts will be loaded here -->
        </div>
    </div>

    <script src="assets/dashboard.js"></script>
</body>
</html>
