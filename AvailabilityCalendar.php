<?php
/**
 * Availability Calendar - Provider Availability Management
 */
require_once __DIR__ . '/includes/Functions.php';

requireProvider();

$userId = getCurrentUserId();
$profile = getProviderProfile($userId);
$providerId = $profile['id'];
$pdo = getDbConnection();

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $dayOfWeek = intval($_POST['day_of_week'] ?? 0);
        $startTime = sanitize($_POST['start_time'] ?? '');
        $endTime = sanitize($_POST['end_time'] ?? '');
        
        if ($startTime >= $endTime) {
            $error = 'End time must be after start time.';
        } else {
            // Check for overlapping slots
            $stmt = $pdo->prepare("
                SELECT id FROM availability 
                WHERE provider_id = ? AND day_of_week = ? 
                AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?))
            ");
            $stmt->execute([$providerId, $dayOfWeek, $endTime, $startTime, $endTime, $startTime]);
            
            if ($stmt->fetch()) {
                $error = 'This time slot overlaps with an existing availability.';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO availability (provider_id, day_of_week, start_time, end_time)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$providerId, $dayOfWeek, $startTime, $endTime]);
                $success = 'Availability added successfully!';
            }
        }
    }
    
    if ($action === 'delete') {
        $slotId = intval($_POST['slot_id']);
        $stmt = $pdo->prepare("DELETE FROM availability WHERE id = ? AND provider_id = ?");
        $stmt->execute([$slotId, $providerId]);
        $success = 'Time slot removed.';
    }
    
    if ($action === 'toggle') {
        $slotId = intval($_POST['slot_id']);
        $stmt = $pdo->prepare("UPDATE availability SET is_available = NOT is_available WHERE id = ? AND provider_id = ?");
        $stmt->execute([$slotId, $providerId]);
        $success = 'Availability updated.';
    }
}

// Get current availability
$availability = getProviderAvailability($providerId);

// Also get disabled slots
$stmt = $pdo->prepare("SELECT * FROM availability WHERE provider_id = ? ORDER BY day_of_week, start_time");
$stmt->execute([$providerId]);
$allSlots = $stmt->fetchAll();

// Group by day
$slotsByDay = [];
for ($i = 0; $i <= 6; $i++) {
    $slotsByDay[$i] = [];
}
foreach ($allSlots as $slot) {
    $slotsByDay[$slot['day_of_week']][] = $slot;
}

$pageTitle = 'Manage Availability';
include __DIR__ . '/includes/Header.php';
?>

<div class="availability-page">
    <div class="page-header">
        <h1>Manage Availability</h1>
        <a href="ProviderDashboard.php" class="btn btn-outline">Back to Dashboard</a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="availability-grid">
        <div class="availability-form-section">
            <h2>Add Time Slot</h2>
            <form method="POST" class="availability-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="day_of_week">Day of Week</label>
                    <select id="day_of_week" name="day_of_week" required>
                        <?php for ($i = 0; $i <= 6; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo getDayName($i); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_time">Start Time</label>
                        <input type="time" id="start_time" name="start_time" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="end_time">End Time</label>
                        <input type="time" id="end_time" name="end_time" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Add Slot</button>
            </form>
            
            <div class="availability-tips">
                <h4>Tips</h4>
                <ul>
                    <li>Add multiple time slots per day for flexibility</li>
                    <li>Leave gaps between slots for breaks</li>
                    <li>You can disable slots without deleting them</li>
                </ul>
            </div>
        </div>
        
        <div class="availability-calendar-section">
            <h2>Your Weekly Schedule</h2>
            
            <div class="weekly-calendar">
                <?php for ($day = 0; $day <= 6; $day++): ?>
                    <div class="calendar-day">
                        <h3><?php echo getDayName($day); ?></h3>
                        
                        <?php if (empty($slotsByDay[$day])): ?>
                            <p class="no-slots">No availability set</p>
                        <?php else: ?>
                            <div class="day-slots">
                                <?php foreach ($slotsByDay[$day] as $slot): ?>
                                    <div class="time-slot-item <?php echo !$slot['is_available'] ? 'disabled' : ''; ?>">
                                        <span class="slot-time">
                                            <?php echo formatTime($slot['start_time']); ?> - <?php echo formatTime($slot['end_time']); ?>
                                        </span>
                                        <div class="slot-actions">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                                                <button type="submit" class="btn-icon" title="<?php echo $slot['is_available'] ? 'Disable' : 'Enable'; ?>">
                                                    <?php echo $slot['is_available'] ? '&#10003;' : '&#10007;'; ?>
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this slot?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="slot_id" value="<?php echo $slot['id']; ?>">
                                                <button type="submit" class="btn-icon btn-icon-danger" title="Delete">&#128465;</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/Footer.php'; ?>