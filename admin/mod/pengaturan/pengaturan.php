<?PHP
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 37;
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

    switch (@$_GET['op']) {
      default:
        echo '
<!-- Header -->
<link rel="stylesheet" href="./mod/pengaturan/style.css">
<div class="header bg-primary pb-4 user-page-header-compact">
      <div class="container-fluid">
        <div class="header-body">
          <div class="row align-items-center py-3"></div>
        </div>
      </div>
    </div>
    
    <!-- Page content -->
    <div class="container-fluid mt--6 user-module-page">
      <div class="row">
        <div class="col">
          <div class="card shadow">
            <div class="card-header py-3 px-3 module-table-header">
              <div class="module-header-row">
                <div class="text-center flex-grow-1">
                  <h4 class="mb-1"><i class="fas fa-sliders-h text-primary mr-2"></i>Panel Pengaturan</h4>
                  <small class="text-muted">Kelola konfigurasi dan pengaturan sistem</small>
                </div>
              </div>
            </div>
            
            <!-- Navigation Tabs -->
            <div class="card-body pb-0">
              <ul class="nav nav-pills nav-fill flex-column flex-md-row tab-responsive" id="tabs-icons-text" role="tablist">
                <li class="nav-item">
                  <a class="nav-link mb-sm-3 mb-md-0 active" href="#tabs-web" onclick="loadSetting(1);" role="tab" aria-controls="tabs-web" aria-selected="true">
                    <i class="fas fa-globe mr-2"></i>
                    Pengaturan Web
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link mb-sm-3 mb-md-0" href="#tabs-menu" onclick="loadSetting(2);" role="tab" aria-controls="tabs-menu" aria-selected="false">
                    <i class="fas fa-plus-circle mr-2"></i>
                    Tambah Menu
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link mb-sm-3 mb-md-0" href="#tabs-email" onclick="loadSetting(3);" role="tab" aria-controls="tabs-email" aria-selected="false">
                    <i class="fas fa-envelope mr-2"></i>
                    Server Email
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link mb-sm-3 mb-md-0" href="#tabs-backup" onclick="loadSetting(4);" role="tab" aria-controls="tabs-backup" aria-selected="false">
                    <i class="fas fa-download mr-2"></i>
                    Backup Data
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link mb-sm-3 mb-md-0" href="#tabs-whatsapp" onclick="loadSetting(7);" role="tab" aria-controls="tabs-whatsapp" aria-selected="false">
                    <i class="fab fa-whatsapp mr-2"></i>
                    WhatsApp Gateway
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link mb-sm-3 mb-md-0" href="#tabs-maintenance" onclick="loadSetting(8);" role="tab" aria-controls="tabs-maintenance" aria-selected="false">
                    <i class="fas fa-tools mr-2"></i>
                    Maintenance
                  </a>
                </li>
              </ul>
            </div>';

        if ($data_role['modifikasi'] == 'Y') {
          echo '
                <div class="card-body">
                  <div class="tab-content" id="myTabContent">
                    <div class="load-form">
                      <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                          <span class="sr-only">Loading...</span>
                        </div>
                        <p class="text-muted mt-3">Memuat pengaturan...</p>
                      </div>
                    </div>
                  </div>
                </div>';
        } else {
          // show 404 theme when user doesn't have view or modify permission
          if (function_exists('theme_404')) {
            theme_404();
          } else {
            header("HTTP/1.0 404 Not Found");
            echo '<div class="container mt-5"><h3>404 Not Found</h3><p>Anda tidak memiliki hak akses untuk melihat halaman ini.</p></div>';
            exit;
          }
        }
        echo '
          </div>
        </div>
      </div>';
        break;
    }
  } else {
    theme_404();
  }
}
