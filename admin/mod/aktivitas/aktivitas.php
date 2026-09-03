<?PHP
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
}

// Cek role untuk modul Aktivitas (modul_id perlu didaftarkan, untuk sementara akses longgar)
$modul_id = 56;
include __DIR__ . '/../check_role.php';
if (!$has_access) {
    hak_akses();
    return;
}

require_once __DIR__ . '/../../../library/activity.php';

$limit = isset($_GET['limit']) ? min(intval($_GET['limit']), 200) : 50;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Filter
$action_filter = isset($_GET['action']) ? trim($_GET['action']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Hitung total
$where = 'WHERE 1=1';
$params = [];
if ($action_filter !== '') {
    $where .= " AND a.action='" . $connection->real_escape_string($action_filter) . "'";
}
if ($search !== '') {
    $where .= " AND (a.admin_name LIKE '%" . $connection->real_escape_string($search) . "%' OR a.description LIKE '%" . $connection->real_escape_string($search) . "%')";
}

$count_q = $connection->query("SELECT COUNT(*) AS cnt FROM activity_log a $where");
$total_rows = $count_q ? intval($count_q->fetch_row()[0]) : 0;
$total_pages = max(1, ceil($total_rows / $limit));

// Ambil data
$q = $connection->query("SELECT a.* FROM activity_log a $where ORDER BY a.id DESC LIMIT $limit OFFSET $offset");
$activities = [];
if ($q) {
    while ($r = $q->fetch_assoc()) {
        $activities[] = $r;
    }
}

// Daftar aksi untuk filter
$actions_q = $connection->query("SELECT DISTINCT action FROM activity_log ORDER BY action");
$actions = [];
if ($actions_q) {
    while ($r = $actions_q->fetch_assoc()) {
        $actions[] = $r['action'];
    }
}
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-history text-info mr-2"></i> Log Aktivitas Sistem
                            </h3>
                            <p class="text-muted small mb-0 mt-1">Riwayat aktivitas admin dan sistem</p>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                                <i class="fas fa-sync-alt"></i> Segarkan
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filter -->
                    <form method="get" class="form-inline mb-3">
                        <div class="form-group mr-2">
                            <select name="action" class="form-control form-control-sm" onchange="this.form.submit()">
                                <option value="">Semua Aksi</option>
                                <?php foreach ($actions as $act): ?>
                                    <option value="<?php echo htmlspecialchars($act); ?>" <?php echo $action_filter === $act ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(ucfirst($act)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group mr-2">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari admin atau deskripsi..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <button type="submit" class="btn btn-sm btn-info mr-2">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <?php if ($action_filter !== '' || $search !== ''): ?>
                            <a href="?" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-times"></i> Reset
                            </a>
                        <?php endif; ?>
                        <span class="ml-auto text-muted small">
                            <?php echo number_format($total_rows); ?> total entri
                        </span>
                    </form>

                    <!-- Tabel -->
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle">
                            <thead class="thead-light">
                                <tr>
                                    <th width="60">#</th>
                                    <th width="160" class="text-nowrap">Tanggal</th>
                                    <th width="120">Aksi</th>
                                    <th width="150">Admin</th>
                                    <th>Deskripsi</th>
                                    <th width="80">Method</th>
                                    <th width="140">IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($activities)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                            Belum ada aktivitas tercatat.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($activities as $i => $a): ?>
                                        <tr>
                                            <td class="text-muted"><?php echo $offset + $i + 1; ?></td>
                                            <td class="text-nowrap small">
                                                <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($a['created_at']))); ?>
                                            </td>
                                            <td>
                                                <?php
                                                $badge = 'secondary';
                                                switch ($a['action']) {
                                                    case 'login': $badge = 'success'; break;
                                                    case 'logout': $badge = 'secondary'; break;
                                                    case 'deploy': $badge = 'info'; break;
                                                    case 'create': $badge = 'primary'; break;
                                                    case 'update': $badge = 'warning'; break;
                                                    case 'delete': $badge = 'danger'; break;
                                                    case 'migration': $badge = 'dark'; break;
                                                }
                                                ?>
                                                <span class="badge badge-<?php echo $badge; ?>">
                                                    <?php echo htmlspecialchars(ucfirst($a['action'])); ?>
                                                </span>
                                            </td>
                                            <td class="small"><?php echo htmlspecialchars($a['admin_name'] ?? '-'); ?></td>
                                            <td class="small"><?php echo htmlspecialchars($a['description'] ?? ''); ?></td>
                                            <td class="small"><span class="badge badge-light"><?php echo htmlspecialchars($a['request_method'] ?? '-'); ?></span></td>
                                            <td class="small text-muted"><?php echo htmlspecialchars($a['ip_address'] ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav>
                            <ul class="pagination pagination-sm justify-content-center">
                                <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                                    <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $p; ?>&limit=<?php echo $limit; ?>&action=<?php echo urlencode($action_filter); ?>&search=<?php echo urlencode($search); ?>">
                                            <?php echo $p; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
if (function_exists('index')) {
    index();
}
?>
