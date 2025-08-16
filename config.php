<?php
// config.php

/**
 * File ini berisi konfigurasi untuk koneksi ke database.
 * Simpan file ini di lokasi yang aman dan jangan tampilkan informasi sensitif secara publik.
 */

// Pengaturan untuk koneksi ke database
define('DB_SERVER', 'ftpupload.net');      // Server database, biasanya 'localhost' untuk pengembangan lokal
define('DB_USERNAME', 'ezyro_39708677');         // Username database, default untuk XAMPP adalah 'root'
define('DB_PASSWORD', 'f38648701dc');             // Password database, default untuk XAMPP adalah kosong
define('DB_NAME', 'ezyro_39708677_kuis_db');          // Nama database yang telah Anda buat di phpMyAdmin

/*
 * Mencoba untuk membuat koneksi ke database MySQL menggunakan ekstensi MySQLi.
 * MySQLi (MySQL Improved) adalah ekstensi yang direkomendasikan untuk bekerja dengan database MySQL di PHP.
 */
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Periksa koneksi
// Jika terjadi error saat koneksi, hentikan skrip dan tampilkan pesan error.
if($mysqli === false){
    die("ERROR: Tidak dapat terhubung ke database. " . $mysqli->connect_error);
}

// Mengatur character set ke utf8mb4 untuk mendukung berbagai macam karakter, termasuk emoji.
// Ini adalah praktik yang baik untuk memastikan data disimpan dan diambil dengan benar.
if (!$mysqli->set_charset("utf8mb4")) {
    printf("Error loading character set utf8mb4: %s\n", $mysqli->error);
    exit();
}
?>
