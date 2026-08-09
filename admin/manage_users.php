<?php
// admin/manage_users.php - Modern User Management
session_start();
include_once '../config/database.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../modules/users/login.php");
    exit();
}

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = $_GET['id'] ?? 0;
    
    if ($action == 'delete') {
        $query = "DELETE FROM users WHERE id = $id";
        mysqli_query($conn, $query);
    } elseif ($action == 'make_admin') {
        $query = "UPDATE users SET role = 'admin' WHERE id = $id";
        mysqli_query($conn, $query);
    }
}

$users = mysqli_query($conn, "SELECT * FROM users");
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
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-shield-lock"></i> Admin Panel
            </a>
        </div>
    </nav>

    <div class="dashboard-wrapper">
        <div class="container">
            <div class="card-modern fade-in-up">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span><i class="bi bi-people text-primary"></i> User Management</span>
                    <input type="text" class="form-control search-input w-auto" placeholder="Search users..." style="min-width: 200px;">
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-modern mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Balance</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($user = mysqli_fetch_assoc($users)): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><strong><?php echo $user['username']; ?></strong></td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td>
                                        <?php if($user['role'] == 'admin'): ?>
                                            <span class="badge-modern danger">
                                                <i class="bi bi-shield"></i> Admin
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-modern info">
                                                <i class="bi bi-person"></i> Customer
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>$<?php echo number_format($user['account_balance'], 2); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="?action=make_admin&id=<?php echo $user['id']; ?>" 
                                               class="btn btn-sm btn-warning" title="Make Admin">
                                                <i class="bi bi-shield"></i>
                                            </a>
                                            <a href="../modules/users/profile.php?view=<?php echo $user['id']; ?>" 
                                               class="btn btn-sm btn-info" title="View Profile">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="?action=delete&id=<?php echo $user['id']; ?>" 
                                               class="btn btn-sm btn-danger confirm-delete" title="Delete User">
                                                <i class="bi bi-trash"></i>
                                            </a>
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