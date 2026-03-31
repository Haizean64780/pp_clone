<?php
/**
 * Book Service Page
 */
require_once __DIR__ . '/includes/Functions.php';

requireLogin();

// Only regular users can book
if (isProvider() || isAdmin()) {
    header('Location: ServiceCatalog.php');
    exit;
}

$serviceId = intval($_GET['service_id'] ?? 0);

if (!$serviceId) {
    header('Location: ServiceCatalog.php');
    exit;
}

$service = getServiceById($serviceId);

if (!$service || !$service['is_active']) {
    redirectWithMessage('ServiceCatalog.php', 'Service not found or unavailable.', 'error');
}

$pdo = getDbConnection();
$userId = getCurrentUserId();
$providerId = $service['provider_id'];
$error = '';
$success = '';

// Get provider availability
$availability = getProviderAvailability($providerId);

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking']) && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $date = sanitize($_POST['appointment_date'] ?? '');
    $startTime = sanitize($_POST['start_time'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    
    // Validation
    if (empty($date) || empty($startTime)) {
        $error = 'Please select a date and time.';
    } elseif (strtotime($date) < strtotime('today')) {
        $error = 'Cannot book appointments in the past.';
    } else {
        // Calculate end time based on service duration
        $endTime = date('H:i:s', strtotime($startTime) + ($service['duration_minutes'] * 60));
        
        // Check for overlapping bookings
        if (!isTimeSlotAvailable($providerId, $date, $startTime, $endTime)) {
            $error = 'This time slot is no longer available. Please choose another time.';
        } else {
            // Create appointment
            $stmt = $pdo->prepare("
                INSERT INTO appointment (client_id, provider_id, service_id, appointment_date, start_time, end_time, status, client_notes, total_price)
                VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?)
            ");
            $stmt->execute([
                $userId,
                $providerId,
                $serviceId,
                $date,
                $startTime,
                $endTime,
                $notes,
                $service['price']
            ]);
            
            // Create notification for provider
            createNotification(
                $service['provider_user_id'],
                'new_booking',
                'New Booking Request',
                "You have a new booking request for {$service['title']} on " . formatDate($date) . " at " . formatTime($startTime),
                'ProviderDashboard.php'
            );
            
            // Create notification for client
            createNotification(
                $userId,
                'booking_submitted',
                'Booking Submitted',
                "Your booking for {$service['title']} has been submitted and is pending confirmation.",
                'MyBookings.php'
            );
            
            redirectWithMessage('MyBookings.php', 'Booking request submitted successfully! Waiting for tutor confirmation.');
        }
    }
}

// Generate available time slots based on provider availability and existing bookings
function getAvailableSlots($providerId, $date, $duration, $pdo) {
    $dayOfWeek = date('w', strtotime($date));
    
    // Get availability for this day
    $stmt = $pdo->prepare("
        SELECT start_time, end_time FROM availability 
        WHERE provider_id = ? AND day_of_week = ? AND is_available = 1
    ");
    $stmt->execute([$providerId, $dayOfWeek]);
    $availabilitySlots = $stmt->fetchAll();
    
    if (empty($availabilitySlots)) {
        return [];
    }
    
    // Get existing appointments for this day
    $stmt = $pdo->prepare("
        SELECT start_time, end_time FROM appointment
        WHERE provider_id = ? AND appointment_date = ? AND status IN ('pending', 'confirmed')
    ");
    $stmt->execute([$providerId, $date]);
    $bookedSlots = $stmt->fetchAll();
    
    $available = [];
    
    foreach ($availabilitySlots as $slot) {
        $slotStart = strtotime($slot['start_time']);
        $slotEnd = strtotime($slot['end_time']);
        
        // Generate 30-minute intervals
        for ($time = $slotStart; $time + ($duration * 60) <= $slotEnd; $time += 1800) {
            $timeStr = date('H:i:s', $time);
            $endStr = date('H:i:s', $time + ($duration * 60));
            
            // Check if this slot overlaps with any booked appointment
            $isAvailable = true;
            foreach ($bookedSlots as $booked) {
                $bookedStart = strtotime($booked['start_time']);
                $bookedEnd = strtotime($booked['end_time']);
                
                if ($time < $bookedEnd && $time + ($duration * 60) > $bookedStart) {
                    $isAvailable = false;
                    break;
                }
            }
            
            if ($isAvailable) {
                $available[] = $timeStr;
            }
        }
    }
    
    return $available;
}

// If date is selected, get available slots
$selectedDate = $_GET['date'] ?? $_POST['appointment_date'] ?? '';
$availableSlots = [];
if ($selectedDate && strtotime($selectedDate) >= strtotime('today')) {
    $availableSlots = getAvailableSlots($providerId, $selectedDate, $service['duration_minutes'], $pdo);
}

$pageTitle = 'Book ' . sanitize($service['title']);
include __DIR__ . '/includes/Header.php';
?>

<div class="booking-page">
    <div class="booking-header">
        <a href="ProviderProfile.php?id=<?php echo $providerId; ?>" class="back-link">&#8592; Back to Profile</a>
        <h1>Book a Session</h1>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="booking-grid">
        <div class="booking-summary">
            <div class="summary-card">
                <h2>Session Details</h2>
                <div class="summary-item">
                    <span class="summary-label">Service:</span>
                    <span class="summary-value"><?php echo sanitize($service['title']); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Tutor:</span>
                    <span class="summary-value"><?php echo sanitize($service['first_name'] . ' ' . $service['last_name']); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Category:</span>
                    <span class="summary-value"><?php echo sanitize($service['category_name']); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Duration:</span>
                    <span class="summary-value"><?php echo $service['duration_minutes']; ?> minutes</span>
                </div>
                <div class="summary-item total">
                    <span class="summary-label">Price:</span>
                    <span class="summary-value"><?php echo formatCurrency($service['price']); ?></span>
                </div>
            </div>
            
            <div class="summary-card">
                <h3>Tutor's Weekly Availability</h3>
                <div class="availability-mini">
                    <?php 
                    $availByDay = [];
                    foreach ($availability as $slot) {
                        $day = $slot['day_of_week'];
                        if (!isset($availByDay[$day])) {
                            $availByDay[$day] = [];
                        }
                        $availByDay[$day][] = formatTime($slot['start_time']) . '-' . formatTime($slot['end_time']);
                    }
                    
                    for ($d = 0; $d <= 6; $d++): 
                    ?>
                        <div class="avail-day <?php echo isset($availByDay[$d]) ? 'available' : ''; ?>">
                            <span class="day-abbr"><?php echo substr(getDayName($d), 0, 3); ?></span>
                            <?php if (isset($availByDay[$d])): ?>
                                <span class="day-times"><?php echo implode(', ', $availByDay[$d]); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
        
        <div class="booking-form-section">
            <form method="POST" action="?service_id=<?php echo $serviceId; ?>" class="booking-form">
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
    
    <h2>Select Date & Time</h2>
    
    <div class="form-group">
        <label for="appointment_date">Select Date *</label>
        <input type="date" id="appointment_date" name="appointment_date" required
               min="<?php echo date('Y-m-d'); ?>"
               value="<?php echo sanitize($selectedDate); ?>">
        
        <button type="submit" class="btn btn-secondary" style="margin-top: 10px;">Check Availability</button>
    </div>
    
    <?php if ($selectedDate): ?>
        <div class="form-group">
            <label>Available Time Slots for <?php echo formatDate($selectedDate); ?></label>
            
            <?php if (empty($availableSlots)): ?>
                <div class="no-slots">
                    <p>No available time slots for this date.</p>
                    <p>Please select a different date.</p>
                </div>
            <?php else: ?>
                <div class="time-slots">
                    <?php foreach ($availableSlots as $slot): ?>
                        <label class="time-slot">
                            <input type="radio" name="start_time" value="<?php echo $slot; ?>" required>
                            <span class="slot-time"><?php echo formatTime($slot); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="form-group">
        <label for="notes">Notes for Tutor (optional)</label>
        <textarea id="notes" name="notes" rows="3" 
                  placeholder="Let the tutor know what topics you'd like to focus on..."><?php echo sanitize($_POST['notes'] ?? ''); ?></textarea>
    </div>
    
    <?php if ($selectedDate && !empty($availableSlots)): ?>
        <button type="submit" name="confirm_booking" value="1" class="btn btn-primary btn-lg btn-block">Confirm Booking</button>
    <?php else: ?>
        <button type="button" class="btn btn-secondary btn-lg btn-block" disabled>Select a date and time</button>
    <?php endif; ?>
    
    <p class="booking-note">
        Your booking will be pending until the tutor confirms it.
    </p>
</form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/Footer.php'; ?>