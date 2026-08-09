<?php
// modules/accounts/deposit.php - Modern Deposit
session_start();
include_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../users/login.php");
    exit();
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $amount = $_POST['amount'] ?? 0;
    $description = $_POST['description'] ?? 'Deposit';
    
    $update_query = "UPDATE users SET account_balance = account_balance + $amount WHERE id = $user_id";
    $result = mysqli_query($conn, $update_query);
    
    if ($result) {
        $transaction_query = "INSERT INTO transactions (user_id, type, amount, description, transaction_date) 
                              VALUES ($user_id, 'deposit', $amount, '$description', NOW())";
        mysqli_query($conn, $transaction_query);
        
        $message = "Deposit of $" . number_format($amount, 2) . " was successful!";
        $message_type = 'success';
    } else {
        $message = "Deposit failed: " . mysqli_error($conn);
        $message_type = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deposit - Ethio Microfinance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-modern fixed-top">
        <div class="container">
            <a class="navbar-brand" href="../loans/dashboard.php">
                <i class="bi bi-bank2"></i> EthioMF
            </a>
        </div>
    </nav>

    <div class="dashboard-wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card-modern fade-in-up">
                        <div class="card-header">
                            <h5 class="mb-0 fw-700">
                                <i class="bi bi-plus-circle text-success"></i> Make a Deposit
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
                                        <i class="bi bi-currency-dollar"></i> Amount to Deposit
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" name="amount" class="form-control" 
                                               placeholder="0.00" min="1" step="0.01" required>
                                    </div>
                                </div>

                                <div class="form-modern mb-4">
                                    <label class="form-label">
                                        <i class="bi bi-file-text"></i> Description
                                    </label>
                                    <input type="text" name="description" class="form-control" 
                                           placeholder="Deposit description" value="Deposit">
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-gradient-success" style="background: linear-gradient(135deg, #00D4AA 0%, #00B894 100%);">
                                        <i class="bi bi-plus-circle"></i> Deposit Funds
                                    </button>
                                </div>
                            </form>
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