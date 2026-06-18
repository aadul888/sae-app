<?php
session_start();
require_once '../../../library/config.php';
require_once '../../../library/function.php';

function render_alert_page($title, $message, $type = 'danger', $buttonsHtml = '')
{
    $iconMap = ['danger' => 'ban', 'warning' => 'exclamation-circle', 'success' => 'check-circle', 'info' => 'info-circle', 'primary' => 'info-circle'];
    $icon = isset($iconMap[$type]) ? $iconMap[$type] : 'ban';
    $borderClass = 'border-' . $type;
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"><title>' . htmlspecialchars($title) . '</title>';
    echo '<link rel="icon" href="../../../content/favicon.png" type="image/png">';
    echo '<link rel="stylesheet" href="../../assets/css/bootstrap/bootstrap.min.css">';
    echo '<link rel="stylesheet" href="../../assets/css/style.css">';
    echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">';
    echo '</head><body class="approve-eizin-bg">';
    echo '<div class="container"><div class="row justify-content-center"><div class="col-lg-8 col-md-10 col-sm-12">';
    echo '<div class="card approve-eizin-card ' . $borderClass . '"><div class="card-body text-center">';
    echo '<i class="fas fa-' . $icon . ' text-' . htmlspecialchars($type) . ' mb-3" style="font-size:3rem"></i>';
    echo '<h4 class="text-' . htmlspecialchars($type) . ' mb-3">' . htmlspecialchars($title) . '</h4>';
    echo '<p class="mb-3">' . htmlspecialchars($message) . '</p>';
    if (!empty($buttonsHtml)) {
        echo '<div class="mt-3 approve-actions d-flex flex-column flex-md-row justify-content-center">' . $buttonsHtml . '</div>';
    }
    echo '</div></div></div></div></div></body></html>';
}

function exit_with_alert($title, $message, $type = 'danger', $showLogin = false, $showBack = true)
{
    $buttons = '';
    if ($showLogin) $buttons .= '<a href="../../login/" class="btn btn-primary"><i class="fas fa-sign-in-alt mr-2"></i>Login</a>';
    if ($showBack) $buttons .= '<a href="../../" class="btn btn-primary"><i class="fas fa-arrow-left mr-2"></i>Kembali ke Dashboard</a>';
    render_alert_page($title, $message, $type, $buttons);
    exit;
}

function fetch_admin_info($connection)
{
    $info = ['admin_id' => 0, 'tugas_arr' => [], 'ptk_id' => ''];
    if (!isset($_COOKIE['ADMIN_KEY'])) return $info;
    $admin_id = (int) epm_decode($_COOKIE['ADMIN_KEY']);
    if ($admin_id <= 0) return $info;
    $stmt = $connection->prepare("SELECT tugas_tambahan, ptk_id, level_id FROM admin WHERE admin_id = ? LIMIT 1");
    if (!$stmt) return $info;
    $stmt->bind_param('i', $admin_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $r = $res->fetch_assoc();
        $tugas = $r['tugas_tambahan'] ?? '';
        $info['ptk_id'] = $r['ptk_id'] ?? '';
        $info['level_id'] = isset($r['level_id']) ? $r['level_id'] : '';
        $info['admin_id'] = $admin_id;
        if ($tugas === '') {
            $info['tugas_arr'] = [];
        } else {
            $parts = array_map('trim', explode(',', $tugas));
            // Keep only non-empty numeric values, normalized as strings of digits
            $parts = array_filter($parts, function ($v) {
                return $v !== '' && preg_match('/^\d+$/', $v);
            });
            $info['tugas_arr'] = array_values($parts);
        }
    }
    $stmt->close();
    return $info;
}

function resolve_short_token($connection, $t, $explicit_role = '')
{
    $out = ['id' => 0, 'requested_role' => '', 'expires' => '', 'used' => '', 'token_action' => ''];
    if ($t === '') return $out;
    $stmt = $connection->prepare("SELECT id, token_admin, token_security, token_wali FROM e_izin WHERE token_admin = ? OR token_security = ? OR token_wali = ? LIMIT 1");
    if (!$stmt) return $out;
    $stmt->bind_param('sss', $t, $t, $t);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || $res->num_rows === 0) {
        $stmt->close();
        return $out;
    }
    $r = $res->fetch_assoc();
    $stmt->close();
    $out['id'] = (int)($r['id'] ?? 0);
    $matches_admin = (!empty($r['token_admin']) && $r['token_admin'] === $t);
    $matches_security = (!empty($r['token_security']) && $r['token_security'] === $t);
    $matches_wali = (!empty($r['token_wali']) && $r['token_wali'] === $t);
    $role = '';
    if ($matches_admin) $role = 'admin';
    elseif ($matches_security) $role = 'security';
    elseif ($matches_wali) $role = 'wali';
    if ($role === 'admin' && $explicit_role === 'wali') $role = 'wali';
    if ($role === 'wali' && $explicit_role === 'admin') $role = 'admin';
    $out['requested_role'] = $role;
    $out['token_action'] = '';
    return $out;
}

function check_session_permission($connection, $level_id, $tugas_arr, $requested_role)
{
    $level_int = intval($level_id);

    // normalize tugas_arr to array of digit-strings for reliable comparisons
    if (!is_array($tugas_arr)) $tugas_arr = [];
    $norm = array_values(array_filter(array_map('strval', $tugas_arr), function ($v) {
        return $v !== '' && preg_match('/^\d+$/', $v);
    }));

    $has_tugas = function ($val) use ($norm) {
        // compare as string for consistency (tugas_arr stored as strings)
        return in_array((string)$val, $norm, true);
    };

    // Allowed combinations:
    // - admin/petugas => level 2 + tugas_tambahan 6
    // - admin/security => level 2 + tugas_tambahan 7
    // - guru/wali kelas => level 3 + tugas_tambahan 4
    $isAdminPetugas = ($level_int === 2) && $has_tugas(6);
    $isAdminSecurity = ($level_int === 2) && $has_tugas(7);
    $isWali = ($level_int === 3) && $has_tugas(4);

    if ($requested_role === 'admin') return $isAdminPetugas;
    if ($requested_role === 'security') return $isAdminSecurity;
    if ($requested_role === 'wali' || $requested_role === 'guru') return $isWali;

    // If no explicit requested role, allow only if session matches one of the allowed combos
    return ($isAdminPetugas || $isAdminSecurity || $isWali);
}

