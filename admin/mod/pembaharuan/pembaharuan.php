<?PHP
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 40;
  include __DIR__ . '/../check_role.php';
  if ($has_access) {

    // Ambil statistik deploy dari setting
    $deploy_count = 0;
    $last_deploy_at = '';
    $last_deploy_by = '-';
    if (isset($connection) && $connection) {
      $q_stat = $connection->query("SELECT deploy_count, last_deploy_at, last_deploy_by FROM setting WHERE site_id=1 LIMIT 1");
      if ($q_stat && $r_stat = $q_stat->fetch_assoc()) {
        $deploy_count = (int)($r_stat['deploy_count'] ?? 0);
        $last_deploy_at = $r_stat['last_deploy_at'] ?? '';
        $last_deploy_by = $r_stat['last_deploy_by'] ?? '-';
      }
    }

    switch (@$_GET['op']) {
      default:
        echo '
<div class="header bg-primary pb-4 user-page-header-compact">
  <div class="container-fluid">
    <div class="header-body">
      <div class="row align-items-center py-3"></div>
    </div>
  </div>
</div>
<div class="container-fluid mt--6 user-module-page module-user-like-page">
  <div class="row">
    <div class="col">
      <!-- Statistik deploy -->
      <div class="row mb-3">
        <div class="col-md-4">
          <div class="card shadow-sm">
            <div class="card-body py-3">
              <div class="media align-items-center">
                <div class="media-body">
                  <span class="text-muted text-sm">Total Deploy</span>
                  <h4 class="mb-0 font-weight-bold" id="deploy-count">' . ((int)($deploy_count ?? 0)) . '</h4>
                </div>
                <div class="icon icon-shape bg-primary text-white rounded-circle shadow">
                  <i class="fas fa-rocket"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card shadow-sm">
            <div class="card-body py-3">
              <div class="media align-items-center">
                <div class="media-body">
                  <span class="text-muted text-sm">Terakhir Deploy</span>
                  <h6 class="mb-0 font-weight-bold">' . (!empty($last_deploy_at) ? tgl_indo($last_deploy_at) . ' ' . jam_indo($last_deploy_at) : '-') . '</h6>
                </div>
                <div class="icon icon-shape bg-success text-white rounded-circle shadow">
                  <i class="fas fa-clock"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card shadow-sm">
            <div class="card-body py-3">
              <div class="media align-items-center">
                <div class="media-body">
                  <span class="text-muted text-sm">Oleh</span>
                  <h6 class="mb-0 font-weight-bold">' . htmlspecialchars($last_deploy_by ?? '-') . '</h6>
                </div>
                <div class="icon icon-shape bg-info text-white rounded-circle shadow">
                  <i class="fas fa-user"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card shadow-sm mb-3">
        <div class="card-body">
          <div class="row align-items-center">
            <div class="col-md-6">
              <h5 class="mb-1"><i class="fas fa-sync-alt text-primary mr-2"></i>Periksa Pembaruan</h5>
              <small class="text-muted">Cek versi terbaru dan lakukan update aplikasi.</small>
            </div>
            <div class="col-md-6 text-md-right mt-3 mt-md-0">
              <button type="button" class="btn btn-primary" id="btn-check-update" onclick="checkUpdateNow()">
                <i class="fas fa-search mr-1"></i> Cek Pembaruan
              </button>
              <button type="button" class="btn btn-success d-none" id="btn-deploy" onclick="doDeployNow()">
                <i class="fas fa-download mr-1"></i> Proses Update
              </button>
            </div>
          </div>
          <div id="update-status-area" class="mt-3"></div>
          <div id="deploy-log-area" class="mt-3" style="display:none;">
            <div class="card bg-dark text-white">
              <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <small><i class="fas fa-terminal mr-1"></i> Log Update</small>
                <button type="button" class="btn btn-sm btn-outline-light" id="btn-copy-deploy-log" onclick="copyDeployLog()"><i class="fas fa-copy mr-1"></i>Salin Log</button>
              </div>
              <div class="card-body py-2">
                <pre id="deploy-log" class="mb-0" style="max-height:420px;overflow-y:auto;font-size:12px;color:#0f0;white-space:pre-wrap;user-select:text;"></pre>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Changelog -->
      <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
          <h5 class="mb-0"><i class="fas fa-history text-info mr-2"></i>Changelog</h5>
        </div>
        <div class="card-body p-3">
';
require_once __DIR__ . '/../../../library/version.php';
$changelog_entries = [];
if (isset($connection) && $connection) {
  $q_cl = $connection->query("SELECT commit_hash, commit_message_bahasa, commit_message, author, committed_at, created_at FROM commit_log ORDER BY IFNULL(committed_at, created_at) DESC LIMIT 50");
  if ($q_cl) {
    while ($r = $q_cl->fetch_assoc()) {
      $changelog_entries[] = $r;
    }
  }
}
if (!empty($changelog_entries)):
  echo '<div class="changelog-render">';
  $current_date = '';
  foreach ($changelog_entries as $entry):
    // Ambil pesan: utamakan bahasa Indonesia, fallback ke English
    $raw = !empty($entry['commit_message_bahasa']) ? $entry['commit_message_bahasa'] : $entry['commit_message'];
    // Bersihkan artefak raw git log: ---HASH: ---AUTHOR: ---DATE: ---SUBJECT: ---BODY:
    $raw = preg_replace('/---[A-Z]+:.*?(?=---|$)/s', '', $raw);
    $raw = trim($raw);
    if (empty($raw)) continue;
    // Ambil hanya baris pertama jika multi-line
    $msg = strtok($raw, "\n");
    // Fallback: jika committed_at NULL/invalid, gunakan created_at
    $ts = $entry['committed_at'] ?: ($entry['created_at'] ?? null);
    $date = '-';
    if ($ts) {
        $dt = strtotime($ts);
        if ($dt !== false && $dt > 0) $date = date('d M Y', $dt);
    }
    // Tentukan label: Perbaikan atau Baru
    $msg_lower = strtolower($msg . ' ' . ($entry['commit_message'] ?? ''));
    $is_fix = preg_match('/\b(fix|fixed|fixes|bug|hotfix|perbaikan|kesalahan|koreksi|error|wrong|typo|issue)\b/i', $msg_lower);
    $label = $is_fix ? 'Perbaikan' : 'Baru';
    $label_class = $is_fix ? 'text-warning' : 'text-success';
    // Hitung versi dari commit hash
    $hash = $entry['commit_hash'] ?? '';
    $version = (!empty($hash) && strlen($hash) >= 7) ? sae_version_from_commit($connection, $hash) : '';
    if ($date !== $current_date):
      if ($current_date !== '') echo '</div></div>';
      $current_date = $date;
      $header_version = $version ? htmlspecialchars($version) : '';
      echo '<div class="mb-3">';
      echo '<h6 class="font-weight-bold text-primary mb-1">' . $header_version . ' <span class="text-muted font-weight-normal">' . htmlspecialchars($date) . '</span></h6>';
      echo '<div class="pl-2" style="border-left:3px solid #e9ecef;">';
    endif;
    echo '<div class="mb-2 small d-flex align-items-center">';
    echo '<span class="' . $label_class . ' font-weight-bold mr-2">' . $label . '</span>';
    echo '<span>' . htmlspecialchars($msg) . '</span>';
    echo '</div>';
  endforeach;
  if ($current_date !== '') echo '</div></div>';
  echo '</div>';
else:
  echo '<div class="text-center text-muted py-4">Belum ada riwayat pembaruan.</div>';
endif;
echo '
        </div>
      </div>
    </div>
  </div>
</div>';
        break;
    }
  } else {
    theme_404();
  }
}
?>
