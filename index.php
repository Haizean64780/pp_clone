<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Le reste de ton code...
/**
 * Homepage / Landing Page
 */
require_once __DIR__ . '/includes/Functions.php';

// Get featured categories
$categories = getCategories();

// Get top-rated services
$featuredServices = getServices(['sort' => 'rating']);
$featuredServices = array_slice($featuredServices, 0, 6);

$pageTitle = 'Home';
include __DIR__ . '/includes/Header.php';
?>

<div class="homepage">
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Find Your Perfect Tutor</h1>
            <p>Connect with qualified peer tutors for personalized learning experiences</p>
            
            <form action="ServiceCatalog.php" method="GET" class="hero-search">
                <input type="text" name="search" placeholder="What do you want to learn?">
                <select name="category_id">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo sanitize($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>
    </section>
    
    <!-- Categories Section -->
    <section class="categories-section">
        <h2>Browse by Category</h2>
        <div class="category-grid">
            <?php foreach ($categories as $cat): ?>
                <a href="ServiceCatalog.php?category_id=<?php echo $cat['id']; ?>" class="category-card">
                    <div class="category-icon">
                        <?php
                        $icons = [
                            'math' => '&#128290;',
                            'science' => '&#128300;',
                            'language' => '&#127760;',
                            'computer' => '&#128187;',
                            'business' => '&#128188;',
                            'arts' => '&#127912;',
                            'test' => '&#128221;',
                            'writing' => '&#128221;'
                        ];
                        echo $icons[$cat['icon']] ?? '&#128218;';
                        ?>
                    </div>
                    <h3><?php echo sanitize($cat['name']); ?></h3>
                    <p><?php echo sanitize($cat['description']); ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    
    <!-- Featured Services Section -->
    <section class="featured-section">
        <h2>Top-Rated Tutors</h2>
        <div class="service-grid">
            <?php foreach ($featuredServices as $service): ?>
                <div class="service-card">
                    <div class="service-header">
                        <div class="tutor-avatar">
                            <?php echo strtoupper(substr($service['first_name'], 0, 1) . substr($service['last_name'], 0, 1)); ?>
                        </div>
                        <div class="tutor-info">
                            <h4><?php echo sanitize($service['first_name'] . ' ' . $service['last_name']); ?></h4>
                            <?php if ($service['is_verified']): ?>
                                <span class="verified-badge">&#10003; Verified</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="service-body">
                        <h3><?php echo sanitize($service['title']); ?></h3>
                        <p class="service-category"><?php echo sanitize($service['category_name']); ?></p>
                        <p class="service-description"><?php echo sanitize(substr($service['description'], 0, 100)); ?>...</p>
                        <div class="service-meta">
                            <?php echo generateStarRating($service['rating_average']); ?>
                            <span class="rating-text"><?php echo number_format($service['rating_average'], 1); ?></span>
                        </div>
                    </div>
                    <div class="service-footer">
                        <span class="service-price"><?php echo formatCurrency($service['price']); ?>/session</span>
                        <a href="ProviderProfile.php?id=<?php echo $service['provider_id']; ?>" class="btn btn-outline btn-sm">View Profile</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="section-action">
            <a href="ServiceCatalog.php" class="btn btn-primary">Browse All Services</a>
        </div>
    </section>
    
    <!-- How It Works Section -->
    <section class="how-it-works">
        <h2>How It Works</h2>
        <div class="steps">
            <div class="step">
                <div class="step-number">1</div>
                <h3>Find a Tutor</h3>
                <p>Browse our catalog of qualified peer tutors and find the perfect match for your needs.</p>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <h3>Book a Session</h3>
                <p>Choose a time that works for you and book your tutoring session online.</p>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <h3>Learn & Grow</h3>
                <p>Meet with your tutor and start improving your skills and knowledge.</p>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="cta-section">
        <div class="cta-content">
            <h2>Ready to Start Learning?</h2>
            <p>Join thousands of students who have improved their grades with peer tutoring.</p>
            <div class="cta-buttons">
                <a href="Signup.php" class="btn btn-primary btn-lg">Get Started</a>
                <a href="Signup.php?role=provider" class="btn btn-outline btn-lg">Become a Tutor</a>
            </div>
        </div>
    </section>
    
    <!-- FAQ Section -->
    <section class="faq-section">
        <h2>Frequently Asked Questions</h2>
        <div class="faq-list">
            <div class="faq-item">
                <h3>How do I book a tutoring session?</h3>
                <p>Simply browse our tutors, select a service, and choose an available time slot. You'll receive a confirmation once the tutor accepts your booking.</p>
            </div>
            <div class="faq-item">
                <h3>Can I cancel or reschedule a booking?</h3>
                <p>Yes, you can cancel or reschedule your booking through your dashboard. Please do so at least 24 hours in advance to avoid any issues.</p>
            </div>
            <div class="faq-item">
                <h3>How do I become a tutor?</h3>
                <p>Sign up as a tutor, complete your profile, add your services, and set your availability. Once approved, students can start booking sessions with you.</p>
            </div>
            <div class="faq-item">
                <h3>Are the tutors verified?</h3>
                <p>All tutors go through a verification process. Look for the verified badge on tutor profiles to ensure quality.</p>
            </div>
        </div>
    </section>
</div>

<?php include __DIR__ . '/includes/Footer.php'; ?>
