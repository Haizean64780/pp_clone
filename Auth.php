<?php
/**
 * Authentication Helper Functions
 */

require_once __DIR__ . '/Config.php';

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("
        SELECT u.*, r.name as role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.id = ?
    ");
    $stmt->execute([getCurrentUserId()]);
    return $stmt->fetch();
}

/**
 * Get user role name
 */
function getUserRole() {
    return $_SESSION['user_role'] ?? 'visitor';
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    return getUserRole() === $role;
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Check if user is provider
 */
function isProvider() {
    return hasRole('provider');
}

/**
 * Check if user is regular user
 */
function isUser() {
    return hasRole('user');
}

/**
 * Login user
 */
function loginUser($email, $password) {
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare("
        SELECT u.*, r.name as role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.email = ? AND u.is_active = 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_role'] = $user['role_name'];
        
        return ['success' => true, 'user' => $user];
    }
    
    return ['success' => false, 'error' => 'Invalid email or password'];
}

/**
 * Logout user
 */
function logoutUser() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Register new user
 */
function registerUser($data) {
    $pdo = getDbConnection();
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Email already registered'];
    }
    
    // Get role ID
    $roleId = 3; // Default to 'user' role
    if (isset($data['role']) && $data['role'] === 'provider') {
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'provider'");
        $stmt->execute();
        $role = $stmt->fetch();
        if ($role) {
            $roleId = $role['id'];
        }
    }
    
    // Hash password
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (email, password, first_name, last_name, phone, location, role_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['email'],
            $hashedPassword,
            $data['first_name'],
            $data['last_name'],
            $data['phone'] ?? null,
            $data['location'] ?? null,
            $roleId
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // If registering as provider, create provider profile
        if ($roleId == 2 || (isset($data['role']) && $data['role'] === 'provider')) {
            $stmt = $pdo->prepare("
                INSERT INTO providerprofile (user_id, bio, hourly_rate)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$userId, '', 25.00]);
        }
        
        return ['success' => true, 'user_id' => $userId];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Registration failed: ' . $e->getMessage()];
    }
}

/**
 * Require login - redirect to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: Login.php');
        exit;
    }
}

/**
 * Require specific role
 */
function requireRole($role) {
    requireLogin();
    if (!hasRole($role) && !isAdmin()) {
        header('Location: index.php?error=unauthorized');
        exit;
    }
}

/**
 * Require admin role
 */
function requireAdmin() {
    requireRole('admin');
}

/**
 * Require provider role
 */
function requireProvider() {
    requireLogin();
    if (!isProvider() && !isAdmin()) {
        header('Location: index.php?error=unauthorized');
        exit;
    }
}

/**
 * Get provider profile for current user
 */
function getProviderProfile($userId = null) {
    $userId = $userId ?? getCurrentUserId();
    if (!$userId) return null;
    
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT * FROM providerprofile WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}




<-- -->