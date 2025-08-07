<?php
require_once '../config.php';
requireLogin();

$error = '';
$success = '';

// Get product ID
$product_id = (int)($_GET['id'] ?? 0);

// Fetch product details
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND user_id = ?");
$stmt->execute([$product_id, $_SESSION['user_id']]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $category = trim($_POST['category']);
    
    // Validation
    if (empty($name) || empty($description) || $price <= 0 || empty($category)) {
        $error = 'Please fill in all required fields with valid data.';
    } else {
        $image_name = $product['image']; // Keep existing image by default
        
        // Handle new image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['image']['type'], $allowed_types)) {
                $error = 'Please upload a valid image file (JPEG, PNG, or GIF).';
            } elseif ($_FILES['image']['size'] > $max_size) {
                $error = 'Image file is too large. Maximum size is 5MB.';
            } else {
                // Delete old image if exists
                if ($product['image'] && file_exists('../uploads/' . $product['image'])) {
                    unlink('../uploads/' . $product['image']);
                }
                
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
        
        // Handle image removal
        if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
            if ($product['image'] && file_exists('../uploads/' . $product['image'])) {
                unlink('../uploads/' . $product['image']);
            }
            $image_name = null;
        }
        
        if (empty($error)) {
            // Update product in database
            $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, category = ?, image = ? WHERE id = ? AND user_id = ?");
            
            if ($stmt->execute([$name, $description, $price, $category, $image_name, $product_id, $_SESSION['user_id']])) {
                header('Location: products.php?updated=1');
                exit();
            } else {
                $error = 'Failed to update product. Please try again.';
            }
        }
    }
} else {
    // Pre-populate form with existing data
    $_POST['name'] = $product['name'];
    $_POST['description'] = $product['description'];
    $_POST['price'] = $product['price'];
    $_POST['category'] = $product['category'];
}

include '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Edit Product</h4>
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
                                <?php if ($product['image']): ?>
                                    <div class="mb-2">
                                        <img src="../uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                             alt="Current Image" style="max-width: 150px; max-height: 150px;" class="img-thumbnail">
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="remove_image">
                                            <label class="form-check-label" for="remove_image">
                                                Remove current image
                                            </label>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <div class="form-text">Maximum file size: 5MB. Supported formats: JPEG, PNG, GIF</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="products.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>