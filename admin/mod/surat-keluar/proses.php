<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login'); exit;
}
require_once '../../../library/config.php';
include('../../../library/function.php');
require_once '../../login/user.php';
require_once '../../assets/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$modul_id = 54;
include __DIR__ . '/../check_role.php';

function sk_check($type) {
  global $data_role;
  if (!isset($data_role[$type]) || $data_role[$type] !== 'Y') { echo 'Akses ditolak.'; exit; }
}

$sekolah = []; $kepsek = []; $alamat_sekolah = '';
$rs = $connection->query("SELECT site_name, site_alamat, site_kelurahan, site_kecamatan, site_kota FROM setting LIMIT 1");
if ($rs) { $sekolah = $rs->fetch_assoc(); $alamat_sekolah = trim(($sekolah['site_alamat']??'') . ', ' . ($sekolah['site_kelurahan']??'') . ', ' . ($sekolah['site_kecamatan']??'') . ', ' . ($sekolah['site_kota']??'')); }
$rk = $connection->query("SELECT a.fullname, a.gelar_depan, a.gelar_belakang, a.nip FROM admin a JOIN level l ON a.level_id=l.level_id WHERE l.level_nama='Kepala Sekolah' LIMIT 1");
if ($rk && $rk->num_rows > 0) $kepsek = $rk->fetch_assoc();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

  case 'datatable':
    header('Content-Type: application/json');
    $data = [];
    $q = $connection->query("SELECT sk.*, si.indeks FROM surat_keluar sk LEFT JOIN surat_index si ON sk.indeks_id=si.id ORDER BY sk.id DESC LIMIT 50");
    if ($q) {
      $no = 1;
      while ($sk = $q->fetch_assoc()) {
        $badge = $sk['status'] === 'Terkirim' ? 'success' : 'secondary';
        $del = (isset($data_role['hapus']) && $data_role['hapus'] == 'Y') ? '<button class="table-action table-action-danger btn-delete-keluar" data-id="' . $sk['id'] . '" title="Hapus"><i class="fas fa-trash"></i></button>' : '';
        $data[] = [
          '<td class="text-center">' . $no++ . '</td>',
          '<td><code>' . htmlspecialchars($sk['no_surat']) . '</code></td>',
          '<td>' . htmlspecialchars($sk['indeks'] ?? '-') . '</td>',
          '<td><strong>' . htmlspecialchars(mb_substr($sk['perihal'], 0, 50)) . '</strong></td>',
          '<td>' . htmlspecialchars(mb_substr($sk['tujuan'], 0, 30)) . '</td>',
          '<td><small>' . date('d/m/Y', strtotime($sk['tgl_surat'] ?? $sk['created_at'])) . '</small></td>',
          '<td><span class="badge badge-' . $badge . '">' . htmlspecialchars($sk['status']) . '</span></td>',
          '<td class="text-center"><button class="table-action table-action-warning btn-cetak-surat" data-id="' . $sk['id'] . '" title="Cetak"><i class="fas fa-print"></i></button> <a href="./mod/surat-keluar/proses.php?action=download&id=' . $sk['id'] . '" class="table-action table-action-success" title="Download"><i class="fas fa-file-word"></i></a> ' . $del . '</td>'
        ];
      }
    }
    echo json_encode(['data' => $data]);
    exit;
    break;

  case 'gen_nomor':
    $indeks = preg_replace('/[^a-zA-Z0-9._-]/', '', $_GET['indeks'] ?? '');
    if (empty($indeks)) { echo '-'; exit; }
    $next = 1;
    $q = $connection->query("SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(no_surat,'/',1) AS UNSIGNED)),0)+1 AS n FROM surat_keluar");
    if ($q) $next = (int)$q->fetch_row()[0];
    echo sprintf('%04d/%s-SMKN1PGL', $next, strtoupper($indeks));
    break;

  case 'load_template':
    $file = preg_replace('/[^a-zA-Z0-9_.-]/', '', $_GET['file'] ?? '');
    $path = __DIR__ . '/../../../content/templates/' . $file;
    if (empty($file) || !file_exists($path)) { echo ''; exit; }
    try {
      $phpword = \PhpOffice\PhpWord\IOFactory::load($path);
      $html = '';
      foreach ($phpword->getSections() as $section) {
        foreach ($section->getElements() as $elem) {
          if (method_exists($elem, 'getText')) {
            $html .= '<p>' . htmlspecialchars($elem->getText()) . '</p>';
          } elseif ($elem instanceof \PhpOffice\PhpWord\Element\TextRun) {
            $line = '';
            foreach ($elem->getElements() as $te) {
              if (method_exists($te, 'getText')) $line .= htmlspecialchars($te->getText());
            }
            if (trim($line)) $html .= '<p>' . $line . '</p>';
          }
        }
      }
      echo $html;
    } catch (Exception $e) { echo ''; }
    break;

  case 'buat':
    sk_check('modifikasi');
    $indeks_id = (int)($_POST['indeks_id'] ?? 0);
    $no_surat = $connection->real_escape_string($_POST['no_surat'] ?? '');
    $perihal = $connection->real_escape_string($_POST['perihal'] ?? '');
    $tujuan = $connection->real_escape_string($_POST['tujuan'] ?? '');
    $tgl_surat = !empty($_POST['tgl_surat']) ? $_POST['tgl_surat'] : date('Y-m-d');
    $lampiran = $connection->real_escape_string($_POST['lampiran'] ?? '');
    $isi_surat = $connection->real_escape_string($_POST['isi_surat'] ?? '');
    $file_template = null;
    if ($indeks_id <= 0 || empty($no_surat) || empty($perihal)) {
      echo json_encode(['status' => 'error', 'message' => 'Lengkapi data.']); exit;
    }
    $q = $connection->query("SELECT format_template FROM surat_index WHERE id=$indeks_id");
    if ($q && $r = $q->fetch_assoc()) $file_template = $r['format_template'];
    $s = $connection->prepare("INSERT INTO surat_keluar (indeks_id, no_surat, tgl_surat, perihal, tujuan, lampiran, isi_surat, template_path, created_by, status) VALUES (?,?,?,?,?,?,?,?,'1','Draf')");
    $s->bind_param('isssssss', $indeks_id, $no_surat, $tgl_surat, $perihal, $tujuan, $lampiran, $isi_surat, $file_template);
    if (!$s->execute()) {
      echo json_encode(['status' => 'error', 'message' => 'Gagal: ' . $connection->error]);
      $s->close(); exit;
    }
    $surat_id = $connection->insert_id;
    $s->close();
    echo json_encode(['status' => 'success', 'download_url' => './mod/surat-keluar/proses.php?action=download&id=' . $surat_id]);
    break;

  case 'download':
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) exit;
    $q = $connection->query("SELECT sk.*, si.indeks, si.format_template FROM surat_keluar sk LEFT JOIN surat_index si ON sk.indeks_id=si.id WHERE sk.id=$id LIMIT 1");
    if (!$q || !($sk = $q->fetch_assoc())) { http_response_code(404); exit; }
    $templatePath = null;
    if (!empty($sk['format_template'])) {
      $tp = __DIR__ . '/../../../content/templates/' . $sk['format_template'];
      if (file_exists($tp)) $templatePath = $tp;
    }
    try {
      $phpword = $templatePath ? \PhpOffice\PhpWord\IOFactory::load($templatePath) : new \PhpOffice\PhpWord\PhpWord();
      $vars = [
        'no_surat' => $sk['no_surat'], 'perihal' => $sk['perihal'], 'tujuan' => $sk['tujuan'],
        'tgl_surat' => date('d F Y', strtotime($sk['tgl_surat'] ?? $sk['created_at'])),
        'tanggal' => date('d F Y'), 'bulan' => date('F'), 'tahun' => date('Y'),
        'nama_sekolah' => $sekolah['site_name'] ?? 'SMK Negeri 1 Pagelaran',
        'alamat_sekolah' => $alamat_sekolah,
        'kepala_sekolah' => ($kepsek['gelar_depan']??'') . ' ' . ($kepsek['fullname']??'') . ($kepsek['gelar_belakang'] ? ', ' . $kepsek['gelar_belakang'] : ''),
        'nip_kepsek' => $kepsek['nip'] ?? '',
      ];
      $phpword = sk_replace_placeholders($phpword, $vars);
      header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
      header('Content-Disposition: attachment; filename="Surat_' . $sk['no_surat'] . '.docx"');
      \PhpOffice\PhpWord\IOFactory::createWriter($phpword, 'Word2007')->save('php://output');
    } catch (Exception $e) { echo 'Error: ' . $e->getMessage(); }
    exit;

  case 'delete':
    sk_check('hapus');
    $id = (int)($_POST['id'] ?? 0);
    echo $id > 0 && $connection->query("DELETE FROM surat_keluar WHERE id=$id") ? 'success' : 'Gagal.';
    break;

  case 'export_excel':
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1','No')->setCellValue('B1','No. Surat')->setCellValue('C1','Indeks')->setCellValue('D1','Perihal')->setCellValue('E1','Tujuan')->setCellValue('F1','Tanggal')->setCellValue('G1','Status');
    $no = 2;
    $q = $connection->query("SELECT sk.*, si.indeks FROM surat_keluar sk LEFT JOIN surat_index si ON sk.indeks_id=si.id ORDER BY sk.id DESC");
    if ($q) while ($r = $q->fetch_assoc()) {
      $sheet->setCellValue('A'.$no, $no-1)->setCellValue('B'.$no, $r['no_surat'])->setCellValue('C'.$no, $r['indeks']??'')->setCellValue('D'.$no, $r['perihal'])->setCellValue('E'.$no, $r['tujuan'])->setCellValue('F'.$no, $r['tgl_surat']??$r['created_at'])->setCellValue('G'.$no, $r['status']);
      $no++;
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="surat-keluar.xlsx"');
    (new Xlsx($spreadsheet))->save('php://output');
    exit;

  default:
    echo 'Aksi tidak dikenali.';
    break;
}

function sk_replace_placeholders($phpword, $vars) {
  foreach ($phpword->getSections() as $section) {
    foreach ($section->getElements() as $element) {
      if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
        foreach ($element->getElements() as $textElement) {
          if ($textElement instanceof \PhpOffice\PhpWord\Element\Text) {
            $text = $textElement->getText();
            foreach ($vars as $key => $val) $text = str_replace('{{' . $key . '}}', $val, $text);
            $textElement->setText($text);
          }
        }
      }
    }
  }
  return $phpword;
}
