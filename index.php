<?php
require_once "config.php";

// Ambil semua data dengan struktur Fakultas -> Jurusan
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
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Jurusan - Kuis Interaktif</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-image: linear-gradient(to bottom, #e0e7ff, #f0f4f8); }
        .header { background-image: linear-gradient(to right, #4A5568, #2D3748); }
        .faculty-card {
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            overflow: hidden;
        }
        .faculty-header {
            cursor: pointer;
            padding: 1.5rem;
            transition: background-color 0.2s;
        }
        .faculty-header:hover {
            background-color: #f8fafc;
        }
        .major-list a {
            transition: background-color 0.2s;
        }
        .major-list a:hover {
            background-color: #f1f5f9;
        }
        .admin-link { text-decoration: none; color: inherit; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen">
    <header class="header text-white p-6 shadow-lg">
        <div class="text-center">
             <h1 class="text-2xl sm:text-3xl font-bold">
                <a href="admin/login.php" class="admin-link" title="Admin Login">S</a>elamat Datang di Kuis Interaktif
             </h1>
             <p class="mt-2 text-slate-300">Langkah 1: Pilih Fakultas dan Jurusan Anda</p>
        </div>
    </header>
    <main class="container mx-auto p-4 md:p-8">
        <div class="space-y-6">
            <?php if (!empty($faculties)): ?>
                <?php foreach ($faculties as $faculty): ?>
                    <!-- Kartu Fakultas -->
                    <details class="faculty-card" open>
                        <summary class="faculty-header">
                            <h2 class="text-2xl font-bold text-slate-800"><?php echo htmlspecialchars($faculty['name']); ?></h2>
                        </summary>
                        <div class="major-list border-t border-slate-200">
                            <?php if (!empty($faculty['majors'])): ?>
                                <?php foreach ($faculty['majors'] as $major): ?>
                                    <a href="courses.php?major_id=<?php echo htmlspecialchars($major['id']); ?>" class="block px-6 py-4 border-b border-slate-100 last:border-b-0">
                                        <div class="flex justify-between items-center">
                                            <p class="text-lg text-slate-700"><?php echo htmlspecialchars($major['name']); ?></p>
                                            <span class="text-blue-600 font-semibold">&rarr;</span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="p-6 text-slate-500">Belum ada jurusan untuk fakultas ini.</p>
                            <?php endif; ?>
                        </div>
                    </details>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="bg-white p-8 rounded-lg shadow-md text-center">
                    <h2 class="text-2xl font-bold text-slate-700">Oops!</h2>
                    <p class="text-slate-500 mt-2">Saat ini belum ada fakultas yang tersedia.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
