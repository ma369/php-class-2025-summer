<?php
require_once 'config.php';
require_once 'header.php';
require_once 'footer.php';
requireLogin();

if ($_POST) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $image_name = '';
    
    // Validation
    $errors = [];
    if (empty($title)) $errors[] = "Title is required";
    if (empty($content)) $errors[] = "Content is required";
    
    // Handle image upload
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
            
            $image_name = time() . '_' . $_FILES['image']['name'];
            $upload_path = $upload_dir . $image_name;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $errors[] = "Failed to upload image";
            }
        }
    }
    
    // Insert into database
    if (empty($errors)) {
        $db = new Database();
        $conn = $db->connect();
        
        try {
            $stmt = $conn->prepare("INSERT INTO posts (title, content, image, created_at) VALUES (?, ?, ?, NOW())");
            if ($stmt->execute([$title, $content, $image_name])) {
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

renderHeader("Create Post");
?>

<div class='container'>
    <div class='card'>
        <h1>Create New Post</h1>
        
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
                <input type='text' name='title' value='<?php echo htmlspecialchars($title ?? ''); ?>' required>
            </div>
            
            <div class='form-group'>
                <label>Content (Required):</label>
                <textarea name='content' rows='10' required><?php echo htmlspecialchars($content ?? ''); ?></textarea>
            </div>
            
            <div class='form-group'>
                <label>Image (Optional):</label>
                <input type='file' name='image' accept='image/*'>
                <small style='color: #666;'>Supported formats: JPEG, PNG, GIF. Maximum size: 5MB</small>
            </div>
            
            <button type='submit' class='btn'>Create Post</button>
            <a href='posts.php' class='btn btn-secondary' style='margin-left: 1rem;'>Cancel</a>
        </form>
    </div>
</div>

<?php renderFooter(); ?>