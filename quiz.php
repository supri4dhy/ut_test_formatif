<?php
// 1. Sertakan file konfigurasi database
require_once "config.php";

// 2. Validasi ID Kuis dan Jurusan dari URL
if(!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['major_id']) || !is_numeric($_GET['major_id'])){
    die("Error: ID Kuis atau Jurusan tidak valid.");
}
$quiz_id_to_load = (int)$_GET['id'];
$major_id_for_back_button = (int)$_GET['major_id'];

// 3. Ambil detail kuis dan nama mata kuliah
$quizTitle = "Kuis Tidak Ditemukan";
$courseName = "Mata Kuliah";
$course_id_for_back_button = 0; 
$sql_quiz = "SELECT q.title, q.course_id, c.name AS course_name 
             FROM quizzes q 
             JOIN courses c ON q.course_id = c.id 
             WHERE q.id = ?";
if($stmt_quiz = $mysqli->prepare($sql_quiz)){
    $stmt_quiz->bind_param("i", $quiz_id_to_load);
    if($stmt_quiz->execute()){
        $stmt_quiz->bind_result($title, $course_id, $c_name);
        if($stmt_quiz->fetch()){
            $quizTitle = $title;
            $courseName = $c_name;
            $course_id_for_back_button = $course_id;
        } else {
            die("ERROR: Kuis dengan ID yang diberikan tidak ada.");
        }
    }
    $stmt_quiz->close();
}

// 4. Ambil semua pertanyaan dan pilihan jawaban untuk kuis ini
$quizQuestions = [];
$sql_questions = "SELECT id, question_text, question_image FROM questions WHERE quiz_id = ?";
if($stmt_questions = $mysqli->prepare($sql_questions)){
    $stmt_questions->bind_param("i", $quiz_id_to_load);
    if($stmt_questions->execute()){
        $result_questions = $stmt_questions->get_result();
        while($question = $result_questions->fetch_assoc()){
            $current_question_id = $question['id'];
            $options = [];
            $correctAnswer = '';
            $sql_options = "SELECT option_key, option_text, option_image, is_correct FROM options WHERE question_id = ?";
            if($stmt_options = $mysqli->prepare($sql_options)){
                $stmt_options->bind_param("i", $current_question_id);
                if($stmt_options->execute()){
                    $result_options = $stmt_options->get_result();
                    while($option = $result_options->fetch_assoc()){
                        $options[$option['option_key']] = ["text" => $option['option_text'], "image" => $option['option_image']];
                        if($option['is_correct'] == 1){ $correctAnswer = $option['option_key']; }
                    }
                }
                $stmt_options->close();
            }
            $quizQuestions[] = ["question" => $question['question_text'], "questionImage" => $question['question_image'], "options" => $options, "correctAnswer" => $correctAnswer];
        }
    }
    $stmt_questions->close();
}
$mysqli->close();

