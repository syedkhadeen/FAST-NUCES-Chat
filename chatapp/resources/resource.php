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

// List of allowed subjects
$allowed_subjects = [
    'PF', 'OOP', 'DATA STRUCTURE', 'DATA BASE SYSTEM', 'OPERATING SYSTEM',
    'COMPUTER NETWORKS', 'ISE', 'SRE', 'SDA', 'DESIGN ANALYSIS', 'WEB'
];

// List of campuses
$campuses = [
    'Lahore', 'Karachi', 'Islamabad', 'Peshawar', 'Multan', 'Faisalabad'
];

// Function to sanitize file names
function sanitize_file_name($filename) {
    return preg_replace('/[^a-zA-Z0-9-_\.]/', '', $filename);
}

// Handle resource upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_resource'])) {
    $subject = sanitize($_POST['subject']);
    $type = sanitize($_POST['type']);
    $batch = sanitize($_POST['batch']);
    $campus = sanitize($_POST['campus']);
    $file_path = null;
    $original_file_name = null;

    // Check if the subject is allowed
    if (!in_array($subject, $allowed_subjects)) {
        $error = "Invalid subject selected.";
    }

    // Handle file upload (only PDFs allowed)
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $allowed_types = ['application/pdf'];
        $max_size = 5 * 1024 * 1024; // 5MB
        $file = $_FILES['file'];

        if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
            // Define the directory based on the subject and campus
            $upload_dir = __DIR__ . '/../uploads/resources/' . sanitize_file_name($subject) . '/' . sanitize_file_name($campus) . '/';
            $original_file_name = basename($file['name']);
            $file_name = $current_user_name . '_' . time() . '_' . sanitize_file_name($file['name']);
            $file_path = $upload_dir . $file_name;

            // Create subject and campus directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Check if file already exists, prevent duplicates
            if (file_exists($file_path)) {
                $error = "The file already exists in the selected subject and campus directory.";
            } else {
                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    $file_path = 'uploads/resources/' . sanitize_file_name($subject) . '/' . sanitize_file_name($campus) . '/' . $file_name;

                    // Insert resource data into the database
                    $stmt = $conn->prepare("INSERT INTO resources (user_id, subject, type, batch, campus, file_path, original_file_name) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("issssss", $current_user, $subject, $type, $batch, $campus, $file_path, $original_file_name);
                    if ($stmt->execute()) {
                        // Redirect to the same page after resource upload
                        header("Location: " . $_SERVER['PHP_SELF']);
                        exit;
                    } else {
                        $error = "Failed to store resource data.";
                    }
                } else {
                    $error = "Failed to upload file.";
                }
            }
        } else {
            $error = "Invalid file type or size. Only PDFs are allowed, and the file must be less than 5MB.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Resource</title>
    <link rel="stylesheet" href="../assets/resources.css">
</head>
<body>
    <div class="container">
        <h2>Upload Resource</h2>
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="resource-form">
            <label for="subject">Subject:</label>
            <select name="subject" required>
                <option value="PF">PF</option>
                <option value="OOP">OOP</option>
                <option value="DATA STRUCTURE">DATA STRUCTURE</option>
                <option value="DATA BASE SYSTEM">DATA BASE SYSTEM</option>
                <option value="OPERATING SYSTEM">OPERATING SYSTEM</option>
                <option value="COMPUTER NETWORKS">COMPUTER NETWORKS</option>
                <option value="ISE">ISE</option>
                <option value="SRE">SRE</option>
                <option value="SDA">SDA</option>
                <option value="DESIGN ANALYSIS">DESIGN ANALYSIS</option>
                <option value="WEB">WEB</option>
            </select>

            <label for="type">Type:</label>
            <select name="type" required>
                <option value="sessional1">Sessional 1</option>
                <option value="sessional2">Sessional 2</option>
                <option value="quiz1">Quiz 1</option>
                <option value="quiz2">Quiz 2</option>
                <option value="quiz3">Quiz 3</option>
                <option value="quiz4">Quiz 4</option>
                <option value="final">Final Exam</option>
                <option value="assignment1">Assignment 1</option>
                <option value="assignment2">Assignment 2</option>
                <option value="assignment3">Assignment 3</option>
                <option value="assignment4">Assignment 4</option>
                <option value="campus">Campus Resource</option>
            </select>

            <label for="batch">Batch:</label>
            <select name="batch" required>
                <option value="22">Batch 22</option>
                <option value="23">Batch 23</option>
                <option value="24">Batch 24</option>
                <option value="25">Batch 25</option>
                <option value="26">Batch 26</option>
            </select>

            <label for="campus">Campus:</label>
            <select name="campus" required>
                <option value="Lahore">Lahore</option>
                <option value="Karachi">Karachi</option>
                <option value="Islamabad">Islamabad</option>
                <option value="Peshawar">Peshawar</option>
                <option value="Multan">Multan</option>
                <option value="Faisalabad">Faisalabad</option>
            </select>

            <label for="file">Choose File (PDF Only):</label>
            <input type="file" name="file" accept="application/pdf" required>

            <button type="submit" name="upload_resource" class="btn-send">Upload Resource</button>
        </form>

        <a href="../user/dashboard.php" class="btn-back">Back to Dashboard</a>
    </div>

    <script>
        // Close modal function
        function closeModal() {
            document.getElementById('uploadResourceModal').style.display = 'none';
        }
    </script>
</body>
</html>
