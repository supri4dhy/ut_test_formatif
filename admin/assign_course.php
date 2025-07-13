<?php
session_start();
require_once '../config.php';
require_once 'auth_check.php';

// Ambil semua mata kuliah untuk dropdown
$courses = [];
$sql_courses = "SELECT id, name FROM courses ORDER BY name ASC";
if ($result = $mysqli->query($sql_courses)) {
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
}

// Ambil semua fakultas beserta jurusannya
$faculties = [];
$sql_faculties = "SELECT id, name FROM faculties ORDER BY name ASC";
if ($result_faculties = $mysqli->query($sql_faculties)) {
    while ($faculty = $result_faculties->fetch_assoc()) {
        $faculty_id = $faculty['id'];
        $faculties[$faculty_id] = [
            'name' => $faculty['name'],
            'majors' => []
        ];

        $stmt_majors = $mysqli->prepare("SELECT id, name FROM majors WHERE faculty_id = ? ORDER BY name ASC");
        $stmt_majors->bind_param("i", $faculty_id);
        $stmt_majors->execute();
        $result_majors = $stmt_majors->get_result();
        while ($major = $result_majors->fetch_assoc()) {
            $faculties[$faculty_id]['majors'][] = $major;
        }
        $stmt_majors->close();
    }
}

// Cek apakah sebuah mata kuliah sudah dipilih untuk diedit
$selected_course_id = null;
$assigned_majors = [];
if (isset($_GET['course_id']) && is_numeric($_GET['course_id'])) {
    $selected_course_id = (int)$_GET['course_id'];
    
    // Ambil jurusan yang sudah terhubung dengan mata kuliah ini
    $stmt = $mysqli->prepare("SELECT major_id FROM major_courses WHERE course_id = ?");
    $stmt->bind_param("i", $selected_course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $assigned_majors[] = $row['major_id'];
    }
    $stmt->close();
}

$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Atur Mata Kuliah ke Jurusan</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100">
    <div class="container mx-auto mt-10 max-w-3xl">
        <h1 class="text-3xl font-bold text-slate-800 mb-6 text-center">Admin Panel: Penugasan Mata Kuliah</h1>

        <?php if ($message): ?>
            <div class="mb-4 p-4 rounded-md bg-green-100 text-green-700">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Form untuk memilih mata kuliah -->
        <form action="assign_course.php" method="get" class="bg-white p-8 rounded-lg shadow-md mb-6">
             <label for="course_id_select" class="block text-lg font-medium text-slate-700 mb-2">1. Pilih Mata Kuliah</label>
             <div class="flex gap-4">
                <select name="course_id" id="course_id_select" class="block w-full p-3 border border-slate-300 rounded-lg shadow-sm">
                    <option value="">-- Pilih Mata Kuliah untuk Diatur --</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['id']; ?>" <?php echo ($selected_course_id == $course['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg">Pilih</button>
             </div>
        </form>

        <!-- Form untuk checklist jurusan (hanya tampil jika mata kuliah sudah dipilih) -->
        <?php if ($selected_course_id): ?>
            <form action="process_assignment.php" method="post" class="bg-white p-8 rounded-lg shadow-md">
                <input type="hidden" name="course_id" value="<?php echo $selected_course_id; ?>">
                <h2 class="text-lg font-medium text-slate-700 mb-4">2. Pilih Jurusan yang Mengambil Mata Kuliah Ini</h2>
                <div class="space-y-6">
                    <!-- Looping berdasarkan fakultas -->
                    <?php foreach ($faculties as $faculty): ?>
                        <div>
                            <h3 class="font-bold text-slate-800 border-b pb-2 mb-3"><?php echo htmlspecialchars($faculty['name']); ?></h3>
                            <div class="space-y-3 pl-2">
                                <?php foreach ($faculty['majors'] as $major): ?>
                                    <div class="flex items-center">
                                        <input type="checkbox" name="major_ids[]" id="major_<?php echo $major['id']; ?>" value="<?php echo $major['id']; ?>" class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                            <?php echo in_array($major['id'], $assigned_majors) ? 'checked' : ''; ?>
                                        >
                                        <label for="major_<?php echo $major['id']; ?>" class="ml-3 min-w-0 flex-1 text-slate-600">
                                            <?php echo htmlspecialchars($major['name']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-right mt-6">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg">Simpan Penugasan</button>
                </div>
            </form>
        <?php endif; ?>

        <div class="text-center mt-6">
            <a href="upload.php" class="text-blue-600 hover:underline">&larr; Kembali ke Panel Utama</a>
        </div>
    </div>
</body>
</html>
