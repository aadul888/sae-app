<?php
/**
 * Agenda - Public Backend API
 * Actions: realtime, report
 */
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../../library/config.php';
require_once __DIR__ . '/../../library/function.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (!isset($connection) || !$connection || $connection->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'DB error']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'realtime':
        $tanggal = $_GET['tanggal'] ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) $tanggal = date('Y-m-d');
        $esc_tgl = $connection->real_escape_string($tanggal);

        $hari_map = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
        $hari = $hari_map[date('l', strtotime($tanggal))];

        // Get all classes
        $q_kelas = $connection->query("SELECT DISTINCT k.kelas, k.kelas_nama FROM user k WHERE k.kelas IS NOT NULL AND k.kelas != '' GROUP BY k.kelas ORDER BY k.kelas_nama ASC");
        $kelas_list = [];
        while ($r = $q_kelas->fetch_assoc()) $kelas_list[] = $r;

        $summary = ['hadir' => 0, 'tidak_hadir' => 0, 'tugas' => 0, 'belum' => 0];
        $data = [];

        foreach ($kelas_list as $kls) {
            $kid = intval($kls['kelas']);
            // Get jadwal for this class + day
            $q_jdw = $connection->query("SELECT j.jam_ke, j.mapel_id, m.nama_mapel, m.kode_mapel, a.fullname, a.gelar_depan, a.gelar_belakang
                FROM agenda_jadwal j
                LEFT JOIN agenda_mapel m ON j.mapel_id = m.mapel_id
                LEFT JOIN admin a ON m.guru_id = a.admin_id
                WHERE j.kelas_id='$kid' AND j.hari='$hari'
                ORDER BY j.jam_ke ASC");

            if (!$q_jdw || $q_jdw->num_rows == 0) continue;

            $jadwal = [];
            $total_jam = 0;
            $terisi = 0;

            while ($jd = $q_jdw->fetch_assoc()) {
                $total_jam++;
                $nama_guru = $jd['fullname']
                    ? trim(($jd['gelar_depan'] ? $jd['gelar_depan'] . ' ' : '') . $jd['fullname'] . ($jd['gelar_belakang'] ? ', ' . $jd['gelar_belakang'] : ''))
                    : '-';

                // Check if agenda is filled for this jam
                $q_ag = $connection->query("SELECT kehadiran_guru FROM agenda_kelas WHERE kelas_id='$kid' AND tanggal='$esc_tgl' AND jam_ke='" . intval($jd['jam_ke']) . "' AND status != 'dihapus' LIMIT 1");
                $kehadiran = null;
                if ($q_ag && $q_ag->num_rows > 0) {
                    $ag = $q_ag->fetch_assoc();
                    $kehadiran = $ag['kehadiran_guru'];
                    $terisi++;
                    if ($kehadiran === 'Hadir') $summary['hadir']++;
                    elseif ($kehadiran === 'Tidak Hadir') $summary['tidak_hadir']++;
                    elseif ($kehadiran === 'Tidak Hadir + Tugas') $summary['tugas']++;
                } else {
                    $summary['belum']++;
                }

                $jadwal[] = [
                    'jam_ke' => $jd['jam_ke'],
                    'nama_mapel' => $jd['nama_mapel'],
                    'nama_guru' => $nama_guru,
                    'kehadiran' => $kehadiran
                ];
            }

            $data[] = [
                'kelas_id' => $kid,
                'kelas_nama' => $kls['kelas_nama'],
                'total_jam' => $total_jam,
                'terisi' => $terisi,
                'jadwal' => $jadwal
            ];
        }

        echo json_encode(['status' => 'success', 'summary' => $summary, 'data' => $data]);
        break;

    case 'report':
        $type = $_GET['type'] ?? 'guru';
        $dari = $_GET['dari'] ?? date('Y-m-01');
        $sampai = $_GET['sampai'] ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari)) $dari = date('Y-m-01');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai)) $sampai = date('Y-m-d');
        $esc_dari = $connection->real_escape_string($dari);
        $esc_sampai = $connection->real_escape_string($sampai);

        $data = [];

        if ($type === 'guru') {
            $q = $connection->query("SELECT a.admin_id, a.fullname, a.gelar_depan, a.gelar_belakang,
                SUM(CASE WHEN ak.kehadiran_guru='Hadir' THEN 1 ELSE 0 END) as hadir,
                SUM(CASE WHEN ak.kehadiran_guru='Tidak Hadir' THEN 1 ELSE 0 END) as tidak_hadir,
                SUM(CASE WHEN ak.kehadiran_guru='Tidak Hadir + Tugas' THEN 1 ELSE 0 END) as tugas
                FROM agenda_kelas ak
                LEFT JOIN admin a ON ak.guru_id = a.admin_id
                WHERE ak.tanggal BETWEEN '$esc_dari' AND '$esc_sampai' AND ak.status != 'dihapus' AND ak.guru_id > 0
                GROUP BY ak.guru_id ORDER BY a.fullname ASC");
            while ($r = $q->fetch_assoc()) {
                $nama = trim(($r['gelar_depan'] ? $r['gelar_depan'] . ' ' : '') . $r['fullname'] . ($r['gelar_belakang'] ? ', ' . $r['gelar_belakang'] : ''));
                $data[] = ['nama' => $nama, 'hadir' => (int)$r['hadir'], 'tidak_hadir' => (int)$r['tidak_hadir'], 'tugas' => (int)$r['tugas']];
            }
        } elseif ($type === 'kelas') {
            $q = $connection->query("SELECT u.kelas_nama,
                SUM(CASE WHEN ak.kehadiran_guru='Hadir' THEN 1 ELSE 0 END) as hadir,
                SUM(CASE WHEN ak.kehadiran_guru='Tidak Hadir' THEN 1 ELSE 0 END) as tidak_hadir,
                SUM(CASE WHEN ak.kehadiran_guru='Tidak Hadir + Tugas' THEN 1 ELSE 0 END) as tugas
                FROM agenda_kelas ak
                LEFT JOIN (SELECT DISTINCT kelas, kelas_nama FROM user) u ON ak.kelas_id = u.kelas
                WHERE ak.tanggal BETWEEN '$esc_dari' AND '$esc_sampai' AND ak.status != 'dihapus'
                GROUP BY ak.kelas_id ORDER BY u.kelas_nama ASC");
            while ($r = $q->fetch_assoc()) {
                $data[] = ['nama' => $r['kelas_nama'] ?: 'Kelas ?', 'hadir' => (int)$r['hadir'], 'tidak_hadir' => (int)$r['tidak_hadir'], 'tugas' => (int)$r['tugas']];
            }
        } elseif ($type === 'mapel') {
            $q = $connection->query("SELECT m.nama_mapel,
                SUM(CASE WHEN ak.kehadiran_guru='Hadir' THEN 1 ELSE 0 END) as hadir,
                SUM(CASE WHEN ak.kehadiran_guru='Tidak Hadir' THEN 1 ELSE 0 END) as tidak_hadir,
                SUM(CASE WHEN ak.kehadiran_guru='Tidak Hadir + Tugas' THEN 1 ELSE 0 END) as tugas
                FROM agenda_kelas ak
                LEFT JOIN agenda_mapel m ON ak.mapel_id = m.mapel_id
                WHERE ak.tanggal BETWEEN '$esc_dari' AND '$esc_sampai' AND ak.status != 'dihapus'
                GROUP BY ak.mapel_id ORDER BY m.nama_mapel ASC");
            while ($r = $q->fetch_assoc()) {
                $data[] = ['nama' => $r['nama_mapel'] ?: '-', 'hadir' => (int)$r['hadir'], 'tidak_hadir' => (int)$r['tidak_hadir'], 'tugas' => (int)$r['tugas']];
            }
        }

        echo json_encode(['status' => 'success', 'data' => $data]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Action tidak valid']);
        break;
}