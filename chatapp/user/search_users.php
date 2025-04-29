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

$search_query = '%' . $_GET['query'] . '%';

try {
    // Search for users whose names match the search query
    $stmt = $conn->prepare("SELECT id, name, profile_pic FROM users WHERE name LIKE ? AND id != ? LIMIT 10");
    $stmt->bind_param("si", $search_query, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($user = $result->fetch_assoc()) {
        $users[] = [
            'id' => $user['id'],
            'name' => htmlspecialchars($user['name']),
            'profile_pic' => htmlspecialchars($user['profile_pic']),
            'profile_url' => '../profile.php?id=' . $user['id']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'users' => $users
    ]);

} catch (Exception $e) {
    error_log("Search error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error searching users'
    ]);
}

$conn->close();
?> 