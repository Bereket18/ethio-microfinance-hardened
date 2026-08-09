<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
}
function start_session() {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}
function get_session_data() { return $_SESSION; }
?>
