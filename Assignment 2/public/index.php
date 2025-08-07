<?php
require_once '../config.php';
include '../includes/header.php';
?>

<div class="hero-section">
    <div class="container">
        <h1 class="display-4 mb-4">Welcome to CRUD Application</h1>
        <p class="lead">A complete solution for content management with user authentication and file uploads</p>
        <?php if (!isLoggedIn()): ?>
            <a href="register.php" class="btn btn-light btn-lg me-3">Get Started</a>
            <a href="login.php" class="btn btn-outline-light btn-lg">Login</a>
        <?php else: ?>
            <a href="../admin/dashboard.php" class="btn btn-light btn-lg">Go to Dashboard</a>
        <?php endif; ?>
    </div>
</div>

<div class="row mb-5">
    <div class="col-md-4">
        <div class="feature-box">
            <h3>Create & Manage</h3>
            <p>Create, read, update, and delete your content with ease. Full CRUD functionality at your fingertips.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="feature-box">
            <h3>Secure Access</h3>
            <p>Admin-level pages are protected with user authentication. Your content stays safe and secure.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="feature-box">
            <h3>File Uploads</h3>
            <p>Upload images and files to enhance your content. Visual elements make your data more engaging.</p>
        </div>
    </div>
</div>

<div class="text-center">
    <h2>🎉 Your CRUD Application is Working!</h2>
    <p class="text-muted">Database connection successful. Ready to start managing your content.</p>
    
    <?php if (!isLoggedIn()): ?>
        <div class="mt-4">
            <a href="login.php" class="btn btn-primary me-2">Login</a>
            <a href="register.php" class="btn btn-outline-primary">Sign Up</a>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>