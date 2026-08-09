<?php
// modules/loans/apply.php - Modern Loan Application
session_start();
include_once '../../config/database.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'] ?? 1;
    $amount = $_POST['amount'] ?? 0;
    $reason = $_POST['reason'] ?? '';
    
    $query = "INSERT INTO loans (user_id, amount, interest_rate, status, application_date, remaining_balance, reason) 
              VALUES ($user_id, $amount, 12.5, 'pending', NOW(), $amount, '$reason')";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        $message = "Loan application submitted successfully! Application ID: " . mysqli_insert_id($conn);
        $message_type = 'success';
    } else {
        $message = "Application failed: " . mysqli_error($conn);
        $message_type = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Loan - Ethio Microfinance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-modern fixed-top">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-bank2"></i> EthioMF
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="apply.php">
                            <i class="bi bi-file-earmark-plus"></i> Apply Loan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../../index.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="dashboard-wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card-modern fade-in-up">
                        <div class="card-header">
                            <h5 class="mb-0 fw-700">
                                <i class="bi bi-file-earmark-plus text-primary"></i> Apply for a Loan
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($message): ?>
                                <div class="alert alert-<?php echo $message_type; ?> alert-auto">
                                    <i class="bi bi-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                                    <?php echo $message; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" class="needs-validation" novalidate>
                                <div class="form-modern mb-4">
                                    <label class="form-label">
                                        <i class="bi bi-currency-dollar"></i> Loan Amount
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" name="amount" class="form-control" 
                                               placeholder="Enter amount" min="100" step="100" required>
                                    </div>
                                    <small class="text-muted">Minimum amount: $100</small>
                                </div>

                                <div class="form-modern mb-4">
                                    <label class="form-label">
                                        <i class="bi bi-info-circle"></i> Interest Rate
                                    </label>
                                    <div class="alert alert-info mb-0" style="background: rgba(13, 202, 240, 0.05); border-color: rgba(13, 202, 240, 0.2); color: #0DCAF0;">
                                        <i class="bi bi-percent"></i> Fixed rate: <strong>12.5%</strong> per annum
                                    </div>
                                </div>

                                <div class="form-modern mb-4">
                                    <label class="form-label">
                                        <i class="bi bi-file-text"></i> Reason for Loan
                                    </label>
                                    <textarea name="reason" class="form-control" rows="4" 
                                              placeholder="Describe why you need this loan..." required></textarea>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-gradient">
                                        <i class="bi bi-send"></i> Submit Application
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Loan Info -->
                    <div class="card-modern mt-4 fade-in-up" style="animation-delay: 0.2s;">
                        <div class="card-header">
                            <i class="bi bi-info-circle text-info"></i> Loan Information
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <h6><i class="bi bi-check-circle text-success"></i> Eligibility</h6>
                                    <ul class="list-unstyled text-muted">
                                        <li><i class="bi bi-check2 text-success me-2"></i> Minimum balance: $0</li>
                                        <li><i class="bi bi-check2 text-success me-2"></i> Maximum loan: $50,000</li>
                                        <li><i class="bi bi-check2 text-success me-2"></i> Repayment: 6-24 months</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6><i class="bi bi-info-circle text-info"></i> Terms</h6>
                                    <ul class="list-unstyled text-muted">
                                        <li><i class="bi bi-info-circle me-2"></i> Interest: 12.5% per annum</li>
                                        <li><i class="bi bi-info-circle me-2"></i> Processing fee: 2%</li>
                                        <li><i class="bi bi-info-circle me-2"></i> Late penalty: $10/day</li>
                                    </ul>
                                </div>
                            </div>
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