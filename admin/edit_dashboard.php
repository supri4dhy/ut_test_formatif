<?php
require_once 'auth_check.php';
require_once '../config.php';

// Logika untuk mengambil struktur Mata Kuliah -> Kuis untuk sidebar
$courses_structure = [];
$sql_courses = "SELECT id, name FROM courses ORDER BY name ASC";
$result_courses = $mysqli->query($sql_courses);
while ($course = $result_courses->fetch_assoc()) {
    $course_id = $course['id'];
    $courses_structure[$course_id] = ['name' => $course['name'], 'quizzes' => []];
    $sql_quizzes = "SELECT id, title FROM quizzes WHERE course_id = ? ORDER BY title ASC";
    $stmt_quizzes = $mysqli->prepare($sql_quizzes);
    $stmt_quizzes->bind_param("i", $course_id);
    $stmt_quizzes->execute();
    $result_quizzes = $stmt_quizzes->get_result();
    while ($quiz = $result_quizzes->fetch_assoc()) {
        $courses_structure[$course_id]['quizzes'][] = $quiz;
    }
    $stmt_quizzes->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Editor Kuis</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .header { background-image: linear-gradient(to right, #1f2937, #374151); }
        .header-btn { padding: 0.5rem 0.75rem; background-color: rgba(255,255,255,0.1); border-radius: 0.375rem; transition: background-color 0.2s; display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; }
        .header-btn:hover { background-color: rgba(255,255,255,0.2); }
        #editor-container { display: grid; grid-template-columns: 300px 1fr; height: calc(100vh - 64px); }
        #quiz-list-sidebar { background-color: #f8fafc; border-right: 1px solid #e2e8f0; overflow-y: auto; }
        #editor-panel { background-color: #f1f5f9; overflow-y: auto; padding: 2rem; }
        .quiz-item-container { display: flex; justify-content: space-between; align-items: center; border-radius: 0.25rem; }
        .quiz-item-container:hover { background-color: #f1f5f9; }
        .quiz-item { cursor: pointer; flex-grow: 1; }
        .quiz-item.active { background-color: #e0e7ff; font-weight: bold; }
        .delete-btn { opacity: 0; transition: opacity 0.2s; }
        .quiz-item-container:hover .delete-btn { opacity: 1; }
        .question-card { transition: box-shadow 0.2s; }
        .question-card:focus-within { box-shadow: 0 0 0 2px #3b82f6; }
        .image-upload-area { border: 2px dashed #cbd5e0; cursor: pointer; }
        .image-upload-area.dragover { border-color: #3b82f6; background-color: #eff6ff; }
        .image-preview-container { position: relative; }
        .delete-image-btn { position: absolute; top: 0.5rem; right: 0.5rem; background-color: rgba(0,0,0,0.6); color: white; border-radius: 9999px; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; line-height: 1; font-size: 1.2rem; }
        .delete-image-btn:hover { background-color: rgba(239, 68, 68, 0.8); }
        .floating-save-bar { position: sticky; bottom: 0; background-color: rgba(255, 255, 255, 0.9); backdrop-filter: blur(5px); -webkit-backdrop-filter: blur(5px); padding: 1rem; border-top: 1px solid #e2e8f0; box-shadow: 0 -4px 6px -1px rgb(0 0 0 / 0.05); margin-left: -2rem; margin-right: -2rem; text-align: right; }
        #edit-quiz-form { padding-bottom: 120px; }
        .square-uploader { aspect-ratio: 1 / 1; }
    </style>
</head>
<body class="bg-slate-100">
    <header class="header text-white shadow-lg p-4 flex justify-between items-center" style="height: 64px;">
        <h1 class="text-xl font-bold">Dashboard Editor Kuis</h1>
        <a href="upload.php" class="header-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"></path><path d="M12 19l-7-7 7-7"></path></svg>
            <span>Kembali ke Panel Utama</span>
        </a>
    </header>

    <div id="editor-container">
        <aside id="quiz-list-sidebar" class="p-4">
            <h2 class="text-lg font-bold text-slate-700 mb-4">Pilih Kuis untuk Diedit</h2>
            <div class="space-y-2">
                <?php foreach ($courses_structure as $course_id => $course): ?>
                    <details id="course-details-<?php echo $course_id; ?>">
                        <summary class="font-semibold text-slate-800 cursor-pointer p-2 rounded hover:bg-slate-100 flex justify-between items-center">
                            <span><?php echo htmlspecialchars($course['name']); ?></span>
                            <button title="Hapus Mata Kuliah" class="delete-course-btn text-red-400 hover:text-red-600 p-1" data-course-id="<?php echo $course_id; ?>" data-course-name="<?php echo htmlspecialchars($course['name']); ?>">&times;</button>
                        </summary>
                        <div class="pl-4 mt-1">
                            <?php if (!empty($course['quizzes'])): ?>
                                <?php foreach ($course['quizzes'] as $quiz): ?>
                                    <div class="quiz-item-container" id="quiz-item-container-<?php echo $quiz['id']; ?>">
                                        <p class="quiz-item p-2 rounded text-sm" data-quiz-id="<?php echo $quiz['id']; ?>"><?php echo htmlspecialchars($quiz['title']); ?></p>
                                        <button title="Hapus Sesi Kuis" class="delete-quiz-btn delete-btn text-red-400 hover:text-red-600 p-1" data-quiz-id="<?php echo $quiz['id']; ?>" data-quiz-title="<?php echo htmlspecialchars($quiz['title']); ?>">&times;</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="p-2 text-xs text-slate-500">Belum ada kuis.</p>
                            <?php endif; ?>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        </aside>

        <main id="editor-panel">
            <div id="editor-placeholder" class="text-center text-slate-500 pt-10">
                <p class="text-xl">Silakan pilih kuis dari panel kiri untuk mulai mengedit.</p>
            </div>
            <div id="editor-content" class="hidden"></div>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const editorContent = document.getElementById('editor-content');
        
        function renderEditor(data, quizId) {
            let html = `
                <h2 class="text-2xl font-bold text-slate-800 mb-4">Mengedit: ${data.quiz_title}</h2>
                <form id="edit-quiz-form">
                    <input type="hidden" name="quiz_id" value="${quizId}">
                    <div class="space-y-8">
            `;
            
            let questionCounter = 1;
            for (const qid in data.questions) {
                const q = data.questions[qid];
                html += `
                    <div id="question-card-${qid}" class="question-card bg-white p-6 rounded-lg shadow">
                        <input type="hidden" name="questions[${qid}][id]" value="${qid}">
                        <div class="flex justify-between items-start mb-4">
                            <label class="block text-sm font-medium text-slate-700">Teks Pertanyaan #${questionCounter}</label>
                            <button type="button" title="Hapus Pertanyaan" class="delete-question-btn text-red-400 hover:text-red-600 font-bold text-xl" data-qid="${qid}">&times;</button>
                        </div>
                        <!-- DIUBAH: Menggunakan grid 12 kolom -->
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                            <div class="md:col-span-9">
                                <textarea name="questions[${qid}][text]" class="w-full border-slate-300 rounded-md shadow-sm p-2" rows="4">${q.question_text || ''}</textarea>
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-sm font-medium text-slate-700 mb-1">Gambar Pertanyaan</label>
                                ${createImageUploader(`questions[${qid}][image]`, q.question_image)}
                            </div>
                        </div>
                        <div class="mt-6">
                            <h4 class="font-semibold mb-2">Pilihan Jawaban</h4>
                            <div class="space-y-4">
                `;
                ['A', 'B', 'C', 'D'].forEach(key => {
                    const opt = q.options[key] || { option_text: '', option_image: '', is_correct: 0 };
                    const checked = opt.is_correct ? 'checked' : '';
                    html += `
                        <!-- DIUBAH: Menggunakan grid 12 kolom untuk pilihan jawaban -->
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 border-t pt-4 items-start">
                            <div class="flex items-center gap-3 md:col-span-9">
                                <input type="radio" name="questions[${qid}][correct_answer]" value="${key}" ${checked} required class="h-5 w-5 text-blue-600">
                                <span class="font-bold">${key}.</span>
                                <textarea name="questions[${qid}][options][${key}][text]" class="flex-grow border-slate-300 rounded-md shadow-sm p-2" rows="3" placeholder="Teks Pilihan ${key}">${opt.option_text || ''}</textarea>
                            </div>
                            <div class="md:col-span-3">
                                ${createImageUploader(`questions[${qid}][options][${key}][image]`, opt.option_image, 'sm')}
                            </div>
                        </div>
                    `;
                });
                html += `</div></div></div>`;
                questionCounter++;
            }

            html += `</div></form>
                <div class="floating-save-bar">
                    <button type="submit" form="edit-quiz-form" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg">Simpan Semua Perubahan</button>
                </div>`;
            editorContent.innerHTML = html;
            initAllUploadHandlers();
            initDeleteHandlers();
            
            const editForm = document.getElementById('edit-quiz-form');
            if(editForm) {
                editForm.addEventListener('submit', handleFormSubmit);
            }
        }

        function createImageUploader(inputName, imageUrl, size = 'md') {
            const hasImage = imageUrl && imageUrl.trim() !== '';
            const previewDisplay = hasImage ? 'block' : 'hidden';
            const uploaderDisplay = hasImage ? 'hidden' : 'flex';
            const heightClass = size === 'sm' ? 'h-28' : 'h-40';
            const imagePath = hasImage ? `../${imageUrl}` : '';

            return `
                <div class="image-upload-wrapper">
                    <input type="hidden" name="${inputName}" value="${imageUrl || ''}" class="image-url-input">
                    <div class="image-preview-container ${previewDisplay}">
                        <img src="${imagePath}" class="w-full ${heightClass} square-uploader object-contain rounded bg-slate-200">
                        <button type="button" class="delete-image-btn" title="Hapus Gambar">&times;</button>
                    </div>
                    <div class="image-upload-area ${uploaderDisplay} ${heightClass} square-uploader items-center justify-center rounded-lg text-center text-slate-500 p-2" contenteditable="true">
                        Drop, paste, atau klik
                    </div>
                </div>
            `;
        }
        
        // Sisa kode JavaScript tidak berubah
        const quizItems = document.querySelectorAll('.quiz-item'); const editorPlaceholder = document.getElementById('editor-placeholder'); let activeQuizItem = null;
        function initDeleteHandlers() { document.querySelectorAll('.delete-question-btn').forEach(btn => { btn.addEventListener('click', function() { const qid = this.dataset.qid; if (confirm(`Apakah Anda yakin ingin menghapus Pertanyaan ini secara permanen?`)) { const formData = new FormData(); formData.append('question_id', qid); fetch('ajax_handler.php?action=delete_question', { method: 'POST', body: formData }).then(res => res.json()).then(data => { if (data.success) { document.getElementById(`question-card-${qid}`).remove(); alert(data.message); } else { throw new Error(data.message); } }).catch(err => alert('Error: ' + err.message)); } }); }); }
        function attachSidebarListeners() { document.querySelectorAll('.delete-course-btn').forEach(btn => { btn.addEventListener('click', function(e) { e.stopPropagation(); const courseId = this.dataset.courseId; const courseName = this.dataset.courseName; if (confirm(`Apakah Anda yakin ingin menghapus mata kuliah "${courseName}"? Ini akan menghapus SEMUA kuis dan soal di dalamnya secara permanen.`)) { const formData = new FormData(); formData.append('course_id', courseId); fetch('ajax_handler.php?action=delete_course', { method: 'POST', body: formData }).then(res => res.json()).then(data => { if (data.success) { document.getElementById(`course-details-${courseId}`).remove(); alert(data.message); if (activeQuizItem && activeQuizItem.closest(`#course-details-${courseId}`)) { editorContent.innerHTML = ''; editorContent.classList.add('hidden'); document.getElementById('editor-placeholder').classList.remove('hidden'); } } else { throw new Error(data.message); } }).catch(err => alert('Error: ' + err.message)); } }); }); document.querySelectorAll('.delete-quiz-btn').forEach(btn => { btn.addEventListener('click', function(e) { e.stopPropagation(); const quizId = this.dataset.quizId; const quizTitle = this.dataset.quizTitle; if (confirm(`Apakah Anda yakin ingin menghapus sesi kuis "${quizTitle}"? Ini akan menghapus SEMUA soal di dalamnya secara permanen.`)) { const formData = new FormData(); formData.append('quiz_id', quizId); fetch('ajax_handler.php?action=delete_quiz', { method: 'POST', body: formData }).then(res => res.json()).then(data => { if (data.success) { document.getElementById(`quiz-item-container-${quizId}`).remove(); alert(data.message); if (activeQuizItem && activeQuizItem.dataset.quizId === quizId) { editorContent.innerHTML = ''; editorContent.classList.add('hidden'); document.getElementById('editor-placeholder').classList.remove('hidden'); } } else { throw new Error(data.message); } }).catch(err => alert('Error: ' + err.message)); } }); }); quizItems.forEach(item => { item.addEventListener('click', function() { const quizId = this.dataset.quizId; if (activeQuizItem) activeQuizItem.classList.remove('active'); this.classList.add('active'); activeQuizItem = this; loadEditor(quizId); }); }); }
        attachSidebarListeners();
        function loadEditor(quizId) { editorPlaceholder.classList.add('hidden'); editorContent.classList.remove('hidden'); editorContent.innerHTML = '<p class="text-center">Memuat pertanyaan...</p>'; fetch(`ajax_handler.php?action=get_quiz_details&id=${quizId}`).then(response => response.json()).then(data => { if (data.success) { renderEditor(data, quizId); } else { throw new Error(data.message); } }).catch(error => { editorContent.innerHTML = `<p class="text-center text-red-500">Gagal memuat editor: ${error.message}</p>`; }); }
        function initAllUploadHandlers() { document.querySelectorAll('.image-upload-area').forEach(area => { const wrapper = area.closest('.image-upload-wrapper'); area.addEventListener('click', () => { const fileInput = document.createElement('input'); fileInput.type = 'file'; fileInput.accept = 'image/*'; fileInput.onchange = e => { if (e.target.files.length) handleFileUpload(e.target.files[0], wrapper); }; fileInput.click(); }); ['dragover', 'dragleave', 'drop'].forEach(eventName => { area.addEventListener(eventName, e => { e.preventDefault(); e.stopPropagation(); if (eventName === 'dragover') area.classList.add('dragover'); else area.classList.remove('dragover'); if (eventName === 'drop' && e.dataTransfer.files.length) { handleFileUpload(e.dataTransfer.files[0], wrapper); } }); }); area.addEventListener('paste', e => { if (e.clipboardData.files.length > 0) { e.preventDefault(); handleFileUpload(e.clipboardData.files[0], wrapper); } }); }); document.querySelectorAll('.delete-image-btn').forEach(btn => { btn.addEventListener('click', function() { const wrapper = this.closest('.image-upload-wrapper'); const preview = wrapper.querySelector('.image-preview-container'); const uploader = wrapper.querySelector('.image-upload-area'); const urlInput = wrapper.querySelector('.image-url-input'); preview.classList.add('hidden'); uploader.classList.remove('hidden'); urlInput.value = ''; }); }); }
        function handleFileUpload(file, wrapper) { const uploader = wrapper.querySelector('.image-upload-area'); const preview = wrapper.querySelector('.image-preview-container'); const previewImg = preview.querySelector('img'); const urlInput = wrapper.querySelector('.image-url-input'); const formData = new FormData(); formData.append('image', file); uploader.innerHTML = 'Mengunggah...'; fetch('ajax_handler.php?action=upload_image', { method: 'POST', body: formData }).then(response => response.json()).then(data => { if (data.success) { previewImg.src = `../${data.url}`; urlInput.value = data.url; preview.classList.remove('hidden'); uploader.classList.add('hidden'); } else { throw new Error(data.message); } }).catch(error => { alert('Gagal mengunggah gambar: ' + error.message); }).finally(() => { uploader.innerHTML = 'Drop, paste, atau klik'; }); }
        function handleFormSubmit(event) { event.preventDefault(); const form = event.target; const formData = new FormData(form); const submitButton = document.querySelector('button[form="edit-quiz-form"]'); const originalButtonText = submitButton.textContent; submitButton.disabled = true; submitButton.textContent = 'Menyimpan...'; fetch('ajax_handler.php?action=save_quiz_details', { method: 'POST', body: formData }).then(response => response.json()).then(data => { if(data.success) { alert(data.message); } else { throw new Error(data.message); } }).catch(error => { alert('Terjadi error: ' + error.message); }).finally(() => { submitButton.disabled = false; submitButton.textContent = originalButtonText; }); }
    });
    </script>
</body>
</html>
