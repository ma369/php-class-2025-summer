<?php
require_once '../config.php';

// Destroy session and redirect to home
session_destroy();
header('Location: ../public/index.php?logged_out=1');
exit();
?>