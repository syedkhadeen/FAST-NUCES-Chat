<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'includes/db.php';

echo "<pre>";
echo "Session data:\n";
print_r($_SESSION);

echo "\nPOST data:\n";
print_r($_POST);

echo "\nGET data:\n";
print_r($_GET);

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    echo "\nUser data:\n";
    print_r($user);
}

echo "\nServer variables:\n";
print_r($_SERVER);
echo "</pre>";
?> 