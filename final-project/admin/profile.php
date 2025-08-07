<?php
require_once '../config.php';
requireLogin();

$error = '';
$success = '';

// Get current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error_message'] = 'User not found.';
    header('Location: ../public/index.php');
    exit();
}

// Simple image upload function
function uploadProfileImage($file, $oldImage = null) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return $oldImage;
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Please upload a valid image file (JPEG, PNG, or GIF).');
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception('Image file is too large. Maximum size is 5MB.');
    }
    
    // Delete old image
    if ($oldImage && file_exists('../uploads/' . $oldImage)) {
        unlink('../uploads/' . $oldImage);
    }
    
    // Create uploads directory
    if (!file_exists('../uploads')) {
        mkdir('../uploads', 0777, true);
    }
    
    // Upload new image
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . uniqid() . '.' . $extension;
    $uploadPath = '../uploads/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Failed to upload image.');
    }
    
    return $filename;
}

// Get user activity stats
function getUserActivity($pdo, $userId) {
    $stats = [];
    
    // Post stats
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE author_id = ?");
    $stmt->execute([$userId]);
    $stats['total_posts'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE author_id = ? AND status = 'published'");
    $stmt->execute([$userId]);
    $stats['published_posts'] = (int)$stmt->fetchColumn();
    
    // Comment stats
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE author_id = ?");
    $stmt->execute([$userId]);
    $stats['comments_made'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM comments c 
        JOIN posts p ON c.post_id = p.id 
        WHERE p.author_id = ? AND c.status = 'approved'
    ");
    $stmt->execute([$userId]);
    $stats['comments_received'] = (int)$stmt->fetchColumn();
    
    return $stats;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    try {
        // Validation
        if (empty($username) || empty($email)) {
            throw new Exception('Username and email are required.');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address.');
        }
        
        // Check if username/email already exists (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            throw new Exception('Username or email already exists.');
        }
        
        // Handle password update
        $hashedPassword = $user['password'];
        if (!empty($newPassword)) {
            if (empty($currentPassword)) {
                throw new Exception('Current password is required to set a new password.');
            }
            
            if (!password_verify($currentPassword, $user['password'])) {
                throw new Exception('Current password is incorrect.');
            }
            
            if (strlen($newPassword) < 6) {
                throw new Exception('New password must be at least 6 characters long.');
            }
            
            if ($newPassword !== $confirmPassword) {
                throw new Exception('New passwords do not match.');
            }
            
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        }
        
        // Handle profile image
        $profileImage = $user['profile_image'];
        
        if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
            // Remove existing image
            if ($profileImage && file_exists('../uploads/' . $profileImage)) {
                unlink('../uploads/' . $profileImage);
            }
            $profileImage = null;
        } elseif (isset($_FILES['profile_image'])) {
            // Upload new image
            $profileImage = uploadProfileImage($_FILES['profile_image'], $profileImage);
        }
        
        // Update user profile
        $stmt = $pdo->prepare("
            UPDATE users 
            SET username = ?, email = ?, password = ?, profile_image = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        if ($stmt->execute([$username, $email, $hashedPassword, $profileImage, $_SESSION['user_id']])) {
            $_SESSION['username'] = $username;
            $_SESSION['success_message'] = 'Profile updated successfully!';
            header('Location: profile.php');
            exit();
        } else {
            throw new Exception('Failed to update profile.');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get user activity stats
$userActivity = getUserActivity($pdo, $_SESSION['user_id']);

$page_title = 'My Profile';
include '../includes/header.php';
?>

<div class="container mt-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">
                    <i class="fas fa-user-edit me-2 text-primary"></i>My Profile
                </h1>
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <!-- Profile Image -->
                            <div class="col-md-4 text-center mb-4">
                                <div class="profile-section">
                                    <?php if ($user['profile_image']): ?>
                                        <img src="../uploads/<?php echo htmlspecialchars($user['profile_image']); ?>" 
                                             alt="Profile Image" 
                                             class="rounded-circle border shadow mb-3"
                                             style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="remove_image">
                                            <label class="form-check-label text-danger" for="remove_image">
                                                <i class="fas fa-trash me-1"></i>Remove current photo
                                            </label>
                                        </div>
                                    <?php else: ?>
                                        <div class="rounded-circle border shadow bg-light d-flex align-items-center justify-content-center mb-3"
                                             style="width: 150px; height: 150px; margin: 0 auto;">
                                            <i class="fas fa-user fa-3x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <label for="profile_image" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-camera me-1"></i>
                                            <?php echo $user['profile_image'] ? 'Change Photo' : 'Upload Photo'; ?>
                                        </label>
                                        <input type="file" 
                                               id="profile_image" 
                                               name="profile_image" 
                                               class="d-none" 
                                               accept="image/*" 
                                               onchange="previewImage(this)">
                                        <div class="form-text">Max 5MB. JPEG, PNG, or GIF</div>
                                    </div>
                                    
                                    <div id="imagePreview" style="display: none;">
                                        <img id="preview" src="" class="rounded-circle border shadow mb-2" 
                                             style="width: 150px; height: 150px; object-fit: cover;">
                                        <br>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removePreview()">
                                            <i class="fas fa-times me-1"></i>Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Form Fields -->
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="username" class="form-label fw-bold">
                                                <i class="fas fa-user me-1"></i>Username *
                                            </label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="username" 
                                                   name="username" 
                                                   value="<?php echo htmlspecialchars($user['username']); ?>" 
                                                   required>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label fw-bold">
                                                <i class="fas fa-envelope me-1"></i>Email Address *
                                            </label>
                                            <input type="email" 
                                                   class="form-control" 
                                                   id="email" 
                                                   name="email" 
                                                   value="<?php echo htmlspecialchars($user['email']); ?>" 
                                                   required>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Account Info -->
                                <div class="mb-4">
                                    <h6 class="fw-bold mb-3">Account Information</h6>
                                    <div class="bg-light p-3 rounded">
                                        <div class="row">
                                            <div class="col-sm-6 mb-2">
                                                <small class="text-muted d-block">Role:</small>
                                                <span class="badge bg-<?php echo $user['role'] == 'admin' ? 'danger' : 'primary'; ?>">
                                                    <i class="fas fa-<?php echo $user['role'] == 'admin' ? 'crown' : 'user'; ?> me-1"></i>
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </div>
                                            <div class="col-sm-6 mb-2">
                                                <small class="text-muted d-block">Member since:</small>
                                                <span><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Password Change Section -->
                        <hr class="my-4">
                        <h5 class="mb-3">
                            <i class="fas fa-lock me-2"></i>Change Password (Optional)
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="current_password" 
                                           name="current_password">
                                    <div class="form-text">Required only if changing password</div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="new_password" 
                                           name="new_password" 
                                           minlength="6">
                                    <div class="form-text">Minimum 6 characters</div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="confirm_password" 
                                           name="confirm_password">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between pt-3">
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Activity Statistics -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Your Activity
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 col-md-3 mb-3">
                            <div class="h4 text-primary"><?php echo $userActivity['total_posts']; ?></div>
                            <div class="small text-muted">Total Posts</div>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <div class="h4 text-success"><?php echo $userActivity['published_posts']; ?></div>
                            <div class="small text-muted">Published</div>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <div class="h4 text-info"><?php echo $userActivity['comments_made']; ?></div>
                            <div class="small text-muted">Comments Made</div>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <div class="h4 text-warning"><?php echo $userActivity['comments_received']; ?></div>
                            <div class="small text-muted">Comments Received</div>
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
            document.getElementById('preview').src = e.target.result;
            document.getElementById('imagePreview').style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function removePreview() {
    document.getElementById('imagePreview').style.display = 'none';
    document.getElementById('profile_image').value = '';
}

// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (confirmPassword && password !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
        this.classList.add('is-invalid');
    } else {
        this.setCustomValidity('');
        this.classList.remove('is-invalid');
    }
});

// Clear password fields validation on new password change
document.getElementById('new_password').addEventListener('input', function() {
    const confirmField = document.getElementById('confirm_password');
    if (confirmField.value) {
        confirmField.dispatchEvent(new Event('input'));
    }
});
</script>

<?php include '../includes/footer.php'; ?>