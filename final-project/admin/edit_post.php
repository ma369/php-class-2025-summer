<?php
require_once '../config.php';
requireLogin();

$error = '';
$postId = (int)($_GET['id'] ?? 0);

if (!$postId) {
    $_SESSION['error_message'] = 'Invalid post ID.';
    header('Location: posts.php');
    exit();
}

// Get post (check ownership unless admin)
if (isAdmin()) {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND author_id = ?");
    $stmt->execute([$postId, $_SESSION['user_id']]);
}

$post = $stmt->fetch();

if (!$post) {
    $_SESSION['error_message'] = 'Post not found or access denied.';
    header('Location: posts.php');
    exit();
}

// Simple image upload function
function uploadImage($file, $oldImage = null) {
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
    
    // Upload new image
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'post_' . uniqid() . '.' . $extension;
    $uploadPath = '../uploads/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Failed to upload image.');
    }
    
    return $filename;
}

// Simple slug creation (for title changes)
function makeSlug($title, $pdo, $excludeId) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    $originalSlug = $slug;
    $counter = 1;
    
    // Make sure slug is unique
    while (true) {
        $stmt = $pdo->prepare("SELECT id FROM posts WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $excludeId]);
        if (!$stmt->fetch()) {
            break;
        }
        $slug = $originalSlug . '-' . $counter++;
    }
    
    return $slug;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    
    try {
        // Validation
        if (empty($title)) {
            throw new Exception('Title is required.');
        }
        if (empty($content)) {
            throw new Exception('Content is required.');
        }
        
        // Generate slug if title changed
        $slug = $post['slug'];
        if ($title !== $post['title']) {
            $slug = makeSlug($title, $pdo, $postId);
        }
        
        // Handle image upload or removal
        $featuredImage = $post['featured_image'];
        
        if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
            // Remove existing image
            if ($featuredImage && file_exists('../uploads/' . $featuredImage)) {
                unlink('../uploads/' . $featuredImage);
            }
            $featuredImage = null;
        } elseif (isset($_FILES['featured_image'])) {
            // Upload new image
            $featuredImage = uploadImage($_FILES['featured_image'], $featuredImage);
        }
        
        // Auto-generate excerpt if empty
        if (empty($excerpt)) {
            $excerpt = substr(strip_tags($content), 0, 200) . '...';
        }
        
        // Update post
        $stmt = $pdo->prepare("
            UPDATE posts 
            SET title = ?, slug = ?, content = ?, excerpt = ?, featured_image = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        if ($stmt->execute([$title, $slug, $content, $excerpt, $featuredImage, $status, $postId])) {
            $_SESSION['success_message'] = 'Post updated successfully!';
            header('Location: posts.php');
            exit();
        } else {
            throw new Exception('Failed to update post.');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
} else {
    // Pre-populate form with existing data
    $_POST['title'] = $post['title'];
    $_POST['content'] = $post['content'];
    $_POST['excerpt'] = $post['excerpt'];
    $_POST['status'] = $post['status'];
}

$page_title = 'Edit Post';
include '../includes/header.php';
?>

<div class="container mt-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2">
                        <i class="fas fa-edit me-2 text-primary"></i>Edit Post
                    </h1>
                    <p class="text-muted mb-0">Last updated: <?php echo timeAgo($post['updated_at']); ?></p>
                </div>
                <div class="btn-group">
                    <?php if ($post['status'] == 'published'): ?>
                        <a href="../public/post.php?slug=<?php echo urlencode($post['slug']); ?>" 
                           class="btn btn-outline-info" target="_blank">
                            <i class="fas fa-eye me-2"></i>View Live
                        </a>
                    <?php endif; ?>
                    <a href="posts.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Posts
                    </a>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <!-- Main Content -->
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-body">
                                <!-- Title -->
                                <div class="mb-3">
                                    <label for="title" class="form-label fw-bold">
                                        <i class="fas fa-heading me-1"></i>Title *
                                    </label>
                                    <input type="text" 
                                           class="form-control form-control-lg" 
                                           id="title" 
                                           name="title" 
                                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                                           required>
                                    <div class="form-text">Current URL: <?php echo htmlspecialchars($post['slug']); ?></div>
                                </div>

                                <!-- Excerpt -->
                                <div class="mb-3">
                                    <label for="excerpt" class="form-label fw-bold">
                                        <i class="fas fa-quote-left me-1"></i>Excerpt (Optional)
                                    </label>
                                    <textarea class="form-control" 
                                              id="excerpt" 
                                              name="excerpt" 
                                              rows="3"><?php echo htmlspecialchars($_POST['excerpt'] ?? ''); ?></textarea>
                                </div>

                                <!-- Content -->
                                <div class="mb-3">
                                    <label for="content" class="form-label fw-bold">
                                        <i class="fas fa-align-left me-1"></i>Content *
                                    </label>
                                    <textarea class="form-control" 
                                              id="content" 
                                              name="content" 
                                              rows="15" 
                                              required><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sidebar -->
                    <div class="col-lg-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-cog me-2"></i>Post Settings
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Status -->
                                <div class="mb-3">
                                    <label for="status" class="form-label fw-bold">
                                        <i class="fas fa-eye me-1"></i>Status
                                    </label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="draft" <?php echo ($_POST['status'] ?? '') == 'draft' ? 'selected' : ''; ?>>
                                            Draft
                                        </option>
                                        <option value="published" <?php echo ($_POST['status'] ?? '') == 'published' ? 'selected' : ''; ?>>
                                            Published
                                        </option>
                                    </select>
                                </div>
                                
                                <!-- Featured Image -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-image me-1"></i>Featured Image
                                    </label>
                                    
                                    <?php if ($post['featured_image']): ?>
                                        <div class="current-image mb-3">
                                            <img src="../uploads/<?php echo htmlspecialchars($post['featured_image']); ?>" 
                                                 alt="Current Featured Image" 
                                                 class="img-thumbnail mb-2" 
                                                 style="max-width: 100%; max-height: 200px;">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="remove_image">
                                                <label class="form-check-label text-danger" for="remove_image">
                                                    <i class="fas fa-trash me-1"></i>Remove current image
                                                </label>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <input type="file" 
                                           class="form-control" 
                                           id="featured_image" 
                                           name="featured_image" 
                                           accept="image/*" 
                                           onchange="previewNewImage(this)">
                                    <div class="form-text">Upload new image (Max 5MB. JPEG, PNG, or GIF)</div>
                                    
                                    <!-- New Image Preview -->
                                    <div id="newImagePreview" class="mt-3" style="display: none;">
                                        <img id="newPreview" src="" class="img-thumbnail" style="max-width: 100%; max-height: 200px;">
                                        <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="removeNewPreview()">
                                            <i class="fas fa-times me-1"></i>Remove
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Post Info -->
                                <div class="mb-4">
                                    <h6 class="fw-bold mb-2">Post Information</h6>
                                    <div class="small text-muted">
                                        <div class="mb-1">
                                            <i class="fas fa-calendar me-1"></i>
                                            Created: <?php echo date('M j, Y g:i A', strtotime($post['created_at'])); ?>
                                        </div>
                                        <?php if ($post['updated_at'] != $post['created_at']): ?>
                                            <div class="mb-1">
                                                <i class="fas fa-edit me-1"></i>
                                                Updated: <?php echo date('M j, Y g:i A', strtotime($post['updated_at'])); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <i class="fas fa-hashtag me-1"></i>
                                            Post ID: <?php echo $post['id']; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fas fa-save me-2"></i>Update Post
                                    </button>
                                    
                                    <?php if ($post['status'] == 'published'): ?>
                                        <a href="../public/post.php?slug=<?php echo urlencode($post['slug']); ?>" 
                                           class="btn btn-outline-info" target="_blank">
                                            <i class="fas fa-external-link-alt me-2"></i>View Live Post
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="posts.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Back to Posts
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// New image preview
function previewNewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('newPreview').src = e.target.result;
            document.getElementById('newImagePreview').style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function removeNewPreview() {
    document.getElementById('newImagePreview').style.display = 'none';
    document.getElementById('featured_image').value = '';
}

// Auto-resize textarea
document.getElementById('content').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = this.scrollHeight + 'px';
});

// Initial resize
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('content');
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
});
</script>

<?php include '../includes/footer.php'; ?>