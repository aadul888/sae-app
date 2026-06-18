<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  require_once '../../../library/function.php';
  require_once '../../login/user.php';

  $gaSql['link'] = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);
  $gaSql['link']->set_charset('utf8');
  $link = $gaSql['link'];

  $mode = ($_POST['mode'] ?? '') === 'survey' ? 'survey' : 'tamu';

  $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
  $start = intval($_POST['start'] ?? 0);
  $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
  $sLimit = ($length != -1) ? "LIMIT $start, $length" : "";
  $search = isset($_POST['search']['value']) ? $link->real_escape_string($_POST['search']['value']) : '';

  if ($mode === 'survey') {
    $where = $search !== '' ? "WHERE (b.nama LIKE '%$search%' OR b.instansi LIKE '%$search%' OR s.komentar LIKE '%$search%')" : '';
    $sql = "SELECT SQL_CALC_FOUND_ROWS s.*, b.nama, b.instansi
            FROM buku_tamu_survey s LEFT JOIN buku_tamu b ON s.guest_table_id=b.id
            $where ORDER BY s.created_at DESC $sLimit";
    $res = $link->query($sql);
    $filtered = $link->query("SELECT FOUND_ROWS()")->fetch_row()[0];
    $total = $link->query("SELECT COUNT(*) FROM buku_tamu_survey")->fetch_row()[0];

    $output = ["draw" => $draw, "recordsTotal" => intval($total), "recordsFiltered" => intval($filtered), "data" => []];
    $no = $start + 1;
    $star = function ($n) {
      $n = intval($n);
      return str_repeat('★', $n) . str_repeat('☆', max(0, 5 - $n));
    };
    while ($r = $res->fetch_assoc()) {
      $output['data'][] = [
        '<div class="text-center">' . $no++ . '</div>',
        '<div class="font-weight-bold">' . htmlspecialchars($r['nama'] ?: '-') . '</div><small class="text-muted">' . htmlspecialchars($r['instansi'] ?: '') . '</small>',
        '<div class="text-center text-warning">' . $star($r['rating']) . '</div>',
        '<div class="text-center">' . intval($r['pelayanan']) . '</div>',
        '<div class="text-center">' . intval($r['kecepatan']) . '</div>',
        '<div class="text-center">' . intval($r['kenyamanan']) . '</div>',
        '<div>' . htmlspecialchars($r['komentar'] ?: '-') . '</div>',
        '<div class="text-center"><small>' . htmlspecialchars($r['created_at']) . '</small></div>',
      ];
    }
    header('Content-Type: application/json');
    echo json_encode($output);
    exit;
  }

  // ---- mode tamu ----
  $conds = [];
  $dari = preg_replace('/[^0-9\-]/', '', $_POST['dari'] ?? '');
  $sampai = preg_replace('/[^0-9\-]/', '', $_POST['sampai'] ?? '');
  $status = in_array($_POST['status'] ?? '', ['Aktif', 'Selesai', 'Batal']) ? $_POST['status'] : '';
  if ($dari !== '') $conds[] = "tanggal_kunjungan >= '" . $link->real_escape_string($dari) . "'";
  if ($sampai !== '') $conds[] = "tanggal_kunjungan <= '" . $link->real_escape_string($sampai) . "'";
  if ($status !== '') $conds[] = "status = '" . $link->real_escape_string($status) . "'";
  if ($search !== '') $conds[] = "(nama LIKE '%$search%' OR instansi LIKE '%$search%' OR keperluan LIKE '%$search%' OR guest_id LIKE '%$search%')";
  $where = count($conds) ? 'WHERE ' . implode(' AND ', $conds) : '';

  $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM buku_tamu $where ORDER BY tanggal_kunjungan DESC, waktu_masuk DESC $sLimit";
  $res = $link->query($sql);
  $filtered = $link->query("SELECT FOUND_ROWS()")->fetch_row()[0];
  $total = $link->query("SELECT COUNT(*) FROM buku_tamu")->fetch_row()[0];

  $output = ["draw" => $draw, "recordsTotal" => intval($total), "recordsFiltered" => intval($filtered), "data" => []];
  $no = $start + 1;
  $base_foto = '../content/tamu/';
  while ($r = $res->fetch_assoc()) {
    $foto = $r['foto']
      ? '<img src="' . $base_foto . rawurlencode($r['foto']) . '" class="avatar avatar-sm rounded-circle" style="object-fit:cover" onerror="this.style.display=\'none\'">'
      : '<span class="avatar avatar-sm rounded-circle bg-secondary text-white"><i class="fas fa-user"></i></span>';

    $badge = $r['status'] === 'Aktif'
      ? '<span class="badge badge-warning">Aktif</span>'
      : ($r['status'] === 'Selesai' ? '<span class="badge badge-success">Selesai</span>' : '<span class="badge badge-danger">Batal</span>');

    $actions = '<div class="text-center">';
    $actions .= '<a href="javascript:void(0)" class="table-action btn-detail" data-id="' . $r['id'] . '" title="Detail"><i class="fas fa-eye"></i></a>';
    $actions .= '<a href="javascript:void(0)" class="table-action table-action-warning btn-edit-tamu" data-id="' . $r['id'] . '" '
      . 'data-nama="' . htmlspecialchars($r['nama'], ENT_QUOTES) . '" '
      . 'data-instansi="' . htmlspecialchars($r['instansi'], ENT_QUOTES) . '" '
      . 'data-telepon="' . htmlspecialchars($r['telepon'] ?? '', ENT_QUOTES) . '" '
      . 'data-keperluan="' . htmlspecialchars($r['keperluan'], ENT_QUOTES) . '" '
      . 'data-keterangan="' . htmlspecialchars($r['keterangan'] ?? '', ENT_QUOTES) . '" '
      . 'data-status="' . $r['status'] . '" title="Edit"><i class="fas fa-edit"></i></a>';
    if ($r['status'] === 'Aktif') {
      $actions .= '<a href="javascript:void(0)" class="table-action table-action-success btn-checkout" data-id="' . $r['id'] . '" title="Check-out"><i class="fas fa-sign-out-alt"></i></a>';
    }
    $actions .= '<a href="javascript:void(0)" class="table-action table-action-delete btn-delete-tamu" data-id="' . $r['id'] . '" title="Hapus"><i class="fas fa-trash"></i></a>';
    $actions .= '</div>';

    $output['data'][] = [
      '<div class="text-center">' . $no++ . '</div>',
      '<div class="text-center">' . $foto . '</div>',
      '<div class="font-weight-bold">' . htmlspecialchars($r['nama']) . '</div><small class="text-muted">' . htmlspecialchars($r['instansi']) . '</small>',
      '<div>' . htmlspecialchars($r['keperluan']) . '</div>',
      '<div class="text-center"><small>' . htmlspecialchars($r['tanggal_kunjungan']) . '</small></div>',
      '<div class="text-center"><small>' . htmlspecialchars(substr((string)$r['waktu_masuk'], 0, 5)) . '</small></div>',
      '<div class="text-center"><small>' . ($r['waktu_keluar'] ? htmlspecialchars(substr((string)$r['waktu_keluar'], 0, 5)) : '-') . '</small></div>',
      '<div class="text-center">' . $badge . '</div>',
      $actions,
    ];
  }
  header('Content-Type: application/json');
  echo json_encode($output);
}
