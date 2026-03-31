<?php
/**
 * Provider Profile Page
 */
require_once __DIR__ . '/includes/Functions.php';

$providerId = intval($_GET['id'] ?? 0);

if (!$providerId) {
    header('Location: ServiceCatalog.php');
    exit;
}

$pdo = getDbConnection();

// Get provider profile with user info
$stmt = $pdo->prepare("
    SELECT pp.*, u.first_name, u.last_name, u.email, u.phone, u.location, u.created_at as member_since
    FROM providerprofile pp
    JOIN users u ON pp.user_id = u.id
    WHERE pp.id = ? AND u.is_active = 1
");
$stmt->execute([$providerId]);
$provider = $stmt->fetch();

if (!$provider) {
    header('Location: ServiceCatalog.php');
    exit;
}

// Get provider services
$services = getProviderServices($providerId);

// Get provider availability
$availability = getProviderAvailability($providerId);

// Group availability by day
$availabilityByDay = [];
foreach ($availability as $slot) {
    $day = $slot['day_of_week'];
    if (!isset($availabilityByDay[$day])) {
        $availabilityByDay[$day] = [];
    }
    $availabilityByDay[$day][] = $slot;
}

// Get reviews
$reviews = getProviderReviews($providerId);

$pageTitle = sanitize($provider['first_name'] . ' ' . $provider['last_name']);
include __DIR__ . '/includes/Header.php';
?>

<div class="profile-page">
    <div class="profile-header">
        <div class="profile-avatar-large">
            <?php echo strtoupper(substr($provider['first_name'], 0, 1) . substr($provider['last_name'], 0, 1)); ?>
        </div>
        <div class="profile-info">
            <h1>
                <?php echo sanitize($provider['first_name'] . ' ' . $provider['last_name']); ?>
                <?php if ($provider['is_verified']): ?>
                    <span class="verified-badge">&#10003; Verified Tutor</span>
                <?php endif; ?>
            </h1>
            <div class="profile-meta">
                <?php if ($provider['location']): ?>
                    <span class="meta-item">&#128205; <?php echo sanitize($provider['location']); ?></span>
                <?php endif; ?>
                <span class="meta-item">&#128197; Member since <?php echo formatDate($provider['member_since'], 'F Y'); ?></span>
                <?php if ($provider['experience_years']): ?>
                    <span class="meta-item">&#127891; <?php echo $provider['experience_years']; ?> years experience</span>
                <?php endif; ?>
            </div>
            <div class="profile-rating">
                <?php echo generateStarRating($provider['rating_average']); ?>
                <span class="rating-text">
                    <?php echo number_format($provider['rating_average'], 1); ?> 
                    (<?php echo $provider['total_reviews']; ?> reviews)
                </span>
            </div>
        </div>
    </div>
    
    <div class="profile-content">
        <div class="profile-main">
            <!-- About Section -->
            <section class="profile-section">
                <h2>About</h2>
                <?php if ($provider['bio']): ?>
                    <p><?php echo nl2br(sanitize($provider['bio'])); ?></p>
                <?php else: ?>
                    <p class="text-muted">No bio available.</p>
                <?php endif; ?>
            </section>
            
            <!-- Qualifications Section -->
            <?php if ($provider['qualifications']): ?>
            <section class="profile-section">
                <h2>Qualifications</h2>
                <p><?php echo nl2br(sanitize($provider['qualifications'])); ?></p>
            </section>
            <?php endif; ?>
            
            <!-- Services Section -->
            <section class="profile-section">
                <h2>Services Offered</h2>
                <?php if (empty($services)): ?>
                    <p class="text-muted">No services listed yet.</p>
                <?php else: ?>
                    <div class="services-list">
                        <?php foreach ($services as $service): ?>
                            <div class="service-item">
                                <div class="service-item-info">
                                    <h3><?php echo sanitize($service['title']); ?></h3>
                                    <p class="service-category"><?php echo sanitize($service['category_name']); ?></p>
                                    <?php if ($service['description']): ?>
                                        <p><?php echo sanitize($service['description']); ?></p>
                                    <?php endif; ?>
                                    <span class="service-duration">&#128337; <?php echo $service['duration_minutes']; ?> minutes</span>
                                </div>
                                <div class="service-item-action">
                                    <span class="service-price"><?php echo formatCurrency($service['price']); ?></span>
                                    <?php if (isLoggedIn() && !isProvider() && !isAdmin()): ?>
                                        <a href="BookService.php?service_id=<?php echo $service['id']; ?>" class="btn btn-primary">Book</a>
                                    <?php elseif (!isLoggedIn()): ?>
                                        <a href="Login.php" class="btn btn-outline">Login to Book</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
            
            <!-- Reviews Section -->
            <section class="profile-section">
                <h2>Reviews</h2>
                <?php if (empty($reviews)): ?>
                    <p class="text-muted">No reviews yet.</p>
                <?php else: ?>
                    <div class="reviews-list">
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="reviewer-info">
                                        <span class="reviewer-name"><?php echo sanitize($review['first_name'] . ' ' . $review['last_name'][0] . '.'); ?></span>
                                        <span class="review-date"><?php echo formatDate($review['created_at'], 'M j, Y'); ?></span>
                                    </div>
                                    <?php echo generateStarRating($review['rating']); ?>
                                </div>
                                <?php if ($review['comment']): ?>
                                    <p class="review-comment"><?php echo sanitize($review['comment']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
        
        <aside class="profile-sidebar">
            <!-- Availability Section -->
            <div class="sidebar-card">
                <h3>Availability</h3>
                <?php if (empty($availability)): ?>
                    <p class="text-muted">Availability not set.</p>
                <?php else: ?>
                    <div class="availability-list">
                        <?php for ($day = 0; $day <= 6; $day++): ?>
                            <div class="availability-day">
                                <span class="day-name"><?php echo getDayName($day); ?></span>
                                <span class="day-times">
                                    <?php if (isset($availabilityByDay[$day])): ?>
                                        <?php 
                                        $times = array_map(function($slot) {
                                            return formatTime($slot['start_time']) . ' - ' . formatTime($slot['end_time']);
                                        }, $availabilityByDay[$day]);
                                        echo implode(', ', $times);
                                        ?>
                                    <?php else: ?>
                                        <span class="unavailable">Unavailable</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Contact Card -->
            <div class="sidebar-card">
                <h3>Quick Info</h3>
                <div class="info-list">
                    <?php if ($provider['hourly_rate']): ?>
                        <div class="info-item">
                            <span class="info-label">Hourly Rate:</span>
                            <span class="info-value"><?php echo formatCurrency($provider['hourly_rate']); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <span class="info-label">Response Rate:</span>
                        <span class="info-value">Usually within 24 hours</span>
                    </div>
                </div>
                
                <?php if (isLoggedIn() && !isProvider() && !isAdmin()): ?>
                    <a href="Messages.php?to=<?php echo $provider['user_id']; ?>" class="btn btn-outline btn-block">Send Message</a>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>

<?php include __DIR__ . '/includes/Footer.php'; ?>



<!-- 
6?php
/**
 * Provider Profile Page
 */
require_once __DIR__ . '/includes/Functions.php';

$providerId = intval($_GET['id'] ?? 0);

if (!$providerId) {
    header('Location: ServiceCatalog.php');
    exit;
}

$pdo = getDbConnection();

// Get provider profile with user info
$stmt = $pdo->prepare("
    SELECT pp.*, u.first_name, u.last_name, u.email, u.phone, u.location, u.created_at as member_since
    FROM providerprofile pp
    JOIN users u ON pp.user_id = u.id
    WHERE pp.id = ? AND u.is_active = 1
");
$stmt->execute([$providerId]);
$provider = $stmt->fetch();

if (!$provider) {
    header('Location: ServiceCatalog.php');
    exit;
}

// Get provider services
$services = getProviderServices($providerId);

// Get provider availability
$availability = getProviderAvailability($providerId);

// Group availability by day
$availabilityByDay = [];
foreach ($availability as $slot) {
    $day = $slot['day_of_week'];
    if (!isset($availabilityByDay[$day])) {
        $availabilityByDay[$day] = [];
    }
    $availabilityByDay[$day][] = $slot;
}

// Get reviews
$reviews = getProviderReviews($providerId);

$pageTitle = sanitize($provider['first_name'] . ' ' . $provider['last_name']);
include __DIR__ . '/includes/Header.php';
?>

<div class="profile-page">
    <div class="profile-header">
        <div class="profile-avatar-large">
            6?php echo strtoupper(substr($provider['first_name'], 0, 1) . substr($provider['last_name'], 0, 1)); ?>
        </div>
        <div class="profile-info">
            <h1>
                6?php echo sanitize($provider['first_name'] . ' ' . $provider['last_name']); ?>
                6?php if ($provider['is_verified']): ?>
                    <span class="verified-badge">&#10003; Verified Tutor</span>
                6?php endif; ?>
            </h1>
            <div class="profile-meta">
                6?php if ($provider['location']): ?>
                    <span class="meta-item">&#128205; 6?php echo sanitize($provider['location']); ?></span>
                6?php endif; ?>
                <span class="meta-item">&#128197; Member since 6?php echo formatDate($provider['member_since'], 'F Y'); ?></span>
                6?php if ($provider['experience_years']): ?>
                    <span class="meta-item">&#127891; 6?php echo $provider['experience_years']; ?> years experience</span>
                6?php endif; ?>
            </div>
            <div class="profile-rating">
                6?php echo generateStarRating($provider['rating_average']); ?>
                <span class="rating-text">
                    6?php echo number_format($provider['rating_average'], 1); ?> 
                    (6?php echo $provider['total_reviews']; ?> reviews)
                </span>
            </div>
        </div>
    </div>
    
    <div class="profile-content">
        <div class="profile-main">
            
            <section class="profile-section">
                <h2>About</h2>
                6?php if ($provider['bio']): ?>
                    <p>6?php echo nl2br(sanitize($provider['bio'])); ?></p>
                6?php else: ?>
                    <p class="text-muted">No bio available.</p>
                6?php endif; ?>
            </section>
            
        
            6?php if ($provider['qualifications']): ?>
            <section class="profile-section">
                <h2>Qualifications</h2>
                <p>6?php echo nl2br(sanitize($provider['qualifications'])); ?></p>
            </section>
            6?php endif; ?>
            
            
            <section class="profile-section">
                <h2>Services Offered</h2>
                6?php if (empty($services)): ?>
                    <p class="text-muted">No services listed yet.</p>
                6?php else: ?>
                    <div class="services-list">
                        6?php foreach ($services as $service): ?>
                            <div class="service-item">
                                <div class="service-item-info">
                                    <h3>6?php echo sanitize($service['title']); ?></h3>
                                    <p class="service-category">6?php echo sanitize($service['category_name']); ?></p>
                                    6?php if ($service['description']): ?>
                                        <p>6?php echo sanitize($service['description']); ?></p>
                                    6?php endif; ?>
                                    <span class="service-duration">&#128337; 6?php echo $service['duration_minutes']; ?> minutes</span>
                                </div>
                                <div class="service-item-action">
                                    <span class="service-price">6?php echo formatCurrency($service['price']); ?></span>
                                    6?php if (isLoggedIn() && !isProvider() && !isAdmin()): ?>
                                        <a href="BookService.php?service_id=6?php echo $service['id']; ?>" class="btn btn-primary">Book</a>
                                    6?php elseif (!isLoggedIn()): ?>
                                        <a href="Login.php" class="btn btn-outline">Login to Book</a>
                                    6?php endif; ?>
                                </div>
                            </div>
                        6?php endforeach; ?>
                    </div>
                6?php endif; ?>
            </section>
            
            
            <section class="profile-section">
                <h2>Reviews</h2>
                6?php if (empty($reviews)): ?>
                    <p class="text-muted">No reviews yet.</p>
                6?php else: ?>
                    <div class="reviews-list">
                        6?php foreach ($reviews as $review): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="reviewer-info">
                                        <span class="reviewer-name">6?php echo sanitize($review['first_name'] . ' ' . $review['last_name'][0] . '.'); ?></span>
                                        <span class="review-date">6?php echo formatDate($review['created_at'], 'M j, Y'); ?></span>
                                    </div>
                                    6?php echo generateStarRating($review['rating']); ?>
                                </div>
                                6?php if ($review['comment']): ?>
                                    <p class="review-comment">6?php echo sanitize($review['comment']); ?></p>
                                6?php endif; ?>
                            </div>
                        6?php endforeach; ?>
                    </div>
                6?php endif; ?>
            </section>
        </div>
        
        <aside class="profile-sidebar">
            
            <div class="sidebar-card">
                <h3>Availability</h3>
                6?php if (empty($availability)): ?>
                    <p class="text-muted">Availability not set.</p>
                6?php else: ?>
                    <div class="availability-list">
                        6?php for ($day = 0; $day <= 6; $day++): ?>
                            <div class="availability-day">
                                <span class="day-name">6?php echo getDayName($day); ?></span>
                                <span class="day-times">
                                    6?php if (isset($availabilityByDay[$day])): ?>
                                        6?php 
                                        $times = array_map(function($slot) {
                                            return formatTime($slot['start_time']) . ' - ' . formatTime($slot['end_time']);
                                        }, $availabilityByDay[$day]);
                                        echo implode(', ', $times);
                                        ?>
                                    6?php else: ?>
                                        <span class="unavailable">Unavailable</span>
                                    6?php endif; ?>
                                </span>
                            </div>
                        6?php endfor; ?>
                    </div>
                6?php endif; ?>
            </div>
            
            
            <div class="sidebar-card">
                <h3>Quick Info</h3>
                <div class="info-list">
                    6?php if ($provider['hourly_rate']): ?>
                        <div class="info-item">
                            <span class="info-label">Hourly Rate:</span>
                            <span class="info-value">6?php echo formatCurrency($provider['hourly_rate']); ?></span>
                        </div>
                    6?php endif; ?>
                    <div class="info-item">
                        <span class="info-label">Response Rate:</span>
                        <span class="info-value">Usually within 24 hours</span>
                    </div>
                </div>
                
                6?php if (isLoggedIn() && !isProvider() && !isAdmin()): ?>
                    <a href="Messages.php?to=6?php echo $provider['user_id']; ?>" class="btn btn-outline btn-block">Send Message</a>
                6?php endif; ?>
            </div>
        </aside>
    </div>
</div>

6?php include __DIR__ . '/includes/Footer.php'; ?> -->