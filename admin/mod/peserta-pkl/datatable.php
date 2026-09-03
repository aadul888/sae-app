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

  $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
  $start = intval($_POST['start'] ?? 0);
  $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
  $sLimit = ($length != -1) ? "LIMIT $start, $length" : "";
  $search = isset($_POST['search']['value']) ? $link->real_escape_string($_POST['search']['value']) : '';

  // Eligible = active, grade XII
  $conds = ["LOWER(TRIM(u.status))='aktif'", "(u.tingkat='12' OR k.tingkat_pendidikan_id='12' OR UPPER(COALESCE(u.kelas_nama, k.nama_kelas, '')) LIKE 'XII%')"];

  $f_kelas = $link->real_escape_string($_POST['f_kelas'] ?? '');
  $f_jurusan = $link->real_escape_string($_POST['f_jurusan'] ?? '');
  $f_kirim = $_POST['f_kirim'] ?? '';
  if ($f_kelas !== '') $conds[] = "u.kelas='$f_kelas'";
  if ($f_jurusan !== '') $conds[] = "u.jurusan_id='$f_jurusan'";
  if ($f_kirim === 'belum') $conds[] = "(p.status_kirim IS NULL OR p.status_kirim='Pending')";
  elseif ($f_kirim === 'Terkirim') $conds[] = "p.status_kirim='Terkirim'";
  elseif ($f_kirim === 'Gagal') $conds[] = "p.status_kirim='Gagal'";
  if ($search !== '') $conds[] = "(u.nama_lengkap LIKE '%$search%' OR u.nisn LIKE '%$search%')";

  $where = 'WHERE ' . implode(' AND ', $conds);

  $base = "FROM user u
           LEFT JOIN kelas k ON u.kelas = k.kelas_id
           LEFT JOIN jurusan j ON u.jurusan_id = j.jurusan_id
           LEFT JOIN pkl_peserta p ON p.nisn = u.nisn
           $where";

  $sql = "SELECT SQL_CALC_FOUND_ROWS u.user_id, u.nisn, u.nama_lengkap,
                 COALESCE(u.kelas_nama, k.nama_kelas, '-') AS nama_kelas,
                 COALESCE(j.nama_jurusan, '-') AS nama_jurusan,
                 p.status_kirim, p.sent_at
          $base ORDER BY nama_kelas ASC, u.nama_lengkap ASC $sLimit";
  $res = $link->query($sql);
  $filtered = $link->query("SELECT FOUND_ROWS()")->fetch_row()[0];

  $totalRow = $link->query("SELECT COUNT(*) FROM user u LEFT JOIN kelas k ON u.kelas=k.kelas_id WHERE LOWER(TRIM(u.status))='aktif' AND (u.tingkat='12' OR k.tingkat_pendidikan_id='12' OR UPPER(COALESCE(u.kelas_nama,k.nama_kelas,'')) LIKE 'XII%')");
  $total = $totalRow ? $totalRow->fetch_row()[0] : 0;

  $output = ["draw" => $draw, "recordsTotal" => intval($total), "recordsFiltered" => intval($filtered), "data" => []];
  $no = $start + 1;
  while ($r = $res->fetch_assoc()) {
    $st = $r['status_kirim'];
    if ($st === 'Terkirim') $badge = '<span class="badge badge-success">Terkirim</span>' . ($r['sent_at'] ? '<br><small class="text-muted">' . htmlspecialchars($r['sent_at']) . '</small>' : '');
    elseif ($st === 'Gagal') $badge = '<span class="badge badge-danger">Gagal</span>';
    else $badge = '<span class="badge badge-secondary">Belum</span>';

    $output['data'][] = [
      '<div class="text-center"><input type="checkbox" class="rowCheck" value="' . htmlspecialchars($r['nisn'], ENT_QUOTES) . '"></div>',
      '<div class="text-center">' . $no++ . '</div>',
      '<div>' . htmlspecialchars($r['nisn']) . '</div>',
      '<div class="font-weight-bold">' . htmlspecialchars($r['nama_lengkap']) . '</div>',
      '<div class="text-center">' . htmlspecialchars($r['nama_kelas']) . '</div>',
      '<div>' . htmlspecialchars($r['nama_jurusan']) . '</div>',
      '<div class="text-center">' . $badge . '</div>',
    ];
  }
  header('Content-Type: application/json');
  echo json_encode($output);
}
