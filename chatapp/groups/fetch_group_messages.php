<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$current_user = $_SESSION['user_id'];
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : null;
$other_user_id = isset($_GET['to']) ? (int)$_GET['to'] : null;

if (!$group_id) {
    echo json_encode(['error' => 'No group specified']);
    exit;
}

// Check if user is a member of the group
$stmt = $conn->prepare("SELECT 1 FROM group_memberships WHERE group_id = ? AND user_id = ?");
$stmt->bind_param("ii", $group_id, $current_user);
$stmt->execute();
if ($stmt->get_result()->num_rows == 0) {
    echo json_encode(['error' => 'Not a member of this group']);
    exit;
}

// Fetch messages with sender names
$stmt = $conn->prepare("SELECT gm.*, u.name AS sender_name 
    FROM group_messages gm 
    JOIN users u ON gm.sender_id = u.id 
    WHERE gm.group_id = ? 
    ORDER BY gm.timestamp ASC");
$stmt->bind_param("i", $group_id);
if (!$stmt->execute()) {
    echo json_encode(['error' => 'Failed to fetch messages: ' . $stmt->error]);
    exit;
}

$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Output messages as HTML
ob_start();
if (!empty($messages)) {
    foreach ($messages as $msg) {
        ?>
        <p class="<?php echo $msg['sender_id'] == $current_user ? 'you' : 'them'; ?>">
            <?php if ($msg['sender_id'] != $current_user): ?>
                <strong><?php echo htmlspecialchars($msg['sender_name']); ?>:</strong>
            <?php endif; ?>
            <?php echo htmlspecialchars($msg['content']); ?>
            <?php if ($msg['file_path'] && $msg['original_file_name']): ?>
                <br><a href="<?php echo htmlspecialchars($msg['file_path']); ?>" download>
                    <?php echo htmlspecialchars($msg['original_file_name']); ?>
                </a>
            <?php endif; ?>
        </p>
        <?php
    }
}
echo ob_get_clean();

header('Location: ../login.php');
?>