<?php
/**
 * Admin Panel
 */
require_once __DIR__ . '/includes/Functions.php';

requireAdmin();

$pdo = getDbConnection();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'toggle_user') {
        $userId = intval($_POST['user_id']);
        $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ? AND id != ?");
        $stmt->execute([$userId, getCurrentUserId()]);
        redirectWithMessage('AdminPanel.php', 'User status updated.');
    }
    
    // NOUVEAU : Gérer le changement de rôle
    if ($action === 'change_role') {
        $targetUserId = intval($_POST['user_id']);
        $newRoleId = intval($_POST['role_id']);
        
        // Empêcher l'admin de modifier son propre rôle (pour ne pas s'enfermer dehors)
        if ($targetUserId !== getCurrentUserId()) {
            $stmt = $pdo->prepare("UPDATE users SET role_id = ? WHERE id = ?");
            $stmt->execute([$newRoleId, $targetUserId]);
            
            // Récupérer le nom du nouveau rôle
            $stmt = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
            $stmt->execute([$newRoleId]);
            $roleName = $stmt->fetchColumn();
            
            // Si on le transforme en Tuteur, on lui crée un profil s'il n'en a pas déjà un
            if ($roleName === 'provider') {
                $stmt = $pdo->prepare("SELECT id FROM providerprofile WHERE user_id = ?");
                $stmt->execute([$targetUserId]);
                if (!$stmt->fetch()) {
                    $stmt = $pdo->prepare("INSERT INTO providerprofile (user_id, bio, hourly_rate) VALUES (?, '', 25.00)");
                    $stmt->execute([$targetUserId]);
                }
            }
            redirectWithMessage('AdminPanel.php?tab=users', 'User role updated successfully.');
        } else {
            redirectWithMessage('AdminPanel.php?tab=users', 'You cannot change your own role.', 'error');
        }
    }
    
    if ($action === 'add_category') {
        $name = sanitize($_POST['category_name']);
        $description = sanitize($_POST['category_description']);
        if (!empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO category (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            // CORRECTION ICI 👇
            redirectWithMessage('AdminPanel.php?tab=categories', 'Category added successfully.');
        }
    }
    
    if ($action === 'delete_category') {
        $categoryId = intval($_POST['category_id']);
        $stmt = $pdo->prepare("DELETE FROM category WHERE id = ?");
        $stmt->execute([$categoryId]);
        // CORRECTION ICI 👇
        redirectWithMessage('AdminPanel.php?tab=categories', 'Category deleted.');
    }
    
    if ($action === 'approve_review') {
        $reviewId = intval($_POST['review_id']);
        $stmt = $pdo->prepare("UPDATE review SET is_approved = 1, is_flagged = 0 WHERE id = ?");
        $stmt->execute([$reviewId]);
        redirectWithMessage('AdminPanel.php?tab=reviews', 'Review approved.');
    }
    
    if ($action === 'delete_review') {
        $reviewId = intval($_POST['review_id']);
        $stmt = $pdo->prepare("DELETE FROM review WHERE id = ?");
        $stmt->execute([$reviewId]);
        redirectWithMessage('AdminPanel.php?tab=reviews', 'Review deleted.');
    }
    
    if ($action === 'override_booking') {
        $appointmentId = intval($_POST['appointment_id']);
        $newStatus = sanitize($_POST['new_status']);
        $justification = sanitize($_POST['justification']);
        
        if (!empty($justification)) {
            $stmt = $pdo->prepare("UPDATE appointment SET status = ?, provider_notes = CONCAT(IFNULL(provider_notes, ''), '\n[Admin Override]: ', ?) WHERE id = ?");
            $stmt->execute([$newStatus, $justification, $appointmentId]);
            redirectWithMessage('AdminPanel.php?tab=bookings', 'Booking updated with justification.');
        }
    }
}

// Get stats
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
$totalUsers = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role_id = (SELECT id FROM roles WHERE name = 'provider')");
$totalProviders = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM appointment");
$totalAppointments = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM review WHERE is_flagged = 1 OR is_approved = 0");
$pendingReviews = $stmt->fetch()['count'];

// Get data based on active tab
$activeTab = $_GET['tab'] ?? 'users';

$users = getAllUsers();
$categories = getCategories();

// NOUVEAU : Récupérer tous les rôles pour le formulaire
$stmt = $pdo->query("SELECT * FROM roles ORDER BY id");
$allRoles = $stmt->fetchAll();

