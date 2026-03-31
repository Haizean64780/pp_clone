<?php
/**
 * Messages Page - Internal Messaging System
 */
require_once __DIR__ . '/includes/Functions.php';

requireLogin();

$userId = getCurrentUserId();
$pdo = getDbConnection();
$error = '';
$success = '';

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $receiverId = intval($_POST['receiver_id'] ?? 0);
    $subject = sanitize($_POST['subject'] ?? '');
    $content = sanitize($_POST['content'] ?? '');
    
    if (empty($receiverId) || empty($content)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Verify receiver exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND is_active = 1");
        $stmt->execute([$receiverId]);
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("
                INSERT INTO message (sender_id, receiver_id, subject, content)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $receiverId, $subject, $content]);
            
            // Create notification
            createNotification($receiverId, 'new_message', 'New Message', 
                'You have a new message from ' . $_SESSION['user_name'], 'Messages.php');
            
            $success = 'Message sent successfully!';
        } else {
            $error = 'Recipient not found.';
        }
    }
}

// Mark message as read
if (isset($_GET['read'])) {
    $messageId = intval($_GET['read']);
    $stmt = $pdo->prepare("UPDATE message SET is_read = 1 WHERE id = ? AND receiver_id = ?");
    $stmt->execute([$messageId, $userId]);
}

// Get conversations (grouped by the other user)
$stmt = $pdo->prepare("
    SELECT 
        m.*,
        CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END as other_user_id,
        u.first_name, u.last_name, u.email
    FROM message m
    JOIN users u ON (CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END) = u.id
    WHERE m.sender_id = ? OR m.receiver_id = ?
    ORDER BY m.created_at DESC
");
$stmt->execute([$userId, $userId, $userId, $userId]);
$allMessages = $stmt->fetchAll();

// Group messages by conversation
$conversations = [];
foreach ($allMessages as $msg) {
    $otherUserId = $msg['other_user_id'];
    if (!isset($conversations[$otherUserId])) {
        $conversations[$otherUserId] = [
            'user_id' => $otherUserId,
            'user_name' => $msg['first_name'] . ' ' . $msg['last_name'],
            'messages' => [],
            'unread' => 0,
            'last_message' => $msg['created_at']
        ];
    }
    $conversations[$otherUserId]['messages'][] = $msg;
    if (!$msg['is_read'] && $msg['receiver_id'] == $userId) {
        $conversations[$otherUserId]['unread']++;
    }
}

// Check if viewing specific conversation
$activeConversation = null;
$activeMessages = [];
if (isset($_GET['with']) || isset($_GET['to'])) {
    $otherUserId = intval($_GET['with'] ?? $_GET['to']);
    
    // Get other user info
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email FROM users WHERE id = ?");
    $stmt->execute([$otherUserId]);
    $otherUser = $stmt->fetch();
    
    if ($otherUser) {
        $activeConversation = $otherUser;
        
        // Get messages in this conversation
        $stmt = $pdo->prepare("
            SELECT m.*, 
                   s.first_name as sender_first_name, s.last_name as sender_last_name
            FROM message m
            JOIN users s ON m.sender_id = s.id
            WHERE (m.sender_id = ? AND m.receiver_id = ?)
               OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$userId, $otherUserId, $otherUserId, $userId]);
        $activeMessages = $stmt->fetchAll();
        
        // Mark messages as read
        $stmt = $pdo->prepare("UPDATE message SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
        $stmt->execute([$otherUserId, $userId]);
    }
}

$pageTitle = 'Messages';
include __DIR__ . '/includes/Header.php';
?>

<div class="messages-page">
    <div class="page-header">
        <h1>Messages</h1>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="messages-layout">
        <div class="conversations-list">
            <h2>Conversations</h2>
            
            <?php if (empty($conversations)): ?>
                <div class="empty-state-small">
                    <p>No conversations yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($conversations as $conv): ?>
                    <a href="?with=<?php echo $conv['user_id']; ?>" 
                       class="conversation-item <?php echo ($activeConversation && $activeConversation['id'] == $conv['user_id']) ? 'active' : ''; ?>">
                        <div class="conv-avatar">
                            <?php echo strtoupper(substr($conv['user_name'], 0, 2)); ?>
                        </div>
                        <div class="conv-info">
                            <span class="conv-name"><?php echo sanitize($conv['user_name']); ?></span>
                            <span class="conv-preview">
                                <?php 
                                $lastMsg = $conv['messages'][0];
                                echo sanitize(substr($lastMsg['content'], 0, 30)) . '...';
                                ?>
                            </span>
                        </div>
                        <?php if ($conv['unread'] > 0): ?>
                            <span class="unread-badge"><?php echo $conv['unread']; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="message-thread">
            <?php if ($activeConversation): ?>
                <div class="thread-header">
                    <h2><?php echo sanitize($activeConversation['first_name'] . ' ' . $activeConversation['last_name']); ?></h2>
                </div>
                
                <div class="thread-messages">
                    <?php if (empty($activeMessages)): ?>
                        <div class="empty-state-small">
                            <p>No messages yet. Start the conversation!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($activeMessages as $msg): ?>
                            <div class="message-bubble <?php echo $msg['sender_id'] == $userId ? 'sent' : 'received'; ?>">
                                <div class="message-content">
                                    <?php if ($msg['subject']): ?>
                                        <strong><?php echo sanitize($msg['subject']); ?></strong><br>
                                    <?php endif; ?>
                                    <?php echo nl2br(sanitize($msg['content'])); ?>
                                </div>
                                <div class="message-time">
                                    <?php echo formatDate($msg['created_at'], 'M j, g:i A'); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="thread-reply">
                    <form method="POST" class="reply-form">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="receiver_id" value="<?php echo $activeConversation['id']; ?>">
                        
                        <textarea name="content" placeholder="Type your message..." required></textarea>
                        <button type="submit" class="btn btn-primary">Send</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="no-conversation">
                    <h3>Select a conversation</h3>
                    <p>Choose a conversation from the list or start a new one.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/Footer.php'; ?>