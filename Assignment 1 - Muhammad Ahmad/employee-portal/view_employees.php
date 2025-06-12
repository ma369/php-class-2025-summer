<?php
require_once 'config.php';

// Fetch all employees from database
try {
    $sql = "SELECT * FROM employees ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error fetching data: " . $e->getMessage();
    $employees = [];
}

// Calculate totals
$total_hours = 0;
$total_pay = 0;
foreach ($employees as $employee) {
    $total_hours += $employee['hours_worked'];
    $total_pay += ($employee['hours_worked'] * $employee['hourly_rate']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Employees - Employee Portal</title>
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
                <a class="nav-link" href="add_employee.php">
                    <i class="fas fa-plus me-1"></i>Add Employee
                </a>
                <a class="nav-link active" href="view_employees.php">
                    <i class="fas fa-list me-1"></i>View Employees
                </a>
            </nav>
        </div>
    </header>

    <main class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h2 class="card-title mb-0">
                            <i class="fas fa-table me-2"></i>Employee Records
                        </h2>
                        <span class="badge bg-light text-dark fs-6">
                            Total Records: <?php echo count($employees); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($employees)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">No Employee Records Found</h4>
                                <p class="text-muted">Get started by adding your first employee record.</p>
                                <a href="add_employee.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Add Employee
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Summary Cards -->
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="card bg-info text-white">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <h5 class="card-title">Total Employees</h5>
                                                    <h3><?php echo count($employees); ?></h3>
                                                </div>
                                                <i class="fas fa-users fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-success text-white">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <h5 class="card-title">Total Hours</h5>
                                                    <h3><?php echo number_format($total_hours, 1); ?></h3>
                                                </div>
                                                <i class="fas fa-clock fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-warning text-white">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <h5 class="card-title">Total Pay</h5>
                                                    <h3>$<?php echo number_format($total_pay, 2); ?></h3>
                                                </div>
                                                <i class="fas fa-dollar-sign fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Employee Table -->
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th><i class="fas fa-hashtag me-1"></i>ID</th>
                                            <th><i class="fas fa-user me-1"></i>Name</th>
                                            <th><i class="fas fa-id-badge me-1"></i>Employee ID</th>
                                            <th><i class="fas fa-building me-1"></i>Department</th>
                                            <th><i class="fas fa-calendar me-1"></i>Work Date</th>
                                            <th><i class="fas fa-clock me-1"></i>Hours</th>
                                            <th><i class="fas fa-dollar-sign me-1"></i>Rate/Hr</th>
                                            <th><i class="fas fa-calculator me-1"></i>Total Pay</th>
                                            <th><i class="fas fa-sticky-note me-1"></i>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($employees as $employee): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($employee['id']); ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($employee['employee_name']); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?php echo htmlspecialchars($employee['employee_id']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        <?php echo htmlspecialchars($employee['department']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($employee['work_date'])); ?></td>
                                                <td>
                                                    <span class="text-success fw-bold">
                                                        <?php echo number_format($employee['hours_worked'], 1); ?>h
                                                    </span>
                                                </td>
                                                <td>$<?php echo number_format($employee['hourly_rate'], 2); ?></td>
                                                <td>
                                                    <span class="text-success fw-bold">
                                                        $<?php echo number_format($employee['hours_worked'] * $employee['hourly_rate'], 2); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($employee['notes'])): ?>
                                                        <span class="text-muted" data-bs-toggle="tooltip" 
                                                              title="<?php echo htmlspecialchars($employee['notes']); ?>">
                                                            <i class="fas fa-comment"></i>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-between mt-3">
                                <a href="add_employee.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Add New Employee
                                </a>
                                <button onclick="window.print()" class="btn btn-outline-secondary">
                                    <i class="fas fa-print me-1"></i>Print Report
                                </button>
                            </div>
                        <?php endif; ?>
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
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>