function ensure_wali_matches($connection, $admin_id, $tugas_arr, $kelas_wali_ptk)
{
    if (empty($kelas_wali_ptk)) return false;
    if (!empty($admin_id)) {
        $stmt = $connection->prepare("SELECT ptk_id FROM admin WHERE admin_id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $admin_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $r = $res->fetch_assoc();
                $adm_ptk = $r['ptk_id'] ?? '';
                $stmt->close();
                if ($adm_ptk !== '') return ($adm_ptk === $kelas_wali_ptk);
            }
            $stmt->close();
        }
    }
    return (in_array('4', $tugas_arr) || in_array(4, $tugas_arr));
}

function mark_token_used($connection, $t, $requested_role, $token_action = '')
{
    if (empty($t) || empty($requested_role)) return;
    if ($requested_role === 'admin') {
        $up = $connection->prepare("UPDATE e_izin SET token_admin = NULL WHERE token_admin = ? LIMIT 1");
        if ($up) {
            $up->bind_param('s', $t);
            $up->execute();
            $up->close();
        }
        return;
    }
    if ($requested_role === 'wali') {
        $up = $connection->prepare("UPDATE e_izin SET token_wali = NULL WHERE token_wali = ? LIMIT 1");
        if ($up) {
            $up->bind_param('s', $t);
            $up->execute();
            $up->close();
        }
        return;
    }
    $up = $connection->prepare("UPDATE e_izin SET token_security = NULL WHERE token_security = ? LIMIT 1");
    if ($up) {
        $up->bind_param('s', $t);
        $up->execute();
        $up->close();
    }
}

$level_id = isset($_COOKIE['level_id']) ? $_COOKIE['level_id'] : '';
$admin_info = fetch_admin_info($connection);
$tugas_arr = $admin_info['tugas_arr'];
$admin_id = $admin_info['admin_id'];
$level_int = intval($level_id);

// If DB returned a level_id for this admin, prefer it over cookie value (prevents cookie tampering)
if (!empty($admin_info['level_id'])) {
    $level_id = $admin_info['level_id'];
    $level_int = intval($level_id);
}

// Require login (ADMIN_KEY) for approval access. Token-only flows are NOT allowed
// for security confirmation — security staff must be logged in (level 2 + tugas 7).
if (empty($admin_id) || $admin_id <= 0) {
    exit_with_alert('Akses Ditolak', 'Anda harus login untuk mengakses halaman approval e-izin.', 'danger', true, true);
}

$id = 0;
$token = '';
$requested_role = '';
$t = '';
$token_flow = false;
if (!empty($_GET['t'])) {
    $t = preg_replace('/[^a-z0-9]/i', '', $_GET['t']);
    if ($t === '') exit_with_alert('Token Tidak Valid', 'Token pendek tidak valid.', 'danger', false, true);
    $explicit_role = isset($_GET['role']) ? trim(strtolower(preg_replace('/[^a-z0-9_-]/i', '', $_GET['role']))) : '';
    $tok = resolve_short_token($connection, $t, $explicit_role);
    if ($tok['id'] <= 0 || $tok['requested_role'] === '') exit_with_alert('Token Tidak Ditemukan', 'Token verifikasi tidak ditemukan atau sudah digunakan.', 'danger', false, true);
    $id = $tok['id'];
    $requested_role = $tok['requested_role'];
    $token_action = $tok['token_action'] ?? '';
    $expires = $tok['expires'];
    $used = $tok['used'];
    $token_flow = true;
    if (empty($token_action) && !empty($_GET['action'])) {
        $token_action = strtolower(preg_replace('/[^a-z]/i', '', $_GET['action']));
    }
    if (!empty($used)) exit_with_alert('Token Tidak Valid', 'Token sudah digunakan.', 'danger', false, true);
    if (!empty($expires) && strtotime($expires) < time()) exit_with_alert('Token Kadaluarsa', 'Token verifikasi sudah kedaluwarsa.', 'danger', false, true);
} else {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $token = $_GET['token'] ?? '';
}

// Consider explicit GET role as valid intent (so URLs like ?id=123&role=security are accepted)
$has_role_param = isset($_GET['role']) && trim($_GET['role']) !== '';
if ($id <= 0 || (empty($token) && empty($requested_role) && empty($_GET['t']) && !$has_role_param)) exit_with_alert('Data Tidak Valid', 'Permintaan tidak lengkap atau data yang dikirim tidak valid.', 'danger', false, true);

$stmt = $connection->prepare("SELECT e_izin.id, e_izin.user_id, e_izin.jenis_izin, e_izin.tanggal, e_izin.keterangan, e_izin.status_izin, e_izin.status_izin_wali, e_izin.alasan_penolakan, e_izin.alasan_penolakan_wali, e_izin.konfirmasi, e_izin.token_admin, e_izin.token_security, e_izin.token_wali, user.nama_lengkap, user.nisn, user.kelas AS kelas_id, kelas.wali_kelas_ptk_id, kelas.nama_kelas FROM e_izin LEFT JOIN user ON e_izin.user_id = user.user_id LEFT JOIN kelas ON user.kelas = kelas.kelas_id WHERE e_izin.id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if (!($res && $res->num_rows > 0)) {
    // Provide minimal debug info when requested so it's easier to see which id was checked
    if (!empty($_GET['debug']) && intval($_GET['debug']) === 1) {
        $dbg = 'Requested id=' . intval($id) . ', query checked e_izin table.';
        exit_with_alert('Data Izin Tidak Ditemukan', 'Data permohonan izin yang diminta tidak ditemukan atau telah dihapus.\n' . $dbg, 'danger', false, true);
    }
    exit_with_alert('Data Izin Tidak Ditemukan', 'Data permohonan izin yang diminta tidak ditemukan atau telah dihapus.', 'danger', false, true);
}
$row = $res->fetch_assoc();

