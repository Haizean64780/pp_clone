<?php
/**
 * Service Catalog - Browse All Services
 */
require_once __DIR__ . '/includes/Functions.php';

// Get filter parameters
$filters = [
    'category_id' => $_GET['category_id'] ?? '',
    'location' => $_GET['location'] ?? '',
    'search' => $_GET['search'] ?? '',
    'sort' => $_GET['sort'] ?? 'rating'
];

// Get services with filters
$services = getServices($filters);

// Get categories for filter dropdown
$categories = getCategories();

// Get current category name if filtered
$currentCategory = null;
if (!empty($filters['category_id'])) {
    $currentCategory = getCategoryById($filters['category_id']);
}

$pageTitle = $currentCategory ? sanitize($currentCategory['name']) . ' Tutoring' : 'Service Catalog';
include __DIR__ . '/includes/Header.php';
?>

<div class="catalog-page">
    <div class="catalog-header">
        <h1><?php echo $currentCategory ? sanitize($currentCategory['name']) . ' Tutoring' : 'Find a Tutor'; ?></h1>
        <p><?php echo count($services); ?> services available</p>
    </div>
    
    <!-- Filters -->
    <div class="catalog-filters">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label for="search">Search</label>
                <input type="text" id="search" name="search" placeholder="Search services..." 
                       value="<?php echo sanitize($filters['search']); ?>">
            </div>
            
            <div class="filter-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $filters['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo sanitize($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location" placeholder="e.g., Library" 
                       value="<?php echo sanitize($filters['location']); ?>">
            </div>
            
            <div class="filter-group">
                <label for="sort">Sort By</label>
                <select id="sort" name="sort">
                    <option value="rating" <?php echo $filters['sort'] === 'rating' ? 'selected' : ''; ?>>Top Rated</option>
                    <option value="price_low" <?php echo $filters['sort'] === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_high" <?php echo $filters['sort'] === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                    <option value="newest" <?php echo $filters['sort'] === 'newest' ? 'selected' : ''; ?>>Newest</option>
                </select>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="ServiceCatalog.php" class="btn btn-outline">Clear</a>
            </div>
        </form>
    </div>
    
    <!-- Services Grid -->
    <?php if (empty($services)): ?>
        <div class="empty-state">
            <h3>No services found</h3>
            <p>Try adjusting your filters or search terms.</p>
            <a href="ServiceCatalog.php" class="btn btn-primary">Clear Filters</a>
        </div>
    <?php else: ?>
        <div class="service-grid">
            <?php foreach ($services as $service): ?>
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
                            <?php if ($service['location']): ?>
                                <span class="location">&#128205; <?php echo sanitize($service['location']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="service-body">
                        <h3><?php echo sanitize($service['title']); ?></h3>
                        <p class="service-category"><?php echo sanitize($service['category_name']); ?></p>
                        <?php if ($service['description']): ?>
                            <p class="service-description"><?php echo sanitize(substr($service['description'], 0, 120)); ?>...</p>
                        <?php endif; ?>
                        <div class="service-details">
                            <span class="duration">&#128337; <?php echo $service['duration_minutes']; ?> min</span>
                        </div>
                        <div class="service-meta">
                            <?php echo generateStarRating($service['rating_average']); ?>
                            <span class="rating-text"><?php echo number_format($service['rating_average'], 1); ?></span>
                        </div>
                    </div>
                    <div class="service-footer">
                        <span class="service-price"><?php echo formatCurrency($service['price']); ?></span>
                        <div class="service-actions">
                            <a href="ProviderProfile.php?id=<?php echo $service['provider_id']; ?>" class="btn btn-outline btn-sm">View Profile</a>
                            <?php if (isLoggedIn() && !isProvider() && !isAdmin()): ?>
                                <a href="BookService.php?service_id=<?php echo $service['id']; ?>" class="btn btn-primary btn-sm">Book Now</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/Footer.php'; ?>









<!-- 6?php
/**
 * Service Catalog - Browse All Services
 */
require_once __DIR__ . '/includes/Functions.php';

// Get filter parameters
$filters = [
    'category_id' => $_GET['category_id'] ?? '',
    'location' => $_GET['location'] ?? '',
    'search' => $_GET['search'] ?? '',
    'sort' => $_GET['sort'] ?? 'rating'
];

// Get services with filters
$services = getServices($filters);

// Get categories for filter dropdown
$categories = getCategories();

