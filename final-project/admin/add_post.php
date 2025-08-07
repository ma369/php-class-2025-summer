<?php
require_once '../config.php';
requireLogin();

$error = '';
$success = '';

// Simple image upload function
function uploadImage($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Please upload a valid image file (JPEG, PNG, or GIF).');
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception('Image file is too large. Maximum size is 5MB.');
    }
    
    // Create uploads directory
    if (!file_exists('../uploads')) {
        mkdir('../uploads', 0777, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'post_' . uniqid() . '.' . $extension;
    $uploadPath = '../uploads/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Failed to upload image.');
    }
    
    return $filename;
}

// Simple slug creation
function makeSlug($title, $pdo) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    $originalSlug = $slug;
    $counter = 1;
    
    // Make sure slug is unique
    while (true) {
        $stmt = $pdo->prepare("SELECT id FROM posts WHERE slug = ?");
        $stmt->execute([$slug]);
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
        
        // Generate slug
        $slug = makeSlug($title, $pdo);
        
        // Handle image upload
        $featuredImage = null;
        if (isset($_FILES['featured_image'])) {
            $featuredImage = uploadImage($_FILES['featured_image']);
        }
        
        // Auto-generate excerpt if empty
        if (empty($excerpt)) {
            $excerpt = substr(strip_tags($content), 0, 200) . '...';
        }
        
        // Insert post
        $stmt = $pdo->prepare("
            INSERT INTO posts (title, slug, content, excerpt, featured_image, author_id, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        if ($stmt->execute([$title, $slug, $content, $excerpt, $featuredImage, $_SESSION['user_id'], $status])) {
            $_SESSION['success_message'] = 'Post ' . ($status == 'published' ? 'published' : 'saved') . ' successfully!';
            header('Location: posts.php');
            exit();
        } else {
            throw new Exception('Failed to create post.');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$page_title = 'Add New Post';
include '../includes/header.php';
?>

<div class="container mt-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">
                    <i class="fas fa-plus me-2 text-primary"></i>Create New Post
                </h1>
                <a href="posts.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Posts
                </a>
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
                                           placeholder="Enter your post title" 
                                           required>
                                </div>

                                <!-- Excerpt -->
                                <div class="mb-3">
                                    <label for="excerpt" class="form-label fw-bold">
                                        <i class="fas fa-quote-left me-1"></i>Excerpt (Optional)
                                    </label>
                                    <textarea class="form-control" 
                                              id="excerpt" 
                                              name="excerpt" 
                                              rows="3" 
                                              placeholder="Brief summary (auto-generated if left empty)"><?php echo htmlspecialchars($_POST['excerpt'] ?? ''); ?></textarea>
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
                                              placeholder="Write your post content here..."
                                              required><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sidebar -->
                    <div class="col-lg-4">
                        <div class="card">
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
                                        <option value="draft" <?php echo ($_POST['status'] ?? 'draft') == 'draft' ? 'selected' : ''; ?>>
                                            Draft
                                        </option>
                                        <option value="published" <?php echo ($_POST['status'] ?? '') == 'published' ? 'selected' : ''; ?>>
                                            Published
                                        </option>
                                    </select>
                                    <div class="form-text">Drafts are only visible to you</div>
                                </div>
                                
                                <!-- Featured Image -->
                                <div class="mb-4">
                                    <label for="featured_image" class="form-label fw-bold">
                                        <i class="fas fa-image me-1"></i>Featured Image
                                    </label>
                                    <input type="file" 
                                           class="form-control" 
                                           id="featured_image" 
                                           name="featured_image" 
                                           accept="image/*" 
                                           onchange="previewImage(this)">
                                    <div class="form-text">Max 5MB. JPEG, PNG, or GIF</div>
                                    
                                    <!-- Image Preview -->
                                    <div id="imagePreview" class="mt-3" style="display: none;">
                                        <img id="preview" src="" class="img-thumbnail" style="max-width: 100%; height: auto;">
                                        <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="removePreview()">
                                            <i class="fas fa-times me-1"></i>Remove
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fas fa-save me-2"></i>Save Post
                                    </button>
                                    <a href="posts.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-2"></i>Cancel
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
// Image preview
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
    document.getElementById('featured_image').value = '';
}

// Auto-resize textarea
document.getElementById('content').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = this.scrollHeight + 'px';
});
</script>

<?php include '../includes/footer.php'; ?>