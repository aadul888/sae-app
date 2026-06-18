<?php
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 5;
  include __DIR__ . '/../check_role.php';

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

<!-- Page Content -->
<div class="container-fluid mt--6 user-module-page">';

      if ($data_role['lihat'] == 'Y') {
        echo '
  <div class="row">
    <div class="col-12">
      <div class="card user-stats-panel mb-3">
        <div class="card-body py-2 px-2 px-md-3">
          <div class="user-stats-wrap">
            <div class="user-stats" id="berkas-stat-row">
              <div class="user-stat-card user-stat-total">
                <div class="info">
                  <span class="label">Total</span>
                  <span class="value" id="berkas-stat-total">0</span>
                </div>
                <div class="icon"><i class="fas fa-database"></i></div>
              </div>
              <div class="user-stat-card user-stat-berkas-valid">
                <div class="info">
                  <span class="label">Valid</span>
                  <span class="value text-success" id="berkas-stat-valid">0</span>
                </div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
              </div>
              <div class="user-stat-card user-stat-belum-sesuai">
                <div class="info">
                  <span class="label">Tidak Valid</span>
                  <span class="value text-danger" id="berkas-stat-tidak">0</span>
                </div>
                <div class="icon"><i class="fas fa-times-circle"></i></div>
              </div>
              <div class="user-stat-card user-stat-belum">
                <div class="info">
                  <span class="label">Perlu Revisi</span>
                  <span class="value text-warning" id="berkas-stat-revisi">0</span>
                </div>
                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
              </div>
              <div class="user-stat-card user-stat-berkas-belum">
                <div class="info">
                  <span class="label">Belum Divalidasi</span>
                  <span class="value text-muted" id="berkas-stat-belum">0</span>
                </div>
                <div class="icon"><i class="fas fa-clock"></i></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card user-table-panel pb-2">
    <div class="card-header py-3 px-3 user-table-header">
      <div class="user-table-head-row" style="gap:10px;">
        <div>
          <h4 class="mb-1">Berkas Persyaratan Siswa</h4>
          <small class="text-muted">Kelola dan validasi berkas persyaratan siswa.</small>
        </div>
        <div class="user-toolbar-actions user-toolbar-actions-table">
          ';
        if (isset($level_id) && intval($level_id) === 1) {
          echo '<select class="form-control filter-kelas d-inline-block" style="width:auto;min-width:140px;">'
            . '<option value="">Semua Kelas</option>';
          $query_kelas = "SELECT * FROM kelas ORDER BY nama_kelas ASC";
          $result_kelas = $connection->query($query_kelas);
          while ($data_kelas = $result_kelas->fetch_assoc()) {
            echo '<option value="' . $data_kelas['kelas_id'] . '">' . $data_kelas['nama_kelas'] . '</option>';
          }
          echo '</select>';
        } else {
          echo '<input type="hidden" class="filter-kelas" value="">';
        }
        echo '
        </div>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table align-items-center table-flush table-striped datatable-berkas" style="width:100%">
        <thead class="thead-light">
          <tr>
            <th class="text-center" style="width:10px;">No</th>
            <th class="text-center">NISN</th>
            <th class="text-center">Nama Lengkap</th>
            <th class="text-center">Kelas</th>
            <th class="text-center">KK</th>
            <th class="text-center">Ijazah</th>
            <th class="text-center">Akte</th>
            <th class="text-center">KIP</th>
            <th class="text-center">KKS</th>
            <th class="text-center">KIS</th>
            <th class="text-center">Status</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>';
        // Modal Preview Berkas (gambar/PDF)
        echo '
  <!-- Modal Preview Berkas -->
  <div class="modal fade" id="modalPreviewBerkas" tabindex="-1" role="dialog" aria-labelledby="modalPreviewBerkasLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalPreviewBerkasLabel">Preview Berkas</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body text-center" id="modalPreviewBerkasBody" style="min-height:400px;max-height:80vh;overflow:auto;"></div>
      </div>
    </div>
  </div>

  <!-- Modal Lihat Semua Berkas -->
  <div class="modal fade" id="modalLihatSemuaBerkas" tabindex="-1" role="dialog" aria-labelledby="modalLihatSemuaBerkasLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="max-width:95%;margin:1rem auto;">
      <div class="modal-content" style="height:90vh;">
        <div class="modal-header">
          <h5 class="modal-title" id="modalLihatSemuaBerkasLabel">Semua Berkas Siswa</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body" id="modalLihatSemuaBerkasBody" style="overflow-y:auto;height:calc(90vh - 60px);">
          <!-- Content akan diisi via JavaScript -->
        </div>
      </div>
    </div>
  </div>
  ';
        // Upload modal removed
      } else {
        hak_akses();
      }
      echo '</div> <!-- End container-fluid -->
</div>';
      break;
  }
}