// Get current category name if filtered
$currentCategory = null;
if (!empty($filters['category_id'])) {
    $currentCategory = getCategoryById($filters['category_id']);
}

$pageTitle = $currentCategory ? sanitize($currentCategory['name']) . ' Tutoring' : 'Service Catalog';
include __DIR__ . '/includes/Header.php';
?>

<div class="catalog-page">
    <div class="catalog-header">
        <h1>6?php echo $currentCategory ? sanitize($currentCategory['name']) . ' Tutoring' : 'Find a Tutor'; ?></h1>
        <p>6?php echo count($services); ?> services available</p>
    </div>
    
     Filters 
         <div class="catalog-filters">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label for="search">Search</label>
                <input type="text" id="search" name="search" placeholder="Search services..." 
                       value="<?php echo sanitize($filters['search']); ?>">
            </div>
            
            <div class="filter-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id">
                    <option value="">All Categories</option>
                    6?php foreach ($categories as $cat): ?>
                        <option value="6?php echo $cat['id']; ?>" 6?php echo $filters['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                            6?php echo sanitize($cat['name']); ?>
                        </option>
                    6?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location" placeholder="e.g., Library" 
                       value="6?php echo sanitize($filters['location']); ?>">
            </div>
            
            <div class="filter-group">
                <label for="sort">Sort By</label>
                <select id="sort" name="sort">
                    <option value="rating" 6?php echo $filters['sort'] === 'rating' ? 'selected' : ''; ?>>Top Rated</option>
                    <option value="price_low" 6?php echo $filters['sort'] === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_high" 6?php echo $filters['sort'] === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                    <option value="newest" 6?php echo $filters['sort'] === 'newest' ? 'selected' : ''; ?>>Newest</option>
                </select>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="ServiceCatalog.php" class="btn btn-outline">Clear</a>
            </div>
        </form>
    </div>
    
     Services Grid 
    6?php if (empty($services)): ?>
        <div class="empty-state">
            <h3>No services found</h3>
            <p>Try adjusting your filters or search terms.</p>
            <a href="ServiceCatalog.php" class="btn btn-primary">Clear Filters</a>
        </div>
    6?php else: ?>
        <div class="service-grid">
            6?php foreach ($services as $service): ?>
                <div class="service-card">
                    <div class="service-header">
                        <div class="tutor-avatar">
                            6?php echo strtoupper(substr($service['first_name'], 0, 1) . substr($service['last_name'], 0, 1)); ?>
                        </div>
                        <div class="tutor-info">
                            <h4>6?php echo sanitize($service['first_name'] . ' ' . $service['last_name']); ?></h4>
                            6?php if ($service['is_verified']): ?>
                                <span class="verified-badge">&#10003; Verified</span>
                            6?php endif; ?>
                            6?php if ($service['location']): ?>
                                <span class="location">&#128205; 6?php echo sanitize($service['location']); ?></span>
                            6?php endif; ?>
                        </div>
                    </div>
                    <div class="service-body">
                        <h3>6?php echo sanitize($service['title']); ?></h3>
                        <p class="service-category">6?php echo sanitize($service['category_name']); ?></p>
                        6?php if ($service['description']): ?>
                            <p class="service-description">6?php echo sanitize(substr($service['description'], 0, 120)); ?>...</p>
                        6?php endif; ?>
                        <div class="service-details">
                            <span class="duration">&#128337; 6?php echo $service['duration_minutes']; ?> min</span>
                        </div>
                        <div class="service-meta">
                            6?php echo generateStarRating($service['rating_average']); ?>
                            <span class="rating-text">6?php echo number_format($service['rating_average'], 1); ?></span>
                        </div>
                    </div>
                    <div class="service-footer">
                        <span class="service-price">6?php echo formatCurrency($service['price']); ?></span>
                        <div class="service-actions">
                            <a href="ProviderProfile.php?id=6?php echo $service['provider_id']; ?>" class="btn btn-outline btn-sm">View Profile</a>
                            6?php if (isLoggedIn() && !isProvider() && !isAdmin()): ?>
                                <a href="BookService.php?service_id=6?php echo $service['id']; ?>" class="btn btn-primary btn-sm">Book Now</a>
                            6?php endif; ?>
                        </div>
                    </div>
                </div>
            6?php endforeach; ?>
        </div>
    6?php endif; ?>
</div>

6?php include __DIR__ . '/includes/Footer.php'; ?>
 -->