<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
}
require_once '../../../library/config.php';
include('../../../library/function.php');
require_once '../../login/user.php';
require_once '../../assets/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$modul_id = 130;
include __DIR__ . '/../check_role.php';

function sx_check($type) {
  global $data_role;
  if (!isset($data_role[$type]) || $data_role[$type] !== 'Y') { echo 'Akses ditolak.'; exit; }
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

  // ====== GET SINGLE (for edit) ======
  case 'get':
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']); exit; }
    $q = $connection->query("SELECT * FROM surat_index WHERE id=$id LIMIT 1");
    if ($q && $r = $q->fetch_assoc()) {
      echo json_encode(['status' => 'success', 'data' => $r]);
    } else {
      echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan']);
    }
    break;

  // ====== ADD ======
  case 'add':
    sx_check('modifikasi');
    $indeks = $connection->real_escape_string($_POST['indeks'] ?? '');
    $perihal = $connection->real_escape_string($_POST['perihal'] ?? '');
    $kategori = $connection->real_escape_string($_POST['kategori'] ?? '');
    $jenis = $connection->real_escape_string($_POST['jenis_surat'] ?? 'Surat Keluar');
    if (empty($indeks) || empty($perihal)) { echo 'Indeks dan Perihal wajib diisi.'; break; }
    $contoh = sprintf('%04d/%s-SMKN1PGL', (int)$connection->query("SELECT COALESCE(MAX(id),0)+1 AS next FROM surat_index")->fetch_row()[0], $indeks);
    $s = $connection->prepare("INSERT INTO surat_index (indeks, perihal, kategori, jenis_surat, contoh_nomor) VALUES (?,?,?,?,?)");
    $s->bind_param('sssss', $indeks, $perihal, $kategori, $jenis, $contoh);
    echo $s->execute() ? 'success' : 'Gagal: ' . $connection->error;
    $s->close();
    break;

  // ====== UPDATE ======
  case 'update':
    sx_check('modifikasi');
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { echo 'ID tidak valid.'; break; }
    $indeks = $connection->real_escape_string($_POST['indeks'] ?? '');
    $perihal = $connection->real_escape_string($_POST['perihal'] ?? '');
    $kategori = $connection->real_escape_string($_POST['kategori'] ?? '');
    $jenis = $connection->real_escape_string($_POST['jenis_surat'] ?? 'Surat Keluar');
    $u = $connection->prepare("UPDATE surat_index SET indeks=?, perihal=?, kategori=?, jenis_surat=? WHERE id=?");
    $u->bind_param('ssssi', $indeks, $perihal, $kategori, $jenis, $id);
    echo $u->execute() ? 'success' : 'Gagal: ' . $connection->error;
    $u->close();
    break;

  // ====== DELETE ======
  case 'delete':
    sx_check('hapus');
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { echo 'ID tidak valid.'; break; }
    $q = $connection->query("SELECT format_template FROM surat_index WHERE id=$id");
    if ($q && $r = $q->fetch_assoc()) {
      $path = __DIR__ . '/../../../content/templates/' . $r['format_template'];
      if (!empty($r['format_template']) && file_exists($path)) @unlink($path);
    }
    echo $connection->query("DELETE FROM surat_index WHERE id=$id") ? 'success' : 'Gagal: ' . $connection->error;
    break;

  // ====== IMPORT EXCEL ======
  case 'import_excel':
    sx_check('modifikasi');
    if (!isset($_FILES['file_excel']) || $_FILES['file_excel']['error'] !== 0) {
      echo 'File tidak valid. Kode error: ' . ($_FILES['file_excel']['error'] ?? -1);
      exit;
    }
    $file_ext = strtolower(pathinfo($_FILES['file_excel']['name'], PATHINFO_EXTENSION));
    if ($file_ext !== 'xlsx') {
      echo 'Format file tidak sesuai, upload file XLSX!';
      exit;
    }
    $file_tmp = $_FILES['file_excel']['tmp_name'];
    if (!file_exists($file_tmp)) { echo 'File upload tidak ditemukan.'; exit; }
    try {
      $spreadsheet = IOFactory::load($file_tmp);
      $sheet = $spreadsheet->getActiveSheet();
      $rows = $sheet->toArray();
      $header = $rows[0] ?? [];
      $map = ['indeks' => false, 'perihal' => false, 'kategori' => false, 'jenis surat' => false, 'jenis' => false];
      foreach ($header as $col_idx => $col_name) {
        $name = strtolower(trim((string)$col_name));
        if (isset($map[$name])) $map[$name] = $col_idx;
      }
      if ($map['jenis surat'] === false) $map['jenis surat'] = $map['jenis'];
      unset($map['jenis']);
      if ($map['indeks'] === false || $map['perihal'] === false) {
        echo 'Format kolom tidak sesuai. Header file: ' . implode(', ', $header);
        exit;
      }
      $sukses = 0;
      $total_baris = count($rows);
      for ($i = 1; $i < $total_baris; $i++) {
        $indeks_v = trim((string)($rows[$i][$map['indeks']] ?? ''));
        if ($indeks_v === '') continue;
        $perihal_v = $connection->real_escape_string(trim((string)($rows[$i][$map['perihal']] ?? '')));
        $kategori_v = ($map['kategori'] !== false) ? $connection->real_escape_string(trim((string)($rows[$i][$map['kategori']] ?? ''))) : '';
        $jenis_v = ($map['jenis surat'] !== false) ? $connection->real_escape_string(trim((string)($rows[$i][$map['jenis surat']] ?? 'Surat Keluar'))) : 'Surat Keluar';
        if ($jenis_v === '') $jenis_v = 'Surat Keluar';
        $next_id = (int)$connection->query("SELECT COALESCE(MAX(id),0) FROM surat_index")->fetch_row()[0] + 1;
        $contoh_v = sprintf('%04d/%s-SMKN1PGL', $next_id, $indeks_v);
        $sql = "INSERT IGNORE INTO surat_index (indeks, perihal, kategori, jenis_surat, contoh_nomor) VALUES ('" . $connection->real_escape_string($indeks_v) . "', '$perihal_v', '$kategori_v', '$jenis_v', '$contoh_v')";
        if ($connection->query($sql)) {
          $sukses++;
        }
      }
      if ($sukses > 0) {
        echo 'success';
      } else {
        echo 'Tidak ada data baru. Total baris di Excel: ' . ($total_baris - 1);
      }
    } catch (Exception $e) {
      echo 'Error: ' . $e->getMessage() . ' (line ' . $e->getLine() . ')';
    }
    break;

  // ====== DOWNLOAD TEMPLATE EXCEL ======
  case 'download_template_excel':
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'Indeks')->setCellValue('B1', 'Perihal')->setCellValue('C1', 'Kategori')->setCellValue('D1', 'Jenis Surat');
    $sheet->setCellValue('A2', 'KPG.11.01')->setCellValue('B2', 'Surat Keterangan Aktif Sekolah')->setCellValue('C2', 'Kesiswaan')->setCellValue('D2', 'Surat Keluar');
    $sheet->setCellValue('A3', 'SAR.41.01')->setCellValue('B3', 'Permohonan Peminjaman Sarana')->setCellValue('C3', 'Sarpras')->setCellValue('D3', 'Surat Keluar');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="template-import-indeks.xlsx"');
    (new Xlsx($spreadsheet))->save('php://output');
    exit;

  // ====== EXPORT EXCEL (existing data) ======
  case 'export_excel':
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'No')->setCellValue('B1', 'Indeks')->setCellValue('C1', 'Perihal')->setCellValue('D1', 'Kategori')->setCellValue('E1', 'Jenis Surat')->setCellValue('F1', 'Contoh Nomor');
    $no = 2;
    $q = $connection->query("SELECT * FROM surat_index ORDER BY indeks ASC");
    if ($q) while ($r = $q->fetch_assoc()) {
      $sheet->setCellValue('A'.$no, $no-1)->setCellValue('B'.$no, $r['indeks'])->setCellValue('C'.$no, $r['perihal'])->setCellValue('D'.$no, $r['kategori'])->setCellValue('E'.$no, $r['jenis_surat'])->setCellValue('F'.$no, $r['contoh_nomor']);
      $no++;
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="data-indeks-surat.xlsx"');
    (new Xlsx($spreadsheet))->save('php://output');
    exit;

  // ====== UPLOAD TEMPLATE WORD ======
  case 'upload_template':
    sx_check('modifikasi');
    $indeks_id = (int)($_POST['indeks_id'] ?? 0);
    if ($indeks_id <= 0) { echo json_encode(['status' => 'error', 'message' => 'Pilih indeks terlebih dahulu.']); exit; }
    if (!isset($_FILES['file_template']) || $_FILES['file_template']['error'] !== 0) {
      echo json_encode(['status' => 'error', 'message' => 'File tidak valid.']); exit;
    }
    $target_dir = __DIR__ . '/../../../content/templates/';
    if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
    $ext = strtolower(pathinfo($_FILES['file_template']['name'], PATHINFO_EXTENSION));
    if ($ext !== 'docx') { echo json_encode(['status' => 'error', 'message' => 'Hanya file .docx yang diizinkan.']); exit; }
    $filename = 'template_' . $indeks_id . '_' . time() . '.docx';
    $dest = $target_dir . $filename;
    if (!move_uploaded_file($_FILES['file_template']['tmp_name'], $dest)) {
      echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan file.']); exit;
    }
    $connection->query("UPDATE surat_index SET format_template='" . $connection->real_escape_string($filename) . "' WHERE id=$indeks_id");
    echo json_encode(['status' => 'success', 'message' => 'Template berhasil diupload untuk indeks.']);
    break;

  default:
    echo 'Aksi tidak dikenali.';
    break;
}
