<?php
// modules/loans/repay.php - Modern Loan Repayment
session_start();
include_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../users/login.php");
    exit();
}

$message = '';
$message_type = '';

$user_id = $_SESSION['user_id'];
$loans_query = "SELECT * FROM loans WHERE user_id = $user_id AND status = 'approved'";
$loans = mysqli_query($conn, $loans_query);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $loan_id = $_POST['loan_id'] ?? 0;
    $amount = $_POST['amount'] ?? 0;
    
    $query = "UPDATE loans SET remaining_balance = remaining_balance - $amount WHERE id = $loan_id";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        $message = "Payment of $" . number_format($amount, 2) . " made successfully!";
        $message_type = 'success';
    } else {
        $message = "Payment failed: " . mysqli_error($conn);
        $message_type = 'danger';
    }
}
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
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-bank2"></i> EthioMF
            </a>
        </div>
    </nav>

    <div class="dashboard-wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card-modern fade-in-up">
                        <div class="card-header">
                            <h5 class="mb-0 fw-700">
                                <i class="bi bi-arrow-repeat text-warning"></i> Repay Loan
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($message): ?>
                                <div class="alert alert-<?php echo $message_type; ?> alert-auto">
                                    <i class="bi bi-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                                    <?php echo $message; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (mysqli_num_rows($loans) > 0): ?>
                                <form method="POST" class="needs-validation" novalidate>
                                    <div class="form-modern mb-4">
                                        <label class="form-label">
                                            <i class="bi bi-file-text"></i> Select Loan
                                        </label>
                                        <select name="loan_id" class="form-select" required>
                                            <option value="">Select a loan...</option>
                                            <?php while($loan = mysqli_fetch_assoc($loans)): ?>
                                                <option value="<?php echo $loan['id']; ?>">
                                                    Loan #<?php echo $loan['id']; ?> - $<?php echo number_format($loan['amount'], 2); ?> 
                                                    (Balance: $<?php echo number_format($loan['remaining_balance'], 2); ?>)
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>

                                    <div class="form-modern mb-4">
                                        <label class="form-label">
                                            <i class="bi bi-currency-dollar"></i> Payment Amount
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" name="amount" class="form-control" 
                                                   placeholder="0.00" min="0.01" step="0.01" required>
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
                                    <i class="bi bi-info-circle"></i>
                                    You don't have any active loans to repay.
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