<?php
require_once "config.php";

// Validasi ID Mata Kuliah dan Jurusan dari URL
if(!isset($_GET['course_id']) || !is_numeric($_GET['course_id']) || !isset($_GET['major_id']) || !is_numeric($_GET['major_id'])){
    die("Error: ID Mata Kuliah atau Jurusan tidak valid.");
}
$course_id = (int)$_GET['course_id'];
$major_id = (int)$_GET['major_id']; // Ambil major_id untuk diteruskan

// Ambil nama mata kuliah untuk ditampilkan di header
$course_name = "Daftar Kuis";
$stmt_course = $mysqli->prepare("SELECT name FROM courses WHERE id = ?");
$stmt_course->bind_param("i", $course_id);
$stmt_course->execute();
$stmt_course->bind_result($name);
if($stmt_course->fetch()) { 
    $course_name = $name; 
}
$stmt_course->close();

// Ambil semua sesi kuis untuk mata kuliah ini
$quizzes = [];
$sql = "SELECT id, title FROM quizzes WHERE course_id = ? ORDER BY title ASC";
if($stmt = $mysqli->prepare($sql)){
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){ 
        while($row = $result->fetch_assoc()){ 
            $quizzes[] = $row; 
        } 
    }
    $stmt->close();
}
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Kuis - <?php echo htmlspecialchars($course_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-image: linear-gradient(to bottom, #e0e7ff, #f0f4f8); }
        .header { background-image: linear-gradient(to right, #4A5568, #2D3748); display: flex; justify-content: space-between; align-items: center; padding: 1rem; }
        .header-btn { padding: 0.5rem 0.75rem; background-color: rgba(255,255,255,0.1); border-radius: 0.375rem; transition: background-color 0.2s; display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; }
        .header-btn:hover { background-color: rgba(255,255,255,0.2); }
        #header-title { overflow: hidden; white-space: nowrap; flex: 1; min-width: 0; }
        #header-title.is-overflowing span { display: inline-block; padding-left: 100%; animation: marquee 15s linear infinite; }
        @keyframes marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-200%); } }
        .card { transition: background-color 0.2s; }
        .card:hover { background-color: #f1f5f9; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen">
    <header class="header text-white shadow-lg">
        <a href="index.php" title="Halaman Utama" class="header-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span class="hidden sm:inline">Home</span>
        </a>
        <h1 id="header-title" class="text-xl sm:text-2xl font-bold px-2 text-center">
            <span><?php echo htmlspecialchars($course_name); ?></span>
        </h1>
        <a href="courses.php?major_id=<?php echo $major_id; ?>" title="Kembali ke Daftar Mata Kuliah" class="header-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 8 8 12 12 16"></polyline><line x1="16" y1="12" x2="8" y2="12"></line></svg>
            <span class="hidden sm:inline">Kembali</span>
        </a>
    </header>
    <main class="container mx-auto p-4 md:p-8">
        <p class="text-center text-slate-600 -mt-4 mb-6">Langkah 3: Pilih Sesi Kuis</p>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <ul class="divide-y divide-slate-200">
            <?php if (!empty($quizzes)): ?>
                <?php foreach ($quizzes as $quiz): ?>
                    <li class="card">
                        <a href="quiz.php?id=<?php echo htmlspecialchars($quiz['id']); ?>&major_id=<?php echo $major_id; ?>" class="block p-4 sm:p-6">
                            <div class="flex items-center justify-between">
                                <p class="text-lg font-medium text-blue-600 truncate"><?php echo htmlspecialchars($quiz['title']); ?></p>
                                <span class="ml-4 text-slate-500">&rarr;</span>
                            </div>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="p-6 text-center">Belum ada sesi kuis untuk mata kuliah ini.</li>
            <?php endif; ?>
            </ul>
        </div>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const titleContainer = document.getElementById('header-title');
            const titleSpan = titleContainer.querySelector('span');
            if (titleSpan.scrollWidth > titleContainer.clientWidth) {
                titleContainer.classList.add('is-overflowing');
            }
        });
    </script>
</body>
</html>
