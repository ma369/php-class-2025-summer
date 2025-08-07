<?php
require_once '../config.php';

// Simple logout function
function doLogout() {
    // Log the logout for security
    if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
        error_log("User logout: " . $_SESSION['username'] . " at " . date('Y-m-d H:i:s'));
    }
    
    // Clear all session data
    $_SESSION = array();
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
    
    return true;
}

// Perform logout
try {
    doLogout();
    
    // Start new session for success message
    session_start();
    $_SESSION['success_message'] = 'You have been successfully logged out.';
    
    header('Location: ../public/index.php?logged_out=1');
    exit();
    
} catch (Exception $e) {
    // Log error but still redirect
    error_log("Logout error: " . $e->getMessage());
    header('Location: ../public/index.php?logout_error=1');
    exit();
}
?>