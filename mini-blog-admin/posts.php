<?php
require_once 'config.php';
require_once 'header.php';
require_once 'footer.php';
requireLogin();

$db = new Database();
$conn = $db->connect();

// Handle deletion
if (isset($_GET['delete'])) {
    $post_id = $_GET['delete'];
    
    // Get image filename before deletion
    $stmt = $conn->prepare("SELECT image FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Delete from database
    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
    if ($stmt->execute([$post_id])) {
        // Delete image file if exists
        if ($post['image'] && file_exists("uploads/" . $post['image'])) {
            unlink("uploads/" . $post['image']);
        }
        $success = "Post deleted successfully";
    } else {
        $error = "Error deleting post";
    }
}

// Fetch all posts
$stmt = $conn->prepare("SELECT * FROM posts ORDER BY created_at DESC");
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

renderHeader("All Posts");
?>

<div class='container'>
    <?php if (isset($success)): ?>
        <div class='alert alert-success'><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class='alert alert-error'><?php echo $error; ?></div>
    <?php endif; ?>
    
    <h1>Blog Posts</h1>
    <a href='create_post.php' class='btn' style='margin-bottom: 2rem;'>Create New Post</a>
    
    <?php if (empty($posts)): ?>
        <div class='card'>
            <p>No posts found. <a href='create_post.php'>Create your first post</a>!</p>
        </div>
    <?php else: ?>
        <div class='post-grid'>
            <?php foreach ($posts as $post): ?>
                <div class='post-item'>
                    <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                    
                    <?php if ($post['image']): ?>
                        <img src='uploads/<?php echo htmlspecialchars($post['image']); ?>' alt='Post image'>
                    <?php endif; ?>
                    
                    <p><?php echo substr(htmlspecialchars($post['content']), 0, 150); ?>...</p>
                    <p><small>Created: <?php echo date('M d, Y H:i', strtotime($post['created_at'])); ?></small></p>
                    
                    <div class='post-actions'>
                        <a href='edit_post.php?id=<?php echo $post['id']; ?>' class='btn'>Edit</a>
                        <a href='posts.php?delete=<?php echo $post['id']; ?>' class='btn btn-danger' 
                           onclick='return confirm("Are you sure you want to delete this post?")'>Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php renderFooter(); ?>