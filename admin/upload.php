<?php
require_once 'auth_check.php';
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
    <title>Admin - Panel Utama</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .header { background-image: linear-gradient(to right, #1f2937, #374151); }
        .header-btn { padding: 0.5rem 0.75rem; background-color: rgba(255,255,255,0.1); border-radius: 0.375rem; transition: background-color 0.2s; display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; }
        .header-btn:hover { background-color: rgba(255,255,255,0.2); }
    </style>
</head>
<body class="bg-slate-100">
    <header class="header text-white shadow-lg p-4 flex justify-between items-center">
        <h1 class="text-xl font-bold">Admin Panel</h1>
        <a href="../index.php" class="header-btn">
            <span class="hidden sm:inline">Logout</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
        </a>
    </header>

    <div class="container mx-auto mt-10 max-w-7xl px-4">
        <?php if ($message): ?>
            <div class="mb-4 p-4 rounded-md <?php echo strpos(strtolower($message), 'gagal') !== false || strpos(strtolower($message), 'error') !== false ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?>">
                <?php echo nl2br(htmlspecialchars($message)); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <div class="lg:col-span-9">
                <div class="bg-white p-8 rounded-lg shadow-md">
                    <h2 class="text-2xl font-bold text-slate-800 mb-4">1. Unggah File Kuis</h2>
                    <p class="text-sm text-slate-500 mb-4">Unggah file Excel untuk membuat Mata Kuliah dan Kuis baru.</p>
                    <form id="upload-form" action="process_upload.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="overwrite" id="overwrite-input" value="false">
                        <div class="mb-6">
                            <label for="excelFile" class="block text-lg font-medium text-slate-700 mb-2">Pilih File (.xlsx)</label>
                            <input type="file" name="excelFile" id="excelFile" required class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        </div>
                        <button type="submit" name="upload_submit" id="submit-button" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg">Unggah Kuis</button>
                    </form>
                </div>
            </div>
            <div class="lg:col-span-3">
                <div class="bg-white p-8 rounded-lg shadow-md h-full flex flex-col">
                    <h2 class="text-2xl font-bold text-slate-800 mb-4">Template</h2>
                    <p class="text-sm text-slate-500 mb-4 flex-grow">Gunakan template ini untuk memastikan format unggahan Anda benar.</p>
                    <a href="generate_template.php" class="block w-full text-center bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold py-3 px-4 rounded-lg transition-colors text-sm">
                        Unduh Template &rarr;
                    </a>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-8 rounded-lg shadow-md mt-8">
            <h2 class="text-2xl font-bold text-slate-800 mb-4">2. Manajemen Konten</h2>
            <div class="grid sm:grid-cols-3 gap-6">
                <a href="manage_structure.php" class="block text-center bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 px-6 rounded-lg">
                    <h3 class="text-lg">Kelola Struktur</h3>
                    <p class="text-sm font-normal opacity-80">Tambah/Hapus Fakultas & Jurusan</p>
                </a>
                <a href="assign_course.php" class="block text-center bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-6 rounded-lg">
                    <h3 class="text-lg">Atur Penugasan</h3>
                    <p class="text-sm font-normal opacity-80">Hubungkan Mata Kuliah ke Jurusan</p>
                </a>
                <a href="edit_dashboard.php" class="block text-center bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-4 px-6 rounded-lg">
                    <h3 class="text-lg">Edit Kuis & Gambar</h3>
                    <p class="text-sm font-normal opacity-80">Ubah soal dan unggah gambar</p>
                </a>
            </div>
        </div>
    </div>
    
    <div id="confirmation-modal" class="modal-backdrop hidden">
        <div class="modal-content">
            <h3 class="text-xl font-bold mb-2">Konfirmasi Perbarui Data</h3>
            <p id="modal-text" class="text-slate-600 mb-6"></p>
            <div class="flex justify-end gap-4">
                <button id="cancel-btn" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold py-2 px-4 rounded-lg">Tidak, Batalkan</button>
                <button id="confirm-btn" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg">Ya, Perbarui</button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const uploadForm = document.getElementById('upload-form');
        const submitButton = document.getElementById('submit-button');
        const modal = document.getElementById('confirmation-modal');
        const modalText = document.getElementById('modal-text');
        const confirmBtn = document.getElementById('confirm-btn');
        const cancelBtn = document.getElementById('cancel-btn');
        const overwriteInput = document.getElementById('overwrite-input');

        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(uploadForm);
            if (!formData.get('excelFile').name) {
                alert('Silakan pilih file untuk diunggah.');
                return;
            }

            submitButton.disabled = true;
            submitButton.textContent = 'Memeriksa...';

            fetch('check_duplicates.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'duplicates_found') {
                    modalText.innerHTML = `Mata kuliah <strong>${data.course_name}</strong> sudah ada dan berisi kuis dengan nama yang sama: <br><strong>- ${data.duplicates.join('<br>- ')}</strong><br><br>Apakah Anda ingin menghapus data lama dan menggantinya dengan yang baru?`;
                    modal.classList.remove('hidden');
                } else if (data.status === 'no_duplicates') {
                    overwriteInput.value = 'false';
                    uploadForm.submit();
                } else {
                    throw new Error(data.message || 'Terjadi kesalahan yang tidak diketahui.');
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
                submitButton.disabled = false;
                submitButton.textContent = 'Unggah Kuis';
            });
        });

        confirmBtn.addEventListener('click', function() {
            overwriteInput.value = 'true';
            submitButton.textContent = 'Memperbarui...';
            uploadForm.submit();
        });

        cancelBtn.addEventListener('click', function() {
            modal.classList.add('hidden');
            submitButton.disabled = false;
            submitButton.textContent = 'Unggah Kuis';
            uploadForm.reset();
        });
    });
    </script>
</body>
</html>