// Get all reviews for moderation
$stmt = $pdo->query("
    SELECT r.*, u.first_name, u.last_name, 
           pu.first_name as provider_first_name, pu.last_name as provider_last_name
    FROM review r
    JOIN users u ON r.reviewer_id = u.id
    JOIN providerprofile pp ON r.provider_id = pp.id
    JOIN users pu ON pp.user_id = pu.id
    ORDER BY r.is_flagged DESC, r.is_approved ASC, r.created_at DESC
");
$reviews = $stmt->fetchAll();

// Get all bookings
$stmt = $pdo->query("
    SELECT a.*, s.title as service_title,
           u.first_name as client_first_name, u.last_name as client_last_name,
           pu.first_name as provider_first_name, pu.last_name as provider_last_name
    FROM appointment a
    JOIN service s ON a.service_id = s.id
    JOIN users u ON a.client_id = u.id
    JOIN providerprofile pp ON a.provider_id = pp.id
    JOIN users pu ON pp.user_id = pu.id
    ORDER BY a.created_at DESC
    LIMIT 50
");
$bookings = $stmt->fetchAll();

$pageTitle = 'Admin Panel';
include __DIR__ . '/includes/Header.php';
?>

<div class="admin-panel">
    <div class="dashboard-header">
        <h1>Admin Panel</h1>
        <p>Manage users, categories, and system settings</p>
    </div>
    
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon">&#128101;</div>
            <div class="stat-info">
                <span class="stat-number"><?php echo $totalUsers; ?></span>
                <span class="stat-label">Total Users</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">&#127891;</div>
            <div class="stat-info">
                <span class="stat-number"><?php echo $totalProviders; ?></span>
                <span class="stat-label">Tutors</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">&#128197;</div>
            <div class="stat-info">
                <span class="stat-number"><?php echo $totalAppointments; ?></span>
                <span class="stat-label">Total Bookings</span>
            </div>
        </div>
        <div class="stat-card stat-warning">
            <div class="stat-icon">&#9888;</div>
            <div class="stat-info">
                <span class="stat-number"><?php echo $pendingReviews; ?></span>
                <span class="stat-label">Pending Reviews</span>
            </div>
        </div>
    </div>
    
    <div class="admin-tabs">
        <a href="?tab=users" class="tab <?php echo $activeTab === 'users' ? 'active' : ''; ?>">Users</a>
        <a href="?tab=categories" class="tab <?php echo $activeTab === 'categories' ? 'active' : ''; ?>">Categories</a>
        <a href="?tab=reviews" class="tab <?php echo $activeTab === 'reviews' ? 'active' : ''; ?>">Reviews</a>
        <a href="?tab=bookings" class="tab <?php echo $activeTab === 'bookings' ? 'active' : ''; ?>">Bookings</a>
    </div>
    
    <?php if ($activeTab === 'users'): ?>
    <div class="admin-section">
        <h2>User Management</h2>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td><?php echo sanitize($u['first_name'] . ' ' . $u['last_name']); ?></td>
                        <td><?php echo sanitize($u['email']); ?></td>
                        <td><span class="badge"><?php echo ucfirst($u['role_name']); ?></span></td>
                        <td><?php echo sanitize($u['location'] ?? 'N/A'); ?></td>
                        <td>
                            <?php if ($u['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($u['id'] != getCurrentUserId()): ?>
                                <button type="button" class="btn btn-outline btn-sm" onclick="showRoleModal(<?php echo $u['id']; ?>)">
                                    Change Role
                                </button>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                    <input type="hidden" name="action" value="toggle_user">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <button type="submit" class="btn btn-sm <?php echo $u['is_active'] ? 'btn-danger' : 'btn-success'; ?>">
                                        <?php echo $u['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($activeTab === 'categories'): ?>
    <div class="admin-section">
        <h2>Category Management</h2>
        
        <form method="POST" class="inline-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="add_category">
            <div class="form-row">
                <input type="text" name="category_name" placeholder="Category Name" required>
                <input type="text" name="category_description" placeholder="Description">
                <button type="submit" class="btn btn-primary">Add Category</button>
            </div>
        </form>
        
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><?php echo $cat['id']; ?></td>
                        <td><?php echo sanitize($cat['name']); ?></td>
                        <td><?php echo sanitize($cat['description'] ?? ''); ?></td>
                        <td>
                            <?php if ($cat['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                <input type="hidden" name="action" value="delete_category">
                                <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($activeTab === 'reviews'): ?>
    <div class="admin-section">
        <h2>Review Moderation</h2>
        
        <?php if (empty($reviews)): ?>
            <div class="empty-state">
                <p>No reviews to moderate.</p>
            </div>
        <?php else: ?>
        <div class="review-list">
            <?php foreach ($reviews as $review): ?>
            <div class="review-card <?php echo $review['is_flagged'] ? 'flagged' : ''; ?> <?php echo !$review['is_approved'] ? 'pending' : ''; ?>">
                <div class="review-header">
                    <div>
                        <strong><?php echo sanitize($review['first_name'] . ' ' . $review['last_name']); ?></strong>
                        reviewed
                        <strong><?php echo sanitize($review['provider_first_name'] . ' ' . $review['provider_last_name']); ?></strong>
                    </div>
                    <div>
                        <?php echo generateStarRating($review['rating']); ?>
                        <?php if ($review['is_flagged']): ?>
                            <span class="badge badge-danger">Flagged</span>
                        <?php endif; ?>
                        <?php if (!$review['is_approved']): ?>
                            <span class="badge badge-warning">Pending</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="review-content">
                    <p><?php echo sanitize($review['comment']); ?></p>
                </div>
                <div class="review-actions">
                    <?php if (!$review['is_approved'] || $review['is_flagged']): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="action" value="approve_review">
                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                        <button type="submit" class="btn btn-success btn-sm">Approve</button>
                    </form>
                    <?php endif; ?>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this review?');">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="action" value="delete_review">
                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <?php if ($activeTab === 'bookings'): ?>
    <div class="admin-section">
        <h2>Booking Management</h2>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Service</th>
                        <th>Client</th>
                        <th>Provider</th>
                        <th>Date/Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?php echo $booking['id']; ?></td>
                        <td><?php echo sanitize($booking['service_title']); ?></td>
                        <td><?php echo sanitize($booking['client_first_name'] . ' ' . $booking['client_last_name']); ?></td>
                        <td><?php echo sanitize($booking['provider_first_name'] . ' ' . $booking['provider_last_name']); ?></td>
                        <td><?php echo formatDate($booking['appointment_date'], 'M j') . ' ' . formatTime($booking['start_time']); ?></td>
                        <td><?php echo getStatusBadge($booking['status']); ?></td>
                        <td>
                            <button type="button" class="btn btn-outline btn-sm" 
                                    onclick="showOverrideModal(<?php echo $booking['id']; ?>, '<?php echo $booking['status']; ?>')">
                                Override
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <div id="overrideModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h3>Override Booking Status</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="override_booking">
                <input type="hidden" name="appointment_id" id="override_appointment_id">
                
                <div class="form-group">
                    <label>New Status</label>
                    <select name="new_status" required>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="no_show">No Show</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Justification (required)</label>
                    <textarea name="justification" required placeholder="Explain why you are overriding this booking..."></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="hideOverrideModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Override</button>
                </div>
            </form>
        </div>
    </div>

    <div id="roleModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h3>Change User Role</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="change_role">
                <input type="hidden" name="user_id" id="role_user_id">
                
                <div class="form-group">
                    <label>Select New Role</label>
                    <select name="role_id" required>
                        <?php foreach ($allRoles as $role): ?>
                            <option value="<?php echo $role['id']; ?>"><?php echo ucfirst($role['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="hideRoleModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Role</button>
                </div>
            </form>
        </div>
    </div>
    
    <style>
        .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: white; padding: 2rem; border-radius: 8px; max-width: 500px; width: 90%; }
        .modal-actions { display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1rem; }
    </style>
    
    <script>
        function showOverrideModal(appointmentId, currentStatus) {
            document.getElementById('override_appointment_id').value = appointmentId;
            document.getElementById('overrideModal').style.display = 'flex';
        }
        function hideOverrideModal() {
            document.getElementById('overrideModal').style.display = 'none';
        }
        
        // NOUVEAU : Fonctions JavaScript pour ouvrir/fermer le modal de rôle
        function showRoleModal(userId) {
            document.getElementById('role_user_id').value = userId;
            document.getElementById('roleModal').style.display = 'flex';
        }
        function hideRoleModal() {
            document.getElementById('roleModal').style.display = 'none';
        }
    </script>
</div>

<?php include __DIR__ . '/includes/Footer.php'; ?>