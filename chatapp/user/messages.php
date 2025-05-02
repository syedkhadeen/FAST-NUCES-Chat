<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$current_user = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$receiver_id = isset($_GET['to']) ? intval($_GET['to']) : null;
$error = null; // Initialize error variable

// Get all users to chat with (excluding self)
$stmt = $conn->prepare("SELECT id, name FROM users WHERE id != ?");
$stmt->bind_param("i", $current_user);
$stmt->execute();
$users = $stmt->get_result();

// Fetch receiver details if chat is selected
$receiver = null;
if ($receiver_id) {
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->bind_param("i", $receiver_id);
    $stmt->execute();
    $receiver = $stmt->get_result()->fetch_assoc();
}

// Fetch messages if chat is selected
$messages = [];
if ($receiver_id) {
    $stmt = $conn->prepare("SELECT * FROM messages WHERE 
        (sender_id = ? AND receiver_id = ?) OR 
        (sender_id = ? AND receiver_id = ?) 
        ORDER BY timestamp ASC");
    $stmt->bind_param("iiii", $current_user, $receiver_id, $receiver_id, $current_user);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Handle sending messages and file uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg_content = isset($_POST['message']) ? sanitize($_POST['message']) : '';
    $file_path = null;
    $original_file_name = null;
    $error = null;

    // File upload handling
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
        $max_size = 5 * 1024 * 1024; // 5MB
        $file = $_FILES['file'];

        if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
            $upload_dir = __DIR__ . '/../uploads/';
            // Get the file extension
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            // Store the original file name
            $original_file_name = basename($file['name']);
            // Create file name in the format usernameTOusernameofreceiver.extension
            $file_name = $user_name . 'TO' . $receiver['name'] . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $file_path = 'Uploads/' . $file_name; // Relative path for HTML
            } else {
                $error = "Failed to upload file.";
            }
        } else {
            $error = "Invalid file type or size.";
        }
    }

    // Insert message into database if there's either a message or a file
    if (!$error && ($msg_content || $file_path)) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, content, file_path, original_file_name) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $current_user, $receiver_id, $msg_content, $file_path, $original_file_name);
        if ($stmt->execute()) {
            header("Location: messages.php?to=$receiver_id");
            exit;
        } else {
            $error = "Failed to send message.";
        }
    } elseif (!$msg_content && !$file_path) {
        $error = "Please enter a message or attach a file.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Private Messages</title>
    <link rel="stylesheet" href="../assets/messages.css">
</head>
<body>
    <!-- Sidebar for Users -->
    <aside class="sidebar">
        <div class="brand">ChatApp</div>
        <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
        <nav class="menu">
            <h4>Students</h4>
            <?php while ($user = $users->fetch_assoc()): ?>
                <a href="?to=<?php echo $user['id']; ?>" class="user-link <?php echo $receiver_id == $user['id'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($user['name']); ?>
                </a>
            <?php endwhile; ?>
            <a href="dashboard.php" class="btn-back">🔙 Back to Dashboard</a>
        </nav>
    </aside>

    <!-- Main Content (Chat Interface) -->
    <main class="main-content">
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($receiver_id && $receiver): ?>
            <div class="card" data-receiver-id="<?php echo $receiver_id; ?>">
                <h3>Chat with <?php echo htmlspecialchars($receiver['name']); ?></h3>
                <div class="chat-box" id="chat-box">
                    <?php foreach ($messages as $msg): ?>
                        <p class="<?php echo $msg['sender_id'] == $current_user ? 'you' : 'them'; ?>">
                            <?php echo htmlspecialchars($msg['content']); ?>
                            <?php if ($msg['file_path'] && $msg['original_file_name']): ?>
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
                        <input type="file" name="file" id="file-input" class="file-input" accept="image/jpeg,image/png,application/pdf">
                        <button type="button" class="btn-attach" id="attach-btn">
                            <!-- Paperclip SVG Icon -->
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                            </svg>
                        </button>
                        <button type="submit" class="btn-send">
                            <!-- Paper Airplane SVG Icon -->
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 2L11 13"></path>
                                <path d="M22 2L15 22L11 13L2 9L22 2Z"></path>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="card">
                <p>Select a student from the sidebar to start chatting.</p>
            </div>
        <?php endif; ?>
    </main>

    <script>
        // Auto scroll to the bottom when new messages are added
        const chatBox = document.getElementById('chat-box');
        if (chatBox) {
            chatBox.scrollTop = chatBox.scrollHeight; // Scroll to the bottom initially
        }

        // Trigger file input click when attach button is clicked
        document.getElementById('attach-btn').addEventListener('click', () => {
            document.getElementById('file-input').click();
        });

        // Dynamically resize textarea based on content
        const textarea = document.getElementById('message-input');
        textarea.addEventListener('input', function () {
            this.style.height = 'auto'; // Reset height to auto to calculate new height
            this.style.height = `${this.scrollHeight}px`; // Set height to scrollHeight
        });

        // Ensure textarea resizes on initial load (in case of pre-filled content)
        textarea.style.height = `${textarea.scrollHeight}px`;

        // Auto-refresh messages every 10 seconds
        const card = document.querySelector('.card');
        if (card && chatBox) {
            const receiverId = card.getAttribute('data-receiver-id');

            const fetchMessages = async () => {
                try {
                    const response = await fetch(`fetch_messages.php?receiver_id=${receiverId}`);
                    const html = await response.text();

                    // Check if the response contains an error (e.g., JSON error message)
                    if (html.includes('{"error":"')) {
                        console.error('Error fetching messages:', html);
                        return;
                    }

                    // Check if the user is at the bottom of the chat
                    const isAtBottom = chatBox.scrollHeight - chatBox.scrollTop - chatBox.clientHeight < 1;

                    // Update the chat box content
                    chatBox.innerHTML = html;

                    // Scroll to the bottom if the user was at the bottom before the update
                    if (isAtBottom) {
                        chatBox.scrollTop = chatBox.scrollHeight;
                    }
                } catch (error) {
                    console.error('Failed to fetch messages:', error);
                }
            };

            // Fetch messages every 10 seconds
            setInterval(fetchMessages, 1000);

            // Fetch immediately on page load to start the cycle
            fetchMessages();
        }
    </script>
</body>
</html>