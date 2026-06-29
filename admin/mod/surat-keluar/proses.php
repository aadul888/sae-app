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

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

  case 'datatable':
    header('Content-Type: application/json');
    $data = [];
    $q = $connection->query("SELECT sk.*, si.indeks FROM surat_keluar sk LEFT JOIN surat_index si ON sk.indeks_id=si.id ORDER BY sk.id DESC LIMIT 50");
    if ($q) {
      $no = 1;
      while ($sk = $q->fetch_assoc()) {
        $badge = $sk['status'] === 'Terkirim' ? 'success' : ($sk['status'] === 'Draf' ? 'warning' : 'secondary');
        $can_edit = (isset($data_role['modifikasi']) && $data_role['modifikasi'] == 'Y');
        $del = (isset($data_role['hapus']) && $data_role['hapus'] == 'Y') ? '<button class="table-action table-action-danger btn-delete-keluar" data-id="' . $sk['id'] . '" title="Hapus"><i class="fas fa-trash"></i></button>' : '';
        $editBtn = $can_edit ? '<button class="table-action table-action-primary btn-edit-surat" data-id="' . $sk['id'] . '" title="Edit"><i class="fas fa-edit"></i></button>' : '';
        $kirimBtn = ($can_edit && $sk['status'] === 'Draf') ? '<button class="table-action table-action-info btn-kirim-surat" data-id="' . $sk['id'] . '" title="Tandai Terkirim"><i class="fas fa-check"></i></button>' : '';
        $data[] = [
          '<td class="text-center">' . $no++ . '</td>',
          '<td><code>' . htmlspecialchars($sk['no_surat']) . '</code></td>',
          '<td>' . htmlspecialchars($sk['indeks'] ?? '-') . '</td>',
          '<td><strong>' . htmlspecialchars(mb_substr($sk['perihal'], 0, 50)) . '</strong></td>',
          '<td>' . htmlspecialchars(mb_substr($sk['tujuan'], 0, 30)) . '</td>',
          '<td><small>' . date('d/m/Y', strtotime($sk['tgl_surat'] ?? $sk['created_at'])) . '</small></td>',
          '<td><span class="badge badge-' . $badge . '">' . htmlspecialchars($sk['status']) . '</span></td>',
          '<td class="text-center">' . $editBtn . ' ' . $kirimBtn . ' ' . $del . '</td>'
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

  case 'load_surat':
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['status'=>'error','message'=>'ID tidak valid']); exit; }
    $q = $connection->query("SELECT sk.*, si.indeks, si.perihal AS ix_perihal FROM surat_keluar sk LEFT JOIN surat_index si ON sk.indeks_id=si.id WHERE sk.id=$id LIMIT 1");
    if (!$q || !($sk = $q->fetch_assoc())) { echo json_encode(['status'=>'error','message'=>'Surat tidak ditemukan']); exit; }
    echo json_encode(['status'=>'success','data'=>$sk]);
    exit;
    break;

  case 'update_surat':
    sk_check('modifikasi');
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['status'=>'error','message'=>'ID tidak valid']); exit; }
    $indeks_id = (int)($_POST['indeks_id'] ?? 0);
    $no_surat = $connection->real_escape_string($_POST['no_surat'] ?? '');
    $perihal = $connection->real_escape_string($_POST['perihal'] ?? '');
    $tujuan = $connection->real_escape_string($_POST['tujuan'] ?? '');
    $tgl_surat = !empty($_POST['tgl_surat']) ? $_POST['tgl_surat'] : date('Y-m-d');
    $lampiran = $connection->real_escape_string($_POST['lampiran'] ?? '');
    $isi_surat = $connection->real_escape_string($_POST['isi_surat'] ?? '');
    $status = $connection->real_escape_string($_POST['status'] ?? 'Draf');
    $u = $connection->prepare("UPDATE surat_keluar SET indeks_id=?, no_surat=?, tgl_surat=?, perihal=?, tujuan=?, lampiran=?, isi_surat=?, status=? WHERE id=?");
    $u->bind_param('isssssssi', $indeks_id, $no_surat, $tgl_surat, $perihal, $tujuan, $lampiran, $isi_surat, $status, $id);
    if (!$u->execute()) {
      echo json_encode(['status'=>'error','message'=>'Gagal: '.$connection->error]);
      $u->close(); exit;
    }
    $u->close();
    echo json_encode(['status'=>'success','message'=>'Surat diperbarui','id'=>$id]);
    exit;
    break;



  case 'kirim':
    sk_check('modifikasi');
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { echo 'ID tidak valid.'; exit; }
    $connection->query("UPDATE surat_keluar SET status='Terkirim' WHERE id=$id");
    echo $connection->affected_rows > 0 ? 'success' : 'Gagal.';
    exit;
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
    if ($indeks_id <= 0 || empty($no_surat) || empty($perihal)) {
      echo json_encode(['status' => 'error', 'message' => 'Lengkapi data.']); exit;
    }
    $s = $connection->prepare("INSERT INTO surat_keluar (indeks_id, no_surat, tgl_surat, perihal, tujuan, lampiran, isi_surat, created_by, status) VALUES (?,?,?,?,?,?,?,'1','Draf')");
    $s->bind_param('issssss', $indeks_id, $no_surat, $tgl_surat, $perihal, $tujuan, $lampiran, $isi_surat);
    if (!$s->execute()) {
      echo json_encode(['status' => 'error', 'message' => 'Gagal: ' . $connection->error]);
      $s->close(); exit;
    }
    $surat_id = $connection->insert_id;
    $s->close();
    echo json_encode(['status' => 'success', 'id' => $surat_id]);
    break;



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
