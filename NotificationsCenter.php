<?php
/**
 * Notifications Center
 */
require_once __DIR__ . '/includes/Functions.php';

requireLogin();

$userId = getCurrentUserId();
$pdo = getDbConnection();

// Handle mark as read
if (isset($_GET['mark_read'])) {
    $notificationId = intval($_GET['mark_read']);
    markNotificationRead($notificationId, $userId);
    
    // Redirect to the notification link if exists
    $stmt = $pdo->prepare("SELECT link FROM notification WHERE id = ? AND user_id = ?");
    $stmt->execute([$notificationId, $userId]);
    $notif = $stmt->fetch();
    
    if ($notif && $notif['link']) {
        header('Location: ' . $notif['link']);
        exit;
    }
    header('Location: NotificationsCenter.php');
    exit;
}

// Handle mark all as read
if (isset($_GET['mark_all_read'])) {
    $stmt = $pdo->prepare("UPDATE notification SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$userId]);
    redirectWithMessage('NotificationsCenter.php', 'All notifications marked as read.');
}

// Handle delete
if (isset($_GET['delete'])) {
    $notificationId = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM notification WHERE id = ? AND user_id = ?");
    $stmt->execute([$notificationId, $userId]);
    redirectWithMessage('NotificationsCenter.php', 'Notification deleted.');
}

// Get notifications with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get total count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notification WHERE user_id = ?");
$stmt->execute([$userId]);
$totalCount = $stmt->fetch()['count'];
$totalPages = ceil($totalCount / $perPage);

// Get notifications
$stmt = $pdo->prepare("
    SELECT * FROM notification 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute([$userId, $perPage, $offset]);
$notifications = $stmt->fetchAll();

$unreadCount = getUnreadNotificationCount($userId);

$pageTitle = 'Notifications';
include __DIR__ . '/includes/Header.php';
?>

<div class="notifications-page">
    <div class="page-header">
        <h1>Notifications</h1>
        <?php if ($unreadCount > 0): ?>
            <a href="?mark_all_read=1" class="btn btn-outline">Mark All as Read</a>
        <?php endif; ?>
    </div>
    
    <?php if ($unreadCount > 0): ?>
        <div class="notification-summary">
            You have <?php echo $unreadCount; ?> unread notification<?php echo $unreadCount > 1 ? 's' : ''; ?>
        </div>
    <?php endif; ?>
    
    <?php if (empty($notifications)): ?>
        <div class="empty-state">
            <h3>No notifications</h3>
            <p>You're all caught up! Check back later for updates.</p>
        </div>
    <?php else: ?>
        <div class="notifications-list">
            <?php foreach ($notifications as $notif): ?>
                <div class="notification-card <?php echo $notif['is_read'] ? 'read' : 'unread'; ?>">
                    <div class="notification-icon">
                        <?php
                        $icons = [
                            'new_booking' => '&#128197;',
                            'booking_confirmed' => '&#9989;',
                            'booking_cancelled' => '&#10060;',
                            'booking_rescheduled' => '&#128260;',
                            'session_completed' => '&#127881;',
                            'new_message' => '&#128172;',
                            'new_review' => '&#11088;',
                            'booking_submitted' => '&#128203;',
                            'booking_pending' => '&#9203;',
                            'booking_rejected' => '&#10060;'
                        ];
                        echo $icons[$notif['type']] ?? '&#128276;';
                        ?>
                    </div>
                    <div class="notification-content">
                        <h4><?php echo sanitize($notif['title']); ?></h4>
                        <p><?php echo sanitize($notif['message']); ?></p>
                        <span class="notification-time"><?php echo formatDate($notif['created_at'], 'M j, Y g:i A'); ?></span>
                    </div>
                    <div class="notification-actions">
                        <?php if (!$notif['is_read']): ?>
                            <a href="?mark_read=<?php echo $notif['id']; ?>" class="btn btn-outline btn-sm">
                                <?php echo $notif['link'] ? 'View' : 'Mark Read'; ?>
                            </a>
                        <?php elseif ($notif['link']): ?>
                            <a href="<?php echo sanitize($notif['link']); ?>" class="btn btn-outline btn-sm">View</a>
                        <?php endif; ?>
                        <a href="?delete=<?php echo $notif['id']; ?>" class="btn btn-outline btn-sm" 
                           onclick="return confirm('Delete this notification?');">Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="btn btn-outline">Previous</a>
                <?php endif; ?>
                
                <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="btn btn-outline">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/Footer.php'; ?>