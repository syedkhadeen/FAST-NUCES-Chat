<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];
$other_user_id = isset($_GET['user']) ? (int)$_GET['user'] : null;

// Get other user's details
if ($other_user_id) {
    $stmt = $conn->prepare("SELECT id, name, profile_pic, status FROM users WHERE id = ?");
    $stmt->bind_param("i", $other_user_id);
    $stmt->execute();
    $other_user = $stmt->get_result()->fetch_assoc();
}

// Get current user's details
$stmt = $conn->prepare("SELECT id, name, profile_pic FROM users WHERE id = ?");
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();

// Create messages table if it doesn't exist
$create_messages_table = "CREATE TABLE IF NOT EXISTS messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (receiver_id) REFERENCES users(id)
)";
$conn->query($create_messages_table);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - ChatApp</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .messages-container {
            max-width: 1200px;
            margin: 20px auto;
            display: flex;
            gap: 20px;
            padding: 0 20px;
        }

        .chat-list {
            width: 300px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 15px;
        }

        .chat-area {
            flex-grow: 1;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            height: calc(100vh - 100px);
        }

        .chat-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chat-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .chat-header .user-info {
            flex-grow: 1;
        }

        .chat-header .user-name {
            font-weight: 600;
            font-size: 1.1em;
        }

        .chat-header .user-status {
            font-size: 0.9em;
            color: #65676b;
        }

        .chat-header .user-status.online {
            color: #31a24c;
        }

        .messages {
            flex-grow: 1;
            padding: 15px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .message {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 15px;
            position: relative;
        }

        .message.sent {
            background: #0084ff;
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 5px;
        }

        .message.received {
            background: #f0f2f5;
            color: black;
            align-self: flex-start;
            border-bottom-left-radius: 5px;
        }

        .message .time {
            font-size: 0.8em;
            opacity: 0.7;
            margin-top: 5px;
        }

        .message-input {
            padding: 15px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
        }

        .message-input textarea {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 20px;
            resize: none;
            height: 40px;
            outline: none;
        }

        .message-input button {
            background: #0084ff;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2em;
        }

        .message-input button:hover {
            background: #0073e6;
        }

        .no-chat-selected {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #65676b;
            text-align: center;
            padding: 20px;
        }

        .no-chat-selected img {
            width: 100px;
            height: 100px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="messages-container">
        <div class="chat-list">
            <h3>Recent Chats</h3>
            <!-- Recent chats will be loaded here -->
        </div>

        <div class="chat-area">
            <?php if ($other_user): ?>
                <div class="chat-header">
                    <img src="uploads/profile/<?php echo htmlspecialchars($other_user['profile_pic']); ?>" alt="Profile">
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($other_user['name']); ?></div>
                        <div class="user-status <?php echo strtolower($other_user['status']); ?>">
                            <?php echo ucfirst($other_user['status']); ?>
                        </div>
                    </div>
                </div>
                <div class="messages" id="messages">
                    <!-- Messages will be loaded here -->
                </div>
                <div class="message-input">
                    <textarea placeholder="Type a message..." id="message-text"></textarea>
                    <button onclick="sendMessage()">➤</button>
                </div>
            <?php else: ?>
                <div class="no-chat-selected">
                    <img src="assets/images/chat-icon.png" alt="No chat selected">
                    <h2>Select a chat to start messaging</h2>
                    <p>Choose from your existing conversations or start a new one</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const currentUserId = <?php echo $current_user_id; ?>;
        const otherUserId = <?php echo $other_user_id ?? 'null'; ?>;
        let lastMessageId = 0;

        function loadMessages() {
            if (!otherUserId) return;

            fetch(`get_messages.php?user=${otherUserId}&last_id=${lastMessageId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.messages.length > 0) {
                        const messagesDiv = document.getElementById('messages');
                        data.messages.forEach(message => {
                            const messageHtml = `
                                <div class="message ${message.sender_id == currentUserId ? 'sent' : 'received'}">
                                    ${message.content}
                                    <div class="time">${message.created_at}</div>
                                </div>
                            `;
                            messagesDiv.insertAdjacentHTML('beforeend', messageHtml);
                            lastMessageId = Math.max(lastMessageId, message.id);
                        });
                        messagesDiv.scrollTop = messagesDiv.scrollHeight;
                    }
                })
                .catch(error => console.error('Error loading messages:', error));
        }

        function sendMessage() {
            if (!otherUserId) return;

            const messageText = document.getElementById('message-text').value.trim();
            if (!messageText) return;

            fetch('send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    receiver_id: otherUserId,
                    content: messageText
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('message-text').value = '';
                    loadMessages();
                }
            })
            .catch(error => console.error('Error sending message:', error));
        }

        // Load messages every 3 seconds
        if (otherUserId) {
            loadMessages();
            setInterval(loadMessages, 3000);
        }

        // Send message on Enter key (Shift+Enter for new line)
        document.getElementById('message-text')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    </script>
</body>
</html> 