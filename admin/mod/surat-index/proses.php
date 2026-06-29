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
    echo $connection->query("DELETE FROM surat_index WHERE id=$id") ? 'success' : 'Gagal: ' . $connection->error;
    break;

  // ====== IMPORT EXCEL ======
  case 'import_excel':
    header('Content-Type: application/json');
    sx_check('modifikasi');
    if (empty($_FILES['file_excel']['name'])) {
      echo json_encode(['ok'=>false,'msg'=>'Pilih file Excel terlebih dahulu.']); exit;
    }
    if (strtolower(pathinfo($_FILES['file_excel']['name'], PATHINFO_EXTENSION)) !== 'xlsx') {
      echo json_encode(['ok'=>false,'msg'=>'Format harus XLSX!']); exit;
    }
    try {
      $spreadsheet = IOFactory::load($_FILES['file_excel']['tmp_name']);
      $rows = $spreadsheet->getActiveSheet()->toArray();
      if (count($rows) < 2) {
        echo json_encode(['ok'=>false,'msg'=>'File kosong atau hanya header.']); exit;
      }
      $hl = array_map('strtolower', array_map('trim', $rows[0]));
      $ci = array_search('indeks', $hl);
      $cp = array_search('perihal', $hl);
      $ck = array_search('kategori', $hl);
      $cj = array_search('jenis surat', $hl);
      if ($cj === false) $cj = array_search('jenis', $hl);
      if ($ci === false || $cp === false) {
        echo json_encode(['ok'=>false,'msg'=>'Header wajib: Indeks, Perihal. Ditemukan: '.implode(', ',$rows[0])]); exit;
      }
      $ok = 0;
      for ($i = 1; $i < count($rows); $i++) {
        $iv = trim($rows[$i][$ci] ?? '');
        if ($iv === '') continue;
        $pv = $connection->real_escape_string(trim($rows[$i][$cp] ?? ''));
        $kv = $ck !== false ? $connection->real_escape_string(trim($rows[$i][$ck] ?? '')) : '';
        $jv = $cj !== false ? $connection->real_escape_string(trim($rows[$i][$cj] ?? '')) : 'Surat Keluar';
        if ($jv === '') $jv = 'Surat Keluar';
        $nx = (int)$connection->query("SELECT COALESCE(MAX(id),0)+1 AS n FROM surat_index")->fetch_row()[0];
        $ct = sprintf('%04d/%s-SMKN1PGL', $nx, $iv);
        $sql = "INSERT INTO surat_index (indeks,perihal,kategori,jenis_surat,contoh_nomor) VALUES ('".$connection->real_escape_string($iv)."','$pv','$kv','$jv','$ct')";
        if ($connection->query($sql)) $ok++;
      }
      $msg = "$ok data berhasil diimport.";
      echo json_encode(['ok'=>$ok>0, 'msg'=>$msg]);
    } catch (Exception $e) {
      echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
    }
    exit;
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

  // ====== EXPORT EXCEL (existing data) ======
    echo 'Aksi tidak dikenali.';
    break;
}
