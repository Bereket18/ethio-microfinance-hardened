<?php
// modules/users/profile.php - Modern Profile
session_start();
include_once '../../config/database.php';
include_once '../../includes/auth.php';
include_once '../../includes/validation.php';
include_once '../../includes/functions.php';

check_auth(); // must be logged in

$user_id = (int)$_SESSION['user_id'];

// Own profile -- always fetched via prepared statement (Part 4.2)
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "SELECT * FROM customer_info WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$info = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// SECURITY (fixes Part 2, Section 2.1.11 -- IDOR):
// Viewing another user's record now requires the viewer to BE an admin.
// The original code returned any user's full row -- including balance and
// email -- to any logged-in visitor who changed the ?view= number in the
// URL, with no ownership or role check at all.
$view_user = null;
$view_denied = false;
if (isset($_GET['view'])) {
    $view_id = (int)$_GET['view'];
    if ($view_id === $user_id) {
        // viewing your own id via ?view= is harmless -- just show own profile
        $view_user = $user;
    } elseif (is_admin()) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $view_id);
        mysqli_stmt_execute($stmt);
        $view_user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
    } else {
        $view_denied = true;
    }
}

$upload_message = '';
$upload_message_type = '';

// SECURITY (fixes Part 2, Section 2.1.9 -- unrestricted file upload):
// This endpoint now requires a valid CSRF token and routes through the
// upload_file() helper (includes/functions.php), which validates real
// image content via getimagesize(), rejects anything else, caps size at
// 2MB, and saves under a random filename -- the original code trusted the
// client-supplied filename directly and performed no content validation
// at all, which allowed a renamed .php file to be uploaded as a webshell.
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_pic']) && !$view_denied) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $upload_message = "Invalid or expired form submission. Please try again.";
        $upload_message_type = 'danger';
    } else {
        $result = upload_file($_FILES['profile_pic']);
        if ($result['ok']) {
            $upload_message = "Profile picture uploaded successfully.";
            $upload_message_type = 'success';
        } else {
            $upload_message = $result['error'];
            $upload_message_type = 'danger';
        }
    }
}

function e($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Ethio Microfinance</title>
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
                <div class="col-lg-8">
                    <div class="card-modern fade-in-up">
                        <div class="card-header">
                            <h5 class="mb-0 fw-700">
                                <i class="bi bi-person-circle text-primary"></i> User Profile
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($view_denied): ?>
                                <div class="alert alert-danger alert-auto">
                                    <i class="bi bi-shield-exclamation"></i>
                                    You don't have permission to view that user's profile.
                                </div>
                                <a href="profile.php" class="btn btn-outline-gradient">
                                    <i class="bi bi-arrow-left"></i> Back to your profile
                                </a>

                            <?php elseif (isset($_GET['view']) && $view_user): ?>
                                <div class="text-center">
                                    <div class="profile-avatar">
                                        <?php echo e(strtoupper(substr($view_user['username'], 0, 2))); ?>
                                    </div>
                                    <h3 class="fw-700"><?php echo e($view_user['username']); ?></h3>
                                    <p><i class="bi bi-envelope"></i> <?php echo e($view_user['email']); ?></p>
                                    <p><i class="bi bi-currency-dollar"></i> Balance: $<?php echo number_format((float)$view_user['account_balance'], 2); ?></p>
                                    <span class="badge-modern info">
                                        <i class="bi bi-person"></i> <?php echo e($view_user['role']); ?>
                                    </span>
                                    <?php if (is_admin() && $view_user['id'] != $user_id): ?>
                                        <p class="text-muted mt-2"><small><i class="bi bi-info-circle"></i> Viewing as admin</small></p>
                                    <?php endif; ?>
                                </div>

                            <?php elseif (isset($_GET['view'])): ?>
                                <div class="alert alert-warning alert-auto">
                                    <i class="bi bi-exclamation-triangle"></i> User not found.
                                </div>

                            <?php else: ?>
                                <div class="text-center">
                                    <div class="profile-avatar">
                                        <?php echo e(strtoupper(substr($user['username'], 0, 2))); ?>
                                    </div>
                                    <h3 class="fw-700"><?php echo e($user['username']); ?></h3>
                                    <p><i class="bi bi-envelope"></i> <?php echo e($user['email']); ?></p>
                                    <p><i class="bi bi-currency-dollar"></i> Balance: $<?php echo number_format((float)$user['account_balance'], 2); ?></p>
                                    <span class="badge-modern info">
                                        <i class="bi bi-person"></i> <?php echo e($user['role']); ?>
                                    </span>
                                </div>

                                <?php if ($info): ?>
                                    <hr>
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <h6><i class="bi bi-person"></i> Personal Information</h6>
                                            <p><strong>Full Name:</strong> <?php echo e($info['full_name']); ?></p>
                                            <p><strong>Phone:</strong> <?php echo e($info['phone']); ?></p>
                                            <p><strong>National ID:</strong> <?php echo e($info['national_id']); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6><i class="bi bi-briefcase"></i> Employment</h6>
                                            <p><strong>Occupation:</strong> <?php echo e($info['occupation']); ?></p>
                                            <p><strong>Monthly Income:</strong> $<?php echo number_format((float)$info['monthly_income'], 2); ?></p>
                                            <p><strong>Address:</strong> <?php echo e($info['address']); ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <hr>
                                <h6><i class="bi bi-upload"></i> Update Profile Picture</h6>

                                <?php if ($upload_message): ?>
                                    <div class="alert alert-<?php echo e($upload_message_type); ?> alert-auto">
                                        <i class="bi bi-info-circle"></i> <?php echo e($upload_message); ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" enctype="multipart/form-data" class="mt-3">
                                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
                                    <div class="upload-area">
                                        <div class="upload-icon">
                                            <i class="bi bi-cloud-upload"></i>
                                        </div>
                                        <p class="mb-2">Drag and drop or click to upload</p>
                                        <input type="file" name="profile_pic" accept="image/png, image/jpeg, image/gif, image/webp" class="file-input" hidden>
                                        <button type="button" class="btn btn-outline-gradient" onclick="document.querySelector('.file-input').click()">
                                            <i class="bi bi-folder-open"></i> Browse Files
                                        </button>
                                        <span class="file-label d-block mt-2 text-muted">No file selected (images only, max 2MB)</span>
                                        <div class="file-preview mt-3"></div>
                                        <button type="submit" class="btn btn-gradient mt-3">
                                            <i class="bi bi-upload"></i> Upload Picture
                                        </button>
                                    </div>
                                </form>
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
