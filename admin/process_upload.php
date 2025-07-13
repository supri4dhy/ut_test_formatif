<?php
session_start();
require '../config.php'; 
require '../vendor/autoload.php'; 

use PhpOffice\PhpSpreadsheet\IOFactory;

// DIUBAH: Memeriksa metode request dan keberadaan file, bukan nama tombol.
// Ini adalah cara yang lebih andal saat form dikirim melalui JavaScript.
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["excelFile"])) {
    if ($_FILES["excelFile"]["error"] == 0) {
        $allowedFileType = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        if (in_array($_FILES["excelFile"]["type"], $allowedFileType)) {
            $targetPath = $_FILES['excelFile']['tmp_name'];
            $mysqli->begin_transaction();

            try {
                $originalFileName = $_FILES['excelFile']['name'];
                $courseName = pathinfo($originalFileName, PATHINFO_FILENAME);
                $overwrite = ($_POST['overwrite'] ?? 'false') === 'true';

                $course_id = null;
                $stmt_find_course = $mysqli->prepare("SELECT id FROM courses WHERE name = ?");
                $stmt_find_course->bind_param("s", $courseName);
                $stmt_find_course->execute();
                $result_course = $stmt_find_course->get_result();
                if ($result_course->num_rows > 0) {
                    $row = $result_course->fetch_assoc();
                    $course_id = $row['id'];
                } else {
                    $stmt_create_course = $mysqli->prepare("INSERT INTO courses (name) VALUES (?)");
                    $stmt_create_course->bind_param("s", $courseName);
                    $stmt_create_course->execute();
                    $course_id = $stmt_create_course->insert_id;
                    $stmt_create_course->close();
                }
                $stmt_find_course->close();

                if (is_null($course_id)) {
                    throw new Exception("Gagal membuat atau menemukan mata kuliah: $courseName");
                }
                
                $spreadsheet = IOFactory::load($targetPath);
                $sheetNames = $spreadsheet->getSheetNames();

                if ($overwrite) {
                    $placeholders = implode(',', array_fill(0, count($sheetNames), '?'));
                    $sql_delete = "DELETE FROM quizzes WHERE course_id = ? AND title IN ($placeholders)";
                    $params = array_merge([$course_id], $sheetNames);
                    $types = 'i' . str_repeat('s', count($sheetNames));
                    
                    $stmt_delete = $mysqli->prepare($sql_delete);
                    $stmt_delete->bind_param($types, ...$params);
                    $stmt_delete->execute();
                    $stmt_delete->close();
                }

                $processedSheets = [];
                foreach ($sheetNames as $sheetName) {
                    $sheet = $spreadsheet->getSheetByName($sheetName);
                    $quizTitle = $sheetName;
                    
                    $stmt_quiz = $mysqli->prepare("INSERT INTO quizzes (course_id, title) VALUES (?, ?)");
                    $stmt_quiz->bind_param("is", $course_id, $quizTitle);
                    $stmt_quiz->execute();
                    $quiz_id = $stmt_quiz->insert_id;
                    $stmt_quiz->close();
                    if ($quiz_id == 0) continue;
                    $stmt_question = $mysqli->prepare("INSERT INTO questions (quiz_id, question_text) VALUES (?, ?)");
                    $stmt_option = $mysqli->prepare("INSERT INTO options (question_id, option_key, option_text, is_correct) VALUES (?, ?, ?, ?)");
                    $rowCount = 0;
                    foreach ($sheet->getRowIterator(2) as $row) {
                        $cellIterator = $row->getCellIterator(); $cellIterator->setIterateOnlyExistingCells(FALSE); $data = [];
                        foreach ($cellIterator as $cell) { $data[] = $cell->getValue(); }
                        $question_text = $data[0] ?? null; $option_a_text = $data[1] ?? null; $option_b_text = $data[2] ?? null; $option_c_text = $data[3] ?? null; $option_d_text = $data[4] ?? null; $correct_answer = strtoupper(trim($data[5] ?? ''));
                        if (empty($question_text) || !in_array($correct_answer, ['A', 'B', 'C', 'D'])) continue;
                        $rowCount++;
                        $stmt_question->bind_param("is", $quiz_id, $question_text); $stmt_question->execute(); $question_id = $stmt_question->insert_id;
                        $options = ['A' => $option_a_text, 'B' => $option_b_text, 'C' => $option_c_text, 'D' => $option_d_text];
                        foreach ($options as $key => $text) { if (!empty($text)) { $is_correct = ($key == $correct_answer) ? 1 : 0; $stmt_option->bind_param("issi", $question_id, $key, $text, $is_correct); $stmt_option->execute(); } }
                    }
                    if ($rowCount > 0) { $processedSheets[] = "\"$quizTitle\" ($rowCount soal)"; }
                }
                
                $mysqli->commit();
                $action_type = $overwrite ? "diperbarui" : "dibuat";
                $_SESSION['message'] = "Berhasil: Mata kuliah \"$courseName\" telah $action_type. Kuis yang diproses: " . implode(', ', $processedSheets);

            } catch (Exception $e) {
                $mysqli->rollback();
                $_SESSION['message'] = "Error Kritis: " . $e->getMessage();
            }
        } else {
            $_SESSION['message'] = "Error: Tipe file tidak valid.";
        }
    } else {
        $_SESSION['message'] = "Error: " . ($_FILES["excelFile"]["error"] ? "Terjadi kesalahan saat unggah file." : "Tidak ada file yang diunggah.");
    }
} else {
    // Jika halaman diakses tanpa metode POST, redirect saja
    $_SESSION['message'] = "Akses tidak sah.";
}

header("location: upload.php");
exit();
?>
