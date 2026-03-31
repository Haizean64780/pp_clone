<?php
/**
 * My Bookings Page
 */
require_once __DIR__ . '/includes/Functions.php';

requireLogin();

$userId = getCurrentUserId();
$pdo = getDbConnection();
$isProviderView = isProvider();

// Get provider profile if provider
$providerId = null;
if ($isProviderView) {
    $profile = getProviderProfile($userId);
    $providerId = $profile['id'];
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $appointmentId = intval($_POST['appointment_id'] ?? 0);
    
    $apt = getAppointmentById($appointmentId);
    
    // Verify ownership
    $canModify = ($apt && ($apt['client_id'] == $userId || ($isProviderView && $apt['provider_profile_id'] == $providerId)));
    
    if ($canModify) {
        if ($action === 'cancel') {
            $reason = sanitize($_POST['reason'] ?? '');
            if (empty($reason)) {
                $_SESSION['flash_message'] = 'Please provide a reason for cancellation.';
                $_SESSION['flash_type'] = 'error';
            } else {
                // Update appointment status
                $stmt = $pdo->prepare("UPDATE appointment SET status = 'cancelled' WHERE id = ?");
                $stmt->execute([$appointmentId]);
                
                // Record cancellation
                $stmt = $pdo->prepare("INSERT INTO cancellation (appointment_id, cancelled_by, reason) VALUES (?, ?, ?)");
                $stmt->execute([$appointmentId, $userId, $reason]);
                
                // Notify other party
                $notifyUserId = $apt['client_id'] == $userId ? $apt['provider_user_id'] : $apt['client_id'];
                createNotification($notifyUserId, 'booking_cancelled', 'Booking Cancelled', 
                    "The booking for {$apt['service_title']} on " . formatDate($apt['appointment_date']) . " has been cancelled.",
                    'MyBookings.php');
                
                redirectWithMessage('MyBookings.php', 'Booking cancelled successfully.');
            }
        }
        
        if ($action === 'reschedule') {
            $newDate = sanitize($_POST['new_date'] ?? '');
            $newTime = sanitize($_POST['new_time'] ?? '');
            $reason = sanitize($_POST['reason'] ?? '');
            
            if (empty($newDate) || empty($newTime)) {
                $_SESSION['flash_message'] = 'Please select a new date and time.';
                $_SESSION['flash_type'] = 'error';
            } else {
                $newEndTime = date('H:i:s', strtotime($newTime) + ($apt['duration_minutes'] * 60));
                
                // Check availability
                if (!isTimeSlotAvailable($apt['provider_profile_id'], $newDate, $newTime, $newEndTime, $appointmentId)) {
                    $_SESSION['flash_message'] = 'Selected time slot is not available.';
                    $_SESSION['flash_type'] = 'error';
                } else {
                    // Create reschedule request
                    $stmt = $pdo->prepare("
                        INSERT INTO schedulerequest (appointment_id, requested_by, original_date, original_start_time, new_date, new_start_time, new_end_time, reason)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $appointmentId, $userId, 
                        $apt['appointment_date'], $apt['start_time'],
                        $newDate, $newTime, $newEndTime, $reason
                    ]);
                    
                    // Update appointment
                    $stmt = $pdo->prepare("
                        UPDATE appointment SET appointment_date = ?, start_time = ?, end_time = ?, status = 'rescheduled'
                        WHERE id = ?
                    ");
                    $stmt->execute([$newDate, $newTime, $newEndTime, $appointmentId]);
                    
                    // Notify other party
                    $notifyUserId = $apt['client_id'] == $userId ? $apt['provider_user_id'] : $apt['client_id'];
                    createNotification($notifyUserId, 'booking_rescheduled', 'Booking Rescheduled', 
                        "The booking for {$apt['service_title']} has been rescheduled to " . formatDate($newDate) . " at " . formatTime($newTime),
                        'MyBookings.php');
                    
                    redirectWithMessage('MyBookings.php', 'Booking rescheduled successfully.');
                }
            }
        }
        
        if ($action === 'review') {
            $rating = intval($_POST['rating'] ?? 0);
            $comment = sanitize($_POST['comment'] ?? '');
            
            if ($rating < 1 || $rating > 5) {
                $_SESSION['flash_message'] = 'Please select a rating.';
                $_SESSION['flash_type'] = 'error';
            } else {
                // Check if review already exists
                $stmt = $pdo->prepare("SELECT id FROM review WHERE appointment_id = ?");
                $stmt->execute([$appointmentId]);
                if (!$stmt->fetch()) {
                    $stmt = $pdo->prepare("
                        INSERT INTO review (appointment_id, reviewer_id, provider_id, rating, comment)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$appointmentId, $userId, $apt['provider_profile_id'], $rating, $comment]);
                    
                    // Update provider rating
                    // Update provider rating
                    $stmt = $pdo->prepare("
                        UPDATE providerprofile SET 
                            rating_average = (SELECT AVG(rating) FROM review WHERE provider_id = ? AND is_approved = 1),
                            total_reviews = (SELECT COUNT(*) FROM review WHERE provider_id = ? AND is_approved = 1)
                        WHERE id = ?
                    ");
                    $stmt->execute([$apt['provider_profile_id'], $apt['provider_profile_id'], $apt['provider_profile_id']]);
                    
                    // NOUVEAU : Créer la notification pour le tuteur
                    createNotification(
                        $apt['provider_user_id'], 
                        'new_review', 
                        'New Review Received! ⭐', 
                        "A student just left you a {$rating}-star review for your session: {$apt['service_title']}.", 
                        'ProviderProfile.php?id=' . $apt['provider_profile_id']
                    );
                    
                    redirectWithMessage('MyBookings.php', 'Review submitted successfully. Thank you!');
                }
            }
        }
    }
}