if (empty($t)) {
    // If there is no short token, only require the long token when the user
    // is NOT logged in. If the admin is logged in (session present), allow
    // access without a matching token so session-based approvals work.
    if (empty($admin_id) || $admin_id <= 0) {
        $expected_token = md5($row['id'] . '|' . $row['tanggal'] . '|' . $row['nama_lengkap'] . '|APPROVE2025');
        if ($token !== $expected_token) exit_with_alert('Token Tidak Valid', 'Token verifikasi tidak sesuai atau sudah kedaluwarsa.', 'danger', false, true);
        $token_flow = true;
    } else {
        // logged-in session: not a token flow
        $token_flow = false;
    }
}

if ($token_flow && !empty($level_id) && $requested_role !== 'security') {
    if (!check_session_permission($connection, $level_id, $tugas_arr, $requested_role)) {
        // Session exists but user lacks the requested role — don't prompt for login.
        exit_with_alert('Akses Ditolak', 'Anda tidak memiliki akses untuk melakukan approval dengan peran ini.', 'danger', false, true);
    }
    if ($requested_role === 'wali') {
        $kelas_wali_ptk = $row['wali_kelas_ptk_id'] ?? '';
        if (!ensure_wali_matches($connection, $admin_id, $tugas_arr, $kelas_wali_ptk)) exit_with_alert('Akses Ditolak', 'Anda bukan wali kelas dari siswa ini.', 'danger', true, true);
    }
}

$izin_date = DateTime::createFromFormat('Y-m-d', $row['tanggal']);
$today_check = new DateTime();
$today_check->setTime(0, 0, 0);
if ($izin_date) {
    $izin_date->setTime(0, 0, 0);
    if (!($token_flow && $requested_role === 'security')) {
        if ($today_check > $izin_date) exit_with_alert('Link Tidak Berlaku', 'Tanggal izin sudah lewat; permintaan ini kadaluarsa.', 'danger', false, true);
        if ($today_check < $izin_date) exit_with_alert('Link Tidak Berlaku', 'Izin ini belum berlaku (ditujukan untuk tanggal ' . htmlspecialchars($row['tanggal']) . ').', 'warning', false, true);
    }
}

if (isset($_GET['card']) && intval($_GET['card']) === 1 && empty($requested_role) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $script = $_SERVER['SCRIPT_NAME'];
    $use_short = !empty($t);
    $baseParams = [];
    if ($use_short) {
        $baseParams['t'] = $t;
    } else {
        $baseParams['id'] = $id;
        $baseParams['token'] = $token ?? '';
    }

    $build = function ($extra) use ($script, $baseParams) {
        $params = $baseParams + $extra;
        return $script . '?' . http_build_query($params);
    };

    $linkAdmin = $build(['role' => 'admin']);
    $linkWali = $build(['role' => 'wali']);
    $linkSecExit = $build(['role' => 'security', 'action' => 'exit']);
    $linkSecReturn = $build(['role' => 'security', 'action' => 'return']);

    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pilih Peran Persetujuan</title>';
    echo '<link rel="stylesheet" href="../../assets/css/bootstrap/bootstrap.min.css">';
    echo '<link rel="stylesheet" href="../../assets/css/style.css">';
    echo '<style>.role-grid{display:flex;gap:12px;flex-wrap:wrap;}.role-card{flex:1;min-width:180px}</style>';
    echo '</head><body class="approve-eizin-bg"><div class="container"><div class="row justify-content-center"><div class="col-lg-8 col-md-10">';
    echo '<div class="card approve-eizin-card"><div class="card-body text-center">';
    echo '<h4 class="mb-3">Pilih Peran untuk Persetujuan</h4>';
    echo '<p class="mb-2"><strong>' . htmlspecialchars($row['nama_lengkap']) . '</strong> — ' . htmlspecialchars($row['nama_kelas'] ?? '') . '</p>';
    echo '<p class="text-muted small">Pilih peran yang sesuai. Setelah memilih, Anda akan diarahkan ke halaman persetujuan dengan hak akses yang cocok.</p>';
    echo '<div class="role-grid mt-3">';
    echo '<div class="card role-card"><div class="card-body"><h5>Admin</h5><p class="small text-muted">Untuk petugas/admin.</p><a class="btn btn-success" href="' . htmlspecialchars($linkAdmin) . '">Pilih Admin</a></div></div>';
    echo '<div class="card role-card"><div class="card-body"><h5>Wali Kelas</h5><p class="small text-muted">Untuk wali kelas siswa.</p><a class="btn btn-primary" href="' . htmlspecialchars($linkWali) . '">Pilih Wali</a></div></div>';
    echo '<div class="card role-card"><div class="card-body"><h5>Keamanan</h5><p class="small text-muted">Untuk verifikasi keluar/kembali.</p><a class="btn btn-secondary mb-2 d-block" href="' . htmlspecialchars($linkSecExit) . '">Keluar</a><a class="btn btn-outline-secondary d-block" href="' . htmlspecialchars($linkSecReturn) . '">Kembali</a></div></div>';
    echo '</div>';
    echo '<div class="mt-4"><a href="../../" class="btn btn-link">Batal</a></div>';
    echo '</div></div></div></div></div></body></html>';
    exit;
}

if (empty($requested_role)) $requested_role = isset($_GET['role']) ? trim(strtolower(preg_replace('/[^a-z0-9_-]/i', '', $_GET['role']))) : '';

if (empty($token_flow) && !check_session_permission($connection, $level_id, $tugas_arr, $requested_role)) {
    // Session-based denial: user is authenticated but unauthorized — offer back to dashboard, not login.
    exit_with_alert('Akses Ditolak', 'Anda tidak memiliki akses untuk melakukan approval e-izin.', 'danger', false, true);
}

