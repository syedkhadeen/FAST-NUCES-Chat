<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$current_user = $_SESSION['user_id'];
$current_user_name = htmlspecialchars($_SESSION['user_name']);

function sanitize_file_name($filename)
{
    return preg_replace('/[^a-zA-Z0-9-_\.]/', '', $filename);
}

$stmt = $conn->prepare("SELECT g.id, g.group_name FROM groups g
    JOIN group_memberships gm ON g.id = gm.group_id
    WHERE gm.user_id = ?");
$stmt->bind_param("i", $current_user);
$stmt->execute();
$groups = $stmt->get_result();

$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : null;
$messages = [];
$group = null;
$error = null;

if ($group_id) {
    $stmt = $conn->prepare("SELECT 1 FROM group_memberships WHERE group_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $group_id, $current_user);
    $stmt->execute();
    if ($stmt->get_result()->num_rows == 0) {
        $error = "You are not a member of this group.";
    } else {
        $stmt = $conn->prepare("SELECT id, group_name FROM groups WHERE id = ?");
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        $group = $stmt->get_result()->fetch_assoc();

        if (!$group) {
            $error = "Group not found.";
        } else {
            $stmt = $conn->prepare("SELECT gm.*, u.name AS sender_name 
                FROM group_messages gm 
                JOIN users u ON gm.sender_id = u.id 
                WHERE gm.group_id = ? 
                ORDER BY gm.timestamp ASC");
            $stmt->bind_param("i", $group_id);
            if ($stmt->execute()) {
                $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            } else {
                $error = "Failed to fetch messages: " . $stmt->error;
            }
        }
    }
} else {
    $error = "No group selected. Please select a group from the sidebar.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && $group_id) {
    $msg_content = sanitize($_POST['message']);
    $file_path = null;
    $original_file_name = null;

    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
        $max_size = 5 * 1024 * 1024;
        $file = $_FILES['file'];

        if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
            $upload_dir = __DIR__ . '/../uploads/';
            $original_file_name = basename($file['name']);
            $file_name = $current_user_name . '_' . time() . '_' . sanitize_file_name($file['name']);
            $file_path_full = $upload_dir . $file_name;

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            if (move_uploaded_file($file['tmp_name'], $file_path_full)) {
                $file_path = 'uploads/' . $file_name;
            } else {
                $error = "Failed to upload file.";
            }
        } else {
            $error = "Invalid file type or size.";
        }
    }

    if (!$error && ($msg_content || $file_path)) {
        $stmt = $conn->prepare("INSERT INTO group_messages (group_id, sender_id, content, file_path, original_file_name) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $group_id, $current_user, $msg_content, $file_path, $original_file_name);
        if ($stmt->execute()) {
            header("Location: group_dashboard.php?group_id=$group_id");
            exit;
        } else {
            $error = "Failed to send message: " . $stmt->error;
        }
    } elseif (!$msg_content && !$file_path) {
        $error = "Please enter a message or attach a file.";
    }
}

$stmt = $conn->prepare("SELECT id, name FROM users WHERE id != ?");
$stmt->bind_param("i", $current_user);
$stmt->execute();
$users = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Group Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/groupchatstyle.css">
</head>

<body>
    <div class="container">
        <aside class="sidebar">
            <div class="brand">ChatApp</div>
            <div class="user-name"><?php echo $current_user_name; ?></div>
            <nav class="menu">
                <h4>Your Groups</h4>
                <?php while ($group_row = $groups->fetch_assoc()): ?>
                    <a href="?group_id=<?php echo $group_row['id']; ?>"
                        class="group-link <?php echo $group_id == $group_row['id'] ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($group_row['group_name']); ?>
                    </a>
                <?php endwhile; ?>
                <button class="create-group-btn">Create New Group</button>
                <a href="../user/dashboard.php" class="btn-back">🔙 Back to Dashboard</a>
            </nav>
        </aside>

        <main class="main-content">
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php elseif ($group_id && $group): ?>
                <div class="card" data-group-id="<?php echo $group_id; ?>">
                    <h3>Chat with <?php echo htmlspecialchars($group['group_name']); ?></h3>
                    <div class="chat-box" id="chat-box">
                        <?php foreach ($messages as $msg): ?>
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
                    </div>

                    <form method="POST" enctype="multipart/form-data" class="message-form">
                        <div class="input-container">
                            <textarea name="message" rows="1" placeholder="Type a message..." id="message-input"></textarea>
                            <input type="file" name="file" id="file-input" class="file-input"
                                accept="image/jpeg,image/png,application/pdf">
                            <button type="button" class="btn-attach" id="attach-btn">
                                📎
                            </button>
                            <button type="submit" class="btn-send">
                                📨
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="card">
                    <p>Select a group to start chatting.</p>
                </div>
            <?php endif; ?>
        </main>

        <!-- Group Modal -->
        <div id="createGroupModal" class="modal">
            <div class="modal-content">
                <span class="close-btn">×</span>
                <h2>Create New Group</h2>
                <?php if (isset($error)): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="POST">
                    <label for="group_name">Group Name:</label>
                    <input type="text" name="group_name" required>
                    <label for="members">Select Members:</label>
                    <select name="members[]" multiple required>
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" name="create_group" class="btn-send">Create Group</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('createGroupModal');
        const openBtn = document.querySelector('.create-group-btn');
        const closeBtn = document.querySelector('.close-btn');
        const attachBtn = document.getElementById('attach-btn');
        const fileInput = document.getElementById('file-input');
        const chatBox = document.getElementById('chat-box');
        const groupId = "<?php echo isset($group_id) ? intval($group_id) : 0; ?>";

        // Modal handlers
        openBtn.addEventListener('click', () => modal.style.display = 'block');
        closeBtn.addEventListener('click', () => modal.style.display = 'none');
        window.addEventListener('click', e => { if (e.target === modal) modal.style.display = 'none'; });
        attachBtn.addEventListener('click', () => fileInput.click());

        // Fetch and refresh messages
        function fetchMessages() {
            fetch(`fetch_messages.php?group_id=${groupId}`)
                .then(res => res.text())
                .then(data => {
                    const nearBottom = chatBox.scrollTop + chatBox.clientHeight >= chatBox.scrollHeight - 50;
                    chatBox.innerHTML = data;
                    if (nearBottom) {
                        chatBox.scrollTop = chatBox.scrollHeight;
                    }
                });
        }

        fetchMessages(); // Initial load
        setInterval(fetchMessages, 3000); // Auto refresh
    </script>
</body>

</html>