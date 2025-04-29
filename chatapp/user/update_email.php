<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

try {
    // First, modify the email column to allow NULL values
    $alter_table = "ALTER TABLE users MODIFY email varchar(255) NULL";
    $conn->query($alter_table);

    // Then update the user's email to NULL
    $stmt = $conn->prepare("UPDATE users SET email = NULL WHERE email = ?");
    $email = 'syed.khadeen@example.com';
    $stmt->bind_param("s", $email);
    
    if ($stmt->execute()) {
        echo "Email updated successfully";
    } else {
        echo "Error updating email: " . $conn->error;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn->close();
?> 