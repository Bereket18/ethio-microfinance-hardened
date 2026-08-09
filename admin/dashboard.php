<?php
session_start();
include_once '../config/database.php';
include_once '../includes/auth.php';
include_once '../includes/security_log.php';

check_auth('admin');
detect_and_log_suspicious_input($conn);

$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users"))['count'];
$total_loans = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM loans"))['count'];
$pending_loans = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM loans WHERE status = 'pending'"))['count'];
$total_amount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM loans"))['total'] ?? 0;
$users = mysqli_query($conn, "SELECT id, username, email, role, account_balance FROM users LIMIT 10");

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - Ethio Microfinance</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
<nav class="navbar navbar-expand-lg navbar-modern fixed-top"><div class="container">
<a class="navbar-brand" href="dashboard.php"><i class="bi bi-shield-lock"></i> Admin Panel</a>
<button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
<div class="collapse navbar-collapse" id="navbarNav"><ul class="navbar-nav ms-auto">
<li class="nav-item"><a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
<li class="nav-item"><a class="nav-link" href="manage_users.php"><i class="bi bi-people"></i> Users</a></li>
<li class="nav-item"><a class="nav-link" href="../modules/loans/approve.php"><i class="bi bi-check-circle"></i> Approve Loans</a></li>
<li class="nav-item"><a class="nav-link" href="../index.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
</ul></div></div></nav>
<div class="dashboard-wrapper"><div class="container">
<div class="dashboard-header fade-in-up"><div class="row align-items-center">
<div class="col-md-8"><h1><i class="bi bi-shield-lock"></i> Admin Dashboard</h1><p class="mb-0">Manage the entire Ethio Microfinance system</p></div>
<div class="col-md-4 text-md-end"><span class="badge-modern danger"><i class="bi bi-shield"></i> Admin Access</span></div>
</div></div>
<div class="row g-4 mb-4">
<div class="col-md-3 fade-in-up" style="animation-delay: 0.1s;"><div class="stat-card"><div class="stat-icon primary"><i class="bi bi-people"></i></div>
<div class="stat-number"><?php echo (int)$total_users; ?></div><div class="stat-label">Total Users</div></div></div>
<div class="col-md-3 fade-in-up" style="animation-delay: 0.2s;"><div class="stat-card"><div class="stat-icon success"><i class="bi bi-file-text"></i></div>
<div class="stat-number"><?php echo (int)$total_loans; ?></div><div class="stat-label">Total Loans</div></div></div>
<div class="col-md-3 fade-in-up" style="animation-delay: 0.3s;"><div class="stat-card"><div class="stat-icon warning"><i class="bi bi-hourglass-split"></i></div>
<div class="stat-number"><?php echo (int)$pending_loans; ?></div><div class="stat-label">Pending Loans</div></div></div>
<div class="col-md-3 fade-in-up" style="animation-delay: 0.4s;"><div class="stat-card"><div class="stat-icon info"><i class="bi bi-currency-dollar"></i></div>
<div class="stat-number">$<?php echo number_format((float)$total_amount, 2); ?></div><div class="stat-label">Total Amount</div></div></div>
</div>
<div class="row g-4">
<div class="col-md-8 fade-in-up" style="animation-delay: 0.5s;"><div class="card-modern">
<div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2"><span><i class="bi bi-people text-primary"></i> Recent Users</span></div>
<div class="card-body p-0"><div class="table-responsive"><table class="table table-modern mb-0">
<thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Balance</th><th>Actions</th></tr></thead>
<tbody><?php while($user = mysqli_fetch_assoc($users)): ?>
<tr><td><?php echo (int)$user['id']; ?></td><td><strong><?php echo e($user['username']); ?></strong></td>
<td><?php echo e($user['email']); ?></td>
<td><?php if($user['role'] == 'admin'): ?><span class="badge-modern danger"><i class="bi bi-shield"></i> Admin</span>
<?php else: ?><span class="badge-modern info"><i class="bi bi-person"></i> Customer</span><?php endif; ?></td>
<td>$<?php echo number_format((float)$user['account_balance'], 2); ?></td>
<td><div class="btn-group" role="group">
<a href="manage_users.php?action=make_admin&id=<?php echo (int)$user['id']; ?>&csrf_token=<?php echo urlencode(csrf_token()); ?>" class="btn btn-sm btn-warning" title="Make Admin"><i class="bi bi-shield"></i></a>
<a href="../modules/users/profile.php?view=<?php echo (int)$user['id']; ?>" class="btn btn-sm btn-info" title="View"><i class="bi bi-eye"></i></a>
<a href="manage_users.php?action=delete&id=<?php echo (int)$user['id']; ?>&csrf_token=<?php echo urlencode(csrf_token()); ?>" class="btn btn-sm btn-danger confirm-delete" title="Delete"><i class="bi bi-trash"></i></a>
</div></td></tr>
<?php endwhile; ?></tbody></table></div></div></div></div>
<div class="col-md-4 fade-in-up" style="animation-delay: 0.6s;"><div class="card-modern">
<div class="card-header"><i class="bi bi-shield-exclamation text-warning"></i> Recent Security Events</div>
<div class="card-body"><?php
$events = mysqli_query($conn, "SELECT action, details, ip_address, log_time FROM audit_log ORDER BY log_time DESC LIMIT 8");
if ($events && mysqli_num_rows($events) > 0): ?><ul class="list-unstyled small mb-0">
<?php while ($ev = mysqli_fetch_assoc($events)): ?><li class="mb-2 pb-2 border-bottom">
<strong><?php echo e($ev['action']); ?></strong> from <?php echo e($ev['ip_address']); ?><br>
<span class="text-muted"><?php echo e($ev['log_time']); ?></span>
<?php if ($ev['details']): ?><br><span class="text-muted"><?php echo e(substr($ev['details'], 0, 80)); ?></span><?php endif; ?>
</li><?php endwhile; ?></ul><?php else: ?><p class="text-muted mb-0">No events logged yet.</p><?php endif; ?>
</div></div></div>
</div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body></html>
