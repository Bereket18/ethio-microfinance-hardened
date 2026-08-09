<?php
function client_ip() { return $_SERVER['REMOTE_ADDR'] ?? 'unknown'; }

function log_login_attempt($conn, $username, $success) {
    $ip = client_ip();
    $stmt = mysqli_prepare($conn, "INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, ?)");
    $success_int = $success ? 1 : 0;
    mysqli_stmt_bind_param($stmt, "ssi", $username, $ip, $success_int);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function is_rate_limited($conn, $username, $max_attempts = 5, $window_seconds = 300) {
    $ip = client_ip();
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as c FROM login_attempts
        WHERE (username = ? OR ip_address = ?) AND success = 0
          AND attempt_time > (NOW() - INTERVAL ? SECOND)");
    mysqli_stmt_bind_param($stmt, "ssi", $username, $ip, $window_seconds);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return ((int)$row['c']) >= $max_attempts;
}

function log_audit($conn, $user_id, $action, $details = '') {
    $ip = client_ip();
    $stmt = mysqli_prepare($conn, "INSERT INTO audit_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isss", $user_id, $action, $details, $ip);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function detect_and_log_suspicious_input($conn) {
    $patterns = [
        '/(\'|")\s*(or|and)\s*(\'|")?\s*1\s*=\s*1/i',
        '/union\s+select/i',
        '/\bsleep\s*\(/i',
        '/<script/i',
        '/;\s*(drop|delete|update)\s+/i',
        '/\bexec\s*\(|\bsystem\s*\(|\beval\s*\(/i',
    ];
    $all_input = array_merge($_GET, $_POST);
    foreach ($all_input as $key => $value) {
        if (!is_string($value)) continue;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $details = "param=" . $key . " value=" . substr($value, 0, 200) . " uri=" . ($_SERVER['REQUEST_URI'] ?? '');
                log_audit($conn, $_SESSION['user_id'] ?? null, 'suspicious_input', $details);
                break;
            }
        }
    }
}
?>
