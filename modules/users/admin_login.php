<?php
session_start();
include_once '../../config/database.php';
include_once '../../includes/validation.php';
include_once '../../includes/security_log.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_csrf_token($_POST['csrf_token'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (is_rate_limited($conn, $username)) {
        $error = "Too many failed attempts. Please try again in a few minutes.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, username, password, role FROM users WHERE username = ? AND role = 'admin'");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user['password'])) {
            log_login_attempt($conn, $username, true);
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = 'admin';
            $_SESSION['is_admin'] = 1;
            log_audit($conn, $user['id'], 'admin_login', 'successful admin login');
            header("Location: ../../admin/dashboard.php");
            exit();
        } else {
            log_login_attempt($conn, $username, false);
            $error = "Invalid admin credentials";
        }
    }
}
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login - Ethio Microfinance</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/style.css"></head>
<body>
<div class="auth-wrapper"><div class="auth-card fade-in-scale">
<div class="auth-brand"><h2><i class="bi bi-shield-lock"></i> Admin Login</h2><p>Secure administrator access</p></div>
<?php if ($error): ?><div class="alert alert-danger alert-auto"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
<form method="POST" class="needs-validation" novalidate>
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
<div class="form-modern mb-3"><label class="form-label"><i class="bi bi-person"></i> Admin Username</label>
<input type="text" name="username" class="form-control" placeholder="Enter admin username" required></div>
<div class="form-modern mb-3"><label class="form-label"><i class="bi bi-lock"></i> Admin Password</label>
<div class="position-relative"><input type="password" name="password" class="form-control" placeholder="Enter admin password" required>
<button type="button" class="password-toggle position-absolute top-50 end-0 translate-middle-y border-0 bg-transparent me-2" style="z-index: 5;"><i class="bi bi-eye text-white-50"></i></button></div></div>
<button type="submit" class="btn btn-gradient"><i class="bi bi-shield-lock"></i> Admin Login</button>
</form>
<div class="auth-divider">or</div>
<div class="auth-links"><p><a href="login.php">Customer Login</a></p></div>
</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/main.js"></script>
</body></html>
