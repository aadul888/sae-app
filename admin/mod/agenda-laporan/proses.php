<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  require_once '../../../library/config.php';
  require_once '../../../library/function.php';
  require_once '../../login/user.php';

  header('Content-Type: application/json');

  $tab = $_POST['tab'] ?? 'guru';
  $dari = $connection->real_escape_string($_POST['dari'] ?? date('Y-m-01'));
  $sampai = $connection->real_escape_string($_POST['sampai'] ?? date('Y-m-d'));

  switch ($tab) {

    case 'guru':
      $filter_guru = !empty($_POST['guru_id']) ? "AND ak.guru_id = " . intval($_POST['guru_id']) : '';
      $sql = "SELECT a.admin_id, a.fullname, a.gelar_depan, a.gelar_belakang,
          COUNT(ak.agenda_id) AS total_jam,
          SUM(CASE WHEN ak.kehadiran_guru='Hadir' THEN 1 ELSE 0 END) AS hadir,
          SUM(CASE WHEN ak.kehadiran_guru='Tidak Hadir' THEN 1 ELSE 0 END) AS tidak_hadir,
          SUM(CASE WHEN ak.kehadiran_guru='Tidak Hadir + Tugas' THEN 1 ELSE 0 END) AS tidak_hadir_tugas
        FROM agenda_kelas ak
        JOIN admin a ON ak.guru_id = a.admin_id
        WHERE ak.tanggal BETWEEN '$dari' AND '$sampai' AND ak.status='submitted' $filter_guru
        GROUP BY a.admin_id ORDER BY a.fullname ASC";

      $result = $connection->query($sql);
      $data = [];
      while ($r = $result->fetch_assoc()) {
        $nama = trim(($r['gelar_depan'] ? $r['gelar_depan'] . ' ' : '') . $r['fullname'] . ($r['gelar_belakang'] ? ', ' . $r['gelar_belakang'] : ''));
        $total = intval($r['total_jam']);
        $pct = $total > 0 ? round(intval($r['hadir']) / $total * 100, 1) : 0;
        $data[] = [
          'nama' => $nama,
          'total' => $total,
          'hadir' => intval($r['hadir']),
          'tidak_hadir' => intval($r['tidak_hadir']),
          'tidak_hadir_tugas' => intval($r['tidak_hadir_tugas']),
          'persen_hadir' => $pct
        ];
      }

      // Summary
      $summary_sql = "SELECT
          COUNT(agenda_id) AS total,
          SUM(CASE WHEN kehadiran_guru='Hadir' THEN 1 ELSE 0 END) AS hadir,
          SUM(CASE WHEN kehadiran_guru='Tidak Hadir' THEN 1 ELSE 0 END) AS tidak_hadir,
          SUM(CASE WHEN kehadiran_guru='Tidak Hadir + Tugas' THEN 1 ELSE 0 END) AS tidak_hadir_tugas
        FROM agenda_kelas WHERE tanggal BETWEEN '$dari' AND '$sampai' AND status='submitted' $filter_guru";
      $s = $connection->query($summary_sql)->fetch_assoc();

      echo json_encode(['data' => $data, 'summary' => $s]);
      break;

    case 'kelas':
      $filter_kelas = !empty($_POST['kelas_id']) ? "AND ak.kelas_id = " . intval($_POST['kelas_id']) : '';
      $sql = "SELECT k.kelas_id, k.nama_kelas,
          COUNT(ak.agenda_id) AS total_jam,
          SUM(CASE WHEN ak.kehadiran_guru='Hadir' THEN 1 ELSE 0 END) AS hadir,
          SUM(CASE WHEN ak.kehadiran_guru='Tidak Hadir' THEN 1 ELSE 0 END) AS tidak_hadir,
          SUM(CASE WHEN ak.kehadiran_guru='Tidak Hadir + Tugas' THEN 1 ELSE 0 END) AS tidak_hadir_tugas
        FROM agenda_kelas ak
        JOIN kelas k ON ak.kelas_id = k.kelas_id
        WHERE ak.tanggal BETWEEN '$dari' AND '$sampai' AND ak.status='submitted' $filter_kelas
        GROUP BY k.kelas_id ORDER BY k.nama_kelas ASC";

      $result = $connection->query($sql);
      $data = [];
      while ($r = $result->fetch_assoc()) {
        $total = intval($r['total_jam']);
        $pct = $total > 0 ? round(intval($r['hadir']) / $total * 100, 1) : 0;
        $data[] = [
          'nama' => $r['nama_kelas'],
          'total' => $total,
          'hadir' => intval($r['hadir']),
          'tidak_hadir' => intval($r['tidak_hadir']),
          'tidak_hadir_tugas' => intval($r['tidak_hadir_tugas']),
          'persen_hadir' => $pct
        ];
      }

      $summary_sql = "SELECT
          COUNT(agenda_id) AS total,
          SUM(CASE WHEN kehadiran_guru='Hadir' THEN 1 ELSE 0 END) AS hadir,
          SUM(CASE WHEN kehadiran_guru='Tidak Hadir' THEN 1 ELSE 0 END) AS tidak_hadir,
          SUM(CASE WHEN kehadiran_guru='Tidak Hadir + Tugas' THEN 1 ELSE 0 END) AS tidak_hadir_tugas
        FROM agenda_kelas WHERE tanggal BETWEEN '$dari' AND '$sampai' AND status='submitted' $filter_kelas";
      $s = $connection->query($summary_sql)->fetch_assoc();

      echo json_encode(['data' => $data, 'summary' => $s]);
      break;

    case 'mapel':
      $filter_mapel = !empty($_POST['mapel_id']) ? "AND ak.mapel_id = " . intval($_POST['mapel_id']) : '';
      $sql = "SELECT m.mapel_id, m.nama_mapel,
          COUNT(ak.agenda_id) AS total_jam,
          SUM(CASE WHEN ak.kehadiran_guru='Hadir' THEN 1 ELSE 0 END) AS hadir,
          SUM(CASE WHEN ak.kehadiran_guru='Tidak Hadir' THEN 1 ELSE 0 END) AS tidak_hadir,
          SUM(CASE WHEN ak.kehadiran_guru='Tidak Hadir + Tugas' THEN 1 ELSE 0 END) AS tidak_hadir_tugas
        FROM agenda_kelas ak
        JOIN agenda_mapel m ON ak.mapel_id = m.mapel_id
        WHERE ak.tanggal BETWEEN '$dari' AND '$sampai' AND ak.status='submitted' $filter_mapel
        GROUP BY m.mapel_id ORDER BY m.nama_mapel ASC";

      $result = $connection->query($sql);
      $data = [];
      while ($r = $result->fetch_assoc()) {
        $total = intval($r['total_jam']);
        $pct = $total > 0 ? round(intval($r['hadir']) / $total * 100, 1) : 0;
        $data[] = [
          'nama' => $r['nama_mapel'],
          'total' => $total,
          'hadir' => intval($r['hadir']),
          'tidak_hadir' => intval($r['tidak_hadir']),
          'tidak_hadir_tugas' => intval($r['tidak_hadir_tugas']),
          'persen_hadir' => $pct
        ];
      }

      $summary_sql = "SELECT
          COUNT(agenda_id) AS total,
          SUM(CASE WHEN kehadiran_guru='Hadir' THEN 1 ELSE 0 END) AS hadir,
          SUM(CASE WHEN kehadiran_guru='Tidak Hadir' THEN 1 ELSE 0 END) AS tidak_hadir,
          SUM(CASE WHEN kehadiran_guru='Tidak Hadir + Tugas' THEN 1 ELSE 0 END) AS tidak_hadir_tugas
        FROM agenda_kelas WHERE tanggal BETWEEN '$dari' AND '$sampai' AND status='submitted' $filter_mapel";
      $s = $connection->query($summary_sql)->fetch_assoc();

      echo json_encode(['data' => $data, 'summary' => $s]);
      break;
  }
}
