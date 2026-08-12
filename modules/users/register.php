<?php
session_start();
include_once '../../config/database.php';
include_once '../../includes/auth.php';
include_once '../../includes/validation.php';
/** @var mysqli $conn */
$message = '';
$message_type = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_csrf_token($_POST['csrf_token'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $role = 'customer'; // never trust client-supplied role

    if (strlen($username) < 3) {
        $message = "Username must be at least 3 characters.";
        $message_type = 'danger';
    } elseif (!validate_password($password)) {
        $message = "Password must be at least 6 characters.";
        $message_type = 'danger';
    } elseif ($email !== '' && !validate_email($email)) {
        $message = "Please enter a valid email address.";
        $message_type = 'danger';
    } else {
        $check = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
        mysqli_stmt_bind_param($check, "s", $username);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);
        $exists = mysqli_stmt_num_rows($check) > 0;
        mysqli_stmt_close($check);
/** @var mysqli $conn */
        if ($exists) {
            $message = "That username is already taken.";
            $message_type = 'danger';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password, email, role, account_balance) VALUES (?, ?, ?, ?, 0)");
            mysqli_stmt_bind_param($stmt, "ssss", $username, $hash, $email, $role);
            $result = mysqli_stmt_execute($stmt);
            if ($result) {
                $new_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);
                session_regenerate_id(true);
                $_SESSION['user_id'] = $new_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                $_SESSION['is_admin'] = 0;
                header("Location: ../loans/dashboard.php");
                exit();
            } else {
                error_log("Registration insert failed: " . mysqli_error($conn));
                $message = "Registration failed. Please try again.";
                $message_type = 'danger';
            }
        }
    }
}
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Ethio Microfinance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
    <div class="auth-wrapper">
        <div class="auth-card fade-in-scale">
            <div class="auth-brand">
                <h2><i class="bi bi-bank2"></i> EthioMF</h2>
                <p>Create your account and start banking</p>
            </div>
            <?php if ($message): ?><div class="alert alert-<?php echo $message_type; ?> alert-auto"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-modern mb-3"><label class="form-label"><i class="bi bi-person"></i> Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Choose a username" minlength="3" required>
                </div>
                <div class="form-modern mb-3"><label class="form-label"><i class="bi bi-envelope"></i> Email</label>
                    <input type="email" name="email" class="form-control" placeholder="Enter your email">
                </div>
                <div class="form-modern mb-3"><label class="form-label"><i class="bi bi-lock"></i> Password</label>
                    <div class="position-relative"><input type="password" name="password" class="form-control" placeholder="Create a password" required minlength="6">
                        <button type="button" class="password-toggle position-absolute top-50 end-0 translate-middle-y border-0 bg-transparent me-2" style="z-index: 5;"><i class="bi bi-eye text-white-50"></i></button>
                    </div>
                    <small class="text-muted">Password must be at least 6 characters</small>
                </div>
                <button type="submit" class="btn btn-gradient"><i class="bi bi-person-plus"></i> Create Account</button>
            </form>
            <div class="auth-divider">or</div>
            <div class="auth-links">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/main.js"></script>
</body>

</html>