// Get filter
$statusFilter = $_GET['status'] ?? '';

// Get appointments
if ($isProviderView) {
    $appointments = getProviderAppointments($providerId, $statusFilter ?: null);
} else {
    $appointments = getUserAppointments($userId, $statusFilter ?: null);
}

// Separate into upcoming and past
$upcoming = [];
$past = [];
foreach ($appointments as $apt) {
    if (in_array($apt['status'], ['pending', 'confirmed']) && strtotime($apt['appointment_date']) >= strtotime('today')) {
        $upcoming[] = $apt;
    } else {
        $past[] = $apt;
    }
}

$pageTitle = 'My Bookings';
include __DIR__ . '/includes/Header.php';
?>

<div class="bookings-page">
    <div class="page-header">
        <h1>My Bookings</h1>
        <?php if (!$isProviderView): ?>
            <a href="ServiceCatalog.php" class="btn btn-primary">Book New Session</a>
        <?php endif; ?>
    </div>
    
    <div class="booking-tabs">
        <a href="?status=" class="tab <?php echo empty($statusFilter) ? 'active' : ''; ?>">All</a>
        <a href="?status=pending" class="tab <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">Pending</a>
        <a href="?status=confirmed" class="tab <?php echo $statusFilter === 'confirmed' ? 'active' : ''; ?>">Confirmed</a>
        <a href="?status=completed" class="tab <?php echo $statusFilter === 'completed' ? 'active' : ''; ?>">Completed</a>
        <a href="?status=cancelled" class="tab <?php echo $statusFilter === 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
    </div>
    
    <?php if (empty($appointments)): ?>
        <div class="empty-state">
            <h3>No bookings found</h3>
            <?php if (!$isProviderView): ?>
                <p>You haven't made any bookings yet.</p>
                <a href="ServiceCatalog.php" class="btn btn-primary">Find a Tutor</a>
            <?php else: ?>
                <p>You don't have any bookings yet.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        
        <?php if (!empty($upcoming) && empty($statusFilter)): ?>
        <section class="booking-section">
            <h2>Upcoming Sessions</h2>
            <div class="booking-list">
                <?php foreach ($upcoming as $apt): ?>
                    <?php include __DIR__ . '/includes/BookingCard.php'; ?>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        
        <section class="booking-section">
            <?php if (empty($statusFilter)): ?>
                <h2>Past Sessions</h2>
            <?php endif; ?>
            <div class="booking-list">
                <?php 
                $displayList = empty($statusFilter) ? $past : $appointments;
                foreach ($displayList as $apt): 
                ?>
                    <?php include __DIR__ . '/includes/BookingCard.php'; ?>
                <?php endforeach; ?>
            </div>
        </section>
        
    <?php endif; ?>
</div>

<div id="cancelModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>Cancel Booking</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="cancel">
            <input type="hidden" name="appointment_id" id="cancel_appointment_id">
            
            <div class="form-group">
                <label>Reason for cancellation *</label>
                <textarea name="reason" required placeholder="Please provide a reason..."></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="hideModal('cancelModal')">Back</button>
                <button type="submit" class="btn btn-danger">Cancel Booking</button>
            </div>
        </form>
    </div>
</div>

<div id="reviewModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>Leave a Review</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="review">
            <input type="hidden" name="appointment_id" id="review_appointment_id">
            
            <div class="form-group">
                <label>Rating *</label>
                <div class="rating-input">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>">
                        <label for="star<?php echo $i; ?>">&#9733;</label>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label>Comment (optional)</label>
                <textarea name="comment" rows="4" placeholder="Share your experience..."></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="hideModal('reviewModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Review</button>
            </div>
        </form>
    </div>
</div>

<style>
    .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
    .modal-content { background: white; padding: 2rem; border-radius: 8px; max-width: 500px; width: 90%; }
    .modal-actions { display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1rem; }
    .rating-input { display: flex; flex-direction: row-reverse; justify-content: flex-end; }
    .rating-input input { display: none; }
    .rating-input label { font-size: 2rem; color: #ddd; cursor: pointer; }
    .rating-input input:checked ~ label,
    .rating-input label:hover,
    .rating-input label:hover ~ label { color: #ffc107; }
</style>

<script>
function showCancelModal(appointmentId) {
    document.getElementById('cancel_appointment_id').value = appointmentId;
    document.getElementById('cancelModal').style.display = 'flex';
}

function showReviewModal(appointmentId) {
    document.getElementById('review_appointment_id').value = appointmentId;
    document.getElementById('reviewModal').style.display = 'flex';
}

function hideModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}
</script>

<?php include __DIR__ . '/includes/Footer.php'; ?>