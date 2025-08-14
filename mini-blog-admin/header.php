<?php
function renderHeader($title = "Blog Admin") {
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>$title</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: Arial, sans-serif; background: #f4f4f4; }
            .navbar { background: #333; color: white; padding: 1rem; }
            .navbar a { color: white; text-decoration: none; margin-right: 1rem; }
            .navbar a:hover { text-decoration: underline; }
            .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
            .card { background: white; padding: 2rem; margin: 1rem 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
            .form-group { margin: 1rem 0; }
            .form-group label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
            .form-group input, .form-group textarea { width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 3px; }
            .btn { padding: 0.5rem 1rem; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; text-decoration: none; display: inline-block; }
            .btn:hover { background: #0056b3; }
            .btn-danger { background: #dc3545; }
            .btn-danger:hover { background: #c82333; }
            .btn-secondary { background: #6c757d; }
            .btn-secondary:hover { background: #5a6268; }
            .alert { padding: 1rem; margin: 1rem 0; border-radius: 3px; }
            .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
            .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
            .post-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem; }
            .post-item { border: 1px solid #ddd; padding: 1rem; border-radius: 5px; background: white; }
            .post-item img { width: 100%; max-height: 200px; object-fit: cover; margin-bottom: 1rem; border-radius: 3px; }
            .post-actions { margin-top: 1rem; }
            .post-actions a { margin-right: 0.5rem; }
            textarea { resize: vertical; min-height: 120px; }
        </style>
    </head>
    <body>";
    
    if (isLoggedIn()) {
        echo "<nav class='navbar'>
            <a href='posts.php'>View Posts</a>
            <a href='create_post.php'>Create Post</a>
            <a href='api_posts.php'>External Posts</a>
            <a href='logout.php'>Logout</a>
        </nav>";
    }
}
?>