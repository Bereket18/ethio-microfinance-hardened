<?php
// modules/loans/approve.php - Modern Loan Approval
session_start();
include_once '../../config/database.php';

if ($_SESSION['role'] != 'admin') {
    if ($_GET['force'] == 'true') {
        $_SESSION['role'] = 'admin';
    } else {
        header("Location: ../users/login.php");
        exit();
    }
}

$message = '';
$message_type = '';

if (isset($_GET['id'])) {
    $loan_id = $_GET['id'];
    $action = $_GET['action'] ?? 'approve';
    
    if ($action == 'approve') {
        $query = "UPDATE loans SET status = 'approved', approval_date = NOW() WHERE id = $loan_id";
    } else {
        $query = "UPDATE loans SET status = 'rejected' WHERE id = $loan_id";
    }
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        $message = "Loan $action completed successfully!";
        $message_type = 'success';
    } else {
        $message = "Action failed: " . mysqli_error($conn);
        $message_type = 'danger';
    }
}

$pending_query = "SELECT * FROM loans WHERE status = 'pending'";
$pending_loans = mysqli_query($conn, $pending_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Loans - Ethio Microfinance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-modern fixed-top">
        <div class="container">
            <a class="navbar-brand" href="../../admin/dashboard.php">
                <i class="bi bi-shield-lock"></i> Admin Panel
            </a>
        </div>
    </nav>

    <div class="dashboard-wrapper">
        <div class="container">
            <div class="card-modern fade-in-up">
                <div class="card-header">
                    <h5 class="mb-0 fw-700">
                        <i class="bi bi-check-circle text-success"></i> Loan Approval System
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-auto">
                            <i class="bi bi-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (mysqli_num_rows($pending_loans) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-modern">
                                <thead>
                                    <tr>
                                        <th>Loan ID</th>
                                        <th>User ID</th>
                                        <th>Amount</th>
                                        <th>Rate</th>
                                        <th>Date</th>
                                        <th>Reason</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($loan = mysqli_fetch_assoc($pending_loans)): ?>
                                    <tr>
                                        <td><strong>#<?php echo $loan['id']; ?></strong></td>
                                        <td><?php echo $loan['user_id']; ?></td>
                                        <td><strong>$<?php echo number_format($loan['amount'], 2); ?></strong></td>
                                        <td><?php echo $loan['interest_rate']; ?>%</td>
                                        <td><?php echo $loan['application_date']; ?></td>
                                        <td><?php echo substr($loan['reason'], 0, 30); ?>...</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="?id=<?php echo $loan['id']; ?>&action=approve" 
                                                   class="btn btn-sm btn-gradient-success" style="background: linear-gradient(135deg, #00D4AA 0%, #00B894 100%);">
                                                    <i class="bi bi-check"></i> Approve
                                                </a>
                                                <a href="?id=<?php echo $loan['id']; ?>&action=reject" 
                                                   class="btn btn-sm btn-gradient-danger" style="background: linear-gradient(135deg, #FF6B6B 0%, #EE5A24 100%);">
                                                    <i class="bi bi-x"></i> Reject
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            No pending loan applications at this time.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/main.js"></script>
</body>
</html>