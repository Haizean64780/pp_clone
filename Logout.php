<?php
/**
 * Logout Page
 */
require_once __DIR__ . '/includes/Auth.php';

logoutUser();

header('Location: index.php');
exit;
