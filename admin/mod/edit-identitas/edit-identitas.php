<?php
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
  header('location:./login');
  exit;
} else {
  $modul_id = 6;
  include __DIR__ . '/../check_role.php';
  if ($has_access) {

    switch (@$_GET['op']) {
      default:
        echo '
      <script>
        window.BERKAS_PATH = "../../content/berkas/";
        document.body.classList.add("page-user-module", "page-edit-identitas-module");
      </script>

<!-- Header -->
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
    <div class="col-12">
      <div class="card user-stats-panel module-stats-shell mb-3">
        <div class="card-body py-2 px-2 px-md-3">
          <div class="user-stats-wrap">
            <div class="user-stats" id="statistik-status-row">
              <div class="user-stat-card user-stat-total">
                <div class="info">
                  <span class="label">Total</span>
                  <span class="value" id="stat-total">0</span>
                </div>
                <div class="icon"><i class="fas fa-database"></i></div>
              </div>
              <div class="user-stat-card user-stat-berkas-valid">
                <div class="info">
                  <span class="label">Disetujui</span>
                  <span class="value" id="stat-disetujui">0</span>
                </div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
              </div>
              <div class="user-stat-card user-stat-belum-sesuai">
                <div class="info">
                  <span class="label">Ditolak</span>
                  <span class="value" id="stat-ditolak">0</span>
                </div>
                <div class="icon"><i class="fas fa-times-circle"></i></div>
              </div>
              <div class="user-stat-card user-stat-identitas">
                <div class="info">
                  <span class="label">Berhasil Dikirim</span>
                  <span class="value" id="stat-berhasil">0</span>
                </div>
                <div class="icon"><i class="fas fa-paper-plane"></i></div>
              </div>
              <div class="user-stat-card user-stat-belum">
                <div class="info">
                  <span class="label">Dalam Proses</span>
                  <span class="value" id="stat-proses">0</span>
                </div>
                <div class="icon"><i class="fas fa-spinner"></i></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card user-table-panel module-table-card pb-2">
    <div class="card-header py-3 px-3 user-table-header">
      <div class="user-table-head-row" style="gap:10px;">
        <div>
          <h4 class="mb-1">Daftar Ajuan Perubahan Data</h4>
          <small class="text-muted">Kelola dan proses ajuan perubahan identitas siswa.</small>
        </div>
        <div class="user-toolbar-actions user-toolbar-actions-table">';
        $status_file = __DIR__ . '/status.json';
        $usulan_closed = false;
        if (file_exists($status_file)) {
          $c = @file_get_contents($status_file);
          $j = json_decode($c, true);
          if (is_array($j) && !empty($j['closed'])) $usulan_closed = true;
        }
        if (isset($_COOKIE['ADMIN_KEY'])) {
          $btnClass = $usulan_closed ? 'btn btn-danger btn-sm' : 'btn btn-success btn-sm';
          $btnHtml = $usulan_closed ? '<i class="fas fa-lock me-1"></i> Buka Usulan' : '<i class="fas fa-unlock me-1"></i> Tutup Usulan';
          echo '<button id="toggle-usulan-btn" data-closed="' . ($usulan_closed ? '1' : '0') . '" class="' . $btnClass . '">' . $btnHtml . '</button>';
        }
        echo '
        </div>
      </div>
    </div>
    <div class="table-responsive">';
        echo "<script>
        (function(){
          try{
            var btn = document.getElementById('toggle-usulan-btn');
            if(!btn) return;
            if(btn.__bound) return;
            btn.__bound = true;
            btn.addEventListener('click', function(e){
              e.preventDefault();
              var closed = btn.getAttribute('data-closed') === '1';
              var newClosed = closed ? 0 : 1;
              var xhr = new XMLHttpRequest();
              xhr.open('POST','./mod/edit-identitas/proses.php?action=toggle_status',true);
              xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
              xhr.onreadystatechange = function(){
                if(xhr.readyState === 4){
                  try{
                    var resp = JSON.parse(xhr.responseText);
                    if(resp && typeof resp.closed !== 'undefined'){
                      btn.setAttribute('data-closed', resp.closed ? '1' : '0');
                      btn.className = resp.closed ? 'btn btn-danger btn-sm' : 'btn btn-success btn-sm';
                      btn.innerHTML = resp.closed ? '<i class=\"fas fa-lock me-1\"></i> Buka Usulan' : '<i class=\"fas fa-unlock me-1\"></i> Tutup Usulan';
                    } else {
                      alert('Gagal menyimpan status');
                    }
                  } catch(e){
                    console.error('Toggle parse error', e, xhr.responseText);
                    alert('Terjadi kesalahan response');
                  }
                }
              };
              xhr.send('closed=' + encodeURIComponent(newClosed));
            });
          } catch(e) { console.error('Toggle init error', e); }
        })();
        </script>";

        // Stats ditampilkan di panel atas (user-stats-panel); tidak perlu lagi di sini
        if ($data_role['lihat'] == 'Y') {
          echo '
          <table class="table align-items-center table-flush table-striped datatable" width="auto">
            <thead class="thead-light">
              <tr>
                <th class="text-center" width="4">No</th>
                <th class="text-center" width="5">NISN</th>
                <th class="text-center" >Nama Lengkap</th>
                <th class="text-center" >Kelas</th>
                <th class="text-center" >Tanggal Ajuan</th>
                <th class="text-center" >Tanggal Proses</th>
                <th class="text-center" >Catatan</th>
                <th class="text-center" >Status</th>
                <th class="text-center" >Aksi</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>';
        } else {
          hak_akses();
        }
        echo '
        </div>
      </div>
  
  <!-- Modal Detail Perubahan -->
  <div class="modal fade modal-detail" id="modal-detail" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="detailModalLabel">
            <i class="fas fa-edit mr-2"></i>Detail Perubahan Data
          </h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div id="detail-content">
            <div class="loading-container">
              <div class="loading-spinner"></div>
              <p>Memuat data...</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <div class="modal fade" id="modal-edit-catatan" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form id="form-edit-catatan">
        <div class="modal-header">
          <h5 class="modal-title">Edit Catatan Penolakan</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="edit-id">
          <textarea class="form-control" id="edit-catatan" rows="4" required></textarea>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        </div>
      </form>
    </div>
  </div>

</div>

</div> <!-- End container-fluid -->
';

        break;
    }
  } else {
    theme_404();
  }
}
