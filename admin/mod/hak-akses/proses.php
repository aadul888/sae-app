<?php
session_start();
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
} else {
    require_once '../../../library/config.php';
    require_once('../../../library/function.php');
    require_once '../../login/user.php';

    if (!function_exists('hak_akses_level_rules')) {
        function hak_akses_level_rules()
        {
            return [
                'utama' => ['Operator Sekolah', 'Guru', 'Tenaga Administrasi'],
                'tugas_guru' => [
                    'Kepala Sekolah',
                    'Waka Kurikulum',
                    'Waka Humas',
                    'Waka Sarpras',
                    'Waka Kesiswaan',
                    'Kepala Program Keahlian',
                    'Wali Kelas'
                ],
                'tugas_tu' => ['Guru Piket', 'Security', 'Toolman/Teknisi'],
                'superadmin' => 'Operator Sekolah'
            ];
        }
    }

    if (!function_exists('hak_akses_normalize_level_name')) {
        function hak_akses_normalize_level_name($name)
        {
            return strtolower(trim((string) $name));
        }
    }

    if (!function_exists('hak_akses_get_level_catalog')) {
        function hak_akses_get_level_catalog($connection)
        {
            static $cache = null;
            if ($cache !== null) {
                return $cache;
            }

            $by_id = [];
            $by_name = [];
            $query = $connection->query("SELECT level_id, level_nama, tipe FROM level");
            if ($query) {
                while ($row = $query->fetch_assoc()) {
                    $row['level_id'] = (int) $row['level_id'];
                    $by_id[$row['level_id']] = $row;
                    $by_name[hak_akses_normalize_level_name($row['level_nama'])] = $row;
                }
            }

            $cache = ['by_id' => $by_id, 'by_name' => $by_name];
            return $cache;
        }
    }

    if (!function_exists('hak_akses_get_level_row')) {
        function hak_akses_get_level_row($connection, $levelId)
        {
            $levelId = (int) $levelId;
            if ($levelId <= 0) {
                return null;
            }

            $catalog = hak_akses_get_level_catalog($connection);
            return isset($catalog['by_id'][$levelId]) ? $catalog['by_id'][$levelId] : null;
        }
    }

    if (!function_exists('hak_akses_get_level_id_by_name')) {
        function hak_akses_get_level_id_by_name($connection, $levelName)
        {
            $catalog = hak_akses_get_level_catalog($connection);
            $key = hak_akses_normalize_level_name($levelName);
            if (!isset($catalog['by_name'][$key])) {
                return 0;
            }
            return (int) $catalog['by_name'][$key]['level_id'];
        }
    }

    if (!function_exists('hak_akses_get_main_level_ids')) {
        function hak_akses_get_main_level_ids($connection)
        {
            static $cache = null;
            if ($cache !== null) {
                return $cache;
            }

            $rules = hak_akses_level_rules();
            $cache = [
                'operator' => hak_akses_get_level_id_by_name($connection, $rules['superadmin']),
                'guru' => hak_akses_get_level_id_by_name($connection, 'Guru'),
                'tu' => hak_akses_get_level_id_by_name($connection, 'Tenaga Administrasi')
            ];
            return $cache;
        }
    }

    if (!function_exists('hak_akses_get_configured_level_ids')) {
        function hak_akses_get_configured_level_ids($connection)
        {
            static $cache = null;
            if ($cache !== null) {
                return $cache;
            }

            $rules = hak_akses_level_rules();
            $all_names = array_merge($rules['utama'], $rules['tugas_guru'], $rules['tugas_tu']);
            $ids = [];
            foreach ($all_names as $name) {
                $id = hak_akses_get_level_id_by_name($connection, $name);
                if ($id > 0) {
                    $ids[$id] = $id;
                }
            }

            $cache = array_values($ids);
            return $cache;
        }
    }

    if (!function_exists('hak_akses_is_configured_level')) {
        function hak_akses_is_configured_level($connection, $levelId)
        {
            $levelId = (int) $levelId;
            if ($levelId <= 0) {
                return false;
            }

            $configured = hak_akses_get_configured_level_ids($connection);
            return in_array($levelId, $configured, true);
        }
    }

    if (!function_exists('hak_akses_is_operator_level')) {
        function hak_akses_is_operator_level($connection, $levelId)
        {
            $levelId = (int) $levelId;
            if ($levelId <= 0) {
                return false;
            }

            $main_ids = hak_akses_get_main_level_ids($connection);
            return ($main_ids['operator'] > 0 && $levelId === (int) $main_ids['operator']);
        }
    }

    if (!function_exists('hak_akses_get_tugas_parent_level_name')) {
        function hak_akses_get_tugas_parent_level_name($levelName)
        {
            $rules = hak_akses_level_rules();
            if (in_array($levelName, $rules['tugas_guru'], true)) {
                return 'Guru';
            }
            if (in_array($levelName, $rules['tugas_tu'], true)) {
                return 'Tenaga Administrasi';
            }
            return '';
        }
    }

    if (!function_exists('hak_akses_get_main_filter_ids_for_tugas')) {
        function hak_akses_get_main_filter_ids_for_tugas($connection)
        {
            $main_ids = hak_akses_get_main_level_ids($connection);
            $ids = [];
            if (!empty($main_ids['guru'])) {
                $ids[] = (int) $main_ids['guru'];
            }
            if (!empty($main_ids['tu'])) {
                $ids[] = (int) $main_ids['tu'];
            }
            return array_values(array_unique($ids));
        }
    }

    if (!function_exists('hak_akses_fetch_modul_rows')) {
        function hak_akses_fetch_modul_rows($connection, $sql)
        {
            $rows = [];
            $result = $connection->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $row['modul_id'] = (int) $row['modul_id'];
                    $rows[] = $row;
                }
            }
            return $rows;
        }
    }

    if (!function_exists('hak_akses_get_global_main_remaining_modules')) {
        function hak_akses_get_global_main_remaining_modules($connection)
        {
            $main_ids = hak_akses_get_main_filter_ids_for_tugas($connection);
            if (empty($main_ids)) {
                return hak_akses_fetch_modul_rows($connection, "SELECT modul_id, modul_nama FROM modul ORDER BY modul_nama ASC, modul_id ASC");
            }

            $in = implode(',', array_map('intval', $main_ids));
            $sql = "SELECT modul.modul_id, modul.modul_nama
                    FROM modul
                    WHERE modul.modul_id NOT IN (
                        SELECT role.modul_id FROM role WHERE role.level_id IN ($in)
                    )
                    ORDER BY modul.modul_nama ASC, modul.modul_id ASC";
            return hak_akses_fetch_modul_rows($connection, $sql);
        }
    }

    if (!function_exists('hak_akses_get_level_remaining_modules')) {
        function hak_akses_get_level_remaining_modules($connection, $levelRow, $levelId)
        {
            $levelId = (int) $levelId;
            if ($levelId <= 0 || empty($levelRow)) {
                return [];
            }

            if (hak_akses_is_operator_level($connection, $levelId)) {
                return [];
            }

            if ($levelRow['tipe'] === 'tugas') {
                $main_ids = hak_akses_get_main_filter_ids_for_tugas($connection);
                $sql = "SELECT modul.modul_id, modul.modul_nama
                        FROM modul
                        WHERE modul.modul_id NOT IN (
                            SELECT role.modul_id FROM role WHERE role.level_id = $levelId
                        )";

                if (!empty($main_ids)) {
                    $in = implode(',', array_map('intval', $main_ids));
                    $sql .= " AND modul.modul_id NOT IN (
                                SELECT role.modul_id FROM role WHERE role.level_id IN ($in)
                              )";
                }

                $sql .= " ORDER BY modul.modul_nama ASC, modul.modul_id ASC";
                return hak_akses_fetch_modul_rows($connection, $sql);
            }

            $sql = "SELECT modul.modul_id, modul.modul_nama
                    FROM modul
                    WHERE modul.modul_id NOT IN (
                        SELECT role.modul_id FROM role WHERE role.level_id = $levelId
                    )
                    ORDER BY modul.modul_nama ASC, modul.modul_id ASC";
            return hak_akses_fetch_modul_rows($connection, $sql);
        }
    }

    if (!function_exists('hak_akses_render_module_badges')) {
        function hak_akses_render_module_badges($modules, $emptyText)
        {
            if (empty($modules)) {
                return '<span class="text-muted">' . strip_tags($emptyText) . '</span>';
            }

            $html = '';
            foreach ($modules as $module) {
                $html .= '<span class="badge badge-light border mr-1 mb-1">' . strip_tags($module['modul_nama']) . '</span>';
            }
            return $html;
        }
    }

    if (!function_exists('hak_akses_get_filtered_roles')) {
        function hak_akses_get_filtered_roles($connection, $levelId)
        {
            $levelRow = hak_akses_get_level_row($connection, $levelId);
            if (!$levelRow) {
                return [null, []];
            }

            $levelId = (int) $levelId;
            if (hak_akses_is_operator_level($connection, $levelId)) {
                $sql = "SELECT
                            modul.modul_id,
                            modul.modul_nama,
                            COALESCE(role.role_id, 0) AS role_id,
                            'Y' AS lihat,
                            'Y' AS modifikasi,
                            'Y' AS hapus
                        FROM modul
                        LEFT JOIN role ON role.modul_id = modul.modul_id AND role.level_id = $levelId
                        ORDER BY modul.modul_nama ASC, modul.modul_id ASC";
            } else {
                $sql = "SELECT role.role_id, role.modul_id, role.lihat, role.modifikasi, role.hapus, modul.modul_nama
                        FROM role
                        LEFT JOIN modul ON role.modul_id = modul.modul_id
                        WHERE role.level_id = $levelId
                        ORDER BY modul.modul_nama ASC, modul.modul_id ASC";
            }

            $rows = [];
            $result = $connection->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $row['role_id'] = (int) $row['role_id'];
                    $row['modul_id'] = (int) $row['modul_id'];
                    $rows[] = $row;
                }
            }

            return [$levelRow, $rows];
        }
    }

    if (!function_exists('hak_akses_render_modul_options')) {
        function hak_akses_render_modul_options($connection, $levelId)
        {
            $levelId = (int) $levelId;
            $levelRow = hak_akses_get_level_row($connection, $levelId);
            if (!$levelRow || !hak_akses_is_configured_level($connection, $levelId)) {
                return '<option value="">-- Level tidak valid --</option>';
            }

            if (hak_akses_is_operator_level($connection, $levelId)) {
                return '<option value="">Operator Sekolah adalah superadmin</option>';
            }

            $remaining = hak_akses_get_level_remaining_modules($connection, $levelRow, $levelId);
            $options = '<option value="">-- Pilih modul --</option>';
            foreach ($remaining as $row) {
                $options .= '<option value="' . (int) $row['modul_id'] . '">' . strip_tags($row['modul_nama']) . '</option>';
            }

            if (count($remaining) === 0) {
                $options .= '<option value="">Tidak ada sisa modul</option>';
            }

            return $options;
        }
    }

    if (!function_exists('hak_akses_is_modul_used_on_main')) {
        function hak_akses_is_modul_used_on_main($connection, $modulId)
        {
            $modulId = (int) $modulId;
            if ($modulId <= 0) {
                return false;
            }

            $main_ids = hak_akses_get_main_filter_ids_for_tugas($connection);
            if (empty($main_ids)) {
                return false;
            }

            $in = implode(',', array_map('intval', $main_ids));
            $query = $connection->query("SELECT role_id FROM role WHERE modul_id = $modulId AND level_id IN ($in) LIMIT 1");
            return ($query && $query->num_rows > 0);
        }
    }

    if (!function_exists('hak_akses_get_role_level_id')) {
        function hak_akses_get_role_level_id($connection, $roleId)
        {
            $roleId = (int) $roleId;
            if ($roleId <= 0) {
                return 0;
            }

            $result = $connection->query("SELECT level_id FROM role WHERE role_id = $roleId LIMIT 1");
            if (!$result || $result->num_rows === 0) {
                return 0;
            }

            $row = $result->fetch_assoc();
            return (int) $row['level_id'];
        }
    }

    if (!function_exists('hak_akses_is_operator_role')) {
        function hak_akses_is_operator_role($connection, $roleId)
        {
            $levelId = hak_akses_get_role_level_id($connection, $roleId);
            if ($levelId <= 0) {
                return false;
            }
            return hak_akses_is_operator_level($connection, $levelId);
        }
    }

    $hak_action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

    switch ($hak_action) {
        case 'data':
            $id = (int) $_GET['id'];
            if ($id <= 0 || !hak_akses_is_configured_level($connection, $id)) {
                echo '<div class="alert alert-danger mb-0">Level tidak valid atau tidak termasuk konfigurasi hak akses.</div>';
                break;
            }

            list($level_row, $roles) = hak_akses_get_filtered_roles($connection, $id);
            if (!$level_row) {
                echo '<div class="alert alert-danger mb-0">Level tidak ditemukan.</div>';
                break;
            }

            $isOperator = hak_akses_is_operator_level($connection, $id);

            echo '<div class="hak-meta d-none" data-operator="' . ($isOperator ? '1' : '0') . '" data-level-name="' . strip_tags($level_row['level_nama']) . '"></div>';

            echo '
            <div class="table-responsive">
                <table class="table datatable table-inverse table-hover" style="vertical-align:middle">
                    <thead>
                        <tr>
                            <th>Modul</th>
                            <th class="text-center">Lihat</th>
                            <th class="text-center">Tambah/Edit</th>
                            <th class="text-center">Hapus</th>
                            <th class="text-center" width="60">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>';
            if (!empty($roles)) {
                foreach ($roles as $data_role) {
                    if ($isOperator) {
                        $lihat = '<span class="badge badge-success">Superadmin</span>';
                        $modifikasi = '<span class="badge badge-success">Superadmin</span>';
                        $hapus = '<span class="badge badge-success">Superadmin</span>';
                        $btn_delete = '<span class="text-muted">-</span>';
                    } else {
                        if ($data_role['lihat'] == 'Y') {
                            $lihat = '<label class="custom-toggle" style="display:inline-block">
                              <input type="checkbox" class="btn-active active' . $data_role['role_id'] . ' lihat" data-id="' . $data_role['role_id'] . '" data-active="N" checked>
                                  <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
                            </label>';
                        } else {
                            $lihat = '<label class="custom-toggle" style="display:inline-block">
                              <input type="checkbox" class="btn-active active' . $data_role['role_id'] . ' lihat" data-id="' . $data_role['role_id'] . '" data-active="Y">
                                  <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
                            </label>';
                        }

                        if ($data_role['modifikasi'] == 'Y') {
                            $modifikasi = '<label class="custom-toggle" style="display:inline-block">
                              <input type="checkbox" class="btn-active active' . $data_role['role_id'] . ' modifikasi" data-id="' . $data_role['role_id'] . '" data-active="N" checked>
                                  <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
                            </label>';
                        } else {
                            $modifikasi = '<label class="custom-toggle" style="display:inline-block">
                              <input type="checkbox" class="btn-active active' . $data_role['role_id'] . ' modifikasi" data-id="' . $data_role['role_id'] . '" data-active="Y">
                                  <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
                            </label>';
                        }

                        if ($data_role['hapus'] == 'Y') {
                            $hapus = '<label class="custom-toggle" style="display:inline-block">
                              <input type="checkbox" class="btn-active active' . $data_role['role_id'] . ' hapus" data-id="' . $data_role['role_id'] . '" data-active="N" checked>
                                  <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
                            </label>';
                        } else {
                            $hapus = '<label class="custom-toggle" style="display:inline-block">
                              <input type="checkbox" class="btn-active active' . $data_role['role_id'] . ' hapus" data-id="' . $data_role['role_id'] . '" data-active="Y">
                                  <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
                            </label>';
                        }

                        $btn_delete = '<button class="btn btn-sm btn-danger btn-delete-role" data-id="' . $data_role['role_id'] . '" title="Hapus role"><i class="fas fa-trash"></i></button>';
                    }

                    echo '
                    <tr>
                        <td class="text-info">' . strip_tags($data_role['modul_nama']) . '</td>
                        <td class="text-center">' . $lihat . '</td>
                        <td class="text-center">' . $modifikasi . '</td>
                        <td class="text-center">' . $hapus . '</td>
                        <td class="text-center">' . $btn_delete . '</td>
                    </tr>';
                }
            }
            echo '
                    </tbody>
                </table>
            </div>
            <script>
            $(".datatable").dataTable({
                "iDisplayLength": 35,
                "aaSorting": [[0, "asc"]],
                "aLengthMenu": [[35, 40, 50, -1], [35, 40, 50, "All"]],
                language: {
                    paginate: {
                        previous: "<",
                        next: ">"
                    }
                }
            });
            </script>';
            break;

        case 'module_options':
            $id = (int) $_GET['id'];
            echo hak_akses_render_modul_options($connection, $id);
            break;

        case 'add':
            $error = [];

            if (empty($_POST['level'])) {
                $error[] = 'Level tidak boleh kosong';
                $level = 0;
            } else {
                $level = (int) anti_injection($_POST['level']);
            }

            if (empty($_POST['modul_id'])) {
                $error[] = 'Modul/Menu tidak boleh kosong';
                $modul_id = 0;
            } else {
                $modul_id = (int) anti_injection($_POST['modul_id']);
            }

            if (!empty($error)) {
                foreach ($error as $values) {
                    echo $values . "\n";
                }
                break;
            }

            if (!hak_akses_is_configured_level($connection, $level)) {
                echo 'Level tidak termasuk konfigurasi hak akses tetap.';
                break;
            }

            if (hak_akses_is_operator_level($connection, $level)) {
                echo 'Operator Sekolah bersifat superadmin dan tidak perlu ditambah manual.';
                break;
            }

            $levelRow = hak_akses_get_level_row($connection, $level);
            if (!$levelRow) {
                echo 'Level tidak ditemukan';
                break;
            }

            $cek_modul = $connection->query("SELECT modul_id FROM modul WHERE modul_id = $modul_id LIMIT 1");
            if (!$cek_modul || $cek_modul->num_rows === 0) {
                echo 'Modul tidak ditemukan.';
                break;
            }

            if ($levelRow['tipe'] === 'tugas' && hak_akses_is_modul_used_on_main($connection, $modul_id)) {
                echo 'Modul/menu ini sudah dipakai oleh level utama Guru/Tenaga Administrasi.';
                break;
            }

            $query = $connection->query("SELECT role_id FROM role WHERE level_id = $level AND modul_id = $modul_id");
            if ($query && $query->num_rows > 0) {
                echo 'Sepertinya Modul/menu ini sudah ada!';
                break;
            }

            $add = "INSERT INTO role(level_id, modul_id, lihat, modifikasi, hapus)
                    VALUES($level, $modul_id, 'N', 'N', 'N')";
            if ($connection->query($add) === false) {
                echo 'Data tidak berhasil disimpan!';
            } else {
                echo 'success';
            }
            break;

        case 'lihat':
        case 'modifikasi':
        case 'hapus':
            $action = $_GET['action'];
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            $active = (isset($_POST['active']) && strtoupper($_POST['active']) === 'Y') ? 'Y' : 'N';

            if ($id <= 0) {
                echo 'error';
                break;
            }

            if (hak_akses_is_operator_role($connection, $id)) {
                echo 'Akses Operator Sekolah bersifat superadmin dan tidak dapat diubah.';
                break;
            }

            $update = "UPDATE role SET $action = '$active' WHERE role_id = $id";
            if ($connection->query($update) === false) {
                echo 'error';
            } else {
                echo 'success';
            }
            break;

        case 'delete_role':
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($id <= 0) {
                echo 'error';
                break;
            }

            if (hak_akses_is_operator_role($connection, $id)) {
                echo 'Akses Operator Sekolah bersifat superadmin dan tidak dapat dihapus.';
                break;
            }

            $delete = "DELETE FROM role WHERE role_id = $id";
            if ($connection->query($delete) === false) {
                echo 'error';
            } else {
                echo 'success';
            }
            break;

        case 'sync_modules':
            header('Content-Type: application/json');

            $operatorLevelId = hak_akses_get_level_id_by_name($connection, 'Operator Sekolah');
            if ($operatorLevelId <= 0) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Level Operator Sekolah tidak ditemukan.'
                ]);
                break;
            }

            $ignored = array('.', '..', 'check_role.php', 'footer.php', 'header.php', 'sidebar.php');
            $modDir = realpath(__DIR__ . '/..');
            $folders = ($modDir && is_dir($modDir)) ? scandir($modDir) : [];
            $routes = [];

            foreach ($folders as $folder) {
                if (in_array($folder, $ignored, true)) continue;
                $path = $modDir . DIRECTORY_SEPARATOR . $folder;
                if (!is_dir($path)) continue;

                // ponytail: modul = semua folder admin/mod; upgrade if non-route folders appear here.
                $routes[] = $folder;
            }
            sort($routes, SORT_NATURAL | SORT_FLAG_CASE);

            $hasRouteColumn = false;
            $qColumn = $connection->query("SHOW COLUMNS FROM modul LIKE 'modul_route'");
            if ($qColumn && $qColumn->num_rows > 0) {
                $hasRouteColumn = true;
            } else {
                $connection->query("ALTER TABLE modul ADD COLUMN modul_route varchar(100) NOT NULL DEFAULT '' AFTER modul_id");
                $hasRouteColumn = true;
            }

            $oldCount = 0;
            $oldRoles = [];
            $qOld = $connection->query("SELECT COUNT(*) AS total FROM modul");
            if ($qOld && $rowOld = $qOld->fetch_assoc()) $oldCount = (int) $rowOld['total'];

            $qRoles = $connection->query("SELECT r.level_id, m.modul_route, m.modul_nama, r.lihat, r.modifikasi, r.hapus FROM role r INNER JOIN modul m ON m.modul_id = r.modul_id");
            if ($qRoles) {
                while ($row = $qRoles->fetch_assoc()) {
                    $routeKey = strtolower(trim($row['modul_route']));
                    $nameKey = strtolower(trim($row['modul_nama']));
                    $role = [
                        'level_id' => (int) $row['level_id'],
                        'lihat' => strtoupper($row['lihat']) === 'Y' ? 'Y' : 'N',
                        'modifikasi' => strtoupper($row['modifikasi']) === 'Y' ? 'Y' : 'N',
                        'hapus' => strtoupper($row['hapus']) === 'Y' ? 'Y' : 'N'
                    ];
                    if ($routeKey !== '') $oldRoles['route:' . $routeKey][] = $role;
                    if ($nameKey !== '') $oldRoles['name:' . $nameKey][] = $role;
                }
            }

            $connection->begin_transaction();
            try {
                $connection->query("DELETE FROM role");
                $connection->query("DELETE FROM modul");

                $insertedModules = 0;
                $restoredRoles = 0;
                $operatorRoles = 0;
                $moduleId = 1;
                $stModule = $connection->prepare("INSERT INTO modul (modul_id, modul_route, modul_nama) VALUES (?, ?, ?)");
                $stRole = $connection->prepare("INSERT INTO role (level_id, modul_id, lihat, modifikasi, hapus) VALUES (?, ?, ?, ?, ?)");

                foreach ($routes as $route) {
                    $name = ucwords(str_replace('-', ' ', $route));
                    $routeKey = 'route:' . strtolower(trim($route));
                    $nameKey = 'name:' . strtolower(trim($name));

                    if ($stModule) {
                        $stModule->bind_param('iss', $moduleId, $route, $name);
                        $stModule->execute();
                        if ($stModule->affected_rows > 0) $insertedModules++;
                    }

                    $seenLevels = [];
                    $rolesToRestore = !empty($oldRoles[$routeKey]) ? $oldRoles[$routeKey] : (!empty($oldRoles[$nameKey]) ? $oldRoles[$nameKey] : []);
                    if (!empty($rolesToRestore) && $stRole) {
                        foreach ($rolesToRestore as $role) {
                            $levelId = (int) $role['level_id'];
                            $lihat = $role['lihat'];
                            $modifikasi = $role['modifikasi'];
                            $hapus = $role['hapus'];
                            if ($levelId === $operatorLevelId) {
                                $lihat = $modifikasi = $hapus = 'Y';
                            }
                            $stRole->bind_param('iisss', $levelId, $moduleId, $lihat, $modifikasi, $hapus);
                            $stRole->execute();
                            if ($stRole->affected_rows > 0) $restoredRoles++;
                            $seenLevels[$levelId] = true;
                        }
                    }

                    if (empty($seenLevels[$operatorLevelId]) && $stRole) {
                        $lihat = $modifikasi = $hapus = 'Y';
                        $stRole->bind_param('iisss', $operatorLevelId, $moduleId, $lihat, $modifikasi, $hapus);
                        $stRole->execute();
                        if ($stRole->affected_rows > 0) $operatorRoles++;
                    }

                    $moduleId++;
                }

                if ($stModule) $stModule->close();
                if ($stRole) $stRole->close();
                $connection->commit();
            } catch (Exception $e) {
                $connection->rollback();
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Sinkronisasi gagal: ' . $e->getMessage()
                ]);
                break;
            }

            $deletedModules = max(0, $oldCount - $insertedModules);
            echo json_encode([
                'status' => 'success',
                'message' => 'Sinkronisasi selesai. Total modul: ' . count($routes) . '. Ditambahkan/diperbarui: ' . $insertedModules . '. Dihapus: ' . $deletedModules . '. Hak akses lama tetap disimpan. Operator Sekolah otomatis dapat semua akses.'
            ]);
            break;

        default:
            echo 'Aksi tidak dikenali.';
            break;
    }
}
