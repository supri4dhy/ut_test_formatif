<?php
require_once "config.php";

// Validasi ID Jurusan dari URL
if(!isset($_GET['major_id']) || !is_numeric($_GET['major_id'])){
    die("Error: ID Jurusan tidak valid.");
}
$major_id = (int)$_GET['major_id'];

// Ambil nama jurusan untuk ditampilkan di header
$major_name = "Pilih Mata Kuliah";
$stmt_major = $mysqli->prepare("SELECT name FROM majors WHERE id = ?");
$stmt_major->bind_param("i", $major_id);
$stmt_major->execute();
$stmt_major->bind_result($name);
if($stmt_major->fetch()) { 
    $major_name = $name; 
}
$stmt_major->close();

// Ambil semua mata kuliah yang terhubung dengan jurusan ini
$courses = [];
$sql = "SELECT c.id, c.name 
        FROM courses c
        JOIN major_courses mc ON c.id = mc.course_id
        WHERE mc.major_id = ?
        ORDER BY c.name ASC";

if($stmt = $mysqli->prepare($sql)){
    $stmt->bind_param("i", $major_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0){ 
        while($row = $result->fetch_assoc()){ 
            $courses[] = $row; 
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
    <title>Pilih Mata Kuliah - <?php echo htmlspecialchars($major_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-image: linear-gradient(to bottom, #e0e7ff, #f0f4f8); }
        .header { background-image: linear-gradient(to right, #4A5568, #2D3748); display: flex; justify-content: space-between; align-items: center; padding: 1rem; }
        .header-btn { padding: 0.5rem 0.75rem; background-color: rgba(255,255,255,0.1); border-radius: 0.375rem; transition: background-color 0.2s; display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; }
        .header-btn:hover { background-color: rgba(255,255,255,0.2); }
        #header-title { overflow: hidden; white-space: nowrap; flex: 1; min-width: 0; }
        #header-title.is-overflowing span { display: inline-block; padding-left: 100%; animation: marquee 15s linear infinite; }
        @keyframes marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-200%); } }
        .card { transition: transform 0.2s, box-shadow 0.2s; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-slate-100 min-h-screen">
    <header class="header text-white shadow-lg">
        <a href="index.php" title="Halaman Utama" class="header-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span class="hidden sm:inline">Home</span>
        </a>
        <h1 id="header-title" class="text-xl sm:text-2xl font-bold px-2 text-center">
            <span><?php echo htmlspecialchars($major_name); ?></span>
        </h1>
        <a href="index.php" title="Kembali ke Daftar Fakultas" class="header-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 8 8 12 12 16"></polyline><line x1="16" y1="12" x2="8" y2="12"></line></svg>
            <span class="hidden sm:inline">Kembali</span>
        </a>
    </header>
    <main class="container mx-auto p-4 md:p-8">
        <p class="text-center text-slate-600 -mt-4 mb-6">Langkah 2: Pilih Mata Kuliah</p>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (!empty($courses)): ?>
                <?php foreach ($courses as $course): ?>
                    <a href="quizzes_list.php?major_id=<?php echo $major_id; ?>&course_id=<?php echo htmlspecialchars($course['id']); ?>" class="card block bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-bold text-slate-800"><?php echo htmlspecialchars($course['name']); ?></h2>
                        <p class="text-slate-500 mt-2">Lihat daftar kuis &rarr;</p>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="md:col-span-2 lg:col-span-3 bg-white p-8 rounded-lg shadow-md text-center">
                    <p class="text-slate-500">Belum ada mata kuliah yang ditugaskan untuk jurusan ini.</p>
                </div>
            <?php endif; ?>
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
