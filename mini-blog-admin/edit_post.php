<?php
require_once 'config.php';
require_once 'header.php';
require_once 'footer.php';
requireLogin();

$post_id = $_GET['id'] ?? 0;
if (!$post_id) {
    header('Location: posts.php');
    exit();
}

$db = new Database();
$conn = $db->connect();

// Fetch existing post
$stmt = $conn->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    header('Location: posts.php');
    exit();
}

if ($_POST) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $image_name = $post['image']; // Keep existing image by default
    
    // Validation
    $errors = [];
    if (empty($title)) $errors[] = "Title is required";
    if (empty($content)) $errors[] = "Content is required";
    
    // Handle new image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $errors[] = "Invalid image type. Only JPEG, PNG, and GIF allowed.";
        }
        
        if ($_FILES['image']['size'] > $max_size) {
            $errors[] = "Image size too large. Maximum 5MB allowed.";
        }
        
        if (empty($errors)) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_image_name = time() . '_' . $_FILES['image']['name'];
            $upload_path = $upload_dir . $new_image_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Delete old image
                if ($post['image'] && file_exists($upload_dir . $post['image'])) {
                    unlink($upload_dir . $post['image']);
                }
                $image_name = $new_image_name;
            } else {
                $errors[] = "Failed to upload image";
            }
        }
    }
    
    // Update database
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("UPDATE posts SET title = ?, content = ?, image = ? WHERE id = ?");
            if ($stmt->execute([$title, $content, $image_name, $post_id])) {
                header('Location: posts.php');
                exit();
            } else {
                $errors[] = "Database error occurred";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

renderHeader("Edit Post");
?>

<div class='container'>
    <div class='card'>
        <h1>Edit Post</h1>
        
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class='alert alert-error'>
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method='POST' enctype='multipart/form-data'>
            <div class='form-group'>
                <label>Title (Required):</label>
                <input type='text' name='title' value='<?php echo htmlspecialchars($post['title']); ?>' required>
            </div>
            
            <div class='form-group'>
                <label>Content (Required):</label>
                <textarea name='content' rows='10' required><?php echo htmlspecialchars($post['content']); ?></textarea>
            </div>
            
            <div class='form-group'>
                <label>Current Image:</label>
                <?php if ($post['image']): ?>
                    <br><img src='uploads/<?php echo htmlspecialchars($post['image']); ?>' style='max-width: 200px; margin: 0.5rem 0; border-radius: 3px;'>
                <?php else: ?>
                    <br><em>No image uploaded</em>
                <?php endif; ?>
            </div>
            
            <div class='form-group'>
                <label>Replace Image (Optional):</label>
                <input type='file' name='image' accept='image/*'>
                <small style='color: #666;'>Leave empty to keep current image. Supported: JPEG, PNG, GIF. Max: 5MB</small>
            </div>
            
            <button type='submit' class='btn'>Update Post</button>
            <a href='posts.php' class='btn btn-secondary' style='margin-left: 1rem;'>Cancel</a>
        </form>
    </div>
</div>

<?php renderFooter(); ?>