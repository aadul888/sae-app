<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
} else {
    require_once '../../../library/config.php';
    require_once('../../../library/function.php');

    $modul_id = 42;
    include __DIR__ . '/../check_role.php';
    if (!isset($data_role['lihat']) || $data_role['lihat'] != 'Y') {
        echo json_encode(["draw" => intval($_REQUEST['draw'] ?? 0), "recordsTotal" => 0, "recordsFiltered" => 0, "data" => [], "aaData" => []]);
        exit;
    }

    $draw = intval($_REQUEST['draw'] ?? 0);
    $start = intval($_REQUEST['start'] ?? 0);
    $length = intval($_REQUEST['length'] ?? 25);
    $search = $_REQUEST['search']['value'] ?? '';

    // Total records
    $totalQ = $connection->query("SELECT COUNT(*) AS c FROM info");
    $totalRecords = $totalQ ? (int)$totalQ->fetch_assoc()['c'] : 0;

    // Filtered
    $where = '';
    if ($search !== '') {
        $s = $connection->real_escape_string($search);
        $where = " WHERE judul LIKE '%$s%' OR konten LIKE '%$s%' OR kategori LIKE '%$s%'";
    }
    $filteredQ = $connection->query("SELECT COUNT(*) AS c FROM info$where");
    $filteredRecords = $filteredQ ? (int)$filteredQ->fetch_assoc()['c'] : 0;

    // Order
    $order = intval($_REQUEST['order'][0]['column'] ?? 1);
    $orderDir = ($_REQUEST['order'][0]['dir'] ?? 'asc') === 'asc' ? 'ASC' : 'DESC';
    $orderMap = [1 => 'judul', 2 => 'kategori', 3 => 'konten', 4 => 'aktif', 5 => 'urutan', 6 => 'tgl_mulai', 7 => 'tgl_selesai'];
    $orderBy = isset($orderMap[$order]) ? $orderMap[$order] : 'urutan DESC, id DESC';

    $baseSql = "SELECT id, judul, kategori, konten, aktif, urutan, tgl_mulai, tgl_selesai FROM info$where ORDER BY $orderBy $orderDir LIMIT $start, $length";
    $qData = $connection->query($baseSql);

    $data = [];
    $no = $start + 1;

    if ($qData) {
        while ($r = $qData->fetch_assoc()) {
            $id = (int)$r['id'];
            $judul = htmlspecialchars($r['judul'] ?? '');
            $judul_short = strlen($judul) > 80 ? substr($judul, 0, 80) . '...' : $judul;

            $aktif = (int)$r['aktif'];
            $aktifBadge = $aktif
                ? '<span class="badge badge-success">Ya</span>'
                : '<span class="badge badge-secondary">Tidak</span>';

            $urutan = (int)$r['urutan'];
            $tgl_mulai = $r['tgl_mulai'] ?? '';
            $tgl_selesai = $r['tgl_selesai'] ?? '';
            $tgl_display = '';
            if ($tgl_mulai || $tgl_selesai) {
                $tgl_display = ($tgl_mulai ? date('d/m/Y', strtotime($tgl_mulai)) : 'â€¦') . ' â€“ ' . ($tgl_selesai ? date('d/m/Y', strtotime($tgl_selesai)) : 'â€¦');
            } else {
                $tgl_display = '<span class="text-muted font-italic">-</span>';
            }

            $aksi = '<div class="btn-group btn-group-sm" style="gap:2px;">';
            if (isset($data_role['modifikasi']) && $data_role['modifikasi'] == 'Y') {
                $j = htmlspecialchars($r['judul'] ?? '', ENT_QUOTES);
                $k = htmlspecialchars($r['konten'] ?? '', ENT_QUOTES);
                $kat = htmlspecialchars($r['kategori'] ?? '', ENT_QUOTES);
                $aksi .= '<a href="javascript:void(0)" class="table-action table-action-primary btn-tooltip btn-edit-info" data-toggle="tooltip" title="Edit" data-id="' . $id . '" data-judul="' . $j . '" data-konten="' . $k . '" data-kategori="' . $kat . '" data-aktif="' . $aktif . '" data-urutan="' . $urutan . '" data-tgl-mulai="' . $tgl_mulai . '" data-tgl-selesai="' . $tgl_selesai . '"><i class="fas fa-edit"></i></a>';
                $aksi .= '<a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-hapus-info" data-toggle="tooltip" title="Hapus" data-id="' . $id . '"><i class="fas fa-trash"></i></a>';
            }
            $aksi .= '</div>';

            $konten = htmlspecialchars($r['konten'] ?? '');
            $konten_short = strlen($konten) > 100 ? substr($konten, 0, 100) . '...' : $konten;
            $konten_display = $konten_short !== '' ? $konten_short : '<span class="text-muted font-italic">-</span>';

            $kategori_val = htmlspecialchars($r['kategori'] ?? '');
            $kategori_display = $kategori_val
                ? '<span class="badge badge-info">' . ucfirst($kategori_val) . '</span>'
                : '<span class="text-muted font-italic">-</span>';

            $data[] = [
                '<div class="text-center font-weight-bold">' . $no++ . '</div>',
                $judul_short,
                $kategori_display,
                $konten_display,
                '<div class="text-center">' . $aktifBadge . '</div>',
                '<div class="text-center">' . $urutan . '</div>',
                '<div class="text-nowrap">' . $tgl_display . '</div>',
                $aksi
            ];
        }
    }

    echo json_encode([
        "draw" => $draw,
        "recordsTotal" => $totalRecords,
        "recordsFiltered" => $filteredRecords,
        "data" => $data,
        "aaData" => $data
    ]);
}