if (empty($token_flow) && check_session_permission($connection, $level_id, $tugas_arr, 'wali')) {
    $kelas_wali_ptk = $row['wali_kelas_ptk_id'] ?? '';
    if (!ensure_wali_matches($connection, $admin_id, $tugas_arr, $kelas_wali_ptk)) exit_with_alert('Akses Ditolak', 'Anda bukan wali kelas dari siswa ini.', 'danger', false, true);
}

$cek = $connection->query("SELECT status_izin, status_izin_wali, alasan_penolakan, alasan_penolakan_wali, konfirmasi, token_admin, token_security, token_wali FROM e_izin WHERE id='" . $connection->real_escape_string($id) . "'");
$row_status = ($cek && $cek->num_rows > 0) ? $cek->fetch_assoc() : [];
$current_status = $row_status['status_izin'] ?? '';
$current_status_wali = $row_status['status_izin_wali'] ?? '';
$current_konfirmasi = $row_status['konfirmasi'] ?? '';
$current_token_security = $row_status['token_security'] ?? '';

// Strict security access rules: only allow when the current session belongs to
// a logged-in security officer (level 2 + tugas 7). Token-only access is NOT allowed.
if ($requested_role === 'security') {
    $has_session_security = (!empty($admin_id) && check_session_permission($connection, $level_id, $tugas_arr, 'security'));
    if (!$has_session_security) {
        // If developer/debug mode requested, show diagnostic info to help troubleshooting.
        if (!empty($_GET['debug']) && intval($_GET['debug']) === 1) {
            $dbg = 'Session admin_id=' . intval($admin_id) . ', level_id=' . htmlspecialchars($level_id) . ', tugas=' . htmlspecialchars(implode(',', $tugas_arr));
            exit_with_alert('Akses Ditolak', 'Akses untuk peran keamanan hanya diperbolehkan bagi petugas keamanan.\n' . $dbg, 'danger', false, true);
        }
        exit_with_alert('Akses Ditolak', 'Akses untuk peran keamanan hanya diperbolehkan bagi petugas keamanan', 'danger', false, true);
    }
    if (strtolower(trim($current_konfirmasi ?? '')) === 'kembali') {
        exit_with_alert('Link Tidak Berlaku', 'Permintaan ini sudah diproses (sudah kembali).', 'danger', false, true);
    }
}

if (!empty($_GET['debug']) && intval($_GET['debug']) === 1 && !empty($token_flow)) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<h3>e-izin Debug Info</h3>';
    echo '<ul>';
    echo '<li>t token: ' . htmlspecialchars($t) . '</li>';
    echo '<li>requested_role: ' . htmlspecialchars($requested_role) . '</li>';
    echo '<li>token_action: ' . htmlspecialchars($token_action ?? '') . '</li>';
    echo '<li>konfirmasi_req (from GET/POST): ' . htmlspecialchars($konfirmasi_req ?? '') . '</li>';
    echo '<li>current_status: ' . htmlspecialchars($current_status) . '</li>';
    echo '<li>current_status_wali: ' . htmlspecialchars($current_status_wali) . '</li>';
    echo '<li>current_konfirmasi: ' . htmlspecialchars($current_konfirmasi) . '</li>';
    echo '<li>current_token_security: ' . htmlspecialchars($current_token_security) . '</li>';
    echo '</ul>';
    echo '<pre>';
    print_r($row);
    echo '</pre>';
    echo '<pre>';
    print_r($row_status);
    echo '</pre>';
    exit;
}

$current_catatan = $row_status['alasan_penolakan'] ?? '';
$current_catatan_wali = $row_status['alasan_penolakan_wali'] ?? '';

$cs = strtolower(trim($current_status));
$cw = strtolower(trim($current_status_wali));
$allow_return_via_token = false;
$security_flow_allowed = false;
if ($requested_role === 'security') {
    $has_session_security = (!empty($level_id) && check_session_permission($connection, $level_id, $tugas_arr, 'security'));
    if ($has_session_security) {
        if (strtolower(trim($current_konfirmasi ?? '')) !== 'kembali') {
            $security_flow_allowed = true;
        }
    }
}

// Token-only flows are not allowed for security; only session-based security is permitted.

if ($cs !== 'menunggu' && $cw !== 'menunggu' && !$allow_return_via_token && !$security_flow_allowed) exit_with_alert('Link Tidak Berlaku', 'Permintaan ini sudah diproses (disetujui atau ditolak). Jika Anda ingin membuat izin baru, silakan ajukan permohonan e-izin lagi.', 'danger', false, true);

