<?php
require_once 'auth_check.php';
require_once '../config.php';

// Ambil semua fakultas dan jurusan untuk ditampilkan
$faculties = [];
$sql = "SELECT f.id as faculty_id, f.name as faculty_name, m.id as major_id, m.name as major_name 
        FROM faculties f 
        LEFT JOIN majors m ON f.id = m.faculty_id 
        ORDER BY f.name, m.name ASC";

if ($result = $mysqli->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $faculties[$row['faculty_id']]['name'] = $row['faculty_name'];
        if ($row['major_id']) {
            $faculties[$row['faculty_id']]['majors'][] = [
                'id' => $row['major_id'],
                'name' => $row['major_name']
            ];
        }
    }
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
    <title>Admin - Kelola Fakultas & Jurusan</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100">
    <header class="bg-gray-800 text-white shadow-lg p-4 flex justify-between items-center">
        <h1 class="text-xl font-bold">Kelola Struktur</h1>
        <a href="upload.php" class="bg-gray-700 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg transition-colors text-sm">Kembali ke Panel Utama</a>
    </header>

    <main class="container mx-auto mt-10 max-w-7xl px-4">
        <?php if ($message): ?>
            <div class="mb-4 p-4 rounded-md <?php echo strpos(strtolower($message), 'gagal') !== false ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Kolom untuk Menambah Data -->
            <div class="space-y-8">
                <!-- Form Tambah Fakultas -->
                <div class="bg-white p-8 rounded-lg shadow-md">
                    <h2 class="text-2xl font-bold text-slate-800 mb-4">Tambah Fakultas Baru</h2>
                    <form action="process_structure.php" method="post">
                        <input type="hidden" name="action" value="add_faculty">
                        <label for="faculty_name" class="block text-slate-700 mb-2">Nama Fakultas</label>
                        <input type="text" name="faculty_name" id="faculty_name" class="w-full border-slate-300 rounded-lg p-3" required>
                        <button type="submit" class="mt-4 w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg">Tambah Fakultas</button>
                    </form>
                </div>

                <!-- Form Tambah Jurusan -->
                <div class="bg-white p-8 rounded-lg shadow-md">
                    <h2 class="text-2xl font-bold text-slate-800 mb-4">Tambah Jurusan Baru</h2>
                    <form action="process_structure.php" method="post">
                        <input type="hidden" name="action" value="add_major">
                        <div class="mb-4">
                            <label for="major_name" class="block text-slate-700 mb-2">Nama Jurusan</label>
                            <input type="text" name="major_name" id="major_name" class="w-full border-slate-300 rounded-lg p-3" required>
                        </div>
                        <div class="mb-4">
                            <label for="faculty_id" class="block text-slate-700 mb-2">Pilih Fakultas</label>
                            <select name="faculty_id" id="faculty_id" class="w-full border-slate-300 rounded-lg p-3 bg-white" required>
                                <option value="">-- Pilih Fakultas --</option>
                                <?php foreach ($faculties as $id => $faculty): ?>
                                    <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($faculty['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="mt-4 w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg">Tambah Jurusan</button>
                    </form>
                </div>
            </div>

            <!-- Kolom untuk Melihat & Menghapus Data -->
            <div class="bg-white p-8 rounded-lg shadow-md">
                <h2 class="text-2xl font-bold text-slate-800 mb-4">Struktur Saat Ini</h2>
                <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-2">
                    <?php foreach ($faculties as $id => $faculty): ?>
                        <div class="border rounded-lg p-4">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-bold"><?php echo htmlspecialchars($faculty['name']); ?></h3>
                                <form action="process_structure.php" method="post" onsubmit="return confirm('Yakin ingin menghapus fakultas ini? Semua jurusan di dalamnya akan ikut terhapus.');">
                                    <input type="hidden" name="action" value="delete_faculty">
                                    <input type="hidden" name="faculty_id" value="<?php echo $id; ?>">
                                    <button type="submit" class="text-red-500 hover:text-red-700 font-bold">&times;</button>
                                </form>
                            </div>
                            <ul class="mt-2 ml-4 space-y-1 list-disc list-inside">
                                <?php if (!empty($faculty['majors'])): ?>
                                    <?php foreach ($faculty['majors'] as $major): ?>
                                        <li class="flex justify-between items-center">
                                            <span><?php echo htmlspecialchars($major['name']); ?></span>
                                            <form action="process_structure.php" method="post" onsubmit="return confirm('Yakin ingin menghapus jurusan ini?');">
                                                <input type="hidden" name="action" value="delete_major">
                                                <input type="hidden" name="major_id" value="<?php echo $major['id']; ?>">
                                                <button type="submit" class="text-red-500 hover:text-red-700 text-sm">&times;</button>
                                            </form>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="text-slate-500 text-sm">Belum ada jurusan.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