// 5. Ubah data PHP menjadi format JSON untuk JavaScript
$quizDataJSON = json_encode($quizQuestions);
$headerTitleJSON = json_encode($courseName); 
$quizTitleJSON = json_encode($quizTitle);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($quizTitle); ?> - <?php echo htmlspecialchars($courseName); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; display: flex; flex-direction: column; min-height: 100vh; margin: 0; background-image: linear-gradient(to bottom, #e0e7ff, #f0f4f8 30%, #f0f4f8 70%, #e0e7ff); }
        .header { background-image: linear-gradient(to right, #4A5568, #2D3748); color: white; padding: 1rem; text-align: center; box-shadow: 0 4px 8px rgba(0,0,0,0.15); display: flex; justify-content: space-between; align-items: center; }
        .header-btn { padding: 0.5rem 0.75rem; background-color: rgba(255,255,255,0.1); border-radius: 0.375rem; transition: background-color 0.2s; display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; }
        .header-btn:hover { background-color: rgba(255,255,255,0.2); }
        #quiz-header-title { overflow: hidden; white-space: nowrap; flex: 1; min-width: 0; }
        #quiz-header-title.is-overflowing span { display: inline-block; padding-left: 100%; animation: marquee 15s linear infinite; }
        @keyframes marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-200%); } }
        .content-area { flex-grow: 1; padding: 1.5rem; display: flex; justify-content: center; align-items: center; background-color: #edf2f7; }
        .footer { background-image: linear-gradient(to right, #2D3748, #1A202C); color: #a0aec0; text-align: center; padding: 1rem; font-size: 0.875rem; box-shadow: 0 -2px 6px rgba(0,0,0,0.1); }
        #quiz-container { width: 100%; max-width: 800px; background-color: white; border-radius: 0.75rem; padding: 2rem; box-shadow: 0 8px 16px rgba(0,0,0,0.1); }
        #quiz-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e2e8f0; }
        #quiz-title { font-size: 1.5rem; font-weight: 700; color: #1a202c; }
        #quiz-progress { font-size: 1rem; font-weight: 600; color: #4a5568; }
        #question-image { max-width: 100%; max-height: 400px; object-fit: contain; border-radius: 0.5rem; margin: 0 auto; display: block; }
        
        /* DIUBAH: Ukuran font pertanyaan diperkecil */
        #question-text {
            font-size: 1.125rem; /* text-lg */
            color: #2d3748;
            margin-bottom: 1.5rem; /* Sedikit dikurangi */
            line-height: 1.6;
        }
        
        #answer-options { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
        .option-btn { width: 100%; padding: 1rem; font-size: 1rem; background-color: #f7fafc; border: 2px solid #e2e8f0; border-radius: 0.5rem; cursor: pointer; transition: all 0.2s ease-in-out; display: flex; flex-direction: column; align-items: center; gap: 0.75rem; }
        .option-image { max-width: 100%; height: 120px; object-fit: cover; border-radius: 0.375rem; }
        .option-text { text-align: center; }
        .option-btn:hover:not([disabled]) { border-color: #3b82f6; background-color: #eff6ff; }
        .option-btn.correct { background-color: #dcfce7; border-color: #22c55e; color: #15803d; font-weight: bold; }
        .option-btn.wrong { background-color: #fee2e2; border-color: #ef4444; color: #b91c1c; font-weight: bold; }
        .option-btn:disabled { cursor: not-allowed; opacity: 0.8; }
        #score-container { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; border-radius: 1rem; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); text-align: center; width: 100%; max-width: 500px; }
        #score-circle { position: relative; width: 200px; height: 200px; margin: 1rem auto; }
        #score-text { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 3rem; font-weight: 800; }
        #score-summary { font-size: 1.125rem; opacity: 0.9; }
        .score-buttons { display: flex; gap: 1rem; margin-top: 2rem; flex-direction: column; sm:flex-direction: row; }
        .score-buttons button, .score-buttons a { flex: 1; padding: 0.75rem 1rem; border-radius: 0.5rem; font-weight: 600; transition: all 0.2s; text-align: center; }
        #restart-quiz-btn { background-color: #ffffff; color: #6b46c1; }
        #restart-quiz-btn:hover { background-color: rgba(255,255,255,0.9); transform: translateY(-2px); }
        #back-to-quiz-list-btn { background-color: transparent; border: 2px solid rgba(255,255,255,0.5); color: white; }
        #back-to-quiz-list-btn:hover { background-color: rgba(255,255,255,0.1); }
        @media (max-width: 767px) {
            .content-area { padding: 0.5rem; }
            #quiz-container { padding: 1rem; }
            #question-area { position: -webkit-sticky; position: sticky; top: 0; background-color: white; z-index: 10; margin-left: -1rem; margin-right: -1rem; padding: 1rem 1rem 1rem 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
            /* DIUBAH: Ukuran font pertanyaan mobile diperkecil */
            #question-text { font-size: 1rem; margin-bottom: 1rem; } /* text-base */
            #answer-options { gap: 0.5rem; }
            .option-btn { padding: 0.5rem; gap: 0.5rem; }
            .option-image { height: 100px; }
            .option-text { font-size: 0.875rem; line-height: 1.2; }
        }
    </style>
</head>
<body>
    <header class="header">
        <a href="index.php" title="Halaman Utama" class="header-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span class="hidden sm:inline">Home</span>
        </a>
        <h1 id="quiz-header-title" class="text-xl sm:text-2xl font-bold px-2">
            <span><?php echo htmlspecialchars($courseName); ?></span>
        </h1>
        <a href="quizzes_list.php?major_id=<?php echo $major_id_for_back_button; ?>&course_id=<?php echo $course_id_for_back_button; ?>" title="Kembali ke Daftar Kuis" class="header-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 8 8 12 12 16"></polyline><line x1="16" y1="12" x2="8" y2="12"></line></svg>
            <span class="hidden sm:inline">Kembali</span>
        </a>
    </header>

    <main class="content-area">
        <div id="quiz-container">
            <div id="quiz-header">
                <h2 id="quiz-title" class="truncate"><?php echo htmlspecialchars($quizTitle); ?></h2>
                <p id="quiz-progress">Soal 0/0</p>
            </div>
            
            <div id="question-area">
                <div class="md:flex md:gap-6 md:items-start mb-8">
                    <div class="flex-grow">
                        <p id="question-text" class="leading-relaxed text-slate-800"></p>
                    </div>
                    <div id="question-image-container" class="w-full md:w-2/5 lg:w-1/3 mt-4 md:mt-0 flex-shrink-0 hidden">
                        <img id="question-image" src="" alt="Gambar Pertanyaan" class="w-full h-auto max-h-80 object-contain rounded-lg bg-slate-100 p-2 border">
                    </div>
                </div>
                <div id="answer-options"></div>
            </div>
        </div>

        <div id="score-container" class="hidden">
            <h2 id="score-title" class="text-3xl font-bold mb-2">Kuis Selesai!</h2>
            <div id="score-circle">
                <canvas id="score-canvas" width="200" height="200"></canvas>
                <div id="score-text">0%</div>
            </div>
            <p id="score-summary" class="text-lg mt-2">Anda menjawab 0 dari 0 soal dengan benar.</p>
            <div class="score-buttons">
                <button id="restart-quiz-btn">Ulangi Kuis</button>
                <a id="back-to-quiz-list-btn" href="quizzes_list.php?course_id=<?php echo $course_id_for_back_button; ?>">Kembali ke Daftar Kuis</a>
            </div>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> Kuis Interaktif Anda.</p>
    </footer>

    <script>
        const headerTitle = <?php echo $headerTitleJSON; ?>;
        const quizTitle = <?php echo $quizTitleJSON; ?>;
        const quizQuestions = <?php echo $quizDataJSON; ?>;
        const courseIdForBackButton = <?php echo $course_id_for_back_button; ?>;
        const majorIdForBackButton = <?php echo $major_id_for_back_button; ?>;
        
        const quizContainer = document.getElementById('quiz-container');
        const scoreContainer = document.getElementById('score-container');
        const restartQuizBtn = document.getElementById('restart-quiz-btn');
        const backToQuizListBtn = document.getElementById('back-to-quiz-list-btn');
        
        const quizTitleEl = document.getElementById('quiz-title');
        const quizProgressEl = document.getElementById('quiz-progress');
        const questionImageContainer = document.getElementById('question-image-container');
        const questionImageEl = document.getElementById('question-image');
        const questionTextEl = document.getElementById('question-text');
        const answerOptionsEl = document.getElementById('answer-options');
        
        let currentQuestionIndex = 0;
        let score = 0;
        let shuffledQuestions = [];
        
        function showScore() {
            quizContainer.classList.add('hidden');
            scoreContainer.classList.remove('hidden');
            const scorePercentage = Math.round((shuffledQuestions.length > 0 ? (score / shuffledQuestions.length) : 0) * 100);
            const scoreTitleEl = document.getElementById('score-title');
            const scoreSummaryEl = document.getElementById('score-summary');
            const scoreTextEl = document.getElementById('score-text');
            const scoreCircle = document.getElementById('score-circle');
            if (scorePercentage >= 80) { scoreTitleEl.textContent = "Luar Biasa!"; scoreCircle.style.color = '#4ade80'; } 
            else if (scorePercentage >= 60) { scoreTitleEl.textContent = "Bagus!"; scoreCircle.style.color = '#60a5fa'; } 
            else { scoreTitleEl.textContent = "Coba Lagi, ya!"; scoreCircle.style.color = '#facc15'; }
            scoreSummaryEl.textContent = `Anda menjawab ${score} dari ${shuffledQuestions.length} soal dengan benar.`;
            let currentScore = 0;
            const scoreAnimation = setInterval(() => { if (currentScore >= scorePercentage) { clearInterval(scoreAnimation); } else { currentScore++; scoreTextEl.textContent = `${currentScore}%`; } }, 15);
            const canvas = document.getElementById('score-canvas');
            const ctx = canvas.getContext('2d');
            const size = 200; const center = size / 2; const radius = size / 2 - 10; const endAngle = (scorePercentage / 100) * 2 * Math.PI;
            ctx.clearRect(0, 0, size, size);
            ctx.beginPath(); ctx.arc(center, center, radius, 0, 2 * Math.PI); ctx.strokeStyle = 'rgba(255, 255, 255, 0.2)'; ctx.lineWidth = 12; ctx.stroke();
            ctx.beginPath(); ctx.arc(center, center, radius, -0.5 * Math.PI, endAngle - 0.5 * Math.PI); ctx.strokeStyle = scoreCircle.style.color; ctx.lineWidth = 12; ctx.lineCap = 'round'; ctx.stroke();
            backToQuizListBtn.href = `quizzes_list.php?major_id=${majorIdForBackButton}&course_id=${courseIdForBackButton}`;
        }
        function shuffleArray(array) { let currentIndex = array.length, randomIndex; while (currentIndex !== 0) { randomIndex = Math.floor(Math.random() * currentIndex); currentIndex--;[array[currentIndex], array[randomIndex]] = [array[randomIndex], array[currentIndex]]; } return array; }
        function startQuiz() { if(quizQuestions.length === 0){ quizContainer.innerHTML = '<p class="text-center text-red-500">Tidak ada pertanyaan untuk kuis ini.</p>'; return; } currentQuestionIndex = 0; score = 0; shuffledQuestions = shuffleArray([...quizQuestions]); quizContainer.classList.remove('hidden'); scoreContainer.classList.add('hidden'); quizTitleEl.textContent = quizTitle; showQuestion(); }
        
        function showQuestion() { 
            answerOptionsEl.innerHTML = ''; 
            const currentQuestion = shuffledQuestions[currentQuestionIndex]; 
            if (currentQuestion.questionImage) { 
                questionImageEl.src = currentQuestion.questionImage; 
                questionImageContainer.classList.remove('hidden'); 
            } else { 
                questionImageContainer.classList.add('hidden'); 
            } 
            if (currentQuestion.question) { 
                questionTextEl.textContent = currentQuestion.question; 
                questionTextEl.classList.remove('hidden'); 
            } else { 
                questionTextEl.classList.add('hidden'); 
            } 
            quizProgressEl.textContent = `Soal ${currentQuestionIndex + 1}/${shuffledQuestions.length}`; 
            const options = currentQuestion.options; 
            let optionKeys = Object.keys(options); 
            shuffleArray(optionKeys); 
            optionKeys.forEach(key => { 
                const optionData = options[key]; 
                const optionButton = document.createElement('button'); 
                optionButton.classList.add('option-btn'); 
                optionButton.dataset.key = key; 
                let buttonContent = ''; 
                if (optionData.image) { 
                    buttonContent += `<img src="${optionData.image}" alt="Pilihan ${key}" class="option-image">`;
                } 
                if (optionData.text) { 
                    buttonContent += `<span class="option-text">${optionData.text}</span>`; 
                } 
                optionButton.innerHTML = buttonContent; 
                optionButton.addEventListener('click', () => selectAnswer(optionButton, key)); 
                answerOptionsEl.appendChild(optionButton); 
            }); 
        }

        function selectAnswer(selectedButton, selectedKey) { 
            const currentQuestion = shuffledQuestions[currentQuestionIndex]; 
            const correctKey = currentQuestion.correctAnswer; 
            const allOptionButtons = answerOptionsEl.querySelectorAll('.option-btn'); 
            
            allOptionButtons.forEach(btn => { 
                btn.disabled = true; 
                if (btn.dataset.key === correctKey) { 
                    btn.classList.add('correct'); 
                } 
            }); 
            
            if (selectedKey === correctKey) { 
                score++; 
            } else { 
                selectedButton.classList.add('wrong'); 
            } 
            
            setTimeout(handleNextQuestion, 1500);
        }
        
        function handleNextQuestion() { 
            currentQuestionIndex++; 
            if (currentQuestionIndex < shuffledQuestions.length) { 
                showQuestion(); 
            } else { 
                showScore(); 
            } 
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const titleContainer = document.getElementById('quiz-header-title');
            const titleSpan = titleContainer.querySelector('span');
            titleSpan.textContent = headerTitle; 
            if (titleSpan.scrollWidth > titleContainer.clientWidth) {
                titleContainer.classList.add('is-overflowing');
            }
            startQuiz();
        });
        restartQuizBtn.addEventListener('click', startQuiz);
    </script>
</body>
</html>
