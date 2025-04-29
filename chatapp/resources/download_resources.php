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

// Fetch all resources
$stmt = $conn->prepare("SELECT * FROM resources");
$stmt->execute();
$resources = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Resource</title>
    <link rel="stylesheet" href="../assets/resources.css">
</head>
<body>
    <div class="container">
        <h2>Download Resource</h2>

        <h3>Available Resources</h3>

        <?php if ($resources->num_rows > 0): ?>
            <table class="resources-table">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Type</th>
                        <th>Batch</th>
                        <th>Campus</th>
                        <th>Resource</th>
                        <th>Download</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($resource = $resources->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($resource['subject']); ?></td>
                            <td><?php echo htmlspecialchars($resource['type']); ?></td>
                            <td><?php echo htmlspecialchars($resource['batch']); ?></td>
                            <td><?php echo htmlspecialchars($resource['campus']); ?></td>
                            <td><?php echo htmlspecialchars($resource['original_file_name']); ?></td>
                            <td>
                                <a href="../<?php echo htmlspecialchars($resource['file_path']); ?>" download>
                                    Download
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No resources available to download.</p>
        <?php endif; ?>

        <a href="../user/dashboard.php" class="btn-back">Back to Dashboard</a>
    </div>
</body>
</html>
