<?php
require_once '../config.php';
requireLogin();

// Simple functions to get dashboard data
function getUserStats($pdo, $userId) {
    try {
        $stats = [];
        
        // Get user's post counts
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE author_id = ?");
        $stmt->execute([$userId]);
        $stats['total_posts'] = (int)$stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE author_id = ? AND status = 'published'");
        $stmt->execute([$userId]);
        $stats['published_posts'] = (int)$stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE author_id = ? AND status = 'draft'");
        $stmt->execute([$userId]);
        $stats['draft_posts'] = (int)$stmt->fetchColumn();
        
        // Get comments on user's posts
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM comments c 
            JOIN posts p ON c.post_id = p.id 
            WHERE p.author_id = ? AND c.status = 'approved'
        ");
        $stmt->execute([$userId]);
        $stats['comments_received'] = (int)$stmt->fetchColumn();
        
        return $stats;
    } catch (PDOException $e) {
        return ['total_posts' => 0, 'published_posts' => 0, 'draft_posts' => 0, 'comments_received' => 0];
    }
}

function getAdminStats($pdo) {
    if (!isAdmin()) return [];
    
    try {
        return [
            'total_users' => (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'total_posts' => (int)$pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn(),
            'total_comments' => (int)$pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn(),
            'pending_comments' => (int)$pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn(),
        ];
    } catch (PDOException $e) {
        return [];
    }
}

function getRecentPosts($pdo, $userId, $limit = 5) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, title, slug, status, featured_image, created_at 
            FROM posts 
            WHERE author_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function getRecentComments($pdo, $userId, $limit = 5) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.content, c.created_at, p.title as post_title, p.slug as post_slug, 
                   u.username as commenter_name 
            FROM comments c 
            JOIN posts p ON c.post_id = p.id 
            JOIN users u ON c.author_id = u.id 
            WHERE p.author_id = ? AND c.status = 'approved'
            ORDER BY c.created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// Get dashboard data
$userId = $_SESSION['user_id'];
$userStats = getUserStats($pdo, $userId);
$adminStats = getAdminStats($pdo);
$recentPosts = getRecentPosts($pdo, $userId);
$recentComments = getRecentComments($pdo, $userId);

$page_title = 'Dashboard';
include '../includes/header.php';
?>

<div class="container mt-4">
    <!-- Welcome Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h2">
                <i class="fas fa-tachometer-alt me-2 text-primary"></i>Dashboard
            </h1>
            <p class="text-muted">Welcome back, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!</p>
        </div>
    </div>

    <!-- User Statistics -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white text-center">
                <div class="card-body">
                    <i class="fas fa-edit fa-2x mb-2"></i>
                    <h3><?php echo $userStats['total_posts']; ?></h3>
                    <p class="mb-0">Total Posts</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white text-center">
                <div class="card-body">
                    <i class="fas fa-eye fa-2x mb-2"></i>
                    <h3><?php echo $userStats['published_posts']; ?></h3>
                    <p class="mb-0">Published</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-white text-center">
                <div class="card-body">
                    <i class="fas fa-file-alt fa-2x mb-2"></i>
                    <h3><?php echo $userStats['draft_posts']; ?></h3>
                    <p class="mb-0">Drafts</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-info text-white text-center">
                <div class="card-body">
                    <i class="fas fa-comments fa-2x mb-2"></i>
                    <h3><?php echo $userStats['comments_received']; ?></h3>
                    <p class="mb-0">Comments</p>
                </div>
            </div>
        </div>
    </div>

    <?php if (isAdmin() && !empty($adminStats)): ?>
    <!-- Admin Statistics -->
    <div class="row mb-4">
        <div class="col-12">
            <h4><i class="fas fa-crown me-2 text-warning"></i>Admin Overview</h4>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-secondary text-white text-center">
                <div class="card-body">
                    <i class="fas fa-users fa-2x mb-2"></i>
                    <h4><?php echo $adminStats['total_users']; ?></h4>
                    <p class="mb-0">Total Users</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-dark text-white text-center">
                <div class="card-body">
                    <i class="fas fa-newspaper fa-2x mb-2"></i>
                    <h4><?php echo $adminStats['total_posts']; ?></h4>
                    <p class="mb-0">All Posts</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-info text-white text-center">
                <div class="card-body">
                    <i class="fas fa-comments fa-2x mb-2"></i>
                    <h4><?php echo $adminStats['total_comments']; ?></h4>
                    <p class="mb-0">All Comments</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-white text-center">
                <div class="card-body">
                    <i class="fas fa-clock fa-2x mb-2"></i>
                    <h4><?php echo $adminStats['pending_comments']; ?></h4>
                    <p class="mb-0">Pending</p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Recent Posts -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>Recent Posts
                    </h5>
                    <a href="add_post.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i>New Post
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($recentPosts)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentPosts as $post): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($post['title']); ?></strong>
                                                <?php if ($post['featured_image']): ?>
                                                    <i class="fas fa-image text-success ms-1" title="Has image"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($post['status'] == 'published'): ?>
                                                    <span class="badge bg-success">Published</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Draft</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo timeAgo($post['created_at']); ?></td>
                                            <td>
                                                <?php if ($post['status'] == 'published'): ?>
                                                    <a href="../public/post.php?slug=<?php echo urlencode($post['slug']); ?>" 
                                                       class="btn btn-sm btn-outline-primary" target="_blank">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="edit_post.php?id=<?php echo $post['id']; ?>" 
                                                   class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-edit fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No posts yet</h5>
                            <p class="text-muted mb-3">Start sharing your thoughts!</p>
                            <a href="add_post.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Create Your First Post
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="add_post.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>New Post
                        </a>
                        <a href="posts.php" class="btn btn-outline-primary">
                            <i class="fas fa-list me-2"></i>Manage Posts
                        </a>
                        <?php if (isAdmin()): ?>
                            <a href="users.php" class="btn btn-outline-secondary">
                                <i class="fas fa-users me-2"></i>Manage Users
                            </a>
                        <?php endif; ?>
                        <a href="profile.php" class="btn btn-outline-info">
                            <i class="fas fa-user-edit me-2"></i>Edit Profile
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Comments -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-comments me-2"></i>Recent Comments
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($recentComments)): ?>
                        <?php foreach ($recentComments as $comment): ?>
                            <div class="border-bottom pb-2 mb-3">
                                <div class="small">
                                    <strong><?php echo htmlspecialchars($comment['commenter_name']); ?></strong> 
                                    commented on <em><?php echo htmlspecialchars(truncate($comment['post_title'], 30)); ?></em>
                                </div>
                                <div class="small mt-1">
                                    <?php echo htmlspecialchars(truncate($comment['content'], 60)); ?>
                                </div>
                                <div class="small text-muted"><?php echo timeAgo($comment['created_at']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0">No comments on your posts yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>