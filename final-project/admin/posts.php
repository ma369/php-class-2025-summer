<?php
require_once '../config.php';
requireLogin();

$error = '';
$success = '';

// Simple delete function
function deletePost($pdo, $postId) {
    if (!isAdmin()) {
        throw new Exception('Unauthorized action.');
    }
    
    // Get post info for cleanup
    $stmt = $pdo->prepare("SELECT featured_image FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();
    
    if (!$post) {
        throw new Exception('Post not found.');
    }
    
    // Delete featured image if exists
    if ($post['featured_image'] && file_exists('../uploads/' . $post['featured_image'])) {
        unlink('../uploads/' . $post['featured_image']);
    }
    
    // Delete post
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    return $stmt->execute([$postId]);
}

// Get posts based on filters
function getPosts($pdo, $userId, $isAdmin, $filters = []) {
    $whereClause = [];
    $params = [];
    
    // Base query
    $query = "
        SELECT p.*, u.username as author_name, 
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND status = 'approved') as comment_count
        FROM posts p 
        JOIN users u ON p.author_id = u.id
    ";
    
    // Filter by author if not admin
    if (!$isAdmin) {
        $whereClause[] = "p.author_id = ?";
        $params[] = $userId;
    }
    
    // Filter by status
    if (!empty($filters['status'])) {
        $whereClause[] = "p.status = ?";
        $params[] = $filters['status'];
    }
    
    // Filter by search
    if (!empty($filters['search'])) {
        $whereClause[] = "(p.title LIKE ? OR p.content LIKE ?)";
        $searchTerm = '%' . $filters['search'] . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Add WHERE clause
    if (!empty($whereClause)) {
        $query .= " WHERE " . implode(" AND ", $whereClause);
    }
    
    $query .= " ORDER BY p.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Get post statistics
function getPostStats($pdo, $userId, $isAdmin) {
    $stats = [];
    
    if ($isAdmin) {
        $stats['total'] = (int)$pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
        $stats['published'] = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn();
        $stats['draft'] = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'draft'")->fetchColumn();
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE author_id = ?");
        $stmt->execute([$userId]);
        $stats['total'] = (int)$stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE author_id = ? AND status = 'published'");
        $stmt->execute([$userId]);
        $stats['published'] = (int)$stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE author_id = ? AND status = 'draft'");
        $stmt->execute([$userId]);
        $stats['draft'] = (int)$stmt->fetchColumn();
    }
    
    return $stats;
}

// Handle delete request
if (isset($_GET['delete']) && isAdmin()) {
    try {
        $postId = (int)$_GET['delete'];
        deletePost($pdo, $postId);
        $_SESSION['success_message'] = 'Post deleted successfully!';
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
    
    header('Location: posts.php');
    exit();
}

// Get filters
$filters = [
    'status' => $_GET['status'] ?? '',
    'search' => trim($_GET['search'] ?? '')
];

// Get data
$userId = $_SESSION['user_id'];
$isAdmin = isAdmin();
$posts = getPosts($pdo, $userId, $isAdmin, $filters);
$stats = getPostStats($pdo, $userId, $isAdmin);

$page_title = 'Manage Posts';
include '../includes/header.php';
?>

<div class="container mt-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">
                <i class="fas fa-list me-2 text-primary"></i>
                <?php echo $isAdmin ? 'All Posts' : 'My Posts'; ?>
            </h1>
            <p class="text-muted mb-0"><?php echo count($posts); ?> posts found</p>
        </div>
        <a href="add_post.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>New Post
        </a>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white text-center">
                <div class="card-body">
                    <h3><?php echo $stats['total']; ?></h3>
                    <p class="mb-0">Total Posts</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white text-center">
                <div class="card-body">
                    <h3><?php echo $stats['published']; ?></h3>
                    <p class="mb-0">Published</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-white text-center">
                <div class="card-body">
                    <h3><?php echo $stats['draft']; ?></h3>
                    <p class="mb-0">Drafts</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <label for="search" class="form-label">Search Posts</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($filters['search']); ?>" 
                           placeholder="Search title or content...">
                </div>
                
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="published" <?php echo $filters['status'] === 'published' ? 'selected' : ''; ?>>
                            Published
                        </option>
                        <option value="draft" <?php echo $filters['status'] === 'draft' ? 'selected' : ''; ?>>
                            Draft
                        </option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Filter
                        </button>
                        <a href="posts.php" class="btn btn-outline-secondary">
                            <i class="fas fa-undo me-1"></i>Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Posts Table -->
    <?php if (!empty($posts)): ?>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Image</th>
                                <th>Title</th>
                                <?php if ($isAdmin): ?>
                                    <th>Author</th>
                                <?php endif; ?>
                                <th>Status</th>
                                <th>Comments</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($posts as $post): ?>
                                <tr>
                                    <td>
                                        <?php if ($post['featured_image']): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($post['featured_image']); ?>" 
                                                 alt="Featured Image" 
                                                 class="rounded"
                                                 style="width: 50px; height: 35px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-light border rounded d-flex align-items-center justify-content-center text-muted" 
                                                 style="width: 50px; height: 35px;">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($post['title']); ?></strong>
                                            <?php if ($post['excerpt']): ?>
                                                <br><small class="text-muted">
                                                    <?php echo htmlspecialchars(truncate($post['excerpt'], 60)); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    
                                    <?php if ($isAdmin): ?>
                                        <td><?php echo htmlspecialchars($post['author_name']); ?></td>
                                    <?php endif; ?>
                                    
                                    <td>
                                        <?php if ($post['status'] == 'published'): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-eye me-1"></i>Published
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-file-alt me-1"></i>Draft
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo $post['comment_count']; ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <div>
                                            <small><?php echo date('M j, Y', strtotime($post['created_at'])); ?></small>
                                            <br><small class="text-muted"><?php echo timeAgo($post['created_at']); ?></small>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <div class="btn-group" role="group">
                                            <?php if ($post['status'] == 'published'): ?>
                                                <a href="../public/post.php?slug=<?php echo urlencode($post['slug']); ?>" 
                                                   class="btn btn-sm btn-outline-primary" 
                                                   target="_blank" 
                                                   title="View Post">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($isAdmin || $post['author_id'] == $_SESSION['user_id']): ?>
                                                <a href="edit_post.php?id=<?php echo $post['id']; ?>" 
                                                   class="btn btn-sm btn-outline-secondary" 
                                                   title="Edit Post">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <?php if ($isAdmin): ?>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger" 
                                                            title="Delete Post"
                                                            onclick="confirmDelete(<?php echo $post['id']; ?>, '<?php echo htmlspecialchars($post['title']); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-edit fa-4x text-muted mb-4"></i>
                <h3 class="text-muted">No Posts Found</h3>
                <p class="text-muted mb-4">
                    <?php if (!empty($filters['search']) || !empty($filters['status'])): ?>
                        No posts match your current filters.
                    <?php else: ?>
                        <?php echo $isAdmin ? 'No posts have been created yet.' : "You haven't written any posts yet."; ?>
                    <?php endif; ?>
                </p>
                
                <?php if (empty($filters['search']) && empty($filters['status'])): ?>
                    <a href="add_post.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus me-2"></i>Create Your First Post
                    </a>
                <?php else: ?>
                    <a href="posts.php" class="btn btn-outline-primary">
                        <i class="fas fa-list me-2"></i>Show All Posts
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>Confirm Deletion
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the post:</p>
                <p class="fw-bold" id="deletePostTitle"></p>
                <div class="alert alert-danger">
                    <i class="fas fa-warning me-2"></i>
                    <strong>This action cannot be undone.</strong> The post and all its comments will be permanently deleted.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-2"></i>Delete Post
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(postId, postTitle) {
    document.getElementById('deletePostTitle').textContent = postTitle;
    document.getElementById('confirmDeleteBtn').href = 'posts.php?delete=' + postId;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>

<?php include '../includes/footer.php'; ?>