<?php
require_once '../config.php';
requireLogin();

$error = '';
$success = '';

// Get current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username) || empty($email)) {
        $error = 'Username and email are required.';
    } else {
        // Check if username or email already exists (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $_SESSION['user_id']]);
        
        if ($stmt->fetch()) {
            $error = 'Username or email already exists.';
        } else {
            $update_password = false;
            $hashed_password = $user['password']; // Keep current password by default
            
            // Check if password update is requested
            if (!empty($new_password)) {
                if (empty($current_password)) {
                    $error = 'Current password is required to set a new password.';
                } elseif (!password_verify($current_password, $user['password'])) {
                    $error = 'Current password is incorrect.';
                } elseif (strlen($new_password) < 6) {
                    $error = 'New password must be at least 6 characters long.';
                } elseif ($new_password !== $confirm_password) {
                    $error = 'New passwords do not match.';
                } else {
                    $update_password = true;
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                }
            }
            
            if (empty($error)) {
                $image_name = $user['profile_image']; // Keep existing image by default
                
                // Handle profile image upload
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $max_size = 5 * 1024 * 1024; // 5MB
                    
                    if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
                        $error = 'Please upload a valid image file (JPEG, PNG, or GIF).';
                    } elseif ($_FILES['profile_image']['size'] > $max_size) {
                        $error = 'Image file is too large. Maximum size is 5MB.';
                    } else {
                        // Delete old image if exists
                        if ($user['profile_image'] && file_exists('../uploads/' . $user['profile_image'])) {
                            unlink('../uploads/' . $user['profile_image']);
                        }
                        
                        // Create uploads directory if it doesn't exist
                        if (!file_exists('../uploads')) {
                            mkdir('../uploads', 0777, true);
                        }
                        
                        $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                        $image_name = 'profile_' . uniqid() . '.' . $file_extension;
                        $upload_path = '../uploads/' . $image_name;
                        
                        if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                            $error = 'Failed to upload image. Please try again.';
                        }
                    }
                }
                
                // Handle image removal
                if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
                    if ($user['profile_image'] && file_exists('../uploads/' . $user['profile_image'])) {
                        unlink('../uploads/' . $user['profile_image']);
                    }
                    $image_name = null;
                }
                
                if (empty($error)) {
                    // Update user profile
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ?, profile_image = ? WHERE id = ?");
                    
                    if ($stmt->execute([$username, $email, $hashed_password, $image_name, $_SESSION['user_id']])) {
                        $_SESSION['username'] = $username;
                        $success = 'Profile updated successfully!';
                        
                        // Refresh user data
                        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                        $user = $stmt->fetch();
                    } else {
                        $error = 'Failed to update profile. Please try again.';
                    }
                }
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">My Profile</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-4 text-center mb-4">
                            <?php if ($user['profile_image']): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($user['profile_image']); ?>" 
                                     alt="Profile Image" class="profile-avatar mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="remove_image">
                                    <label class="form-check-label" for="remove_image">
                                        Remove photo
                                    </label>
                                </div>
                            <?php else: ?>
                                <div class="profile-avatar bg-light d-flex align-items-center justify-content-center mb-3">
                                    <span class="text-muted">No Photo</span>
                                </div>
                            <?php endif; ?>
                            <div class="mb-3">
                                <label for="profile_image" class="form-label">Profile Photo</label>
                                <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                                <div class="form-text">Max 5MB. JPEG, PNG, or GIF</div>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username *</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <hr>
                            <h6>Change Password (Optional)</h6>
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                                <div class="form-text">Required only if changing password</div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>