<?php
// modules/loans/repay.php - Modern Loan Repayment
session_start();
include_once '../../config/database.php';
include_once '../../includes/auth.php';
include_once '../../includes/validation.php';

check_auth();
$user_id = (int)$_SESSION['user_id'];

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_csrf_token($_POST['csrf_token'] ?? '');

    $loan_id = (int)($_POST['loan_id'] ?? 0);
    $amount = $_POST['amount'] ?? 0;

    if (!validate_amount($amount)) {
        $message = "Please enter a valid payment amount.";
        $message_type = 'danger';
    } else {
        $amount = (float)$amount;

        // SECURITY (fixes an IDOR-class bug): the original query updated
        // ANY loan by ID with no check that it belonged to the logged-in
        // user -- meaning any authenticated customer could pay down (or,
        // combined with the missing bounds check below, manipulate) any
        // other user's loan simply by guessing/incrementing the ID. The
        // "AND user_id = ?" clause here is the ownership check; it also
        // now refuses to reduce the balance below zero.
        $stmt = mysqli_prepare($conn, "SELECT remaining_balance FROM loans WHERE id = ? AND user_id = ? AND status = 'approved'");
        mysqli_stmt_bind_param($stmt, "ii", $loan_id, $user_id);
        mysqli_stmt_execute($stmt);
        $loan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$loan) {
            $message = "That loan wasn't found on your account.";
            $message_type = 'danger';
        } elseif ($amount > (float)$loan['remaining_balance']) {
            $message = "Payment amount exceeds the remaining balance.";
            $message_type = 'danger';
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE loans SET remaining_balance = remaining_balance - ? WHERE id = ? AND user_id = ?");
            mysqli_stmt_bind_param($stmt, "dii", $amount, $loan_id, $user_id);
            $result = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if ($result) {
                $message = "Payment of $" . number_format($amount, 2) . " made successfully!";
                $message_type = 'success';
            } else {
                error_log("Loan repayment failed: " . mysqli_error($conn));
                $message = "Payment failed. Please try again.";
                $message_type = 'danger';
            }
        }
    }
}

$stmt = mysqli_prepare($conn, "SELECT * FROM loans WHERE user_id = ? AND status = 'approved'");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$loans_result = mysqli_stmt_get_result($stmt);
$active_loans = mysqli_fetch_all($loans_result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$csrf = csrf_token();
function e($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repay Loan - Ethio Microfinance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-modern fixed-top">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php"><i class="bi bi-bank2"></i> EthioMF</a>
        </div>
    </nav>

    <div class="dashboard-wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card-modern fade-in-up">
                        <div class="card-header">
                            <h5 class="mb-0 fw-700"><i class="bi bi-arrow-repeat text-warning"></i> Repay Loan</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($message): ?>
                                <div class="alert alert-<?php echo e($message_type); ?> alert-auto">
                                    <i class="bi bi-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                                    <?php echo e($message); ?>
                                </div>
                            <?php endif; ?>

                            <?php if (count($active_loans) > 0): ?>
                                <form method="POST" class="needs-validation" novalidate>
                                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
                                    <div class="form-modern mb-4">
                                        <label class="form-label"><i class="bi bi-file-text"></i> Select Loan</label>
                                        <select name="loan_id" class="form-select" required>
                                            <option value="">Select a loan...</option>
                                            <?php foreach ($active_loans as $loan): ?>
                                                <option value="<?php echo (int)$loan['id']; ?>">
                                                    Loan #<?php echo (int)$loan['id']; ?> - $<?php echo number_format((float)$loan['amount'], 2); ?>
                                                    (Balance: $<?php echo number_format((float)$loan['remaining_balance'], 2); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-modern mb-4">
                                        <label class="form-label"><i class="bi bi-currency-dollar"></i> Payment Amount</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" name="amount" class="form-control" placeholder="0.00" min="0.01" step="0.01" required>
                                        </div>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-warning" style="background: linear-gradient(135deg, #FFC107 0%, #E0A800 100%); border: none; color: #000;">
                                            <i class="bi bi-arrow-repeat"></i> Make Payment
                                        </button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> You don't have any active loans to repay.
                                    <a href="apply.php" class="fw-bold">Apply for a loan</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/main.js"></script>
</body>
</html>
