<?php
function check_permission($conn, $user_id) {
    static $cache = [];
    if (isset($cache[$user_id])) return $cache[$user_id];
    $stmt = mysqli_prepare($conn, "SELECT role FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $has_permission = false;
    if ($row = mysqli_fetch_assoc($result)) { $has_permission = ($row['role'] === 'admin'); }
    mysqli_stmt_close($stmt);
    $cache[$user_id] = $has_permission;
    return $has_permission;
}

function process_transaction($conn, $user_id, $amount, $type) {
    if (!is_numeric($amount) || (float)$amount <= 0) return false;
    $amount = (float)$amount;
    $stmt = mysqli_prepare($conn, "SELECT account_balance FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    if (!$row) return false;
    $balance = (float)$row['account_balance'];

    if ($type === 'withdraw') {
        if ($balance < $amount) return false;
        $stmt = mysqli_prepare($conn, "UPDATE users SET account_balance = account_balance - ? WHERE id = ? AND account_balance >= ?");
        mysqli_stmt_bind_param($stmt, "did", $amount, $user_id, $amount);
        mysqli_stmt_execute($stmt);
        $ok = mysqli_stmt_affected_rows($stmt) > 0;
        mysqli_stmt_close($stmt);
        return $ok;
    }
    if ($type === 'deposit') {
        $stmt = mysqli_prepare($conn, "UPDATE users SET account_balance = account_balance + ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "di", $amount, $user_id);
        mysqli_stmt_execute($stmt);
        $ok = mysqli_stmt_affected_rows($stmt) > 0;
        mysqli_stmt_close($stmt);
        return $ok;
    }
    return false;
}

function upload_file($file, $target_dir = __DIR__ . "/../uploads/") {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'error' => 'No file uploaded.'];
    }
    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        return ['ok' => false, 'error' => 'File too large (max 2MB).'];
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $image_info = @getimagesize($file['tmp_name']);
    if ($image_info === false) return ['ok' => false, 'error' => 'File is not a valid image.'];
    $mime = $image_info['mime'] ?? '';
    if (!isset($allowed[$mime])) return ['ok' => false, 'error' => 'Unsupported image type.'];
    if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
    $safe_name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $target_file = rtrim($target_dir, '/') . '/' . $safe_name;
    if (!move_uploaded_file($file['tmp_name'], $target_file)) {
        return ['ok' => false, 'error' => 'Failed to save file.'];
    }
    return ['ok' => true, 'filename' => $safe_name, 'path' => $target_file];
}

function calculate_interest($rate, $amount) {
    if (!is_numeric($rate) || !is_numeric($amount)) return 0;
    return (float)$amount * ((float)$rate / 100);
}

function get_user_data($conn, $user_id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row;
}

function display_message($message) {
    echo htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8');
}
?>
