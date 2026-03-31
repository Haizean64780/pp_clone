<?php
/**
 * General Helper Functions
 */

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Auth.php';

/**
 * Sanitize input
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate CSRF token
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Format date
 */
function formatDate($date, $format = 'F j, Y') {
    return date($format, strtotime($date));
}

/**
 * Format time
 */
function formatTime($time, $format = 'g:i A') {
    return date($format, strtotime($time));
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

/**
 * Get all categories
 */
function getCategories() {
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT * FROM category WHERE is_active = 1 ORDER BY name");
    return $stmt->fetchAll();
}

/**
 * Get category by ID
 */
function getCategoryById($id) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM category WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Get all services with optional filters
 */
function getServices($filters = []) {
    $pdo = getDbConnection();
    
    $sql = "
        SELECT s.*, c.name as category_name, 
               u.first_name, u.last_name, u.location,
               pp.rating_average, pp.is_verified, pp.id as provider_id
        FROM service s
        JOIN category c ON s.category_id = c.id
        JOIN providerprofile pp ON s.provider_id = pp.id
        JOIN users u ON pp.user_id = u.id
        WHERE s.is_active = 1 AND u.is_active = 1
    ";
    
    $params = [];
    
    if (!empty($filters['category_id'])) {
        $sql .= " AND s.category_id = ?";
        $params[] = $filters['category_id'];
    }
    
    if (!empty($filters['location'])) {
        $sql .= " AND u.location LIKE ?";
        $params[] = '%' . $filters['location'] . '%';
    }
    
    if (!empty($filters['search'])) {
        $sql .= " AND (s.title LIKE ? OR s.description LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
        $searchTerm = '%' . $filters['search'] . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Sorting
    $sortOptions = [
        'rating' => 'pp.rating_average DESC',
        'price_low' => 's.price ASC',
        'price_high' => 's.price DESC',
        'newest' => 's.created_at DESC'
    ];
    $sort = $filters['sort'] ?? 'rating';
    $sql .= " ORDER BY " . ($sortOptions[$sort] ?? $sortOptions['rating']);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Get service by ID
 */
function getServiceById($id) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("
        SELECT s.*, c.name as category_name,
               u.first_name, u.last_name, u.email, u.phone, u.location,
               pp.bio, pp.qualifications, pp.experience_years, pp.hourly_rate,
               pp.rating_average, pp.total_reviews, pp.is_verified, pp.id as provider_id, pp.user_id as provider_user_id
        FROM service s
        JOIN category c ON s.category_id = c.id
        JOIN providerprofile pp ON s.provider_id = pp.id
        JOIN users u ON pp.user_id = u.id
        WHERE s.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Get provider services
 */
function getProviderServices($providerId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("
        SELECT s.*, c.name as category_name
        FROM service s
        JOIN category c ON s.category_id = c.id
        WHERE s.provider_id = ?
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([$providerId]);
    return $stmt->fetchAll();
}

/**
 * Get provider availability
 */
function getProviderAvailability($providerId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("
        SELECT * FROM availability 
        WHERE provider_id = ? AND is_available = 1
        ORDER BY day_of_week, start_time
    ");
    $stmt->execute([$providerId]);
    return $stmt->fetchAll();
}

/**
 * Get day name from number
 */
function getDayName($dayNumber) {
    $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    return $days[$dayNumber] ?? '';
}

/**
 * Check if time slot is available
 */
function isTimeSlotAvailable($providerId, $date, $startTime, $endTime, $excludeAppointmentId = null) {
    $pdo = getDbConnection();
    
    $sql = "
        SELECT COUNT(*) as count FROM appointment
        WHERE provider_id = ? 
        AND appointment_date = ?
        AND status IN ('pending', 'confirmed')
        AND (
            (start_time < ? AND end_time > ?)
            OR (start_time < ? AND end_time > ?)
            OR (start_time >= ? AND end_time <= ?)
        )
    ";
    $params = [$providerId, $date, $endTime, $startTime, $endTime, $startTime, $startTime, $endTime];
    
    if ($excludeAppointmentId) {
        $sql .= " AND id != ?";
        $params[] = $excludeAppointmentId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    
    return $result['count'] == 0;
}

/**
 * Create notification
 */
function createNotification($userId, $type, $title, $message, $link = null) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("
        INSERT INTO notification (user_id, type, title, message, link)
        VALUES (?, ?, ?, ?, ?)
    ");
    return $stmt->execute([$userId, $type, $title, $message, $link]);
}

/**
 * Get user notifications
 */
function getUserNotifications($userId, $limit = 10) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("
        SELECT * FROM notification 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}

/**
 * Get unread notification count
 */
function getUnreadNotificationCount($userId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notification WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result['count'];
}

/**
 * Mark notification as read
 */
function markNotificationRead($notificationId, $userId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("UPDATE notification SET is_read = 1 WHERE id = ? AND user_id = ?");
    return $stmt->execute([$notificationId, $userId]);
}

/**
 * Get user appointments
 */
function getUserAppointments($userId, $status = null) {
    $pdo = getDbConnection();
    
    $sql = "
        SELECT a.*, s.title as service_title, s.duration_minutes,
               u.first_name as provider_first_name, u.last_name as provider_last_name,
               pp.id as provider_id
        FROM appointment a
        JOIN service s ON a.service_id = s.id
        JOIN providerprofile pp ON a.provider_id = pp.id
        JOIN users u ON pp.user_id = u.id
        WHERE a.client_id = ?
    ";
    $params = [$userId];
    
    if ($status) {
        $sql .= " AND a.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY a.appointment_date DESC, a.start_time DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Get provider appointments
 */
function getProviderAppointments($providerId, $status = null) {
    $pdo = getDbConnection();
    
    $sql = "
        SELECT a.*, s.title as service_title, s.duration_minutes,
               u.first_name as client_first_name, u.last_name as client_last_name,
               u.email as client_email, u.phone as client_phone
        FROM appointment a
        JOIN service s ON a.service_id = s.id
        JOIN users u ON a.client_id = u.id
        WHERE a.provider_id = ?
    ";
    $params = [$providerId];
    
    if ($status) {
        $sql .= " AND a.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY a.appointment_date DESC, a.start_time DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Get appointment by ID
 */
function getAppointmentById($id) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("
        SELECT a.*, s.title as service_title, s.duration_minutes,
               u.first_name as client_first_name, u.last_name as client_last_name,
               u.email as client_email,
               pu.first_name as provider_first_name, pu.last_name as provider_last_name,
               pu.email as provider_email,
               pp.id as provider_profile_id, pp.user_id as provider_user_id
        FROM appointment a
        JOIN service s ON a.service_id = s.id
        JOIN users u ON a.client_id = u.id
        JOIN providerprofile pp ON a.provider_id = pp.id
        JOIN users pu ON pp.user_id = pu.id
        WHERE a.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Get provider reviews
 */
function getProviderReviews($providerId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("
        SELECT r.*, u.first_name, u.last_name
        FROM review r
        JOIN users u ON r.reviewer_id = u.id
        WHERE r.provider_id = ? AND r.is_approved = 1
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$providerId]);
    return $stmt->fetchAll();
}

/**
 * Get all users (for admin)
 */
function getAllUsers() {
    $pdo = getDbConnection();
    $stmt = $pdo->query("
        SELECT u.*, r.name as role_name
        FROM users u
        JOIN roles r ON u.role_id = r.id
        ORDER BY u.created_at DESC
    ");
    return $stmt->fetchAll();
}

/**
 * Get messages for user
 */
function getUserMessages($userId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("
        SELECT m.*, 
               s.first_name as sender_first_name, s.last_name as sender_last_name,
               r.first_name as receiver_first_name, r.last_name as receiver_last_name
        FROM message m
        JOIN users s ON m.sender_id = s.id
        JOIN users r ON m.receiver_id = r.id
        WHERE m.sender_id = ? OR m.receiver_id = ?
        ORDER BY m.created_at DESC
    ");
    $stmt->execute([$userId, $userId]);
    return $stmt->fetchAll();
}

/**
 * Redirect with message
 */
function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: $url");
    exit;
}

/**
 * Display flash message
 */
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        
        $class = $type === 'error' ? 'alert-error' : ($type === 'warning' ? 'alert-warning' : 'alert-success');
        echo "<div class='alert {$class}'>" . sanitize($message) . "</div>";
    }
}

/**
 * Generate star rating HTML
 */
function generateStarRating($rating) {
    $html = '<div class="star-rating">';
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5;
    
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $fullStars) {
            $html .= '<span class="star filled">&#9733;</span>';
        } elseif ($halfStar && $i == $fullStars + 1) {
            $html .= '<span class="star half">&#9733;</span>';
        } else {
            $html .= '<span class="star">&#9734;</span>';
        }
    }
    $html .= '</div>';
    return $html;
}

/**
 * Get status badge HTML
 */
function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge badge-warning">Pending</span>',
        'confirmed' => '<span class="badge badge-success">Confirmed</span>',
        'completed' => '<span class="badge badge-info">Completed</span>',
        'cancelled' => '<span class="badge badge-danger">Cancelled</span>',
        'rescheduled' => '<span class="badge badge-secondary">Rescheduled</span>',
        'no_show' => '<span class="badge badge-dark">No Show</span>'
    ];
    return $badges[$status] ?? '<span class="badge">' . ucfirst($status) . '</span>';
}