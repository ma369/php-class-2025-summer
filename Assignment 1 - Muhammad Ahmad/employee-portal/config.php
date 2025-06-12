<?php
// Database configuration
$servername = "sql313.infinityfree.com";
$username = "if0_39217339";
$password = "";
$dbname = "if0_39217339_employee_portal";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Create table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_name VARCHAR(100) NOT NULL,
    employee_id VARCHAR(20) NOT NULL UNIQUE,
    department VARCHAR(50) NOT NULL,
    hours_worked DECIMAL(5,2) NOT NULL,
    hourly_rate DECIMAL(8,2) NOT NULL,
    work_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$pdo->exec($sql);
?>