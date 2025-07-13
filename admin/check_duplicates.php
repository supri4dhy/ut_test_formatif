<?php
require_once '../config.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

if (!isset($_FILES['excelFile']) || $_FILES['excelFile']['error'] != 0) {
    echo json_encode(['status' => 'error', 'message' => 'File tidak diterima.']);
    exit;
}

$targetPath = $_FILES['excelFile']['tmp_name'];
$courseName = pathinfo($_FILES['excelFile']['name'], PATHINFO_FILENAME);

try {
    // Cek apakah mata kuliah sudah ada
    $stmt_course = $mysqli->prepare("SELECT id FROM courses WHERE name = ?");
    $stmt_course->bind_param("s", $courseName);
    $stmt_course->execute();
    $result_course = $stmt_course->get_result();

    if ($result_course->num_rows > 0) {
        $course = $result_course->fetch_assoc();
        $course_id = $course['id'];

        // Dapatkan nama semua sheet dari file Excel
        $reader = IOFactory::createReaderForFile($targetPath);
        $sheetNames = $reader->listWorksheetNames($targetPath);

        // Cek sheet mana yang sudah ada sebagai kuis di mata kuliah ini
        if (!empty($sheetNames)) {
            $placeholders = implode(',', array_fill(0, count($sheetNames), '?'));
            $sql_quizzes = "SELECT title FROM quizzes WHERE course_id = ? AND title IN ($placeholders)";
            
            $params = array_merge([$course_id], $sheetNames);
            $types = 'i' . str_repeat('s', count($sheetNames));
            
            $stmt_quizzes = $mysqli->prepare($sql_quizzes);
            $stmt_quizzes->bind_param($types, ...$params);
            $stmt_quizzes->execute();
            $result_quizzes = $stmt_quizzes->get_result();

            $duplicates = [];
            while ($row = $result_quizzes->fetch_assoc()) {
                $duplicates[] = $row['title'];
            }

            if (!empty($duplicates)) {
                echo json_encode([
                    'status' => 'duplicates_found',
                    'course_name' => $courseName,
                    'duplicates' => $duplicates
                ]);
            } else {
                echo json_encode(['status' => 'no_duplicates']);
            }
            $stmt_quizzes->close();
        } else {
             echo json_encode(['status' => 'no_duplicates']);
        }
    } else {
        echo json_encode(['status' => 'no_duplicates']);
    }
    $stmt_course->close();

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$mysqli->close();
?>
