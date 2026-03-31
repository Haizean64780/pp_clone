<?php
/**
 * Signup Page
 */
require_once __DIR__ . '/includes/Functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: UserDashboard.php');
    exit;
}

$error = '';
$success = '';
$isProvider = isset($_GET['role']) && $_GET['role'] === 'provider';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'email' => sanitize($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'first_name' => sanitize($_POST['first_name'] ?? ''),
        'last_name' => sanitize($_POST['last_name'] ?? ''),
        'phone' => sanitize($_POST['phone'] ?? ''),
        'location' => sanitize($_POST['location'] ?? ''),
        'role' => $_POST['role'] ?? 'user'
    ];
    
    // Validate CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } elseif (empty($data['email']) || empty($data['password']) || empty($data['first_name']) || empty($data['last_name'])) {
        $error = 'Please fill in all required fields.';
    } elseif (!isValidEmail($data['email'])) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($data['password']) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($data['password'] !== $data['confirm_password']) {
        $error = 'Passwords do not match.';
    } else {
        $result = registerUser($data);
        
        if ($result['success']) {
            // Auto-login after registration
            $loginResult = loginUser($data['email'], $data['password']);
            if ($loginResult['success']) {
                if ($data['role'] === 'provider') {
                    header('Location: ProviderDashboard.php?welcome=1');
                } else {
                    header('Location: UserDashboard.php?welcome=1');
                }
                exit;
            }
            $success = 'Registration successful! You can now login.';
        } else {
            $error = $result['error'];
        }
    }
}

$pageTitle = 'Sign Up';
include __DIR__ . '/includes/Header.php';
?>

<div class="auth-page">
    <div class="auth-card auth-card-wide">
        <h1><?php echo $isProvider ? 'Become a Tutor' : 'Create Account'; ?></h1>
        <p class="auth-subtitle"><?php echo $isProvider ? 'Share your knowledge and earn money' : 'Join our peer tutoring community'; ?></p>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo sanitize($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo sanitize($success); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="Signup.php" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" required
                           value="<?php echo sanitize($_POST['first_name'] ?? ''); ?>"
                           placeholder="Enter your first name">
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" required
                           value="<?php echo sanitize($_POST['last_name'] ?? ''); ?>"
                           placeholder="Enter your last name">
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo sanitize($_POST['email'] ?? ''); ?>"
                       placeholder="Enter your email">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone"
                           value="<?php echo sanitize($_POST['phone'] ?? ''); ?>"
                           placeholder="Enter your phone number">
                </div>
                
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location"
                           value="<?php echo sanitize($_POST['location'] ?? ''); ?>"
                           placeholder="e.g., Library, Science Building">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required
                           placeholder="Minimum 6 characters">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                           placeholder="Confirm your password">
                </div>
            </div>
            
            <div class="form-group">
                <label>Account Type *</label>
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="role" value="user" <?php echo (!$isProvider) ? 'checked' : ''; ?>>
                        <span class="radio-text">
                            <strong>Student</strong>
                            <small>I want to book tutoring sessions</small>
                        </span>
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="role" value="provider" <?php echo ($isProvider) ? 'checked' : ''; ?>>
                        <span class="radio-text">
                            <strong>Tutor</strong>
                            <small>I want to offer tutoring services</small>
                        </span>
                    </label>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Create Account</button>
        </form>
        
        <div class="auth-footer">
            <p>Already have an account? <a href="Login.php">Sign in</a></p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/Footer.php'; ?>





<!-- <?php
/**
 * Signup Page
 */
require_once __DIR__ . '/includes/Functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: UserDashboard.php');
    exit;
}

$error = '';
$success = '';
$isProvider = isset($_GET['role']) && $_GET['role'] === 'provider';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'email' => sanitize($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'first_name' => sanitize($_POST['first_name'] ?? ''),
        'last_name' => sanitize($_POST['last_name'] ?? ''),
        'phone' => sanitize($_POST['phone'] ?? ''),
        'location' => sanitize($_POST['location'] ?? ''),
        'role' => $_POST['role'] ?? 'user'
    ];
    
    // Validate CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } elseif (empty($data['email']) || empty($data['password']) || empty($data['first_name']) || empty($data['last_name'])) {
        $error = 'Please fill in all required fields.';
    } elseif (!isValidEmail($data['email'])) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($data['password']) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($data['password'] !== $data['confirm_password']) {
        $error = 'Passwords do not match.';
    } else {
        $result = registerUser($data);
        
        if ($result['success']) {
            // Auto-login after registration
            $loginResult = loginUser($data['email'], $data['password']);
            if ($loginResult['success']) {
                if ($data['role'] === 'provider') {
                    header('Location: ProviderDashboard.php?welcome=1');
                } else {
                    header('Location: UserDashboard.php?welcome=1');
                }
                exit;
            }
            $success = 'Registration successful! You can now login.';
        } else {
            $error = $result['error'];
        }
    }
}

$pageTitle = 'Sign Up';
include __DIR__ . '/includes/Header.php';
?>
<--
<div class="auth-page">
    <div class="auth-card auth-card-wide">
        <h1>6?php echo $isProvider ? 'Become a Tutor' : 'Create Account'; ?></h1>
        <p class="auth-subtitle">6?php echo $isProvider ? 'Share your knowledge and earn money' : 'Join our peer tutoring community'; ?></p>
        
        6?php if ($error): ?>
            <div class="alert alert-error">6?php echo sanitize($error); ?></div>
        6?php endif; ?>
        
        6?php if ($success): ?>
            <div class="alert alert-success">6?php echo sanitize($success); ?></div>
        6?php endif; ?>
        
        <form method="POST" action="Signup.php" class="auth-form">
            <input type="hidden" name="csrf_token" value="6?php echo generateCsrfToken(); ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" required
                           value="6?php echo sanitize($_POST['first_name'] ?? ''); ?>"
                           placeholder="Enter your first name">
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" required
                           value="6?php echo sanitize($_POST['last_name'] ?? ''); ?>"
                           placeholder="Enter your last name">
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" required
                       value="6?php echo sanitize($_POST['email'] ?? ''); ?>"
                       placeholder="Enter your email">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone"
                           value="6?php echo sanitize($_POST['phone'] ?? ''); ?>"
                           placeholder="Enter your phone number">
                </div>
                
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location"
                           value="6?php echo sanitize($_POST['location'] ?? ''); ?>"
                           placeholder="e.g., Library, Science Building">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required
                           placeholder="Minimum 6 characters">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                           placeholder="Confirm your password">
                </div>
            </div>
            
            <div class="form-group">
                <label>Account Type *</label>
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="role" value="user" 6?php echo (!$isProvider) ? 'checked' : ''; ?>>
                        <span class="radio-text">
                            <strong>Student</strong>
                            <small>I want to book tutoring sessions</small>
                        </span>
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="role" value="provider" 6?php echo ($isProvider) ? 'checked' : ''; ?>>
                        <span class="radio-text">
                            <strong>Tutor</strong>
                            <small>I want to offer tutoring services</small>
                        </span>
                    </label>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Create Account</button>
        </form>
        
        <div class="auth-footer">
            <p>Already have an account? <a href="Login.php">Sign in</a></p>
        </div>
    </div>
</div>

6?php include __DIR__ . '/includes/Footer.php'; ?>
 -->