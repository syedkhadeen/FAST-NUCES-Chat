<?php
session_start();
require_once '../includes/db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if (!isset($_POST['post_content']) || empty(trim($_POST['post_content']))) {
    echo json_encode(['success' => false, 'message' => 'Post content is required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$content = trim($_POST['post_content']);
$image_path = null;

// Debug log
error_log("Received post request - User ID: $user_id");
error_log("Files array: " . print_r($_FILES, true));

// Handle image upload if present
if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../uploads/posts/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            error_log("Failed to create directory: $upload_dir");
            echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
            exit;
        }
    }
    
    // Validate file type
    $file_info = getimagesize($_FILES['post_image']['tmp_name']);
    if ($file_info === false) {
        error_log("Invalid image file");
        echo json_encode(['success' => false, 'message' => 'Invalid image file']);
        exit;
    }
    
    $allowed_types = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF];
    if (!in_array($file_info[2], $allowed_types)) {
        error_log("Invalid image type");
        echo json_encode(['success' => false, 'message' => 'Invalid image type. Only JPG, PNG, and GIF are allowed.']);
        exit;
    }
    
    // Generate unique filename
    $file_extension = image_type_to_extension($file_info[2]);
    $file_name = uniqid('post_') . $file_extension;
    $target_path = $upload_dir . $file_name;
    
    error_log("Attempting to move uploaded file to: $target_path");
    
    if (move_uploaded_file($_FILES['post_image']['tmp_name'], $target_path)) {
        $image_path = '../uploads/posts/' . $file_name;
        error_log("File uploaded successfully to: $image_path");
    } else {
        error_log("Failed to move uploaded file. Upload error code: " . $_FILES['post_image']['error']);
        echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
        exit;
    }
}

try {
    // Begin transaction
    $conn->begin_transaction();

    // Create the post
    $create_post = $conn->prepare("INSERT INTO posts (user_id, content, image_path) VALUES (?, ?, ?)");
    $create_post->bind_param("iss", $user_id, $content, $image_path);
    
    if (!$create_post->execute()) {
        throw new Exception("Failed to create post: " . $conn->error);
    }
    
    $post_id = $conn->insert_id;
    
    // Get the created post with author information
    $get_post = $conn->prepare("
        SELECT p.*, u.name as author_name 
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.id = ?
    ");
    $get_post->bind_param("i", $post_id);
    $get_post->execute();
    $post = $get_post->get_result()->fetch_assoc();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Post created successfully',
        'post' => $post
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Delete uploaded image if it exists
    if ($image_path && file_exists($target_path)) {
        unlink($target_path);
    }
    
    error_log("Error in create_post.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?> 