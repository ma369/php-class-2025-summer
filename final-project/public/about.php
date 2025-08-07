<?php
require_once '../config.php';

class AboutPage {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getStats() {
        try {
            return [
                'posts' => (int)$this->pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn(),
                'users' => (int)$this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
                'comments' => (int)$this->pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'approved'")->fetchColumn()
            ];
        } catch (PDOException $e) {
            error_log("About stats error: " . $e->getMessage());
            return ['posts' => 0, 'users' => 0, 'comments' => 0];
        }
    }
}

$aboutPage = new AboutPage($pdo);
$stats = $aboutPage->getStats();

$page_title = 'About Us';
$page_description = 'Learn about BlogPlatform - our mission, values, and the community we\'re building.';
include '../includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h1 class="display-4 fw-bold mb-4">About BlogPlatform</h1>
                <p class="lead mb-4">
                    Empowering voices, connecting minds, and fostering meaningful conversations through the power of shared stories.
                </p>
            </div>
        </div>
        
        <!-- Stats -->
        <div class="row text-center mt-5">
            <div class="col-md-4">
                <div class="h1 fw-bold"><?php echo number_format($stats['posts']); ?></div>
                <div class="text-light">Published Posts</div>
            </div>
            <div class="col-md-4">
                <div class="h1 fw-bold"><?php echo number_format($stats['users']); ?></div>
                <div class="text-light">Community Members</div>
            </div>
            <div class="col-md-4">
                <div class="h1 fw-bold"><?php echo number_format($stats['comments']); ?></div>
                <div class="text-light">Comments & Discussions</div>
            </div>
        </div>
    </div>
</section>

<!-- Main Content -->
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Story Section -->
            <div class="card shadow-sm mb-5">
                <div class="card-body p-5">
                    <h2 class="h3 mb-4">Our Story</h2>
                    <p class="lead">
                        BlogPlatform was born from a simple belief: everyone has a story worth telling, and everyone deserves to be heard.
                    </p>
                    <p>
                        In an age where information moves at lightning speed, we wanted to create a space where thoughtful, 
                        meaningful content could thrive. A place where writers could craft their narratives without distraction, 
                        and readers could discover perspectives that challenge, inspire, and enlighten.
                    </p>
                </div>
            </div>
            
            <!-- Features Grid -->
            <div class="row g-4 mb-5">
                <div class="col-md-6">
                    <div class="card h-100 border-0 bg-light">
                        <div class="card-body text-center p-4">
                            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                                 style="width: 60px; height: 60px;">
                                <i class="fas fa-users fa-lg"></i>
                            </div>
                            <h5>Community-Driven</h5>
                            <p class="mb-0">Our platform is shaped by our users. Every feature comes from listening to our community.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card h-100 border-0 bg-light">
                        <div class="card-body text-center p-4">
                            <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                                 style="width: 60px; height: 60px;">
                                <i class="fas fa-shield-alt fa-lg"></i>
                            </div>
                            <h5>Privacy-Focused</h5>
                            <p class="mb-0">Your data is yours. We believe in transparent, ethical data practices and user privacy.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card h-100 border-0 bg-light">
                        <div class="card-body text-center p-4">
                            <div class="bg-info text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                                 style="width: 60px; height: 60px;">
                                <i class="fas fa-rocket fa-lg"></i>
                            </div>
                            <h5>Innovation</h5>
                            <p class="mb-0">We're constantly evolving with new features and technologies to enhance your experience.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card h-100 border-0 bg-light">
                        <div class="card-body text-center p-4">
                            <div class="bg-warning text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                                 style="width: 60px; height: 60px;">
                                <i class="fas fa-heart fa-lg"></i>
                            </div>
                            <h5>Quality Content</h5>
                            <p class="mb-0">We prioritize substance over noise, fostering quality content that rises to the top.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mission & CTA -->
            <div class="card shadow-sm">
                <div class="card-body p-5 text-center">
                    <h3 class="mb-4">Our Mission</h3>
                    <p class="lead mb-4">
                        To democratize publishing and create a platform where diverse voices can share their knowledge, 
                        experiences, and perspectives with the world.
                    </p>
                    
                    <?php if (!isLoggedIn()): ?>
                        <div class="d-flex gap-3 justify-content-center flex-wrap">
                            <a href="register.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus me-2"></i>Join Our Community
                            </a>
                            <a href="index.php" class="btn btn-outline-primary btn-lg">
                                <i class="fas fa-book-open me-2"></i>Explore Posts
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="../admin/dashboard.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>