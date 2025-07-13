<?php
// Memuat autoloader dari Composer untuk library PhpSpreadsheet
require '../vendor/autoload.php';

// Menggunakan kelas-kelas yang diperlukan dari PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// 1. Membuat objek Spreadsheet baru
$spreadsheet = new Spreadsheet();

// --- Mengisi Sheet Pertama ("Sesi 1") ---
$sheet1 = $spreadsheet->getActiveSheet();
$sheet1->setTitle('Sesi 1');

// 2. Menulis Header
$headers = [
    'question_text',
    'option_a_text',
    'option_b_text',
    'option_c_text',
    'option_d_text',
    'correct_answer'
];
$sheet1->fromArray($headers, NULL, 'A1');

// 3. Menulis Data Contoh
$exampleData = [
    ['Apa ibu kota Indonesia?', 'Jakarta', 'Bandung', 'Surabaya', 'Medan', 'A'],
    ['Berapa hasil dari 2 + 2 * 2?', '8', '6', '4', '', 'B'],
    ['Siapa presiden pertama Amerika Serikat?', 'Abraham Lincoln', 'Thomas Jefferson', 'George Washington', 'John Adams', 'C']
];
$sheet1->fromArray($exampleData, NULL, 'A2');

// Membuat kolom lebih lebar agar mudah dibaca
foreach (range('A', 'F') as $columnID) {
    $sheet1->getColumnDimension($columnID)->setAutoSize(true);
}


// --- Mengisi Sheet Kedua ("Sesi 2") ---
$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('Sesi 2');
$sheet2->fromArray($headers, NULL, 'A1');
$sheet2->setCellValue('A2', 'Isi pertanyaan untuk Sesi 2 di sini...');

foreach (range('A', 'F') as $columnID) {
    $sheet2->getColumnDimension($columnID)->setAutoSize(true);
}

// Mengatur sheet aktif kembali ke yang pertama saat file dibuka
$spreadsheet->setActiveSheetIndex(0);


// 4. Mengatur Header HTTP untuk memicu unduhan
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="template_kuis.xlsx"'); // Nama file yang akan diunduh
header('Cache-Control: max-age=0');

// 5. Membuat file Excel dan mengirimkannya ke output browser
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

exit();
?>
