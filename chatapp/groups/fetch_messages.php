<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['group_id'])) {
    http_response_code(403);
    exit("Unauthorized");
}

$current_user = $_SESSION['user_id'];
$group_id = intval($_GET['group_id']);

$stmt = $conn->prepare("SELECT gm.*, u.name AS sender_name 
    FROM group_messages gm 
    JOIN users u ON gm.sender_id = u.id 
    WHERE gm.group_id = ? 
    ORDER BY gm.timestamp ASC");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($messages as $msg): ?>
    <p class="<?php echo $msg['sender_id'] == $current_user ? 'you' : 'them'; ?>">
        <?php if ($msg['sender_id'] != $current_user): ?>
            <strong><?php echo htmlspecialchars($msg['sender_name']); ?>:</strong>
        <?php endif; ?>
        <?php echo htmlspecialchars($msg['content']); ?>
        <?php if ($msg['file_path']): ?>
            <br><a href="../<?php echo htmlspecialchars($msg['file_path']); ?>" download>
                <?php echo htmlspecialchars($msg['original_file_name']); ?>
            </a>
        <?php endif; ?>
    </p>
<?php endforeach; ?>