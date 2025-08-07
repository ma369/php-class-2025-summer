<?php
require_once '../config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ../admin/dashboard.php');
    exit();
}

class LoginController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function authenticate($username, $password) {
        if (empty($username) || empty($password)) {
            throw new Exception('Please enter both username/email and password.');
        }
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, username, password, role 
                FROM users 
                WHERE username = ? OR email = ?
            ");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($password, $user['password'])) {
                throw new Exception('Invalid username/email or password.');
            }
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            throw new Exception('Login system temporarily unavailable. Please try again later.');
        }
    }
}

$error = '';
$loginController = new LoginController($pdo);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        $loginController->authenticate($username, $password);
        
        $_SESSION['success_message'] = 'Welcome back, ' . htmlspecialchars($_SESSION['username']) . '!';
        
        // Redirect to intended page or dashboard
        $redirectTo = $_GET['redirect'] ?? '../admin/dashboard.php';
        $redirectTo = filter_var($redirectTo, FILTER_SANITIZE_URL);
        header('Location: ' . $redirectTo);
        exit();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$page_title = 'Login';
$page_description = 'Login to your account to access your dashboard and start writing.';
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <!-- Login Card -->
            <div class="card shadow">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="fas fa-sign-in-alt fa-3x text-primary mb-3"></i>
                        <h2 class="h4 mb-2">Welcome Back</h2>
                        <p class="text-muted mb-0">Sign in to your account</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" novalidate>
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="fas fa-user me-1"></i>Username or Email
                            </label>
                            <input type="text" 
                                   class="form-control form-control-lg" 
                                   id="username" 
                                   name="username" 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                                   placeholder="Enter your username or email" 
                                   required 
                                   autocomplete="username">
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-1"></i>Password
                            </label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control form-control-lg" 
                                       id="password" 
                                       name="password" 
                                       placeholder="Enter your password" 
                                       required 
                                       autocomplete="current-password">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                    <i class="fas fa-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center">
                        <p class="mb-0">Don't have an account? 
                            <a href="register.php" class="text-decoration-none fw-semibold">Create one</a>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Demo Credentials -->
            <div class="card mt-3 bg-light">
                <div class="card-body p-3">
                    <h6 class="card-title text-center mb-3">
                        <i class="fas fa-info-circle me-1"></i>Demo Credentials
                    </h6>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end pe-3">
                                <div class="fw-semibold small text-danger">Admin Account</div>
                                <div class="small">Username: <code>admin</code></div>
                                <div class="small">Password: <code>admin123</code></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="fw-semibold small text-primary">User Account</div>
                            <div class="small">Username: <code>john_writer</code></div>
                            <div class="small">Password: <code>admin123</code></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="text-center mt-4">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-info btn-sm" onclick="quickLogin('admin', 'admin123')">
                        <i class="fas fa-crown me-1"></i>Demo Admin
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="quickLogin('john_writer', 'admin123')">
                        <i class="fas fa-user me-1"></i>Demo User
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.className = 'fas fa-eye-slash';
    } else {
        passwordInput.type = 'password';
        toggleIcon.className = 'fas fa-eye';
    }
}

function quickLogin(username, password) {
    document.getElementById('username').value = username;
    document.getElementById('password').value = password;
    
    // Add visual feedback
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Logging in...';
    button.disabled = true;
    
    setTimeout(() => {
        document.querySelector('form').submit();
    }, 500);
}

// Auto-focus username field
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('username').focus();
});

// Form validation feedback
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    
    form.addEventListener('submit', function(e) {
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        
        if (!username || !password) {
            e.preventDefault();
            
            if (!username) {
                document.getElementById('username').classList.add('is-invalid');
            }
            if (!password) {
                document.getElementById('password').classList.add('is-invalid');
            }
            
            return false;
        }
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Signing In...';
        submitBtn.disabled = true;
    });
    
    // Remove validation errors on input
    ['username', 'password'].forEach(fieldName => {
        document.getElementById(fieldName).addEventListener('input', function() {
            this.classList.remove('is-invalid');
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>