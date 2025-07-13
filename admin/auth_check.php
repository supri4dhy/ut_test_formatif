<?php
// auth_check.php

// DIUBAH: Mulai sesi hanya jika belum ada sesi yang aktif.
// Ini akan mencegah error "session is already active".
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 
// Cek apakah pengguna sudah login, jika tidak, arahkan ke halaman login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}
?>
