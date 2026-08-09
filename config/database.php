<?php
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'ethio_microfinance';
$db_port = getenv('DB_PORT') ?: 3306;

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name, (int)$db_port);

if (!$conn) {
    error_log("DB connection failed: " . mysqli_connect_error());
    die("Connection failed. Please contact the administrator.");
}

$GLOBALS['db_connection'] = $conn;
mysqli_set_charset($conn, "utf8mb4");
?>
