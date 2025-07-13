<?php
session_start();
require_once '../config.php';
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    $action = $_POST['action'];

    switch ($action) {
        case 'add_faculty':
            if (!empty($_POST['faculty_name'])) {
                $name = $_POST['faculty_name'];
                $stmt = $mysqli->prepare("INSERT INTO faculties (name) VALUES (?)");
                $stmt->bind_param("s", $name);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Fakultas berhasil ditambahkan.";
                } else {
                    $_SESSION['message'] = "Gagal menambahkan fakultas.";
                }
                $stmt->close();
            }
            break;

        case 'add_major':
            if (!empty($_POST['major_name']) && !empty($_POST['faculty_id'])) {
                $name = $_POST['major_name'];
                $faculty_id = (int)$_POST['faculty_id'];
                $stmt = $mysqli->prepare("INSERT INTO majors (name, faculty_id) VALUES (?, ?)");
                $stmt->bind_param("si", $name, $faculty_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Jurusan berhasil ditambahkan.";
                } else {
                    $_SESSION['message'] = "Gagal menambahkan jurusan.";
                }
                $stmt->close();
            }
            break;

        case 'delete_faculty':
            if (!empty($_POST['faculty_id'])) {
                $id = (int)$_POST['faculty_id'];
                // Menghapus fakultas akan otomatis menghapus jurusan terkait karena ON DELETE CASCADE
                $stmt = $mysqli->prepare("DELETE FROM faculties WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Fakultas berhasil dihapus.";
                } else {
                    $_SESSION['message'] = "Gagal menghapus fakultas.";
                }
                $stmt->close();
            }
            break;

        case 'delete_major':
            if (!empty($_POST['major_id'])) {
                $id = (int)$_POST['major_id'];
                $stmt = $mysqli->prepare("DELETE FROM majors WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Jurusan berhasil dihapus.";
                } else {
                    $_SESSION['message'] = "Gagal menghapus jurusan.";
                }
                $stmt->close();
            }
            break;
    }
}

$mysqli->close();
// Redirect kembali ke halaman pengelolaan
header("location: manage_structure.php");
exit();
?>
