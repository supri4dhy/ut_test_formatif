<?php
session_start();

// Untuk keamanan, kredensial seharusnya disimpan dengan lebih aman,
// tapi untuk saat ini kita gunakan variabel biasa.
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'password123'); // Ganti dengan password yang kuat

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Verifikasi kredensial
    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        // Jika berhasil, simpan data sesi
        $_SESSION["loggedin"] = true;
        $_SESSION["username"] = $username;
        
        // Arahkan ke halaman utama admin
        header("location: upload.php");
        exit;
    } else {
        // Jika gagal, kirim pesan error kembali ke halaman login
        $_SESSION['error_msg'] = "Username atau password salah.";
        header("location: login.php");
        exit;
    }
} else {
    // Jika halaman diakses langsung, arahkan ke login
    header("location: login.php");
    exit;
}
?>
