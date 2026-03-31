<?php
/**
 * Booking Card Component
 * Used in MyBookings.php
 * Expects $apt variable with appointment data
 */
?>
<div class="booking-card">
    <div class="booking-card-header">
        <div class="booking-info">
            <h3><?php echo sanitize($apt['service_title']); ?></h3>
            <p class="booking-with">
                <?php if ($isProviderView): ?>
                    Client: <?php echo sanitize($apt['client_first_name'] . ' ' . $apt['client_last_name']); ?>
                <?php else: ?>
                    Tutor: <?php echo sanitize($apt['provider_first_name'] . ' ' . $apt['provider_last_name']); ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="booking-status">
            <?php echo getStatusBadge($apt['status']); ?>
        </div>
    </div>
    
    <div class="booking-card-body">
        <div class="booking-details">
            <div class="detail-item">
                <span class="detail-icon">&#128197;</span>
                <span><?php echo formatDate($apt['appointment_date']); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-icon">&#128337;</span>
                <span><?php echo formatTime($apt['start_time']); ?> - <?php echo formatTime($apt['end_time']); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-icon">&#128176;</span>
                <span><?php echo formatCurrency($apt['total_price']); ?></span>
            </div>
        </div>
        
        <?php if (!empty($apt['client_notes'])): ?>
            <div class="booking-notes">
                <strong>Notes:</strong> <?php echo sanitize($apt['client_notes']); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="booking-card-actions">
        <?php if ($apt['status'] === 'pending' || $apt['status'] === 'confirmed'): ?>
            <?php if (strtotime($apt['appointment_date']) >= strtotime('today')): ?>
                <button type="button" class="btn btn-outline btn-sm" onclick="showCancelModal(<?php echo $apt['id']; ?>)">
                    Cancel
                </button>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($apt['status'] === 'completed' && !$isProviderView): ?>
            <?php
            // Check if review exists
            $reviewStmt = $pdo->prepare("SELECT id FROM review WHERE appointment_id = ?");
            $reviewStmt->execute([$apt['id']]);
            $hasReview = $reviewStmt->fetch();
            ?>
            <?php if (!$hasReview): ?>
                <button type="button" class="btn btn-primary btn-sm" onclick="showReviewModal(<?php echo $apt['id']; ?>)">
                    Leave Review
                </button>
            <?php else: ?>
                <span class="text-muted">Reviewed</span>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if (!$isProviderView): ?>
            <a href="ProviderProfile.php?id=<?php echo $apt['provider_id']; ?>" class="btn btn-outline btn-sm">View Tutor</a>
        <?php endif; ?>
    </div>
</div>