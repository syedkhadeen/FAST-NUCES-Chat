<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$current_user = $_SESSION['user_id'];
$current_user_name = htmlspecialchars($_SESSION['user_name']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resources Dashboard</title>
    <link rel="stylesheet" href="../assets/dresources.css">
</head>
<body>
    <div class="container">
        <h2>Resources Dashboard</h2>

        <!-- Buttons for Download and Upload Resources -->
        <div class="button-container">
            <a href="download_resources.php" class="btn-action">Download Resources</a>
            <a href="resource.php" class="btn-action">Upload Resources</a>
        </div>
    </div>
</body>
</html>
