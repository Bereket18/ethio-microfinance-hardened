<?php
function is_authenticated() { return isset($_SESSION['user_id']); }
function is_admin() { return isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; }
function check_auth($required_role = 'customer') {
    if (!is_authenticated()) {
        header("Location: /ethio_microfinance/modules/users/login.php");
        exit();
    }
    if ($required_role === 'admin' && !is_admin()) {
        header("Location: /ethio_microfinance/index.php");
        exit();
    }
    return true;
}
function validate_password($password) {
    return is_string($password) && strlen($password) >= 6;
}
?>
