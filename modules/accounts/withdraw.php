<?php
// modules/accounts/withdraw.php - Modern Withdraw
session_start();
include_once '../../config/database.php';
include_once '../../includes/auth.php';
include_once '../../includes/validation.php';
include_once '../../includes/functions.php';

check_auth();
$user_id = (int)$_SESSION['user_id'];

// Fetch a real, current balance from the database to display -- the
// original template read $_SESSION['balance'], a key that is never
// actually set anywhere in this codebase, so it silently always showed
// $0.00 regardless of the real balance.
$stmt = mysqli_prepare($conn, "SELECT account_balance FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$current_balance = (float)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['account_balance'] ?? 0);
mysqli_stmt_close($stmt);

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_csrf_token($_POST['csrf_token'] ?? '');

    $amount = $_POST['amount'] ?? 0;

    if (!validate_amount($amount)) {
        $message = "Please enter a valid withdrawal amount.";
        $message_type = 'danger';
    } else {
        $amount = (float)$amount;
        // SECURITY: the original UPDATE had NO balance check at all --
        // account_balance = account_balance - $amount would happily go
        // negative for any amount, however large. process_transaction()
        // (includes/functions.php) checks balance >= amount inside the
        // same prepared-statement UPDATE before allowing it through.
        $ok = process_transaction($conn, $user_id, $amount, 'withdraw');

        if ($ok) {
            $stmt = mysqli_prepare($conn, "INSERT INTO transactions (user_id, type, amount, description, transaction_date) VALUES (?, 'withdrawal', ?, 'Withdrawal', NOW())");
            mysqli_stmt_bind_param($stmt, "id", $user_id, $amount);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $current_balance -= $amount;
            $message = "Withdrawal of $" . number_format($amount, 2) . " was successful!";
            $message_type = 'success';
        } else {
            $message = "Withdrawal failed -- insufficient balance or invalid amount.";
            $message_type = 'danger';
        }
    }
}
$csrf = csrf_token();
function e($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdraw - Ethio Microfinance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-modern fixed-top">
        <div class="container">
            <a class="navbar-brand" href="../loans/dashboard.php"><i class="bi bi-bank2"></i> EthioMF</a>
        </div>
    </nav>

    <div class="dashboard-wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card-modern fade-in-up">
                        <div class="card-header">
                            <h5 class="mb-0 fw-700"><i class="bi bi-dash-circle text-danger"></i> Withdraw Funds</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info mb-4">
                                <i class="bi bi-info-circle"></i> Current Balance: <strong>$<?php echo number_format($current_balance, 2); ?></strong>
                            </div>

                            <?php if ($message): ?>
                                <div class="alert alert-<?php echo e($message_type); ?> alert-auto">
                                    <i class="bi bi-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                                    <?php echo e($message); ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
                                <div class="form-modern mb-4">
                                    <label class="form-label"><i class="bi bi-currency-dollar"></i> Amount to Withdraw</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" name="amount" class="form-control" placeholder="0.00" min="0.01" step="0.01" required>
                                    </div>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-gradient-danger" style="background: linear-gradient(135deg, #FF6B6B 0%, #EE5A24 100%);">
                                        <i class="bi bi-dash-circle"></i> Withdraw Funds
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
