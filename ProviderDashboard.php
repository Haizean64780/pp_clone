<?php
/**
 * Provider Dashboard
 */
require_once __DIR__ . '/includes/Functions.php';

requireProvider();

$user = getCurrentUser();
$userId = getCurrentUserId();
$profile = getProviderProfile($userId);

if (!$profile) {
    // Create profile if doesn't exist
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("INSERT INTO providerprofile (user_id, bio, hourly_rate) VALUES (?, '', 25.00)");
    $stmt->execute([$userId]);
    $profile = getProviderProfile($userId);
}

$providerId = $profile['id'];
$pdo = getDbConnection();

// Get stats
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count FROM appointment 
    WHERE provider_id = ? AND status = 'pending'
");
$stmt->execute([$providerId]);
$pendingCount = $stmt->fetch()['count'];

$stmt = $pdo->prepare("
    SELECT COUNT(*) as count FROM appointment 
    WHERE provider_id = ? AND status = 'confirmed' AND appointment_date >= CURDATE()
");
$stmt->execute([$providerId]);
$upcomingCount = $stmt->fetch()['count'];

$stmt = $pdo->prepare("
    SELECT COUNT(*) as count FROM appointment 
    WHERE provider_id = ? AND status = 'completed'
");
$stmt->execute([$providerId]);
$completedCount = $stmt->fetch()['count'];

$stmt = $pdo->prepare("
    SELECT SUM(total_price) as total FROM appointment 
    WHERE provider_id = ? AND status = 'completed'
");
$stmt->execute([$providerId]);
$totalEarnings = $stmt->fetch()['total'] ?? 0;

// Handle appointment actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $appointmentId = intval($_POST['appointment_id']);
    $action = $_POST['action'];
    
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $apt = getAppointmentById($appointmentId);
        
        if ($apt && $apt['provider_profile_id'] == $providerId) {
            if ($action === 'confirm' && $apt['status'] === 'pending') {
                $stmt = $pdo->prepare("UPDATE appointment SET status = 'confirmed' WHERE id = ?");
                $stmt->execute([$appointmentId]);
                createNotification($apt['client_id'], 'booking_confirmed', 'Booking Confirmed', 
                    "Your booking for {$apt['service_title']} has been confirmed.", 'MyBookings.php');
                redirectWithMessage('ProviderDashboard.php', 'Appointment confirmed successfully.');
            } elseif ($action === 'reject' && $apt['status'] === 'pending') {
                $stmt = $pdo->prepare("UPDATE appointment SET status = 'cancelled' WHERE id = ?");
                $stmt->execute([$appointmentId]);
                createNotification($apt['client_id'], 'booking_rejected', 'Booking Rejected', 
                    "Your booking for {$apt['service_title']} was not accepted.", 'MyBookings.php');
                redirectWithMessage('ProviderDashboard.php', 'Appointment rejected.');
            } elseif ($action === 'complete' && $apt['status'] === 'confirmed') {
                $stmt = $pdo->prepare("UPDATE appointment SET status = 'completed' WHERE id = ?");
                $stmt->execute([$appointmentId]);
                createNotification($apt['client_id'], 'session_completed', 'Session Completed', 
                    "Your session for {$apt['service_title']} has been marked as completed. Please leave a review!", 'MyBookings.php');
                redirectWithMessage('ProviderDashboard.php', 'Session marked as completed.');
            } elseif ($action === 'noshow' && $apt['status'] === 'confirmed') {
                $stmt = $pdo->prepare("UPDATE appointment SET status = 'no_show' WHERE id = ?");
                $stmt->execute([$appointmentId]);
                redirectWithMessage('ProviderDashboard.php', 'Marked as no-show.');
            }
        }
    }
}

// Get pending appointments
$pendingAppointments = getProviderAppointments($providerId, 'pending');

// Get today's appointments
$stmt = $pdo->prepare("
    SELECT a.*, s.title as service_title, s.duration_minutes,
           u.first_name as client_first_name, u.last_name as client_last_name,
           u.email as client_email
    FROM appointment a
    JOIN service s ON a.service_id = s.id
    JOIN users u ON a.client_id = u.id
    WHERE a.provider_id = ? AND a.appointment_date = CURDATE() AND a.status IN ('confirmed', 'pending')
    ORDER BY a.start_time
");
$stmt->execute([$providerId]);
$todayAppointments = $stmt->fetchAll();

// Get services
$services = getProviderServices($providerId);

$pageTitle = 'Provider Dashboard';
include __DIR__ . '/includes/Header.php';
?>

<div class="dashboard">
    <div class="dashboard-header">
        <h1>Provider Dashboard</h1>
        <p>Manage your tutoring services and appointments</p>
    </div>
    
    <?php if (isset($_GET['welcome'])): ?>
        <div class="alert alert-success">
            Welcome! Start by setting up your profile and adding services.
        </div>
    <?php endif; ?>
    
    <div class="dashboard-stats">
        <div class="stat-card stat-warning">
            <div class="stat-icon">&#9888;</div>
            <div class="stat-info">
                <span class="stat-number"><?php echo $pendingCount; ?></span>
                <span class="stat-label">Pending Requests</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">&#128197;</div>
            <div class="stat-info">
                <span class="stat-number"><?php echo $upcomingCount; ?></span>
                <span class="stat-label">Upcoming Sessions</span>
            </div>
        </div>
        <div class="stat-card stat-success">
            <div class="stat-icon">&#9989;</div>
            <div class="stat-info">
                <span class="stat-number"><?php echo $completedCount; ?></span>
                <span class="stat-label">Completed</span>
            </div>
        </div>
        <div class="stat-card stat-info">
            <div class="stat-icon">&#128176;</div>
            <div class="stat-info">
                <span class="stat-number"><?php echo formatCurrency($totalEarnings); ?></span>
                <span class="stat-label">Total Earnings</span>
            </div>
        </div>
    </div>
    
    <?php if (!empty($pendingAppointments)): ?>
    <div class="dashboard-section">
        <div class="section-header">
            <h2>Pending Requests</h2>
        </div>
        <div class="appointment-list">
            <?php foreach ($pendingAppointments as $apt): ?>
                <div class="appointment-card appointment-pending">
                    <div class="appointment-info">
                        <h4><?php echo sanitize($apt['service_title']); ?></h4>
                        <p class="appointment-client">
                            Client: <?php echo sanitize($apt['client_first_name'] . ' ' . $apt['client_last_name']); ?>
                        </p>
                        <p class="appointment-datetime">
                            <?php echo formatDate($apt['appointment_date']); ?> at <?php echo formatTime($apt['start_time']); ?> - <?php echo formatTime($apt['end_time']); ?>
                        </p>
                        <?php if ($apt['client_notes']): ?>
                            <p class="appointment-notes"><em>Note: <?php echo sanitize($apt['client_notes']); ?></em></p>
                        <?php endif; ?>
                    </div>
                    <div class="appointment-actions">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                            <button type="submit" name="action" value="confirm" class="btn btn-success btn-sm">Accept</button>
                            <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="dashboard-grid">
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Today's Schedule</h2>
            </div>
            
            <?php if (empty($todayAppointments)): ?>
                <div class="empty-state">
                    <p>No appointments scheduled for today.</p>
                </div>
            <?php else: ?>
                <div class="appointment-list">
                    <?php foreach ($todayAppointments as $apt): ?>
                        <div class="appointment-card">
                            <div class="appointment-info">
                                <h4><?php echo sanitize($apt['service_title']); ?></h4>
                                <p><?php echo sanitize($apt['client_first_name'] . ' ' . $apt['client_last_name']); ?></p>
                                <p class="appointment-datetime">
                                    <?php echo formatTime($apt['start_time']); ?> - <?php echo formatTime($apt['end_time']); ?>
                                </p>
                            </div>
                            <div class="appointment-actions">
                                <?php echo getStatusBadge($apt['status']); ?>
                                <?php if ($apt['status'] === 'confirmed'): ?>
                                    <form method="POST" style="display: inline; margin-top: 0.5rem;">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                        <button type="submit" name="action" value="complete" class="btn btn-success btn-sm">Mark Complete</button>
                                        <button type="submit" name="action" value="noshow" class="btn btn-outline btn-sm">No Show</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="dashboard-section">
            <div class="section-header">
                <h2>My Services</h2>
                <a href="ManageServices.php" class="btn btn-primary btn-sm">Add Service</a>
            </div>
            
            <?php if (empty($services)): ?>
                <div class="empty-state">
                    <p>No services added yet.</p>
                    <a href="ManageServices.php" class="btn btn-primary">Add Your First Service</a>
                </div>
            <?php else: ?>
                <div class="service-list-mini">
                    <?php foreach ($services as $service): ?>
                        <div class="service-item-mini">
                            <div class="service-info">
                                <h4><?php echo sanitize($service['title']); ?></h4>
                                <p><?php echo sanitize($service['category_name']); ?> - <?php echo $service['duration_minutes']; ?> min</p>
                            </div>
                            <div class="service-price">
                                <?php echo formatCurrency($service['price']); ?>
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
            <a href="ManageServices.php" class="action-card">
                <span class="action-icon">&#128218;</span>
                <span class="action-text">Manage Services</span>
            </a>
            <a href="AvailabilityCalendar.php" class="action-card">
                <span class="action-icon">&#128197;</span>
                <span class="action-text">Set Availability</span>
            </a>
            <a href="ProviderProfile.php?id=<?php echo $profile['id']; ?>" class="action-card">
                <span class="action-icon">&#128100;</span>
                <span class="action-text">View Profile</span>
            </a>
            <a href="Messages.php" class="action-card">
                <span class="action-icon">&#128172;</span>
                <span class="action-text">Messages</span>
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/Footer.php'; ?>