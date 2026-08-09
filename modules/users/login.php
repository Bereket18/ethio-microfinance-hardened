<?php
session_start();
include_once '../../config/database.php';
include_once '../../includes/validation.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_csrf_token($_POST['csrf_token'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Please enter both username and password.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, username, password, role FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['is_admin'] = ($user['role'] == 'admin') ? 1 : 0;
            header("Location: ../loans/dashboard.php");
            exit();
        } else {
            $error = "Invalid username or password";
        }
    }
}
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Ethio Microfinance</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<div class="auth-wrapper"><div class="auth-card fade-in-scale">
<div class="auth-brand"><h2><i class="bi bi-bank2"></i> EthioMF</h2><p>Welcome back! Sign in to your account</p></div>
<?php if ($error): ?><div class="alert alert-danger alert-auto"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
<form method="POST" class="needs-validation" novalidate>
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
<div class="form-modern mb-3"><label class="form-label"><i class="bi bi-person"></i> Username</label>
<input type="text" name="username" class="form-control" placeholder="Enter your username" required></div>
<div class="form-modern mb-3"><label class="form-label"><i class="bi bi-lock"></i> Password</label>
<div class="position-relative"><input type="password" name="password" class="form-control" placeholder="Enter your password" required>
<button type="button" class="password-toggle position-absolute top-50 end-0 translate-middle-y border-0 bg-transparent me-2" style="z-index: 5;"><i class="bi bi-eye text-white-50"></i></button></div></div>
<button type="submit" class="btn btn-gradient"><i class="bi bi-box-arrow-in-right"></i> Sign In</button>
</form>
<div class="auth-divider">or</div>
<div class="auth-links"><p>Don't have an account? <a href="register.php">Register now</a></p>
<p class="mt-2"><a href="admin_login.php" class="text-warning"><i class="bi bi-shield-lock"></i> Admin Login</a></p></div>
<div class="alert alert-warning mt-3" style="background: rgba(255, 193, 7, 0.05); border-color: rgba(255, 193, 7, 0.2); color: #FFC107;">
<small><i class="bi bi-info-circle"></i> <strong>Demo Credentials:</strong><br>admin/admin123 - alemu/password - betty/betty123</small></div>
</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
</body></html>
