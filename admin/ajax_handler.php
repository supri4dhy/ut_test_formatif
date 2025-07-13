<?php
// ajax_handler.php
require_once 'auth_check.php';
require_once '../config.php';

header('Content-Type: application/json');

// Sembunyikan warning PHP agar tidak merusak output JSON
error_reporting(0);
ini_set('display_errors', 0);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// =================================================================
// AKSI: MENGAMBIL DETAIL KUIS UNTUK DIEDIT
// =================================================================
if ($action === 'get_quiz_details') {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Error: ID Kuis tidak valid.']);
        exit;
    }
    $quiz_id = (int)$_GET['id'];

    $stmt_title = $mysqli->prepare("SELECT title FROM quizzes WHERE id = ?");
    $stmt_title->bind_param("i", $quiz_id);
    $stmt_title->execute();
    $quiz = $stmt_title->get_result()->fetch_assoc();
    $quiz_title = $quiz ? $quiz['title'] : 'Kuis Tidak Ditemukan';
    $stmt_title->close();

    $sql = "SELECT q.id as question_id, q.question_text, q.question_image, o.id as option_id, o.option_key, o.option_text, o.option_image, o.is_correct 
            FROM questions q 
            LEFT JOIN options o ON q.id = o.question_id 
            WHERE q.quiz_id = ? 
            ORDER BY q.id, o.option_key ASC";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $quiz_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $questions = [];
    while ($row = $result->fetch_assoc()) {
        $qid = $row['question_id'];
        if (!isset($questions[$qid])) {
            $questions[$qid] = [
                'question_text' => $row['question_text'],
                'question_image' => $row['question_image'],
                'options' => []
            ];
        }
        if ($row['option_id']) {
            $questions[$qid]['options'][$row['option_key']] = [
                'option_text' => $row['option_text'],
                'option_image' => $row['option_image'],
                'is_correct' => $row['is_correct']
            ];
        }
    }
    $stmt->close();
    $mysqli->close();

    echo json_encode(['success' => true, 'quiz_title' => $quiz_title, 'questions' => $questions]);
    exit;
}

// =================================================================
// AKSI: MENYIMPAN PERUBAHAN DARI EDITOR
// =================================================================
if ($action === 'save_quiz_details') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Metode request tidak valid.']);
        exit;
    }

    $questions_data = $_POST['questions'] ?? [];

    if (empty($questions_data)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tidak ada data untuk disimpan.']);
        exit;
    }

    $mysqli->begin_transaction();
    try {
        $stmt_update_q = $mysqli->prepare("UPDATE questions SET question_text = ?, question_image = ? WHERE id = ?");
        $stmt_update_o = $mysqli->prepare("UPDATE options SET option_text = ?, option_image = ? WHERE question_id = ? AND option_key = ?");
        $stmt_update_c = $mysqli->prepare("UPDATE options SET is_correct = ? WHERE question_id = ? AND option_key = ?");

        foreach ($questions_data as $qid => $q_data) {
            $question_text = $q_data['text'] ?? '';
            $question_image = !empty($q_data['image']) ? $q_data['image'] : null;
            $stmt_update_q->bind_param("ssi", $question_text, $question_image, $qid);
            $stmt_update_q->execute();

            $correct_key = $q_data['correct_answer'] ?? '';
            foreach (['A', 'B', 'C', 'D'] as $key) {
                $is_correct = ($key === $correct_key) ? 1 : 0;
                $stmt_update_c->bind_param("iis", $is_correct, $qid, $key);
                $stmt_update_c->execute();
            }

            if (isset($q_data['options'])) {
                foreach ($q_data['options'] as $key => $opt_data) {
                    $option_text = $opt_data['text'] ?? '';
                    $option_image = !empty($opt_data['image']) ? $opt_data['image'] : null;
                    $stmt_update_o->bind_param("ssis", $option_text, $option_image, $qid, $key);
                    $stmt_update_o->execute();
                }
            }
        }
        
        $mysqli->commit();
        echo json_encode(['success' => true, 'message' => 'Perubahan berhasil disimpan.']);

    } catch (Exception $e) {
        $mysqli->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    $stmt_update_q->close();
    $stmt_update_o->close();
    $stmt_update_c->close();
    $mysqli->close();
    exit;
}

// =================================================================
// AKSI: MENGUNGGAH DAN MENGOMPRES GAMBAR
// =================================================================
if ($action === 'upload_image') {
    if (!function_exists('imagecreatefromjpeg')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error Server: Library GD untuk pemrosesan gambar tidak aktif.']);
        exit;
    }

    if (isset($_FILES['image'])) {
        $file = $_FILES['image'];
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => "Error Server: Folder '$uploadDir' tidak ada atau tidak dapat ditulis."]);
            exit;
        }

        $fileName = uniqid() . '-' . basename($file['name']);
        $targetFilePath = $uploadDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

        $allowTypes = array('jpg', 'png', 'jpeg', 'gif');
        if (in_array($fileType, $allowTypes)) {
            if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
                $quality = 75;
                $new_image = null;
                if ($fileType == 'jpg' || $fileType == 'jpeg') {
                    $new_image = @imagecreatefromjpeg($targetFilePath);
                } elseif ($fileType == 'png') {
                    $new_image = @imagecreatefrompng($targetFilePath);
                } elseif ($fileType == 'gif') {
                    $new_image = @imagecreatefromgif($targetFilePath);
                }
                
                if ($new_image) {
                    if ($fileType == 'png') {
                        imagepalettetotruecolor($new_image);
                        imagealphablending($new_image, true);
                        imagesavealpha($new_image, true);
                        $pngQuality = round(($quality / 100) * 9);
                        imagepng($new_image, $targetFilePath, $pngQuality);
                    } elseif ($fileType == 'gif') {
                        imagegif($new_image, $targetFilePath);
                    } else {
                        imagejpeg($new_image, $targetFilePath, $quality);
                    }
                    imagedestroy($new_image);
                }

                echo json_encode(['success' => true, 'url' => 'uploads/' . $fileName]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal memindahkan file yang diunggah.']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Tipe file tidak valid. Hanya JPG, JPEG, PNG, & GIF yang diizinkan.']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tidak ada file gambar yang diterima.']);
    }
    exit;
}

// =================================================================
// AKSI: MENGHAPUS SOAL
// =================================================================
if ($action === 'delete_question') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['question_id']) || !is_numeric($_POST['question_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Permintaan tidak valid.']);
        exit;
    }
    $question_id = (int)$_POST['question_id'];

    $stmt = $mysqli->prepare("DELETE FROM questions WHERE id = ?");
    $stmt->bind_param("i", $question_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Pertanyaan berhasil dihapus.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus pertanyaan.']);
    }
    $stmt->close();
    $mysqli->close();
    exit;
}

// =================================================================
// AKSI: MENGHAPUS MATA KULIAH
// =================================================================
if ($action === 'delete_course') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['course_id']) || !is_numeric($_POST['course_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Permintaan tidak valid.']);
        exit;
    }
    $course_id = (int)$_POST['course_id'];

    $stmt = $mysqli->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->bind_param("i", $course_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Mata kuliah dan semua kuis terkait berhasil dihapus.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus mata kuliah.']);
    }
    $stmt->close();
    $mysqli->close();
    exit;
}


http_response_code(404);
echo json_encode(['success' => false, 'message' => 'Aksi tidak ditemukan.']);
?>
