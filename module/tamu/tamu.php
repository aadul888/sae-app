<?php

/**
 * Dashboard Buku Tamu - Halaman Admin
 * Menampilkan daftar tamu dan statistik
 */

// Include config dan functions
if (!isset($connection)) {
    include_once '../../library/config.php';
    include_once '../../library/function.php';
}

// Check if standalone
$is_standalone = !isset($site_name);

// Get statistics
$stats = getGuestStats($connection);
$recent_guests = getRecentGuests($connection, 10);

if ($is_standalone) {
?>
    <!DOCTYPE html>
    <html lang="id">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard Buku Tamu | <?php echo $site_name ?? 'SMK Negeri 1 Pagelaran'; ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <link href="../assets/css/dashboard.css" rel="stylesheet">
    </head>

    <body>
    <?php } ?>

    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1">
                            <i class="fas fa-users text-primary me-2"></i>
                            Dashboard Buku Tamu
                        </h2>
                        <p class="text-muted mb-0">Kelola dan pantau data kunjungan tamu</p>
                    </div>
                    <div>
                        <a href="<?php echo $is_standalone ? 'form.php' : ($base_url . 'tamu/form'); ?>" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Tamu Baru
                        </a>
                        <button class="btn btn-outline-secondary" onclick="location.reload()">
                            <i class="fas fa-sync me-2"></i>Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-primary bg-opacity-10 rounded p-3">
                                    <i class="fas fa-users text-primary fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1 small">Hari Ini</h6>
                                <h3 class="mb-0"><?php echo $stats['today']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-success bg-opacity-10 rounded p-3">
                                    <i class="fas fa-calendar-week text-success fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1 small">Minggu Ini</h6>
                                <h3 class="mb-0"><?php echo $stats['week']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-warning bg-opacity-10 rounded p-3">
                                    <i class="fas fa-calendar-alt text-warning fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1 small">Bulan Ini</h6>
                                <h3 class="mb-0"><?php echo $stats['month']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-info bg-opacity-10 rounded p-3">
                                    <i class="fas fa-user-clock text-info fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1 small">Sedang Berkunjung</h6>
                                <h3 class="mb-0"><?php echo $stats['active']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Guests Table -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-history text-primary me-2"></i>
                                Tamu Terbaru
                            </h5>
                            <div class="d-flex gap-2">
                                <input type="text" class="form-control form-control-sm" id="searchInput"
                                    placeholder="Cari nama/instansi..." style="width: 200px;">
                                <select class="form-select form-select-sm" id="statusFilter" style="width: 150px;">
                                    <option value="">Semua Status</option>
                                    <option value="Aktif">Aktif</option>
                                    <option value="Selesai">Selesai</option>
                                    <option value="Batal">Batal</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="guestsTable">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="border-0 ps-3">Guest ID</th>
                                        <th class="border-0">Foto</th>
                                        <th class="border-0">Nama</th>
                                        <th class="border-0">Instansi</th>
                                        <th class="border-0">Keperluan</th>
                                        <th class="border-0">Waktu Masuk</th>
                                        <th class="border-0">Status</th>
                                        <th class="border-0 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_guests)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">
                                                <i class="fas fa-users fs-1 mb-3 d-block opacity-25"></i>
                                                Belum ada data tamu
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_guests as $guest): ?>
                                            <tr>
                                                <td class="ps-3">
                                                    <code><?php echo htmlspecialchars($guest['guest_id']); ?></code>
                                                </td>
                                                <td>
                                                    <?php if ($guest['foto']): ?>
                                                        <img src="../../content/tamu/<?php echo htmlspecialchars($guest['foto']); ?>"
                                                            alt="Foto" class="rounded-circle" width="40" height="40"
                                                            style="object-fit: cover;" onclick="showPhotoModal(this.src)">
                                                    <?php else: ?>
                                                        <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center"
                                                            style="width: 40px; height: 40px;">
                                                            <i class="fas fa-user text-white"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($guest['nama']); ?></strong>
                                                        <?php if ($guest['telepon']): ?>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars($guest['telepon']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($guest['instansi']); ?></td>
                                                <td>
                                                    <span class="badge bg-primary bg-opacity-10 text-primary">
                                                        <?php echo htmlspecialchars($guest['keperluan']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div>
                                                        <?php echo date('d/m/Y', strtotime($guest['tanggal_kunjungan'])); ?>
                                                        <br><small class="text-muted"><?php echo date('H:i', strtotime($guest['waktu_masuk'])); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_class = [
                                                        'Aktif' => 'success',
                                                        'Selesai' => 'secondary',
                                                        'Batal' => 'danger'
                                                    ];
                                                    $class = $status_class[$guest['status']] ?? 'secondary';
                                                    ?>
                                                    <span class="badge bg-<?php echo $class; ?>">
                                                        <?php echo $guest['status']; ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary btn-sm"
                                                            onclick="viewGuest(<?php echo $guest['id']; ?>)"
                                                            title="Lihat Detail">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <?php if ($guest['status'] === 'Aktif'): ?>
                                                            <button class="btn btn-outline-success btn-sm"
                                                                onclick="checkoutGuest(<?php echo $guest['id']; ?>)"
                                                                title="Check Out">
                                                                <i class="fas fa-sign-out-alt"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <button class="btn btn-outline-secondary btn-sm"
                                                            onclick="editGuest(<?php echo $guest['id']; ?>)"
                                                            title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                Menampilkan <?php echo count($recent_guests); ?> data terbaru
                            </small>
                            <a href="?view=all" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-list me-1"></i>Lihat Semua
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Photo Modal -->
    <div class="modal fade" id="photoModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Foto Tamu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="" alt="Foto Tamu" class="img-fluid rounded" id="modalPhoto">
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Tamu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <?php if ($is_standalone): ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php endif; ?>

    <script>
        // Search and filter functionality
        document.getElementById('searchInput').addEventListener('input', filterTable);
        document.getElementById('statusFilter').addEventListener('change', filterTable);

        function filterTable() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('#guestsTable tbody tr');

            rows.forEach(row => {
                if (row.cells.length === 1) return; // Skip empty state row

                const nama = row.cells[2].textContent.toLowerCase();
                const instansi = row.cells[3].textContent.toLowerCase();
                const status = row.cells[6].textContent.trim();

                const matchesSearch = nama.includes(searchTerm) || instansi.includes(searchTerm);
                const matchesStatus = !statusFilter || status === statusFilter;

                row.style.display = matchesSearch && matchesStatus ? '' : 'none';
            });
        }

        function showPhotoModal(src) {
            document.getElementById('modalPhoto').src = src;
            new bootstrap.Modal(document.getElementById('photoModal')).show();
        }

        function viewGuest(id) {
            // Load guest details
            fetch(`api.php?action=get_guest&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('detailContent').innerHTML = generateDetailHTML(data.guest);
                        new bootstrap.Modal(document.getElementById('detailModal')).show();
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function checkoutGuest(id) {
            if (confirm('Yakin ingin checkout tamu ini?')) {
                fetch(`api.php?action=checkout&id=${id}`, {
                        method: 'POST'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        }

        function editGuest(id) {
            // Redirect to edit page or open edit modal
            window.location.href = `edit.php?id=${id}`;
        }

        function generateDetailHTML(guest) {
            return `
        <div class="row">
            <div class="col-md-4 text-center">
                ${guest.foto ? 
                    `<img src="../../content/tamu/${guest.foto}" alt="Foto" class="img-fluid rounded mb-3" style="max-height: 200px;">` :
                    `<div class="bg-light rounded p-4 mb-3"><i class="fas fa-user fs-1 text-muted"></i></div>`
                }
                <h5>${guest.nama}</h5>
                <p class="text-muted">${guest.instansi}</p>
            </div>
            <div class="col-md-8">
                <table class="table">
                    <tr><th>Guest ID:</th><td><code>${guest.guest_id}</code></td></tr>
                    <tr><th>Telepon:</th><td>${guest.telepon || '-'}</td></tr>
                    <tr><th>Keperluan:</th><td><span class="badge bg-primary">${guest.keperluan}</span></td></tr>
                    <tr><th>Keterangan:</th><td>${guest.keterangan || '-'}</td></tr>
                    <tr><th>Tanggal:</th><td>${new Date(guest.tanggal_kunjungan).toLocaleDateString('id-ID')}</td></tr>
                    <tr><th>Waktu Masuk:</th><td>${guest.waktu_masuk}</td></tr>
                    <tr><th>Waktu Keluar:</th><td>${guest.waktu_keluar || '-'}</td></tr>
                    <tr><th>Status:</th><td><span class="badge bg-${guest.status === 'Aktif' ? 'success' : 'secondary'}">${guest.status}</span></td></tr>
                </table>
            </div>
        </div>
    `;
        }
    </script>

    <?php if ($is_standalone): ?>
    </body>

    </html>
<?php endif; ?>

<?php
/**
 * Helper Functions
 */

function getGuestStats($connection)
{
    // Create table if not exists
    createGuestBookTableIfNotExists($connection);

    $stats = [
        'today' => 0,
        'week' => 0,
        'month' => 0,
        'active' => 0
    ];

    try {
        // Today
        $today_query = "SELECT COUNT(*) as count FROM buku_tamu WHERE DATE(tanggal_kunjungan) = CURDATE()";
        $result = $connection->query($today_query);
        if ($result) {
            $stats['today'] = $result->fetch_assoc()['count'];
        }

        // This week
        $week_query = "SELECT COUNT(*) as count FROM buku_tamu WHERE YEARWEEK(tanggal_kunjungan) = YEARWEEK(NOW())";
        $result = $connection->query($week_query);
        if ($result) {
            $stats['week'] = $result->fetch_assoc()['count'];
        }

        // This month
        $month_query = "SELECT COUNT(*) as count FROM buku_tamu WHERE YEAR(tanggal_kunjungan) = YEAR(NOW()) AND MONTH(tanggal_kunjungan) = MONTH(NOW())";
        $result = $connection->query($month_query);
        if ($result) {
            $stats['month'] = $result->fetch_assoc()['count'];
        }

        // Active guests
        $active_query = "SELECT COUNT(*) as count FROM buku_tamu WHERE status = 'Aktif'";
        $result = $connection->query($active_query);
        if ($result) {
            $stats['active'] = $result->fetch_assoc()['count'];
        }
    } catch (Exception $e) {
        error_log('Error getting stats: ' . $e->getMessage());
    }

    return $stats;
}

function getRecentGuests($connection, $limit = 10)
{
    createGuestBookTableIfNotExists($connection);

    $guests = [];

    try {
        $query = "SELECT * FROM buku_tamu ORDER BY created_at DESC LIMIT ?";
        $stmt = $connection->prepare($query);

        if ($stmt) {
            $stmt->bind_param('i', $limit);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $guests[] = $row;
            }

            $stmt->close();
        }
    } catch (Exception $e) {
        error_log('Error getting recent guests: ' . $e->getMessage());
    }

    return $guests;
}

function createGuestBookTableIfNotExists($connection)
{
    $check_table = "SHOW TABLES LIKE 'buku_tamu'";
    $result = $connection->query($check_table);

    if ($result && $result->num_rows == 0) {
        $create_table = "
            CREATE TABLE `buku_tamu` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `guest_id` varchar(50) NOT NULL UNIQUE,
                `nama` varchar(100) NOT NULL,
                `instansi` varchar(100) NOT NULL,
                `telepon` varchar(20) DEFAULT NULL,
                `keperluan` varchar(50) NOT NULL,
                `keterangan` text DEFAULT NULL,
                `foto` varchar(255) DEFAULT NULL,
                `tanggal_kunjungan` date NOT NULL,
                `waktu_masuk` time NOT NULL,
                `waktu_keluar` time DEFAULT NULL,
                `status` enum('Aktif','Selesai','Batal') DEFAULT 'Aktif',
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_guest_id` (`guest_id`),
                KEY `idx_tanggal` (`tanggal_kunjungan`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";

        $connection->query($create_table);
    }
}
?>