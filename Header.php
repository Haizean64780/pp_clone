<?php
/**
 * Header Template
 */
require_once __DIR__ . '/Functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? sanitize($pageTitle) . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="styles/Main.css">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">
                    <span class="logo-icon">&#128218;</span>
                    <span class="logo-text"><?php echo SITE_NAME; ?></span>
                </a>
                
                <nav class="main-nav">
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="ServiceCatalog.php">Services</a></li>
                        <?php if (isLoggedIn()): ?>
                            <?php if (isAdmin()): ?>
                                <li><a href="AdminPanel.php">Admin Panel</a></li>
                            <?php elseif (isProvider()): ?>
                                <li><a href="ProviderDashboard.php">Dashboard</a></li>
                            <?php else: ?>
                                <li><a href="UserDashboard.php">Dashboard</a></li>
                            <?php endif; ?>
                            <li><a href="MyBookings.php">My Bookings</a></li>
                            <li class="notification-link">
                                <a href="NotificationsCenter.php">
                                    Notifications
                                    <?php 
                                    $unreadCount = getUnreadNotificationCount(getCurrentUserId());
                                    if ($unreadCount > 0): 
                                    ?>
                                        <span class="notification-badge"><?php echo $unreadCount; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                
                <div class="header-actions">
                    <?php if (isLoggedIn()): ?>
                        <div class="user-menu">
                            <span class="user-name"><?php echo sanitize($_SESSION['user_name']); ?></span>
                            <a href="Logout.php" class="btn btn-outline">Logout</a>
                        </div>
                    <?php else: ?>
                        <a href="Login.php" class="btn btn-outline">Login</a>
                        <a href="Signup.php" class="btn btn-primary">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    
    <main class="main-content">
        <div class="container">
            <?php displayFlashMessage(); ?>
