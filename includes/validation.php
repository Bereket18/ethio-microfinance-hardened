<?php
function validate_email($email) { return filter_var($email, FILTER_VALIDATE_EMAIL) !== false; }
function validate_phone($phone) { return is_string($phone) && strlen(trim($phone)) >= 10; }
function validate_amount($amount) { return is_numeric($amount) && (float)$amount > 0; }
function sanitize_output($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function sanitize_input($input) { return strip_tags(trim((string)$input)); }

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && is_string($token) && $token !== ''
        && hash_equals($_SESSION['csrf_token'], $token);
}
function require_csrf_token($token) {
    if (!validate_csrf_token($token)) {
        http_response_code(403);
        die('Invalid or expired form submission. Please go back and try again.');
    }
}
?>
