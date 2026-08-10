<?php
// modules/loans/approve.php - Modern Loan Approval
session_start();
include_once '../../config/database.php';
include_once '../../includes/auth.php';
include_once '../../includes/validation.php';

// SECURITY: this file previously contained its OWN independent admin
// bypass -- "?force=true" granted admin role to any visitor, completely
// separate from the "?bypass=true" backdoor already removed from
// includes/auth.php and admin/dashboard.php. Same severity, different
// file. Removed entirely; check_auth('admin') is the only gate now.
check_auth('admin');

$message = '';
$message_type = '';

if (isset($_GET['id'])) {
    // CSRF: approve/reject are state-changing actions reachable via a GET
    // link, which is inherently forgeable (an attacker's page could embed
    // <img src="...?id=5&action=approve"> and trigger it via an admin's
    // browser). Requiring the same CSRF token pattern already used by
    // admin/dashboard.php's make_admin/delete links closes that.
    require_csrf_token($_GET['csrf_token'] ?? '');

    $loan_id = (int)$_GET['id'];
    $action = ($_GET['action'] ?? 'approve') === 'reject' ? 'reject' : 'approve';

    if ($action == 'approve') {
        $stmt = mysqli_prepare($conn, "UPDATE loans SET status = 'approved', approval_date = NOW() WHERE id = ?");
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE loans SET status = 'rejected' WHERE id = ?");
    }
    mysqli_stmt_bind_param($stmt, "i", $loan_id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($result) {
        $message = "Loan " . ($action === 'approve' ? 'approved' : 'rejected') . " successfully!";
        $message_type = 'success';
    } else {
        error_log("Loan approval action failed: " . mysqli_error($conn));
        $message = "Action failed. Please try again.";
        $message_type = 'danger';
    }
}

$pending_loans = mysqli_query($conn, "SELECT * FROM loans WHERE status = 'pending'");
$csrf = csrf_token();
function e($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
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
            <a class="navbar-brand" href="../../admin/dashboard.php"><i class="bi bi-shield-lock"></i> Admin Panel</a>
        </div>
    </nav>

    <div class="dashboard-wrapper">
        <div class="container">
            <div class="card-modern fade-in-up">
                <div class="card-header">
                    <h5 class="mb-0 fw-700"><i class="bi bi-check-circle text-success"></i> Loan Approval System</h5>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo e($message_type); ?> alert-auto">
                            <i class="bi bi-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                            <?php echo e($message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (mysqli_num_rows($pending_loans) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-modern">
                                <thead><tr><th>Loan ID</th><th>User ID</th><th>Amount</th><th>Rate</th><th>Date</th><th>Reason</th><th>Actions</th></tr></thead>
                                <tbody>
                                    <?php while ($loan = mysqli_fetch_assoc($pending_loans)): ?>
                                    <tr>
                                        <td><strong>#<?php echo (int)$loan['id']; ?></strong></td>
                                        <td><?php echo (int)$loan['user_id']; ?></td>
                                        <td><strong>$<?php echo number_format((float)$loan['amount'], 2); ?></strong></td>
                                        <td><?php echo e($loan['interest_rate']); ?>%</td>
                                        <td><?php echo e($loan['application_date']); ?></td>
                                        <td><?php echo e(substr($loan['reason'], 0, 30)); ?>...</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="?id=<?php echo (int)$loan['id']; ?>&action=approve&csrf_token=<?php echo urlencode($csrf); ?>"
                                                   class="btn btn-sm btn-gradient-success" style="background: linear-gradient(135deg, #00D4AA 0%, #00B894 100%);">
                                                    <i class="bi bi-check"></i> Approve
                                                </a>
                                                <a href="?id=<?php echo (int)$loan['id']; ?>&action=reject&csrf_token=<?php echo urlencode($csrf); ?>"
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
                        <div class="alert alert-info"><i class="bi bi-info-circle"></i> No pending loan applications at this time.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/main.js"></script>
</body>
</html>
