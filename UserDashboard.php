<?php
/**
 * User Dashboard
 */
require_once __DIR__ . '/includes/Functions.php';

requireLogin();

$user = getCurrentUser();
$userId = getCurrentUserId();

// Get user stats
$pdo = getDbConnection();

// Upcoming appointments
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count FROM appointment 
    WHERE client_id = ? AND status IN ('pending', 'confirmed') AND appointment_date >= CURDATE()
");
$stmt->execute([$userId]);
$upcomingCount = $stmt->fetch()['count'];

// Completed sessions
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count FROM appointment 
    WHERE client_id = ? AND status = 'completed'
");
$stmt->execute([$userId]);
$completedCount = $stmt->fetch()['count'];

// Get recent appointments
$recentAppointments = getUserAppointments($userId);
$recentAppointments = array_slice($recentAppointments, 0, 5);

// Get recent notifications
$notifications = getUserNotifications($userId, 5);

$pageTitle = 'Dashboard';
include __DIR__ . '/includes/Header.php';
?>

<div class="dashboard">
    <div class="dashboard-header">
        <h1>Welcome, <?php echo sanitize($user['first_name']); ?>!</h1>
        <p>Manage your tutoring sessions and find new tutors</p>
    </div>
    
    <?php if (isset($_GET['welcome'])): ?>
        <div class="alert alert-success">
            Welcome to PeerTutoringMatchmaker! Start by browsing our tutors.
        </div>
    <?php endif; ?>
    
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon">&#128197;</div>
            <div class="stat-info">
                <span class="stat-number"><?php echo $upcomingCount; ?></span>
                <span class="stat-label">Upcoming Sessions</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">&#9989;</div>
            <div class="stat-info">
                <span class="stat-number"><?php echo $completedCount; ?></span>
                <span class="stat-label">Completed Sessions</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">&#128276;</div>
            <div class="stat-info">
                <span class="stat-number"><?php echo getUnreadNotificationCount($userId); ?></span>
                <span class="stat-label">New Notifications</span>
            </div>
        </div>
    </div>
    
    <div class="dashboard-grid">
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Recent Appointments</h2>
                <a href="MyBookings.php" class="btn btn-outline btn-sm">View All</a>
            </div>
            
            <?php if (empty($recentAppointments)): ?>
                <div class="empty-state">
                    <p>No appointments yet.</p>
                    <a href="ServiceCatalog.php" class="btn btn-primary">Find a Tutor</a>
                </div>
            <?php else: ?>
                <div class="appointment-list">
                    <?php foreach ($recentAppointments as $apt): ?>
                        <div class="appointment-card">
                            <div class="appointment-info">
                                <h4><?php echo sanitize($apt['service_title']); ?></h4>
                                <p class="appointment-tutor">
                                    with <?php echo sanitize($apt['provider_first_name'] . ' ' . $apt['provider_last_name']); ?>
                                </p>
                                <p class="appointment-datetime">
                                    <?php echo formatDate($apt['appointment_date']); ?> at <?php echo formatTime($apt['start_time']); ?>
                                </p>
                            </div>
                            <div class="appointment-status">
                                <?php echo getStatusBadge($apt['status']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Recent Notifications</h2>
                <a href="NotificationsCenter.php" class="btn btn-outline btn-sm">View All</a>
            </div>
            
            <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <p>No notifications yet.</p>
                </div>
            <?php else: ?>
                <div class="notification-list">
                    <?php foreach ($notifications as $notif): ?>
                        <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>">
                            <div class="notification-content">
                                <h4><?php echo sanitize($notif['title']); ?></h4>
                                <p><?php echo sanitize($notif['message']); ?></p>
                                <span class="notification-time"><?php echo formatDate($notif['created_at'], 'M j, g:i A'); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="dashboard-actions">
        <h2>Quick Actions</h2>
        <div class="action-buttons">
            <a href="ServiceCatalog.php" class="action-card">
                <span class="action-icon">&#128269;</span>
                <span class="action-text">Find a Tutor</span>
            </a>
            <a href="MyBookings.php" class="action-card">
                <span class="action-icon">&#128197;</span>
                <span class="action-text">My Bookings</span>
            </a>
            <a href="Messages.php" class="action-card">
                <span class="action-icon">&#128172;</span>
                <span class="action-text">Messages</span>
            </a>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/Footer.php'; ?>



<!-- 6?php
require_once __DIR__ . '/includes/Functions.php';

requireLogin();

$user = getCurrentUser();
$userId = getCurrentUserId();

// Get user stats
$pdo = getDbConnection();

// Upcoming appointments
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count FROM appointment 
    WHERE client_id = ? AND status IN ('pending', 'confirmed') AND appointment_date >= CURDATE()
");
$stmt->execute([$userId]);
$upcomingCount = $stmt->fetch()['count'];

// Completed sessions
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count FROM appointment 
    WHERE client_id = ? AND status = 'completed'
");
$stmt->execute([$userId]);
$completedCount = $stmt->fetch()['count'];

// Get recent appointments
$recentAppointments = getUserAppointments($userId);
$recentAppointments = array_slice($recentAppointments, 0, 5);

// Get recent notifications
$notifications = getUserNotifications($userId, 5);

$pageTitle = 'Dashboard';
include __DIR__ . '/includes/Header.php';
?>

<div class="dashboard">
    <div class="dashboard-header">
        <h1>Welcome, 6?php echo sanitize($user['first_name']); ?>!</h1>
        <p>Manage your tutoring sessions and find new tutors</p>
    </div>
    
    6?php if (isset($_GET['welcome'])): ?>
        <div class="alert alert-success">
            Welcome to PeerTutoringMatchmaker! Start by browsing our tutors.
        </div>
    6?php endif; ?>
    
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon">&#128197;</div>
            <div class="stat-info">
                <span class="stat-number">6?php echo $upcomingCount; ?></span>
                <span class="stat-label">Upcoming Sessions</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">&#9989;</div>
            <div class="stat-info">
                <span class="stat-number">6?php echo $completedCount; ?></span>
                <span class="stat-label">Completed Sessions</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">&#128276;</div>
            <div class="stat-info">
                <span class="stat-number">6?php echo getUnreadNotificationCount($userId); ?></span>
                <span class="stat-label">New Notifications</span>
            </div>
        </div>
    </div>
    
    <div class="dashboard-grid">
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Recent Appointments</h2>
                <a href="MyBookings.php" class="btn btn-outline btn-sm">View All</a>
            </div>
            
            6?php if (empty($recentAppointments)): ?>
                <div class="empty-state">
                    <p>No appointments yet.</p>
                    <a href="ServiceCatalog.php" class="btn btn-primary">Find a Tutor</a>
                </div>
            6?php else: ?>
                <div class="appointment-list">
                    6?php foreach ($recentAppointments as $apt): ?>
                        <div class="appointment-card">
                            <div class="appointment-info">
                                <h4>6?php echo sanitize($apt['service_title']); ?></h4>
                                <p class="appointment-tutor">
                                    with 6?php echo sanitize($apt['provider_first_name'] . ' ' . $apt['provider_last_name']); ?>
                                </p>
                                <p class="appointment-datetime">
                                    6?php echo formatDate($apt['appointment_date']); ?> at 6?php echo formatTime($apt['start_time']); ?>
                                </p>
                            </div>
                            <div class="appointment-status">
                                6?php echo getStatusBadge($apt['status']); ?>
                            </div>
                        </div>
                    6?php endforeach; ?>
                </div>
            6?php endif; ?>
        </div>
        
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Recent Notifications</h2>
                <a href="NotificationsCenter.php" class="btn btn-outline btn-sm">View All</a>
            </div>
            
            6?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <p>No notifications yet.</p>
                </div>
            6?php else: ?>
                <div class="notification-list">
                    6?php foreach ($notifications as $notif): ?>
                        <div class="notification-item 6?php echo $notif['is_read'] ? '' : 'unread'; ?>">
                            <div class="notification-content">
                                <h4>6?php echo sanitize($notif['title']); ?></h4>
                                <p>6?php echo sanitize($notif['message']); ?></p>
                                <span class="notification-time">6?php echo formatDate($notif['created_at'], 'M j, g:i A'); ?></span>
                            </div>
                        </div>
                    6?php endforeach; ?>
                </div>
            6?php endif; ?>
        </div>
    </div>
    
    <div class="dashboard-actions">
        <h2>Quick Actions</h2>
        <div class="action-buttons">
            <a href="ServiceCatalog.php" class="action-card">
                <span class="action-icon">&#128269;</span>
                <span class="action-text">Find a Tutor</span>
            </a>
            <a href="MyBookings.php" class="action-card">
                <span class="action-icon">&#128197;</span>
                <span class="action-text">My Bookings</span>
            </a>
            <a href="Messages.php" class="action-card">
                <span class="action-icon">&#128172;</span>
                <span class="action-text">Messages</span>
            </a>
        </div>
    </div>
</div>
6?php include __DIR__ . '/includes/Footer.php'; ?> -->

