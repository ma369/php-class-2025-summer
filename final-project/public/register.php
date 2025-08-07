<?php
require_once '../config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ../admin/dashboard.php');
    exit();
}

class RegisterController {
    private $pdo;
    private $uploadDir = '../uploads/';
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->ensureUploadDir();
    }
    
    private function ensureUploadDir() {
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    public function validateInput($data) {
        $errors = [];
        
        if (empty(trim($data['username'] ?? ''))) {
            $errors[] = 'Username is required.';
        } elseif (strlen(trim($data['username'])) < 3) {
            $errors[] = 'Username must be at least 3 characters.';
        }
        
        if (empty(trim($data['email'] ?? ''))) {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        if (empty($data['password'] ?? '')) {
            $errors[] = 'Password is required.';
        } elseif (strlen($data['password']) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }
        
        if (($data['password'] ?? '') !== ($data['confirm_password'] ?? '')) {
            $errors[] = 'Passwords do not match.';
        }
        
        return $errors;
    }
    
    public function checkUniqueFields($username, $email) {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            return $stmt->fetch() === false;
        } catch (PDOException $e) {
            error_log("Check unique fields error: " . $e->getMessage());
            return false;
        }
    }
    
    public function handleImageUpload($file) {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        // Validate file
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('Please upload a valid image file (JPEG, PNG, GIF, or WebP).');
        }
        
        if ($file['size'] > $maxSize) {
            throw new Exception('Image file is too large. Maximum size is 5MB.');
        }
        
        // Upload file
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . uniqid() . '.' . strtolower($extension);
        $uploadPath = $this->uploadDir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to upload image. Please try again.');
        }
        
        return $filename;
    }
    
    public function createUser($data, $files) {
        $username = trim($data['username']);
        $email = trim($data['email']);
        $password = $data['password'];
        
        if (!$this->checkUniqueFields($username, $email)) {
            throw new Exception('Username or email already exists.');
        }
        
        // Handle profile image
        $profileImage = null;
        if (isset($files['profile_image'])) {
            $profileImage = $this->handleImageUpload($files['profile_image']);
        }
        
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, email, password, profile_image, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            if (!$stmt->execute([$username, $email, $hashedPassword, $profileImage])) {
                throw new Exception('Registration failed. Please try again.');
            }
            
            return true;
            
        } catch (PDOException $e) {
            // Clean up uploaded file if user creation failed
            if ($profileImage && file_exists($this->uploadDir . $profileImage)) {
                unlink($this->uploadDir . $profileImage);
            }
            
            error_log("Create user error: " . $e->getMessage());
            throw new Exception('Registration failed. Please try again.');
        }
    }
}

