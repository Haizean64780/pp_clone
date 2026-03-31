<?php
/**
 * Manage Services - Provider Service Management
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
    
    if ($action === 'add' || $action === 'edit') {
        $serviceId = intval($_POST['service_id'] ?? 0);
        $data = [
            'title' => sanitize($_POST['title'] ?? ''),
            'category_id' => intval($_POST['category_id'] ?? 0),
            'description' => sanitize($_POST['description'] ?? ''),
            'duration_minutes' => intval($_POST['duration_minutes'] ?? 60),
            'price' => floatval($_POST['price'] ?? 0)
        ];
        
        // Validation
        if (empty($data['title']) || empty($data['category_id']) || $data['price'] <= 0) {
            $error = 'Please fill in all required fields.';
        } else {
            if ($action === 'add') {
                $stmt = $pdo->prepare("
                    INSERT INTO service (provider_id, category_id, title, description, duration_minutes, price)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$providerId, $data['category_id'], $data['title'], $data['description'], $data['duration_minutes'], $data['price']]);
                $success = 'Service added successfully!';
            } else {
                // Verify ownership
                $stmt = $pdo->prepare("SELECT id FROM service WHERE id = ? AND provider_id = ?");
                $stmt->execute([$serviceId, $providerId]);
                if ($stmt->fetch()) {
                    $stmt = $pdo->prepare("
                        UPDATE service SET category_id = ?, title = ?, description = ?, duration_minutes = ?, price = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$data['category_id'], $data['title'], $data['description'], $data['duration_minutes'], $data['price'], $serviceId]);
                    $success = 'Service updated successfully!';
                }
            }
        }
    }
    
    if ($action === 'delete') {
        $serviceId = intval($_POST['service_id']);
        $stmt = $pdo->prepare("DELETE FROM service WHERE id = ? AND provider_id = ?");
        $stmt->execute([$serviceId, $providerId]);
        $success = 'Service deleted.';
    }
    
    if ($action === 'toggle') {
        $serviceId = intval($_POST['service_id']);
        $stmt = $pdo->prepare("UPDATE service SET is_active = NOT is_active WHERE id = ? AND provider_id = ?");
        $stmt->execute([$serviceId, $providerId]);
        $success = 'Service status updated.';
    }
}

// Get services
$services = getProviderServices($providerId);
$categories = getCategories();

// Check if editing
$editService = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM service WHERE id = ? AND provider_id = ?");
    $stmt->execute([$editId, $providerId]);
    $editService = $stmt->fetch();
}

$pageTitle = 'Manage Services';
include __DIR__ . '/includes/Header.php';
?>

<div class="manage-page">
    <div class="page-header">
        <h1>Manage Services</h1>
        <a href="ProviderDashboard.php" class="btn btn-outline">Back to Dashboard</a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="manage-grid">
        <div class="manage-form-section">
            <h2><?php echo $editService ? 'Edit Service' : 'Add New Service'; ?></h2>
            
            <form method="POST" class="manage-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="<?php echo $editService ? 'edit' : 'add'; ?>">
                <?php if ($editService): ?>
                    <input type="hidden" name="service_id" value="<?php echo $editService['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="title">Service Title *</label>
                    <input type="text" id="title" name="title" required
                           value="<?php echo $editService ? sanitize($editService['title']) : ''; ?>"
                           placeholder="e.g., Calculus Tutoring">
                </div>
                
                <div class="form-group">
                    <label for="category_id">Category *</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" 
                                    <?php echo ($editService && $editService['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo sanitize($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"
                              placeholder="Describe what you'll cover in this tutoring session..."><?php echo $editService ? sanitize($editService['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="duration_minutes">Duration (minutes) *</label>
                        <select id="duration_minutes" name="duration_minutes" required>
                            <option value="30" <?php echo ($editService && $editService['duration_minutes'] == 30) ? 'selected' : ''; ?>>30 minutes</option>
                            <option value="45" <?php echo ($editService && $editService['duration_minutes'] == 45) ? 'selected' : ''; ?>>45 minutes</option>
                            <option value="60" <?php echo (!$editService || $editService['duration_minutes'] == 60) ? 'selected' : ''; ?>>60 minutes</option>
                            <option value="90" <?php echo ($editService && $editService['duration_minutes'] == 90) ? 'selected' : ''; ?>>90 minutes</option>
                            <option value="120" <?php echo ($editService && $editService['duration_minutes'] == 120) ? 'selected' : ''; ?>>120 minutes</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price ($) *</label>
                        <input type="number" id="price" name="price" step="0.01" min="1" required
                               value="<?php echo $editService ? $editService['price'] : ''; ?>"
                               placeholder="25.00">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $editService ? 'Update Service' : 'Add Service'; ?>
                    </button>
                    <?php if ($editService): ?>
                        <a href="ManageServices.php" class="btn btn-outline">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <div class="manage-list-section">
            <h2>Your Services</h2>
            
            <?php if (empty($services)): ?>
                <div class="empty-state">
                    <p>You haven't added any services yet.</p>
                    <p>Use the form to add your first service!</p>
                </div>
            <?php else: ?>
                <div class="service-manage-list">
                    <?php foreach ($services as $service): ?>
                        <div class="service-manage-item <?php echo !$service['is_active'] ? 'inactive' : ''; ?>">
                            <div class="service-manage-info">
                                <h3><?php echo sanitize($service['title']); ?></h3>
                                <p><?php echo sanitize($service['category_name']); ?> - <?php echo $service['duration_minutes']; ?> min</p>
                                <span class="service-price"><?php echo formatCurrency($service['price']); ?></span>
                                <?php if (!$service['is_active']): ?>
                                    <span class="badge badge-secondary">Inactive</span>
                                <?php endif; ?>
                            </div>
                            <div class="service-manage-actions">
                                <a href="?edit=<?php echo $service['id']; ?>" class="btn btn-outline btn-sm">Edit</a>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                    <button type="submit" class="btn btn-outline btn-sm">
                                        <?php echo $service['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this service?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/Footer.php'; ?>