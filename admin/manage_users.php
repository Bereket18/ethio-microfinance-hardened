<?php
// admin/manage_users.php - Modern User Management
session_start();
include_once '../config/database.php';
include_once '../includes/auth.php';
include_once '../includes/validation.php';

check_auth('admin');

$notice = '';

if (isset($_GET['action'])) {
    // SECURITY: admin/dashboard.php's make_admin/delete links already
    // append a csrf_token -- this file previously never checked it, so
    // the token was decorative. Validating it here is what actually
    // closes the CSRF hole for these state-changing GET actions.
    require_csrf_token($_GET['csrf_token'] ?? '');

    $action = $_GET['action'];
    $id = (int)($_GET['id'] ?? 0);
    $current_admin_id = (int)$_SESSION['user_id'];

    if ($action == 'delete') {
        if ($id === $current_admin_id) {
            $notice = "You can't delete your own account while logged in as it.";
        } else {
            $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    } elseif ($action == 'make_admin') {
        $stmt = mysqli_prepare($conn, "UPDATE users SET role = 'admin' WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

$users = mysqli_query($conn, "SELECT * FROM users");
$csrf = csrf_token();
function e($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Ethio Microfinance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-modern fixed-top">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php"><i class="bi bi-shield-lock"></i> Admin Panel</a>
        </div>
    </nav>

    <div class="dashboard-wrapper">
        <div class="container">
            <?php if ($notice): ?>
                <div class="alert alert-warning alert-auto"><i class="bi bi-exclamation-triangle"></i> <?php echo e($notice); ?></div>
            <?php endif; ?>
            <div class="card-modern fade-in-up">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span><i class="bi bi-people text-primary"></i> User Management</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-modern mb-0">
                            <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Balance</th><th>Created</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php while ($user = mysqli_fetch_assoc($users)): ?>
                                <tr>
                                    <td><?php echo (int)$user['id']; ?></td>
                                    <td><strong><?php echo e($user['username']); ?></strong></td>
                                    <td><?php echo e($user['email']); ?></td>
                                    <td>
                                        <?php if ($user['role'] == 'admin'): ?>
                                            <span class="badge-modern danger"><i class="bi bi-shield"></i> Admin</span>
                                        <?php else: ?>
                                            <span class="badge-modern info"><i class="bi bi-person"></i> Customer</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>$<?php echo number_format((float)$user['account_balance'], 2); ?></td>
                                    <td><?php echo e(date('M d, Y', strtotime($user['created_at']))); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="?action=make_admin&id=<?php echo (int)$user['id']; ?>&csrf_token=<?php echo urlencode($csrf); ?>"
                                               class="btn btn-sm btn-warning" title="Make Admin"><i class="bi bi-shield"></i></a>
                                            <a href="../modules/users/profile.php?view=<?php echo (int)$user['id']; ?>"
                                               class="btn btn-sm btn-info" title="View Profile"><i class="bi bi-eye"></i></a>
                                            <a href="?action=delete&id=<?php echo (int)$user['id']; ?>&csrf_token=<?php echo urlencode($csrf); ?>"
                                               class="btn btn-sm btn-danger confirm-delete" title="Delete User"><i class="bi bi-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
