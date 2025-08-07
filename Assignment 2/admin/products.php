<?php
require_once '../config.php';
requireLogin();

// Handle delete request
if (isset($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    
    // Check if product belongs to current user
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ? AND user_id = ?");
    $stmt->execute([$product_id, $_SESSION['user_id']]);
    $product = $stmt->fetch();
    
    if ($product) {
        // Delete image file if exists
        if ($product['image'] && file_exists('../uploads/' . $product['image'])) {
            unlink('../uploads/' . $product['image']);
        }
        
        // Delete product from database
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND user_id = ?");
        $stmt->execute([$product_id, $_SESSION['user_id']]);
        
        header('Location: products.php?deleted=1');
        exit();
    }
}

// Get all products for current user
$stmt = $pdo->prepare("SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$products = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>My Products</h1>
            <a href="add_product.php" class="btn btn-primary">Add New Product</a>
        </div>
    </div>
</div>

<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Product deleted successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($products): ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <?php if ($product['image']): ?>
                                        <img src="../uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                             alt="Product Image" class="product-image">
                                    <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center product-image">
                                            <small class="text-muted">No Image</small>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars(substr($product['description'], 0, 50)) . '...'; ?></td>
                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                <td>$<?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo date('M j, Y', strtotime($product['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">Edit</a>
                                        <a href="products.php?delete=<?php echo $product['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <h3 class="text-muted">No Products Yet</h3>
            <p class="text-muted mb-4">You haven't created any products yet. Get started by adding your first product!</p>
            <a href="add_product.php" class="btn btn-primary">Add Your First Product</a>
        </div>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>