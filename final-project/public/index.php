<?php
require_once '../config.php';

class HomePage {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getPublishedPosts($limit = 8) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.id, p.title, p.slug, p.excerpt, p.featured_image, p.created_at,
                       u.username as author_name, u.profile_image as author_image
                FROM posts p 
                JOIN users u ON p.author_id = u.id 
                WHERE p.status = 'published' 
                ORDER BY p.created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get posts error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getRecentComments($limit = 5) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT c.content, c.created_at, p.title as post_title, p.slug as post_slug, 
                       u.username as author_name 
                FROM comments c 
                JOIN posts p ON c.post_id = p.id 
                JOIN users u ON c.author_id = u.id 
                WHERE c.status = 'approved' AND p.status = 'published'
                ORDER BY c.created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get comments error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getStats() {
        try {
            return [
                'posts' => (int)$this->pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn(),
                'users' => (int)$this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
                'comments' => (int)$this->pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'approved'")->fetchColumn()
            ];
        } catch (PDOException $e) {
            error_log("Get stats error: " . $e->getMessage());
            return ['posts' => 0, 'users' => 0, 'comments' => 0];
        }
    }
}

// Initialize homepage
$homePage = new HomePage($pdo);
$posts = $homePage->getPublishedPosts();
$recentComments = $homePage->getRecentComments();
$stats = $homePage->getStats();

$page_title = 'Home';
$page_description = 'Discover amazing content from our community of writers and thought leaders.';
include '../includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-bold mb-4">Welcome to BlogPlatform</h1>
                <p class="lead mb-4">
                    Discover amazing stories, insights, and ideas from our community of passionate writers and thought leaders.
                </p>
                <?php if (!isLoggedIn()): ?>
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="register.php" class="btn btn-light btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Join Our Community
                        </a>
                        <a href="about.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-info-circle me-2"></i>Learn More
                        </a>
                    </div>
                <?php else: ?>
                    <a href="../admin/dashboard.php" class="btn btn-light btn-lg">
                        <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Stats -->
        <div class="row mt-5 text-center">
            <div class="col-md-4">
                <div class="h2 fw-bold"><?php echo number_format($stats['posts']); ?></div>
                <div class="text-light">Published Posts</div>
            </div>
            <div class="col-md-4">
                <div class="h2 fw-bold"><?php echo number_format($stats['users']); ?></div>
                <div class="text-light">Community Members</div>
            </div>
            <div class="col-md-4">
                <div class="h2 fw-bold"><?php echo number_format($stats['comments']); ?></div>
                <div class="text-light">Discussions</div>
            </div>
        </div>
    </div>
</section>

