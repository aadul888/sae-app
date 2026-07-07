<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login'); exit;
}
require_once '../../../library/config.php';
include('../../../library/function.php');
require_once '../../login/user.php';

$modul_id = 54;
include __DIR__ . '/../check_role.php';

if (!isset($data_role['lihat']) || $data_role['lihat'] !== 'Y') {
  header('Content-Type: application/json');
  echo json_encode([
    "draw" => intval($_REQUEST['draw'] ?? 0),
    "recordsTotal" => 0,
    "recordsFiltered" => 0,
    "data" => [],
  ]);
  exit;
}

$can_edit = (isset($data_role['modifikasi']) && $data_role['modifikasi'] == 'Y');
$can_del  = (isset($data_role['hapus']) && $data_role['hapus'] == 'Y');

// DataTables server-side parameters
$draw = intval($_REQUEST['draw'] ?? 0);
$start = intval($_REQUEST['start'] ?? 0);
$length = intval($_REQUEST['length'] ?? 25);
$search = $_REQUEST['search']['value'] ?? '';

// Kolom yang bisa di-search
$aColumns = ['sk.no_surat', 'si.indeks', 'sk.perihal', 'sk.tgl_surat', 'sk.status'];
$sIndexColumn = "sk.id";
$sTable = "surat_keluar sk LEFT JOIN surat_index si ON sk.indeks_id=si.id";

// Build search condition
$sWhere = "WHERE 1=1";
if (!empty($search)) {
  $searchEsc = $connection->real_escape_string($search);
  $sWhere .= " AND (sk.no_surat LIKE '%$searchEsc%' OR si.indeks LIKE '%$searchEsc%' OR sk.perihal LIKE '%$searchEsc%')";
}

// Ordering
$sOrder = "ORDER BY sk.id DESC";
if (isset($_REQUEST['order'][0]['column'])) {
  $colIdx = intval($_REQUEST['order'][0]['column']);
  $dir = strtolower($_REQUEST['order'][0]['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
  if (isset($aColumns[$colIdx])) {
    $sOrder = "ORDER BY " . $aColumns[$colIdx] . " " . $dir;
  }
}

// Total records
$totalQ = $connection->query("SELECT COUNT(*) AS cnt FROM $sTable $sWhere");
$totalFiltered = $totalQ ? (int)$totalQ->fetch_assoc()['cnt'] : 0;

// Total all (unfiltered)
$totalAllQ = $connection->query("SELECT COUNT(*) AS cnt FROM surat_keluar");
$totalAll = $totalAllQ ? (int)$totalAllQ->fetch_row()[0] : 0;

// Cari template yang punya indeks_id untuk tombol generate
$templateMap = [];
$tq = $connection->query("SELECT id, indeks_id FROM surat_template WHERE indeks_id IS NOT NULL");
if ($tq) while ($tr = $tq->fetch_assoc()) {
  $templateMap[(int)$tr['indeks_id']] = (int)$tr['id'];
}

// Fetch data
$limit = $length > 0 ? "LIMIT $start, $length" : "";
$q = $connection->query("SELECT sk.*, si.indeks FROM $sTable $sWhere $sOrder $limit");

$data = [];
if ($q) {
  $no = $start + 1;
  while ($sk = $q->fetch_assoc()) {
    $badge = $sk['status'] === 'Terkirim' ? 'success' : ($sk['status'] === 'Draf' ? 'warning' : 'secondary');
    
    // Tombol Hapus
    $del = $can_del ? '<button class="table-action table-action-danger btn-delete-keluar" data-id="' . $sk['id'] . '" title="Hapus"><i class="fas fa-trash"></i></button>' : '';
    
    // Tombol Edit
    $editBtn = $can_edit ? '<button class="table-action table-action-primary btn-edit-surat" data-id="' . $sk['id'] . '" title="Edit"><i class="fas fa-edit"></i></button>' : '';
    
    // Tombol Tandai Terkirim (hanya Draf)
    $kirimBtn = ($can_edit && $sk['status'] === 'Draf') ? '<button class="table-action table-action-info btn-kirim-surat" data-id="' . $sk['id'] . '" title="Tandai Terkirim"><i class="fas fa-check"></i></button>' : '';
    
    // Tombol Generate (hanya jika indeks memiliki template)
    $genBtn = '';
    $indeksId = (int)$sk['indeks_id'];
    if ($can_edit && isset($templateMap[$indeksId])) {
      $tmplId = $templateMap[$indeksId];
      $genBtn = '<button class="table-action table-action-warning btn-generate-surat" data-id="' . $sk['id'] . '" data-indeks-id="' . $indeksId . '" data-template-id="' . $tmplId . '" data-no-surat="' . htmlspecialchars($sk['no_surat']) . '" title="Generate Dokumen"><i class="fas fa-magic"></i></button>';
    }
    
    // Link dokumen — prioritaskan drive_view_link
    $docLink = '-';
    if (!empty($sk['drive_view_link'])) {
      $docLink = '<a href="' . htmlspecialchars($sk['drive_view_link']) . '" target="_blank" class="table-action table-action-success" title="Lihat Dokumen (Google Drive)"><i class="fas fa-file-alt"></i></a>';
    } elseif (!empty($sk['template_path']) && strpos($sk['template_path'], 'drive:') !== 0) {
      $pdfUrl = htmlspecialchars('/' . $sk['template_path']);
      $docLink = '<a href="' . $pdfUrl . '" target="_blank" class="table-action table-action-success" title="Lihat Dokumen"><i class="fas fa-file-alt"></i></a>';
    }

    // Aksi buttons
    $aksiHtml = $genBtn . ' ' . $editBtn . ' ' . $kirimBtn . ' ' . $del;

    $data[] = [
      '<td class="text-center">' . $no++ . '</td>',
      '<td><code>' . htmlspecialchars($sk['no_surat']) . '</code></td>',
      '<td>' . htmlspecialchars($sk['indeks'] ?? '-') . '</td>',
      '<td><strong>' . htmlspecialchars(mb_substr($sk['perihal'], 0, 50)) . '</strong></td>',
      '<td><small>' . date('d/m/Y', strtotime($sk['tgl_surat'] ?? $sk['created_at'])) . '</small></td>',
      '<td><span class="badge badge-' . $badge . '">' . htmlspecialchars($sk['status']) . '</span></td>',
      '<td class="text-center">' . $docLink . '</td>',
      '<td class="text-center">' . $aksiHtml . '</td>'
    ];
  }
}

header('Content-Type: application/json');
echo json_encode([
  "draw" => $draw,
  "recordsTotal" => $totalAll,
  "recordsFiltered" => $totalFiltered,
  "data" => $data,
]);
exit;
