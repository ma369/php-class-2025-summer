<?php
require_once '../config.php';

class PostViewer {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getPost($slug) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.*, u.username as author_name, u.profile_image as author_image
                FROM posts p 
                JOIN users u ON p.author_id = u.id 
                WHERE p.slug = ? AND p.status = 'published'
            ");
            $stmt->execute([$slug]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get post error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getComments($postId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT c.*, u.username as author_name, u.profile_image as author_image
                FROM comments c 
                JOIN users u ON c.author_id = u.id 
                WHERE c.post_id = ? AND c.status = 'approved'
                ORDER BY c.created_at ASC
            ");
            $stmt->execute([$postId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get comments error: " . $e->getMessage());
            return [];
        }
    }
    
    public function addComment($postId, $authorId, $content) {
        if (empty(trim($content))) {
            throw new Exception('Please enter a comment.');
        }
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO comments (post_id, author_id, content, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            return $stmt->execute([$postId, $authorId, trim($content)]);
        } catch (PDOException $e) {
            error_log("Add comment error: " . $e->getMessage());
            throw new Exception('Failed to post comment. Please try again.');
        }
    }
}

// Get post slug
$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    header('Location: index.php');
    exit();
}

$postViewer = new PostViewer($pdo);
$post = $postViewer->getPost($slug);

if (!$post) {
    header('Location: index.php');
    exit();
}

$comments = $postViewer->getComments($post['id']);
$error = '';

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isLoggedIn()) {
    try {
        $content = $_POST['content'] ?? '';
        $postViewer->addComment($post['id'], $_SESSION['user_id'], $content);
        $_SESSION['success_message'] = 'Your comment has been posted!';
        header('Location: post.php?slug=' . urlencode($slug));
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$page_title = $post['title'];
$page_description = $post['excerpt'] ?: truncate(strip_tags($post['content']), 160);
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Post Article -->
            <article class="card shadow-sm mb-4">
                <?php if ($post['featured_image']): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($post['featured_image']); ?>" 
                         class="card-img-top" 
                         style="height: 400px; object-fit: cover;" 
                         alt="<?php echo htmlspecialchars($post['title']); ?>">
                <?php endif; ?>
                
                <div class="card-body p-5">
                    <!-- Post Header -->
                    <div class="text-center mb-4">
                        <h1 class="display-5 fw-bold mb-4"><?php echo htmlspecialchars($post['title']); ?></h1>
                        
                        <div class="d-flex align-items-center justify-content-center mb-4">
                            <?php if ($post['author_image']): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($post['author_image']); ?>" 
                                     class="rounded-circle me-3" width="48" height="48" alt="Author">
                            <?php else: ?>
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" 
                                     style="width: 48px; height: 48px; font-size: 18px;">
                                    <?php echo strtoupper(substr($post['author_name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="text-start">
                                <div class="fw-bold"><?php echo htmlspecialchars($post['author_name']); ?></div>
                                <div class="text-muted small">
                                    <i class="fas fa-calendar me-1"></i><?php echo date('F j, Y', strtotime($post['created_at'])); ?>
                                    <span class="mx-2">•</span>
                                    <i class="fas fa-clock me-1"></i><?php echo timeAgo($post['created_at']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Post Content -->
                    <div class="post-content">
                        <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                    </div>
                    
                    <!-- Post Footer -->
                    <hr class="my-5">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="index.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Posts
                        </a>
                        
                        <div class="text-muted">
                            <i class="fas fa-comments me-1"></i><?php echo count($comments); ?> 
                            comment<?php echo count($comments) != 1 ? 's' : ''; ?>
                        </div>
                    </div>
                </div>
            </article>
            
            <!-- Comments Section -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="h5 mb-0">
                        <i class="fas fa-comments me-2"></i>Comments (<?php echo count($comments); ?>)
                    </h3>
                </div>
                <div class="card-body">
                    <!-- Comment Form -->
                    <?php if (isLoggedIn()): ?>
                        <div class="mb-4">
                            <h6 class="mb-3">Leave a Comment</h6>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST">
                                <div class="mb-3">
                                    <textarea class="form-control" 
                                              name="content" 
                                              rows="4" 
                                              placeholder="Share your thoughts..." 
                                              required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Post Comment
                                </button>
                            </form>
                        </div>
                        <hr>
                    <?php else: ?>
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="alert-link">Login</a> or 
                            <a href="register.php" class="alert-link">register</a> to join the conversation.
                        </div>
                    <?php endif; ?>
                    
                    <!-- Comments List -->
                    <?php if ($comments): ?>
                        <div class="comments-list">
                            <?php foreach ($comments as $comment): ?>
                                <div class="d-flex mb-4">
                                    <!-- Avatar -->
                                    <div class="flex-shrink-0 me-3">
                                        <?php if ($comment['author_image']): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($comment['author_image']); ?>" 
                                                 class="rounded-circle" width="40" height="40" alt="Commenter">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" 
                                                 style="width: 40px; height: 40px; font-size: 14px;">
                                                <?php echo strtoupper(substr($comment['author_name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Comment Content -->
                                    <div class="flex-grow-1">
                                        <div class="bg-light rounded p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="mb-0 text-primary"><?php echo htmlspecialchars($comment['author_name']); ?></h6>
                                                <small class="text-muted"><?php echo timeAgo($comment['created_at']); ?></small>
                                            </div>
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No comments yet</h6>
                            <p class="text-muted mb-0">Be the first to share your thoughts!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="sticky-top" style="top: 2rem;">
                <!-- Author Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body text-center">
                        <?php if ($post['author_image']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($post['author_image']); ?>" 
                                 class="rounded-circle mb-3" width="80" height="80" alt="Author">
                        <?php else: ?>
                            <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" 
                                 style="width: 80px; height: 80px; font-size: 28px;">
                                <?php echo strtoupper(substr($post['author_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        
                        <h6 class="mb-2"><?php echo htmlspecialchars($post['author_name']); ?></h6>
                        <p class="text-muted small mb-3">Article Author</p>
                        
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary btn-sm" onclick="sharePost()">
                                <i class="fas fa-share-alt me-1"></i>Share Post
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Post Meta -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>Post Details
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="border-end">
                                    <div class="h6 text-primary"><?php echo count($comments); ?></div>
                                    <div class="small text-muted">Comments</div>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="h6 text-success" id="readTime">~5 min</div>
                                <div class="small text-muted">Read Time</div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="small text-muted">
                            <div class="mb-2">
                                <i class="fas fa-calendar me-2"></i>Published: <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                            </div>
                            <?php if ($post['updated_at'] != $post['created_at']): ?>
                                <div class="mb-2">
                                    <i class="fas fa-edit me-2"></i>Updated: <?php echo timeAgo($post['updated_at']); ?>
                                </div>
                            <?php endif; ?>
                            <div>
                                <i class="fas fa-link me-2"></i>Permalink: <code><?php echo htmlspecialchars($post['slug']); ?></code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Calculate read time
document.addEventListener('DOMContentLoaded', function() {
    const content = document.querySelector('.post-content').textContent;
    const wordsPerMinute = 200;
    const wordCount = content.trim().split(/\s+/).length;
    const readTime = Math.ceil(wordCount / wordsPerMinute);
    
    document.getElementById('readTime').textContent = `~${readTime} min`;
});

// Share functionality
function sharePost() {
    if (navigator.share) {
        navigator.share({
            title: '<?php echo htmlspecialchars($post['title']); ?>',
            text: '<?php echo htmlspecialchars($post['excerpt'] ?: truncate(strip_tags($post['content']), 100)); ?>',
            url: window.location.href
        });
    } else {
        // Fallback - copy to clipboard
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('Post URL copied to clipboard!');
        });
    }
}

// Auto-resize comment textarea
document.querySelector('textarea[name="content"]')?.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = this.scrollHeight + 'px';
});
</script>

<?php include '../includes/footer.php'; ?>