<!-- Main Content -->
<div class="container py-5">
    <div class="row">
        <!-- Posts Grid -->
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h3 mb-0">Latest Posts</h2>
                <span class="text-muted">
                    <i class="fas fa-rss me-1"></i><?php echo count($posts); ?> posts
                </span>
            </div>
            
            <?php if ($posts): ?>
                <div class="row g-4">
                    <?php foreach ($posts as $post): ?>
                        <div class="col-md-6">
                            <article class="card h-100 shadow-sm">
                                <?php if ($post['featured_image']): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($post['featured_image']); ?>" 
                                         class="card-img-top" 
                                         style="height: 200px; object-fit: cover;" 
                                         alt="<?php echo htmlspecialchars($post['title']); ?>"
                                         loading="lazy">
                                <?php else: ?>
                                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center text-muted" 
                                         style="height: 200px;">
                                        <i class="fas fa-file-alt fa-3x"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body d-flex flex-column">
                                    <!-- Author & Date -->
                                    <div class="d-flex align-items-center mb-3">
                                        <?php if ($post['author_image']): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($post['author_image']); ?>" 
                                                 class="rounded-circle me-2" width="32" height="32">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" 
                                                 style="width: 32px; height: 32px; font-size: 14px;">
                                                <?php echo strtoupper(substr($post['author_name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-semibold small"><?php echo htmlspecialchars($post['author_name']); ?></div>
                                            <div class="text-muted small"><?php echo timeAgo($post['created_at']); ?></div>
                                        </div>
                                    </div>
                                    
                                    <!-- Title -->
                                    <h5 class="card-title">
                                        <a href="post.php?slug=<?php echo urlencode($post['slug']); ?>" 
                                           class="text-decoration-none text-dark stretched-link">
                                            <?php echo htmlspecialchars($post['title']); ?>
                                        </a>
                                    </h5>
                                    
                                    <!-- Excerpt -->
                                    <p class="card-text text-muted flex-grow-1">
                                        <?php echo htmlspecialchars($post['excerpt'] ?: truncate(strip_tags($post['content'] ?? ''), 120)); ?>
                                    </p>
                                    
                                    <!-- Read More -->
                                    <div class="mt-auto">
                                        <small class="text-primary">
                                            <i class="fas fa-arrow-right me-1"></i>Read More
                                        </small>
                                    </div>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Load More -->
                <?php if (count($posts) >= 8): ?>
                    <div class="text-center mt-4">
                        <button class="btn btn-outline-primary" onclick="loadMorePosts()">
                            <i class="fas fa-plus me-2"></i>Load More Posts
                        </button>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-edit fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No Posts Yet</h4>
                    <p class="text-muted">Be the first to share your thoughts!</p>
                    <?php if (isLoggedIn()): ?>
                        <a href="../admin/add_post.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Write First Post
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="sticky-top" style="top: 2rem;">
                <!-- Recent Comments -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-comments me-2"></i>Recent Comments
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($recentComments): ?>
                            <?php foreach ($recentComments as $comment): ?>
                                <div class="border-bottom pb-3 mb-3 last:border-0 last:pb-0 last:mb-0">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <strong class="text-primary small"><?php echo htmlspecialchars($comment['author_name']); ?></strong>
                                        <small class="text-muted"><?php echo timeAgo($comment['created_at']); ?></small>
                                    </div>
                                    <p class="mb-1 small"><?php echo htmlspecialchars(truncate($comment['content'], 80)); ?></p>
                                    <small class="text-muted">
                                        on <a href="post.php?slug=<?php echo urlencode($comment['post_slug']); ?>" 
                                              class="text-decoration-none">
                                            <?php echo htmlspecialchars(truncate($comment['post_title'], 30)); ?>
                                        </a>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted mb-0 text-center">No comments yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- About Section -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>About BlogPlatform
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            A modern platform for sharing ideas, connecting with readers, and building meaningful conversations.
                        </p>
                        <div class="d-grid gap-2">
                            <a href="about.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-arrow-right me-1"></i>Learn More
                            </a>
                            <?php if (!isLoggedIn()): ?>
                                <a href="register.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-user-plus me-1"></i>Join Us
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let postsOffset = <?php echo count($posts); ?>;

async function loadMorePosts() {
    try {
        const response = await fetch(`api/posts.php?offset=${postsOffset}&limit=8`);
        const data = await response.json();
        
        if (data.success && data.posts.length > 0) {
            const postsGrid = document.querySelector('.row.g-4');
            
            data.posts.forEach(post => {
                const postElement = createPostElement(post);
                postsGrid.insertAdjacentHTML('beforeend', postElement);
            });
            
            postsOffset += data.posts.length;
            
            if (data.posts.length < 8) {
                document.querySelector('[onclick="loadMorePosts()"]').style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error loading posts:', error);
    }
}

function createPostElement(post) {
    const authorImage = post.author_image 
        ? `<img src="../uploads/${post.author_image}" class="rounded-circle me-2" width="32" height="32">`
        : `<div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 14px;">${post.author_name.charAt(0).toUpperCase()}</div>`;
        
    const featuredImage = post.featured_image
        ? `<img src="../uploads/${post.featured_image}" class="card-img-top" style="height: 200px; object-fit: cover;" alt="${post.title}" loading="lazy">`
        : `<div class="card-img-top bg-light d-flex align-items-center justify-content-center text-muted" style="height: 200px;"><i class="fas fa-file-alt fa-3x"></i></div>`;
        
    return `
        <div class="col-md-6">
            <article class="card h-100 shadow-sm">
                ${featuredImage}
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center mb-3">
                        ${authorImage}
                        <div>
                            <div class="fw-semibold small">${post.author_name}</div>
                            <div class="text-muted small">${post.time_ago}</div>
                        </div>
                    </div>
                    <h5 class="card-title">
                        <a href="post.php?slug=${encodeURIComponent(post.slug)}" class="text-decoration-none text-dark stretched-link">
                            ${post.title}
                        </a>
                    </h5>
                    <p class="card-text text-muted flex-grow-1">${post.excerpt}</p>
                    <div class="mt-auto">
                        <small class="text-primary">
                            <i class="fas fa-arrow-right me-1"></i>Read More
                        </small>
                    </div>
                </div>
            </article>
        </div>
    `;
}
</script>

<?php include '../includes/footer.php'; ?>