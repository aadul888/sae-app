<?php
/**
 * Simulasi endpoint import — untuk debug.
 * Akses via browser langsung: admin/?mod=surat-index&test=1
 */
require_once '../../../library/config.php';
include('../../../library/function.php');
require_once '../../assets/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

echo "<h2>Test Import Excel</h2>";

// 1. Buat file Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Indeks');
$sheet->setCellValue('B1', 'Perihal');
$sheet->setCellValue('C1', 'Kategori');
$sheet->setCellValue('D1', 'Jenis Surat');
$sheet->setCellValue('A2', 'DEBUG.01');
$sheet->setCellValue('B2', 'Debug Import Test');
$sheet->setCellValue('C2', 'Debug');
$sheet->setCellValue('D2', 'Surat Keluar');

$file = __DIR__ . '/../../../content/templates/debug_import.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($file);
echo "1. File created: " . filesize($file) . " bytes<br>";

// 2. Load balik
$loaded = IOFactory::load($file);
$rows = $loaded->getActiveSheet()->toArray();
echo "2. Rows: " . count($rows) . "<br>";
echo "3. Header: " . implode(' | ', $rows[0]) . "<br>";

// 3. Cek mapping
$header = $rows[0] ?? [];
$map = ['indeks' => false, 'perihal' => false, 'kategori' => false, 'jenis surat' => false, 'jenis' => false];
foreach ($header as $col_idx => $col_name) {
    $name = strtolower(trim((string)$col_name));
    echo "   Kolom $col_idx: '$name'<br>";
    if (isset($map[$name])) $map[$name] = $col_idx;
}
echo "4. Map: indeks=" . var_export($map['indeks'], true) . ", perihal=" . var_export($map['perihal'], true) . "<br>";

if ($map['indeks'] === false || $map['perihal'] === false) {
    echo "5. ERROR: Format kolom tidak sesuai<br>";
    exit;
}

// 4. Insert
$sukses = 0;
for ($i = 1; $i < count($rows); $i++) {
    $indeks_v = trim((string)($rows[$i][$map['indeks']] ?? ''));
    if ($indeks_v === '') continue;
    $perihal_v = $connection->real_escape_string(trim((string)($rows[$i][$map['perihal']] ?? '')));
    $kategori_v = ($map['kategori'] !== false) ? $connection->real_escape_string(trim((string)($rows[$i][$map['kategori']] ?? ''))) : '';
    $jenis_v = ($map['jenis surat'] !== false) ? $connection->real_escape_string(trim((string)($rows[$i][$map['jenis surat']] ?? 'Surat Keluar'))) : 'Surat Keluar';
    if ($jenis_v === '') $jenis_v = 'Surat Keluar';
    $next_id = (int)$connection->query("SELECT COALESCE(MAX(id),0) FROM surat_index")->fetch_row()[0] + 1;
    $contoh_v = sprintf('%04d/%s-SMKN1PGL', $next_id, $indeks_v);
    $sql = "INSERT IGNORE INTO surat_index (indeks, perihal, kategori, jenis_surat, contoh_nomor) VALUES ('" . $connection->real_escape_string($indeks_v) . "', '$perihal_v', '$kategori_v', '$jenis_v', '$contoh_v')";
    echo "5. SQL[$i]: " . substr($sql, 0, 200) . "<br>";
    if ($connection->query($sql)) {
        $sukses++;
        echo "   OK<br>";
    } else {
        echo "   ERROR: " . $connection->error . "<br>";
    }
}
echo "6. Sukses: $sukses<br>";
echo "<hr><h3>SELESAI ✅</h3>";
