<?php
require_once 'includes/db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Update users table to include bio and profile_pic
    $conn->query("ALTER TABLE users 
        ADD COLUMN IF NOT EXISTS bio TEXT DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS profile_pic VARCHAR(255) DEFAULT 'default.jpg'");

    echo "Users table updated successfully!\n";

    // Create posts table
    $conn->query("CREATE TABLE IF NOT EXISTS posts (
        id int(11) NOT NULL AUTO_INCREMENT,
        user_id int(11) NOT NULL,
        content text,
        image_path varchar(255) DEFAULT NULL,
        created_at timestamp NULL DEFAULT current_timestamp(),
        PRIMARY KEY (id),
        KEY user_id (user_id),
        CONSTRAINT posts_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo "Posts table created successfully!\n";

    // Create likes table
    $conn->query("CREATE TABLE IF NOT EXISTS likes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id),
        UNIQUE KEY unique_like (post_id, user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo "Likes table created successfully!\n";

    // Create comments table
    $conn->query("CREATE TABLE IF NOT EXISTS comments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo "Comments table created successfully!\n";

} catch (Exception $e) {
    echo "Error creating tables: " . $e->getMessage() . "\n";
}

$conn->close();
?> 