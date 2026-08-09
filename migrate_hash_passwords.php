<?php
// migrate_hash_passwords.php
//
// One-time migration: the original schema seeds users with plaintext
// passwords. Now that login.php/admin_login.php use password_verify(),
// plaintext values in the password column will never match (correctly --
// this is password_verify() doing its job), so existing seed accounts need
// their passwords rehashed with password_hash() once before first use.
//
// Safe to re-run: it detects already-hashed passwords (bcrypt hashes always
// start with $2y$) and skips them, so running this twice does not double-hash.
//
// Usage: php migrate_hash_passwords.php
// Delete this file after running it once in a real deployment -- it has no
// business existing in a production image long-term.

require_once __DIR__ . '/config/database.php';

$result = mysqli_query($conn, "SELECT id, username, password FROM users");
$migrated = 0;
$skipped = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $pw = $row['password'];
    if (str_starts_with($pw, '$2y$') || str_starts_with($pw, '$2a$') || str_starts_with($pw, '$argon2')) {
        $skipped++;
        continue; // already hashed
    }
    $hash = password_hash($pw, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $hash, $row['id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $migrated++;
    echo "Migrated: {$row['username']} (id {$row['id']})\n";
}

echo "\nDone. Migrated: $migrated, already-hashed (skipped): $skipped\n";
