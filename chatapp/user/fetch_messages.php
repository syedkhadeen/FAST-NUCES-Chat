<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$current_user = $_SESSION['user_id'];
$receiver_id = isset($_GET['receiver_id']) ? intval($_GET['receiver_id']) : null;

if (!$receiver_id) {
    echo json_encode(['error' => 'Receiver ID not provided']);
    exit;
}

// Fetch messages
$stmt = $conn->prepare("SELECT * FROM messages WHERE 
    (sender_id = ? AND receiver_id = ?) OR 
    (sender_id = ? AND receiver_id = ?) 
    ORDER BY timestamp ASC");
$stmt->bind_param("iiii", $current_user, $receiver_id, $receiver_id, $current_user);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Output messages as HTML
ob_start();
foreach ($messages as $msg) {
    $class = $msg['sender_id'] == $current_user ? 'you' : 'them';
    echo '<p class="' . $class . '">';
    echo htmlspecialchars($msg['content']);
    if ($msg['file_path'] && $msg['original_file_name']) {
        echo '<br><a href="../' . htmlspecialchars($msg['file_path']) . '" download>';
        echo htmlspecialchars($msg['original_file_name']);
        echo '</a>';
    }
    echo '</p>';
}
$html = ob_get_clean();
echo $html;
?>