$konfirmasi_req = '';
if (!empty($_POST['konfirmasi'])) {
    $k = strtolower(trim($_POST['konfirmasi']));
    if (in_array($k, ['keluar', 'kembali', 'pulang'], true)) $konfirmasi_req = $k;
} elseif (!empty($_GET['konfirmasi'])) {
    $k = strtolower(trim($_GET['konfirmasi']));
    if (in_array($k, ['keluar', 'kembali', 'pulang'], true)) $konfirmasi_req = $k;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status_izin'])) {
    $new_status = $_POST['status_izin'];
    $catatan = trim($_POST['alasan_penolakan'] ?? '');
    if ($new_status === 'Ditolak' && $catatan === '') {
        $error = 'Catatan penolakan wajib diisi jika menolak.';
    } else {
        $requested_role_post = !empty($requested_role) ? $requested_role : (isset($_GET['role']) ? trim(strtolower($_GET['role'])) : '');
        $note_to_save = $catatan;
        $ok = false;

        // Determine whether this update targets wali-status or admin-status
        $is_wali_action = ($requested_role_post === 'wali') || (isset($_POST['target']) && $_POST['target'] === 'wali');

        if (!empty($token_flow)) {
            // Token flows should carry explicit role intent (resolve_short_token sets requested_role)
            if ($is_wali_action) {
                $stmt_up = $connection->prepare("UPDATE e_izin SET status_izin_wali = ?, alasan_penolakan_wali = ? WHERE id = ?");
                if ($stmt_up) {
                    $stmt_up->bind_param('ssi', $new_status, $note_to_save, $id);
                    $ok = $stmt_up->execute();
                    $stmt_up->close();
                }
            } else {
                $stmt_up = $connection->prepare("UPDATE e_izin SET status_izin = ?, alasan_penolakan = ? WHERE id = ?");
                if ($stmt_up) {
                    $stmt_up->bind_param('ssi', $new_status, $note_to_save, $id);
                    $ok = $stmt_up->execute();
                    $stmt_up->close();
                }
            }
        } else {
            // For session-based requests, require explicit role match for the specific action.
            if ($is_wali_action) {
                if (!check_session_permission($connection, $level_id, $tugas_arr, 'wali')) {
                    $error = 'Anda tidak memiliki hak wali untuk melakukan approval ini.';
                    $ok = false;
                } else {
                    $stmt_up = $connection->prepare("UPDATE e_izin SET status_izin_wali = ?, alasan_penolakan_wali = ? WHERE id = ?");
                    if ($stmt_up) {
                        $stmt_up->bind_param('ssi', $new_status, $note_to_save, $id);
                        $ok = $stmt_up->execute();
                        $stmt_up->close();
                    }
                }
            } else {
                // admin approval must be performed by admin/petugas (level 2 + tugas 6)
                if (!check_session_permission($connection, $level_id, $tugas_arr, 'admin')) {
                    $error = 'Anda tidak memiliki hak admin untuk melakukan approval ini.';
                    $ok = false;
                } else {
                    $stmt_up = $connection->prepare("UPDATE e_izin SET status_izin = ?, alasan_penolakan = ? WHERE id = ?");
                    if ($stmt_up) {
                        $stmt_up->bind_param('ssi', $new_status, $note_to_save, $id);
                        $ok = $stmt_up->execute();
                        $stmt_up->close();
                    }
                }
            }
        }
        if ($ok) {
            try {
                $should_apply_konfirmasi = false;
                $konfirmasi_to_write = '';
                $effective_role = $requested_role_post;
                if (empty($effective_role)) $effective_role = $requested_role;
                if ($effective_role === 'admin' || $effective_role === 'security') {
                    if (!empty($konfirmasi_req)) {
                        $should_apply_konfirmasi = true;
                        $konfirmasi_to_write = $konfirmasi_req;
                    } else {
                        if (!empty($token_action)) {
                            if ($token_action === 'exit') {
                                $should_apply_konfirmasi = true;
                                $konfirmasi_to_write = 'keluar';
                            } elseif ($token_action === 'return') {
                                $should_apply_konfirmasi = true;
                                $konfirmasi_to_write = 'kembali';
                            }
                        } else {
                            if (strtolower(trim($row['jenis_izin'] ?? '')) === 'pulang') {
                                // Only allow security role to auto-write 'pulang' confirmation.
                                if ($effective_role === 'security') {
                                    $should_apply_konfirmasi = true;
                                    $konfirmasi_to_write = 'pulang';
                                }
                            }
                        }
                    }
                }
                if ($should_apply_konfirmasi && in_array($konfirmasi_to_write, ['keluar', 'kembali', 'pulang'], true)) {
                    $u = $connection->prepare("UPDATE e_izin SET konfirmasi = ? WHERE id = ? LIMIT 1");
                    if ($u) {
                        $u->bind_param('si', $konfirmasi_to_write, $id);
                        $u->execute();
                        $u->close();
                    }
                }
                if (!empty($konfirmasi_req)) {
                    $is_security_actor = check_session_permission($connection, $level_id, $tugas_arr, 'security');
                    if ($is_security_actor && in_array($konfirmasi_req, ['keluar', 'kembali', 'pulang'], true)) {
                        $applyU = $connection->prepare("UPDATE e_izin SET konfirmasi = ? WHERE id = ? LIMIT 1");
                        if ($applyU) {
                            $applyU->bind_param('si', $konfirmasi_req, $id);
                            $applyU->execute();
                            $applyU->close();
                        }
                        if ($konfirmasi_req === 'keluar') {
                            try {
                                $newTok2 = bin2hex(random_bytes(4));
                                $up2 = $connection->prepare("UPDATE e_izin SET token_security = ? WHERE id = ? LIMIT 1");
                                if ($up2) {
                                    $up2->bind_param('si', $newTok2, $id);
                                    $up2->execute();
                                    $up2->close();
                                }
                                $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
                                $host = $_SERVER['HTTP_HOST'];
                                $script_name = $_SERVER['SCRIPT_NAME'];
                                $saev4_root = '/' . explode('/', trim($script_name, '/'))[0];
                                $return_link2 = rtrim($protocol . '://' . $host . $saev4_root, '/') . '/admin/mod/e-izin/approve.php?t=' . $newTok2 . '&role=security&action=return';
                                $qr_img2 = rtrim($protocol . '://' . $host . $saev4_root, '/') . '/dashboard/mod/e-izin/qrcode.php?id=' . intval($id) . '&role=security&action=return';
                                echo '<div style="max-width:640px;margin:12px auto;padding:12px;border:1px solid #e9ecef;background:#fff;border-radius:8px;text-align:center">';
                                echo '<h5>Token Kembali Dibuat</h5>';
                                echo '<p>Token untuk proses <strong>kembali</strong> telah dibuat. Berikan QR atau link ini kepada siswa saat kembali.</p>';
                                echo '<div style="display:inline-block;padding:8px;border-radius:8px;background:#fafafa"><img src="' . htmlspecialchars($qr_img2) . '" style="max-width:220px;display:block;margin:0 auto;" alt="QR Return"/></div>';
                                echo '<p style="margin-top:8px"><a href="' . htmlspecialchars($return_link2) . '">' . htmlspecialchars($return_link2) . '</a></p>';
                                echo '</div>';
                            } catch (Exception $e) {
                            }
                        }
                    }
                }
            } catch (Exception $e) {
            }
            if (!empty($t) && !empty($requested_role) && in_array($requested_role, ['admin', 'wali', 'security'])) {
                try {
                    mark_token_used($connection, $t, $requested_role, $token_action ?? '');
                } catch (Exception $e) {
                }
            }
            if (check_session_permission($connection, $level_id, $tugas_arr, 'security')) {
                $effective_role = $requested_role_post;
                if (empty($effective_role)) $effective_role = $requested_role;
                if (empty($konfirmasi_to_write) && !empty($token_action)) {
                    if ($token_action === 'exit') $konfirmasi_to_write = 'keluar';
                    if ($token_action === 'return') $konfirmasi_to_write = 'kembali';
                }
                if (!empty($konfirmasi_to_write) && $konfirmasi_to_write === 'keluar') {
                    try {
                        $newTok = bin2hex(random_bytes(4));
                        $up = $connection->prepare("UPDATE e_izin SET token_security = ? WHERE id = ? LIMIT 1");
                        if ($up) {
                            $up->bind_param('si', $newTok, $id);
                            $up->execute();
                            $up->close();
                        }
                        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
                        $host = $_SERVER['HTTP_HOST'];
                        $script_name = $_SERVER['SCRIPT_NAME'];
                        $saev4_root = '/' . explode('/', trim($script_name, '/'))[0];
                        $return_link = rtrim($protocol . '://' . $host . $saev4_root, '/') . '/admin/mod/e-izin/approve.php?t=' . $newTok . '&role=security&action=return';
                        $qr_img = rtrim($protocol . '://' . $host . $saev4_root, '/') . '/dashboard/mod/e-izin/qrcode.php?id=' . intval($id) . '&role=security&action=return';
                        echo '<div style="max-width:640px;margin:12px auto;padding:12px;border:1px solid #e9ecef;background:#fff;border-radius:8px;text-align:center">';
                        echo '<h5>Token Kembali Dibuat</h5>';
                        echo '<p>Token untuk proses <strong>kembali</strong> telah dibuat. Berikan QR atau link ini kepada siswa saat kembali.</p>';
                        echo '<div style="display:inline-block;padding:8px;border-radius:8px;background:#fafafa"><img src="' . htmlspecialchars($qr_img) . '" style="max-width:220px;display:block;margin:0 auto;" alt="QR Return"/></div>';
                        echo '<p style="margin-top:8px"><a href="' . htmlspecialchars($return_link) . '">' . htmlspecialchars($return_link) . '</a></p>';
                        echo '</div>';
                    } catch (Exception $e) {
                    }
                } elseif (!empty($konfirmasi_to_write) && $konfirmasi_to_write === 'pulang') {
                }
            }
            $chk = $connection->prepare("SELECT status_izin, status_izin_wali FROM e_izin WHERE id = ? LIMIT 1");
            $summary_ready = false;
            if ($chk) {
                $chk->bind_param('i', $id);
                $chk->execute();
                $res_chk = $chk->get_result();
                if ($res_chk && $res_chk->num_rows > 0) {
                    $rchk = $res_chk->fetch_assoc();
                    $s_admin = $rchk['status_izin'] ?? '';
                    $s_wali = $rchk['status_izin_wali'] ?? '';
                    if (strtolower($s_admin) === 'disetujui' && strtolower($s_wali) === 'disetujui') $summary_ready = true;
                }
                $chk->close();
            }
            if ($summary_ready && check_session_permission($connection, $level_id, $tugas_arr, 'admin')) {
                // Summary page (QR) removed per request — redirect to admin e-izin list instead.
                $script_name = $_SERVER['SCRIPT_NAME'];
                $saev4_root = '/' . explode('/', trim($script_name, '/'))[0];
                // If application is deployed at site root (first segment is 'admin'),
                // avoid duplicating '/admin' in the redirect path.
                $root_prefix = $saev4_root;
                if (trim($saev4_root, '/') === 'admin') $root_prefix = '';
                $dashboard_path = rtrim($root_prefix, '/') . '/admin/e-izin';
                // Try to close child window or redirect opener-aware, then fallback to redirect.
                echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Selesai</title><link rel="stylesheet" href="../../assets/css/bootstrap/bootstrap.min.css"></head><body class="p-4"><div class="container"><div class="card"><div class="card-body text-center"><p class="mb-3">Tindakan berhasil. Jendela akan ditutup otomatis.</p><p class="small text-muted">Jika tidak tertutup, Anda akan diarahkan kembali.</p></div></div></div>';
                $escaped = htmlspecialchars('http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $dashboard_path, ENT_QUOTES);
                echo '<script>(function(){try{if(window.opener&&!window.opener.closed){try{window.opener.postMessage({type:"eizin-close", id:"' . intval($id) . '"}, "*");}catch(e){} } }catch(e){}try{window.open("","_self");window.close();}catch(e){}setTimeout(function(){window.location.replace("' . $escaped . '");},900);})();</script>';
                echo '</body></html>';
                exit;
            } else {
                $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $script_name = $_SERVER['SCRIPT_NAME'];
                $saev4_root = '/' . explode('/', trim($script_name, '/'))[0];
                $root_prefix = $saev4_root;
                if (trim($saev4_root, '/') === 'admin') $root_prefix = '';
                $link_eizin = $protocol . '://' . $host . rtrim($root_prefix, '/') . '/admin/e-izin';
                echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Selesai</title><link rel="stylesheet" href="../../assets/css/bootstrap/bootstrap.min.css"></head><body class="p-4"><div class="container"><div class="card"><div class="card-body text-center"><p class="mb-3">Tindakan berhasil. Jendela akan ditutup otomatis.</p><p class="small text-muted">Jika tidak tertutup, Anda akan diarahkan kembali.</p></div></div></div>';
                $escaped = htmlspecialchars($link_eizin, ENT_QUOTES);
                echo '<script>(function(){function __tryClose(){try{if(window.opener&&!window.opener.closed){try{window.opener.postMessage({type:"eizin-close", id:"' . intval($id) . '"}, "*");}catch(e){} } }catch(e){}try{window.open("","_self");window.close();}catch(e){}try{if(window.opener&&!window.opener.closed){window.opener.focus();window.close();return;} }catch(e){}try{if(window.top&&window.top!==window){window.top.close();} }catch(e){}try{window.close();}catch(e){}window.location.replace("' . $escaped . '");}setTimeout(__tryClose,900);})();</script>';
                echo '</body></html>';
                exit;
            }
        } else {
            $error = 'Gagal memperbarui status izin.';
        }
    }
}

// Build canonical admin e-izin URL using site settings or server script root
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
$saev4_root = '/' . (explode('/', trim($script_name, '/'))[0] ?? '');
$root_prefix = $saev4_root;
if (trim($saev4_root, '/') === 'admin') $root_prefix = '';
if (!empty($site_url)) {
    $site_base = rtrim($site_url, '/');
} else {
    $site_base = rtrim($protocol . '://' . $host . $root_prefix, '/');
}
$admin_eizin_link = $site_base . '/admin/e-izin';

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Approval e-Izin Siswa - <?= htmlspecialchars($row['nama_lengkap']) ?></title>
    <link rel="icon" href="../../../content/<?= $site_favicon ?>" type="image/png">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700">
    <link rel="stylesheet" href="../../assets/vendor/nucleo/css/nucleo.css" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" type="text/css">
    <link rel="stylesheet" href="../../assets/css/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/vendor/select2/dist/css/select2.min.css">
    <link rel="stylesheet" href="../../assets/vendor/timepicker/bootstrap-timepicker.min.css">
    <link rel="stylesheet" href="../../assets/vendor/datatables.net-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="../../assets/vendor/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css">
    <link rel="stylesheet" href="../../assets/vendor/datatables.net-select-bs4/css/select.bootstrap4.min.css">
    <link rel="stylesheet" href="../../assets/vendor/Magnific-Popup/magnific-popup.css">
    <link rel="stylesheet" href="../../assets/vendor/leatfet/leaflet.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/argon.css" type="text/css">
    <link rel="stylesheet" href="../../assets/vendor/viewerjs/viewer.min.css" />
</head>

<body class="approve-eizin-bg">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10 col-sm-12">
                <div class="card approve-eizin-card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0 text-white"><i class="fas fa-user-check mr-2"></i>Approval e-Izin Siswa</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)) : ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-triangle mr-2"></i><?= htmlspecialchars($error) ?>
                                <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span></button>
                            </div>
                        <?php endif; ?>
                        <div class="student-info">
                            <div class="d-flex align-items-center flex-column flex-md-row mb-3">
                                <?php
                                $avatar_nisn = "../../../content/avatar/{$row['nisn']}.png";
                                $default_avatar = "../../../content/avatar/avatar.jpg";
                                $avatar_path = file_exists($avatar_nisn) ? $avatar_nisn : $default_avatar;
                                $bg = "background-image: url('" . htmlspecialchars($avatar_path, ENT_COMPAT) . "')";
                                ?>
                                <div class="card-profile-image has-bg mb-3 mb-md-0 mr-md-4" role="img" aria-label="Avatar <?= htmlspecialchars($row['nama_lengkap']) ?>" style="<?= $bg ?>"></div>
                                <div class="flex-grow-1 text-center text-md-left">
                                    <h5 class="mb-1 font-weight-bold text-primary"><?= htmlspecialchars($row['nama_lengkap']) ?></h5>
                                    <span class="text-muted"><i class="fas fa-calendar-alt mr-1"></i><?= date('d M Y', strtotime($row['tanggal'])) ?></span>
                                </div>
                                <?php
                                // Determine who the approval is pending for (petugas/admin or wali kelas)
                                $cs = strtolower(trim($current_status ?? ''));
                                $cw = strtolower(trim($current_status_wali ?? ''));
                                $pending_for = '';
                                if ($cs === 'menunggu' && $cw === 'menunggu') {
                                    $pending_for = 'Petugas & Wali Kelas';
                                } elseif ($cs === 'menunggu') {
                                    $pending_for = 'Petugas';
                                } elseif ($cw === 'menunggu') {
                                    $pending_for = 'Wali Kelas';
                                }

                                // Decide which status value to show in the main badge.
                                // When the approver is acting as wali, show the wali-specific status from DB.
                                $status_for_badge = $current_status;
                                if (!empty($requested_role) && $requested_role === 'wali') {
                                    $status_for_badge = $current_status_wali;
                                }
                                // Fallback: if badge should reflect overall approval (both approved), prefer admin status
                                $sb = strtolower(trim($status_for_badge));
                                $badgeClass = 'warning';
                                if ($sb === 'disetujui') $badgeClass = 'success';
                                elseif ($sb === 'ditolak') $badgeClass = 'danger';
                                $badgeText = htmlspecialchars($status_for_badge ?: ($pending_for ? 'Menunggu' : ''));
                                if ($pending_for) {
                                    if ($badgeText !== '') $badgeText .= ' • ' . $pending_for;
                                    else $badgeText = 'Menunggu • ' . $pending_for;
                                }
                                ?>
                                <div class="text-center text-md-right mt-3 mt-md-0">
                                    <span class="badge badge-<?= $badgeClass ?> badge-lg"><?= $badgeText ?></span>
                                </div>
                            </div>
                            <div class="border-top pt-3">
                                <h6 class="text-primary mb-3"><i class="fas fa-clipboard-list mr-2"></i>Detail Permohonan</h6>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="info-row">
                                            <div class="info-label"><i class="fas fa-tag mr-1"></i>Jenis Izin:</div>
                                            <div class="info-value font-weight-bold"><?= htmlspecialchars($row['jenis_izin']) ?></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label"><i class="fas fa-calendar mr-1"></i>Tanggal Izin:</div>
                                            <div class="info-value"><?= date('d M Y', strtotime($row['tanggal'])) ?></div>
                                        </div>
                                        <div class="info-row" style="align-items:flex-start;">
                                            <div class="info-label"><i class="fas fa-file-alt mr-1"></i>Keterangan:</div>
                                            <div class="info-value" id="keteranganText">
                                                <div class="keterangan"><?= nl2br(htmlspecialchars($row['keterangan'] ?? '')) ?></div>
                                            </div>
                                        </div>
                                        <?php if (!empty($current_catatan)) : ?>
                                            <div class="info-row">
                                                <div class="info-label"><i class="fas fa-comment-dots mr-1"></i>Alasan Penolakan:</div>
                                                <div class="info-value"><?= nl2br(htmlspecialchars($current_catatan)) ?></div>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($current_catatan_wali)) : ?>
                                            <div class="info-row">
                                                <div class="info-label"><i class="fas fa-user-shield mr-1"></i>Alasan Wali Kelas:</div>
                                                <div class="info-value"><?= nl2br(htmlspecialchars($current_catatan_wali)) ?></div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <form id="approveForm" method="post" style="width:100%">
                            <input type="hidden" name="id" id="approve_id" value="<?= intval($id) ?>">
                            <input type="hidden" name="status_izin" id="status_izin" value="">
                            <input type="hidden" name="konfirmasi" id="konfirmasi" value="">
                            <div id="catatanGroup" class="form-group mb-3" style="display:none;">
                                <label class="font-weight-bold"><i class="fas fa-comment-alt mr-2 text-primary"></i>Catatan <span class="text-danger">(wajib jika menolak)</span></label>
                                <textarea name="alasan_penolakan" id="alasan_penolakan" class="form-control" rows="4" placeholder="Masukkan catatan atau alasan penolakan..."></textarea>
                            </div>
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch gap-2">
                                <?php
                                $show_security_controls = false;
                                if ($requested_role === 'security') $show_security_controls = true;
                                if (empty($token_flow) && check_session_permission($connection, $level_id, $tugas_arr, 'security')) $show_security_controls = true;

                                if ($show_security_controls) :
                                    $jenis = strtolower(trim($row['jenis_izin'] ?? ''));
                                    $is_return_mode = (!empty($token_action) && $token_action === 'return') || (strtolower($current_konfirmasi) === 'keluar');
                                    if ($jenis === 'pulang') :
                                        if ($is_return_mode) : ?>
                                            <button type="button" class="btn btn-primary btn-lg mb-2 mb-md-0" id="btnKembali"><i class="fas fa-undo mr-2"></i>Kembali</button>
                                            <a href="<?= htmlspecialchars($admin_eizin_link) ?>" class="btn btn-secondary btn-lg"><i class="fas fa-arrow-left mr-2"></i>Kembali ke e-Izin</a>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-success btn-lg mb-2 mb-md-0" id="btnPulang"><i class="fas fa-sign-out-alt mr-2"></i>Pulang</button>
                                            <a href="<?= htmlspecialchars($admin_eizin_link) ?>" class="btn btn-secondary btn-lg"><i class="fas fa-arrow-left mr-2"></i>Kembali ke e-Izin</a>
                                        <?php endif;
                                    else:
                                        if ($is_return_mode) : ?>
                                            <button type="button" class="btn btn-primary btn-lg mb-2 mb-md-0" id="btnKembali"><i class="fas fa-undo mr-2"></i>Kembali</button>
                                            <a href="<?= htmlspecialchars($admin_eizin_link) ?>" class="btn btn-secondary btn-lg"><i class="fas fa-arrow-left mr-2"></i>Kembali ke e-Izin</a>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-warning btn-lg mb-2 mb-md-0" id="btnKeluar"><i class="fas fa-door-open mr-2"></i>Keluar</button>
                                            <a href="<?= htmlspecialchars($admin_eizin_link) ?>" class="btn btn-secondary btn-lg"><i class="fas fa-arrow-left mr-2"></i>Kembali ke e-Izin</a>
                                    <?php endif;
                                    endif;
                                else: ?>
                                    <button type="button" class="btn btn-success btn-lg mb-2 mb-md-0" id="btnSetujui"><i class="fas fa-check mr-2"></i>Setujui</button>
                                    <button type="button" class="btn btn-danger btn-lg mb-2 mb-md-0" id="btnTolak"><i class="fas fa-times mr-2"></i>Tolak & Isi Catatan</button>
                                    <a href="<?= htmlspecialchars($admin_eizin_link) ?>" class="btn btn-secondary btn-lg"><i class="fas fa-arrow-left mr-2"></i>Kembali ke e-Izin</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../../assets/vendor/jquery/dist/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/vendor/js-cookie/js.cookie.js"></script>
    <script src="../../assets/vendor/jquery.scrollbar/jquery.scrollbar.min.js"></script>
    <script src="../../assets/vendor/jquery-scroll-lock/dist/jquery-scrollLock.min.js"></script>
    <script src="../../assets/vendor/select2/dist/js/select2.min.js"></script>
    <script src="../../assets/vendor/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
    <script src="../../assets/vendor/timepicker/bootstrap-timepicker.js"></script>
    <script src="../../assets/vendor/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="../../assets/vendor/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="../../assets/vendor/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
    <script src="../../assets/vendor/datatables.net-buttons-bs4/js/buttons.bootstrap4.min.js"></script>
    <script src="../../assets/vendor/datatables.net-buttons/js/buttons.html5.min.js"></script>
    <script src="../../assets/vendor/datatables.net-buttons/js/buttons.flash.min.js"></script>
    <script src="../../assets/vendor/datatables.net-buttons/js/buttons.print.min.js"></script>
    <script src="../../assets/vendor/datatables.net-select/js/dataTables.select.min.js"></script>
    <script src="../../assets/vendor/Magnific-Popup/jquery.magnific-popup.min.js"></script>
    <script src="../../assets/js/jquery.validate.min.js"></script>
    <script src="../../assets/js/sweetalert.min.js"></script>
    <script src="../../assets/vendor/viewerjs/viewer.min.js"></script>
    <script src="../../assets/js/argon.js"></script>
    <script src="../../assets/vendor/leatfet/leaflet.js"></script>
    <script src="../../assets/js/demo.js"></script>
    <script src="../../mod/e-izin/scripts.js"></script>

</body>

</html>

<?php
$stmt->close();
exit;
?>