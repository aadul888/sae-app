<?PHP
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 41;
  include __DIR__ . '/../check_role.php';
  if ($has_access) {

    switch (@$_GET['op']) {
      default:
        echo '
<!-- Header -->
<div class="header bg-primary pb-4 user-page-header-compact">
      <div class="container-fluid">
        <div class="header-body">
          <div class="row align-items-center py-3"></div>
        </div>
      </div>
    </div>
    
    <!-- Page content -->
    <div class="container-fluid mt--6 user-module-page sync-module-page">
      <div class="row">
        <div class="col">
          <div class="card shadow">
            <!-- Card header -->
            <div class="card-header bg-white border-0">
              <div class="row align-items-center">
                <div class="col">
                  <h3 class="mb-0">
                    <i class="fas fa-sliders-h text-primary mr-2"></i>
                    Tarik Data Dapodik
                  </h3>
                  <p class="text-muted mb-0">Kelola koneksi Dapodik dan proses penarikan data langsung</p>
                </div>
              </div>
            </div>
            

            <!-- Navigation Tabs: Koneksi dan Data Masuk -->
            <div class="card-body pb-0">
              <ul class="nav nav-pills nav-fill flex-column flex-md-row tab-responsive" id="tabs-icons-text" role="tablist">
                <li class="nav-item">
                  <a class="nav-link mb-sm-3 mb-md-0 active" href="#tabs-koneksi" onclick="loadSetting(7);" role="tab" aria-controls="tabs-koneksi" aria-selected="true">
                    <i class="fas fa-network-wired mr-2"></i>
                    Koneksi
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link mb-sm-3 mb-md-0" href="#tabs-tarikdata" onclick="loadSetting(8);" role="tab" aria-controls="tabs-tarikdata" aria-selected="false">
                    <i class="fas fa-download mr-2"></i>
                    Tarik Data
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link mb-sm-3 mb-md-0" href="#tabs-kirimdata" onclick="loadSetting(9);" role="tab" aria-controls="tabs-kirimdata" aria-selected="false">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Kirim Data
                  </a>
                </li>
              </ul>
            </div>';

        if ($data_role['lihat'] == 'Y' or $data_role['modifikasi'] == 'Y') {
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
          echo '
                <div class="card-body">
                  <div class="alert alert-warning" role="alert">
                    <span class="alert-inner--icon"><i class="ni ni-notification-70"></i></span>
                    <span class="alert-inner--text">
                      <strong>Akses Terbatas!</strong> 
                      Anda tidak memiliki hak akses untuk melihat halaman ini.
                    </span>
                  </div>
                </div>';
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
