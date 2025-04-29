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

// Fetch all forums
$stmt = $conn->prepare("SELECT * FROM forums");
$stmt->execute();
$forums = $stmt->get_result();

// Handle new forum creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_forum'])) {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);

    if (!empty($title)) {
        $stmt = $conn->prepare("INSERT INTO forums (title, description, created_by) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $title, $description, $current_user);
        if ($stmt->execute()) {
            header("Location: forums.php");
            exit;
        } else {
            $error = "Failed to create the forum.";
        }
    } else {
        $error = "Title cannot be empty.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forums</title>
    <link rel="stylesheet" href="../assets/forum.css">
</head>
<body>
    <div class="container">
        <h2>Forums</h2>

        <!-- Display all forums -->
        <div class="forum-list">
            <?php if ($forums->num_rows > 0): ?>
                <ul>
                    <?php while ($forum = $forums->fetch_assoc()): ?>
                        <li>
                            <a href="forum_view.php?forum_id=<?php echo $forum['id']; ?>">
                                <?php echo htmlspecialchars($forum['title']); ?>
                            </a>
                            <p><?php echo htmlspecialchars($forum['description']); ?></p>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No forums available.</p>
            <?php endif; ?>
        </div>

        <!-- Button to create a new forum -->
        <button id="createForumBtn">Create New Forum</button>

        <!-- Create Forum Modal -->
        <div id="createForumModal" class="modal">
            <div class="modal-content">
                <span class="close-btn" onclick="closeModal()">×</span>
                <h2>Create New Forum</h2>
                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST">
                    <label for="title">Forum Title:</label>
                    <input type="text" name="title" required>

                    <label for="description">Forum Description:</label>
                    <textarea name="description" rows="4" required></textarea>

                    <button type="submit" name="create_forum" class="btn-send">Create Forum</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Show the create forum modal
        document.getElementById('createForumBtn').addEventListener('click', function() {
            document.getElementById('createForumModal').style.display = 'block';
        });

        // Close the modal
        function closeModal() {
            document.getElementById('createForumModal').style.display = 'none';
        }
    </script>
</body>
</html>
