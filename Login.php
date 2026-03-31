<?php
/**
 * Login Page
 */
require_once __DIR__ . '/includes/Functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = getUserRole();
    if ($role === 'admin') {
        header('Location: AdminPanel.php');
    } elseif ($role === 'provider') {
        header('Location: ProviderDashboard.php');
    } else {
        header('Location: UserDashboard.php');
    }
    exit;
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address.';
    } else {
        $result = loginUser($email, $password);
        
        if ($result['success']) {
            $role = $result['user']['role_name'];
            if ($role === 'admin') {
                header('Location: AdminPanel.php');
            } elseif ($role === 'provider') {
                header('Location: ProviderDashboard.php');
            } else {
                header('Location: UserDashboard.php');
            }
            exit;
        } else {
            $error = $result['error'];
        }
    }
}

$pageTitle = 'Login';
include __DIR__ . '/includes/Header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <h1>Welcome Back</h1>
        <p class="auth-subtitle">Sign in to your account</p>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo sanitize($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="Login.php" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo sanitize($_POST['email'] ?? ''); ?>"
                       placeholder="Enter your email">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required
                       placeholder="Enter your password">
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
        </form>
        
        <div class="auth-footer">
            <p>Don't have an account? <a href="Signup.php">Sign up</a></p>
        </div>
        
        <div class="demo-credentials">
            <h4>Demo Accounts</h4>
            <p><strong>Admin:</strong> admin@peertutoring.com</p>
            <p><strong>Provider:</strong> john.tutor@peertutoring.com</p>
            <p><strong>User:</strong> alice.student@peertutoring.com</p>
            <p><em>Password for all: password</em></p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/Footer.php'; ?>