$error = '';
$registerController = new RegisterController($pdo);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate input
        $errors = $registerController->validateInput($_POST);
        if (!empty($errors)) {
            $error = implode('<br>', $errors);
        } else {
            // Create user
            $registerController->createUser($_POST, $_FILES);
            $_SESSION['success_message'] = 'Registration successful! You can now login.';
            header('Location: login.php');
            exit();
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$page_title = 'Register';
$page_description = 'Join our community of writers and readers. Create your account to start sharing your ideas.';
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <!-- Registration Card -->
            <div class="card shadow">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
                        <h2 class="h4 mb-2">Join Our Community</h2>
                        <p class="text-muted mb-0">Create your account to start writing</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" novalidate>
                        <!-- Profile Image -->
                        <div class="text-center mb-4">
                            <div class="profile-upload position-relative d-inline-block">
                                <img id="profilePreview" 
                                     src="data:image/svg+xml,%3csvg width='100' height='100' xmlns='http://www.w3.org/2000/svg'%3e%3crect width='100' height='100' fill='%23f8f9fa'/%3e%3ctext x='50%25' y='50%25' font-size='32' fill='%236c757d' text-anchor='middle' dy='.3em'%3e%3ci class='fas fa-user'%3e%3c/i%3e%3c/text%3e%3c/svg%3e"
                                     class="rounded-circle border shadow-sm" 
                                     style="width: 100px; height: 100px; object-fit: cover; cursor: pointer;"
                                     onclick="document.getElementById('profile_image').click()">
                                <div class="position-absolute bottom-0 end-0">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                         style="width: 30px; height: 30px; cursor: pointer;">
                                        <i class="fas fa-camera fa-sm"></i>
                                    </div>
                                </div>
                            </div>
                            <input type="file" 
                                   id="profile_image" 
                                   name="profile_image" 
                                   class="d-none" 
                                   accept="image/*" 
                                   onchange="previewImage(this)">
                            <div class="form-text mt-2">Optional profile photo (Max 5MB)</div>
                        </div>
                        
                        <!-- Form Fields -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">
                                        <i class="fas fa-user me-1"></i>Username
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="username" 
                                           name="username" 
                                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                                           placeholder="Choose username" 
                                           required 
                                           minlength="3"
                                           autocomplete="username">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-1"></i>Email
                                    </label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                           placeholder="Enter your email" 
                                           required
                                           autocomplete="email">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock me-1"></i>Password
                                    </label>
                                    <div class="input-group">
                                        <input type="password" 
                                               class="form-control" 
                                               id="password" 
                                               name="password" 
                                               placeholder="Min 6 characters" 
                                               required 
                                               minlength="6"
                                               autocomplete="new-password">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label">
                                        <i class="fas fa-lock me-1"></i>Confirm
                                    </label>
                                    <div class="input-group">
                                        <input type="password" 
                                               class="form-control" 
                                               id="confirm_password" 
                                               name="confirm_password" 
                                               placeholder="Repeat password" 
                                               required
                                               autocomplete="new-password">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </button>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="terms" required>
                            <label class="form-check-label small" for="terms">
                                I agree to the <a href="#" class="text-decoration-none">Terms of Service</a> and 
                                <a href="#" class="text-decoration-none">Privacy Policy</a>
                            </label>
                        </div>
                    </form>
                    
                    <div class="text-center">
                        <p class="mb-0">Already have an account? 
                            <a href="login.php" class="text-decoration-none fw-semibold">Sign in</a>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Features -->
            <div class="card mt-3 bg-light">
                <div class="card-body p-3">
                    <h6 class="text-center mb-3">Why Join BlogPlatform?</h6>
                    <div class="row text-center">
                        <div class="col-4">
                            <i class="fas fa-edit text-primary mb-1"></i>
                            <div class="small">Write & Share</div>
                        </div>
                        <div class="col-4">
                            <i class="fas fa-users text-success mb-1"></i>
                            <div class="small">Join Community</div>
                        </div>
                        <div class="col-4">
                            <i class="fas fa-comments text-info mb-1"></i>
                            <div class="small">Engage & Discuss</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profilePreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        button.className = 'fas fa-eye-slash';
    } else {
        field.type = 'password';
        button.className = 'fas fa-eye';
    }
}

// Real-time validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const username = document.getElementById('username');
    const email = document.getElementById('email');
    
    // Password match validation
    function validatePasswordMatch() {
        if (confirmPassword.value && password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match');
            confirmPassword.classList.add('is-invalid');
        } else {
            confirmPassword.setCustomValidity('');
            confirmPassword.classList.remove('is-invalid');
        }
    }
    
    // Username validation
    function validateUsername() {
        if (username.value.length > 0 && username.value.length < 3) {
            username.setCustomValidity('Username must be at least 3 characters');
            username.classList.add('is-invalid');
        } else {
            username.setCustomValidity('');
            username.classList.remove('is-invalid');
            username.classList.add('is-valid');
        }
    }
    
    // Email validation
    function validateEmail() {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email.value && !emailRegex.test(email.value)) {
            email.setCustomValidity('Please enter a valid email address');
            email.classList.add('is-invalid');
        } else if (email.value) {
            email.setCustomValidity('');
            email.classList.remove('is-invalid');
            email.classList.add('is-valid');
        }
    }
    
    // Password strength indicator
    function validatePassword() {
        const value = password.value;
        if (value.length >= 6) {
            password.classList.remove('is-invalid');
            password.classList.add('is-valid');
        } else if (value.length > 0) {
            password.classList.add('is-invalid');
        }
    }
    
    // Event listeners
    password.addEventListener('input', function() {
        validatePassword();
        validatePasswordMatch();
    });
    
    confirmPassword.addEventListener('input', validatePasswordMatch);
    username.addEventListener('input', validateUsername);
    email.addEventListener('input', validateEmail);
    
    // Form submission
    form.addEventListener('submit', function(e) {
        // Final validation
        validateUsername();
        validateEmail();
        validatePassword();
        validatePasswordMatch();
        
        const isValid = form.checkValidity();
        
        if (isValid) {
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Account...';
            submitBtn.disabled = true;
        } else {
            e.preventDefault();
            e.stopPropagation();
        }
        
        form.classList.add('was-validated');
    });
    
    // Auto-focus first field
    username.focus();
});
</script>

<?php include '../includes/footer.php'; ?>