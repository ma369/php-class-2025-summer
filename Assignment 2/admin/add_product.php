<?php
require_once '../config.php';
requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $category = trim($_POST['category']);
    
    // Validation
    if (empty($name) || empty($description) || $price <= 0 || empty($category)) {
        $error = 'Please fill in all required fields with valid data.';
    } else {
        $image_name = null;
        
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['image']['type'], $allowed_types)) {
                $error = 'Please upload a valid image file (JPEG, PNG, or GIF).';
            } elseif ($_FILES['image']['size'] > $max_size) {
                $error = 'Image file is too large. Maximum size is 5MB.';
            } else {
                // Create uploads directory if it doesn't exist
                if (!file_exists('../uploads')) {
                    mkdir('../uploads', 0777, true);
                }
                
                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $image_name = uniqid() . '.' . $file_extension;
                $upload_path = '../uploads/' . $image_name;
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $error = 'Failed to upload image. Please try again.';
                }
            }
        }
        
        if (empty($error)) {
            // Insert product into database
            $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category, image, user_id) VALUES (?, ?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$name, $description, $price, $category, $image_name, $_SESSION['user_id']])) {
                header('Location: products.php?added=1');
                exit();
            } else {
                $error = 'Failed to add product. Please try again.';
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Add New Product</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Product Name *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="category" class="form-label">Category *</label>
                                <select class="form-control" id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="Electronics" <?php echo ($_POST['category'] ?? '') == 'Electronics' ? 'selected' : ''; ?>>Electronics</option>
                                    <option value="Clothing" <?php echo ($_POST['category'] ?? '') == 'Clothing' ? 'selected' : ''; ?>>Clothing</option>
                                    <option value="Books" <?php echo ($_POST['category'] ?? '') == 'Books' ? 'selected' : ''; ?>>Books</option>
                                    <option value="Home & Garden" <?php echo ($_POST['category'] ?? '') == 'Home & Garden' ? 'selected' : ''; ?>>Home & Garden</option>
                                    <option value="Sports" <?php echo ($_POST['category'] ?? '') == 'Sports' ? 'selected' : ''; ?>>Sports</option>
                                    <option value="Other" <?php echo ($_POST['category'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description *</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="price" class="form-label">Price ($) *</label>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" 
                                       value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="image" class="form-label">Product Image</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <div class="form-text">Maximum file size: 5MB. Supported formats: JPEG, PNG, GIF</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="products.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
