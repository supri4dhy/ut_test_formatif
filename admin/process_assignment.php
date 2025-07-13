<?php
session_start();
require_once '../config.php';
require_once 'auth_check.php';

// Pastikan request adalah metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi course_id yang dikirim dari form
    if (!isset($_POST['course_id']) || !is_numeric($_POST['course_id'])) {
        die("Error: ID Mata Kuliah tidak valid.");
    }
    
    $course_id = (int)$_POST['course_id'];
    // Ambil array ID jurusan dari checkbox. 
    // Jika tidak ada yang dipilih, $_POST['major_ids'] tidak akan ada, jadi kita gunakan array kosong sebagai default.
    $major_ids = $_POST['major_ids'] ?? [];

    // Memulai transaksi untuk memastikan integritas data
    $mysqli->begin_transaction();

    try {
        // 1. Hapus semua penugasan lama untuk mata kuliah ini.
        // Ini adalah cara paling sederhana untuk menangani pembaruan: hapus semua, lalu masukkan yang baru.
        $stmt_delete = $mysqli->prepare("DELETE FROM major_courses WHERE course_id = ?");
        $stmt_delete->bind_param("i", $course_id);
        $stmt_delete->execute();
        $stmt_delete->close();

        // 2. Jika ada jurusan yang dipilih (checkbox dicentang), masukkan penugasan baru.
        if (!empty($major_ids)) {
            // Siapkan statement di luar loop untuk efisiensi
            $stmt_insert = $mysqli->prepare("INSERT INTO major_courses (course_id, major_id) VALUES (?, ?)");
            foreach ($major_ids as $major_id) {
                // Pastikan setiap ID adalah angka sebelum dimasukkan
                if (is_numeric($major_id)) {
                    $stmt_insert->bind_param("ii", $course_id, $major_id);
                    $stmt_insert->execute();
                }
            }
            $stmt_insert->close();
        }

        // Jika semua query berhasil, simpan perubahan secara permanen
        $mysqli->commit();
        $_SESSION['message'] = "Penugasan mata kuliah berhasil diperbarui.";

    } catch (Exception $e) {
        // Jika terjadi error, batalkan semua perubahan yang sudah dilakukan
        $mysqli->rollback();
        $_SESSION['message'] = "Gagal memperbarui penugasan: " . $e->getMessage();
    }

    // Redirect kembali ke halaman penugasan dengan course_id yang sama
    // agar pengguna tetap melihat mata kuliah yang baru saja mereka edit.
    header("location: assign_course.php?course_id=" . $course_id);
    exit();

} else {
    // Jika halaman diakses langsung tanpa metode POST, redirect ke halaman utama admin
    header("location: upload.php");
    exit();
}
?>
