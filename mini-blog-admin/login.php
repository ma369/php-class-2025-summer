<?php
require_once 'config.php';
require_once 'header.php';
require_once 'footer.php';

if ($_POST) {
    $db = new Database();
    $conn = $db->connect();
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username && $password) {
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: posts.php');
            exit();
        } else {
            $error = "Invalid username or password";
        }
    } else {
        $error = "Please fill in all fields";
    }
}

renderHeader("Login");
?>

<div class='container'>
    <div class='card' style='max-width: 400px; margin: 0 auto;'>
        <h2>Blog Admin Login</h2>
        
        <?php if (isset($error)): ?>
            <div class='alert alert-error'><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method='POST'>
            <div class='form-group'>
                <label>Username:</label>
                <input type='text' name='username' required>
            </div>
            <div class='form-group'>
                <label>Password:</label>
                <input type='password' name='password' required>
            </div>
            <button type='submit' class='btn'>Login</button>
        </form>
        
        <p style='margin-top: 1rem; color: #666; font-size: 0.9em;'>
            <strong>Default Login:</strong><br>
            Username: admin<br>
            Password: admin123
        </p>
    </div>
</div>

<?php renderFooter(); ?>