<?php
session_start();
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

try {
    // Handle profile picture upload
    if (isset($_FILES['profile_pic'])) {
        $file = $_FILES['profile_pic'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];
        
        // Validate file
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if ($fileError === 0) {
            if (in_array($fileExt, $allowedExtensions)) {
                if ($fileSize < 5 * 1024 * 1024) { // 5MB max
                    // Create uploads directory if it doesn't exist
                    $uploadDir = 'uploads/profile/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    // Generate unique filename
                    $newFileName = uniqid('profile_') . '.' . $fileExt;
                    $destination = $uploadDir . $newFileName;
                    
                    if (move_uploaded_file($fileTmpName, $destination)) {
                        // Update database
                        $stmt = $conn->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
                        $stmt->bind_param("si", $newFileName, $_SESSION['user_id']);
                        $stmt->execute();
                        
                        echo json_encode([
                            'success' => true,
                            'profile_pic_url' => $uploadDir . $newFileName
                        ]);
                        exit;
                    }
                } else {
                    echo json_encode(['error' => 'File too large']);
                    exit;
                }
            } else {
                echo json_encode(['error' => 'Invalid file type']);
                exit;
            }
        }
    }
    
    // Handle bio update
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['bio'])) {
        $stmt = $conn->prepare("UPDATE users SET bio = ? WHERE id = ?");
        $stmt->bind_param("si", $data['bio'], $_SESSION['user_id']);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Bio updated successfully'
        ]);
        exit;
    }
    
    echo json_encode(['error' => 'No valid action specified']);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?> 