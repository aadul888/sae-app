<?php
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
}

$modul_id = 48;
include __DIR__ . '/../check_role.php';

if (!$has_access) {
    theme_404();
    return;
}

require_once __DIR__ . '/../../../library/kelulusan_helper.php';
kelulusan_ensure_tables($connection);

$kelasCol = kelulusan_user_kelas_column($connection);

$where = "1=1";
if (!empty($_GET['action_filter'])) {
    $actionFilter = $connection->real_escape_string($_GET['action_filter']);
    if (in_array($actionFilter, array('OPEN_ENVELOPE', 'DOWNLOAD_SKL'), true)) {
        $where .= " AND h.action='" . $actionFilter . "'";
    }
}

if (!empty($_GET['tanggal'])) {
    $tanggal = $connection->real_escape_string($_GET['tanggal']);
    $where .= " AND DATE(h.created_at)='" . $tanggal . "'";
}

$sql = "SELECT h.*, k.nama_kelas
    FROM kelulusan_history h
    INNER JOIN (
        SELECT MAX(id) AS last_id
        FROM kelulusan_history
        GROUP BY user_id, action
    ) hx ON hx.last_id = h.id
    LEFT JOIN user u ON u.user_id=h.user_id
    LEFT JOIN kelas k ON k.kelas_id=u.`" . $kelasCol . "`
    WHERE " . $where . "
    ORDER BY h.created_at DESC
    LIMIT 2000";
$result = $connection->query($sql);
?>

<div class="header bg-primary pb-4 user-page-header-compact">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-3"></div>
        </div>
    </div>
</div>

<div class="container-fluid mt--6 user-module-page">
    <div class="row">
        <div class="col">
            <div class="card shadow module-table-card">
                <div class="card-header module-table-header">
                    <div class="module-header-row" style="gap:10px;">
                        <div>
                            <h4 class="mb-1">Log Aktivitas SKL</h4>
                            <small class="text-muted">Riwayat aktivitas buka amplop dan unduh SKL oleh murid.</small>
                        </div>
                        <div class="module-header-actions">
                            <button type="button" class="btn-mod btn-mod-teal btn-open-filter-history" title="Filter"><i class="fas fa-filter"></i></button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped align-items-center" id="table-skl-history">
                        <thead class="thead-light">
                            <tr>
                                <th>Waktu</th>
                                <th>NISN</th>
                                <th>Nama</th>
                                <th>Kelas</th>
                                <th>Aksi</th>
                                <th>IP</th>
                                <th>User Agent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result) {
                                while ($row = $result->fetch_assoc()) { ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nisn']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                                        <td><?php echo htmlspecialchars(!empty($row['nama_kelas']) ? $row['nama_kelas'] : '-'); ?></td>
                                        <td>
                                            <?php if ($row['action'] === 'DOWNLOAD_SKL') { ?>
                                                <span class="badge badge-success">Unduh SKL</span>
                                            <?php } else { ?>
                                                <span class="badge badge-info">Buka Amplop</span>
                                            <?php } ?>
                                        </td>
                                        <td><?php echo htmlspecialchars(!empty($row['ip_address']) ? $row['ip_address'] : '-'); ?></td>
                                        <td><?php echo htmlspecialchars(!empty($row['user_agent']) ? substr($row['user_agent'], 0, 90) : '-'); ?></td>
                                    </tr>
                            <?php }
                            } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Filter SKL History -->
<div class="modal fade modal-filter-history" id="modalFilterHistory" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-filter mr-2 text-teal"></i>Filter Log Aktivitas</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body pb-2">
                <form method="get" id="form-filter-history">
                    <input type="hidden" name="mod" value="skl-history">
                    <div class="form-group">
                        <label class="form-control-label">Aksi</label>
                        <select name="action_filter" class="form-control form-control-sm">
                            <option value="">Semua</option>
                            <option value="OPEN_ENVELOPE" <?php echo (isset($_GET['action_filter']) && $_GET['action_filter'] === 'OPEN_ENVELOPE') ? 'selected' : ''; ?>>Buka Amplop</option>
                            <option value="DOWNLOAD_SKL" <?php echo (isset($_GET['action_filter']) && $_GET['action_filter'] === 'DOWNLOAD_SKL') ? 'selected' : ''; ?>>Unduh SKL</option>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-control-label">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control form-control-sm" value="<?php echo isset($_GET['tanggal']) ? htmlspecialchars($_GET['tanggal']) : ''; ?>">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <a href="?mod=skl-history" class="btn btn-outline-secondary btn-sm">Reset</a>
                <button type="submit" form="form-filter-history" class="btn btn-primary btn-sm">Terapkan</button>
            </div>
        </div>
    </div>
</div>
