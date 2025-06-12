<?php
require_once 'config.php';

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $employee_name = trim($_POST['employee_name']);
    $employee_id = trim($_POST['employee_id']);
    $department = trim($_POST['department']);
    $hours_worked = floatval($_POST['hours_worked']);
    $hourly_rate = floatval($_POST['hourly_rate']);
    $work_date = $_POST['work_date'];
    $notes = trim($_POST['notes']);
    
    // Basic validation
    if (empty($employee_name) || empty($employee_id) || empty($department) || 
        $hours_worked <= 0 || $hourly_rate <= 0 || empty($work_date)) {
        $error = "Please fill in all required fields with valid data.";
    } else {
        try {
            $sql = "INSERT INTO employees (employee_name, employee_id, department, hours_worked, hourly_rate, work_date, notes) 
                    VALUES (:employee_name, :employee_id, :department, :hours_worked, :hourly_rate, :work_date, :notes)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':employee_name', $employee_name);
            $stmt->bindParam(':employee_id', $employee_id);
            $stmt->bindParam(':department', $department);
            $stmt->bindParam(':hours_worked', $hours_worked);
            $stmt->bindParam(':hourly_rate', $hourly_rate);
            $stmt->bindParam(':work_date', $work_date);
            $stmt->bindParam(':notes', $notes);
            
            if ($stmt->execute()) {
                $message = "Employee data added successfully!";
                // Clear form data
                $_POST = array();
            }
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Employee ID already exists. Please use a unique Employee ID.";
            } else {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Employee - Employee Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-users me-2"></i>
                Employee Portal
            </a>
            <nav class="navbar-nav ms-auto">
                <a class="nav-link active" href="add_employee.php">
                    <i class="fas fa-plus me-1"></i>Add Employee
                </a>
                <a class="nav-link" href="view_employees.php">
                    <i class="fas fa-list me-1"></i>View Employees
                </a>
            </nav>
        </div>
    </header>

    <main class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h2 class="card-title mb-0">
                            <i class="fas fa-user-plus me-2"></i>Add Employee Information
                        </h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="add_employee.php" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="employee_name" class="form-label">
                                        <i class="fas fa-user me-1"></i>Employee Name *
                                    </label>
                                    <input type="text" class="form-control" id="employee_name" 
                                           name="employee_name" required maxlength="100"
                                           value="<?php echo isset($_POST['employee_name']) ? htmlspecialchars($_POST['employee_name']) : ''; ?>">
                                    <div class="invalid-feedback">Please provide a valid employee name.</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="employee_id" class="form-label">
                                        <i class="fas fa-id-badge me-1"></i>Employee ID *
                                    </label>
                                    <input type="text" class="form-control" id="employee_id" 
                                           name="employee_id" required maxlength="20"
                                           value="<?php echo isset($_POST['employee_id']) ? htmlspecialchars($_POST['employee_id']) : ''; ?>">
                                    <div class="invalid-feedback">Please provide a valid employee ID.</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="department" class="form-label">
                                        <i class="fas fa-building me-1"></i>Department *
                                    </label>
                                    <select class="form-select" id="department" name="department" required>
                                        <option value="">Select Department</option>
                                        <option value="IT" <?php echo (isset($_POST['department']) && $_POST['department'] == 'IT') ? 'selected' : ''; ?>>IT</option>
                                        <option value="HR" <?php echo (isset($_POST['department']) && $_POST['department'] == 'HR') ? 'selected' : ''; ?>>HR</option>
                                        <option value="Finance" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Finance') ? 'selected' : ''; ?>>Finance</option>
                                        <option value="Marketing" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Marketing') ? 'selected' : ''; ?>>Marketing</option>
                                        <option value="Operations" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Operations') ? 'selected' : ''; ?>>Operations</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a department.</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="work_date" class="form-label">
                                        <i class="fas fa-calendar me-1"></i>Work Date *
                                    </label>
                                    <input type="date" class="form-control" id="work_date" 
                                           name="work_date" required max="<?php echo date('Y-m-d'); ?>"
                                           value="<?php echo isset($_POST['work_date']) ? htmlspecialchars($_POST['work_date']) : date('Y-m-d'); ?>">
                                    <div class="invalid-feedback">Please provide a valid work date.</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="hours_worked" class="form-label">
                                        <i class="fas fa-clock me-1"></i>Hours Worked *
                                    </label>
                                    <input type="number" class="form-control" id="hours_worked" 
                                           name="hours_worked" required min="0.1" max="24" step="0.1"
                                           value="<?php echo isset($_POST['hours_worked']) ? htmlspecialchars($_POST['hours_worked']) : ''; ?>">
                                    <div class="invalid-feedback">Please provide valid hours worked (0.1-24).</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="hourly_rate" class="form-label">
                                        <i class="fas fa-dollar-sign me-1"></i>Hourly Rate ($) *
                                    </label>
                                    <input type="number" class="form-control" id="hourly_rate" 
                                           name="hourly_rate" required min="1" max="999.99" step="0.01"
                                           value="<?php echo isset($_POST['hourly_rate']) ? htmlspecialchars($_POST['hourly_rate']) : ''; ?>">
                                    <div class="invalid-feedback">Please provide a valid hourly rate.</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">
                                    <i class="fas fa-sticky-note me-1"></i>Notes (Optional)
                                </label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" 
                                          maxlength="500"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                                <div class="form-text">Additional information about the work performed.</div>
                            </div>

                            <div class="d-grip gap-2 d-md-flex justify-content-md-end">
                                <button type="reset" class="btn btn-outline-secondary me-2">
                                    <i class="fas fa-undo me-1"></i>Reset
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Add Employee
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">
                <i class="fas fa-copyright me-1"></i>2025 Employee Portal. 
                Built with <i class="fas fa-heart text-danger"></i> using PHP & Bootstrap.
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Bootstrap form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    </script>
</body>
</html>