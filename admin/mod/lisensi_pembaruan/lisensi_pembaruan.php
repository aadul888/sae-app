<?PHP
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 133;
  include __DIR__ . '/../check_role.php';
  if ($has_access) {

    // Early permission guard: allow only users with 'modifikasi' permission
    if (!isset($data_role['modifikasi']) || $data_role['modifikasi'] != 'Y') {
      if (function_exists('theme_404')) {
        theme_404();
      } else {
        header("HTTP/1.0 404 Not Found");
        echo '<div class="container mt-5"><h3>404 Not Found</h3><p>Anda tidak memiliki hak akses untuk melihat halaman ini.</p></div>';
      }
      exit;
    }

    $license_validation = $_SESSION['license_validation'] ?? null;
    unset($_SESSION['license_validation']);

    $deploy_result = $_SESSION['deploy_result'] ?? null;
    unset($_SESSION['deploy_result']);
    // unset($_SESSION['api_debug_log']);

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

<div class="container-fluid mt--6 user-module-page">
  ' . (isset($_GET['status']) && $_GET['status'] === 'ok' ? '<div class="alert alert-success alert-dismissible fade show" role="alert"><i class="fas fa-check-circle mr-1"></i> Lisensi berhasil disimpan.<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>' : '') . '
  ' . (!empty($license_validation['message']) && empty($license_validation['valid']) ? '<div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="fas fa-times-circle mr-1"></i> Validasi: ' . htmlspecialchars($license_validation['message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>' : '') . '
  ' . (!empty($deploy_result['success']) ? '<div class="alert alert-success alert-dismissible fade show" role="alert"><i class="fas fa-check-circle mr-1"></i> ' . htmlspecialchars($deploy_result['message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>' : '') . '
  ' . (!empty($deploy_result['error']) ? '<div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="fas fa-times-circle mr-1"></i> ' . htmlspecialchars($deploy_result['error']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>' : '') . '
  <div class="row">
    <div class="col">
      <div class="card shadow">
        <div class="card-header py-3 px-3 module-table-header">
          <div class="module-header-row">
            <div class="text-center flex-grow-1">
              <h4 class="mb-1"><i class="fas fa-key text-primary mr-2"></i>Lisensi &amp; Pembaruan</h4>
              <small class="text-muted">Kelola lisensi aplikasi dan pembaruan sistem</small>
            </div>
          </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="card-body pb-0">
          <ul class="nav nav-pills nav-fill flex-column flex-md-row tab-responsive" id="tabs-icons-text" role="tablist">
            <li class="nav-item">
              <a class="nav-link mb-sm-3 mb-md-0 active" href="#tabs-license" onclick="loadTab(1);" role="tab" aria-controls="tabs-license" aria-selected="true">
                <i class="fas fa-key mr-2"></i>
                Informasi Lisensi
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link mb-sm-3 mb-md-0" href="#tabs-updates" onclick="loadTab(2);" role="tab" aria-controls="tabs-updates" aria-selected="false">
                <i class="fas fa-sync-alt mr-2"></i>
                Status Pembaruan
              </a>
            </li>
          </ul>
        </div>

        <div class="card-body">
          <div class="tab-content" id="myTabContent">
            <div class="load-form">
              <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                  <span class="sr-only">Loading...</span>
                </div>
                <p class="text-muted mt-3">Memuat...</p>
              </div>
            </div>
          </div>
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
