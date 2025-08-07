<?php
require_once '../config.php';
requireLogin();
requireAdmin();

$error = '';
$success = '';

// Simple delete user function
function deleteUser($pdo, $userId, $currentUserId) {
    if ($userId == $currentUserId) {
        throw new Exception('You cannot delete your own account.');
    }
    
    // Get user info for cleanup
    $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('User not found.');
    }
    
    // Delete profile image if exists
    if ($user['profile_image'] && file_exists('../uploads/' . $user['profile_image'])) {
        unlink('../uploads/' . $user['profile_image']);
    }
    
    // Delete user (posts and comments will be deleted by CASCADE)
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    return $stmt->execute([$userId]);
}

// Simple update user role function
function updateUserRole($pdo, $userId, $newRole, $currentUserId) {
    if ($userId == $currentUserId) {
        throw new Exception('You cannot change your own role.');
    }
    
    if (!in_array($newRole, ['user', 'admin'])) {
        throw new Exception('Invalid role specified.');
    }
    
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    return $stmt->execute([$newRole, $userId]);
}

// Get all users with stats
function getAllUsers($pdo) {
    $stmt = $pdo->query("
        SELECT u.*, 
               COUNT(DISTINCT p.id) as post_count,
               COUNT(DISTINCT c.id) as comment_count
        FROM users u
        LEFT JOIN posts p ON u.id = p.author_id
        LEFT JOIN comments c ON u.id = c.author_id
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
    return $stmt->fetchAll();
}

// Get user statistics
function getUserStats($pdo) {
    $stats = [];
    $stats['total'] = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['admins'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    $stats['active_writers'] = (int)$pdo->query("SELECT COUNT(DISTINCT author_id) FROM posts")->fetchColumn();
    $stats['new_this_month'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();
    return $stats;
}

// Handle user deletion
if (isset($_GET['delete'])) {
    try {
        $userId = (int)$_GET['delete'];
        deleteUser($pdo, $userId, $_SESSION['user_id']);
        $_SESSION['success_message'] = 'User deleted successfully!';
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
    
    header('Location: users.php');
    exit();
}

// Handle role update
if (isset($_POST['update_role'])) {
    try {
        $userId = (int)$_POST['user_id'];
        $newRole = $_POST['role'];
        updateUserRole($pdo, $userId, $newRole, $_SESSION['user_id']);
        $_SESSION['success_message'] = 'User role updated successfully!';
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
    
    header('Location: users.php');
    exit();
}

// Get data
$users = getAllUsers($pdo);
$stats = getUserStats($pdo);

$page_title = 'Manage Users';
include '../includes/header.php';
?>

<div class="container mt-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="fas fa-users me-2 text-primary"></i>Manage Users
            </h1>
            <p class="text-muted mb-0"><?php echo count($users); ?> total users</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white text-center">
                <div class="card-body">
                    <i class="fas fa-users fa-2x mb-2"></i>
                    <h3><?php echo $stats['total']; ?></h3>
                    <p class="mb-0">Total Users</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-white text-center">
                <div class="card-body">
                    <i class="fas fa-crown fa-2x mb-2"></i>
                    <h3><?php echo $stats['admins']; ?></h3>
                    <p class="mb-0">Administrators</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white text-center">
                <div class="card-body">
                    <i class="fas fa-edit fa-2x mb-2"></i>
                    <h3><?php echo $stats['active_writers']; ?></h3>
                    <p class="mb-0">Active Writers</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-info text-white text-center">
                <div class="card-body">
                    <i class="fas fa-user-plus fa-2x mb-2"></i>
                    <h3><?php echo $stats['new_this_month']; ?></h3>
                    <p class="mb-0">New This Month</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Posts</th>
                            <th>Comments</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr class="<?php echo $user['id'] == $_SESSION['user_id'] ? 'table-warning' : ''; ?>">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($user['profile_image']): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($user['profile_image']); ?>" 
                                                 alt="Profile" class="rounded-circle me-3" width="40" height="40">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-3" 
                                                 style="width: 40px; height: 40px; font-size: 14px;">
                                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-bold">
                                                <?php echo htmlspecialchars($user['username']); ?>
                                                <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                    <span class="badge bg-info ms-2">You</span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted">ID: #<?php echo $user['id']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                
                                <td>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <select name="role" class="form-select form-select-sm" onchange="this.form.submit()" style="width: auto;">
                                                <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                                                <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            </select>
                                            <input type="hidden" name="update_role" value="1">
                                        </form>
                                    <?php else: ?>
                                        <span class="badge bg-<?php echo $user['role'] == 'admin' ? 'danger' : 'primary'; ?>">
                                            <i class="fas fa-<?php echo $user['role'] == 'admin' ? 'crown' : 'user'; ?> me-1"></i>
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <span class="badge bg-success">
                                        <i class="fas fa-edit me-1"></i><?php echo $user['post_count']; ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <span class="badge bg-info">
                                        <i class="fas fa-comments me-1"></i><?php echo $user['comment_count']; ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <div>
                                        <small><?php echo date('M j, Y', strtotime($user['created_at'])); ?></small>
                                        <br><small class="text-muted"><?php echo timeAgo($user['created_at']); ?></small>
                                    </div>
                                </td>
                                
                                <td>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger" 
                                                title="Delete User"
                                                onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', <?php echo $user['post_count']; ?>, <?php echo $user['comment_count']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted small">Current user</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Delete User Confirmation Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>Confirm User Deletion
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the user:</p>
                <p class="fw-bold" id="deleteUserName"></p>
                
                <div id="deleteUserStats" class="mb-3"></div>
                
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>This action cannot be undone.</strong> The user and all their posts and comments will be permanently deleted.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" class="btn btn-danger" id="confirmDeleteUserBtn">
                    <i class="fas fa-trash me-2"></i>Delete User
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(userId, username, postCount, commentCount) {
    document.getElementById('deleteUserName').textContent = username;
    
    let statsHtml = '<p class="mb-2">This will also delete:</p><ul class="mb-0">';
    statsHtml += '<li>' + postCount + ' post' + (postCount !== 1 ? 's' : '') + '</li>';
    statsHtml += '<li>' + commentCount + ' comment' + (commentCount !== 1 ? 's' : '') + '</li>';
    statsHtml += '</ul>';
    
    document.getElementById('deleteUserStats').innerHTML = statsHtml;
    document.getElementById('confirmDeleteUserBtn').href = 'users.php?delete=' + userId;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
    modal.show();
}
</script>

<?php include '../includes/footer.php'; ?>