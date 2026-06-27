<?php
require_once '../../../library/config.php';
require_once '../../assets/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// 1. Buat file Excel test
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Indeks');
$sheet->setCellValue('B1', 'Perihal');
$sheet->setCellValue('C1', 'Kategori');
$sheet->setCellValue('D1', 'Jenis Surat');
$sheet->setCellValue('A2', 'TEST.01');
$sheet->setCellValue('B2', 'Test Import');
$sheet->setCellValue('C2', 'Test');
$sheet->setCellValue('D2', 'Surat Keluar');
$file = __DIR__ . '/../../../content/templates/test_import.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($file);
echo "1. File test created: $file\n";
echo "   Size: " . filesize($file) . " bytes\n";

// 2. Load balik
try {
    $loaded = IOFactory::load($file);
    $rows = $loaded->getActiveSheet()->toArray();
    echo "2. Load OK, rows: " . count($rows) . "\n";
    echo "   Header: " . implode(' | ', $rows[0]) . "\n";
    echo "   Row 1: " . implode(' | ', $rows[1]) . "\n";

    // 3. Cek array_search case-insensitive
    $header = array_map('trim', $rows[0] ?? []);
    $h_lower = array_map('strtolower', $header);
    echo "3. Header lower: " . implode(' | ', $h_lower) . "\n";
    echo "   idx indeks: " . (array_search('indeks', $h_lower) ?: 'not found') . "\n";
    echo "   idx perihal: " . (array_search('perihal', $h_lower) ?: 'not found') . "\n";

    // 4. Insert test
    $last_id = (int)$connection->query("SELECT COALESCE(MAX(id),0) FROM surat_index")->fetch_row()[0];
    echo "4. Last ID: $last_id\n";
    $last_id++;
    $indeks_v = 'TEST.' . $last_id;
    $perihal_v = 'Test Import ke-' . $last_id;
    $contoh_v = sprintf('%04d/%s-SMKN1PGL', $last_id, $indeks_v);
    $sql = "INSERT IGNORE INTO surat_index (indeks, perihal, kategori, jenis_surat, contoh_nomor) VALUES ('" . $connection->real_escape_string($indeks_v) . "', '$perihal_v', 'Test', 'Surat Keluar', '$contoh_v')";
    echo "5. SQL: $sql\n";
    if ($connection->query($sql)) {
        echo "6. Insert OK\n";
    } else {
        echo "6. Insert ERR: " . $connection->error . "\n";
    }

    echo "\n=== SEMUA BERHASIL ===\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
