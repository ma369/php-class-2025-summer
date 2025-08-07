<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Application</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../public/index.php">CRUD App</a>
            <div class="navbar-nav ms-auto">
                <?php if (isLoggedIn()): ?>
                    <a class="nav-link" href="../admin/dashboard.php">Dashboard</a>
                    <a class="nav-link" href="../admin/products.php">Products</a>
                    <a class="nav-link" href="../admin/profile.php">Profile</a>
                    <a class="nav-link" href="../admin/logout.php">Logout</a>
                <?php else: ?>
                    <a class="nav-link" href="../public/login.php">Login</a>
                    <a class="nav-link" href="../public/register.php">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <main class="container mt-4">