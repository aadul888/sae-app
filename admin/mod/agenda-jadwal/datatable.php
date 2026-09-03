<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  require_once '../../../library/function.php';
  require_once '../../login/user.php';

  $type = $_GET['type'] ?? $_POST['type'] ?? 'jadwal';
  $gaSql['link'] = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);

  $sLimit = "";
  if (isset($_POST['start']) && $_POST['length'] != -1) {
    $sLimit = "LIMIT " . intval($_POST['start']) . ", " . intval($_POST['length']);
  }

  $sOrder = "";
  $sWhere = "";

  switch ($type) {

    case 'jadwal':
      $aColumns = ['j.jadwal_id', 'k.nama_kelas', 'j.hari', 'j.jam_ke', 'm.nama_mapel', 'a.fullname'];
      if (isset($_POST['order'])) {
        $col_idx = intval($_POST['order'][0]['column']);
        $col_dir = ($_POST['order'][0]['dir'] === 'asc') ? 'ASC' : 'DESC';
        if (isset($aColumns[$col_idx])) $sOrder = "ORDER BY " . $aColumns[$col_idx] . " " . $col_dir;
      }
      if (empty($sOrder)) $sOrder = "ORDER BY k.nama_kelas ASC, FIELD(j.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), j.jam_ke ASC";

      $filters = [];
      if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
        $search = $gaSql['link']->real_escape_string($_POST['search']['value']);
        $filters[] = "(k.nama_kelas LIKE '%$search%' OR m.nama_mapel LIKE '%$search%' OR a.fullname LIKE '%$search%')";
      }
      if (!empty($_POST['kelas_id'])) {
        $filters[] = "j.kelas_id='" . intval($_POST['kelas_id']) . "'";
      }
      if (!empty($filters)) $sWhere = "WHERE " . implode(' AND ', $filters);

      $sQuery = "SELECT SQL_CALC_FOUND_ROWS j.jadwal_id, k.nama_kelas, j.hari, j.jam_ke, m.nama_mapel, a.fullname, a.gelar_depan, a.gelar_belakang
        FROM agenda_jadwal j
        LEFT JOIN kelas k ON j.kelas_id = k.kelas_id
        LEFT JOIN agenda_mapel m ON j.mapel_id = m.mapel_id
        LEFT JOIN admin a ON m.guru_id = a.admin_id
        $sWhere $sOrder $sLimit";

      $rResult = $gaSql['link']->query($sQuery);
      $iFilteredTotal = $gaSql['link']->query("SELECT FOUND_ROWS()")->fetch_row()[0];
      $iTotal = $gaSql['link']->query("SELECT COUNT(jadwal_id) FROM agenda_jadwal")->fetch_row()[0];

      $output = ["draw" => intval($_POST['draw'] ?? 0), "recordsTotal" => $iTotal, "recordsFiltered" => $iFilteredTotal, "data" => []];
      $no = intval($_POST['start'] ?? 0) + 1;
      while ($r = $rResult->fetch_assoc()) {
        $nama_guru = trim(($r['gelar_depan'] ? $r['gelar_depan'] . ' ' : '') . $r['fullname'] . ($r['gelar_belakang'] ? ', ' . $r['gelar_belakang'] : ''));
        $output['data'][] = [
          '<div class="text-center">' . $no++ . '</div>',
          '<div class="text-center font-weight-bold">' . htmlspecialchars($r['nama_kelas'] ?? '-') . '</div>',
          '<div class="text-center">' . htmlspecialchars($r['hari']) . '</div>',
          '<div class="text-center">' . $r['jam_ke'] . '</div>',
          '<div class="text-center">' . htmlspecialchars($r['nama_mapel'] ?? '-') . '</div>',
          '<div class="text-center">' . htmlspecialchars($nama_guru) . '</div>',
        ];
      }
      echo json_encode($output);
      break;

    case 'agenda':
      $aColumns = ['ak.agenda_id', 'k.nama_kelas', 'ak.tanggal', 'ak.jam_ke', 'm.nama_mapel', 'a.fullname', 'ak.kehadiran_guru', 'ak.keterangan_materi'];
      if (isset($_POST['order'])) {
        $col_idx = intval($_POST['order'][0]['column']);
        $col_dir = ($_POST['order'][0]['dir'] === 'asc') ? 'ASC' : 'DESC';
        if (isset($aColumns[$col_idx])) $sOrder = "ORDER BY " . $aColumns[$col_idx] . " " . $col_dir;
      }
      if (empty($sOrder)) $sOrder = "ORDER BY ak.tanggal DESC, ak.jam_ke ASC";

      $filters = ["ak.status != 'dihapus'"];
      if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
        $search = $gaSql['link']->real_escape_string($_POST['search']['value']);
        $filters[] = "(k.nama_kelas LIKE '%$search%' OR m.nama_mapel LIKE '%$search%' OR a.fullname LIKE '%$search%' OR ak.keterangan_materi LIKE '%$search%')";
      }
      if (!empty($_POST['kelas_id'])) $filters[] = "ak.kelas_id='" . intval($_POST['kelas_id']) . "'";
      if (!empty($_POST['tanggal'])) $filters[] = "ak.tanggal='" . $gaSql['link']->real_escape_string($_POST['tanggal']) . "'";
      $sWhere = "WHERE " . implode(' AND ', $filters);

      $sQuery = "SELECT SQL_CALC_FOUND_ROWS ak.*, k.nama_kelas, m.nama_mapel, a.fullname, a.gelar_depan, a.gelar_belakang
        FROM agenda_kelas ak
        LEFT JOIN kelas k ON ak.kelas_id = k.kelas_id
        LEFT JOIN agenda_mapel m ON ak.mapel_id = m.mapel_id
        LEFT JOIN admin a ON ak.guru_id = a.admin_id
        $sWhere $sOrder $sLimit";

      $rResult = $gaSql['link']->query($sQuery);
      $iFilteredTotal = $gaSql['link']->query("SELECT FOUND_ROWS()")->fetch_row()[0];
      $iTotal = $gaSql['link']->query("SELECT COUNT(agenda_id) FROM agenda_kelas WHERE status != 'dihapus'")->fetch_row()[0];

      $output = ["draw" => intval($_POST['draw'] ?? 0), "recordsTotal" => $iTotal, "recordsFiltered" => $iFilteredTotal, "data" => []];
      $no = intval($_POST['start'] ?? 0) + 1;
      while ($r = $rResult->fetch_assoc()) {
        $nama_guru = trim(($r['gelar_depan'] ? $r['gelar_depan'] . ' ' : '') . $r['fullname'] . ($r['gelar_belakang'] ? ', ' . $r['gelar_belakang'] : ''));
        $badge = 'success'; $btxt = 'Hadir';
        if ($r['kehadiran_guru'] === 'Tidak Hadir') { $badge = 'danger'; $btxt = 'Tidak Hadir'; }
        elseif ($r['kehadiran_guru'] === 'Tidak Hadir + Tugas') { $badge = 'warning'; $btxt = 'Tidak Hadir + Tugas'; }
        $foto = $r['foto_bukti'] ? '<a href="../content/agenda/' . htmlspecialchars($r['foto_bukti']) . '" target="_blank"><i class="fas fa-image text-primary"></i></a>' : '-';

        $output['data'][] = [
          '<div class="text-center">' . $no++ . '</div>',
          '<div class="text-center font-weight-bold">' . htmlspecialchars($r['nama_kelas'] ?? '-') . '</div>',
          '<div class="text-center">' . date('d/m/Y', strtotime($r['tanggal'])) . '</div>',
          '<div class="text-center">' . $r['jam_ke'] . '</div>',
          '<div class="text-center">' . htmlspecialchars($r['nama_mapel'] ?? '-') . '</div>',
          '<div class="text-center">' . htmlspecialchars($nama_guru) . '</div>',
          '<div class="text-center"><span class="badge badge-' . $badge . '">' . $btxt . '</span></div>',
          '<div class="text-center"><small>' . htmlspecialchars(mb_strimwidth($r['keterangan_materi'] ?? '-', 0, 60, '...')) . '</small></div>',
          '<div class="text-center">' . $foto . '</div>',
        ];
      }
      echo json_encode($output);
      break;

    case 'edit-request':
      $aColumns = ['r.id', 'k.nama_kelas', 'r.tanggal', 'u.nama_lengkap', 'r.catatan', 'r.status', 'r.created_at'];
      if (isset($_POST['order'])) {
        $col_idx = intval($_POST['order'][0]['column']);
        $col_dir = ($_POST['order'][0]['dir'] === 'asc') ? 'ASC' : 'DESC';
        if (isset($aColumns[$col_idx])) $sOrder = "ORDER BY " . $aColumns[$col_idx] . " " . $col_dir;
      }
      if (empty($sOrder)) $sOrder = "ORDER BY r.id DESC";

      if (isset($_POST['search']['value']) && $_POST['search']['value'] != "") {
        $search = $gaSql['link']->real_escape_string($_POST['search']['value']);
        $sWhere = "WHERE (k.nama_kelas LIKE '%$search%' OR u.nama_lengkap LIKE '%$search%' OR r.catatan LIKE '%$search%')";
      }

      $sQuery = "SELECT SQL_CALC_FOUND_ROWS r.*, k.nama_kelas, u.nama_lengkap, adm.fullname AS responded_by_nama
        FROM agenda_edit_request r
        LEFT JOIN kelas k ON r.kelas_id = k.kelas_id
        LEFT JOIN user u ON r.requested_by = u.user_id
        LEFT JOIN admin adm ON r.responded_by = adm.admin_id
        $sWhere $sOrder $sLimit";

      $rResult = $gaSql['link']->query($sQuery);
      $iFilteredTotal = $gaSql['link']->query("SELECT FOUND_ROWS()")->fetch_row()[0];
      $iTotal = $gaSql['link']->query("SELECT COUNT(id) FROM agenda_edit_request")->fetch_row()[0];

      $output = ["draw" => intval($_POST['draw'] ?? 0), "recordsTotal" => $iTotal, "recordsFiltered" => $iFilteredTotal, "data" => []];
      $no = intval($_POST['start'] ?? 0) + 1;
      while ($r = $rResult->fetch_assoc()) {
        $badge = 'warning'; $btxt = 'Menunggu';
        if ($r['status'] == 'approved') { $badge = 'success'; $btxt = 'Disetujui'; }
        elseif ($r['status'] == 'rejected') { $badge = 'danger'; $btxt = 'Ditolak'; }

        $actions = '<div class="text-center">';
        if ($r['status'] == 'pending') {
          $actions .= '<a href="javascript:void(0)" class="table-action table-action-success btn-approve-agenda btn-tooltip" data-id="' . $r['id'] . '" data-toggle="tooltip" title="Setujui"><i class="fas fa-check-circle"></i></a>';
          $actions .= '<a href="javascript:void(0)" class="table-action table-action-delete btn-reject-agenda btn-tooltip" data-id="' . $r['id'] . '" data-toggle="tooltip" title="Tolak"><i class="fas fa-times-circle"></i></a>';
        } else {
          $actions .= '<small class="text-muted">' . htmlspecialchars($r['responded_by_nama'] ?? '-') . '</small>';
        }
        $actions .= '</div>';

        $output['data'][] = [
          '<div class="text-center">' . $no++ . '</div>',
          '<div class="text-center font-weight-bold">' . htmlspecialchars($r['nama_kelas'] ?? '-') . '</div>',
          '<div class="text-center">' . date('d/m/Y', strtotime($r['tanggal'])) . '</div>',
          '<div class="text-center">' . htmlspecialchars($r['nama_lengkap'] ?? '-') . '</div>',
          '<div class="text-center"><small>' . htmlspecialchars($r['catatan'] ?: '-') . '</small></div>',
          '<div class="text-center"><span class="badge badge-' . $badge . '">' . $btxt . '</span></div>',
          '<div class="text-center"><small>' . date('d/m/Y H:i', strtotime($r['created_at'])) . '</small></div>',
          $actions,
        ];
      }
      echo json_encode($output);
      break;
  }
}
