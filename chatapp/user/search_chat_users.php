<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

if (!isset($_GET['query']) || empty($_GET['query'])) {
    echo json_encode(['error' => 'No search query provided']);
    exit;
}

$search_query = '%' . strtolower($_GET['query']) . '%';
$current_user_id = $_SESSION['user_id'];

try {
    // First, let's check if the table exists and has the correct structure
    $check_table = $conn->query("SHOW TABLES LIKE 'users'");
    if ($check_table->num_rows === 0) {
        throw new Exception("Users table does not exist");
    }

    // Search for users whose names match the search query (case-insensitive)
    $stmt = $conn->prepare("
        SELECT id, name, profile_pic, status, email 
        FROM users 
        WHERE LOWER(name) LIKE ? 
        AND id != ? 
        ORDER BY name ASC 
        LIMIT 10
    ");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("si", $search_query, $current_user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    
    if (!$result) {
        throw new Exception("Get result failed: " . $stmt->error);
    }

    $users = [];
    while ($user = $result->fetch_assoc()) {
        // For debugging
        error_log("Found user: " . print_r($user, true));
        
        $users[] = [
            'id' => $user['id'],
            'name' => htmlspecialchars($user['name']),
            'profile_pic' => htmlspecialchars($user['profile_pic'] ?? 'default.jpg'),
            'status' => $user['status'] ?? 'offline',
            'email' => $user['email']
        ];
    }
    
    if (empty($users)) {
        error_log("No users found for query: " . $_GET['query']);
    }

    echo json_encode([
        'success' => true,
        'users' => $users,
        'debug' => [
            'query' => $search_query,
            'current_user' => $current_user_id
        ]
    ]);

} catch (Exception $e) {
    error_log("Chat search error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error searching users: ' . $e->getMessage(),
        'debug' => [
            'query' => $search_query,
            'current_user' => $current_user_id
        ]
    ]);
}

$conn->close();
?> 